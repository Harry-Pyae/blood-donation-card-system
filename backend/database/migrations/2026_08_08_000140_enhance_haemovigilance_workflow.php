<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adverse_reactions', function (Blueprint $table): void {
            $table->string('status', 30)->default('reported')->index();
            $table->string('suspected_reaction_type', 60)->nullable();
            $table->string('reaction_type', 60)->nullable()->index();
            $table->string('imputability', 30)->nullable();
            $table->string('outcome', 30)->nullable();
            $table->text('investigation_notes')->nullable();
            $table->text('corrective_action')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('adverse_reactions', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropIndex(['reaction_type']);
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropConstrainedForeignId('closed_by');
            $table->dropColumn([
                'status', 'suspected_reaction_type', 'reaction_type', 'imputability', 'outcome',
                'investigation_notes', 'corrective_action', 'reviewed_at', 'closed_at',
            ]);
        });
    }
};
