<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Public\BookAppointmentAction;
use App\Actions\Public\LookupPublicRecordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\PublicLookupRequest;
use App\Http\Requests\Public\StoreAppointmentRequest;
use App\Http\Resources\AppointmentConfirmationResource;
use App\Http\Resources\AppointmentStatusResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    /**
     * Booking options the form needs, kept in one place so the frontend never
     * hardcodes clinical or scheduling limits of its own.
     */
    public function options(): JsonResponse
    {
        return response()->json([
            'data' => [
                'times' => StoreAppointmentRequest::TIMES,
                'request_location_value' => StoreAppointmentRequest::REQUEST_LOCATION,
                'min_date' => now()->toDateString(),
                'max_date' => now()->addMonths(3)->toDateString(),
            ],
        ]);
    }

    public function store(
        StoreAppointmentRequest $request,
        BookAppointmentAction $bookAppointment,
    ): JsonResponse {
        ['appointment' => $appointment] = $bookAppointment->handle($request->validated());

        return (new AppointmentConfirmationResource($appointment))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Privacy-safe status lookup requiring reference and phone together.
     */
    public function check(
        PublicLookupRequest $request,
        LookupPublicRecordAction $lookup,
    ): AppointmentStatusResource {
        $data = $request->validated();
        $appointment = $lookup->appointment($data['reference'], $data['phone']);

        if (! $appointment) {
            // One generic message for both "unknown reference" and "wrong
            // phone", so responses cannot be used to enumerate donors.
            throw ValidationException::withMessages([
                'reference' => __('bloodcare.public.lookup.record_not_found'),
            ]);
        }

        return new AppointmentStatusResource($appointment);
    }
}
