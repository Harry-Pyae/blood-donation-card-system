<?php

use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\CentreController;
use App\Http\Controllers\Api\V1\DonationCardController;
use App\Http\Controllers\Api\V1\DonorRegistrationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API (v1)
|--------------------------------------------------------------------------
|
| Narrowly scoped, versioned endpoints for the separate Vue 3 + Vuetify
| public frontend. Laravel remains authoritative for validation,
| authorization, business rules, transactions and database access. Every
| endpoint returns only the fields the corresponding public screen requires.
|
| No authentication is applied: these are the same anonymous journeys the
| public Blade pages already expose. Rate limits are tightened on the
| endpoints that write data or that could be probed to enumerate donors.
|
*/

Route::prefix('v1')->group(function (): void {
    // Read-only reference data.
    Route::middleware('throttle:60,1')->group(function (): void {
        Route::get('/centres', [CentreController::class, 'index'])
            ->name('api.v1.centres.index');

        Route::get('/donors/nrc-reference', [DonorRegistrationController::class, 'reference'])
            ->name('api.v1.donors.nrc-reference');

        Route::get('/appointments/options', [AppointmentController::class, 'options'])
            ->name('api.v1.appointments.options');
    });

    // Writes.
    Route::middleware('throttle:10,1')->group(function (): void {
        Route::post('/donors/register', [DonorRegistrationController::class, 'store'])
            ->name('api.v1.donors.register');

        Route::post('/appointments', [AppointmentController::class, 'store'])
            ->name('api.v1.appointments.store');
    });

    // Privacy-sensitive lookups: throttled to blunt reference guessing.
    Route::middleware('throttle:20,1')->group(function (): void {
        Route::post('/appointments/check', [AppointmentController::class, 'check'])
            ->name('api.v1.appointments.check');

        Route::post('/cards/check', [DonationCardController::class, 'check'])
            ->name('api.v1.cards.check');
    });
});
