<?php

namespace App\Http\Resources;

use App\Models\DonationCentre;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public-safe representation of a donation centre.
 *
 * The field set intentionally matches the JSON block already exposed by the
 * public Blade pages (resources/views/public/home.blade.php and
 * appointment-book.blade.php). Internal identifiers (id, code), the centre
 * telephone number, and timestamps are deliberately excluded.
 *
 * @mixin DonationCentre
 */
class CentreResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'region' => $this->region,
            'township' => $this->township,
            'address' => $this->address,
            'hours' => $this->opening_hours,
            'active' => $this->is_active,
        ];
    }
}
