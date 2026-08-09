<?php

namespace Database\Seeders;

use App\Models\BloodUnit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FefoDemoStockSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Add standalone, clearly labelled stock for local FEFO/UI testing only.
     *
     * These rows deliberately do not represent real donor-linked inventory.
     * Production stock must be created through donation collection, laboratory
     * release and (when applicable) component processing.
     */
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            $this->command?->error('FEFO demo stock is allowed only in the local or testing environment.');

            return;
        }

        $groups = [
            'A+' => 'APOS',
            'A-' => 'ANEG',
            'B+' => 'BPOS',
            'B-' => 'BNEG',
            'AB+' => 'ABPOS',
            'AB-' => 'ABNEG',
            'O+' => 'OPOS',
            'O-' => 'ONEG',
        ];

        $components = [
            'whole_blood' => ['code' => 'WB', 'expiries' => [14, 30]],
            'red_cells' => ['code' => 'RBC', 'expiries' => [10, 28]],
            'plasma' => ['code' => 'PLS', 'expiries' => [60, 120]],
            'platelets' => ['code' => 'PLT', 'expiries' => [2, 4]],
            'cryoprecipitate' => ['code' => 'CRYO', 'expiries' => [45, 90]],
        ];

        DB::transaction(function () use ($groups, $components): void {
            foreach ($groups as $bloodGroup => $groupCode) {
                foreach ($components as $componentType => $definition) {
                    foreach ($definition['expiries'] as $position => $daysToExpiry) {
                        $sequence = $position + 1;
                        $unitNumber = "BU-DEMO-FEFO-{$groupCode}-{$definition['code']}-{$sequence}";

                        BloodUnit::updateOrCreate(
                            ['unit_number' => $unitNumber],
                            [
                                'donation_id' => null,
                                'parent_blood_unit_id' => null,
                                'blood_group' => $bloodGroup,
                                'component_type' => $componentType,
                                'leukoreduced' => $componentType === 'red_cells' && $sequence === 2,
                                'irradiated' => $componentType === 'red_cells' && $sequence === 2,
                                'washed' => $componentType === 'red_cells' && $sequence === 2,
                                'collected_at' => today()->subDays(2),
                                'expires_at' => today()->addDays($daysToExpiry),
                                'storage_location' => "FEFO Demo / {$groupCode} / {$definition['code']}",
                                'status' => 'available',
                                'released_at' => now()->subDay(),
                                'released_by' => null,
                                'notes' => 'LOCAL DEMO ONLY: released standalone stock for FEFO selector testing.',
                            ],
                        );
                    }
                }
            }

            // Three deliberately unsafe A+ red-cell examples. They must never
            // appear beside the two safe A+ RBC options in the allocation list.
            foreach ([
                ['suffix' => 'EXPIRED', 'status' => 'available', 'expires' => -1, 'released' => true],
                ['suffix' => 'QUAR', 'status' => 'quarantined', 'expires' => 20, 'released' => false],
                ['suffix' => 'RES', 'status' => 'reserved', 'expires' => 20, 'released' => true],
            ] as $unsafe) {
                BloodUnit::updateOrCreate(
                    ['unit_number' => 'BU-DEMO-FEFO-APOS-RBC-'.$unsafe['suffix']],
                    [
                        'donation_id' => null,
                        'parent_blood_unit_id' => null,
                        'blood_group' => 'A+',
                        'component_type' => 'red_cells',
                        'collected_at' => today()->subDays(3),
                        'expires_at' => today()->addDays($unsafe['expires']),
                        'storage_location' => 'FEFO Demo / Safety Exclusions',
                        'status' => $unsafe['status'],
                        'released_at' => $unsafe['released'] ? now()->subDays(2) : null,
                        'released_by' => null,
                        'notes' => 'LOCAL DEMO ONLY: deliberately ineligible FEFO safety-control row.',
                    ],
                );
            }

            DB::table('blood_units')
                ->whereNull('trace_token')
                ->where('unit_number', 'like', 'BU-DEMO-FEFO-%')
                ->orderBy('id')
                ->get(['id', 'unit_number'])
                ->each(function ($unit): void {
                    DB::table('blood_units')->where('id', $unit->id)->update([
                        'trace_token' => 'demo-fefo-'.substr(hash('sha256', $unit->unit_number), 0, 48),
                    ]);
                });
        });

        $this->command?->info('FEFO demo stock is ready: two safe choices for every blood-group/component combination.');
    }
}
