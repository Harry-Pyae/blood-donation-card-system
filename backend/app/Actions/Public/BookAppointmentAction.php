<?php

namespace App\Actions\Public;

use App\Http\Requests\Public\StoreAppointmentRequest;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\DonationCentre;
use App\Models\Donor;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Books a public appointment, or records a "no centre near me" location request.
 *
 * Extracted verbatim from PublicSiteController so the Blade flow and the API
 * share one implementation of donor matching, centre resolution, reference
 * generation, transaction boundary and activity logging.
 */
class BookAppointmentAction
{
    /**
     * @param  array<string, mixed>  $data  Validated booking data.
     * @return array{donor: Donor, appointment: Appointment, centre: DonationCentre|null}
     *
     * @throws ValidationException when reference and phone do not match a donor.
     */
    public function handle(array $data): array
    {
        $donor = Donor::query()
            ->whereRaw('UPPER(reference) = ?', [strtoupper(trim($data['donor_reference']))])
            ->where('phone_normalized', Donor::normalizePhone($data['phone']))
            ->first();

        if (! $donor) {
            throw ValidationException::withMessages([
                'donor_reference' => __('bloodcare.public.booking.donor_not_found'),
            ]);
        }

        $centre = $data['centre'] === StoreAppointmentRequest::REQUEST_LOCATION
            ? null
            : DonationCentre::where('name', $data['centre'])->where('is_active', true)->first();

        $appointment = DB::transaction(function () use ($data, $donor, $centre): Appointment {
            $appointment = Appointment::create([
                'reference' => Appointment::generateReference(),
                'donor_id' => $donor->id,
                'donation_centre_id' => $centre?->id,
                'centre_name' => $centre?->name,
                'appointment_date' => $data['appointment_date'] ?? null,
                'appointment_time' => $data['appointment_time'] ?? null,
                'requested_region' => $data['requested_region'] ?? null,
                'requested_township' => $data['requested_township'] ?? null,
                'purpose' => $donor->eligibility_status === 'eligible' ? 'Donation' : 'Eligibility review',
                'source' => 'Public booking',
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'acknowledged_at' => now(),
            ]);

            ActivityLog::record([
                'type' => 'Appointment',
                'action' => $centre ? 'Public appointment booked' : 'Donation location requested',
                'subject_type' => Appointment::class,
                'subject_id' => $appointment->id,
                'donor_id' => $donor->id,
                'result' => 'Pending',
                'details' => $centre
                    ? "Appointment requested at {$centre->name}."
                    : 'A donor requested a donation location in another area.',
                'source' => 'public-booking',
            ]);

            return $appointment;
        });

        return ['donor' => $donor, 'appointment' => $appointment, 'centre' => $centre];
    }
}
