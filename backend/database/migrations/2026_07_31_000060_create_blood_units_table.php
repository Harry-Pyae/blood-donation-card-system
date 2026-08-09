<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blood_units', function (Blueprint $table) {
            $table->id();
            $table->string('unit_number', 40)->unique();
            $table->foreignId('donation_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('blood_group', 10)->index();
            $table->date('collected_at');
            $table->date('expires_at')->index();
            $table->string('storage_location', 120);
            $table->string('status', 30)->default('available')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_units');
    }
};
