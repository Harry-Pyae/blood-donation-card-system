<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\BloodUnit;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\DonorScreening;
use App\Services\DonorScreeningService;
use App\Services\BloodCareNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DonationManagementController extends Controller
{
    public function edit(string $donation): View
    {
        $model = Donation::where('reference', $donation)
            ->with(['donor.appointments', 'donor.screenings', 'appointment', 'screening', 'bloodUnit', 'recordedBy'])
            ->firstOrFail();

        return view('admin.donors.donation-edit', [
            'donation' => $model,
            'donor' => $model->donor,
            'appointments' => $model->donor->appointments()->orderByDesc('appointment_date')->get(),
            'screenings' => $model->donor->screenings()->orderByDesc('screened_at')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $donation = DB::transaction(fn (): Donation => $this->persist($data));

        return response()->json(['message' => 'Donation saved.', 'donation' => $donation], 201);
    }

    public function update(Request $request, string $donation): JsonResponse|RedirectResponse
    {
        $model = Donation::where('reference', $donation)->firstOrFail();
        $data = $this->validated($request, $model);
        $updated = DB::transaction(fn (): Donation => $this->persist($data, $model));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Donation updated.', 'donation' => $updated]);
        }

        return redirect()
            ->to(route('bloodcare.admin.donors.show', $updated->donor).'#donation-history')
            ->with('status', __('bloodcare.donor_details.donation_updated'));
    }

    public function destroy(string $donation, DonorScreeningService $screenings): RedirectResponse
    {
        $model = Donation::where('reference', $donation)->firstOrFail();
        $donor = $model->donor;

        DB::transaction(function () use ($model, $donor, $screenings): void {
            $lockedDonation = Donation::query()->lockForUpdate()->findOrFail($model->id);
            $lockedDonor = Donor::query()->lockForUpdate()->findOrFail($donor->id);
            $bloodUnit = BloodUnit::where('donation_id', $lockedDonation->id)->lockForUpdate()->first();

            if ($bloodUnit && ! in_array($bloodUnit->status, ['available', 'discarded', 'quarantined'], true)) {
                throw ValidationException::withMessages([
                    'donation' => __('bloodcare.donor_details.protected_donation_delete_blocked'),
                ]);
            }

            $appointment = $lockedDonation->appointment_id
                ? Appointment::query()->lockForUpdate()->find($lockedDonation->appointment_id)
                : null;

            DonorScreening::where('donation_id', $lockedDonation->id)->update(['donation_id' => null]);
            $bloodUnit?->delete();

            if ($appointment?->status === 'completed') {
                $appointment->update([
                    'status' => 'checked_in',
                    'handled_by' => backpack_user()?->id,
                ]);
            }

            ActivityLog::record([
                'type' => 'Donation',
                'action' => 'Donation record deleted',
                'subject_type' => Donation::class,
                'subject_id' => $lockedDonation->id,
                'donor_id' => $lockedDonor->id,
                'user_id' => backpack_user()?->id,
                'result' => 'Deleted',
                'details' => "{$lockedDonation->reference}; {$lockedDonation->blood_group}; {$lockedDonation->quantity_ml} ml",
                'source' => 'admin-donations',
            ]);

            $lockedDonation->delete();
            $screenings->synchronizeDonor($lockedDonor);
        });

        return redirect()
            ->to(route('bloodcare.admin.donors.show', $donor).'#donation-history')
            ->with('status', __('bloodcare.donor_details.donation_deleted'));
    }

    public function updateStatus(Request $request, string $donation): JsonResponse
    {
        $model = Donation::where('reference', $donation)->firstOrFail();
        $data = $request->validate([
            'status' => ['required', Rule::in(['Accepted', 'Rejected'])],
        ]);

        $payload = [
            'donorId' => $model->donor->reference,
            'donationDate' => $model->donation_date->toDateString(),
            'group' => $model->blood_group,
            'donationType' => $model->donation_type ?? 'whole_blood',
            'quantity' => $model->quantity_ml,
            'screeningResult' => $data['status'] === 'Accepted' ? 'Passed' : 'Failed',
            'status' => $data['status'],
            'appointmentReference' => $model->appointment?->reference,
            'screeningReference' => $model->screening?->reference,
            'bagUnit' => $model->bloodUnit?->unit_number ?: ($model->bag_unit_number ?: BloodUnit::generateUnitNumber()),
            'expiryDate' => $model->bloodUnit?->expires_at?->toDateString()
                ?: ($model->expires_at?->toDateString() ?: Carbon::parse($model->donation_date)->addDays(42)->toDateString()),
            'location' => $model->bloodUnit?->storage_location ?: ($model->storage_location ?: 'Cold room A'),
            'notes' => $model->screening_notes,
        ];

        DB::transaction(fn (): Donation => $this->persist($payload, $model));

        return response()->json(['message' => 'Donation status updated.']);
    }

    private function validated(Request $request, ?Donation $donation = null): array
    {
        $request->merge([
            'donationType' => $request->input('donationType', $donation?->donation_type ?? 'whole_blood'),
            'screeningReference' => $request->input('screeningReference', $donation?->screening?->reference),
        ]);

        return $request->validate([
            'donorId' => ['required', 'string', Rule::exists('donors', 'reference')],
            'donationDate' => ['required', 'date', 'before_or_equal:today'],
            'group' => ['required', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'donationType' => ['required', Rule::in(['whole_blood', 'platelets', 'plasma'])],
            'quantity' => ['required', 'integer', 'min:100', 'max:600'],
            'screeningResult' => ['required', Rule::in(['Passed', 'Pending', 'Failed'])],
            'status' => ['required', Rule::in(['Accepted', 'Screening', 'Rejected'])],
            'appointmentReference' => [
                'nullable', 'string', Rule::exists('appointments', 'reference'),
            ],
            'screeningReference' => [
                'nullable', 'string', Rule::exists('donor_screenings', 'reference'),
            ],
            'bagUnit' => [
                'required', 'string', 'max:40',
                Rule::unique('blood_units', 'unit_number')->ignore($donation?->bloodUnit?->id),
                Rule::unique('donations', 'bag_unit_number')->ignore($donation?->id),
            ],
            'expiryDate' => ['required', 'date', 'after:donationDate'],
            'location' => ['required', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'appointmentReference.exists' => __('bloodcare.donations.invalid_appointment_reference'),
        ]);
    }

    private function persist(array $data, ?Donation $donation = null): Donation
    {
        if ($donation?->exists) {
            $donation = Donation::query()->lockForUpdate()->findOrFail($donation->id);
        }

        $donor = Donor::where('reference', $data['donorId'])->lockForUpdate()->firstOrFail();
        $appointment = ! empty($data['appointmentReference'])
            ? Appointment::where('reference', $data['appointmentReference'])->lockForUpdate()->firstOrFail()
            : null;
        $screening = ! empty($data['screeningReference'])
            ? DonorScreening::where('reference', $data['screeningReference'])->lockForUpdate()->firstOrFail()
            : null;
        $bloodUnit = $donation?->exists
            ? BloodUnit::where('donation_id', $donation->id)->lockForUpdate()->first()
            : null;
        $wasAccepted = $donation?->status === 'accepted';

        if ($donation && $donation->donor_id !== $donor->id) {
            throw ValidationException::withMessages([
                'donorId' => 'The donor cannot be changed after a donation record is created.',
            ]);
        }
        if ($donation?->appointment_id !== null && $donation->appointment_id !== $appointment?->id) {
            throw ValidationException::withMessages([
                'appointmentReference' => 'The appointment cannot be changed after it is linked to a donation.',
            ]);
        }
        if ($donation?->screening && $donation->screening->id !== $screening?->id) {
            throw ValidationException::withMessages([
                'screeningReference' => 'The medical screening cannot be changed after it is linked to a donation.',
            ]);
        }

        if ($appointment && $appointment->donor_id !== $donor->id) {
            throw ValidationException::withMessages([
                'appointmentReference' => 'The appointment belongs to a different donor.',
            ]);
        }
        if ($appointment && Donation::where('appointment_id', $appointment->id)
            ->when($donation, fn ($query) => $query->where('id', '!=', $donation->id))
            ->exists()) {
            throw ValidationException::withMessages([
                'appointmentReference' => 'This appointment already has a donation record.',
            ]);
        }
        if ($screening && $screening->donor_id !== $donor->id) {
            throw ValidationException::withMessages([
                'screeningReference' => 'The medical screening belongs to a different donor.',
            ]);
        }
        if ($screening && $screening->donation_id !== null && $screening->donation_id !== $donation?->id) {
            throw ValidationException::withMessages([
                'screeningReference' => 'This medical screening is already linked to another donation.',
            ]);
        }
        if (! $donation) {
            $nextEligible = $donor->next_eligible_date?->toDateString();
            if ($donor->status !== 'active'
                || $donor->eligibility_status !== 'eligible'
                || ($nextEligible && $nextEligible > $data['donationDate'])) {
                throw ValidationException::withMessages([
                    'donorId' => 'The donor is not eligible on the selected donation date.',
                ]);
            }
        }

        if ($data['group'] !== $donor->blood_group) {
            throw ValidationException::withMessages(['group' => 'The blood group must match the donor profile.']);
        }
        if ($data['status'] === 'Accepted' && $data['screeningResult'] !== 'Passed') {
            throw ValidationException::withMessages(['screeningResult' => 'Accepted donations require a passed screening result.']);
        }
        if ($data['status'] === 'Rejected' && $data['screeningResult'] !== 'Failed') {
            throw ValidationException::withMessages(['screeningResult' => 'Rejected donations require a failed screening result.']);
        }
        if ($data['status'] === 'Accepted' && ! $wasAccepted) {
            if (! $screening || $screening->outcome !== 'passed') {
                throw ValidationException::withMessages([
                    'screeningReference' => 'A current passed medical screening is required before accepting a donation.',
                ]);
            }

            if ($screening->screened_at->toDateString() > $data['donationDate']
                || ($screening->next_screening_date
                    && $screening->next_screening_date->toDateString() < $data['donationDate'])) {
                throw ValidationException::withMessages([
                    'screeningReference' => 'The selected medical screening is not current on the donation date.',
                ]);
            }
        }

        $donation ??= new Donation(['reference' => Donation::generateReference()]);
        $donation->fill([
            'donor_id' => $donor->id,
            'appointment_id' => $appointment?->id,
            'donation_centre_id' => $appointment?->donation_centre_id ?? $screening?->donation_centre_id,
            'donation_date' => $data['donationDate'],
            'quantity_ml' => $data['quantity'],
            'blood_group' => $data['group'],
            'donation_type' => $data['donationType'],
            'screening_result' => strtolower($data['screeningResult']),
            'status' => strtolower($data['status']),
            'bag_unit_number' => strtoupper($data['bagUnit']),
            'expires_at' => $data['expiryDate'],
            'storage_location' => trim($data['location']),
            'screening_notes' => $data['notes'] ?: null,
            'recorded_by' => backpack_user()?->id,
        ]);
        $donation->save();

        if ($screening && $screening->donation_id !== $donation->id) {
            $screening->update([
                'donation_id' => $donation->id,
                'appointment_id' => $screening->appointment_id ?? $appointment?->id,
                'donation_centre_id' => $screening->donation_centre_id
                    ?? $appointment?->donation_centre_id,
            ]);
        }

        if ($data['status'] === 'Accepted') {
            $this->syncBloodUnit($donation, $bloodUnit, $data);
            $this->syncDonorEligibility($donor);
            $appointment?->update([
                'status' => 'completed',
                'handled_by' => backpack_user()?->id,
            ]);
        } else {
            if ($bloodUnit && ! in_array($bloodUnit->status, ['available', 'discarded', 'quarantined'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'A reserved or used blood unit cannot be moved back to screening or rejected.',
                ]);
            }

            $bloodUnit?->delete();

            if ($wasAccepted) {
                $this->syncDonorEligibility($donor);

                if ($appointment?->status === 'completed') {
                    $appointment->update([
                        'status' => 'checked_in',
                        'handled_by' => backpack_user()?->id,
                    ]);
                }
            }
        }

        $wasCreated = $donation->wasRecentlyCreated;
        $donation->unsetRelation('bloodUnit');
        $donation->load('bloodUnit');

        if ($donation->status === 'accepted' && ! $wasAccepted && $donation->bloodUnit) {
            app(BloodCareNotificationService::class)->donationQuarantined($donation, $donation->bloodUnit);
        }

        $this->log($donation, $wasCreated ? 'Donation recorded' : 'Donation updated');

        return $donation->fresh(['donor', 'appointment', 'bloodUnit', 'recordedBy']);
    }

    private function syncBloodUnit(Donation $donation, ?BloodUnit $bloodUnit, array $data): void
    {
        $attributes = [
            'unit_number' => strtoupper($data['bagUnit']),
            'blood_group' => $data['group'],
            'collected_at' => $data['donationDate'],
            'expires_at' => $data['expiryDate'],
            'storage_location' => trim($data['location']),
            'notes' => 'Received from donation '.$donation->reference.'.',
        ];

        if (! $bloodUnit) {
            // Collection acceptance is not a laboratory release. Every new
            // donation-linked unit stays quarantined until mandatory testing.
            $donation->bloodUnit()->create($attributes + ['status' => 'quarantined']);

            return;
        }

        $bloodUnit->fill($attributes);

        if ($bloodUnit->isDirty() && ! in_array($bloodUnit->status, ['available', 'discarded', 'quarantined'], true)) {
            throw ValidationException::withMessages([
                'status' => 'A reserved or used blood unit cannot have its donation details changed.',
            ]);
        }

        // Preserve inventory lifecycle state; editing a donation must never
        // turn a reserved, used, or discarded unit back into available stock.
        $bloodUnit->save();
    }

    private function syncDonorEligibility(Donor $donor): void
    {
        $latestAccepted = $donor->donations()
            ->where('status', 'accepted')
            ->orderByDesc('donation_date')
            ->orderByDesc('id')
            ->first();

        $medicalDeferralDate = null;
        $hasMedicalDeferral = $donor->deferral_type === 'permanent';

        if ($donor->deferral_type === 'temporary' && $donor->deferral_end_date) {
            $medicalDeferralDate = Carbon::parse($donor->deferral_end_date);
            $hasMedicalDeferral = $medicalDeferralDate->isAfter(today());
        }

        if (! $latestAccepted) {
            if ($hasMedicalDeferral) {
                $donor->update([
                    'last_donation_date' => null,
                    'next_eligible_date' => $medicalDeferralDate?->toDateString(),
                    'eligibility_status' => 'deferred',
                    'status' => 'active',
                ]);

                return;
            }

            $donor->update([
                'last_donation_date' => null,
                'next_eligible_date' => null,
                'eligibility_status' => 'eligible',
                'status' => 'active',
                'deferral_type' => 'none',
                'deferral_reason' => null,
                'deferral_end_date' => null,
            ]);

            return;
        }

        $lastDonationDate = Carbon::parse($latestAccepted->donation_date);
        $donationType = $latestAccepted->donation_type ?: 'whole_blood';
        $waitDays = (int) config("bloodcare.donation_intervals_days.{$donationType}", 90);
        $nextEligibleDate = $lastDonationDate->copy()->addDays($waitDays);

        if ($medicalDeferralDate?->isAfter($nextEligibleDate)) {
            $nextEligibleDate = $medicalDeferralDate;
        }

        $isWaiting = $hasMedicalDeferral || $nextEligibleDate->isAfter(today());

        $donor->update([
            'last_donation_date' => $lastDonationDate->toDateString(),
            'next_eligible_date' => $donor->deferral_type === 'permanent'
                ? null
                : ($isWaiting ? $nextEligibleDate->toDateString() : null),
            'eligibility_status' => $isWaiting ? 'deferred' : 'eligible',
            'status' => 'active',
            'deferral_type' => $hasMedicalDeferral ? $donor->deferral_type : 'none',
            'deferral_reason' => $hasMedicalDeferral ? $donor->deferral_reason : null,
            'deferral_end_date' => $hasMedicalDeferral ? $donor->deferral_end_date : null,
        ]);
    }

    private function log(Donation $donation, string $action): void
    {
        ActivityLog::record([
            'type' => 'Donation',
            'action' => $action,
            'subject_type' => Donation::class,
            'subject_id' => $donation->id,
            'donor_id' => $donation->donor_id,
            'user_id' => backpack_user()?->id,
            'result' => ucfirst($donation->status),
            'details' => "{$donation->reference}; {$donation->blood_group}; {$donation->quantity_ml} ml",
            'source' => 'admin-donations',
        ]);

        if ($donation->status === 'accepted' && $donation->bloodUnit) {
            ActivityLog::record([
                'type' => 'Inventory',
                'action' => 'Blood unit created from accepted donation',
                'subject_type' => BloodUnit::class,
                'subject_id' => $donation->bloodUnit->id,
                'donor_id' => $donation->donor_id,
                'user_id' => backpack_user()?->id,
                'result' => ucfirst($donation->bloodUnit->status),
                'details' => "{$donation->bloodUnit->unit_number}; {$donation->blood_group}; {$donation->bloodUnit->storage_location}",
                'source' => 'admin-donations',
            ]);
        }
    }
}
