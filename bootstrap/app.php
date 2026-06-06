<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
             'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
             'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
             'localize'              => LaravelLocalizationRoutes::class,
            'localizationRedirect'  => LaravelLocalizationRedirectFilter::class,
            'localeSessionRedirect' => LocaleSessionRedirect::class,
            'localeCookieRedirect'  => LocaleCookieRedirect::class,
            'localeViewPath'        => LaravelLocalizationViewPath::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, \Illuminate\Http\Request $request) {
            $user = $request->user();

            if ($user?->hasRole('DeliveryAgent') && \Illuminate\Support\Facades\Route::has('delivery.index')) {
                return redirect()
                    ->route('delivery.index')
                    ->with('status', 'Delivery agents can access only their assigned delivery dashboard.');
            }

            return null;
        });
    })->create();

    return [
    // ...

    'providers' => ServiceProvider::defaultProviders()->merge([
        // ...
        Milon\Barcode\BarcodeServiceProvider::class,
    ])->toArray(),

    'aliases' => Facade::defaultAliases()->merge([
        // ...
        'DNS1D' => Milon\Barcode\Facades\DNS1DFacade::class,
        'DNS2D' => Milon\Barcode\Facades\DNS2DFacade::class,
    ])->toArray(),
];