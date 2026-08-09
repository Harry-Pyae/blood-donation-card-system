<?php

namespace App\Http\Controllers;

use App\Models\BloodUnit;
use App\Models\DonationCard;
use Illuminate\View\View;

class TraceabilityController extends Controller
{
    public function card(string $token): View
    {
        $card = DonationCard::query()
            ->with(['donor.donations'])
            ->where('qr_token', $token)
            ->firstOrFail();

        $status = $card->status;
        if ($status !== 'suspended' && $card->expires_at?->isPast()) {
            $status = 'expired';
        }

        $lastDonation = $card->donor->last_donation_date?->format('d M Y')
            ?? __('bloodcare.traceability.not_recorded');
        $nextEligible = $card->donor->next_eligible_date?->format('d M Y')
            ?? ($card->donor->eligibility_status === 'eligible'
                ? __('bloodcare.traceability.eligible_now')
                : __('bloodcare.traceability.review_required'));

        return view('public.traceability', [
            'kind' => 'card',
            'eyebrow' => __('bloodcare.traceability.card.eyebrow'),
            'title' => __('bloodcare.traceability.card.title'),
            'description' => __('bloodcare.traceability.card.description'),
            'reference' => $card->card_number,
            'status' => $status,
            'statusLabel' => $this->statusLabel($status),
            'summary' => [
                ['label' => __('bloodcare.traceability.card.card_number'), 'value' => $card->card_number],
                ['label' => __('bloodcare.traceability.card.donor'), 'value' => $card->donor->full_name],
                ['label' => __('bloodcare.traceability.card.blood_group'), 'value' => $card->donor->blood_group],
                ['label' => __('bloodcare.traceability.card.issued'), 'value' => $card->issued_at?->format('d M Y') ?? '—'],
                ['label' => __('bloodcare.traceability.card.valid_until'), 'value' => $card->expires_at?->format('d M Y') ?? '—'],
                ['label' => __('bloodcare.traceability.card.version'), 'value' => (string) ($card->replacement_count + 1)],
                ['label' => __('bloodcare.traceability.card.last_donation'), 'value' => $lastDonation],
                ['label' => __('bloodcare.traceability.card.next_eligible'), 'value' => $nextEligible],
            ],
            'timeline' => [
                [
                    'label' => __('bloodcare.traceability.card.issued_event'),
                    'date' => $card->issued_at?->format('d M Y') ?? '—',
                    'detail' => __('bloodcare.traceability.card.issued_event_detail', ['version' => $card->replacement_count + 1]),
                    'tone' => 'done',
                ],
                [
                    'label' => __('bloodcare.traceability.card.current_event'),
                    'date' => now()->format('d M Y'),
                    'detail' => __('bloodcare.traceability.card.current_event_detail', ['status' => $this->statusLabel($status)]),
                    'tone' => in_array($status, ['active', 'expiring'], true) ? 'done' : 'warning',
                ],
            ],
            'ancestors' => [],
            'descendants' => [],
            'privacy' => __('bloodcare.traceability.card.privacy'),
        ]);
    }

    public function bloodUnit(string $token): View
    {
        $unit = BloodUnit::query()
            ->with([
                'donation.centre',
                'donation.labTest',
                'allocations.request.hospital',
                'allocations.reactions',
            ])
            ->where('trace_token', $token)
            ->firstOrFail();

        $timeline = [];
        $donation = $unit->donation;
        $lab = $donation?->labTest;

        if ($donation) {
            $timeline[] = [
                'label' => __('bloodcare.traceability.unit.collection_event'),
                'date' => $unit->collected_at?->format('d M Y') ?? '—',
                'detail' => __('bloodcare.traceability.unit.collection_event_detail', [
                    'donation' => $donation->reference,
                    'centre' => $donation->centre?->name ?? __('bloodcare.traceability.not_recorded'),
                ]),
                'tone' => 'done',
            ];
        } else {
            $timeline[] = [
                'label' => __('bloodcare.traceability.unit.manual_event'),
                'date' => $unit->collected_at?->format('d M Y') ?? '—',
                'detail' => __('bloodcare.traceability.unit.manual_event_detail'),
                'tone' => 'neutral',
            ];
        }

        if ($lab) {
            $released = $lab->release_status === 'released';
            $timeline[] = [
                'label' => $released
                    ? __('bloodcare.traceability.unit.lab_released_event')
                    : __('bloodcare.traceability.unit.lab_discarded_event'),
                'date' => $lab->released_at?->format('d M Y, H:i')
                    ?? $lab->tested_at?->format('d M Y, H:i')
                    ?? '—',
                'detail' => $released
                    ? __('bloodcare.traceability.unit.lab_released_detail', ['reference' => $lab->reference])
                    : __('bloodcare.traceability.unit.lab_discarded_detail', ['reference' => $lab->reference]),
                'tone' => $released ? 'done' : 'danger',
            ];
        } elseif ($donation && $unit->status === 'quarantined') {
            $timeline[] = [
                'label' => __('bloodcare.traceability.unit.quarantine_event'),
                'date' => '—',
                'detail' => __('bloodcare.traceability.unit.quarantine_detail'),
                'tone' => 'warning',
            ];
        }

        if ($unit->parent_blood_unit_id) {
            $timeline[] = [
                'label' => __('bloodcare.traceability.unit.component_event'),
                'date' => $unit->created_at?->format('d M Y, H:i') ?? '—',
                'detail' => __('bloodcare.traceability.unit.component_event_detail', [
                    'component' => $this->componentLabel($unit->component_type),
                    'source' => $unit->parent?->unit_number ?? __('bloodcare.traceability.not_recorded'),
                ]),
                'tone' => 'done',
            ];
        }

        foreach ($unit->allocations->sortBy('allocated_at') as $allocation) {
            $hospital = $allocation->request?->hospital?->name ?? __('bloodcare.traceability.not_recorded');
            $request = $allocation->request?->reference ?? __('bloodcare.traceability.not_recorded');

            if ($allocation->allocated_at) {
                $timeline[] = [
                    'label' => __('bloodcare.traceability.unit.allocated_event'),
                    'date' => $allocation->allocated_at->format('d M Y, H:i'),
                    'detail' => __('bloodcare.traceability.unit.allocated_detail', ['hospital' => $hospital, 'request' => $request]),
                    'tone' => 'done',
                ];
            }
            if ($allocation->dispatched_at) {
                $timeline[] = [
                    'label' => __('bloodcare.traceability.unit.dispatched_event'),
                    'date' => $allocation->dispatched_at->format('d M Y, H:i'),
                    'detail' => __('bloodcare.traceability.unit.dispatched_detail', ['hospital' => $hospital]),
                    'tone' => 'done',
                ];
            }
            if ($allocation->received_at) {
                $timeline[] = [
                    'label' => __('bloodcare.traceability.unit.received_event'),
                    'date' => $allocation->received_at->format('d M Y, H:i'),
                    'detail' => __('bloodcare.traceability.unit.received_detail', ['hospital' => $hospital]),
                    'tone' => 'done',
                ];
            }
            if ($allocation->transfused_at) {
                $timeline[] = [
                    'label' => __('bloodcare.traceability.unit.transfused_event'),
                    'date' => $allocation->transfused_at->format('d M Y, H:i'),
                    'detail' => __('bloodcare.traceability.unit.transfused_detail'),
                    'tone' => 'done',
                ];
            }
            if ($allocation->reactions->isNotEmpty()) {
                $firstReaction = $allocation->reactions->sortBy('occurred_at')->first();
                $timeline[] = [
                    'label' => __('bloodcare.traceability.unit.reaction_event'),
                    'date' => $firstReaction?->occurred_at
                        ? \Illuminate\Support\Carbon::parse($firstReaction->occurred_at)->format('d M Y, H:i')
                        : '—',
                    'detail' => __('bloodcare.traceability.unit.reaction_detail'),
                    'tone' => 'warning',
                ];
            }
        }

        $timeline[] = [
            'label' => __('bloodcare.traceability.unit.inventory_event'),
            'date' => now()->format('d M Y'),
            'detail' => __('bloodcare.traceability.unit.inventory_event_detail', ['status' => $this->statusLabel($unit->status)]),
            'tone' => $unit->status === 'discarded' ? 'danger' : 'neutral',
        ];

        $modifiers = collect([
            $unit->leukoreduced ? __('bloodcare.national.components.modifiers.leukoreduced') : null,
            $unit->irradiated ? __('bloodcare.national.components.modifiers.irradiated') : null,
            $unit->washed ? __('bloodcare.national.components.modifiers.washed') : null,
        ])->filter()->implode(' · ');

        return view('public.traceability', [
            'kind' => 'unit',
            'eyebrow' => __('bloodcare.traceability.unit.eyebrow'),
            'title' => __('bloodcare.traceability.unit.title'),
            'description' => __('bloodcare.traceability.unit.description'),
            'reference' => $unit->unit_number,
            'status' => $unit->status,
            'statusLabel' => $this->statusLabel($unit->status),
            'summary' => [
                ['label' => __('bloodcare.traceability.unit.unit_number'), 'value' => $unit->unit_number],
                ['label' => __('bloodcare.traceability.unit.blood_group'), 'value' => $unit->blood_group],
                ['label' => __('bloodcare.traceability.unit.component'), 'value' => $this->componentLabel($unit->component_type)],
                ['label' => __('bloodcare.traceability.unit.collected'), 'value' => $unit->collected_at?->format('d M Y') ?? '—'],
                ['label' => __('bloodcare.traceability.unit.expires'), 'value' => $unit->expires_at?->format('d M Y') ?? '—'],
                ['label' => __('bloodcare.traceability.unit.modifiers'), 'value' => $modifiers ?: __('bloodcare.traceability.unit.no_modifiers')],
            ],
            'timeline' => $timeline,
            'ancestors' => $this->ancestorLineage($unit),
            'descendants' => $this->descendantLineage($unit),
            'privacy' => __('bloodcare.traceability.unit.privacy'),
        ]);
    }

    /** @return array<int, array{unit:string, component:string, url:string}> */
    private function ancestorLineage(BloodUnit $unit): array
    {
        $items = [];
        $cursor = $unit->parent;
        $guard = 0;

        while ($cursor && $guard < 10) {
            array_unshift($items, $this->lineageItem($cursor));
            $cursor = $cursor->parent;
            $guard++;
        }

        return $items;
    }

    /** @return array<int, array{unit:string, component:string, url:string}> */
    private function descendantLineage(BloodUnit $unit, int $depth = 0): array
    {
        if ($depth >= 4) {
            return [];
        }

        $items = [];
        foreach ($unit->components()->orderBy('id')->get() as $child) {
            $items[] = $this->lineageItem($child);
            array_push($items, ...$this->descendantLineage($child, $depth + 1));
        }

        return $items;
    }

    /** @return array{unit:string, component:string, url:string} */
    private function lineageItem(BloodUnit $unit): array
    {
        return [
            'unit' => $unit->unit_number,
            'component' => $this->componentLabel($unit->component_type),
            'url' => $unit->trace_token ? route('trace.blood-unit', $unit->trace_token) : '',
        ];
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
}
