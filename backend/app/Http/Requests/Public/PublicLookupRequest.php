<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared validation for privacy-safe public lookups.
 *
 * A reference alone must never reveal anything, so the phone number is always
 * required alongside it.
 */
class PublicLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'max:40'],
            'phone' => ['required', 'string', 'max:30'],
        ];
    }
}
