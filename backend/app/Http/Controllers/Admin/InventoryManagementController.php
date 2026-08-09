<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BloodUnit;
use App\Models\LabTest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InventoryManagementController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $unit = DB::transaction(function () use ($data): BloodUnit {
            $unit = BloodUnit::create($this->attributes($data));
            $this->log($unit, 'Inventory unit added manually');
            return $unit;
        });

        return response()->json(['message' => 'Inventory unit saved.', 'unit' => $unit], 201);
    }

    public function update(Request $request, string $unit): JsonResponse
    {
        $model = BloodUnit::where('unit_number', $unit)->firstOrFail();
        $data = $this->validated($request, $model);
        DB::transaction(function () use ($model, $data): void {
            $model->update($this->attributes($data));
            $this->log($model, 'Inventory unit updated');
        });

        return response()->json(['message' => 'Inventory unit updated.', 'unit' => $model->fresh()]);
    }

    public function updateStatus(Request $request, string $unit): JsonResponse
    {
        $model = BloodUnit::where('unit_number', $unit)->firstOrFail();
        $data = $request->validate([
            'status' => ['required', Rule::in(['Available', 'Reserved', 'Used', 'Discarded'])],
        ]);
        if ($model->donation_id && $model->status === 'quarantined') {
            throw ValidationException::withMessages([
                'status' => 'Quarantined donation units can only be released by the Laboratory workflow.',
            ]);
        }
        $model->update(['status' => strtolower($data['status'])]);
        $this->log($model, 'Inventory status changed');

        return response()->json(['message' => 'Inventory status updated.']);
    }

    public function details(string $unit): JsonResponse
    {
        $model = BloodUnit::query()
            ->with([
                'donation.centre',
                'donation.labTest.testedBy',
                'donation.labTest.releasedBy',
                'allocations.request.hospital',
                'allocations.reactions',
            ])
            ->where('unit_number', $unit)
            ->firstOrFail();

        $lab = $model->donation?->labTest;

        return response()->json([
            'unit' => [
                'id' => $model->unit_number,
                'bloodGroup' => $model->blood_group,
                'componentKey' => $model->component_type,
                'component' => $this->componentLabel($model->component_type),
                'collected' => $model->collected_at?->toDateString(),
                'expires' => $model->expires_at?->toDateString(),
                'location' => $model->storage_location ?: __('bloodcare.traceability.not_recorded'),
                'status' => $model->status,
                'statusLabel' => $this->statusLabel($model->status),
                'releasedAt' => $model->released_at?->toIso8601String(),
                'donationReference' => $model->donation?->reference,
                'donationCentre' => $model->donation?->centre?->name,
                'note' => $model->notes ?: null,
                'modifiers' => $this->modifierLabels($model),
            ],
            'laboratory' => $lab ? $this->laboratoryDetails($lab, $model) : null,
            'laboratoryPending' => (bool) ($model->donation_id && ! $lab && $model->status === 'quarantined'),
            'lineage' => [
                'ancestors' => $this->ancestorLineage($model),
                'descendants' => $this->descendantLineage($model),
            ],
            'haemovigilance' => $this->haemovigilanceDetails($model),
            'traceHistory' => $this->traceHistory($model, $lab),
            'traceUrl' => $model->trace_token ? route('trace.blood-unit', $model->trace_token) : '',
        ]);
    }

    private function laboratoryDetails(LabTest $lab, BloodUnit $unit): array
    {
        return [
            'reference' => $lab->reference,
            'releaseStatus' => $lab->release_status,
            'releaseLabel' => $this->statusLabel($lab->release_status),
            'testedAt' => $lab->tested_at?->toIso8601String(),
            'releasedAt' => $lab->released_at?->toIso8601String(),
            'testedBy' => $lab->testedBy?->name,
            'releasedBy' => $lab->releasedBy?->name,
            'notes' => $lab->notes,
            'inheritedFromDonation' => $unit->parent_blood_unit_id !== null,
            'mandatoryTests' => [
                $this->labResult('hiv', $lab->hiv_status),
                $this->labResult('hepatitis_b', $lab->hepatitis_b_status),
                $this->labResult('hepatitis_c', $lab->hepatitis_c_status),
                $this->labResult('syphilis', $lab->syphilis_status),
            ],
            'immunohematology' => [
                [
                    'key' => 'confirmed_group',
                    'label' => __('bloodcare.national.laboratory.confirmed_group'),
                    'result' => $lab->confirmed_blood_group,
                    'resultLabel' => $lab->confirmed_blood_group ?: __('bloodcare.national.laboratory.not_tested'),
                    'tone' => $lab->confirmed_blood_group ? 'confirmed' : 'not_tested',
                ],
                [
                    'key' => 'rhd',
                    'label' => __('bloodcare.national.laboratory.rhd_typing'),
                    'result' => $lab->rhd_type,
                    'resultLabel' => $this->rhdLabel($lab->rhd_type),
                    'tone' => $lab->rhd_type ? 'confirmed' : 'not_tested',
                ],
                $this->labResult('antibody_screen', $lab->antibody_screen_status),
            ],
            'regionalTests' => [
                $this->labResult('htlv', $lab->htlv_status),
                $this->labResult('malaria', $lab->malaria_status),
                $this->labResult('chagas', $lab->chagas_status),
                $this->labResult('west_nile', $lab->west_nile_status),
                $this->labResult('zika', $lab->zika_status),
            ],
        ];
    }

    private function labResult(string $test, ?string $result): array
    {
        $result ??= 'not_tested';

        return [
            'key' => $test,
            'label' => __('bloodcare.national.laboratory.tests.'.$test),
            'result' => $result,
            'resultLabel' => $this->labResultLabel($result),
            'tone' => $result,
        ];
    }

    private function labResultLabel(string $result): string
    {
        $key = 'bloodcare.national.laboratory.'.$result;
        $label = __($key);

        return $label === $key ? str($result)->replace('_', ' ')->title()->toString() : $label;
    }

    private function rhdLabel(?string $type): string
    {
        if (! $type) {
            return __('bloodcare.national.laboratory.not_tested');
        }

        $key = 'bloodcare.national.laboratory.rhd_'.$type;
        $label = __($key);

        return $label === $key ? 'RhD '.str($type)->title()->toString() : $label;
    }

    /** @return array<int, array<string, mixed>> */
    private function ancestorLineage(BloodUnit $unit): array
    {
        $items = [];
        $cursor = $unit->parent()->first();
        $guard = 0;

        while ($cursor && $guard < 10) {
            array_unshift($items, $this->lineageItem($cursor));
            $cursor = $cursor->parent()->first();
            $guard++;
        }

        return $items;
    }

    /** @return array<int, array<string, mixed>> */
    private function descendantLineage(BloodUnit $unit, int $depth = 0): array
    {
        if ($depth >= 4) {
            return [];
        }

        $items = [];
        foreach ($unit->components()->orderBy('id')->get() as $child) {
            $items[] = $this->lineageItem($child, $depth + 1);
            array_push($items, ...$this->descendantLineage($child, $depth + 1));
        }

        return $items;
    }

    /** @return array<string, mixed> */
    private function lineageItem(BloodUnit $unit, int $depth = 0): array
    {
        return [
            'id' => $unit->unit_number,
            'component' => $this->componentLabel($unit->component_type),
            'status' => $unit->status,
            'statusLabel' => $this->statusLabel($unit->status),
            'expires' => $unit->expires_at?->toDateString(),
            'modifiers' => $this->modifierLabels($unit),
            'depth' => $depth,
            'createdAt' => $unit->created_at?->toIso8601String(),
            'sourceId' => $unit->parent?->unit_number,
            'traceUrl' => $unit->trace_token ? route('trace.blood-unit', $unit->trace_token) : '',
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function traceHistory(BloodUnit $unit, ?LabTest $lab): array
    {
        $history = [];
        $donation = $unit->donation;

        if ($donation) {
            $history[] = $this->traceEvent(
                __('bloodcare.inventory.trace.collected'),
                $unit->collected_at?->toDateString(),
                __('bloodcare.inventory.trace.collected_detail', [
                    'donation' => $donation->reference,
                    'centre' => $donation->centre?->name ?? __('bloodcare.traceability.not_recorded'),
                ]),
                'done',
            );
            $history[] = $this->traceEvent(
                __('bloodcare.inventory.trace.quarantined'),
                $unit->collected_at?->toDateString(),
                __('bloodcare.inventory.trace.quarantined_detail'),
                $lab ? 'done' : 'warning',
            );
        } else {
            $history[] = $this->traceEvent(
                __('bloodcare.inventory.trace.registered'),
                $unit->created_at?->toIso8601String(),
                __('bloodcare.inventory.trace.registered_detail'),
                'neutral',
            );
        }

        if ($lab) {
            $released = $lab->release_status === 'released';
            $history[] = $this->traceEvent(
                $released ? __('bloodcare.inventory.trace.lab_released') : __('bloodcare.inventory.trace.lab_discarded'),
                ($lab->released_at ?? $lab->tested_at)?->toIso8601String(),
                __('bloodcare.inventory.trace.lab_detail', [
                    'reference' => $lab->reference,
                    'decision' => $this->statusLabel($lab->release_status),
                ]),
                $released ? 'done' : 'danger',
            );
        }

        if ($unit->parent_blood_unit_id) {
            $history[] = $this->traceEvent(
                __('bloodcare.inventory.trace.component_prepared'),
                $unit->created_at?->toIso8601String(),
                __('bloodcare.inventory.trace.component_detail', [
                    'component' => $this->componentLabel($unit->component_type),
                    'source' => $unit->parent?->unit_number ?? __('bloodcare.traceability.not_recorded'),
                ]),
                'done',
            );
        }

        foreach ($this->descendantLineage($unit) as $derived) {
            $history[] = $this->traceEvent(
                __('bloodcare.inventory.trace.component_prepared'),
                $derived['createdAt'],
                __('bloodcare.inventory.trace.component_detail', [
                    'component' => $derived['component'],
                    'source' => $derived['sourceId'] ?? __('bloodcare.traceability.not_recorded'),
                ]),
                'done',
            );
        }

        foreach ($unit->allocations->sortBy('allocated_at') as $allocation) {
            $hospital = $allocation->request?->hospital?->name ?? __('bloodcare.traceability.not_recorded');
            $request = $allocation->request?->reference ?? __('bloodcare.traceability.not_recorded');

            if ($allocation->allocated_at) {
                $history[] = $this->traceEvent(
                    __('bloodcare.inventory.trace.allocated'),
                    $allocation->allocated_at->toIso8601String(),
                    __('bloodcare.inventory.trace.allocated_detail', [
                        'hospital' => $hospital,
                        'request' => $request,
                        'crossmatch' => str($allocation->crossmatch_result)->replace('_', ' ')->title()->toString(),
                    ]),
                    'done',
                );
            }
            if ($allocation->dispatched_at) {
                $history[] = $this->traceEvent(
                    __('bloodcare.inventory.trace.dispatched'),
                    $allocation->dispatched_at->toIso8601String(),
                    __('bloodcare.inventory.trace.hospital_detail', ['hospital' => $hospital]),
                    'done',
                );
            }
            if ($allocation->received_at) {
                $history[] = $this->traceEvent(
                    __('bloodcare.inventory.trace.received'),
                    $allocation->received_at->toIso8601String(),
                    __('bloodcare.inventory.trace.hospital_detail', ['hospital' => $hospital]),
                    'done',
                );
            }
            if ($allocation->transfused_at) {
                $history[] = $this->traceEvent(
                    __('bloodcare.inventory.trace.transfused'),
                    $allocation->transfused_at->toIso8601String(),
                    __('bloodcare.inventory.trace.transfused_detail', ['hospital' => $hospital]),
                    'done',
                );
            }
            foreach ($allocation->reactions->sortBy('occurred_at') as $reaction) {
                $history[] = $this->traceEvent(
                    __('bloodcare.inventory.trace.reaction'),
                    $reaction->occurred_at?->toIso8601String(),
                    __('bloodcare.inventory.trace.reaction_detail', [
                        'reference' => $reaction->reference,
                        'severity' => str($reaction->severity)->replace('_', ' ')->title()->toString(),
                    ]),
                    'warning',
                );
                if ($reaction->reviewed_at) {
                    $history[] = $this->traceEvent(
                        __('bloodcare.inventory.trace.reaction_reviewed'),
                        $reaction->reviewed_at->toIso8601String(),
                        __('bloodcare.inventory.trace.reaction_reviewed_detail', [
                            'reference' => $reaction->reference,
                            'status' => $this->haemovigilanceLabel('statuses', $reaction->status),
                        ]),
                        $reaction->status === 'closed' ? 'done' : 'warning',
                    );
                }
                if ($reaction->closed_at) {
                    $history[] = $this->traceEvent(
                        __('bloodcare.inventory.trace.reaction_closed'),
                        $reaction->closed_at->toIso8601String(),
                        __('bloodcare.inventory.trace.reaction_closed_detail', [
                            'reference' => $reaction->reference,
                            'classification' => $reaction->reaction_type
                                ? $this->haemovigilanceLabel('reaction_types', $reaction->reaction_type)
                                : __('bloodcare.national.haemovigilance.not_classified'),
                        ]),
                        'done',
                    );
                }
            }
        }

        $history[] = $this->traceEvent(
            __('bloodcare.inventory.trace.current_state'),
            now()->toIso8601String(),
            __('bloodcare.inventory.trace.current_state_detail', ['status' => $this->statusLabel($unit->status)]),
            $unit->status === 'discarded' ? 'danger' : 'neutral',
        );

        return $history;
    }

    /** @return array<int, array<string, mixed>> */
    private function haemovigilanceDetails(BloodUnit $unit): array
    {
        return $unit->allocations
            ->flatMap(fn ($allocation) => $allocation->reactions)
            ->sortByDesc('occurred_at')
            ->values()
            ->map(fn ($reaction): array => [
                'reference' => $reaction->reference,
                'severity' => $reaction->severity,
                'severityLabel' => __('bloodcare.national.portal.'.$reaction->severity),
                'status' => $reaction->status,
                'statusLabel' => $this->haemovigilanceLabel('statuses', $reaction->status),
                'suspectedTypeLabel' => $reaction->suspected_reaction_type
                    ? $this->haemovigilanceLabel('reaction_types', $reaction->suspected_reaction_type)
                    : __('bloodcare.national.haemovigilance.not_classified'),
                'reactionTypeLabel' => $reaction->reaction_type
                    ? $this->haemovigilanceLabel('reaction_types', $reaction->reaction_type)
                    : __('bloodcare.national.haemovigilance.not_classified'),
                'imputabilityLabel' => $reaction->imputability
                    ? $this->haemovigilanceLabel('imputability_options', $reaction->imputability)
                    : __('bloodcare.national.haemovigilance.not_assessed'),
                'outcomeLabel' => $reaction->outcome
                    ? $this->haemovigilanceLabel('outcomes', $reaction->outcome)
                    : __('bloodcare.national.haemovigilance.not_assessed'),
                'occurredAt' => $reaction->occurred_at?->toIso8601String(),
                'reviewedAt' => $reaction->reviewed_at?->toIso8601String(),
                'closedAt' => $reaction->closed_at?->toIso8601String(),
            ])
            ->all();
    }

    private function haemovigilanceLabel(string $group, string $value): string
    {
        $key = 'bloodcare.national.haemovigilance.'.$group.'.'.$value;
        $label = __($key);

        return $label === $key ? str($value)->replace('_', ' ')->title()->toString() : $label;
    }

    /** @return array{label:string,date:?string,detail:string,tone:string} */
    private function traceEvent(string $label, ?string $date, string $detail, string $tone): array
    {
        return compact('label', 'date', 'detail', 'tone');
    }

    /** @return array<int, string> */
    private function modifierLabels(BloodUnit $unit): array
    {
        return collect([
            $unit->leukoreduced ? __('bloodcare.national.components.modifiers.leukoreduced') : null,
            $unit->irradiated ? __('bloodcare.national.components.modifiers.irradiated') : null,
            $unit->washed ? __('bloodcare.national.components.modifiers.washed') : null,
        ])->filter()->values()->all();
    }

    private function componentLabel(string $component): string
    {
        $key = 'bloodcare.national.components.types.'.$component;
        $label = __($key);

        return $label === $key ? str($component)->replace('_', ' ')->title()->toString() : $label;
    }

    private function statusLabel(string $status): string
    {
        $key = 'bloodcare.traceability.statuses.'.$status;
        $label = __($key);

        return $label === $key ? str($status)->replace('_', ' ')->title()->toString() : $label;
    }

    private function validated(Request $request, ?BloodUnit $unit = null): array
    {
        $data = $request->validate(
            [
                'id' => ['required', 'string', 'max:40', Rule::unique('blood_units', 'unit_number')->ignore($unit?->id)],
                'group' => ['required', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
                'collected' => ['required', 'date', 'before_or_equal:today'],
                'expires' => ['required', 'date', 'after:collected'],
                'location' => ['required', 'string', 'max:120'],
                'status' => ['required', Rule::in($unit ? ['Available', 'Reserved', 'Used', 'Discarded', 'Quarantined'] : ['Available', 'Reserved', 'Used', 'Discarded'])],
                'note' => ['required', 'string', 'max:2000'],
            ],
            [],
            [
                'id' => __('bloodcare.inventory.unit_number'),
                'group' => __('bloodcare.inventory.blood_group'),
                'collected' => __('bloodcare.inventory.collected_date'),
                'expires' => __('bloodcare.inventory.expiry_date'),
                'location' => __('bloodcare.inventory.location'),
                'status' => __('bloodcare.inventory.status'),
                'note' => __('bloodcare.inventory.note'),
            ],
        );

        if ($unit && $data['group'] !== $unit->blood_group) {
            throw ValidationException::withMessages([
                'group' => 'The blood group of an existing unit cannot be changed.',
            ]);
        }

        if ($unit?->donation_id && $unit->status === 'quarantined' && strtolower($data['status']) !== 'quarantined') {
            throw ValidationException::withMessages([
                'status' => 'Quarantined donation units can only be released by the Laboratory workflow.',
            ]);
        }

        return $data;
    }

    private function attributes(array $data): array
    {
        return [
            'unit_number' => strtoupper($data['id']),
            'blood_group' => $data['group'],
            'collected_at' => $data['collected'],
            'expires_at' => $data['expires'],
            'storage_location' => trim($data['location']),
            'status' => strtolower($data['status']),
            'notes' => trim($data['note']),
        ];
    }

    private function log(BloodUnit $unit, string $action): void
    {
        ActivityLog::record([
            'type' => 'Inventory',
            'action' => $action,
            'subject_type' => BloodUnit::class,
            'subject_id' => $unit->id,
            'donor_id' => $unit->donation?->donor_id,
            'user_id' => backpack_user()?->id,
            'result' => ucfirst($unit->status),
            'details' => "{$unit->unit_number}; {$unit->blood_group}; {$unit->storage_location}",
            'source' => 'admin-inventory',
        ]);
    }
}
