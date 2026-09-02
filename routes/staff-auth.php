<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Auth\StaffAuthenticatedSessionController;
use App\Http\Controllers\Auth\StaffImpersonationBridgeController;
use App\Http\Middleware\RedirectIfStaffAuthenticated;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware(RedirectIfStaffAuthenticated::class)->group(function (): void {
        Route::get('/login', [StaffAuthenticatedSessionController::class, 'create'])
            ->name('login');

        Route::post('/login', [StaffAuthenticatedSessionController::class, 'store'])
            ->middleware('throttle:staff-login')
            ->name('login.store');
    });

    Route::post('/logout', [StaffAuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth:staff')
        ->name('logout');
});

Route::prefix('staff-impersonation')
    ->name('staff-impersonation.')
    ->group(function (): void {
        Route::get('/accept/{token}', [StaffImpersonationBridgeController::class, 'accept'])
            ->middleware(['signed', 'throttle:10,1'])
            ->name('accept');

        /*
         * GET is retained for compatibility with existing impersonation
         * banners. POST is available for new forms.
         */
        Route::match(['GET', 'POST'], '/leave', [StaffImpersonationBridgeController::class, 'leave'])
            ->middleware('throttle:20,1')
            ->name('leave');

        Route::get('/finish', [StaffImpersonationBridgeController::class, 'finish'])
            ->middleware(['signed', 'throttle:20,1'])
            ->name('finish');
    });
