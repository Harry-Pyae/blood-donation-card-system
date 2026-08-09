<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\AdverseReaction;
use App\Models\BloodAllocation;
use App\Models\BloodRequest;
use App\Models\BloodUnit;
use App\Models\Donation;
use App\Models\DonationCard;
use App\Models\DonationCentre;
use App\Models\Donor;
use App\Models\DonorScreening;
use App\Models\Hospital;
use App\Models\LabTest;
use App\Models\User;
use App\Notifications\BloodCareWorkflowNotification;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BloodCareClearDemoDataCommand extends Command
{
    protected $signature = 'bloodcare:demo-data:clear
        {--force : Skip the confirmation prompt}';

    protected $description = 'Delete only the deterministic BloodCare demo records while preserving real and manually entered data';

    /** @var list<string> */
    private const DEMO_USER_EMAILS = [
        'demo.admin@bloodcare.test',
        'demo.staff@bloodcare.test',
        'pending.staff@bloodcare.test',
        'rejected.staff@bloodcare.test',
        'banned.staff@bloodcare.test',
        'demo.donor@bloodcare.test',
        'demo.lab.admin@bloodcare.test',
        'demo.lab.staff@bloodcare.test',
        'demo.hospital.yangon@bloodcare.test',
        'demo.hospital.mandalay@bloodcare.test',
    ];

    public function handle(): int
    {
        if (! app()->environment('local', 'testing')) {
            $this->components->error('Demo data can be removed only in the local or testing environment.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm(
            'Delete the BloodCare demo donors, histories, accounts, centres, and linked records?',
            false,
        )) {
            $this->components->info('Nothing was deleted.');

            return self::SUCCESS;
        }

        $deleted = DB::transaction(fn (): array => $this->deleteDemoRecords());
        $total = array_sum($deleted);

        $this->newLine();
        $this->table(
            ['Table', 'Deleted'],
            collect($deleted)->map(fn (int $count, string $table): array => [$table, $count])->values()->all(),
        );

        $this->newLine();
        $this->components->info(
            $total > 0
                ? "Removed {$total} demo records. Non-demo data was preserved."
                : 'No BloodCare demo records were found. Nothing was deleted.',
        );

        return self::SUCCESS;
    }

    /** @return array<string, int> */
    private function deleteDemoRecords(): array
    {
        $userIds = $this->ids('users', fn (Builder $query): Builder => $query
            ->whereIn('email', self::DEMO_USER_EMAILS));
        $hospitalIds = $this->ids('hospitals', fn (Builder $query): Builder => $query
            ->where('code', 'like', 'HSP-DEMO-%'));
        $donorIds = $this->ids('donors', fn (Builder $query): Builder => $query
            ->where('reference', 'like', 'BC-DEMO-%'));

        $appointmentIds = $this->ids('appointments', function (Builder $query) use ($donorIds): Builder {
            $query->where('reference', 'like', 'APT-DEMO-%');
            $this->orWhereIn($query, 'donor_id', $donorIds);

            return $query;
        });

        $donationIds = $this->ids('donations', function (Builder $query) use ($donorIds, $appointmentIds): Builder {
            $query->where('reference', 'like', 'DON-DEMO-%');
            $this->orWhereIn($query, 'donor_id', $donorIds);
            $this->orWhereIn($query, 'appointment_id', $appointmentIds);

            return $query;
        });

        $screeningIds = $this->ids('donor_screenings', function (Builder $query) use ($donorIds, $appointmentIds, $donationIds): Builder {
            $query->where('reference', 'like', 'SCR-DEMO-%');
            $this->orWhereIn($query, 'donor_id', $donorIds);
            $this->orWhereIn($query, 'appointment_id', $appointmentIds);
            $this->orWhereIn($query, 'donation_id', $donationIds);

            return $query;
        });

        $cardIds = $this->ids('donation_cards', function (Builder $query) use ($donorIds): Builder {
            $query->where('card_number', 'like', 'CARD-DEMO-%');
            $this->orWhereIn($query, 'donor_id', $donorIds);

            return $query;
        });

        $bloodUnitIds = $this->ids('blood_units', function (Builder $query) use ($donationIds): Builder {
            $query->where('unit_number', 'like', 'BU-DEMO-%');
            $this->orWhereIn($query, 'donation_id', $donationIds);

            return $query;
        });

        $labTestIds = $this->ids('lab_tests', function (Builder $query) use ($donationIds): Builder {
            $query->where('reference', 'like', 'LAB-DEMO-%');
            $this->orWhereIn($query, 'donation_id', $donationIds);

            return $query;
        });

        $requestIds = $this->ids('blood_requests', function (Builder $query) use ($hospitalIds, $userIds): Builder {
            $query->where('reference', 'like', 'REQ-DEMO-%');
            $this->orWhereIn($query, 'hospital_id', $hospitalIds);
            $this->orWhereIn($query, 'requested_by', $userIds);

            return $query;
        });

        $allocationIds = $this->ids('blood_allocations', fn (Builder $query): Builder => $query
            ->whereIn('blood_request_id', $requestIds->all()));

        $reactionIds = $this->ids('adverse_reactions', function (Builder $query) use ($allocationIds, $hospitalIds, $userIds): Builder {
            $query->where('reference', 'like', 'HVR-DEMO-%');
            $this->orWhereIn($query, 'blood_allocation_id', $allocationIds);
            $this->orWhereIn($query, 'hospital_id', $hospitalIds);
            $this->orWhereIn($query, 'reported_by', $userIds);

            return $query;
        });

        // A standalone demo unit may have been attached to a manually entered,
        // non-demo request during local testing. Preserve that request history
        // instead of deleting its allocation behind the user's back.
        $protectedBloodUnitIds = $bloodUnitIds->isEmpty()
            ? collect()
            : DB::table('blood_allocations')
                ->whereIn('blood_unit_id', $bloodUnitIds->all())
                ->when($requestIds->isNotEmpty(), fn (Builder $query): Builder => $query->whereNotIn('blood_request_id', $requestIds->all()))
                ->pluck('blood_unit_id')
                ->map(fn (mixed $id): int => (int) $id);
        $bloodUnitIds = $bloodUnitIds->diff($protectedBloodUnitIds)->values();

        $centreIds = $this->ids('donation_centres', fn (Builder $query): Builder => $query
            ->where('code', 'like', 'CTR-DEMO-%'));

        $deleted = [];
        $deleted['activity_logs'] = $this->deleteDemoActivityLogs([
            Donor::class => $donorIds,
            Appointment::class => $appointmentIds,
            DonationCard::class => $cardIds,
            Donation::class => $donationIds,
            DonorScreening::class => $screeningIds,
            BloodUnit::class => $bloodUnitIds,
            LabTest::class => $labTestIds,
            BloodRequest::class => $requestIds,
            BloodAllocation::class => $allocationIds,
            AdverseReaction::class => $reactionIds,
            Hospital::class => $hospitalIds,
            DonationCentre::class => $centreIds,
            User::class => $userIds,
        ], $donorIds, $userIds);
        $deleted['notifications'] = DB::table('notifications')
            ->where(function (Builder $query) use ($userIds): void {
                if ($userIds->isNotEmpty()) {
                    $query->where(function (Builder $recipientQuery) use ($userIds): void {
                        $recipientQuery->where('notifiable_type', User::class)
                            ->whereIn('notifiable_id', $userIds->all());
                    });
                }
                $query->orWhere(function (Builder $dataQuery): void {
                    $dataQuery->where('type', BloodCareWorkflowNotification::class)
                        ->where('data', 'like', '%DEMO%');
                });
            })
            ->delete();
        $deleted['adverse_reactions'] = $this->deleteIds('adverse_reactions', $reactionIds);
        $deleted['blood_allocations'] = $this->deleteIds('blood_allocations', $allocationIds);
        $deleted['blood_requests'] = $this->deleteIds('blood_requests', $requestIds);
        $deleted['lab_tests'] = $this->deleteIds('lab_tests', $labTestIds);
        $childBloodUnitIds = $bloodUnitIds->isEmpty()
            ? collect()
            : DB::table('blood_units')
                ->whereIn('id', $bloodUnitIds->all())
                ->whereNotNull('parent_blood_unit_id')
                ->pluck('id')
                ->map(fn (mixed $id): int => (int) $id);
        $deleted['blood_units'] = $this->deleteIds('blood_units', $childBloodUnitIds);
        $deleted['blood_units'] += $this->deleteIds('blood_units', $bloodUnitIds->diff($childBloodUnitIds)->values());
        $deleted['donor_screenings'] = $this->deleteIds('donor_screenings', $screeningIds);
        $deleted['donation_cards'] = $this->deleteIds('donation_cards', $cardIds);
        $deleted['donations'] = $this->deleteIds('donations', $donationIds);
        $deleted['appointments'] = $this->deleteIds('appointments', $appointmentIds);
        $deleted['donors'] = $this->deleteIds('donors', $donorIds);
        $deleted['donation_centres'] = $this->deleteIds('donation_centres', $centreIds);
        $deleted['hospitals'] = $this->deleteIds('hospitals', $hospitalIds);

        if ($userIds->isNotEmpty()) {
            DB::table('users')
                ->whereNotIn('id', $userIds->all())
                ->whereIn('approved_by', $userIds->all())
                ->update(['approved_by' => null]);
        }

        $deleted['sessions'] = Schema::hasTable('sessions')
            ? $this->deleteWhereIn('sessions', 'user_id', $userIds)
            : 0;
        $deleted['password_reset_tokens'] = Schema::hasTable('password_reset_tokens')
            ? DB::table('password_reset_tokens')->whereIn('email', self::DEMO_USER_EMAILS)->delete()
            : 0;
        $deleted['users'] = $this->deleteIds('users', $userIds);

        return $deleted;
    }

    /**
     * @param  array<class-string, Collection<int, int>>  $subjectIds
     */
    private function deleteDemoActivityLogs(array $subjectIds, Collection $donorIds, Collection $userIds): int
    {
        return DB::table('activity_logs')
            ->where(function (Builder $query) use ($subjectIds, $donorIds, $userIds): void {
                $query->where('reference', 'like', 'HIS-DEMO-%');
                $this->orWhereIn($query, 'donor_id', $donorIds);
                $this->orWhereIn($query, 'user_id', $userIds);

                foreach ($subjectIds as $subjectType => $ids) {
                    if ($ids->isEmpty()) {
                        continue;
                    }

                    $query->orWhere(function (Builder $subjectQuery) use ($subjectType, $ids): void {
                        $subjectQuery
                            ->where('subject_type', $subjectType)
                            ->whereIn('subject_id', $ids->all());
                    });
                }
            })
            ->delete();
    }

    /** @return Collection<int, int> */
    private function ids(string $table, callable $scope): Collection
    {
        return $scope(DB::table($table))->pluck('id')->map(fn (mixed $id): int => (int) $id);
    }

    private function orWhereIn(Builder $query, string $column, Collection $ids): void
    {
        if ($ids->isNotEmpty()) {
            $query->orWhereIn($column, $ids->all());
        }
    }

    private function deleteIds(string $table, Collection $ids): int
    {
        return $this->deleteWhereIn($table, 'id', $ids);
    }

    private function deleteWhereIn(string $table, string $column, Collection $ids): int
    {
        return $ids->isEmpty()
            ? 0
            : DB::table($table)->whereIn($column, $ids->all())->delete();
    }
}
