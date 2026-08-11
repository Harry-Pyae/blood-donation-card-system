<?php

namespace Tests\Feature\Api\V1;

use App\Models\Donation;
use App\Models\DonationCard;
use App\Models\DonationCentre;
use App\Models\Donor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDonorJourneyApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Api Journey Donor',
            'date_of_birth' => '1996-05-04',
            'gender' => 'female',
            'identity_document_type' => 'passport',
            'passport_number' => 'AP1234567',
            'phone_local' => '9421000555',
            'email' => 'api.donor@example.com',
            'address' => 'Yangon',
            'blood_group' => 'O+',
            'emergency_contact' => '09 421 000 556',
            'previous_donation' => 'no',
            'health_notes' => null,
            'consent' => '1',
        ], $overrides);
    }

    private function makeDonor(array $overrides = []): Donor
    {
        return Donor::create(array_merge([
            'reference' => Donor::generateReference(),
            'full_name' => 'Lookup Donor',
            'date_of_birth' => '1995-03-03',
            'gender' => 'male',
            'identity_document_type' => 'passport',
            'identity_number' => 'LK1112223',
            'passport_number' => 'LK1112223',
            'phone' => '09 555 111 222',
            'phone_normalized' => '09555111222',
            'address' => 'Yangon',
            'blood_group' => 'B+',
            'emergency_contact' => '09 555 111 223',
            'previous_donation' => false,
            'consent_at' => now(),
            'status' => 'pending',
            'eligibility_status' => 'review',
        ], $overrides));
    }

    // -- Registration --------------------------------------------------------

    public function test_it_registers_a_donor_and_returns_a_minimal_confirmation(): void
    {
        $response = $this->postJson('/api/v1/donors/register', $this->registrationPayload());

        $response->assertCreated();

        $this->assertSame(
            ['reference', 'full_name', 'phone', 'blood_group', 'status', 'eligibility_status'],
            array_keys($response->json('data')),
            'Registration confirmation must not widen beyond the agreed fields.',
        );

        $this->assertDatabaseHas('donors', [
            'full_name' => 'Api Journey Donor',
            'identity_number' => 'AP1234567',
            'phone' => '+95 9421000555',
            'phone_normalized' => '959421000555',
            'status' => 'pending',
            'eligibility_status' => 'review',
        ]);

        $this->assertDatabaseHas('activity_logs', ['source' => 'public-registration']);
    }

    public function test_it_normalises_burmese_numerals_in_the_phone_number(): void
    {
        $response = $this->postJson('/api/v1/donors/register', $this->registrationPayload([
            'passport_number' => 'BN7654321',
            'phone_local' => '၉၄၂၁၀၀၀၇၇၇',
        ]));

        $response->assertCreated();
        $this->assertDatabaseHas('donors', [
            'phone' => '+95 9421000777',
            'phone_normalized' => '959421000777',
        ]);
    }

    public function test_it_returns_field_scoped_422_errors_for_invalid_registration(): void
    {
        $response = $this->postJson('/api/v1/donors/register', $this->registrationPayload([
            'date_of_birth' => now()->subYears(17)->toDateString(),
            'consent' => null,
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['date_of_birth', 'consent']);
    }

    public function test_it_rejects_a_duplicate_identity_number(): void
    {
        $this->postJson('/api/v1/donors/register', $this->registrationPayload())->assertCreated();

        $this->postJson('/api/v1/donors/register', $this->registrationPayload([
            'phone_local' => '9421000999',
        ]))->assertStatus(422)->assertJsonValidationErrors('identity_document_type');

        $this->assertSame(1, Donor::query()->count());
    }

    public function test_it_never_returns_private_donor_fields(): void
    {
        $response = $this->postJson('/api/v1/donors/register', $this->registrationPayload());

        $data = $response->json('data');

        foreach (['id', 'identity_number', 'address', 'email', 'health_notes', 'staff_notes', 'created_at'] as $field) {
            $this->assertArrayNotHasKey($field, $data);
        }

        $response->assertDontSee('AP1234567');
        $response->assertDontSee('api.donor@example.com');
    }

    // -- Booking -------------------------------------------------------------

    public function test_it_books_an_appointment_at_an_active_centre(): void
    {
        $donor = $this->makeDonor();
        $date = now()->addDays(9)->toDateString();

        $response = $this->postJson('/api/v1/appointments', [
            'donor_reference' => $donor->reference,
            'phone' => '09 555 111 222',
            'centre' => 'Yangon Central',
            'appointment_date' => $date,
            'appointment_time' => '10:30',
            'booking_acknowledgement' => '1',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.centre_name', 'Yangon Central');
        $response->assertJsonPath('data.appointment_time', '10:30');
        $response->assertJsonPath('data.is_location_request', false);

        $this->assertDatabaseHas('appointments', [
            'donor_id' => $donor->id,
            'centre_name' => 'Yangon Central',
            'status' => 'pending',
        ]);
    }

    public function test_it_preserves_the_request_location_sentinel(): void
    {
        $donor = $this->makeDonor(['identity_number' => 'LK2223334', 'passport_number' => 'LK2223334']);

        $response = $this->postJson('/api/v1/appointments', [
            'donor_reference' => $donor->reference,
            'phone' => '09 555 111 222',
            'centre' => '__request__',
            'requested_region' => 'Shan State',
            'requested_township' => 'Taunggyi',
            'booking_acknowledgement' => '1',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.is_location_request', true);
        $response->assertJsonPath('data.centre_name', null);
        $response->assertJsonPath('data.requested_township', 'Taunggyi');

        $this->assertDatabaseHas('appointments', [
            'donor_id' => $donor->id,
            'donation_centre_id' => null,
            'requested_region' => 'Shan State',
        ]);
    }

    public function test_it_requires_region_and_township_when_requesting_a_location(): void
    {
        $donor = $this->makeDonor();

        $this->postJson('/api/v1/appointments', [
            'donor_reference' => $donor->reference,
            'phone' => '09 555 111 222',
            'centre' => '__request__',
            'booking_acknowledgement' => '1',
        ])->assertStatus(422)->assertJsonValidationErrors(['requested_region', 'requested_township']);
    }

    public function test_it_rejects_a_booking_beyond_three_months_or_at_an_invalid_time(): void
    {
        $donor = $this->makeDonor();

        $this->postJson('/api/v1/appointments', [
            'donor_reference' => $donor->reference,
            'phone' => '09 555 111 222',
            'centre' => 'Yangon Central',
            'appointment_date' => now()->addMonths(4)->toDateString(),
            'appointment_time' => '10:30',
            'booking_acknowledgement' => '1',
        ])->assertStatus(422)->assertJsonValidationErrors('appointment_date');

        $this->postJson('/api/v1/appointments', [
            'donor_reference' => $donor->reference,
            'phone' => '09 555 111 222',
            'centre' => 'Yangon Central',
            'appointment_date' => now()->addDays(5)->toDateString(),
            'appointment_time' => '11:45',
            'booking_acknowledgement' => '1',
        ])->assertStatus(422)->assertJsonValidationErrors('appointment_time');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_it_rejects_a_booking_at_an_inactive_centre(): void
    {
        $donor = $this->makeDonor();

        DonationCentre::create([
            'code' => 'CTR-OFF-01',
            'name' => 'Closed Centre',
            'region' => 'Yangon Region',
            'township' => 'Dagon',
            'address' => 'Closed site',
            'opening_hours' => 'Closed',
            'is_active' => false,
        ]);

        $this->postJson('/api/v1/appointments', [
            'donor_reference' => $donor->reference,
            'phone' => '09 555 111 222',
            'centre' => 'Closed Centre',
            'appointment_date' => now()->addDays(5)->toDateString(),
            'appointment_time' => '09:00',
            'booking_acknowledgement' => '1',
        ])->assertStatus(422)->assertJsonValidationErrors('centre');
    }

    public function test_it_rejects_a_booking_when_the_phone_does_not_match_the_reference(): void
    {
        $donor = $this->makeDonor();

        $this->postJson('/api/v1/appointments', [
            'donor_reference' => $donor->reference,
            'phone' => '09 000 000 000',
            'centre' => 'Yangon Central',
            'appointment_date' => now()->addDays(5)->toDateString(),
            'appointment_time' => '09:00',
            'booking_acknowledgement' => '1',
        ])->assertStatus(422)->assertJsonValidationErrors('donor_reference');

        $this->assertDatabaseCount('appointments', 0);
    }

    // -- Lookups -------------------------------------------------------------

    public function test_it_looks_up_an_appointment_case_insensitively(): void
    {
        $donor = $this->makeDonor();

        $this->postJson('/api/v1/appointments', [
            'donor_reference' => $donor->reference,
            'phone' => '09 555 111 222',
            'centre' => 'Yangon Central',
            'appointment_date' => now()->addDays(6)->toDateString(),
            'appointment_time' => '13:00',
            'booking_acknowledgement' => '1',
        ])->assertCreated();

        $reference = $donor->appointments()->firstOrFail()->reference;

        $response = $this->postJson('/api/v1/appointments/check', [
            'reference' => strtolower($reference),
            'phone' => '09 555 111 222',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.centre_name', 'Yangon Central');

        $this->assertSame(
            ['reference', 'centre_name', 'requested_region', 'requested_township',
                'appointment_date', 'appointment_time', 'purpose', 'status'],
            array_keys($response->json('data')),
        );

        $response->assertDontSee('Lookup Donor');
        $response->assertDontSee('09555111222');
    }

    public function test_appointment_lookup_requires_the_matching_phone(): void
    {
        $donor = $this->makeDonor();

        $this->postJson('/api/v1/appointments', [
            'donor_reference' => $donor->reference,
            'phone' => '09 555 111 222',
            'centre' => 'Yangon Central',
            'appointment_date' => now()->addDays(6)->toDateString(),
            'appointment_time' => '13:00',
            'booking_acknowledgement' => '1',
        ])->assertCreated();

        $reference = $donor->appointments()->firstOrFail()->reference;

        $this->postJson('/api/v1/appointments/check', [
            'reference' => $reference,
            'phone' => '09 999 888 777',
        ])->assertStatus(422)->assertJsonValidationErrors('reference');

        $this->postJson('/api/v1/appointments/check', [
            'reference' => $reference,
        ])->assertStatus(422)->assertJsonValidationErrors('phone');
    }

    public function test_unknown_and_mismatched_lookups_return_the_same_message(): void
    {
        $donor = $this->makeDonor();

        $this->postJson('/api/v1/appointments', [
            'donor_reference' => $donor->reference,
            'phone' => '09 555 111 222',
            'centre' => 'Yangon Central',
            'appointment_date' => now()->addDays(6)->toDateString(),
            'appointment_time' => '13:00',
            'booking_acknowledgement' => '1',
        ])->assertCreated();

        $reference = $donor->appointments()->firstOrFail()->reference;

        $unknown = $this->postJson('/api/v1/appointments/check', [
            'reference' => 'APT-000000-ZZZZZ',
            'phone' => '09 555 111 222',
        ]);

        $mismatched = $this->postJson('/api/v1/appointments/check', [
            'reference' => $reference,
            'phone' => '09 999 888 777',
        ]);

        // Identical responses, so a caller cannot tell a real reference from a
        // fabricated one and enumerate donors.
        $this->assertSame(
            $unknown->json('errors.reference'),
            $mismatched->json('errors.reference'),
        );
    }

    public function test_it_looks_up_a_donation_card_with_public_safe_fields_only(): void
    {
        $donor = $this->makeDonor([
            'last_donation_date' => now()->subMonths(4)->toDateString(),
            'next_eligible_date' => now()->addMonths(1)->toDateString(),
            'staff_notes' => 'Internal staff note',
        ]);

        $card = DonationCard::create([
            'card_number' => 'CARD-TEST-001',
            'donor_id' => $donor->id,
            'issued_at' => now()->subMonths(4),
            'expires_at' => now()->addYear(),
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/cards/check', [
            'reference' => 'card-test-001',
            'phone' => '09 555 111 222',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.donor_name', 'Lookup Donor');
        $response->assertJsonPath('data.blood_group', 'B+');
        $response->assertJsonPath('data.total_donations', 0);

        $this->assertSame(
            ['card_number', 'donor_name', 'blood_group', 'last_donation_date',
                'next_eligible_date', 'total_donations', 'status'],
            array_keys($response->json('data')),
        );

        // The QR token is the traceability credential and must never leak.
        $response->assertDontSee($card->qr_token);
        $response->assertDontSee('Internal staff note');
        $response->assertDontSee('LK1112223');
    }

    public function test_card_lookup_rejects_a_mismatched_phone(): void
    {
        $donor = $this->makeDonor();

        DonationCard::create([
            'card_number' => 'CARD-TEST-002',
            'donor_id' => $donor->id,
            'issued_at' => now(),
            'expires_at' => now()->addYear(),
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/cards/check', [
            'reference' => 'CARD-TEST-002',
            'phone' => '09 000 000 000',
        ])->assertStatus(422)->assertJsonValidationErrors('reference');
    }

    // -- Supporting endpoints ------------------------------------------------

    public function test_it_exposes_booking_options_matching_the_backend_rules(): void
    {
        $response = $this->getJson('/api/v1/appointments/options');

        $response->assertOk();
        $response->assertJsonPath('data.times', ['09:00', '10:30', '13:00', '14:30']);
        $response->assertJsonPath('data.request_location_value', '__request__');
        $response->assertJsonPath('data.max_date', now()->addMonths(3)->toDateString());
    }

    public function test_it_exposes_nrc_reference_data_for_the_registration_form(): void
    {
        $response = $this->getJson('/api/v1/donors/nrc-reference');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'states' => [['code', 'en', 'my']],
                'types' => [['code', 'en', 'my']],
                'townships',
            ],
        ]);
    }
}
