<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DonationCentre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DonationCentreManagementController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $centre = DonationCentre::create([
            ...$this->attributes($data),
            'code' => $this->generateCode(),
        ]);
        $this->log($centre, 'Donation centre created');

        return response()->json(['message' => 'Centre saved.', 'centre' => $centre], 201);
    }

    public function update(Request $request, string $centre): JsonResponse
    {
        $model = DonationCentre::where('code', $centre)->firstOrFail();
        $data = $this->validated($request, $model);
        $model->update($this->attributes($data));
        $this->log($model, 'Donation centre updated');

        return response()->json(['message' => 'Centre updated.', 'centre' => $model->fresh()]);
    }

    public function updateStatus(Request $request, string $centre): JsonResponse
    {
        $model = DonationCentre::where('code', $centre)->firstOrFail();
        $data = $request->validate(['active' => ['required', 'boolean']]);
        $model->update(['is_active' => $data['active']]);
        $this->log($model, $model->is_active ? 'Donation centre activated' : 'Donation centre deactivated');

        return response()->json(['message' => 'Centre status updated.']);
    }

    private function validated(Request $request, ?DonationCentre $centre = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160', Rule::unique('donation_centres', 'name')->ignore($centre?->id)],
            'region' => ['required', 'string', 'max:120'],
            'township' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:40'],
            'hours' => ['nullable', 'string', 'max:160'],
            'active' => ['required', 'boolean'],
        ]);
    }

    private function attributes(array $data): array
    {
        return [
            'name' => trim($data['name']),
            'region' => trim($data['region']),
            'township' => trim($data['township']),
            'address' => trim($data['address']),
            'phone' => $data['phone'] ?: null,
            'opening_hours' => $data['hours'] ?: null,
            'is_active' => $data['active'],
        ];
    }

    private function generateCode(): string
    {
        do {
            $code = 'CTR-'.strtoupper(Str::random(6));
        } while (DonationCentre::where('code', $code)->exists());

        return $code;
    }

    private function log(DonationCentre $centre, string $action): void
    {
        ActivityLog::record([
            'type' => 'Centre',
            'action' => $action,
            'subject_type' => DonationCentre::class,
            'subject_id' => $centre->id,
            'user_id' => backpack_user()?->id,
            'result' => $centre->is_active ? 'Active' : 'Inactive',
            'details' => "{$centre->code}; {$centre->name}; {$centre->township}",
            'source' => 'admin-centres',
        ]);
    }
}
