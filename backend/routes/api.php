<?php

use App\Http\Controllers\Api\V1\CentreController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API (v1)
|--------------------------------------------------------------------------
|
| Narrowly scoped, versioned endpoints for the separate Vue 3 + Vuetify 3
| public frontend. Laravel remains authoritative for validation,
| authorization, business rules, and all database access. Every endpoint
| here must return only fields the public screen genuinely requires.
|
*/

Route::prefix('v1')
    ->middleware('throttle:60,1')
    ->group(function (): void {
        Route::get('/centres', [CentreController::class, 'index'])
            ->name('api.v1.centres.index');
    });
