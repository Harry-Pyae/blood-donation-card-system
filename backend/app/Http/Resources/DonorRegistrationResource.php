<?php

namespace App\Http\Resources;

use App\Models\Donor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Confirmation payload returned after a public donor registration.
 *
 * Deliberately minimal: only what the donor needs to see on the confirmation
 * screen and to carry into appointment booking. No id, identity number,
 * address, email, health notes, staff notes, deferral data or timestamps.
 *
 * @mixin Donor
 */
class DonorRegistrationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->reference,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'blood_group' => $this->blood_group,
            'status' => $this->status,
            'eligibility_status' => $this->eligibility_status,
        ];
    }
}
