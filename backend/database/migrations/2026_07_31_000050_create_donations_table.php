<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('donor_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('appointment_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('donation_centre_id')->nullable()->constrained()->nullOnDelete();
            $table->date('donation_date')->index();
            $table->unsignedSmallInteger('quantity_ml')->default(450);
            $table->string('blood_group', 10)->index();
            $table->string('screening_result', 30)->default('pending')->index();
            $table->string('status', 30)->default('screening')->index();
            $table->string('bag_unit_number', 40)->nullable()->unique();
            $table->date('expires_at')->nullable();
            $table->string('storage_location', 120)->nullable();
            $table->text('screening_notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
