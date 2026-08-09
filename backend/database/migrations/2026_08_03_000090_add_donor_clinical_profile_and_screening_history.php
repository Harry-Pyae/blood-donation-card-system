<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donors', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('donation_type_preference', 30)->default('whole_blood')->after('blood_group');
            $table->string('deferral_type', 20)->default('none')->after('eligibility_status');
            $table->string('deferral_reason', 255)->nullable()->after('deferral_type');
            $table->date('deferral_end_date')->nullable()->after('deferral_reason');

            $table->unique('user_id');
            $table->index(['deferral_type', 'deferral_end_date']);
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->string('donation_type', 30)->default('whole_blood')->after('blood_group')->index();
        });

        Schema::create('donor_screenings', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('donor_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('donation_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('donation_centre_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('screened_at')->index();
            $table->date('next_screening_date')->nullable()->index();
            $table->decimal('weight_kg', 5, 2);
            $table->decimal('hemoglobin_level', 4, 2);
            $table->unsignedSmallInteger('systolic_blood_pressure');
            $table->unsignedSmallInteger('diastolic_blood_pressure');
            $table->unsignedSmallInteger('pulse_rate');
            $table->decimal('body_temperature_celsius', 4, 2);
            $table->boolean('medication_flag')->default(false);
            $table->text('current_medications')->nullable();
            $table->boolean('recent_travel_flag')->default(false);
            $table->text('recent_travel_details')->nullable();
            $table->boolean('high_risk_activity_flag')->default(false);
            $table->text('high_risk_activity_details')->nullable();
            $table->string('outcome', 20)->default('pending')->index();
            $table->string('deferral_type', 20)->default('none');
            $table->string('deferral_reason', 255)->nullable();
            $table->date('deferral_end_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('verified_by_staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['donor_id', 'screened_at']);
            $table->index(['appointment_id', 'outcome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donor_screenings');

        Schema::table('donations', function (Blueprint $table) {
            $table->dropIndex(['donation_type']);
            $table->dropColumn('donation_type');
        });

        Schema::table('donors', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
            $table->dropIndex(['deferral_type', 'deferral_end_date']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn([
                'donation_type_preference',
                'deferral_type',
                'deferral_reason',
                'deferral_end_date',
            ]);
        });
    }
};
