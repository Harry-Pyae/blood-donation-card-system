<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CentreResource;
use App\Models\DonationCentre;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CentreController extends Controller
{
    /**
     * List the active donation centres available to the public frontend.
     *
     * Mirrors the ordering already used by the public Blade home page so the
     * Vue centre selector presents centres in the same sequence donors
     * currently see.
     */
    public function index(): AnonymousResourceCollection
    {
        $centres = DonationCentre::query()
            ->where('is_active', true)
            ->orderBy('region')
            ->orderBy('township')
            ->orderBy('name')
            ->get();

        return CentreResource::collection($centres);
    }
}
