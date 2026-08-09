<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DonationCentre;
use App\Models\Donor;
use App\Models\DonorScreening;
use App\Services\DonorScreeningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DonorScreeningController extends Controller
{
    public function edit(Donor $donor, DonorScreening $screening): View
    {
        $this->ensureOwnership($donor, $screening);
        $screening->load(['appointment', 'donation', 'centre', 'verifiedBy']);

        return view('admin.donors.screening-edit', [
            'donor' => $donor,
            'screening' => $screening,
            'appointments' => $donor->appointments()
                ->orderByDesc('appointment_date')
                ->orderByDesc('appointment_time')
                ->get(),
            'centres' => DonationCentre::query()->where('is_active', true)->orderBy('name')->get(),
            'today' => now()->toDateString(),
            'nowLocal' => now()->format('Y-m-d\TH:i'),
        ]);
    }

    public function store(
        Request $request,
        Donor $donor,
        DonorScreeningService $screenings,
    ): RedirectResponse {
        $data = $this->validated($request);

        DB::transaction(function () use ($donor, $data, $screenings): void {
            $lockedDonor = Donor::query()->lockForUpdate()->findOrFail($donor->id);
            $screenings->record($lockedDonor, $data, backpack_user());
        });

        return redirect()
            ->route('bloodcare.admin.donors.show', $donor)
            ->with('status', __('bloodcare.donor_details.screening_saved'));
    }

    public function update(
        Request $request,
        Donor $donor,
        DonorScreening $screening,
        DonorScreeningService $screenings,
    ): RedirectResponse {
        $this->ensureOwnership($donor, $screening);
        $data = $this->validated($request, true);

        DB::transaction(function () use ($donor, $screening, $data, $screenings): void {
            $lockedDonor = Donor::query()->lockForUpdate()->findOrFail($donor->id);
            $lockedScreening = DonorScreening::query()->lockForUpdate()->findOrFail($screening->id);
            $screenings->update($lockedDonor, $lockedScreening, $data, backpack_user());
        });

        return redirect()
            ->to(route('bloodcare.admin.donors.show', $donor).'#screening-history')
            ->with('status', __('bloodcare.donor_details.screening_updated'));
    }

    public function destroy(
        Donor $donor,
        DonorScreening $screening,
        DonorScreeningService $screenings,
    ): RedirectResponse {
        $this->ensureOwnership($donor, $screening);

        DB::transaction(function () use ($donor, $screening, $screenings): void {
            $lockedDonor = Donor::query()->lockForUpdate()->findOrFail($donor->id);
            $lockedScreening = DonorScreening::query()->lockForUpdate()->findOrFail($screening->id);
            $screenings->delete($lockedDonor, $lockedScreening, backpack_user());
        });

        return redirect()
            ->to(route('bloodcare.admin.donors.show', $donor).'#screening-history')
            ->with('status', __('bloodcare.donor_details.screening_deleted'));
    }

    /** @return array<string, mixed> */
    public static function rules(bool $editing = false): array
    {
        return [
            'screenedAt' => ['required', 'date', 'before_or_equal:now'],
            'nextScreeningDate' => $editing
                ? ['nullable', 'date']
                : ['nullable', 'date', 'after_or_equal:today'],
            'weightKg' => ['required', 'numeric', 'between:25,300'],
            'hemoglobinLevel' => ['required', 'numeric', 'between:3,25'],
            'systolicBloodPressure' => ['required', 'integer', 'between:50,250'],
            'diastolicBloodPressure' => ['required', 'integer', 'between:30,150'],
            'pulseRate' => ['required', 'integer', 'between:30,220'],
            'bodyTemperatureCelsius' => ['required', 'numeric', 'between:30,45'],
            'medicationFlag' => ['required', 'boolean'],
            'currentMedications' => [
                'nullable',
                Rule::requiredIf(fn (): bool => request()->boolean('medicationFlag')),
                'string',
                'max:2000',
            ],
            'recentTravelFlag' => ['required', 'boolean'],
            'recentTravelDetails' => [
                'nullable',
                Rule::requiredIf(fn (): bool => request()->boolean('recentTravelFlag')),
                'string',
                'max:2000',
            ],
            'highRiskActivityFlag' => ['required', 'boolean'],
            'highRiskActivityDetails' => [
                'nullable',
                Rule::requiredIf(fn (): bool => request()->boolean('highRiskActivityFlag')),
                'string',
                'max:2000',
            ],
            'screeningOutcome' => ['required', Rule::in(['Passed', 'Pending', 'Deferred', 'Failed'])],
            'screeningDeferralType' => ['required', Rule::in(['none', 'temporary', 'permanent'])],
            'screeningDeferralReason' => [
                'nullable',
                'required_unless:screeningDeferralType,none',
                'string',
                'max:255',
            ],
            'screeningDeferralEndDate' => [
                'nullable',
                'required_if:screeningDeferralType,temporary',
                'date',
            ],
            'appointmentReference' => ['nullable', 'string', Rule::exists('appointments', 'reference')],
            'centreCode' => ['nullable', 'string', Rule::exists('donation_centres', 'code')],
            'screeningNotes' => ['nullable', 'string', 'max:3000'],
        ];
    }

    private function validated(Request $request, bool $editing = false): array
    {
        $request->merge([
            'medicationFlag' => $request->boolean('medicationFlag'),
            'recentTravelFlag' => $request->boolean('recentTravelFlag'),
            'highRiskActivityFlag' => $request->boolean('highRiskActivityFlag'),
        ]);

        $data = $request->validate(self::rules($editing));
        $requiresDeferral = in_array($data['screeningOutcome'], ['Deferred', 'Failed'], true);

        if ($requiresDeferral && $data['screeningDeferralType'] === 'none') {
            throw ValidationException::withMessages([
                'screeningDeferralType' => __('bloodcare.donor_details.screening_deferral_required'),
            ]);
        }
        if (! $requiresDeferral && $data['screeningDeferralType'] !== 'none') {
            throw ValidationException::withMessages([
                'screeningDeferralType' => __('bloodcare.donor_details.screening_deferral_not_allowed'),
            ]);
        }

        return $data;
    }

    private function ensureOwnership(Donor $donor, DonorScreening $screening): void
    {
        abort_unless($screening->donor_id === $donor->id, 404);
    }
}
