<?php

namespace App\Http\Requests\Public;

use App\Models\DonationCentre;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared validation for public appointment booking.
 *
 * The centre is submitted by its canonical name, not its id, and the
 * "__request__" sentinel means "no centre near me" — both behaviours are part
 * of the accepted backend and are preserved here verbatim.
 */
class StoreAppointmentRequest extends FormRequest
{
    public const REQUEST_LOCATION = '__request__';

    /** @var list<string> */
    public const TIMES = ['09:00', '10:30', '13:00', '14:30'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $activeCentreNames = DonationCentre::query()
            ->where('is_active', true)
            ->pluck('name')
            ->all();

        return [
            'donor_reference' => ['required', 'string', 'max:40'],
            'phone' => ['required', 'string', 'max:30'],
            'centre' => ['required', Rule::in([...$activeCentreNames, self::REQUEST_LOCATION])],
            'appointment_date' => ['nullable', 'required_unless:centre,'.self::REQUEST_LOCATION, 'date', 'after_or_equal:today', 'before_or_equal:'.now()->addMonths(3)->toDateString()],
            'appointment_time' => ['nullable', 'required_unless:centre,'.self::REQUEST_LOCATION, Rule::in(self::TIMES)],
            'requested_region' => ['nullable', 'required_if:centre,'.self::REQUEST_LOCATION, 'string', 'max:120'],
            'requested_township' => ['nullable', 'required_if:centre,'.self::REQUEST_LOCATION, 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
            'booking_acknowledgement' => ['accepted'],
        ];
    }
}
