<?php

namespace App\Http\Resources;

use App\Models\DonationCard;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Privacy-safe donation card lookup payload.
 *
 * Mirrors the fields the Blade card lookup already displays. It must never
 * carry the QR token (that is the traceability credential), medical screening
 * data, identity numbers, contact details, deferral reasons, staff notes or
 * database identifiers.
 *
 * @mixin DonationCard
 */
class DonationCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'card_number' => $this->card_number,
            'donor_name' => $this->donor->full_name,
            'blood_group' => $this->donor->blood_group,
            'last_donation_date' => $this->donor->last_donation_date?->toDateString(),
            'next_eligible_date' => $this->donor->next_eligible_date?->toDateString(),
            'total_donations' => $this->donor->donations->count(),
            'status' => $this->status,
        ];
    }
}
