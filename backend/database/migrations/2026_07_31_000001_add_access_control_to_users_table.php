<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('user');
            $table->boolean('is_banned')->default(false);
            $table->index(['role', 'is_banned']);
        });

        // Existing Backpack accounts already had staff-workspace access before
        // roles existed. Preserve that access, then choose one bootstrap
        // System Administrator for the restricted Users area.
        DB::table('users')->update(['role' => 'staff']);

        $bootstrapAdministratorId = DB::table('users')
            ->whereIn('name', ['System Administrator', 'Administrator', 'Admin'])
            ->orWhere('email', 'like', 'admin@%')
            ->orderBy('id')
            ->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');

        if ($bootstrapAdministratorId !== null) {
            DB::table('users')
                ->where('id', $bootstrapAdministratorId)
                ->update(['role' => 'admin']);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'is_banned']);
            $table->dropColumn(['role', 'is_banned']);
        });
    }
};
