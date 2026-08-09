<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hospitals')) {
            Schema::create('hospitals', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 30)->unique();
                $table->string('name', 180);
                $table->string('region', 100)->nullable();
                $table->string('address', 255)->nullable();
                $table->string('phone', 30)->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('users', 'hospital_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->foreignId('hospital_id')->nullable()->after('workplace')->constrained()->nullOnDelete();
            });
        }

        if (! Schema::hasTable('lab_tests')) {
            Schema::create('lab_tests', function (Blueprint $table): void {
                $table->id();
                $table->string('reference', 40)->unique();
                $table->foreignId('donation_id')->unique()->constrained()->restrictOnDelete();
                $table->string('hiv_status', 15)->default('pending');
                $table->string('hepatitis_b_status', 15)->default('pending');
                $table->string('hepatitis_c_status', 15)->default('pending');
                $table->string('syphilis_status', 15)->default('pending');
                $table->string('confirmed_blood_group', 10)->nullable();
                $table->string('release_status', 20)->default('quarantined')->index();
                $table->text('notes')->nullable();
                $table->foreignId('tested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('tested_at')->nullable();
                $table->timestamp('released_at')->nullable();
                $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // MySQL needs a non-unique donation_id index before the old unique
        // index can be removed because the existing foreign key depends on it.
        if (! Schema::hasIndex('blood_units', 'blood_units_donation_id_index')) {
            Schema::table('blood_units', function (Blueprint $table): void {
                $table->index('donation_id', 'blood_units_donation_id_index');
            });
        }

        if (Schema::hasIndex('blood_units', 'blood_units_donation_id_unique')) {
            Schema::table('blood_units', function (Blueprint $table): void {
                $table->dropUnique('blood_units_donation_id_unique');
            });
        }

        if (! Schema::hasColumn('blood_units', 'component_type')) {
            Schema::table('blood_units', function (Blueprint $table): void {
                $table->string('component_type', 30)->default('whole_blood')->after('blood_group')->index();
            });
        }

        if (! Schema::hasColumn('blood_units', 'parent_blood_unit_id')) {
            Schema::table('blood_units', function (Blueprint $table): void {
                $table->foreignId('parent_blood_unit_id')->nullable()->after('donation_id')->constrained('blood_units')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('blood_units', 'released_at')) {
            Schema::table('blood_units', function (Blueprint $table): void {
                $table->timestamp('released_at')->nullable()->after('status');
            });
        }

        if (! Schema::hasColumn('blood_units', 'released_by')) {
            Schema::table('blood_units', function (Blueprint $table): void {
                $table->foreignId('released_by')->nullable()->after('released_at')->constrained('users')->nullOnDelete();
            });
        }

        if (! Schema::hasIndex('blood_units', 'blood_units_donation_id_component_type_index')) {
            Schema::table('blood_units', function (Blueprint $table): void {
                $table->index(['donation_id', 'component_type']);
            });
        }

        // Safety upgrade: donation-linked stock must be laboratory released before use.
        DB::table('blood_units')
            ->whereNotNull('donation_id')
            ->where('status', 'available')
            ->update(['status' => 'quarantined']);

        if (! Schema::hasTable('blood_requests')) {
            Schema::create('blood_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('hospital_id')->constrained()->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->string('patient_reference', 80);
            $table->string('blood_group', 10)->index();
            $table->string('component_type', 30)->index();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->string('priority', 20)->default('routine')->index();
            $table->string('status', 30)->default('pending')->index();
            $table->text('clinical_note')->nullable();
            $table->text('decision_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            });
        }

        if (! Schema::hasTable('blood_allocations')) {
            Schema::create('blood_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('blood_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blood_unit_id')->unique()->constrained()->restrictOnDelete();
            $table->string('crossmatch_result', 20)->default('pending');
            $table->string('status', 30)->default('allocated')->index();
            $table->foreignId('allocated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('allocated_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('transfused_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            });
        }

        if (! Schema::hasTable('adverse_reactions')) {
            Schema::create('adverse_reactions', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('blood_allocation_id')->constrained()->restrictOnDelete();
            $table->foreignId('hospital_id')->constrained()->restrictOnDelete();
            $table->foreignId('reported_by')->constrained('users')->restrictOnDelete();
            $table->string('severity', 20)->index();
            $table->text('symptoms');
            $table->text('action_taken');
            $table->timestamp('occurred_at');
            $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('adverse_reactions');
        Schema::dropIfExists('blood_allocations');
        Schema::dropIfExists('blood_requests');

        DB::table('blood_units')->whereNotNull('parent_blood_unit_id')->delete();

        Schema::table('blood_units', function (Blueprint $table): void {
            $table->dropIndex(['donation_id', 'component_type']);
            $table->dropConstrainedForeignId('released_by');
            $table->dropConstrainedForeignId('parent_blood_unit_id');
            $table->dropColumn(['component_type', 'released_at']);
            $table->unique('donation_id');
        });

        Schema::dropIfExists('lab_tests');
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('hospital_id'));
        Schema::dropIfExists('hospitals');
    }
};
