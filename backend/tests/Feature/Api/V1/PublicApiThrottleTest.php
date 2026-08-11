<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicApiThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_lookup_endpoints_are_rate_limited(): void
    {
        $payload = ['reference' => 'APT-000000-AAAAA', 'phone' => '09 555 000 000'];

        // The lookup limiter allows 20 requests per minute.
        for ($attempt = 1; $attempt <= 20; $attempt++) {
            $this->postJson('/api/v1/appointments/check', $payload)->assertStatus(422);
        }

        $this->postJson('/api/v1/appointments/check', $payload)->assertStatus(429);
    }

    public function test_registration_is_rate_limited_more_tightly_than_reads(): void
    {
        // Writes allow 10 per minute; an empty body still consumes a slot.
        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->postJson('/api/v1/donors/register', [])->assertStatus(422);
        }

        $this->postJson('/api/v1/donors/register', [])->assertStatus(429);
    }
}
