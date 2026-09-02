<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\B2BApplicationController as AdminB2BApplicationController;
use App\Http\Controllers\BusinessAccountEntryController;
use App\Http\Controllers\BusinessAccountLandingController;
use App\Http\Controllers\BusinessAccountRegistrationController;
use App\Http\Controllers\Customer\B2BApplicationController as CustomerB2BApplicationController;
use Illuminate\Support\Facades\Route;

/* Public information and dedicated business entry points. */
Route::get('/business-account', BusinessAccountLandingController::class)
    ->name('business-account.index');
Route::get('/business/login', [BusinessAccountEntryController::class, 'login'])
    ->name('business-account.login');

/*
 | New businesses use a dedicated registration form. It creates the normal
 | customer login and a draft B2B application, but never asks for DOB and
 | never grants B2B access before approval.
 */
Route::get('/business/register', [BusinessAccountRegistrationController::class, 'create'])
    ->name('business-account.register');
Route::post('/business/register', [BusinessAccountRegistrationController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('business-account.register.store');
Route::get('/business/register/cities', [BusinessAccountRegistrationController::class, 'cities'])
    ->middleware('throttle:60,1')
    ->name('business-account.register.cities');

Route::middleware('auth')->group(function (): void {
    Route::get('/business/continue', [BusinessAccountEntryController::class, 'resume'])
        ->name('business-account.continue');

    Route::prefix('account/business-application')
        ->name('account.business-application.')
        ->group(function (): void {
            Route::get('/', [CustomerB2BApplicationController::class, 'show'])->name('show');
            Route::get('/business-details', [CustomerB2BApplicationController::class, 'stepOne'])->name('step-one');
            Route::post('/business-details', [CustomerB2BApplicationController::class, 'saveStepOne'])
                ->middleware('throttle:20,1')
                ->name('step-one.save');
            Route::get('/requirements', [CustomerB2BApplicationController::class, 'stepTwo'])->name('step-two');
            Route::post('/requirements', [CustomerB2BApplicationController::class, 'saveStepTwo'])
                ->middleware('throttle:10,1')
                ->name('step-two.save');
            Route::get('/cities', [CustomerB2BApplicationController::class, 'cities'])
                ->middleware('throttle:60,1')
                ->name('cities');
            Route::post('/withdraw', [CustomerB2BApplicationController::class, 'withdraw'])->name('withdraw');
            Route::post('/restart', [CustomerB2BApplicationController::class, 'restart'])->name('restart');
        });

    Route::prefix('admin/b2b-applications')
        ->name('admin.b2b-applications.')
        ->group(function (): void {
            Route::get('/', [AdminB2BApplicationController::class, 'index'])->name('index');
            Route::get('/{b2bApplication}', [AdminB2BApplicationController::class, 'show'])->name('show');
            Route::post('/{b2bApplication}/assign', [AdminB2BApplicationController::class, 'assign'])->name('assign');
            Route::post('/{b2bApplication}/start-review', [AdminB2BApplicationController::class, 'startReview'])->name('start-review');
            Route::post('/{b2bApplication}/request-information', [AdminB2BApplicationController::class, 'requestInformation'])->name('request-information');
            Route::post('/{b2bApplication}/notes', [AdminB2BApplicationController::class, 'note'])->name('note');
            Route::post('/{b2bApplication}/approve', [AdminB2BApplicationController::class, 'approve'])->name('approve');
            Route::post('/{b2bApplication}/reject', [AdminB2BApplicationController::class, 'reject'])->name('reject');
        });
});
