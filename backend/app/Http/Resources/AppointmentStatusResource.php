<?php

namespace App\Http\Resources;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Privacy-safe appointment lookup payload.
 *
 * Matches the four fields the Blade lookup already exposes: centre, date and
 * time, purpose, status. The donor's name, phone, notes and every internal
 * identifier are withheld — the caller proved knowledge of the reference and
 * phone, which is not the same as being authenticated.
 *
 * @mixin Appointment
 */
class AppointmentStatusResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->reference,
            'centre_name' => $this->centre_name,
            'requested_region' => $this->requested_region,
            'requested_township' => $this->requested_township,
            'appointment_date' => $this->appointment_date?->toDateString(),
            'appointment_time' => $this->appointment_time
                ? substr((string) $this->appointment_time, 0, 5)
                : null,
            'purpose' => $this->purpose,
            'status' => $this->status,
        ];
    }
}
