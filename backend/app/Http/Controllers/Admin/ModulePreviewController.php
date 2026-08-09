<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\BloodUnit;
use App\Models\Donation;
use App\Models\DonationCard;
use App\Models\DonationCentre;
use App\Models\Donor;
use App\Models\User;
use App\Support\MyanmarNrc;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ModulePreviewController extends Controller
{
    public function donors(): View
    {
        $text = trans('bloodcare.modules.donors');
        $donorModels = Donor::query()
            ->with(['user', 'latestScreening.verifiedBy'])
            ->withCount([
                'donations as accepted_donations_count' => fn ($query) => $query->where('status', 'accepted'),
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get();
        $nrcReference = MyanmarNrc::reference();

        $records = $donorModels->map(function (Donor $donor): array {
            $eligibility = ucfirst($donor->eligibility_status);
            $nextEligible = match ($donor->eligibility_status) {
                'eligible' => $donor->next_eligible_date?->toDateString() ?? 'now',
                'review' => 'review',
                default => $donor->next_eligible_date?->toDateString() ?? 'review',
            };

            return [
                'id' => $donor->reference,
                'name' => $donor->full_name,
                'group' => $donor->blood_group,
                'phone' => $donor->phone,
                'email' => $donor->email ?? '',
                'dateOfBirth' => $donor->date_of_birth?->toDateString(),
                'gender' => ucfirst($donor->gender),
                'identity' => $donor->identity_number,
                'identityDocumentType' => $donor->identity_document_type,
                'nrcState' => $donor->nrc_state ?? '',
                'nrcTownship' => $donor->nrc_township ?? '',
                'nrcType' => $donor->nrc_type ?? '',
                'nrcSerial' => $donor->nrc_serial ?? '',
                'passportNumber' => $donor->passport_number ?? '',
                'address' => $donor->address,
                'lastDonation' => $donor->last_donation_date?->toDateString(),
                'totalDonations' => $donor->accepted_donations_count,
                'nextEligible' => $nextEligible,
                'eligibility' => $eligibility,
                'status' => ucfirst($donor->status),
                'notes' => $donor->staff_notes ?: $donor->health_notes ?: '',
                'userName' => $donor->user?->name ?? '',
                'userEmail' => $donor->user?->email ?? '',
                'userId' => $donor->user_id,
                'emergencyContact' => $donor->emergency_contact,
                'donationTypePreference' => $donor->donation_type_preference,
                'deferralType' => $donor->deferral_type,
                'deferralReason' => $donor->deferral_reason ?? '',
                'deferralEndDate' => $donor->deferral_end_date?->toDateString(),
                'latestScreening' => $donor->latestScreening ? [
                    'reference' => $donor->latestScreening->reference,
                    'date' => $donor->latestScreening->screened_at?->toDateString(),
                    'outcome' => ucfirst($donor->latestScreening->outcome),
                    'weightKg' => $donor->latestScreening->weight_kg,
                    'hemoglobin' => $donor->latestScreening->hemoglobin_level,
                    'staff' => $donor->latestScreening->verifiedBy?->name ?? '',
                ] : null,
            ];
        })->all();

        $total = Donor::count();
        $eligible = Donor::where('eligibility_status', 'eligible')->count();
        $review = Donor::whereIn('eligibility_status', ['review', 'pending'])->count();

        return $this->render('donors', [
            'title' => $text['title'],
            'eyebrow' => $text['eyebrow'],
            'description' => $text['description'],
            'icon' => 'la-users',
            'primaryAction' => $text['action'],
            'metrics' => [
                ['label' => $text['metrics'][0], 'value' => number_format($total), 'tone' => 'red'],
                ['label' => $text['metrics'][1], 'value' => number_format($eligible), 'tone' => 'green'],
                ['label' => $text['metrics'][2], 'value' => number_format($review), 'tone' => 'amber'],
            ],
            'filters' => $text['filters'],
            'columns' => $text['columns'],
            'donorData' => [
                'today' => now()->toDateString(),
                'staffName' => backpack_user()?->name ?? 'System Administrator',
                'summary' => [
                    'total' => $total,
                    'eligible' => $eligible,
                    'review' => $review,
                ],
                'records' => $records,
                ...$nrcReference,
            ],
            'rows' => array_map(
                fn (array $record): array => [
                    $record['id'],
                    $record['name'],
                    $record['group'],
                    $record['lastDonation'],
                    $record['nextEligible'],
                    $record['status'],
                ],
                array_slice($records, 0, 5)
            ),
        ]);
    }

    public function cards(): View
    {
        $text = trans('bloodcare.modules.cards');
        $cardModels = DonationCard::query()
            ->with('donor')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(300)
            ->get();

        $records = $cardModels->map(function (DonationCard $card): array {
            $status = $card->status;
            if ($status !== 'suspended' && $card->expires_at) {
                $status = $card->expires_at->isPast()
                    ? 'expired'
                    : ($card->expires_at->lte(now()->addDays(60)) ? 'expiring' : 'active');
            }

            return [
                'cardNumber' => $card->card_number,
                'donorId' => $card->donor?->reference ?? '',
                'donorName' => $card->donor?->full_name ?? 'Unknown donor',
                'group' => $card->donor?->blood_group ?? '',
                'phone' => $card->donor?->phone ?? '',
                'issueDate' => $card->issued_at?->toDateString(),
                'expiryDate' => $card->expires_at?->toDateString(),
                'status' => ucfirst($status),
                'replacementCount' => $card->replacement_count,
                'printCount' => $card->print_count,
                'qrToken' => $card->qr_token ?? '',
                'traceUrl' => $card->qr_token ? route('trace.card', $card->qr_token) : '',
                'notes' => $card->notes ?? '',
            ];
        })->all();

        $donors = Donor::query()->orderBy('full_name')->limit(500)->get()->map(
            fn (Donor $donor): array => [
                'id' => $donor->reference,
                'name' => $donor->full_name,
                'group' => $donor->blood_group,
                'phone' => $donor->phone,
                'eligibility' => ucfirst($donor->eligibility_status),
                'status' => ucfirst($donor->status),
            ]
        )->all();

        $active = DonationCard::where('status', 'active')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhereDate('expires_at', '>=', now()))
            ->count();
        $pending = Donor::where('status', '!=', 'inactive')->whereDoesntHave('card')->count()
            + DonationCard::where('status', 'pending')->count();
        $expiring = DonationCard::where('status', 'active')
            ->whereBetween('expires_at', [now()->toDateString(), now()->addDays(60)->toDateString()])
            ->count();

        return $this->render('cards', [
            'title' => $text['title'],
            'eyebrow' => $text['eyebrow'],
            'description' => $text['description'],
            'icon' => 'la-id-card',
            'primaryAction' => $text['action'],
            'metrics' => [
                ['label' => $text['metrics'][0], 'value' => number_format($active), 'tone' => 'red'],
                ['label' => $text['metrics'][1], 'value' => number_format($pending), 'tone' => 'amber'],
                ['label' => $text['metrics'][2], 'value' => number_format($expiring), 'tone' => 'blue'],
            ],
            'filters' => $text['filters'],
            'columns' => $text['columns'],
            'cardData' => [
                'today' => now()->toDateString(),
                'staffName' => backpack_user()?->name ?? 'System Administrator',
                'expiringWithinDays' => 60,
                'validYears' => 5,
                'summary' => compact('active', 'pending', 'expiring'),
                'records' => $records,
                'donors' => $donors,
            ],
            'rows' => array_map(
                fn (array $record): array => [
                    $record['cardNumber'],
                    $record['donorName'],
                    $record['group'],
                    $record['issueDate'],
                    $record['expiryDate'],
                    $record['status'],
                ],
                array_slice($records, 0, 5)
            ),
        ]);
    }

    public function donations(): View
    {
        $text = trans('bloodcare.modules.donations');
        $donationModels = Donation::query()
            ->with(['donor', 'appointment', 'recordedBy', 'bloodUnit', 'screening'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(300)
            ->get();

        $records = $donationModels->map(fn (Donation $donation): array => [
            'id' => $donation->reference,
            'donorId' => $donation->donor?->reference ?? '',
            'donorName' => $donation->donor?->full_name ?? 'Unknown donor',
            'group' => $donation->blood_group,
            'donationType' => $donation->donation_type,
            'quantity' => $donation->quantity_ml,
            'donationDate' => $donation->donation_date?->toDateString(),
            'staff' => $donation->recordedBy?->name ?? 'System Administrator',
            'screeningResult' => ucfirst($donation->screening_result),
            'status' => ucfirst($donation->status),
            'appointmentReference' => $donation->appointment?->reference ?? '',
            'bagUnit' => $donation->bloodUnit?->unit_number ?? ($donation->bag_unit_number ?? ''),
            'expiryDate' => $donation->bloodUnit?->expires_at?->toDateString() ?? ($donation->expires_at?->toDateString() ?? ''),
            'location' => $donation->bloodUnit?->storage_location ?? ($donation->storage_location ?? 'Cold room A'),
            'notes' => $donation->screening_notes ?? '',
            'screeningReference' => $donation->screening?->reference ?? '',
            'inventorySynced' => $donation->status === 'accepted' && $donation->bloodUnit !== null,
            'donorSynced' => $donation->status === 'accepted',
        ])->all();

        $donors = Donor::query()->orderBy('full_name')->limit(500)->get()->map(
            fn (Donor $donor): array => [
                'id' => $donor->reference,
                'name' => $donor->full_name,
                'group' => $donor->blood_group,
                'phone' => $donor->phone,
                'email' => $donor->email ?? '',
                'dateOfBirth' => $donor->date_of_birth?->toDateString(),
                'gender' => ucfirst($donor->gender),
                'identity' => $donor->identity_number,
                'address' => $donor->address,
                'lastDonation' => $donor->last_donation_date?->toDateString(),
                'nextEligible' => $donor->eligibility_status === 'eligible'
                    ? ($donor->next_eligible_date?->toDateString() ?? 'now')
                    : ($donor->next_eligible_date?->toDateString() ?? 'review'),
                'eligibility' => ucfirst($donor->eligibility_status),
                'status' => ucfirst($donor->status),
                'notes' => $donor->staff_notes ?? '',
            ]
        )->all();

        $inventoryRecords = BloodUnit::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(500)
            ->get()
            ->map(
            fn (BloodUnit $unit): array => [
                'id' => $unit->unit_number,
                'group' => $unit->blood_group,
                'collected' => $unit->collected_at?->toDateString(),
                'expires' => $unit->expires_at?->toDateString(),
                'location' => $unit->storage_location,
                'status' => ucfirst($unit->status),
                'note' => $unit->notes ?? '',
            ]
        )->all();

        $appointmentRecords = Appointment::query()
            ->with(['donor', 'donation'])
            ->where('purpose', 'Donation')
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->limit(500)
            ->get()
            ->map(fn (Appointment $appointment): array => [
                'id' => $appointment->reference,
                'donorId' => $appointment->donor?->reference ?? '',
                'date' => $appointment->appointment_date?->toDateString(),
                'centre' => $appointment->centre_name ?: 'Location request',
                'status' => str($appointment->status)->replace('_', ' ')->title()->toString(),
                'donationId' => $appointment->donation?->reference,
            ])
            ->all();

        $screeningRecords = \App\Models\DonorScreening::query()
            ->with(['donor', 'centre', 'donation'])
            ->where('outcome', 'passed')
            ->whereNull('donation_id')
            ->orderByDesc('screened_at')
            ->limit(1000)
            ->get()
            ->map(fn ($screening): array => [
                'id' => $screening->reference,
                'donorId' => $screening->donor?->reference ?? '',
                'date' => $screening->screened_at?->toDateString(),
                'outcome' => ucfirst($screening->outcome),
                'centre' => $screening->centre?->name ?? '',
                'nextScreeningDate' => $screening->next_screening_date?->toDateString(),
            ])
            ->all();

        $month = Donation::whereBetween('donation_date', [now()->startOfMonth(), now()->endOfMonth()])->count();
        $accepted = Donation::whereBetween('donation_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->where('status', 'accepted')->count();
        $review = Donation::whereIn('status', ['screening', 'rejected'])->count();

        return $this->render('donations', [
            'title' => $text['title'],
            'eyebrow' => $text['eyebrow'],
            'description' => $text['description'],
            'icon' => 'la-tint',
            'primaryAction' => $text['action'],
            'metrics' => [
                ['label' => $text['metrics'][0], 'value' => number_format($month), 'tone' => 'red'],
                ['label' => $text['metrics'][1], 'value' => number_format($accepted), 'tone' => 'green'],
                ['label' => $text['metrics'][2], 'value' => number_format($review), 'tone' => 'amber'],
            ],
            'filters' => $text['filters'],
            'columns' => $text['columns'],
            'donationData' => [
                'today' => now()->toDateString(),
                'eligibilityWaitDays' => 90,
                'donationIntervalsDays' => config('bloodcare.donation_intervals_days'),
                'wholeBloodExpiryDays' => 42,
                'staffName' => backpack_user()?->name ?? 'System Administrator',
                'summary' => compact('month', 'accepted', 'review'),
                'records' => $records,
                'donors' => $donors,
                'inventoryRecords' => $inventoryRecords,
                'appointments' => $appointmentRecords,
                'screenings' => $screeningRecords,
            ],
            'rows' => array_map(
                fn (array $record): array => [
                    $record['id'],
                    $record['donorName'],
                    $record['group'],
                    $record['quantity'].' ml',
                    $record['donationDate'],
                    $record['status'],
                ],
                array_slice($records, 0, 5)
            ),
        ]);
    }

    public function inventory(): View
    {
        $text = trans('bloodcare.modules.inventory');
        $unitModels = BloodUnit::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(500)
            ->get();
        $records = $unitModels->map(fn (BloodUnit $unit): array => [
            'id' => $unit->unit_number,
            'traceUrl' => $unit->trace_token ? route('trace.blood-unit', $unit->trace_token) : '',
            'group' => $unit->blood_group,
            'collected' => $unit->collected_at?->toDateString(),
            'expires' => $unit->expires_at?->toDateString(),
            'location' => $unit->storage_location,
            'status' => ucfirst($unit->status),
            'note' => $unit->notes ?? '',
        ])->all();

        $available = BloodUnit::where('status', 'available')->whereDate('expires_at', '>=', now())->count();
        $reserved = BloodUnit::where('status', 'reserved')->whereDate('expires_at', '>=', now())->count();
        $expiring = BloodUnit::whereIn('status', ['available', 'reserved'])
            ->whereBetween('expires_at', [now()->toDateString(), now()->addDays(7)->toDateString()])->count();
        $targets = ['A+' => 100, 'A-' => 50, 'B+' => 100, 'B-' => 50, 'AB+' => 60, 'AB-' => 50, 'O+' => 140, 'O-' => 50];
        $totals = BloodUnit::whereIn('status', ['available', 'reserved'])
            ->whereDate('expires_at', '>=', now())
            ->selectRaw('blood_group, COUNT(*) as total')
            ->groupBy('blood_group')
            ->pluck('total', 'blood_group');
        $groupTotals = collect($targets)->mapWithKeys(
            fn ($target, $group): array => [$group => (int) ($totals[$group] ?? 0)]
        )->all();
        $bloodGroups = collect($targets)->map(function (int $target, string $group) use ($groupTotals): array {
            $units = $groupTotals[$group];
            $percent = min(100, (int) round(($units / max(1, $target)) * 100));
            $status = $percent < 25 ? 'critical' : ($percent < 50 ? 'low' : 'healthy');
            return ['group' => str_replace('-', '−', $group), 'units' => $units, 'status' => $status, 'percent' => $percent];
        })->values()->all();

        return $this->render('inventory', [
            'title' => $text['title'],
            'eyebrow' => $text['eyebrow'],
            'description' => $text['description'],
            'icon' => 'la-boxes',
            'primaryAction' => $text['action'],
            'metrics' => [
                ['label' => $text['metrics'][0], 'value' => number_format($available), 'tone' => 'red'],
                ['label' => $text['metrics'][1], 'value' => number_format($reserved), 'tone' => 'blue'],
                ['label' => $text['metrics'][2], 'value' => number_format($expiring), 'tone' => 'amber'],
            ],
            'bloodGroups' => $bloodGroups,
            'inventoryData' => [
                'today' => now()->toDateString(),
                'staffName' => backpack_user()?->name ?? 'System Administrator',
                'summary' => compact('available', 'reserved', 'expiring'),
                'groupTotals' => $groupTotals,
                'groupTargets' => $targets,
                'records' => $records,
            ],
            'filters' => $text['filters'],
            'columns' => $text['columns'],
            'rows' => array_map(fn (array $record): array => array_values($record), array_slice($records, 0, 4)),
        ]);
    }

    public function appointments(): View
    {
        $text = trans('bloodcare.modules.appointments');
        $appointmentModels = Appointment::query()
            ->with(['donor', 'centre', 'handledBy', 'donation'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $records = $appointmentModels->map(function (Appointment $appointment): array {
            $locationRequest = trim(
                ($appointment->requested_region ?? '').' / '.($appointment->requested_township ?? ''),
                ' /'
            );

            return [
                'id' => $appointment->reference,
                'donorId' => $appointment->donor?->reference ?? '',
                'donorName' => $appointment->donor?->full_name ?? 'Unknown donor',
                'group' => $appointment->donor?->blood_group ?? 'unknown',
                'date' => $appointment->appointment_date?->toDateString(),
                'time' => $appointment->appointment_time ? substr((string) $appointment->appointment_time, 0, 5) : '',
                'purpose' => $appointment->purpose,
                'centre' => $appointment->centre_name ?: ($locationRequest ?: 'Location request'),
                'status' => str($appointment->status)->replace('_', ' ')->title()->toString(),
                'staff' => $appointment->handledBy?->name ?? 'Unassigned',
                'source' => $appointment->source,
                'notes' => $appointment->notes ?? '',
                'donationCreated' => $appointment->donation !== null,
            ];
        })->all();

        $donors = Donor::query()->orderBy('full_name')->limit(500)->get()->map(
            fn (Donor $donor): array => [
                'id' => $donor->reference,
                'name' => $donor->full_name,
                'group' => $donor->blood_group,
                'phone' => $donor->phone,
                'lastDonation' => $donor->last_donation_date?->toDateString(),
                'nextEligible' => $donor->eligibility_status === 'eligible'
                    ? ($donor->next_eligible_date?->toDateString() ?? 'now')
                    : ($donor->next_eligible_date?->toDateString() ?? 'review'),
                'eligibility' => ucfirst($donor->eligibility_status),
                'status' => ucfirst($donor->status),
            ]
        )->all();

        $centreModels = DonationCentre::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
        $centreRecords = $centreModels->map(fn (DonationCentre $centre): array => [
            'id' => $centre->code,
            'name' => $centre->name,
            'region' => $centre->region,
            'township' => $centre->township,
            'address' => $centre->address ?? '',
            'phone' => $centre->phone ?? '',
            'hours' => $centre->opening_hours ?? '',
            'active' => $centre->is_active,
        ])->all();

        $today = now()->toDateString();
        $todayCount = Appointment::whereDate('appointment_date', $today)->count();
        $checkedIn = Appointment::whereDate('appointment_date', $today)
            ->where('status', 'checked_in')
            ->count();
        $pending = Appointment::whereIn('status', ['pending', 'confirmed'])->count();

        return $this->render('appointments', [
            'title' => $text['title'],
            'eyebrow' => $text['eyebrow'],
            'description' => $text['description'],
            'icon' => 'la-calendar-check',
            'primaryAction' => $text['action'],
            'metrics' => [
                ['label' => $text['metrics'][0], 'value' => number_format($todayCount), 'tone' => 'red'],
                ['label' => $text['metrics'][1], 'value' => number_format($checkedIn), 'tone' => 'green'],
                ['label' => $text['metrics'][2], 'value' => number_format($pending), 'tone' => 'amber'],
            ],
            'filters' => $text['filters'],
            'columns' => $text['columns'],
            'appointmentData' => [
                'today' => $today,
                'staffName' => backpack_user()?->name ?? 'System Administrator',
                'donationRoute' => route('bloodcare.admin.donations'),
                'summary' => [
                    'today' => $todayCount,
                    'checkedIn' => $checkedIn,
                    'pending' => $pending,
                ],
                'centres' => $centreModels->where('is_active', true)->pluck('name')->values()->all(),
                'centreRecords' => $centreRecords,
                'records' => $records,
                'donors' => $donors,
            ],
            'rows' => array_map(
                fn (array $record): array => [
                    $record['id'],
                    $record['donorName'],
                    $record['date'] ?: '—',
                    $record['time'] ?: '—',
                    $record['centre'],
                    $record['status'],
                ],
                array_slice($records, 0, 5)
            ),
        ]);
    }

    public function history(): View
    {
        $text = trans('bloodcare.modules.history');
        $logs = ActivityLog::query()
            ->with(['donor', 'user', 'subject'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(300)
            ->get();

        $records = $logs->map(fn (ActivityLog $log): array => [
            'id' => $log->reference,
            'type' => $log->type,
            'action' => $log->action,
            'reference' => $log->subject?->reference
                ?? $log->subject?->card_number
                ?? $log->subject?->unit_number
                ?? $log->subject?->code
                ?? $log->reference,
            'donorId' => $log->donor?->reference ?? '',
            'donorName' => $log->donor?->full_name ?? '',
            'staff' => $log->user?->name ?? ($log->source === 'public-registration' || $log->source === 'public-booking'
                ? 'Public website'
                : 'System'),
            'dateTime' => $log->created_at?->format('Y-m-d\TH:i:s'),
            'result' => $log->result ?? '',
            'details' => $log->details ?? '',
            'source' => $log->source ?? 'database',
        ])->all();

        $today = now()->toDateString();
        $todayCount = ActivityLog::whereDate('created_at', $today)->count();
        $staffActions = ActivityLog::whereDate('created_at', $today)->whereNotNull('user_id')->count();
        $inventoryActions = ActivityLog::whereDate('created_at', $today)->where('type', 'Inventory')->count();

        return $this->render('history', [
            'title' => $text['title'],
            'eyebrow' => $text['eyebrow'],
            'description' => $text['description'],
            'icon' => 'la-history',
            'primaryAction' => null,
            'metrics' => [
                ['label' => $text['metrics'][0], 'value' => number_format($todayCount), 'tone' => 'red'],
                ['label' => $text['metrics'][1], 'value' => number_format($staffActions), 'tone' => 'green'],
                ['label' => $text['metrics'][2], 'value' => number_format($inventoryActions), 'tone' => 'blue'],
            ],
            'filters' => $text['filters'],
            'columns' => $text['columns'],
            'historyData' => [
                'today' => $today,
                'staffName' => backpack_user()?->name ?? 'System Administrator',
                'records' => $records,
            ],
            'rows' => array_map(
                fn (array $record): array => [
                    $record['dateTime'],
                    $record['action'],
                    $record['reference'],
                    $record['staff'],
                    $record['details'],
                    $record['result'],
                ],
                array_slice($records, 0, 5)
            ),
        ]);
    }

    public function users(): View
    {
        $text = trans('bloodcare.modules.users');
        $currentUser = backpack_user();
        $lastActivities = DB::table('sessions')
            ->whereNotNull('user_id')
            ->selectRaw('user_id, MAX(last_activity) as last_activity')
            ->groupBy('user_id')
            ->pluck('last_activity', 'user_id');

        $records = User::query()
            // Hospital portal identities have their own lifecycle under
            // Hospital Services and are intentionally not a sixth Users role.
            ->where('role', '!=', User::ROLE_HOSPITAL)
            ->orderByDesc('id')
            ->limit(500)
            ->get()->map(
            function (User $user) use ($currentUser, $lastActivities): array {
                $lastActivity = $lastActivities[$user->id] ?? null;
                $status = $user->is_banned
                    ? 'Banned'
                    : match (strtolower((string) ($user->approval_status ?? User::APPROVAL_APPROVED))) {
                        User::APPROVAL_PENDING => 'Pending',
                        User::APPROVAL_REJECTED => 'Rejected',
                        default => 'Active',
                    };

                return [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? '',
                    'jobTitle' => $user->job_title ?? '',
                    'workplace' => $user->workplace ?? '',
                    'registrationNote' => $user->registration_note ?? '',
                    'role' => $user->managementRoleLabel(),
                    'status' => $status,
                    'approvalStatus' => ucfirst($user->approval_status ?? User::APPROVAL_APPROVED),
                    'approvedAt' => $user->approved_at?->toIso8601String(),
                    'joinedAt' => $user->created_at?->toDateString() ?? now()->toDateString(),
                    'lastLogin' => $lastActivity ? date('c', (int) $lastActivity) : null,
                    'current' => (int) $currentUser?->id === (int) $user->id,
                ];
            }
        )->all();

        return view('admin.users', [
            'title' => $text['title'],
            'eyebrow' => $text['eyebrow'],
            'description' => $text['description'],
            'filters' => $text['filters'],
            'columns' => $text['columns'],
            'userData' => [
                'today' => now()->toDateString(),
                'staffName' => $currentUser?->name ?? 'System Administrator',
                'currentUserId' => (string) ($currentUser?->id ?? ''),
                'records' => $records,
            ],
        ]);
    }

    public function reports(): View
    {
        $text = trans('bloodcare.modules.reports');
        $groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        $groupDefaults = array_fill_keys($groups, 0);

        $donorsByGroup = array_replace($groupDefaults, Donor::query()
            ->selectRaw('blood_group, COUNT(*) as total')
            ->groupBy('blood_group')
            ->pluck('total', 'blood_group')->map(fn ($value) => (int) $value)->all());
        $readinessRaw = Donor::query()
            ->selectRaw('eligibility_status, COUNT(*) as total')
            ->groupBy('eligibility_status')
            ->pluck('total', 'eligibility_status');
        $donorReadiness = [
            'Eligible' => (int) ($readinessRaw['eligible'] ?? 0),
            'Deferred' => (int) ($readinessRaw['deferred'] ?? 0),
            'Review' => (int) (($readinessRaw['review'] ?? 0) + ($readinessRaw['pending'] ?? 0)),
        ];

        $months = collect(range(5, 0))->map(fn (int $offset): string => now()->copy()->subMonths($offset)->format('Y-m'));
        $donationTrend = $months->mapWithKeys(function (string $month): array {
            [$year, $number] = array_map('intval', explode('-', $month));
            return [$month => Donation::whereYear('donation_date', $year)
                ->whereMonth('donation_date', $number)
                ->where('status', 'accepted')->count()];
        })->all();
        $donationsByGroup = array_replace($groupDefaults, Donation::query()
            ->where('status', 'accepted')
            ->whereYear('donation_date', now()->year)
            ->whereMonth('donation_date', now()->month)
            ->selectRaw('blood_group, COUNT(*) as total')
            ->groupBy('blood_group')
            ->pluck('total', 'blood_group')->map(fn ($value) => (int) $value)->all());

        $inventoryByGroup = array_replace($groupDefaults, BloodUnit::query()
            ->where('status', 'available')
            ->whereDate('expires_at', '>=', now())
            ->selectRaw('blood_group, COUNT(*) as total')
            ->groupBy('blood_group')
            ->pluck('total', 'blood_group')->map(fn ($value) => (int) $value)->all());
        $inventoryTargets = ['A+' => 100, 'A-' => 50, 'B+' => 100, 'B-' => 50, 'AB+' => 60, 'AB-' => 50, 'O+' => 140, 'O-' => 50];

        $appointmentRaw = Appointment::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $appointmentOutcomes = [
            'Completed' => (int) ($appointmentRaw['completed'] ?? 0),
            'Confirmed' => (int) ($appointmentRaw['confirmed'] ?? 0),
            'Pending' => (int) ($appointmentRaw['pending'] ?? 0),
            'Cancelled' => (int) ($appointmentRaw['cancelled'] ?? 0),
            'No-show' => (int) ($appointmentRaw['no_show'] ?? 0),
        ];

        $centreActivity = DonationCentre::query()->withCount([
            'appointments as bookings',
            'appointments as attended' => fn ($query) => $query->where('status', 'completed'),
        ])->orderBy('name')->get()->map(fn (DonationCentre $centre): array => [
            'name' => $centre->name,
            'bookings' => $centre->bookings,
            'attended' => $centre->attended,
        ])->all();
        $zeroGroups = $groupDefaults;
        $zeroMonths = $months->mapWithKeys(fn (string $month): array => [$month => 0])->all();
        $zeroMonthlyGroups = $months->mapWithKeys(fn (string $month): array => [$month => $zeroGroups])->all();

        return view('admin.reports', [
            'title' => $text['title'],
            'eyebrow' => $text['eyebrow'],
            'description' => $text['description'],
            'reportData' => [
                'today' => now()->toDateString(),
                'locale' => app()->getLocale(),
                'staffName' => backpack_user()?->name ?? 'System Administrator',
                'baseline' => [
                    'donorsTotal' => Donor::count(),
                    'donorsByGroup' => $donorsByGroup,
                    'donorReadiness' => $donorReadiness,
                    'donationTrend' => $donationTrend,
                    'donationsByGroup' => $donationsByGroup,
                    'availableUnits' => array_sum($inventoryByGroup),
                    'inventoryByGroup' => $inventoryByGroup,
                    'inventoryTargets' => $inventoryTargets,
                    'appointmentOutcomes' => $appointmentOutcomes,
                    'centreActivity' => $centreActivity,
                ],
                'sampleContributions' => [
                    'donors' => ['total' => 0, 'byGroup' => $zeroGroups, 'readiness' => ['Eligible' => 0, 'Deferred' => 0, 'Review' => 0]],
                    'donationsByMonth' => $zeroMonths,
                    'donationsByGroup' => $zeroGroups,
                    'donationsMonthlyGroup' => $zeroMonthlyGroups,
                    'availableInventory' => ['total' => 0, 'byGroup' => $zeroGroups],
                    'appointments' => array_fill_keys(array_keys($appointmentOutcomes), 0),
                ],
            ],
        ]);
    }

    private function render(string $key, array $data): View
    {
        return view('admin.module-preview', ['moduleKey' => $key, ...$data]);
    }
}
