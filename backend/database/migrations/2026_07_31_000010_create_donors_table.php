<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donors', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 32)->unique();
            $table->string('full_name', 120);
            $table->date('date_of_birth');
            $table->string('gender', 20);
            $table->string('identity_document_type', 20);
            $table->string('identity_number', 80)->unique();
            $table->string('nrc_state', 10)->nullable();
            $table->string('nrc_township', 40)->nullable();
            $table->string('nrc_type', 20)->nullable();
            $table->string('nrc_serial', 6)->nullable();
            $table->string('passport_number', 30)->nullable();
            $table->string('phone', 30);
            $table->string('phone_normalized', 30)->index();
            $table->string('email', 120)->nullable()->index();
            $table->text('address');
            $table->string('blood_group', 10)->default('unknown')->index();
            $table->string('emergency_contact', 120);
            $table->boolean('previous_donation')->default(false);
            $table->text('health_notes')->nullable();
            $table->timestamp('consent_at')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->string('eligibility_status', 20)->default('review')->index();
            $table->date('last_donation_date')->nullable();
            $table->date('next_eligible_date')->nullable();
            $table->text('staff_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donors');
    }
};
