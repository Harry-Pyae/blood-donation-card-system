<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donation_centres', function (Blueprint $table) {
            $table->id();
            $table->string('code', 24)->unique();
            $table->string('name', 160)->unique();
            $table->string('region', 120);
            $table->string('township', 120);
            $table->text('address')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('opening_hours', 160)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        DB::table('donation_centres')->insert([
            [
                'code' => 'CTR-0001',
                'name' => 'Yangon Central',
                'region' => 'Yangon Region',
                'township' => 'Central Yangon',
                'address' => 'Main BloodCare collection site',
                'phone' => '01 555 0123',
                'opening_hours' => 'Mon–Sat, 08:30–16:30',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'CTR-0002',
                'name' => 'North Okkala',
                'region' => 'Yangon Region',
                'township' => 'North Okkalapa',
                'address' => 'North Okkalapa donation site',
                'phone' => '01 555 0124',
                'opening_hours' => 'Mon–Sat, 08:30–16:30',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'CTR-0003',
                'name' => 'Thingangyun',
                'region' => 'Yangon Region',
                'township' => 'Thingangyun',
                'address' => 'Thingangyun donation site',
                'phone' => '01 555 0125',
                'opening_hours' => 'Mon–Sat, 08:30–16:30',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('donation_centres');
    }
};
