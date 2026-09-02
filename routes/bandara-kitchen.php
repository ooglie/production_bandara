<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Kitchen\ChefController as AdminChefController;
use App\Http\Controllers\Storefront\ChefController as StorefrontChefController;
use App\Http\Controllers\Storefront\KitchenController;
use App\Http\Middleware\BandaraKitchenAdminAccess;
use Illuminate\Support\Facades\Route;

Route::prefix('kitchen')->name('kitchen.')->group(function (): void {
    Route::get('/', [KitchenController::class, 'index'])->name('index');
    Route::get('/chefs', [StorefrontChefController::class, 'index'])->name('chefs.index');
    Route::get('/chefs/{chef:slug}', [StorefrontChefController::class, 'show'])->name('chefs.show');
});

Route::prefix('admin/kitchen')
    ->name('admin.kitchen.')
    ->middleware(['auth:staff', BandaraKitchenAdminAccess::class])
    ->group(function (): void {
        Route::delete('/featured-chef', [AdminChefController::class, 'unfeature'])
            ->name('chefs.unfeature');
        Route::patch('/chefs/{chef}/feature', [AdminChefController::class, 'feature'])
            ->name('chefs.feature');
        Route::resource('chefs', AdminChefController::class)->except(['show']);
    });
