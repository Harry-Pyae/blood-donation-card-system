<?php

use App\Http\Controllers\Auth\StaffLoginController;
use App\Http\Controllers\Auth\StaffRegistrationController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use Backpack\CRUD\app\Http\Controllers\Auth\ForgotPasswordController;
use Backpack\CRUD\app\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\PublicSiteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HospitalPortalController;
use App\Http\Controllers\TraceabilityController;
use App\Http\Controllers\TwoFactorSecurityController;
use App\Http\Middleware\EnsureHospitalAccess;

Route::post('/language', function (Request $request) {
    $locale = (string) $request->input('locale', '');

    if (! in_array($locale, ['en', 'my'], true)) {
        return back();
    }

    $request->session()->put('locale', $locale);

    return back();
})->name('language.switch');

Route::get('/', [PublicSiteController::class, 'home'])->name('home');

Route::get('/two-factor/challenge', [TwoFactorChallengeController::class, 'show'])
    ->name('two-factor.challenge');
Route::post('/two-factor/challenge', [TwoFactorChallengeController::class, 'verify'])
    ->middleware('throttle:10,1')
    ->name('two-factor.challenge.verify');
Route::post('/two-factor/challenge/cancel', [TwoFactorChallengeController::class, 'cancel'])
    ->name('two-factor.challenge.cancel');

Route::prefix('security/two-factor')->middleware('auth:web')->group(function (): void {
    Route::get('/', [TwoFactorSecurityController::class, 'manage'])->name('two-factor.manage');
    Route::post('/setup', [TwoFactorSecurityController::class, 'startSetup'])
        ->middleware('throttle:5,1')
        ->name('two-factor.setup.start');
    Route::get('/setup', [TwoFactorSecurityController::class, 'setup'])->name('two-factor.setup');
    Route::post('/confirm', [TwoFactorSecurityController::class, 'confirm'])
        ->middleware('throttle:10,1')
        ->name('two-factor.confirm');
    Route::get('/recovery-codes', [TwoFactorSecurityController::class, 'recoveryCodes'])->name('two-factor.recovery-codes');
    Route::post('/recovery-codes', [TwoFactorSecurityController::class, 'regenerateRecoveryCodes'])
        ->middleware('throttle:5,1')
        ->name('two-factor.recovery-codes.regenerate');
    Route::delete('/', [TwoFactorSecurityController::class, 'disable'])
        ->middleware('throttle:5,1')
        ->name('two-factor.disable');
});

Route::get('/trace/card/{token}', [TraceabilityController::class, 'card'])
    ->where('token', '[A-Za-z0-9-]{8,100}')
    ->middleware('throttle:60,1')
    ->name('trace.card');
Route::get('/trace/blood-unit/{token}', [TraceabilityController::class, 'bloodUnit'])
    ->where('token', '[A-Za-z0-9-]{8,100}')
    ->middleware('throttle:60,1')
    ->name('trace.blood-unit');


Route::prefix(config('backpack.base.route_prefix', 'admin'))->group(function (): void {
    Route::get('login', [StaffLoginController::class, 'showLoginForm'])
        ->name('backpack.auth.login');
    Route::post('login', [StaffLoginController::class, 'login'])
        ->middleware('throttle:10,1');
    Route::get('logout', [StaffLoginController::class, 'logout'])
        ->name('backpack.auth.logout');
    Route::post('logout', [StaffLoginController::class, 'logout']);

    if (config('backpack.base.setup_password_recovery_routes', true)) {
        Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])
            ->name('backpack.auth.password.reset');
        Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])
            ->name('backpack.auth.password.email')
            ->middleware('throttle:3,10');
        Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])
            ->name('backpack.auth.password.reset.token');
        Route::post('password/reset', [ResetPasswordController::class, 'reset']);
    }
});


Route::get('/staff/register', [StaffRegistrationController::class, 'create'])
    ->name('bloodcare.staff.register');
Route::post('/staff/register', [StaffRegistrationController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('bloodcare.staff.register.store');

Route::get('/donor/register', [PublicSiteController::class, 'donorRegistration'])
    ->name('donor.register');
Route::post('/donor/register', [PublicSiteController::class, 'storeDonorRegistration'])
    ->name('donor.register.store');

Route::get('/appointments/book', [PublicSiteController::class, 'appointmentBooking'])
    ->name('appointments.book');
Route::post('/appointments/book', [PublicSiteController::class, 'storeAppointment'])
    ->name('appointments.book.store');
Route::get('/appointments/check', [PublicSiteController::class, 'appointmentLookup'])
    ->name('appointments.check');
Route::post('/appointments/check', [PublicSiteController::class, 'showAppointment'])
    ->name('appointments.check.show');

Route::get('/card/check', [PublicSiteController::class, 'cardLookup'])
    ->name('card.check');
Route::post('/card/check', [PublicSiteController::class, 'showCard'])
    ->name('card.check.show');

Route::get('/eligibility', [PublicSiteController::class, 'eligibility'])
    ->name('eligibility');
Route::get('/about', [PublicSiteController::class, 'about'])
    ->name('about');

Route::prefix('hospital')->group(function (): void {
    Route::get('login', [HospitalPortalController::class, 'loginForm'])->name('hospital.login');
    Route::post('login', [HospitalPortalController::class, 'login'])->middleware('throttle:10,1')->name('hospital.login.store');
    Route::middleware(['auth', EnsureHospitalAccess::class])->group(function (): void {
        Route::get('/', [HospitalPortalController::class, 'dashboard'])->name('hospital.dashboard');
        Route::post('logout', [HospitalPortalController::class, 'logout'])->name('hospital.logout');
        Route::post('requests', [HospitalPortalController::class, 'storeRequest'])->name('hospital.requests.store');
        Route::patch('notifications/read-all', [HospitalPortalController::class, 'markAllNotificationsRead'])->name('hospital.notifications.read-all');
        Route::patch('notifications/{notification}/read', [HospitalPortalController::class, 'markNotificationRead'])->name('hospital.notifications.read');
        Route::patch('allocations/{allocation}/receive', [HospitalPortalController::class, 'receive'])->name('hospital.allocations.receive');
        Route::patch('allocations/{allocation}/transfuse', [HospitalPortalController::class, 'transfuse'])->name('hospital.allocations.transfuse');
        Route::post('allocations/{allocation}/reactions', [HospitalPortalController::class, 'reaction'])->name('hospital.reactions.store');
    });
});
