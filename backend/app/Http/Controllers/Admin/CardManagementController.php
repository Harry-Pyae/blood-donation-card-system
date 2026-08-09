<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DonationCard;
use App\Models\Donor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CardManagementController extends Controller
{
    private const DONOR_SEARCH_LIMIT = 8;

    public function searchDonors(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $term = trim((string) ($data['q'] ?? ''));

        $query = Donor::query()
            ->where('status', 'active')
            ->where('eligibility_status', 'eligible')
            ->whereDoesntHave('card', fn ($cardQuery) => $cardQuery->whereNotNull('issued_at'));

        if ($term !== '') {
            $query->where(function ($searchQuery) use ($term): void {
                $searchQuery
                    ->where('reference', 'like', "%{$term}%")
                    ->orWhere('full_name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('blood_group', 'like', "%{$term}%");
            });
        }

        $donors = $query
            ->orderBy('full_name')
            ->orderBy('id')
            ->limit(self::DONOR_SEARCH_LIMIT)
            ->get(['id', 'reference', 'full_name', 'blood_group', 'phone', 'eligibility_status', 'status'])
            ->map(fn (Donor $donor): array => [
                'id' => $donor->reference,
                'name' => $donor->full_name,
                'group' => $donor->blood_group,
                'phone' => $donor->phone,
                'eligibility' => ucfirst($donor->eligibility_status),
                'status' => ucfirst($donor->status),
            ])
            ->values();

        return response()->json([
            'donors' => $donors,
            'limit' => self::DONOR_SEARCH_LIMIT,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $donor = Donor::where('reference', $data['donorId'])->firstOrFail();

        if ($donor->status !== 'active' || $donor->eligibility_status !== 'eligible') {
            throw ValidationException::withMessages([
                'donorId' => 'Only active, eligible donors can receive a card.',
            ]);
        }

        if ($donor->card?->issued_at) {
            throw ValidationException::withMessages([
                'donorId' => 'This donor already has an issued card.',
            ]);
        }

        $card = DB::transaction(function () use ($data, $donor): DonationCard {
            $card = DonationCard::updateOrCreate(
                ['donor_id' => $donor->id],
                [
                    'card_number' => $data['cardNumber'] ?: $donor->reference,
                    'issued_at' => $data['issueDate'],
                    'expires_at' => $data['expiryDate'],
                    'status' => 'active',
                    'qr_token' => Str::uuid()->toString(),
                    'issued_by' => backpack_user()?->id,
                    'notes' => $data['notes'] ?: null,
                ]
            );
            $this->log($card, 'Donation card issued');

            return $card;
        });

        return response()->json(['message' => 'Donation card issued.', 'card' => $card], 201);
    }

    public function update(Request $request, string $card): JsonResponse
    {
        $model = DonationCard::where('card_number', $card)->firstOrFail();
        $data = $request->validate([
            'issueDate' => ['required', 'date'],
            'expiryDate' => ['required', 'date', 'after:issueDate'],
            'status' => ['required', Rule::in(['Active', 'Suspended'])],
            'replacementCount' => ['required', 'integer', 'min:0', 'max:999'],
            'printCount' => ['required', 'integer', 'min:0', 'max:9999'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'action' => ['nullable', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($model, $data): void {
            $replacement = ($data['action'] ?? '') === 'Lost card replaced';
            $model->update([
                'issued_at' => $data['issueDate'],
                'expires_at' => $data['expiryDate'],
                'status' => strtolower($data['status']),
                'replacement_count' => $data['replacementCount'],
                'print_count' => $data['printCount'],
                'issued_by' => backpack_user()?->id,
                'notes' => $data['notes'] ?: null,
                // A lost card must invalidate the QR printed on the old copy.
                // Renewal/printing keeps the existing QR identity stable.
                'qr_token' => $replacement ? Str::uuid()->toString() : $model->qr_token,
            ]);
            $this->log($model, $data['action'] ?: 'Donation card updated');
        });

        return response()->json(['message' => 'Donation card updated.', 'card' => $model->fresh()]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'donorId' => ['required', 'string', Rule::exists('donors', 'reference')],
            'cardNumber' => ['nullable', 'string', 'max:40', Rule::unique('donation_cards', 'card_number')],
            'issueDate' => ['required', 'date'],
            'expiryDate' => ['required', 'date', 'after:issueDate'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function log(DonationCard $card, string $action): void
    {
        $card->loadMissing('donor');
        ActivityLog::record([
            'type' => 'Card',
            'action' => $action,
            'subject_type' => DonationCard::class,
            'subject_id' => $card->id,
            'donor_id' => $card->donor_id,
            'user_id' => backpack_user()?->id,
            'result' => ucfirst($card->status),
            'details' => "{$card->card_number}; {$card->donor?->full_name}; valid until {$card->expires_at?->toDateString()}",
            'source' => 'admin-cards',
        ]);
    }
}
