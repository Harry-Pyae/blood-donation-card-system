<?php

namespace Tests\Feature\Api\V1;

use App\Models\DonationCentre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CentreApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_only_the_public_safe_centre_fields(): void
    {
        $response = $this->getJson('/api/v1/centres');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                ['name', 'region', 'township', 'address', 'hours', 'active'],
            ],
        ]);

        $first = $response->json('data.0');

        $this->assertSame(
            ['name', 'region', 'township', 'address', 'hours', 'active'],
            array_keys($first),
            'The centre payload must expose exactly the six public fields.',
        );
    }

    public function test_it_never_exposes_internal_or_contact_fields(): void
    {
        DonationCentre::create([
            'code' => 'CTR-TEST-01',
            'name' => 'Privacy Probe Centre',
            'region' => 'Yangon Region',
            'township' => 'Hlaing',
            'address' => 'Privacy probe address',
            'phone' => '01 555 9999',
            'opening_hours' => 'Mon–Fri, 09:00–17:00',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/centres');

        $response->assertOk();

        foreach ($response->json('data') as $centre) {
            $this->assertArrayNotHasKey('id', $centre);
            $this->assertArrayNotHasKey('code', $centre);
            $this->assertArrayNotHasKey('phone', $centre);
            $this->assertArrayNotHasKey('created_at', $centre);
            $this->assertArrayNotHasKey('updated_at', $centre);
        }

        $response->assertDontSee('CTR-TEST-01');
        $response->assertDontSee('01 555 9999');
    }

    public function test_it_excludes_inactive_centres(): void
    {
        DonationCentre::create([
            'code' => 'CTR-TEST-02',
            'name' => 'Decommissioned Centre',
            'region' => 'Yangon Region',
            'township' => 'Dagon',
            'address' => 'Closed site',
            'phone' => '01 555 8888',
            'opening_hours' => 'Closed',
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/v1/centres');

        $response->assertOk();

        $names = array_column($response->json('data'), 'name');

        $this->assertNotContains('Decommissioned Centre', $names);

        foreach ($response->json('data') as $centre) {
            $this->assertTrue($centre['active']);
        }
    }

    public function test_it_orders_centres_by_region_then_township_then_name(): void
    {
        DonationCentre::create([
            'code' => 'CTR-TEST-03',
            'name' => 'Pathein General',
            'region' => 'Ayeyarwady Region',
            'township' => 'Pathein',
            'address' => 'Pathein donation site',
            'phone' => '042 555 0100',
            'opening_hours' => 'Mon–Sat, 08:30–16:30',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/centres');

        $response->assertOk();

        $this->assertSame('Pathein General', $response->json('data.0.name'));

        $sortKeys = array_map(
            static fn (array $centre): string => $centre['region'].'|'.$centre['township'].'|'.$centre['name'],
            $response->json('data'),
        );

        $expected = $sortKeys;
        sort($expected, SORT_STRING);

        $this->assertSame($expected, $sortKeys);
    }
}
