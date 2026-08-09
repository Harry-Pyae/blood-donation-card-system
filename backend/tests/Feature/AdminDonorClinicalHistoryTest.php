<?php

namespace Tests\Feature;

use App\Models\DonationCentre;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\DonorScreening;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminDonorClinicalHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_donor_listing_uses_dedicated_register_details_and_preview_workflows(): void
    {
        $admin = $this->loginAdministrator();
        $donor = $this->createDonor();

        $listing = $this->get(route('bloodcare.admin.donors'));
        $listing->assertOk()
            ->assertSee(route('bloodcare.admin.donors.create'), false)
            ->assertSee('data-bc-open-registration-choice', false)
            ->assertSee(__('bloodcare.donor_details.register_existing_donor'))
            ->assertSee(__('bloodcare.donor_details.register_new_donor'))
            ->assertSee('showUrlTemplate', false)
            ->assertSee('editUrlTemplate', false)
            ->assertSee(__('bloodcare.donors.preview'));

        $newRegistration = $this->get(route('bloodcare.admin.donors.create', ['mode' => 'new']));
        $newRegistration
            ->assertOk()
            ->assertDontSee(__('bloodcare.donor_details.user_section'))
            ->assertSee('data-bc-registration-flow', false)
            ->assertSee('data-bc-section-target="personal"', false)
            ->assertSee('data-bc-section-target="identity"', false)
            ->assertSee('data-bc-section-target="donation"', false)
            ->assertSee('data-bc-section-target="medical"', false)
            ->assertSee('data-bc-registration-next', false)
            ->assertSee('data-bc-registration-save', false)
            ->assertSee('name="phoneLocal"', false)
            ->assertSee('<strong>+95</strong>', false)
            ->assertSee('data-bc-print-donor-locale="en"', false)
            ->assertSee('data-bc-print-donor-locale="my"', false)
            ->assertSee('data-bc-donor-print-sheet="en"', false)
            ->assertSee('data-bc-donor-print-sheet="my"', false)
            ->assertSee(__('bloodcare.donor_details.print_identity_number'))
            ->assertSee('bc-donor-print-checkbox', false)
            ->assertDontSee('bc-donor-print-stages', false)
            ->assertDontSee('data-bc-print-value', false)
            ->assertSee(now()->subYears(18)->toDateString())
            ->assertSee(__('bloodcare.donor_details.physical_vitals'))
            ->assertSee('name="weightKg"', false)
            ->assertSee('name="hemoglobinLevel"', false)
            ->assertSee('name="systolicBloodPressure"', false)
            ->assertSee('name="diastolicBloodPressure"', false)
            ->assertSee('name="pulseRate"', false)
            ->assertSee('name="bodyTemperatureCelsius"', false)
            ->assertSee('name="medicationFlag"', false)
            ->assertSee('name="recentTravelFlag"', false)
            ->assertSee('name="highRiskActivityFlag"', false)
            ->assertSee('name="donationTypePreference"', false)
            ->assertSee('name="screeningDeferralType"', false);

        $publicUser = User::factory()->create([
            'name' => 'Linked Account Candidate',
            'email' => 'linked.account.candidate@example.com',
            'phone' => '09970001122',
            'role' => User::ROLE_USER,
            'approval_status' => User::APPROVAL_APPROVED,
            'is_banned' => false,
        ]);

        $this->get(route('bloodcare.admin.donors.create', ['mode' => 'linked']))
            ->assertOk()
            ->assertSee(__('bloodcare.donor_details.user_section'))
            ->assertSee('data-bc-linked-user-select', false)
            ->assertSee('data-user-name="Linked Account Candidate"', false)
            ->assertSee('data-user-email="linked.account.candidate@example.com"', false)
            ->assertSee('data-user-phone="09970001122"', false)
            ->assertSee('name="userId"', false);

        $newRegistration
            ->assertSee('data-bc-weight-converter', false)
            ->assertSee('data-bc-weight-pounds', false)
            ->assertSee('data-bc-weight-apply', false)
            ->assertSee(__('bloodcare.donor_details.weight_guidance'))
            ->assertSee(__('bloodcare.donor_details.hemoglobin_guidance'))
            ->assertSee(__('bloodcare.donor_details.conversion_save_notice'));

        $formScript = file_get_contents(public_path('js/bloodcare-donor-form.js'));
        $globalControls = file_get_contents(public_path('js/bloodcare-form-controls.js'));
        $adminStyles = file_get_contents(public_path('css/bloodcare-admin.css'));
        $this->assertStringContainsString('prefillLinkedUser', $formScript);
        $this->assertStringContainsString('data-bc-registration-flow', $formScript);
        $this->assertStringNotContainsString('populatePrintSheet', $formScript);
        $this->assertStringNotContainsString("|| '—'", $formScript);
        $this->assertStringContainsString('.bc-donor-print-sheet[hidden]', $adminStyles);
        $this->assertStringContainsString('print-color-adjust: exact !important', $adminStyles);
        $this->assertStringContainsString('-webkit-print-color-adjust: exact !important', $adminStyles);
        $this->assertStringContainsString("querySelector(':scope > span, :scope > legend')", $globalControls);

        $this->assertTrue($admin->canAccessStaffWorkspace());
    }

    public function test_staff_can_register_a_linked_donor_with_an_initial_medical_screening(): void
    {
        $admin = $this->loginAdministrator();
        $publicUser = User::factory()->create([
            'role' => User::ROLE_USER,
            'approval_status' => User::APPROVAL_APPROVED,
            'is_banned' => false,
        ]);
        $centre = DonationCentre::query()->firstOrFail();

        $response = $this->post(route('bloodcare.admin.donors.store'), [
            'registrationMode' => 'linked',
            'userId' => $publicUser->id,
            'name' => 'Linked Clinical Donor',
            'group' => 'A+',
            'phone' => '+959911122233',
            'email' => 'linked.donor@example.com',
            'emergencyContact' => '09911122244',
            'dateOfBirth' => '1995-05-10',
            'gender' => 'Female',
            'identityDocumentType' => 'passport',
            'passportNumber' => 'CLINICAL-001',
            'address' => 'Yangon',
            'donationTypePreference' => 'platelets',
            'lastDonation' => null,
            'nextEligible' => null,
            'eligibility' => 'Review',
            'status' => 'Pending',
            'deferralType' => 'none',
            'notes' => 'Initial clinical registration.',
            'recordInitialScreening' => '1',
            'screenedAt' => now()->subMinute()->format('Y-m-d\TH:i'),
            'nextScreeningDate' => now()->addDay()->toDateString(),
            'weightKg' => '58.40',
            'hemoglobinLevel' => '13.10',
            'systolicBloodPressure' => 118,
            'diastolicBloodPressure' => 76,
            'pulseRate' => 70,
            'bodyTemperatureCelsius' => '36.70',
            'medicationFlag' => '0',
            'recentTravelFlag' => '0',
            'highRiskActivityFlag' => '0',
            'screeningOutcome' => 'Passed',
            'screeningDeferralType' => 'none',
            'centreCode' => $centre->code,
            'screeningNotes' => 'Vitals checked in person.',
        ]);

        $donor = Donor::query()->where('full_name', 'Linked Clinical Donor')->firstOrFail();
        $response->assertRedirect(route('bloodcare.admin.donors.show', $donor));
        $this->assertSame($publicUser->id, $donor->user_id);
        $this->assertNull($donor->last_donation_date);
        $this->assertSame('platelets', $donor->donation_type_preference);
        $this->assertSame('eligible', $donor->fresh()->eligibility_status);
        $this->assertDatabaseHas('donor_screenings', [
            'donor_id' => $donor->id,
            'outcome' => 'passed',
            'verified_by_staff_id' => $admin->id,
            'weight_kg' => 58.40,
        ]);
        $this->assertSame($donor->id, $publicUser->fresh()->donor?->id);
    }

    public function test_repeat_medical_checks_are_preserved_and_searchable_on_donor_details(): void
    {
        $this->loginAdministrator();
        $donor = $this->createDonor();
        $centre = DonationCentre::query()->firstOrFail();
        $this->createPassedScreening($donor, 'SCR-HISTORY-FIRST', now()->subMonth());

        $response = $this->post(route('bloodcare.admin.donors.screenings.store', $donor), [
            'screenedAt' => now()->subMinute()->format('Y-m-d\TH:i'),
            'nextScreeningDate' => now()->addDays(30)->toDateString(),
            'weightKg' => '57.20',
            'hemoglobinLevel' => '11.80',
            'systolicBloodPressure' => 116,
            'diastolicBloodPressure' => 74,
            'pulseRate' => 68,
            'bodyTemperatureCelsius' => '36.60',
            'medicationFlag' => '1',
            'currentMedications' => 'Antibiotic course',
            'recentTravelFlag' => '0',
            'highRiskActivityFlag' => '0',
            'screeningOutcome' => 'Deferred',
            'screeningDeferralType' => 'temporary',
            'screeningDeferralReason' => 'Medication review',
            'screeningDeferralEndDate' => now()->addDays(30)->toDateString(),
            'centreCode' => $centre->code,
            'screeningNotes' => 'Return after medication review.',
        ]);

        $response->assertRedirect(route('bloodcare.admin.donors.show', $donor));
        $this->assertDatabaseCount('donor_screenings', 2);
        $this->assertDatabaseHas('donor_screenings', ['reference' => 'SCR-HISTORY-FIRST']);
        $this->assertDatabaseHas('donors', [
            'id' => $donor->id,
            'eligibility_status' => 'deferred',
            'deferral_type' => 'temporary',
            'deferral_reason' => 'Medication review',
        ]);

        $details = $this->get(route('bloodcare.admin.donors.show', [
            'donor' => $donor,
            'screening_search' => 'Medication review',
            'screening_outcome' => 'deferred',
        ]));
        $details->assertOk()
            ->assertSee(__('bloodcare.donor_details.donation_history'))
            ->assertSee(__('bloodcare.donor_details.screening_history'))
            ->assertSee('data-bc-section-target="overview"', false)
            ->assertSee('data-bc-section-target="clinical"', false)
            ->assertSee(__('bloodcare.donor_details.medical_regulatory_summary'))
            ->assertSee('Medication review')
            ->assertSee('Antibiotic course')
            ->assertSee('57.20')
            ->assertSee('11.80')
            ->assertSee('name="screening_search"', false)
            ->assertSee('name="donation_search"', false);
    }

    public function test_clinical_schema_preserves_every_requested_profile_and_screening_field(): void
    {
        $this->assertTrue(Schema::hasColumns('donors', [
            'donation_type_preference',
            'deferral_type',
            'deferral_reason',
            'deferral_end_date',
        ]));

        $this->assertTrue(Schema::hasColumns('donor_screenings', [
            'weight_kg',
            'hemoglobin_level',
            'systolic_blood_pressure',
            'diastolic_blood_pressure',
            'pulse_rate',
            'body_temperature_celsius',
            'medication_flag',
            'current_medications',
            'recent_travel_flag',
            'recent_travel_details',
            'high_risk_activity_flag',
            'high_risk_activity_details',
            'deferral_type',
            'deferral_reason',
            'deferral_end_date',
            'verified_by_staff_id',
        ]));
    }

    public function test_new_accepted_donation_requires_a_passed_screening_and_uses_type_interval(): void
    {
        $this->loginAdministrator();
        $donor = $this->createDonor();

        $payload = [
            'donorId' => $donor->reference,
            'donationDate' => now()->toDateString(),
            'group' => $donor->blood_group,
            'donationType' => 'platelets',
            'quantity' => 300,
            'screeningResult' => 'Passed',
            'status' => 'Accepted',
            'appointmentReference' => null,
            'screeningReference' => null,
            'bagUnit' => 'BU-CLINICAL-001',
            'expiryDate' => now()->addDays(5)->toDateString(),
            'location' => 'Cold room A',
            'notes' => 'Platelet donation.',
        ];

        $this->postJson(route('bloodcare.admin.donations.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('screeningReference');

        $screening = $this->createPassedScreening($donor, 'SCR-CLINICAL-PASS', now());
        $payload['screeningReference'] = $screening->reference;
        $this->postJson(route('bloodcare.admin.donations.store'), $payload)->assertCreated();

        $this->assertDatabaseHas('donations', [
            'donor_id' => $donor->id,
            'donation_type' => 'platelets',
            'status' => 'accepted',
        ]);
        $this->assertDatabaseHas('donor_screenings', [
            'id' => $screening->id,
            'donation_id' => $donor->donations()->firstOrFail()->id,
        ]);
        $this->assertSame(now()->addDays(14)->toDateString(), $donor->fresh()->next_eligible_date->toDateString());
    }

    public function test_history_tables_are_compact_and_offer_details_edit_and_delete_actions(): void
    {
        $admin = $this->loginAdministrator();
        $donor = $this->createDonor();
        $screening = $this->createPassedScreening($donor, 'SCR-COMPACT-001', now()->subDay());
        $donation = $this->createScreeningDonation($donor, $admin, 'DON-COMPACT-001');

        $response = $this->get(route('bloodcare.admin.donors.show', $donor));

        $response->assertOk()
            ->assertSee('bc-donation-history-table', false)
            ->assertSee('bc-screening-history-table', false)
            ->assertSee('data-bc-history-details-open="bc-donation-history-modal-'.$donation->id.'"', false)
            ->assertSee('data-bc-history-details-open="bc-screening-history-modal-'.$screening->id.'"', false)
            ->assertSee(route('bloodcare.admin.donations.edit', $donation->reference), false)
            ->assertSee(route('bloodcare.admin.donations.destroy', $donation->reference), false)
            ->assertSee(route('bloodcare.admin.donors.screenings.edit', [$donor, $screening]), false)
            ->assertSee(route('bloodcare.admin.donors.screenings.destroy', [$donor, $screening]), false)
            ->assertSee(__('bloodcare.donor_details.donation_record_details'))
            ->assertSee(__('bloodcare.donor_details.screening_record_details'))
            ->assertSee('class="bc-history-filter-action"', false)
            ->assertSee('title="'.__('bloodcare.donor_details.view_details').'"', false)
            ->assertSee('title="'.__('bloodcare.donor_details.edit_record').'"', false)
            ->assertSee('title="'.__('bloodcare.donor_details.delete_record').'"', false)
            ->assertSee($screening->screened_at->format('d M Y, H:i'));

        $css = file_get_contents(public_path('css/bloodcare-admin.css'));
        $this->assertStringContainsString('.bc-history-filter-action > .bc-btn-primary', $css);
        $this->assertStringContainsString('align-items: start;', $css);
        $this->assertStringContainsString('align-self: start;', $css);
        $this->assertStringContainsString('height: 46px;', $css);
        $this->assertStringContainsString('flex-flow: row nowrap;', $css);
        $this->assertStringContainsString('min-width: max-content;', $css);
        $this->assertStringContainsString('.bc-donor-history-dialog > .bc-modal-header', $css);
        $this->assertStringContainsString('overscroll-behavior: contain;', $css);
        $this->assertStringContainsString('.bc-row-menu > a:hover', $css);
        $this->assertStringContainsString('.bc-row-menu > button:focus-visible', $css);
        $this->assertStringContainsString('.bc-row-menu > a.bc-row-menu-link:hover', $css);
        $this->assertStringContainsString('background-color: var(--bc-admin-red-soft) !important;', $css);

        $donorScript = file_get_contents(public_path('js/bloodcare-donors.js'));
        $this->assertNotFalse($donorScript);
        $this->assertSame(3, substr_count($donorScript, 'class="bc-row-menu-link"'));
    }

    public function test_staff_can_correct_and_delete_an_unlinked_screening_history_record(): void
    {
        $this->loginAdministrator();
        $donor = $this->createDonor();
        $screening = $this->createPassedScreening($donor, 'SCR-CORRECT-001', now()->subMonth());

        $payload = [
            'screenedAt' => now()->subMonth()->format('Y-m-d\TH:i'),
            'nextScreeningDate' => now()->subDays(10)->toDateString(),
            'weightKg' => '64.25',
            'hemoglobinLevel' => '13.40',
            'systolicBloodPressure' => 122,
            'diastolicBloodPressure' => 78,
            'pulseRate' => 74,
            'bodyTemperatureCelsius' => '36.90',
            'medicationFlag' => '0',
            'recentTravelFlag' => '0',
            'highRiskActivityFlag' => '0',
            'screeningOutcome' => 'Passed',
            'screeningDeferralType' => 'none',
            'screeningNotes' => 'Corrected after reviewing the paper form.',
        ];

        $this->put(route('bloodcare.admin.donors.screenings.update', [$donor, $screening]), $payload)
            ->assertRedirect(route('bloodcare.admin.donors.show', $donor).'#screening-history');

        $this->assertDatabaseHas('donor_screenings', [
            'id' => $screening->id,
            'weight_kg' => 64.25,
            'notes' => 'Corrected after reviewing the paper form.',
        ]);

        $this->delete(route('bloodcare.admin.donors.screenings.destroy', [$donor, $screening]))
            ->assertRedirect(route('bloodcare.admin.donors.show', $donor).'#screening-history');
        $this->assertDatabaseMissing('donor_screenings', ['id' => $screening->id]);
    }

    public function test_staff_can_correct_and_delete_a_safe_donation_history_record(): void
    {
        $admin = $this->loginAdministrator();
        $donor = $this->createDonor();
        $donation = $this->createScreeningDonation($donor, $admin, 'DON-CORRECT-001');

        $payload = [
            'donorId' => $donor->reference,
            'donationDate' => now()->subDay()->toDateString(),
            'group' => $donor->blood_group,
            'donationType' => 'plasma',
            'quantity' => 350,
            'screeningResult' => 'Pending',
            'status' => 'Screening',
            'appointmentReference' => null,
            'screeningReference' => null,
            'bagUnit' => 'BU-CORRECT-001',
            'expiryDate' => now()->addDays(20)->toDateString(),
            'location' => 'Medical review shelf',
            'notes' => 'Corrected operational details.',
        ];

        $this->put(route('bloodcare.admin.donations.update', $donation->reference), $payload)
            ->assertRedirect(route('bloodcare.admin.donors.show', $donor).'#donation-history');

        $this->assertDatabaseHas('donations', [
            'id' => $donation->id,
            'donation_type' => 'plasma',
            'quantity_ml' => 350,
            'storage_location' => 'Medical review shelf',
        ]);

        $this->delete(route('bloodcare.admin.donations.destroy', $donation->reference))
            ->assertRedirect(route('bloodcare.admin.donors.show', $donor).'#donation-history');
        $this->assertDatabaseMissing('donations', ['id' => $donation->id]);
    }

    public function test_linked_screening_history_cannot_be_deleted_before_its_donation(): void
    {
        $admin = $this->loginAdministrator();
        $donor = $this->createDonor();
        $donation = $this->createScreeningDonation($donor, $admin, 'DON-LINKED-001');
        $screening = $this->createPassedScreening($donor, 'SCR-LINKED-001', now()->subDay());
        $screening->update(['donation_id' => $donation->id]);

        $this->from(route('bloodcare.admin.donors.show', $donor).'#screening-history')
            ->delete(route('bloodcare.admin.donors.screenings.destroy', [$donor, $screening]))
            ->assertSessionHasErrors('screening');

        $this->assertDatabaseHas('donor_screenings', ['id' => $screening->id]);
        $this->assertDatabaseHas('donations', ['id' => $donation->id]);
    }

    private function loginAdministrator(): User
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_at' => now(),
            'is_banned' => false,
        ]);
        backpack_auth()->login($admin);

        return $admin;
    }

    private function createDonor(): Donor
    {
        return Donor::create([
            'reference' => 'BC-CLINICAL-001',
            'full_name' => 'Clinical History Donor',
            'date_of_birth' => '1996-02-03',
            'gender' => 'female',
            'identity_document_type' => 'passport',
            'identity_number' => 'P-CLINICAL-001',
            'passport_number' => 'P-CLINICAL-001',
            'phone' => '09123456789',
            'phone_normalized' => '09123456789',
            'address' => 'Yangon',
            'blood_group' => 'O+',
            'emergency_contact' => '09123456789',
            'consent_at' => now(),
            'status' => 'active',
            'eligibility_status' => 'eligible',
        ]);
    }

    private function createPassedScreening(Donor $donor, string $reference, mixed $screenedAt): DonorScreening
    {
        return DonorScreening::create([
            'reference' => $reference,
            'donor_id' => $donor->id,
            'screened_at' => $screenedAt,
            'next_screening_date' => now()->addDay()->toDateString(),
            'weight_kg' => 60,
            'hemoglobin_level' => 13,
            'systolic_blood_pressure' => 120,
            'diastolic_blood_pressure' => 80,
            'pulse_rate' => 72,
            'body_temperature_celsius' => 36.8,
            'outcome' => 'passed',
            'deferral_type' => 'none',
            'verified_by_staff_id' => backpack_user()?->id,
        ]);
    }

    private function createScreeningDonation(Donor $donor, User $staff, string $reference): Donation
    {
        return Donation::create([
            'reference' => $reference,
            'donor_id' => $donor->id,
            'donation_date' => now()->subDay()->toDateString(),
            'quantity_ml' => 450,
            'blood_group' => $donor->blood_group,
            'donation_type' => 'whole_blood',
            'screening_result' => 'pending',
            'status' => 'screening',
            'bag_unit_number' => 'BU-'.$reference,
            'expires_at' => now()->addDays(20)->toDateString(),
            'storage_location' => 'Medical review shelf',
            'screening_notes' => 'Pending staff decision.',
            'recorded_by' => $staff->id,
        ]);
    }
}
