<?php

namespace Tests\Feature;

use App\Models\Donor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDonorWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_creates_a_real_donor(): void
    {
        $response = $this->post(route('donor.register.store'), [
            'full_name' => 'Test Donor',
            'date_of_birth' => '1998-04-12',
            'gender' => 'female',
            'identity_document_type' => 'passport',
            'passport_number' => 'PA1234567',
            'phone_local' => '9421000111',
            'email' => 'donor@example.com',
            'address' => 'Yangon',
            'blood_group' => 'O+',
            'emergency_contact' => '09 421 000 222',
            'previous_donation' => 'no',
            'health_notes' => null,
            'consent' => '1',
        ]);

        $response->assertOk();
        $response->assertSee('BC-');

        $this->assertDatabaseHas('donors', [
            'full_name' => 'Test Donor',
            'identity_number' => 'PA1234567',
            'phone' => '+95 9421000111',
            'phone_normalized' => '959421000111',
            'status' => 'pending',
            'eligibility_status' => 'review',
        ]);
    }

    public function test_public_registration_rejects_under_eighteen_and_non_plus95_phone(): void
    {
        $payload = [
            'full_name' => 'Public Boundary Donor',
            'date_of_birth' => now()->subYears(18)->addDay()->toDateString(),
            'gender' => 'male',
            'identity_document_type' => 'passport',
            'passport_number' => 'PUBAGE001',
            'phone' => '+959555444333',
            'email' => null,
            'address' => 'Yangon',
            'blood_group' => 'unknown',
            'emergency_contact' => '09555444334',
            'previous_donation' => 'no',
            'health_notes' => null,
            'consent' => '1',
        ];

        $this->from(route('donor.register'))->post(route('donor.register.store'), $payload)
            ->assertRedirect(route('donor.register'))
            ->assertSessionHasErrors('date_of_birth');

        $payload['date_of_birth'] = now()->subYears(18)->toDateString();
        $payload['phone'] = '09555444333';
        $this->from(route('donor.register'))->post(route('donor.register.store'), $payload)
            ->assertRedirect(route('donor.register'))
            ->assertSessionHasErrors('phone');
    }

    public function test_registered_donor_can_book_and_lookup_an_appointment(): void
    {
        $donor = Donor::create([
            'reference' => Donor::generateReference(),
            'full_name' => 'Appointment Donor',
            'date_of_birth' => '1997-02-01',
            'gender' => 'male',
            'identity_document_type' => 'passport',
            'identity_number' => 'PB7654321',
            'passport_number' => 'PB7654321',
            'phone' => '09 777 123 456',
            'phone_normalized' => '09777123456',
            'email' => null,
            'address' => 'Yangon',
            'blood_group' => 'A+',
            'emergency_contact' => '09 777 123 456',
            'previous_donation' => false,
            'consent_at' => now(),
            'status' => 'pending',
            'eligibility_status' => 'review',
        ]);

        $date = now()->addDays(7)->toDateString();
        $booking = $this->post(route('appointments.book.store'), [
            'donor_reference' => $donor->reference,
            'phone' => '09 777 123 456',
            'centre' => 'Yangon Central',
            'appointment_date' => $date,
            'appointment_time' => '10:30',
            'notes' => 'Test booking',
            'booking_acknowledgement' => '1',
        ]);

        $booking->assertOk();
        $this->assertDatabaseHas('appointments', [
            'donor_id' => $donor->id,
            'centre_name' => 'Yangon Central',
            'status' => 'pending',
        ]);

        $appointment = $donor->appointments()->firstOrFail();
        $this->assertSame($date, $appointment->appointment_date->toDateString());

        $reference = $appointment->reference;
        $lookup = $this->post(route('appointments.check.show'), [
            'reference' => $reference,
            'phone' => '09 777 123 456',
        ]);

        $lookup->assertOk();
        $lookup->assertSee($reference);
        $lookup->assertSee('Yangon Central');
    }

    public function test_appointment_booking_rejects_a_mismatched_phone(): void
    {
        $donor = Donor::create([
            'reference' => Donor::generateReference(),
            'full_name' => 'Protected Donor',
            'date_of_birth' => '1990-01-01',
            'gender' => 'other',
            'identity_document_type' => 'passport',
            'identity_number' => 'PC9876543',
            'passport_number' => 'PC9876543',
            'phone' => '09 111 222 333',
            'phone_normalized' => '09111222333',
            'address' => 'Yangon',
            'blood_group' => 'B+',
            'emergency_contact' => '09 111 222 333',
            'previous_donation' => false,
            'consent_at' => now(),
            'status' => 'pending',
            'eligibility_status' => 'review',
        ]);

        $response = $this->from(route('appointments.book'))->post(route('appointments.book.store'), [
            'donor_reference' => $donor->reference,
            'phone' => '09 000 000 000',
            'centre' => 'Yangon Central',
            'appointment_date' => now()->addDays(4)->toDateString(),
            'appointment_time' => '09:00',
            'booking_acknowledgement' => '1',
        ]);

        $response->assertRedirect(route('appointments.book'));
        $response->assertSessionHasErrors('donor_reference');
        $this->assertDatabaseCount('appointments', 0);
    }
}
