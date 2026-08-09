<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('blood_units', 'trace_token')) {
            Schema::table('blood_units', function (Blueprint $table): void {
                $table->string('trace_token', 100)->nullable()->unique()->after('unit_number');
            });
        }

        DB::table('blood_units')
            ->whereNull('trace_token')
            ->orderBy('id')
            ->chunkById(200, function ($units): void {
                foreach ($units as $unit) {
                    DB::table('blood_units')->where('id', $unit->id)->update([
                        'trace_token' => Str::uuid()->toString(),
                    ]);
                }
            });

        // The card schema has carried qr_token since v1. Backfill any legacy
        // card that predates token generation so every current card can scan.
        DB::table('donation_cards')
            ->whereNull('qr_token')
            ->orderBy('id')
            ->chunkById(200, function ($cards): void {
                foreach ($cards as $card) {
                    DB::table('donation_cards')->where('id', $card->id)->update([
                        'qr_token' => Str::uuid()->toString(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('blood_units', 'trace_token')) {
            Schema::table('blood_units', function (Blueprint $table): void {
                $table->dropUnique('blood_units_trace_token_unique');
                $table->dropColumn('trace_token');
            });
        }
    }
};
