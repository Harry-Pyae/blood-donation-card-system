<?php

namespace App\Actions\Public;

use App\Models\ActivityLog;
use App\Models\Donor;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creates a public donor registration.
 *
 * Extracted verbatim from PublicSiteController so the Blade flow and the API
 * share one implementation of identity assembly, duplicate detection, phone
 * canonicalisation, default state and activity logging.
 */
class RegisterDonorAction
{
    /**
     * @param  array<string, mixed>  $data  Validated registration data.
     * @param  list<array{value: string, display: string, myanmarCode: string, myanmarName: string}>  $stateTownships
     *
     * @throws ValidationException when the identity number is already registered.
     */
    public function handle(array $data, array $stateTownships): Donor
    {
        $data['phone'] = '+95 '.substr(Donor::normalizePhone($data['phone']), 2);
        $data['identity_number'] = $this->identityNumber($data, $stateTownships);

        if (Donor::where('identity_number', $data['identity_number'])->exists()) {
            throw ValidationException::withMessages([
                'identity_document_type' => __('bloodcare.public.registration.identity_exists'),
            ]);
        }

        return DB::transaction(function () use ($data): Donor {
            $donor = Donor::create([
                ...$data,
                'reference' => Donor::generateReference(),
                'phone_normalized' => Donor::normalizePhone($data['phone']),
                'previous_donation' => $data['previous_donation'] === 'yes',
                'consent_at' => now(),
                'status' => 'pending',
                'eligibility_status' => 'review',
            ]);

            ActivityLog::record([
                'type' => 'Donor',
                'action' => 'Public donor registration submitted',
                'subject_type' => Donor::class,
                'subject_id' => $donor->id,
                'donor_id' => $donor->id,
                'result' => 'Pending',
                'details' => 'A new donor registration is awaiting staff review.',
                'source' => 'public-registration',
            ]);

            return $donor;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array{value: string, display: string, myanmarCode: string, myanmarName: string}>  $stateTownships
     */
    private function identityNumber(array $data, array $stateTownships): string
    {
        if ($data['identity_document_type'] !== 'nrc') {
            return strtoupper($data['passport_number']);
        }

        $selectedTownship = collect($stateTownships)->firstWhere('value', $data['nrc_township']);

        return sprintf(
            '%s/%s(%s)%s',
            $data['nrc_state'],
            $selectedTownship['display'],
            $data['nrc_type'],
            $data['nrc_serial'],
        );
    }
}
