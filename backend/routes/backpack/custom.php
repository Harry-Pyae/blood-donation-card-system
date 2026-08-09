<?php

use App\Http\Controllers\Admin\AppointmentManagementController;
use App\Http\Controllers\Admin\CardManagementController;
use App\Http\Controllers\Admin\DonationCentreManagementController;
use App\Http\Controllers\Admin\DonationManagementController;
use App\Http\Controllers\Admin\InventoryManagementController;
use App\Http\Controllers\Admin\DonorManagementController;
use App\Http\Controllers\Admin\DonorScreeningController;
use App\Http\Controllers\Admin\ModulePreviewController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\LaboratoryController;
use App\Http\Controllers\Admin\BloodComponentController;
use App\Http\Controllers\Admin\HospitalManagementController;
use App\Http\Controllers\Admin\BloodRequestManagementController;
use App\Http\Controllers\Admin\AccountProfileController;
use App\Http\Controllers\Admin\LabDashboardController;
use App\Http\Controllers\Admin\NotificationCentreController;
use App\Http\Controllers\Admin\HaemovigilanceController;
use App\Http\Middleware\EnsureSystemAdministrator;
use App\Http\Middleware\EnsureLaboratoryAccess;
use App\Http\Middleware\EnsureBloodBankAccess;
use Illuminate\Support\Facades\Route;

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\CRUD.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::get('edit-account-info', [AccountProfileController::class, 'edit'])
        ->name('backpack.account.info');
    Route::post('edit-account-info', [AccountProfileController::class, 'update'])
        ->name('backpack.account.info.store');
    Route::post('change-password', [AccountProfileController::class, 'changePassword'])
        ->name('backpack.account.password');
    Route::get('notifications', [NotificationCentreController::class, 'index'])
        ->name('bloodcare.admin.notifications');
    Route::get('notifications/unread-count', [NotificationCentreController::class, 'unreadCount'])
        ->name('bloodcare.admin.notifications.unread-count');
    Route::patch('notifications/read-all', [NotificationCentreController::class, 'markAllRead'])
        ->name('bloodcare.admin.notifications.read-all');
    Route::patch('notifications/{notification}/read', [NotificationCentreController::class, 'markRead'])
        ->name('bloodcare.admin.notifications.read');
    Route::patch('notifications/{notification}/open', [NotificationCentreController::class, 'open'])
        ->name('bloodcare.admin.notifications.open');

    Route::get('donors', [ModulePreviewController::class, 'donors'])
        ->name('bloodcare.admin.donors');
    Route::get('donors/register', [DonorManagementController::class, 'create'])
        ->name('bloodcare.admin.donors.create');
    Route::post('donors', [DonorManagementController::class, 'store'])
        ->name('bloodcare.admin.donors.store');
    Route::get('donors/{donor}/edit', [DonorManagementController::class, 'edit'])
        ->name('bloodcare.admin.donors.edit');
    Route::get('donors/{donor}', [DonorManagementController::class, 'show'])
        ->name('bloodcare.admin.donors.show');
    Route::put('donors/{donor}', [DonorManagementController::class, 'update'])
        ->name('bloodcare.admin.donors.update');
    Route::post('donors/{donor}/screenings', [DonorScreeningController::class, 'store'])
        ->name('bloodcare.admin.donors.screenings.store');
    Route::get('donors/{donor}/screenings/{screening}/edit', [DonorScreeningController::class, 'edit'])
        ->name('bloodcare.admin.donors.screenings.edit');
    Route::put('donors/{donor}/screenings/{screening}', [DonorScreeningController::class, 'update'])
        ->name('bloodcare.admin.donors.screenings.update');
    Route::delete('donors/{donor}/screenings/{screening}', [DonorScreeningController::class, 'destroy'])
        ->name('bloodcare.admin.donors.screenings.destroy');
    Route::get('cards', [ModulePreviewController::class, 'cards'])
        ->name('bloodcare.admin.cards');
    Route::get('cards/donors/search', [CardManagementController::class, 'searchDonors'])
        ->name('bloodcare.admin.cards.donors.search');
    Route::post('cards', [CardManagementController::class, 'store'])
        ->name('bloodcare.admin.cards.store');
    Route::put('cards/{card}', [CardManagementController::class, 'update'])
        ->name('bloodcare.admin.cards.update');
    Route::get('donations', [ModulePreviewController::class, 'donations'])
        ->name('bloodcare.admin.donations');
    Route::post('donations', [DonationManagementController::class, 'store'])
        ->name('bloodcare.admin.donations.store');
    Route::put('donations/{donation}', [DonationManagementController::class, 'update'])
        ->name('bloodcare.admin.donations.update');
    Route::get('donations/{donation}/edit', [DonationManagementController::class, 'edit'])
        ->name('bloodcare.admin.donations.edit');
    Route::delete('donations/{donation}', [DonationManagementController::class, 'destroy'])
        ->name('bloodcare.admin.donations.destroy');
    Route::patch('donations/{donation}/status', [DonationManagementController::class, 'updateStatus'])
        ->name('bloodcare.admin.donations.status');
    Route::get('inventory', [ModulePreviewController::class, 'inventory'])
        ->name('bloodcare.admin.inventory');
    Route::get('inventory/{unit}/details', [InventoryManagementController::class, 'details'])
        ->name('bloodcare.admin.inventory.details');
    Route::post('inventory', [InventoryManagementController::class, 'store'])
        ->name('bloodcare.admin.inventory.store');
    Route::put('inventory/{unit}', [InventoryManagementController::class, 'update'])
        ->name('bloodcare.admin.inventory.update');
    Route::patch('inventory/{unit}/status', [InventoryManagementController::class, 'updateStatus'])
        ->name('bloodcare.admin.inventory.status');
    Route::get('appointments', [ModulePreviewController::class, 'appointments'])
        ->name('bloodcare.admin.appointments');
    Route::post('appointments', [AppointmentManagementController::class, 'store'])
        ->name('bloodcare.admin.appointments.store');
    Route::put('appointments/{appointment}', [AppointmentManagementController::class, 'update'])
        ->name('bloodcare.admin.appointments.update');
    Route::patch('appointments/{appointment}/status', [AppointmentManagementController::class, 'updateStatus'])
        ->name('bloodcare.admin.appointments.status');
    Route::post('centres', [DonationCentreManagementController::class, 'store'])
        ->name('bloodcare.admin.centres.store');
    Route::put('centres/{centre}', [DonationCentreManagementController::class, 'update'])
        ->name('bloodcare.admin.centres.update');
    Route::patch('centres/{centre}/status', [DonationCentreManagementController::class, 'updateStatus'])
        ->name('bloodcare.admin.centres.status');
    Route::get('history', [ModulePreviewController::class, 'history'])
        ->name('bloodcare.admin.history');
    Route::get('users', [ModulePreviewController::class, 'users'])
        ->middleware(EnsureSystemAdministrator::class)
        ->name('bloodcare.admin.users');
    Route::patch('users/{user}/role', [UserManagementController::class, 'updateRole'])
        ->middleware(EnsureSystemAdministrator::class)
        ->name('bloodcare.admin.users.role');
    Route::patch('users/{user}/status', [UserManagementController::class, 'updateStatus'])
        ->middleware(EnsureSystemAdministrator::class)
        ->name('bloodcare.admin.users.status');
    Route::patch('users/{user}/approval', [UserManagementController::class, 'updateApproval'])
        ->middleware(EnsureSystemAdministrator::class)
        ->name('bloodcare.admin.users.approval');
    Route::get('reports', [ModulePreviewController::class, 'reports'])
        ->name('bloodcare.admin.reports');
    Route::prefix('lab')->middleware(EnsureLaboratoryAccess::class)->group(function (): void {
        Route::get('dashboard', [LabDashboardController::class, 'index'])
            ->name('bloodcare.lab.dashboard');
        Route::get('laboratory', [LaboratoryController::class, 'index'])
            ->name('bloodcare.lab.laboratory');
        Route::post('laboratory/{donation}', [LaboratoryController::class, 'store'])
            ->name('bloodcare.lab.laboratory.store');
        Route::get('components', [BloodComponentController::class, 'index'])
            ->name('bloodcare.lab.components');
        Route::post('components/{unit}', [BloodComponentController::class, 'store'])
            ->name('bloodcare.lab.components.store');
        Route::get('inventory', [ModulePreviewController::class, 'inventory'])
            ->name('bloodcare.lab.inventory');
        Route::get('inventory/{unit}/details', [InventoryManagementController::class, 'details'])
            ->name('bloodcare.lab.inventory.details');
        Route::post('inventory', [InventoryManagementController::class, 'store'])
            ->name('bloodcare.lab.inventory.store');
        Route::put('inventory/{unit}', [InventoryManagementController::class, 'update'])
            ->name('bloodcare.lab.inventory.update');
        Route::patch('inventory/{unit}/status', [InventoryManagementController::class, 'updateStatus'])
            ->name('bloodcare.lab.inventory.status');
        Route::get('history', [LabDashboardController::class, 'history'])
            ->name('bloodcare.lab.history');
        Route::get('notifications', [NotificationCentreController::class, 'index'])
            ->name('bloodcare.lab.notifications');
        Route::get('notifications/unread-count', [NotificationCentreController::class, 'unreadCount'])
            ->name('bloodcare.lab.notifications.unread-count');
        Route::patch('notifications/read-all', [NotificationCentreController::class, 'markAllRead'])
            ->name('bloodcare.lab.notifications.read-all');
        Route::patch('notifications/{notification}/read', [NotificationCentreController::class, 'markRead'])
            ->name('bloodcare.lab.notifications.read');
        Route::patch('notifications/{notification}/open', [NotificationCentreController::class, 'open'])
            ->name('bloodcare.lab.notifications.open');
    });
    Route::get('hospitals', [HospitalManagementController::class, 'index'])
        ->middleware(EnsureSystemAdministrator::class)->name('bloodcare.admin.hospitals');
    Route::post('hospitals', [HospitalManagementController::class, 'store'])
        ->middleware(EnsureSystemAdministrator::class)->name('bloodcare.admin.hospitals.store');
    Route::patch('hospitals/{hospital}', [HospitalManagementController::class, 'update'])
        ->middleware(EnsureSystemAdministrator::class)->name('bloodcare.admin.hospitals.update');
    Route::delete('hospitals/{hospital}', [HospitalManagementController::class, 'destroy'])
        ->middleware(EnsureSystemAdministrator::class)->name('bloodcare.admin.hospitals.destroy');
    Route::get('blood-requests', [BloodRequestManagementController::class, 'index'])->middleware(EnsureBloodBankAccess::class)->name('bloodcare.admin.blood-requests');
    Route::patch('blood-requests/{bloodRequest}/review', [BloodRequestManagementController::class, 'review'])->middleware(EnsureBloodBankAccess::class)->name('bloodcare.admin.blood-requests.review');
    Route::post('blood-requests/{bloodRequest}/allocate', [BloodRequestManagementController::class, 'allocate'])->middleware(EnsureBloodBankAccess::class)->name('bloodcare.admin.blood-requests.allocate');
    Route::patch('allocations/{allocation}/dispatch', [BloodRequestManagementController::class, 'dispatch'])->middleware(EnsureBloodBankAccess::class)->name('bloodcare.admin.allocations.dispatch');
    Route::get('haemovigilance', [HaemovigilanceController::class, 'index'])->middleware(EnsureBloodBankAccess::class)->name('bloodcare.admin.haemovigilance');
    Route::get('haemovigilance/{reaction}', [HaemovigilanceController::class, 'show'])->middleware(EnsureBloodBankAccess::class)->name('bloodcare.admin.haemovigilance.show');
    Route::patch('haemovigilance/{reaction}', [HaemovigilanceController::class, 'update'])->middleware(EnsureBloodBankAccess::class)->name('bloodcare.admin.haemovigilance.update');
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
