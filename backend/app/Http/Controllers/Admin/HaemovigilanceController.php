<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AdverseReaction;
use App\Services\BloodCareNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class HaemovigilanceController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(AdverseReaction::STATUSES)],
            'severity' => ['nullable', Rule::in(['mild', 'moderate', 'severe', 'life_threatening'])],
        ]);
        $search = trim((string) ($filters['q'] ?? ''));
        $status = $filters['status'] ?? null;
        $severity = $filters['severity'] ?? null;

        $reports = AdverseReaction::query()
            ->with([
                'hospital', 'allocation.unit', 'allocation.request',
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('reference', 'like', '%'.$search.'%')
                        ->orWhere('severity', 'like', '%'.$search.'%')
                        ->orWhere('reaction_type', 'like', '%'.str_replace(' ', '_', strtolower($search)).'%')
                        ->orWhereHas('hospital', fn ($hospital) => $hospital->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%'))
                        ->orWhereHas('allocation.unit', fn ($unit) => $unit->where('unit_number', 'like', '%'.$search.'%'))
                        ->orWhereHas('allocation.request', fn ($bloodRequest) => $bloodRequest->where('reference', 'like', '%'.$search.'%')->orWhere('patient_reference', 'like', '%'.$search.'%'));
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($severity, fn ($query) => $query->where('severity', $severity))
            ->orderByRaw("CASE WHEN status = 'closed' THEN 1 ELSE 0 END")
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $metrics = [
            'total' => AdverseReaction::count(),
            'open' => AdverseReaction::where('status', '!=', 'closed')->count(),
            'serious' => AdverseReaction::where('status', '!=', 'closed')->whereIn('severity', ['severe', 'life_threatening'])->count(),
            'closed' => AdverseReaction::where('status', 'closed')->count(),
        ];

        return view('admin.national.haemovigilance', compact('reports', 'metrics', 'search', 'status', 'severity'));
    }

    public function show(AdverseReaction $reaction): View
    {
        $reaction->load([
            'hospital', 'reporter', 'reviewedBy', 'closedBy',
            'allocation.unit.donation.bloodUnits', 'allocation.request',
        ]);

        return view('admin.national.haemovigilance-show', compact('reaction'));
    }

    public function update(Request $request, AdverseReaction $reaction, BloodCareNotificationService $notifications): RedirectResponse
    {
        if ($reaction->status === 'closed') {
            throw ValidationException::withMessages(['status' => __('bloodcare.national.haemovigilance.closed_locked')]);
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(AdverseReaction::STATUSES)],
            'reaction_type' => ['nullable', Rule::in(AdverseReaction::REACTION_TYPES)],
            'imputability' => ['nullable', Rule::in(AdverseReaction::IMPUTABILITY)],
            'outcome' => ['nullable', Rule::in(AdverseReaction::OUTCOMES)],
            'investigation_notes' => ['nullable', 'string', 'max:5000'],
            'corrective_action' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($data['status'] === 'closed') {
            $required = [
                'reaction_type' => $data['reaction_type'] ?? null,
                'imputability' => $data['imputability'] ?? null,
                'outcome' => $data['outcome'] ?? null,
                'investigation_notes' => trim((string) ($data['investigation_notes'] ?? '')),
                'corrective_action' => trim((string) ($data['corrective_action'] ?? '')),
            ];
            $missing = array_keys(array_filter($required, fn ($value) => $value === null || $value === '' || $value === 'not_assessed'));
            if ($missing !== []) {
                throw ValidationException::withMessages(['status' => __('bloodcare.national.haemovigilance.close_requirements')]);
            }
        }

        DB::transaction(function () use ($reaction, $data, $request, $notifications): void {
            $current = AdverseReaction::query()->lockForUpdate()->findOrFail($reaction->id);
            if ($current->status === 'closed') {
                throw ValidationException::withMessages(['status' => __('bloodcare.national.haemovigilance.closed_locked')]);
            }

            $attributes = [
                ...$data,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ];
            if ($data['status'] === 'closed') {
                $attributes['closed_by'] = $request->user()->id;
                $attributes['closed_at'] = now();
            }

            $current->update($attributes);
            $current->load(['hospital', 'reporter', 'allocation.unit', 'allocation.request']);

            ActivityLog::record([
                'type' => 'Haemovigilance',
                'action' => $data['status'] === 'closed' ? 'Haemovigilance case closed' : 'Haemovigilance investigation updated',
                'subject_type' => AdverseReaction::class,
                'subject_id' => $current->id,
                'user_id' => $request->user()->id,
                'result' => str($data['status'])->replace('_', ' ')->title()->toString(),
                'details' => $current->reference.'; '.($current->allocation?->unit?->unit_number ?? 'Unit unavailable'),
                'source' => 'admin-haemovigilance',
            ]);
            $notifications->haemovigilanceUpdated($current);
        });

        return redirect()->route('bloodcare.admin.haemovigilance.show', $reaction)
            ->with('status', __('bloodcare.national.haemovigilance.saved'));
    }
}
