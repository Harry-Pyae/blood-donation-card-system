<?php

namespace App\Http\Requests\Public;

use App\Support\MyanmarNrc;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared validation for public donor registration.
 *
 * Used by both the existing Blade flow (PublicSiteController) and the versioned
 * API. The rules, translated attribute names, Burmese numeral normalisation and
 * +95 phone assembly all live here so the two entry points can never drift.
 */
class StoreDonorRegistrationRequest extends FormRequest
{
    /** @var array<string, mixed>|null */
    private ?array $nrcReference = null;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Runs before validation, exactly as the Blade controller did.
     *
     * Burmese digits are folded to ASCII and the local number is expanded into
     * the canonical "+95 <digits>" form the phone rule expects.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('phone_local')) {
            $localPhone = preg_replace('/[^0-9]+/', '', strtr((string) $this->input('phone_local'), [
                '၀' => '0', '၁' => '1', '၂' => '2', '၃' => '3', '၄' => '4',
                '၅' => '5', '၆' => '6', '၇' => '7', '၈' => '8', '၉' => '9',
            ])) ?? '';

            $this->merge([
                'phone_local' => $localPhone,
                'phone' => '+95 '.$localPhone,
            ]);
        }

        $this->merge([
            'nrc_serial' => MyanmarNrc::normalizeSerial($this->input('nrc_serial')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $reference = $this->nrcReference();
        $allowedTownships = array_column($this->stateTownships(), 'value');

        return [
            'full_name' => ['required', 'string', 'max:120'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString()],
            'gender' => ['required', Rule::in(['female', 'male', 'other'])],
            'identity_document_type' => ['required', Rule::in(['nrc', 'passport'])],
            'nrc_state' => ['exclude_unless:identity_document_type,nrc', 'required', Rule::in(array_keys($reference['nrcStates']))],
            'nrc_township' => ['exclude_unless:identity_document_type,nrc', 'required', Rule::in($allowedTownships)],
            'nrc_type' => ['exclude_unless:identity_document_type,nrc', 'required', Rule::in(array_keys($reference['nrcTypes']))],
            'nrc_serial' => ['exclude_unless:identity_document_type,nrc', 'required', 'regex:/^[0-9]{6}$/'],
            'passport_number' => ['exclude_unless:identity_document_type,passport', 'required', 'string', 'min:5', 'max:20', 'regex:/^[A-Za-z0-9]+$/'],
            'phone_local' => ['nullable', 'regex:/^[1-9][0-9]{6,11}$/'],
            'phone' => ['required', 'string', 'regex:/^\+95\s?[1-9][0-9]{6,11}$/'],
            'email' => ['nullable', 'email', 'max:120'],
            'address' => ['required', 'string', 'max:500'],
            'blood_group' => ['required', Rule::in(['unknown', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'emergency_contact' => ['required', 'string', 'max:120'],
            'previous_donation' => ['required', Rule::in(['yes', 'no'])],
            'health_notes' => ['nullable', 'string', 'max:1000'],
            'consent' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'date_of_birth' => __('bloodcare.public.registration.date_of_birth'),
            'phone_local' => __('bloodcare.public.registration.phone'),
            'phone' => __('bloodcare.public.registration.phone'),
            'identity_document_type' => __('bloodcare.public.registration.document_type'),
            'nrc_state' => __('bloodcare.public.registration.nrc_state'),
            'nrc_township' => __('bloodcare.public.registration.nrc_township'),
            'nrc_type' => __('bloodcare.public.registration.nrc_type'),
            'nrc_serial' => __('bloodcare.public.registration.nrc_serial'),
            'passport_number' => __('bloodcare.public.registration.passport_number'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function nrcReference(): array
    {
        return $this->nrcReference ??= MyanmarNrc::reference();
    }

    /**
     * Townships belonging to the submitted state, used both to constrain the
     * township rule and to render the NRC identity number afterwards.
     *
     * @return list<array{value: string, display: string, myanmarCode: string, myanmarName: string}>
     */
    public function stateTownships(): array
    {
        $reference = $this->nrcReference();

        return $reference['nrcTownships'][(string) $this->input('nrc_state', '')] ?? [];
    }
}
