<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donation_cards', function (Blueprint $table) {
            $table->id();
            $table->string('card_number', 40)->unique();
            $table->foreignId('donor_id')->unique()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->string('status', 24)->default('pending')->index();
            $table->string('qr_token', 100)->nullable()->unique();
            $table->unsignedSmallInteger('replacement_count')->default(0);
            $table->unsignedSmallInteger('print_count')->default(0);
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donation_cards');
    }
};
