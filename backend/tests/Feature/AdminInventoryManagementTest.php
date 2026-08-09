<?php

namespace Tests\Feature;

use App\Models\BloodUnit;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_adjustments_require_a_note_and_alerts_use_display_labels(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_banned' => false,
        ]);
        backpack_auth()->login($admin);

        $this->postJson(route('bloodcare.admin.inventory.store'), [
            'id' => 'BU-REQUIRED-NOTE-001',
            'group' => 'O+',
            'collected' => now()->subDay()->toDateString(),
            'expires' => now()->addDays(41)->toDateString(),
            'location' => 'Cold room A',
            'status' => 'Available',
            'note' => '',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('note')
            ->assertJsonPath('errors.note.0', 'The '.__('bloodcare.inventory.note').' field is required.');

        $this->assertDatabaseMissing('blood_units', [
            'unit_number' => 'BU-REQUIRED-NOTE-001',
        ]);

        $this->get(route('bloodcare.admin.inventory'))
            ->assertOk()
            ->assertSee('id="bc-unit-note" name="note" rows="3" required maxlength="2000"', false);

        $this->get(route('bloodcare.admin.cards'))
            ->assertOk()
            ->assertSee('data-bc-field-label="'.__('bloodcare.cards.donor_search').'"', false);

        $controls = file_get_contents(public_path('js/bloodcare-form-controls.js'));
        $this->assertIsString($controls);
        $this->assertStringContainsString('field.dataset.bcFieldLabel', $controls);
    }

    public function test_staff_cannot_change_an_existing_blood_units_group(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_banned' => false,
        ]);
        $unit = BloodUnit::create([
            'unit_number' => 'BU-IMMUTABLE-001',
            'blood_group' => 'O+',
            'collected_at' => now()->subDay()->toDateString(),
            'expires_at' => now()->addDays(41)->toDateString(),
            'storage_location' => 'Cold room A',
            'status' => 'available',
            'notes' => 'Immutable blood group test.',
        ]);

        backpack_auth()->login($admin);

        $response = $this->putJson(route('bloodcare.admin.inventory.update', [
            'unit' => $unit->unit_number,
        ]), [
            'id' => $unit->unit_number,
            'group' => 'A-',
            'collected' => $unit->collected_at->toDateString(),
            'expires' => $unit->expires_at->toDateString(),
            'location' => $unit->storage_location,
            'status' => 'Available',
            'note' => $unit->notes,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('group');
        $this->assertDatabaseHas('blood_units', [
            'id' => $unit->id,
            'blood_group' => 'O+',
        ]);
    }

    public function test_inventory_details_include_full_inherited_lab_results_and_component_lineage(): void
    {
        $this->seed(DemoDataSeeder::class);
        $admin = User::where('email', 'demo.admin@bloodcare.test')->firstOrFail();
        backpack_auth()->login($admin);

        $response = $this->getJson(route('bloodcare.admin.inventory.details', [
            'unit' => 'BU-DEMO-V2-CMP01-CRYO',
        ]));

        $response->assertOk()
            ->assertJsonPath('unit.componentKey', 'cryoprecipitate')
            ->assertJsonPath('laboratory.inheritedFromDonation', true)
            ->assertJsonCount(4, 'laboratory.mandatoryTests')
            ->assertJsonCount(3, 'laboratory.immunohematology')
            ->assertJsonCount(5, 'laboratory.regionalTests')
            ->assertJsonPath('laboratory.mandatoryTests.0.result', 'negative')
            ->assertJsonPath('laboratory.immunohematology.2.result', 'negative')
            ->assertJsonPath('laboratory.regionalTests.1.result', 'negative')
            ->assertJsonPath('lineage.ancestors.0.id', 'BU-DEMO-V2-CMP01')
            ->assertJsonPath('lineage.ancestors.1.id', 'BU-DEMO-V2-CMP01-PLS');

        $testKeys = collect($response->json('laboratory.mandatoryTests'))
            ->concat($response->json('laboratory.immunohematology'))
            ->concat($response->json('laboratory.regionalTests'))
            ->pluck('key')
            ->values()
            ->all();
        $this->assertSame([
            'hiv', 'hepatitis_b', 'hepatitis_c', 'syphilis',
            'confirmed_group', 'rhd', 'antibody_screen',
            'htlv', 'malaria', 'chagas', 'west_nile', 'zika',
        ], $testKeys);
    }

    public function test_inventory_details_trace_history_covers_hospital_handoff_and_haemovigilance(): void
    {
        $this->seed(DemoDataSeeder::class);
        $admin = User::where('email', 'demo.admin@bloodcare.test')->firstOrFail();
        backpack_auth()->login($admin);

        $response = $this->getJson(route('bloodcare.admin.inventory.details', [
            'unit' => 'BU-DEMO-V2-TX01',
        ]))->assertOk();

        $labels = collect($response->json('traceHistory'))->pluck('label');
        $this->assertTrue($labels->contains(__('bloodcare.inventory.trace.collected')));
        $this->assertTrue($labels->contains(__('bloodcare.inventory.trace.lab_released')));
        $this->assertTrue($labels->contains(__('bloodcare.inventory.trace.allocated')));
        $this->assertTrue($labels->contains(__('bloodcare.inventory.trace.dispatched')));
        $this->assertTrue($labels->contains(__('bloodcare.inventory.trace.received')));
        $this->assertTrue($labels->contains(__('bloodcare.inventory.trace.transfused')));
        $this->assertTrue($labels->contains(__('bloodcare.inventory.trace.reaction')));
        $this->assertTrue($labels->contains(__('bloodcare.inventory.trace.current_state')));
    }

    public function test_inventory_details_keep_genuine_quarantined_units_pending_without_fabricating_lab_results(): void
    {
        $this->seed(DemoDataSeeder::class);
        $admin = User::where('email', 'demo.admin@bloodcare.test')->firstOrFail();
        backpack_auth()->login($admin);

        $response = $this->getJson(route('bloodcare.admin.inventory.details', [
            'unit' => 'BU-DEMO-V2-Q01',
        ]));

        $response->assertOk()
            ->assertJsonPath('unit.status', 'quarantined')
            ->assertJsonPath('laboratory', null)
            ->assertJsonPath('laboratoryPending', true);
    }
}
