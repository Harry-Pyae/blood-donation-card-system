<?php

namespace Tests\Feature;

use App\Models\BloodUnit;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_demo_seeder_populates_coherent_end_to_end_workflows(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'demo.admin@bloodcare.test', 'role' => 'admin']);
        $this->assertDatabaseHas('users', ['email' => 'demo.lab.admin@bloodcare.test', 'role' => 'lab_admin']);
        $this->assertDatabaseHas('users', ['email' => 'demo.hospital.yangon@bloodcare.test', 'role' => 'hospital']);
        $this->assertDatabaseHas('hospitals', ['code' => 'HSP-DEMO-YGN-01', 'is_active' => true]);
        $this->assertDatabaseHas('hospitals', ['code' => 'HSP-DEMO-BGO-01', 'is_active' => false]);
        $this->assertDatabaseHas('donors', ['reference' => 'BC-DEMO-V2-0010', 'deferral_type' => 'permanent']);
        $this->assertDatabaseHas('appointments', ['reference' => 'APT-DEMO-V2-0007', 'status' => 'checked_in']);
        $this->assertDatabaseHas('donor_screenings', ['reference' => 'SCR-DEMO-V2-READY01', 'outcome' => 'passed', 'donation_id' => null]);

        $quarantinedDonationId = DB::table('donations')->where('reference', 'DON-DEMO-V2-Q01')->value('id');
        $this->assertNotNull($quarantinedDonationId);
        $this->assertDatabaseHas('blood_units', ['unit_number' => 'BU-DEMO-V2-Q01', 'status' => 'quarantined']);
        $this->assertNotNull(BloodUnit::where('unit_number', 'BU-DEMO-V2-Q01')->value('trace_token'));
        $this->assertDatabaseMissing('lab_tests', ['donation_id' => $quarantinedDonationId]);

        $this->assertDatabaseHas('lab_tests', [
            'reference' => 'LAB-DEMO-V2-REL_A_NEG', 'release_status' => 'released',
            'antibody_screen_status' => 'negative', 'malaria_status' => 'negative',
        ]);
        $this->assertDatabaseHas('blood_units', ['unit_number' => 'BU-DEMO-V2-REL01', 'status' => 'available']);
        $this->assertDatabaseHas('lab_tests', [
            'reference' => 'LAB-DEMO-V2-DISCARD', 'hepatitis_b_status' => 'reactive', 'release_status' => 'discarded',
        ]);
        $this->assertDatabaseHas('blood_units', ['unit_number' => 'BU-DEMO-V2-DISC01', 'status' => 'discarded']);

        $plasma = BloodUnit::where('unit_number', 'BU-DEMO-V2-CMP01-PLS')->firstOrFail();
        $cryo = BloodUnit::where('unit_number', 'BU-DEMO-V2-CMP01-CRYO')->firstOrFail();
        $this->assertSame($plasma->id, $cryo->parent_blood_unit_id);
        $this->assertNotNull($cryo->trace_token);
        $this->assertSame('used', $plasma->status);
        $this->assertDatabaseHas('blood_units', [
            'unit_number' => 'BU-DEMO-V2-CMP01-RBC', 'component_type' => 'red_cells',
            'status' => 'available', 'leukoreduced' => true, 'irradiated' => true,
        ]);
        $this->assertDatabaseHas('blood_units', [
            'unit_number' => 'BU-DEMO-V2-CMP02', 'component_type' => 'whole_blood', 'status' => 'processing',
        ]);

        $early = BloodUnit::where('unit_number', 'BU-DEMO-V2-CMP01-RBC')->firstOrFail();
        $late = BloodUnit::where('unit_number', 'BU-DEMO-V2-CMP02-RBC')->firstOrFail();
        $this->assertTrue($early->expires_at->lt($late->expires_at));
        $this->assertTrue($early->leukoreduced && $late->leukoreduced);

        $this->assertDatabaseHas('blood_requests', ['reference' => 'REQ-DEMO-V2-0001', 'status' => 'pending']);
        $this->assertDatabaseHas('blood_requests', ['reference' => 'REQ-DEMO-V2-0002', 'status' => 'approved', 'requires_leukoreduced' => true]);
        $this->assertDatabaseHas('blood_requests', ['reference' => 'REQ-DEMO-V2-0003', 'status' => 'allocated']);
        $this->assertDatabaseHas('blood_units', ['unit_number' => 'BU-DEMO-V2-ALLOC01', 'status' => 'reserved']);
        $this->assertDatabaseHas('blood_requests', ['reference' => 'REQ-DEMO-V2-0004', 'status' => 'transfused']);
        $this->assertDatabaseHas('adverse_reactions', [
            'reference' => 'HVR-DEMO-V2-0001',
            'severity' => 'moderate',
            'status' => 'investigating',
            'reaction_type' => 'febrile_non_hemolytic',
            'imputability' => 'possible',
            'outcome' => 'recovered',
        ]);
        $this->assertDatabaseCount('notifications', 5);

        $this->assertDatabaseCount('donors', 14);
        $this->assertDatabaseCount('appointments', 8);
        $this->assertDatabaseCount('donation_cards', 8);
        $this->assertDatabaseCount('donations', 13);
        $this->assertDatabaseCount('donor_screenings', 14);
        $this->assertDatabaseCount('lab_tests', 10);
        $this->assertDatabaseCount('blood_units', 16);
        $this->assertDatabaseCount('blood_requests', 5);
        $this->assertDatabaseCount('blood_allocations', 2);
        $this->assertDatabaseCount('adverse_reactions', 1);
        $this->assertDatabaseCount('activity_logs', 10);

        $counts = collect([
            'users', 'donation_centres', 'hospitals', 'donors', 'appointments', 'donation_cards',
            'donations', 'donor_screenings', 'lab_tests', 'blood_units', 'blood_requests',
            'blood_allocations', 'adverse_reactions', 'activity_logs', 'notifications',
        ])->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()]);

        $this->seed(DemoDataSeeder::class);

        $counts->each(
            fn (int $count, string $table) => $this->assertSame($count, DB::table($table)->count(), $table),
        );
    }

    public function test_demo_cleanup_removes_old_and_current_demo_records_but_preserves_manual_data_and_base_centres(): void
    {
        User::factory()->create([
            'name' => 'Preserved Administrator', 'email' => 'preserved.admin@example.com', 'role' => User::ROLE_ADMIN,
        ]);
        $this->seed(DemoDataSeeder::class);

        $this->artisan('bloodcare:demo-data:clear', ['--force' => true])->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'preserved.admin@example.com']);
        $this->assertDatabaseHas('donation_centres', ['code' => 'CTR-0001']);
        $this->assertDatabaseHas('donation_centres', ['code' => 'CTR-0002']);
        $this->assertDatabaseHas('donation_centres', ['code' => 'CTR-0003']);
        $this->assertDatabaseMissing('users', ['email' => 'demo.lab.admin@bloodcare.test']);
        $this->assertDatabaseMissing('hospitals', ['code' => 'HSP-DEMO-YGN-01']);
        $this->assertDatabaseMissing('donors', ['reference' => 'BC-DEMO-V2-0001']);
        $this->assertDatabaseMissing('blood_units', ['unit_number' => 'BU-DEMO-V2-CMP01-RBC']);
        $this->assertDatabaseMissing('lab_tests', ['reference' => 'LAB-DEMO-V2-DISCARD']);
        $this->assertDatabaseMissing('blood_requests', ['reference' => 'REQ-DEMO-V2-0004']);
        $this->assertDatabaseMissing('adverse_reactions', ['reference' => 'HVR-DEMO-V2-0001']);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('donation_centres', 3);
        $this->assertDatabaseCount('hospitals', 0);
        $this->assertDatabaseCount('donors', 0);
        $this->assertDatabaseCount('appointments', 0);
        $this->assertDatabaseCount('donation_cards', 0);
        $this->assertDatabaseCount('donations', 0);
        $this->assertDatabaseCount('donor_screenings', 0);
        $this->assertDatabaseCount('lab_tests', 0);
        $this->assertDatabaseCount('blood_units', 0);
        $this->assertDatabaseCount('blood_requests', 0);
        $this->assertDatabaseCount('blood_allocations', 0);
        $this->assertDatabaseCount('adverse_reactions', 0);
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_demo_reset_restores_the_quarantined_lab_scenario_to_a_clean_start(): void
    {
        $this->seed(DemoDataSeeder::class);
        $quarantined = BloodUnit::where('unit_number', 'BU-DEMO-V2-Q01')->firstOrFail();
        $quarantined->update(['status' => 'available', 'released_at' => now()]);

        $this->artisan('bloodcare:demo-data:reset', ['--force' => true])->assertSuccessful();

        $this->assertDatabaseHas('blood_units', [
            'unit_number' => 'BU-DEMO-V2-Q01', 'status' => 'quarantined', 'released_at' => null,
        ]);
        $donationId = DB::table('donations')->where('reference', 'DON-DEMO-V2-Q01')->value('id');
        $this->assertDatabaseMissing('lab_tests', ['donation_id' => $donationId]);
        $this->assertDatabaseCount('blood_units', 16);
        $this->assertDatabaseCount('notifications', 5);
    }
}
