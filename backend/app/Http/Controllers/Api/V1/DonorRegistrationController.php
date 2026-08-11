<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Public\RegisterDonorAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreDonorRegistrationRequest;
use App\Http\Resources\DonorRegistrationResource;
use App\Http\Resources\NrcReferenceResource;
use App\Support\MyanmarNrc;
use Illuminate\Http\JsonResponse;

class DonorRegistrationController extends Controller
{
    /**
     * Reference data the registration form needs to build its NRC selects.
     */
    public function reference(): NrcReferenceResource
    {
        return new NrcReferenceResource(MyanmarNrc::reference());
    }

    /**
     * Register a public donor.
     *
     * Validation, normalisation and creation are the same shared Form Request
     * and action the Blade flow uses, so the two cannot diverge.
     */
    public function store(
        StoreDonorRegistrationRequest $request,
        RegisterDonorAction $registerDonor,
    ): JsonResponse {
        $donor = $registerDonor->handle($request->validated(), $request->stateTownships());

        return (new DonorRegistrationResource($donor))
            ->response()
            ->setStatusCode(201);
    }
}
