<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('donor_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('donation_centre_id')->nullable()->constrained()->nullOnDelete();
            $table->string('centre_name', 160)->nullable();
            $table->date('appointment_date')->nullable()->index();
            $table->time('appointment_time')->nullable();
            $table->string('requested_region', 120)->nullable();
            $table->string('requested_township', 120)->nullable();
            $table->string('purpose', 40)->default('Donation');
            $table->string('source', 40)->default('Public booking');
            $table->string('status', 30)->default('pending')->index();
            $table->text('notes')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['appointment_date', 'appointment_time']);
            $table->index(['donor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
