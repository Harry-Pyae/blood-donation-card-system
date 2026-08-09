<?php

namespace Tests\Feature;

use App\Models\Donor;
use App\Models\User;
use App\Support\MyanmarNrc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDonorManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_register_a_donor_with_structured_nrc_fields(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_banned' => false,
        ]);
        $reference = MyanmarNrc::reference();
        $township = $reference['nrcTownships']['12'][0];

        backpack_auth()->login($admin);

        $response = $this->postJson(route('bloodcare.admin.donors.store'), [
            'name' => 'Admin Registered Donor',
            'group' => 'A+',
            'phone' => '+959987654321',
            'email' => 'admin-donor@example.com',
            'dateOfBirth' => '1995-04-20',
            'gender' => 'Female',
            'identityDocumentType' => 'nrc',
            'nrcState' => '12',
            'nrcTownship' => $township['value'],
            'nrcType' => 'N',
            'nrcSerial' => '123456',
            'passportNumber' => '',
            'address' => 'Yangon',
            'lastDonation' => null,
            'nextEligible' => 'review',
            'eligibility' => 'Review',
            'status' => 'Pending',
            'notes' => 'Created from the Backpack donor editor.',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('donors', [
            'full_name' => 'Admin Registered Donor',
            'identity_document_type' => 'nrc',
            'identity_number' => "12/{$township['display']}(N)123456",
            'nrc_state' => '12',
            'nrc_township' => $township['value'],
            'nrc_type' => 'N',
            'nrc_serial' => '123456',
            'passport_number' => null,
            'last_donation_date' => null,
            'previous_donation' => false,
        ]);
    }

    public function test_registration_modes_keep_linked_accounts_separate_from_new_donors(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_banned' => false,
        ]);
        $publicUser = User::factory()->create([
            'role' => User::ROLE_USER,
            'approval_status' => User::APPROVAL_APPROVED,
            'is_banned' => false,
        ]);

        backpack_auth()->login($admin);

        $this->postJson(route('bloodcare.admin.donors.store'), [
            'registrationMode' => 'linked',
        ])->assertUnprocessable()->assertJsonValidationErrors('userId');

        $this->postJson(route('bloodcare.admin.donors.store'), [
            'registrationMode' => 'new',
            'userId' => $publicUser->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('userId');
    }

    public function test_registration_enforces_exact_eighteenth_birthday_and_plus95_phone(): void
    {
        $this->travelTo(now()->startOfDay());
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_banned' => false,
        ]);
        backpack_auth()->login($admin);

        $payload = [
            'name' => 'Age Boundary Donor',
            'group' => 'O+',
            'phone' => '+959123456789',
            'email' => null,
            'dateOfBirth' => now()->subYears(18)->addDay()->toDateString(),
            'gender' => 'Female',
            'identityDocumentType' => 'passport',
            'passportNumber' => 'AGEBOUNDARY001',
            'address' => 'Yangon',
            'lastDonation' => null,
            'nextEligible' => 'review',
            'eligibility' => 'Review',
            'status' => 'Pending',
            'notes' => null,
        ];

        $this->postJson(route('bloodcare.admin.donors.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('dateOfBirth');

        $payload['dateOfBirth'] = now()->subYears(18)->toDateString();
        $payload['phone'] = '09123456789';
        $this->postJson(route('bloodcare.admin.donors.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('phone');

        unset($payload['phone']);
        $payload['phoneLocal'] = '9123456789';
        $this->postJson(route('bloodcare.admin.donors.store'), $payload)->assertCreated();
        $this->assertDatabaseHas('donors', [
            'full_name' => 'Age Boundary Donor',
            'phone' => '+95 9123456789',
        ]);

        $savedDonor = Donor::query()->where('full_name', 'Age Boundary Donor')->firstOrFail();
        $this->assertSame(now()->subYears(18)->toDateString(), $savedDonor->date_of_birth->toDateString());
    }

    public function test_staff_cannot_change_a_registered_donors_blood_group(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_banned' => false,
        ]);
        $donor = Donor::create([
            'reference' => 'BC-IMMUTABLE-GROUP',
            'full_name' => 'Immutable Blood Group Donor',
            'date_of_birth' => '1994-03-12',
            'gender' => 'female',
            'identity_document_type' => 'passport',
            'identity_number' => 'PIMMUTABLE001',
            'passport_number' => 'PIMMUTABLE001',
            'phone' => '+959912345678',
            'phone_normalized' => '959912345678',
            'address' => 'Yangon',
            'blood_group' => 'A+',
            'emergency_contact' => '09912345678',
            'consent_at' => now(),
            'status' => 'active',
            'eligibility_status' => 'eligible',
        ]);

        backpack_auth()->login($admin);

        $response = $this->putJson(route('bloodcare.admin.donors.update', [
            'donor' => $donor->reference,
        ]), [
            'name' => $donor->full_name,
            'group' => 'B+',
            'phone' => $donor->phone,
            'email' => null,
            'dateOfBirth' => $donor->date_of_birth->toDateString(),
            'gender' => 'Female',
            'identityDocumentType' => 'passport',
            'nrcState' => '',
            'nrcTownship' => '',
            'nrcType' => '',
            'nrcSerial' => '',
            'passportNumber' => $donor->passport_number,
            'address' => $donor->address,
            'lastDonation' => null,
            'nextEligible' => 'now',
            'eligibility' => 'Eligible',
            'status' => 'Active',
            'notes' => null,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('group');
        $this->assertDatabaseHas('donors', [
            'id' => $donor->id,
            'blood_group' => 'A+',
        ]);
    }
}
