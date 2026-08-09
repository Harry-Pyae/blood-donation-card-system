<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\BloodUnit;
use App\Models\Donation;
use App\Models\DonationCentre;
use App\Models\DonationCard;
use App\Models\Donor;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWorkflowUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_card_issue_form_has_searchable_verified_donor_and_card_detail_sections(): void
    {
        $this->loginAdministrator();
        $this->createEligibleDonor();

        $this->get(route('bloodcare.admin.cards'))
            ->assertOk()
            ->assertSee('bc-card-issue-sections', false)
            ->assertSee('id="bc-card-donor-search"', false)
            ->assertSee('id="bc-card-donor-results"', false)
            ->assertSee('vendor/bloodcare-qrcode/qrcode.js', false)
            ->assertSee(__('bloodcare.cards.back_side'))
            ->assertSee('donorSearchUrl', false)
            ->assertSee(__('bloodcare.cards.card_section_title'));

        $script = file_get_contents(public_path('js/bloodcare-cards.js'));
        $styles = file_get_contents(public_path('css/bloodcare-admin.css'));
        $this->assertIsString($script);
        $this->assertIsString($styles);
        $this->assertStringContainsString('bc-donation-card-back', $script);
        $this->assertStringContainsString('data-bc-qr-url', $script);
        $this->assertStringContainsString('.bc-card-preview-stack', $styles);
        $this->assertStringContainsString('html.bc-print-card .bc-card-preview-stack', $styles);
    }

    public function test_card_donor_search_is_server_side_and_not_limited_to_initial_results(): void
    {
        $this->loginAdministrator();

        foreach (range(1, 8) as $index) {
            $this->createEligibleDonor([
                'reference' => sprintf('BC-SEARCH-%02d', $index),
                'full_name' => sprintf('Alpha Donor %02d', $index),
                'identity_number' => sprintf('P-SEARCH-%02d', $index),
                'passport_number' => sprintf('P-SEARCH-%02d', $index),
                'phone' => sprintf('09111111%03d', $index),
                'phone_normalized' => sprintf('09111111%03d', $index),
            ]);
        }

        $target = $this->createEligibleDonor([
            'reference' => 'BC-SEARCH-TARGET',
            'full_name' => 'Zeta Scalable Target',
            'identity_number' => 'P-SEARCH-TARGET',
            'passport_number' => 'P-SEARCH-TARGET',
            'phone' => '09999999999',
            'phone_normalized' => '09999999999',
        ]);
        $this->createEligibleDonor([
            'reference' => 'BC-SEARCH-DEFERRED',
            'full_name' => 'Deferred Search Candidate',
            'identity_number' => 'P-SEARCH-DEFERRED',
            'passport_number' => 'P-SEARCH-DEFERRED',
            'phone' => '09888888888',
            'phone_normalized' => '09888888888',
            'eligibility_status' => 'deferred',
        ]);
        $issued = $this->createEligibleDonor([
            'reference' => 'BC-SEARCH-ISSUED',
            'full_name' => 'Issued Search Candidate',
            'identity_number' => 'P-SEARCH-ISSUED',
            'passport_number' => 'P-SEARCH-ISSUED',
            'phone' => '09777777777',
            'phone_normalized' => '09777777777',
        ]);
        DonationCard::create([
            'card_number' => 'CARD-SEARCH-ISSUED',
            'donor_id' => $issued->id,
            'issued_at' => now()->toDateString(),
            'expires_at' => now()->addYears(5)->toDateString(),
            'status' => 'active',
        ]);

        $this->getJson(route('bloodcare.admin.cards.donors.search'))
            ->assertOk()
            ->assertJsonCount(8, 'donors')
            ->assertJsonMissing(['id' => $target->reference]);

        $this->getJson(route('bloodcare.admin.cards.donors.search', ['q' => 'Scalable Target']))
            ->assertOk()
            ->assertJsonCount(1, 'donors')
            ->assertJsonPath('donors.0.id', $target->reference)
            ->assertJsonPath('donors.0.name', $target->full_name)
            ->assertJsonPath('limit', 8);

        $this->getJson(route('bloodcare.admin.cards.donors.search', ['q' => 'Deferred Search Candidate']))
            ->assertOk()
            ->assertJsonCount(0, 'donors');
        $this->getJson(route('bloodcare.admin.cards.donors.search', ['q' => 'Issued Search Candidate']))
            ->assertOk()
            ->assertJsonCount(0, 'donors');

        $script = file_get_contents(public_path('js/bloodcare-cards.js'));
        $this->assertIsString($script);
        $this->assertStringContainsString('searchVerifiedDonors', $script);
        $this->assertStringContainsString('donorSearchDebounceMs', $script);
    }

    public function test_donation_form_exposes_real_appointment_references_for_the_selected_donor(): void
    {
        $this->loginAdministrator();
        $donor = $this->createEligibleDonor();
        $centre = DonationCentre::create([
            'code' => 'CTR-UI-001',
            'name' => 'Asia Royal',
            'region' => 'Yangon',
            'township' => 'Bahan',
            'address' => 'Test address',
            'is_active' => true,
        ]);
        Appointment::create([
            'reference' => 'APT-UI-000001',
            'donor_id' => $donor->id,
            'donation_centre_id' => $centre->id,
            'centre_name' => $centre->name,
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '10:00',
            'purpose' => 'Donation',
            'source' => 'Staff',
            'status' => 'checked_in',
        ]);

        $this->get(route('bloodcare.admin.donations'))
            ->assertOk()
            ->assertSee('id="bc-donation-appointment"', false)
            ->assertSee('APT-UI-000001')
            ->assertSee(__('bloodcare.donations.no_linked_appointment'));
    }

    public function test_centre_name_is_rejected_with_a_helpful_appointment_message(): void
    {
        $this->loginAdministrator();
        $donor = $this->createEligibleDonor();

        $this->postJson(route('bloodcare.admin.donations.store'), [
            'donorId' => $donor->reference,
            'donationDate' => now()->toDateString(),
            'group' => $donor->blood_group,
            'quantity' => 450,
            'screeningResult' => 'Passed',
            'status' => 'Accepted',
            'appointmentReference' => 'Asia Royal',
            'bagUnit' => 'BU-UI-000001',
            'expiryDate' => now()->addDays(42)->toDateString(),
            'location' => 'Cold room A',
            'notes' => 'Appointment validation regression test.',
        ])->assertUnprocessable()
            ->assertJsonPath(
                'errors.appointmentReference.0',
                __('bloodcare.donations.invalid_appointment_reference'),
            );
    }

    public function test_reports_use_accessible_section_buttons_and_searchable_centre_activity(): void
    {
        $this->loginAdministrator();

        $response = $this->get(route('bloodcare.admin.reports'));

        $response->assertOk()
            ->assertSee('class="bc-report-section-nav"', false)
            ->assertSee('data-bc-report-section-button="summary"', false)
            ->assertSee('data-bc-report-section-button="centres"', false)
            ->assertSee('data-bc-report-section-panel="insights"', false)
            ->assertSee('id="bc-report-centre-search"', false)
            ->assertSee(__('bloodcare.reports.search_centres'));

        $script = file_get_contents(public_path('js/bloodcare-reports.js'));
        $styles = file_get_contents(public_path('css/bloodcare-admin.css'));

        $this->assertIsString($script);
        $this->assertIsString($styles);
        $this->assertStringContainsString('initializeSectionNavigation', $script);
        $this->assertStringContainsString('state.centreSearch', $script);
        $this->assertStringContainsString('.bc-report-section-panel[hidden]', $styles);
        $this->assertStringContainsString('.bc-report-centre-toolbar', $styles);
    }

    public function test_management_tables_keep_newly_created_records_at_the_top(): void
    {
        $admin = $this->loginAdministrator();
        $donor = $this->createEligibleDonor();
        $centre = DonationCentre::create([
            'code' => 'CTR-ORDER-001',
            'name' => 'Ordering Regression Centre',
            'region' => 'Yangon Region',
            'township' => 'Bahan',
            'address' => 'Ordering test address',
            'is_active' => true,
        ]);

        Appointment::create([
            'reference' => 'APT-ORDER-OLDER',
            'donor_id' => $donor->id,
            'donation_centre_id' => $centre->id,
            'centre_name' => $centre->name,
            'appointment_date' => now()->addDays(10)->toDateString(),
            'appointment_time' => '16:00',
            'purpose' => 'Donation',
            'source' => 'Staff',
            'status' => 'pending',
        ]);
        $newAppointment = Appointment::create([
            'reference' => 'APT-ORDER-NEWEST',
            'donor_id' => $donor->id,
            'donation_centre_id' => $centre->id,
            'centre_name' => $centre->name,
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '08:00',
            'purpose' => 'Donation',
            'source' => 'Staff',
            'status' => 'pending',
        ]);

        Donation::create([
            'reference' => 'DON-ORDER-OLDER',
            'donor_id' => $donor->id,
            'donation_centre_id' => $centre->id,
            'donation_date' => now()->toDateString(),
            'quantity_ml' => 450,
            'blood_group' => 'O+',
            'screening_result' => 'passed',
            'status' => 'screening',
            'recorded_by' => $admin->id,
        ]);
        $newDonation = Donation::create([
            'reference' => 'DON-ORDER-NEWEST',
            'donor_id' => $donor->id,
            'donation_centre_id' => $centre->id,
            'donation_date' => now()->subYear()->toDateString(),
            'quantity_ml' => 450,
            'blood_group' => 'O+',
            'screening_result' => 'passed',
            'status' => 'screening',
            'recorded_by' => $admin->id,
        ]);

        BloodUnit::create([
            'unit_number' => 'BU-ORDER-OLDER',
            'blood_group' => 'O+',
            'collected_at' => now()->toDateString(),
            'expires_at' => now()->addDays(42)->toDateString(),
            'storage_location' => 'Cold room A',
            'status' => 'available',
        ]);
        $newUnit = BloodUnit::create([
            'unit_number' => 'BU-ORDER-NEWEST',
            'blood_group' => 'O+',
            'collected_at' => now()->subYear()->toDateString(),
            'expires_at' => now()->addDays(42)->toDateString(),
            'storage_location' => 'Cold room A',
            'status' => 'available',
        ]);

        Hospital::create([
            'code' => 'YGN-HSP-ORDER1',
            'name' => 'Alpha Older Hospital',
            'region' => 'Yangon Region',
            'is_active' => true,
        ]);
        $newHospital = Hospital::create([
            'code' => 'YGN-HSP-ORDER2',
            'name' => 'Zulu Newest Hospital',
            'region' => 'Yangon Region',
            'is_active' => true,
        ]);

        $this->get(route('bloodcare.admin.appointments'))
            ->assertOk()
            ->assertViewHas('appointmentData', fn (array $data): bool => $data['records'][0]['id'] === $newAppointment->reference
                && $data['centreRecords'][0]['id'] === $centre->code);

        $this->get(route('bloodcare.admin.donations'))
            ->assertOk()
            ->assertViewHas('donationData', fn (array $data): bool => $data['records'][0]['id'] === $newDonation->reference);

        $this->get(route('bloodcare.admin.inventory'))
            ->assertOk()
            ->assertViewHas('inventoryData', fn (array $data): bool => $data['records'][0]['id'] === $newUnit->unit_number);

        $this->get(route('bloodcare.admin.hospitals'))
            ->assertOk()
            ->assertViewHas('hospitals', fn ($hospitals): bool => $hospitals->first() !== null
                && $hospitals->first()->id === $newHospital->id);

        $appointmentScript = file_get_contents(public_path('js/bloodcare-appointments.js'));
        $donationScript = file_get_contents(public_path('js/bloodcare-donations.js'));
        $this->assertIsString($appointmentScript);
        $this->assertIsString($donationScript);
        $this->assertStringNotContainsString('first.date.localeCompare(second.date)', $appointmentScript);
        $this->assertStringNotContainsString('second.donationDate.localeCompare(first.donationDate)', $donationScript);
    }

    private function loginAdministrator(): User
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_banned' => false,
        ]);
        backpack_auth()->login($admin);

        return $admin;
    }

    private function createEligibleDonor(array $overrides = []): Donor
    {
        return Donor::create(array_merge([
            'reference' => 'BC-UI-000001',
            'full_name' => 'UI Test Donor',
            'date_of_birth' => '1998-01-01',
            'gender' => 'female',
            'identity_document_type' => 'passport',
            'identity_number' => 'P-UI-000001',
            'passport_number' => 'P-UI-000001',
            'phone' => '09123456789',
            'phone_normalized' => '09123456789',
            'address' => 'Yangon',
            'blood_group' => 'O+',
            'emergency_contact' => '09123456789',
            'consent_at' => now(),
            'status' => 'active',
            'eligibility_status' => 'eligible',
        ], $overrides));
    }
}
