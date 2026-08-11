<?php

namespace Tests\Feature;

use App\Http\Requests\Public\PublicLookupRequest;
use App\Http\Requests\Public\StoreAppointmentRequest;
use App\Http\Requests\Public\StoreDonorRegistrationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Guards the Blade/API shared-logic refactor.
 *
 * The point of extracting the Form Requests and actions was that the public
 * Blade flow and the versioned API can never drift apart. These tests fail if
 * someone reintroduces a second, independent set of rules.
 */
class BladeApiSharedLogicTest extends TestCase
{
    use RefreshDatabase;

    public function test_blade_and_api_registration_use_the_same_form_request(): void
    {
        foreach ([
            [\App\Http\Controllers\PublicSiteController::class, 'storeDonorRegistration'],
            [\App\Http\Controllers\Api\V1\DonorRegistrationController::class, 'store'],
        ] as [$class, $method]) {
            $type = (new ReflectionMethod($class, $method))->getParameters()[0]->getType();

            $this->assertSame(
                StoreDonorRegistrationRequest::class,
                $type?->getName(),
                "{$class}::{$method} must validate through the shared Form Request.",
            );
        }
    }

    public function test_blade_and_api_booking_use_the_same_form_request(): void
    {
        foreach ([
            [\App\Http\Controllers\PublicSiteController::class, 'storeAppointment'],
            [\App\Http\Controllers\Api\V1\AppointmentController::class, 'store'],
        ] as [$class, $method]) {
            $type = (new ReflectionMethod($class, $method))->getParameters()[0]->getType();

            $this->assertSame(StoreAppointmentRequest::class, $type?->getName());
        }
    }

    public function test_blade_and_api_lookups_use_the_same_form_request(): void
    {
        foreach ([
            [\App\Http\Controllers\PublicSiteController::class, 'showAppointment'],
            [\App\Http\Controllers\PublicSiteController::class, 'showCard'],
            [\App\Http\Controllers\Api\V1\AppointmentController::class, 'check'],
            [\App\Http\Controllers\Api\V1\DonationCardController::class, 'check'],
        ] as [$class, $method]) {
            $type = (new ReflectionMethod($class, $method))->getParameters()[0]->getType();

            $this->assertSame(PublicLookupRequest::class, $type?->getName());
        }
    }

    public function test_the_booking_time_slots_are_defined_once(): void
    {
        $this->assertSame(
            ['09:00', '10:30', '13:00', '14:30'],
            StoreAppointmentRequest::TIMES,
        );

        $this->assertSame('__request__', StoreAppointmentRequest::REQUEST_LOCATION);
    }
}
