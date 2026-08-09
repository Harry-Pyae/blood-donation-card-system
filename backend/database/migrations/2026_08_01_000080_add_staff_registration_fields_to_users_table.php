<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('job_title', 100)->nullable()->after('phone');
            $table->string('workplace', 150)->nullable()->after('job_title');
            $table->string('approval_status', 20)->default('approved')->after('role');
            $table->timestamp('approved_at')->nullable()->after('approval_status');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at')->index();
            $table->text('registration_note')->nullable()->after('approved_by');
            $table->index(['approval_status', 'is_banned']);
        });

        DB::table('users')->update([
            'approval_status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['approval_status', 'is_banned']);
            $table->dropIndex(['approved_by']);
            $table->dropColumn([
                'phone',
                'job_title',
                'workplace',
                'approval_status',
                'approved_at',
                'approved_by',
                'registration_note',
            ]);
        });
    }
};
