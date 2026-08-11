<?php

namespace App\Http\Resources;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Confirmation payload returned after booking, or after a location request.
 *
 * When no centre was chosen, centre_name is null and the requested region and
 * township carry the donor's area instead.
 *
 * @mixin Appointment
 */
class AppointmentConfirmationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->reference,
            'centre_name' => $this->centre_name,
            'appointment_date' => $this->appointment_date?->toDateString(),
            'appointment_time' => $this->appointment_time
                ? substr((string) $this->appointment_time, 0, 5)
                : null,
            'requested_region' => $this->requested_region,
            'requested_township' => $this->requested_township,
            'purpose' => $this->purpose,
            'status' => $this->status,
            'is_location_request' => $this->donation_centre_id === null,
        ];
    }
}
