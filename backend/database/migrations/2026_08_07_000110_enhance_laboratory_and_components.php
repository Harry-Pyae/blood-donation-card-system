<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_tests', function (Blueprint $table): void {
            $table->string('rhd_type', 10)->nullable()->after('confirmed_blood_group');
            $table->string('antibody_screen_status', 15)->nullable()->after('rhd_type');
            $table->string('htlv_status', 20)->default('not_required')->after('antibody_screen_status');
            $table->string('malaria_status', 20)->default('not_required')->after('htlv_status');
            $table->string('chagas_status', 20)->default('not_required')->after('malaria_status');
            $table->string('west_nile_status', 20)->default('not_required')->after('chagas_status');
            $table->string('zika_status', 20)->default('not_required')->after('west_nile_status');
        });

        Schema::table('blood_units', function (Blueprint $table): void {
            $table->boolean('leukoreduced')->default(false)->after('component_type');
            $table->boolean('irradiated')->default(false)->after('leukoreduced');
            $table->boolean('washed')->default(false)->after('irradiated');
        });

        Schema::table('blood_requests', function (Blueprint $table): void {
            $table->boolean('requires_leukoreduced')->default(false)->after('component_type');
            $table->boolean('requires_irradiated')->default(false)->after('requires_leukoreduced');
            $table->boolean('requires_washed')->default(false)->after('requires_irradiated');
        });
    }

    public function down(): void
    {
        Schema::table('blood_requests', function (Blueprint $table): void {
            $table->dropColumn(['requires_leukoreduced', 'requires_irradiated', 'requires_washed']);
        });

        Schema::table('blood_units', function (Blueprint $table): void {
            $table->dropColumn(['leukoreduced', 'irradiated', 'washed']);
        });

        Schema::table('lab_tests', function (Blueprint $table): void {
            $table->dropColumn([
                'rhd_type', 'antibody_screen_status', 'htlv_status', 'malaria_status',
                'chagas_status', 'west_nile_status', 'zika_status',
            ]);
        });
    }
};
