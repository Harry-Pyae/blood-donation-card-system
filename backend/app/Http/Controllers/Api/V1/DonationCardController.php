<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Public\LookupPublicRecordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\PublicLookupRequest;
use App\Http\Resources\DonationCardResource;
use Illuminate\Validation\ValidationException;

class DonationCardController extends Controller
{
    /**
     * Privacy-safe donation card lookup requiring card number and phone.
     */
    public function check(
        PublicLookupRequest $request,
        LookupPublicRecordAction $lookup,
    ): DonationCardResource {
        $data = $request->validated();
        $card = $lookup->card($data['reference'], $data['phone']);

        if (! $card) {
            throw ValidationException::withMessages([
                'reference' => __('bloodcare.public.lookup.record_not_found'),
            ]);
        }

        return new DonationCardResource($card);
    }
}
