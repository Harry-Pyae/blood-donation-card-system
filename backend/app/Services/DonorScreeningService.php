<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\DonationCentre;
use App\Models\Donor;
use App\Models\DonorScreening;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class DonorScreeningService
{
    public function record(Donor $donor, array $data, ?User $staff): DonorScreening
    {
        [$appointment, $centre] = $this->context($donor, $data);

        $screening = $donor->screenings()->create([
            'reference' => DonorScreening::generateReference(),
            'appointment_id' => $appointment?->id,
            'donation_centre_id' => $centre?->id,
            ...$this->attributes($data),
            'verified_by_staff_id' => $staff?->id,
        ]);

        $this->synchronizeDonor($donor);

        ActivityLog::record([
            'type' => 'Screening',
            'action' => 'Donor medical screening recorded',
            'subject_type' => DonorScreening::class,
            'subject_id' => $screening->id,
            'donor_id' => $donor->id,
            'user_id' => $staff?->id,
            'result' => str($screening->outcome)->title()->toString(),
            'details' => "{$screening->reference}; {$screening->weight_kg} kg; Hb {$screening->hemoglobin_level} g/dL",
            'source' => 'admin-donor-screening',
        ]);

        return $screening->fresh(['appointment', 'centre', 'verifiedBy']);
    }

    public function update(Donor $donor, DonorScreening $screening, array $data, ?User $staff): DonorScreening
    {
        if ($screening->donor_id !== $donor->id) {
            abort(404);
        }

        [$appointment, $centre] = $this->context($donor, $data);
        $outcome = strtolower($data['screeningOutcome']);

        if ($screening->donation) {
            if ($screening->appointment_id !== $appointment?->id) {
                throw ValidationException::withMessages([
                    'appointmentReference' => __('bloodcare.donor_details.linked_screening_appointment_locked'),
                ]);
            }

            if ($screening->donation->status === 'accepted' && $outcome !== 'passed') {
                throw ValidationException::withMessages([
                    'screeningOutcome' => __('bloodcare.donor_details.accepted_screening_must_pass'),
                ]);
            }
        }

        $screening->update([
            'appointment_id' => $appointment?->id,
            'donation_centre_id' => $centre?->id,
            ...$this->attributes($data),
            'verified_by_staff_id' => $staff?->id,
        ]);

        $this->synchronizeDonor($donor);

        ActivityLog::record([
            'type' => 'Screening',
            'action' => 'Donor medical screening corrected',
            'subject_type' => DonorScreening::class,
            'subject_id' => $screening->id,
            'donor_id' => $donor->id,
            'user_id' => $staff?->id,
            'result' => str($screening->outcome)->title()->toString(),
            'details' => "{$screening->reference}; corrected; {$screening->weight_kg} kg; Hb {$screening->hemoglobin_level} g/dL",
            'source' => 'admin-donor-screening',
        ]);

        return $screening->fresh(['appointment', 'donation', 'centre', 'verifiedBy']);
    }

    public function delete(Donor $donor, DonorScreening $screening, ?User $staff): void
    {
        if ($screening->donor_id !== $donor->id) {
            abort(404);
        }

        if ($screening->donation_id !== null) {
            throw ValidationException::withMessages([
                'screening' => __('bloodcare.donor_details.linked_screening_delete_blocked'),
            ]);
        }

        ActivityLog::record([
            'type' => 'Screening',
            'action' => 'Donor medical screening deleted',
            'subject_type' => DonorScreening::class,
            'subject_id' => $screening->id,
            'donor_id' => $donor->id,
            'user_id' => $staff?->id,
            'result' => 'Deleted',
            'details' => "{$screening->reference}; deleted correction record",
            'source' => 'admin-donor-screening',
        ]);

        $screening->delete();
        $this->synchronizeDonor($donor);
    }

    public function synchronizeDonor(Donor $donor): void
    {
        $donor->refresh();
        $latestAccepted = $donor->donations()
            ->where('status', 'accepted')
            ->orderByDesc('donation_date')
            ->orderByDesc('id')
            ->first();
        $latestScreening = $donor->screenings()
            ->orderByDesc('screened_at')
            ->orderByDesc('id')
            ->first();
        $permanentDeferral = $donor->screenings()
            ->whereIn('outcome', ['deferred', 'failed'])
            ->where('deferral_type', 'permanent')
            ->orderByDesc('screened_at')
            ->orderByDesc('id')
            ->first();

        $lastDonationDate = $latestAccepted ? Carbon::parse($latestAccepted->donation_date) : null;
        $intervalDate = null;
        if ($latestAccepted) {
            $type = $latestAccepted->donation_type ?: 'whole_blood';
            $intervalDate = $lastDonationDate->copy()
                ->addDays((int) config("bloodcare.donation_intervals_days.{$type}", 90));
        }

        if ($permanentDeferral) {
            $donor->update([
                'last_donation_date' => $lastDonationDate?->toDateString(),
                'next_eligible_date' => null,
                'eligibility_status' => 'deferred',
                'deferral_type' => 'permanent',
                'deferral_reason' => $permanentDeferral->deferral_reason,
                'deferral_end_date' => null,
            ]);

            return;
        }

        $temporaryDeferral = $latestScreening
            && in_array($latestScreening->outcome, ['deferred', 'failed'], true)
            && $latestScreening->deferral_type === 'temporary'
            && $latestScreening->deferral_end_date
            && $latestScreening->deferral_end_date->isAfter(today());

        if ($temporaryDeferral) {
            $nextEligible = $latestScreening->deferral_end_date->copy();
            if ($intervalDate?->isAfter($nextEligible)) {
                $nextEligible = $intervalDate;
            }

            $donor->update([
                'last_donation_date' => $lastDonationDate?->toDateString(),
                'eligibility_status' => 'deferred',
                'deferral_type' => 'temporary',
                'deferral_reason' => $latestScreening->deferral_reason,
                'deferral_end_date' => $latestScreening->deferral_end_date,
                'next_eligible_date' => $nextEligible->toDateString(),
            ]);

            return;
        }

        if ($latestScreening?->outcome === 'pending') {
            $donor->update([
                'last_donation_date' => $lastDonationDate?->toDateString(),
                'eligibility_status' => 'review',
                'next_eligible_date' => $intervalDate?->isAfter(today()) ? $intervalDate->toDateString() : null,
                'deferral_type' => 'none',
                'deferral_reason' => null,
                'deferral_end_date' => null,
            ]);

            return;
        }

        $waitingForDonationInterval = $intervalDate?->isAfter(today()) ?? false;
        $donor->update([
            'last_donation_date' => $lastDonationDate?->toDateString(),
            'eligibility_status' => $waitingForDonationInterval ? 'deferred' : 'eligible',
            'status' => $latestScreening?->outcome === 'passed' && $donor->status === 'pending'
                ? 'active'
                : $donor->status,
            'next_eligible_date' => $waitingForDonationInterval ? $intervalDate->toDateString() : null,
            'deferral_type' => 'none',
            'deferral_reason' => null,
            'deferral_end_date' => null,
        ]);
    }

    /** @return array{0: ?Appointment, 1: ?DonationCentre} */
    private function context(Donor $donor, array $data): array
    {
        $appointment = ! empty($data['appointmentReference'])
            ? Appointment::where('reference', $data['appointmentReference'])->lockForUpdate()->firstOrFail()
            : null;
        $centre = ! empty($data['centreCode'])
            ? DonationCentre::where('code', $data['centreCode'])->firstOrFail()
            : $appointment?->centre;

        if ($appointment && $appointment->donor_id !== $donor->id) {
            throw ValidationException::withMessages([
                'appointmentReference' => __('bloodcare.donor_details.screening_appointment_mismatch'),
            ]);
        }

        return [$appointment, $centre];
    }

    private function attributes(array $data): array
    {
        return [
            'screened_at' => $data['screenedAt'],
            'next_screening_date' => $data['nextScreeningDate'] ?? null,
            'weight_kg' => $data['weightKg'],
            'hemoglobin_level' => $data['hemoglobinLevel'],
            'systolic_blood_pressure' => $data['systolicBloodPressure'],
            'diastolic_blood_pressure' => $data['diastolicBloodPressure'],
            'pulse_rate' => $data['pulseRate'],
            'body_temperature_celsius' => $data['bodyTemperatureCelsius'],
            'medication_flag' => (bool) ($data['medicationFlag'] ?? false),
            'current_medications' => $data['currentMedications'] ?? null,
            'recent_travel_flag' => (bool) ($data['recentTravelFlag'] ?? false),
            'recent_travel_details' => $data['recentTravelDetails'] ?? null,
            'high_risk_activity_flag' => (bool) ($data['highRiskActivityFlag'] ?? false),
            'high_risk_activity_details' => $data['highRiskActivityDetails'] ?? null,
            'outcome' => strtolower($data['screeningOutcome']),
            'deferral_type' => strtolower($data['screeningDeferralType'] ?? 'none'),
            'deferral_reason' => $data['screeningDeferralReason'] ?? null,
            'deferral_end_date' => $data['screeningDeferralEndDate'] ?? null,
            'notes' => $data['screeningNotes'] ?? null,
        ];
    }
}
