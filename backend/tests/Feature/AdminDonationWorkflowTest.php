<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\BloodUnit;
use App\Models\DonationCentre;
use App\Models\Donor;
use App\Models\DonorScreening;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDonationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepting_a_donation_updates_all_connected_records(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_banned' => false,
        ]);
        $donor = Donor::create([
            'reference' => 'BC-TEST-001',
            'full_name' => 'Test Donor',
            'date_of_birth' => '1998-01-01',
            'gender' => 'female',
            'identity_document_type' => 'passport',
            'identity_number' => 'P-TEST-001',
            'passport_number' => 'P-TEST-001',
            'phone' => '09123456789',
            'phone_normalized' => '09123456789',
            'address' => 'Yangon',
            'blood_group' => 'O+',
            'emergency_contact' => '09123456789',
            'consent_at' => now(),
            'status' => 'active',
            'eligibility_status' => 'eligible',
        ]);
        $centre = DonationCentre::create([
            'code' => 'CTR-TEST',
            'name' => 'Test Centre',
            'region' => 'Yangon',
            'township' => 'Bahan',
            'address' => 'Test address',
            'is_active' => true,
        ]);
        $appointment = Appointment::create([
            'reference' => 'APT-TEST-001',
            'donor_id' => $donor->id,
            'donation_centre_id' => $centre->id,
            'centre_name' => $centre->name,
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '10:00',
            'purpose' => 'Donation',
            'source' => 'Staff',
            'status' => 'checked_in',
        ]);

        backpack_auth()->login($admin);
        $screening = $this->createPassedScreening($donor, $admin, $centre, $appointment, 'SCR-TEST-001');

        $response = $this->postJson(route('bloodcare.admin.donations.store'), [
            'donorId' => $donor->reference,
            'donationDate' => now()->toDateString(),
            'group' => 'O+',
            'quantity' => 450,
            'screeningResult' => 'Passed',
            'status' => 'Accepted',
            'appointmentReference' => $appointment->reference,
            'screeningReference' => $screening->reference,
            'bagUnit' => 'BU-TEST-001',
            'expiryDate' => now()->addDays(42)->toDateString(),
            'location' => 'Cold room A',
            'notes' => 'Automated workflow test.',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('donations', [
            'donor_id' => $donor->id,
            'appointment_id' => $appointment->id,
            'status' => 'accepted',
            'screening_result' => 'passed',
        ]);
        $this->assertDatabaseHas('blood_units', [
            'unit_number' => 'BU-TEST-001',
            'blood_group' => 'O+',
            'status' => 'quarantined',
        ]);
        $this->assertDatabaseHas('donors', [
            'id' => $donor->id,
            'eligibility_status' => 'deferred',
        ]);

        $donor->refresh();
        $this->assertSame(now()->toDateString(), $donor->last_donation_date->toDateString());
        $this->assertSame(now()->addDays(90)->toDateString(), $donor->next_eligible_date->toDateString());
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'completed',
            'handled_by' => $admin->id,
        ]);
        $this->assertDatabaseCount('activity_logs', 2);
    }

    public function test_rejecting_an_accepted_donation_reverses_connected_records(): void
    {
        [$admin, $donor, $appointment] = $this->acceptDonation();

        $response = $this->patchJson(route('bloodcare.admin.donations.status', [
            'donation' => 'DON-TEST-REVERSE',
        ]), [
            'status' => 'Rejected',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('donations', [
            'reference' => 'DON-TEST-REVERSE',
            'status' => 'rejected',
            'screening_result' => 'failed',
        ]);
        $this->assertDatabaseMissing('blood_units', ['unit_number' => 'BU-TEST-REVERSE']);
        $this->assertDatabaseHas('donors', [
            'id' => $donor->id,
            'last_donation_date' => null,
            'next_eligible_date' => null,
            'eligibility_status' => 'eligible',
        ]);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'checked_in',
            'handled_by' => $admin->id,
        ]);
    }

    public function test_reversing_a_donation_preserves_a_later_medical_deferral(): void
    {
        [, $donor] = $this->acceptDonation();
        $deferralEnd = now()->addDays(30)->toDateString();

        $this->post(route('bloodcare.admin.donors.screenings.store', $donor), [
            'screenedAt' => now()->subMinute()->format('Y-m-d\TH:i'),
            'nextScreeningDate' => $deferralEnd,
            'weightKg' => '61.50',
            'hemoglobinLevel' => '11.70',
            'systolicBloodPressure' => 118,
            'diastolicBloodPressure' => 76,
            'pulseRate' => 70,
            'bodyTemperatureCelsius' => '36.70',
            'medicationFlag' => '1',
            'currentMedications' => 'Temporary medication course',
            'recentTravelFlag' => '0',
            'highRiskActivityFlag' => '0',
            'screeningOutcome' => 'Deferred',
            'screeningDeferralType' => 'temporary',
            'screeningDeferralReason' => 'Medication review',
            'screeningDeferralEndDate' => $deferralEnd,
            'screeningNotes' => 'Review after medication course.',
        ])->assertRedirect(route('bloodcare.admin.donors.show', $donor));

        $this->patchJson(route('bloodcare.admin.donations.status', [
            'donation' => 'DON-TEST-REVERSE',
        ]), [
            'status' => 'Rejected',
        ])->assertOk();

        $this->assertDatabaseHas('donors', [
            'id' => $donor->id,
            'last_donation_date' => null,
            'eligibility_status' => 'deferred',
            'deferral_type' => 'temporary',
            'deferral_reason' => 'Medication review',
        ]);

        $donor->refresh();

        $this->assertSame($deferralEnd, $donor->next_eligible_date?->toDateString());
        $this->assertSame($deferralEnd, $donor->deferral_end_date?->toDateString());
    }

    public function test_reserved_blood_unit_prevents_donation_reversal_atomically(): void
    {
        [, $donor, $appointment] = $this->acceptDonation();
        BloodUnit::where('unit_number', 'BU-TEST-REVERSE')->update(['status' => 'reserved']);

        $response = $this->patchJson(route('bloodcare.admin.donations.status', [
            'donation' => 'DON-TEST-REVERSE',
        ]), [
            'status' => 'Rejected',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->assertDatabaseHas('donations', [
            'reference' => 'DON-TEST-REVERSE',
            'status' => 'accepted',
            'screening_result' => 'passed',
        ]);
        $this->assertDatabaseHas('blood_units', [
            'unit_number' => 'BU-TEST-REVERSE',
            'status' => 'reserved',
        ]);
        $this->assertDatabaseHas('donors', [
            'id' => $donor->id,
            'eligibility_status' => 'deferred',
        ]);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'completed',
        ]);
    }

    public function test_reserved_blood_unit_prevents_inventory_details_being_changed_through_donation_edit(): void
    {
        $this->acceptDonation();
        BloodUnit::where('unit_number', 'BU-TEST-REVERSE')->update(['status' => 'reserved']);

        $response = $this->putJson(route('bloodcare.admin.donations.update', [
            'donation' => 'DON-TEST-REVERSE',
        ]), [
            'donorId' => 'BC-TEST-REVERSE',
            'donationDate' => now()->toDateString(),
            'group' => 'O+',
            'quantity' => 450,
            'screeningResult' => 'Passed',
            'status' => 'Accepted',
            'appointmentReference' => 'APT-TEST-REVERSE',
            'bagUnit' => 'BU-TEST-REVERSE',
            'expiryDate' => now()->addDays(42)->toDateString(),
            'location' => 'Cold room B',
            'notes' => 'This edit must roll back.',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->assertDatabaseHas('donations', [
            'reference' => 'DON-TEST-REVERSE',
            'storage_location' => 'Cold room A',
            'screening_notes' => 'Reversal workflow test.',
            'status' => 'accepted',
        ]);
        $this->assertDatabaseHas('blood_units', [
            'unit_number' => 'BU-TEST-REVERSE',
            'storage_location' => 'Cold room A',
            'status' => 'reserved',
        ]);
    }

    /**
     * @return array{User, Donor, Appointment}
     */
    private function acceptDonation(): array
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_banned' => false,
        ]);
        $donor = Donor::create([
            'reference' => 'BC-TEST-REVERSE',
            'full_name' => 'Reversal Test Donor',
            'date_of_birth' => '1998-01-01',
            'gender' => 'female',
            'identity_document_type' => 'passport',
            'identity_number' => 'P-TEST-REVERSE',
            'passport_number' => 'P-TEST-REVERSE',
            'phone' => '09123456789',
            'phone_normalized' => '09123456789',
            'address' => 'Yangon',
            'blood_group' => 'O+',
            'emergency_contact' => '09123456789',
            'consent_at' => now(),
            'status' => 'active',
            'eligibility_status' => 'eligible',
        ]);
        $centre = DonationCentre::create([
            'code' => 'CTR-TEST-REVERSE',
            'name' => 'Reversal Test Centre',
            'region' => 'Yangon',
            'township' => 'Bahan',
            'address' => 'Test address',
            'is_active' => true,
        ]);
        $appointment = Appointment::create([
            'reference' => 'APT-TEST-REVERSE',
            'donor_id' => $donor->id,
            'donation_centre_id' => $centre->id,
            'centre_name' => $centre->name,
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '10:00',
            'purpose' => 'Donation',
            'source' => 'Staff',
            'status' => 'checked_in',
        ]);

        backpack_auth()->login($admin);
        $screening = $this->createPassedScreening($donor, $admin, $centre, $appointment, 'SCR-TEST-REVERSE');
        // Keep the donation screening earlier than any follow-up clinical
        // decision recorded by the reversal tests. The UI submits minute-
        // precision datetimes, while this factory value otherwise retains
        // seconds and can incorrectly sort after a newly entered screening.
        $screening->update(['screened_at' => now()->subMinutes(2)]);

        $this->postJson(route('bloodcare.admin.donations.store'), [
            'donorId' => $donor->reference,
            'donationDate' => now()->toDateString(),
            'group' => 'O+',
            'quantity' => 450,
            'screeningResult' => 'Passed',
            'status' => 'Accepted',
            'appointmentReference' => $appointment->reference,
            'screeningReference' => $screening->reference,
            'bagUnit' => 'BU-TEST-REVERSE',
            'expiryDate' => now()->addDays(42)->toDateString(),
            'location' => 'Cold room A',
            'notes' => 'Reversal workflow test.',
        ])->assertCreated();

        $donation = $donor->donations()->firstOrFail();
        $donation->update(['reference' => 'DON-TEST-REVERSE']);

        return [$admin, $donor, $appointment];
    }

    private function createPassedScreening(
        Donor $donor,
        User $staff,
        DonationCentre $centre,
        Appointment $appointment,
        string $reference,
    ): DonorScreening {
        return DonorScreening::create([
            'reference' => $reference,
            'donor_id' => $donor->id,
            'appointment_id' => $appointment->id,
            'donation_centre_id' => $centre->id,
            'screened_at' => now(),
            'next_screening_date' => now()->addDay()->toDateString(),
            'weight_kg' => 62.5,
            'hemoglobin_level' => 13.5,
            'systolic_blood_pressure' => 120,
            'diastolic_blood_pressure' => 80,
            'pulse_rate' => 72,
            'body_temperature_celsius' => 36.8,
            'outcome' => 'passed',
            'deferral_type' => 'none',
            'verified_by_staff_id' => $staff->id,
        ]);
    }
}
