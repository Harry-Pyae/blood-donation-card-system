<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Donation;
use App\Models\LabTest;
use App\Services\BloodCareNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LaboratoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $group = (string) $request->query('group', 'all');
        $safety = (string) $request->query('safety', 'all');

        $bloodGroups = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
        $safetyStatuses = ['quarantined', 'released', 'discarded'];

        if (! in_array($group, $bloodGroups, true)) {
            $group = 'all';
        }

        if (! in_array($safety, $safetyStatuses, true)) {
            $safety = 'all';
        }

        $baseQuery = Donation::query()
            ->where('status', 'accepted')
            ->whereHas('bloodUnit')
            ->where(function ($query): void {
                // The Laboratory queue is a safety workflow, not a list of every
                // historical accepted donation. Legacy/demo donations can already
                // have used/discarded inventory units without a LabTest record.
                // Those units must never be presented as "Quarantined" simply
                // because they predate the laboratory module.
                $query->whereHas('labTest', fn ($labQuery) => $labQuery
                    ->whereIn('release_status', ['released', 'discarded']))
                    ->orWhere(function ($quarantineQuery): void {
                        $quarantineQuery->whereDoesntHave('labTest')
                            ->whereHas('bloodUnit', fn ($unitQuery) => $unitQuery
                                ->where('status', 'quarantined'));
                    });
            });

        $metrics = [
            'total' => (clone $baseQuery)->count(),
            'quarantined' => (clone $baseQuery)
                ->whereDoesntHave('labTest')
                ->whereHas('bloodUnit', fn ($query) => $query->where('status', 'quarantined'))
                ->count(),
            'released' => (clone $baseQuery)->whereHas('labTest', fn ($query) => $query->where('release_status', 'released'))->count(),
            'discarded' => (clone $baseQuery)->whereHas('labTest', fn ($query) => $query->where('release_status', 'discarded'))->count(),
        ];

        $donations = $baseQuery
            ->with(['bloodUnit', 'labTest.testedBy'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('reference', 'like', '%'.$search.'%')
                        ->orWhere('bag_unit_number', 'like', '%'.$search.'%')
                        ->orWhereHas('bloodUnit', fn ($unitQuery) => $unitQuery->where('unit_number', 'like', '%'.$search.'%'));
                });
            })
            ->when($group !== 'all', fn ($query) => $query->where('blood_group', $group))
            ->when($safety === 'quarantined', fn ($query) => $query
                ->whereDoesntHave('labTest')
                ->whereHas('bloodUnit', fn ($unitQuery) => $unitQuery->where('status', 'quarantined')))
            ->when(in_array($safety, ['released', 'discarded'], true), fn ($query) => $query->whereHas('labTest', fn ($labQuery) => $labQuery->where('release_status', $safety)))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(8)
            ->withQueryString();

        return view('admin.national.laboratory', compact(
            'donations',
            'metrics',
            'search',
            'group',
            'safety',
            'bloodGroups',
        ));
    }

    public function store(Request $request, Donation $donation, BloodCareNotificationService $notifications): RedirectResponse
    {
        abort_unless(backpack_user()?->canUseLaboratory(), 403);
        $data = $request->validate([
            'hiv_status' => ['required', Rule::in(['negative', 'reactive'])],
            'hepatitis_b_status' => ['required', Rule::in(['negative', 'reactive'])],
            'hepatitis_c_status' => ['required', Rule::in(['negative', 'reactive'])],
            'syphilis_status' => ['required', Rule::in(['negative', 'reactive'])],
            'confirmed_blood_group' => ['required', Rule::in(['A+','A-','B+','B-','AB+','AB-','O+','O-'])],
            'rhd_type' => ['required', Rule::in(['positive', 'negative'])],
            'antibody_screen_status' => ['required', Rule::in(['negative', 'reactive'])],
            'htlv_status' => ['required', Rule::in(['not_required', 'negative', 'reactive'])],
            'malaria_status' => ['required', Rule::in(['not_required', 'negative', 'reactive'])],
            'chagas_status' => ['required', Rule::in(['not_required', 'negative', 'reactive'])],
            'west_nile_status' => ['required', Rule::in(['not_required', 'negative', 'reactive'])],
            'zika_status' => ['required', Rule::in(['not_required', 'negative', 'reactive'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($donation, $data, $notifications): void {
            $locked = Donation::query()->lockForUpdate()->with('bloodUnit')->findOrFail($donation->id);
            abort_unless($locked->status === 'accepted' && $locked->bloodUnit, 422, 'Only collected donation units can be laboratory tested.');
            if ($locked->bloodUnit->status !== 'quarantined') {
                $existingTest = $locked->labTest;
                if ($existingTest
                    && in_array($existingTest->release_status, ['released', 'discarded'], true)
                    && $this->matchesSavedDecision($existingTest, $data)) {
                    // A fast double-click can replay the same POST after the first
                    // transaction has already applied the safety lock. Treat the
                    // exact replay as success without changing the locked record or
                    // sending duplicate workflow notifications.
                    return;
                }

                if ($existingTest) {
                    // A finalized safety decision is immutable. Keep this as an
                    // explicit 422 response: validation/session redirection is
                    // reserved for a stale historical unit that has no LabTest.
                    abort(422, __('bloodcare.national.laboratory.decision_locked'));
                }

                throw ValidationException::withMessages([
                    'laboratory' => __('bloodcare.national.laboratory.unit_not_quarantined'),
                ]);
            }

            $hasReactive = collect([
                'hiv_status', 'hepatitis_b_status', 'hepatitis_c_status', 'syphilis_status',
                'antibody_screen_status', 'htlv_status', 'malaria_status', 'chagas_status',
                'west_nile_status', 'zika_status',
            ])
                ->contains(fn (string $field) => $data[$field] === 'reactive');
            $groupMatches = $data['confirmed_blood_group'] === $locked->blood_group;
            $expectedRhd = str_ends_with($data['confirmed_blood_group'], '+') ? 'positive' : 'negative';
            $rhdMatches = $data['rhd_type'] === $expectedRhd;
            $release = (! $hasReactive && $groupMatches && $rhdMatches) ? 'released' : 'discarded';

            $test = LabTest::updateOrCreate(['donation_id' => $locked->id], [
                'reference' => $locked->labTest?->reference ?? LabTest::generateReference(),
                ...$data,
                'release_status' => $release,
                'tested_by' => backpack_user()?->id,
                'tested_at' => now(),
                'released_at' => $release === 'released' ? now() : null,
                'released_by' => $release === 'released' ? backpack_user()?->id : null,
            ]);

            $locked->bloodUnit->update([
                'blood_group' => $data['confirmed_blood_group'],
                'status' => $release === 'released' ? 'available' : 'discarded',
                'released_at' => $release === 'released' ? now() : null,
                'released_by' => $release === 'released' ? backpack_user()?->id : null,
                'notes' => $release === 'released'
                    ? 'Laboratory screening complete; released for clinical inventory.'
                    : 'Laboratory safety lock: reactive screening, blood-group mismatch, or RhD mismatch.',
            ]);

            ActivityLog::record([
                'type'=>'Laboratory','action'=>'Mandatory donation screening completed','subject_type'=>LabTest::class,
                'subject_id'=>$test->id,'donor_id'=>$locked->donor_id,'user_id'=>backpack_user()?->id,
                'result'=>ucfirst($release),'details'=>"{$locked->reference}; {$data['confirmed_blood_group']}; RhD {$data['rhd_type']}; antibody {$data['antibody_screen_status']}; safety {$release}",'source'=>'admin-laboratory',
            ]);

            $notifications->laboratoryDecision($locked, $locked->bloodUnit, $release);
        });

        return back()->with('status', __('bloodcare.national.laboratory.saved'));
    }

    /** @param array<string, mixed> $data */
    private function matchesSavedDecision(LabTest $test, array $data): bool
    {
        $decisionFields = [
            'hiv_status', 'hepatitis_b_status', 'hepatitis_c_status', 'syphilis_status',
            'confirmed_blood_group', 'rhd_type', 'antibody_screen_status', 'htlv_status',
            'malaria_status', 'chagas_status', 'west_nile_status', 'zika_status', 'notes',
        ];

        foreach ($decisionFields as $field) {
            if (($test->{$field} ?? null) !== ($data[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }
}
