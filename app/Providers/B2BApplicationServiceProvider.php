<?php

declare(strict_types=1);

namespace App\Providers;

use App\Console\Commands\InstallB2BApplicationPermissions;
use App\Console\Commands\VerifyB2BApplication;
use App\Http\Middleware\RedirectBusinessAccountIntent;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class B2BApplicationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(config_path('b2b_application.php'), 'b2b_application');
        $this->mergeConfigFrom(
            config_path('b2b_application_corrective.php'),
            'b2b_application_corrective',
        );
    }

    public function boot(Router $router): void
    {
        $router->pushMiddlewareToGroup('web', RedirectBusinessAccountIntent::class);

        if (! $this->app->routesAreCached()) {
            Route::middleware('web')->group(base_path('routes/b2b_application.php'));
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallB2BApplicationPermissions::class,
                VerifyB2BApplication::class,
            ]);
        }
    }
}
