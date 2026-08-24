<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\Auth\StaffImpersonationBridgeController;
use App\Http\Middleware\EnforceAuthenticationBoundary;
use App\Http\Middleware\SelectAuthenticationContext;
use App\Support\Authentication\AccountTypeResolver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

final class StaffAuthenticationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AccountTypeResolver::class);

        /*
         * Capture the existing customer session values before the route-aware
         * middleware starts switching config for individual requests.
         */
        $this->app->instance('bandara.customer-session-config', [
            'cookie' => config('session.cookie'),
            'path' => config('session.path'),
            'domain' => config('session.domain'),
            'secure' => config('session.secure'),
            'http_only' => config('session.http_only'),
            'same_site' => config('session.same_site'),
        ]);

        $guardName = (string) config('staff-auth.staff_guard', 'staff');
        $webProvider = (string) config('auth.guards.web.provider', 'users');
        $configuredGuard = (array) config('staff-auth.guard', []);

        config([
            "auth.guards.{$guardName}" => array_replace(
                [
                    'driver' => 'session',
                    'provider' => $webProvider,
                ],
                $configuredGuard,
                ['provider' => $configuredGuard['provider'] ?? $webProvider]
            ),
        ]);
    }

    public function boot(Router $router): void
    {
        /*
         * Select /admin requests globally as a fallback for any nonstandard
         * admin route group. The web-group copy below runs later with full
         * route metadata and refines shared attachment/impersonation routes.
         */
        $kernel = $this->app->make(HttpKernel::class);

        if (method_exists($kernel, 'prependMiddleware')) {
            $kernel->prependMiddleware(SelectAuthenticationContext::class);
        }

        /*
         * Selection must happen before EncryptCookies / StartSession. Boundary
         * enforcement must happen after StartSession but before route-specific
         * auth/role middleware.
         */
        $router->prependMiddlewareToGroup('web', SelectAuthenticationContext::class);
        $router->pushMiddlewareToGroup('web', EnforceAuthenticationBoundary::class);

        RateLimiter::for('staff-login', function (Request $request): Limit {
            $identity = 'unknown';

            foreach (array_merge(
                Arr::wrap(config('staff-auth.login_columns', ['email'])),
                ['login']
            ) as $field) {
                $value = trim((string) $request->input((string) $field, ''));

                if ($value !== '') {
                    $identity = Str::lower($value);
                    break;
                }
            }

            return Limit::perMinute(5)->by($identity.'|'.$request->ip());
        });

        /*
         * RouteMatched runs before the route middleware pipeline. It lets us
         * adapt only the matched legacy route, avoiding a broad routes-file
         * rewrite and retaining its existing authorization middleware.
         */
        Event::listen(RouteMatched::class, function (RouteMatched $event): void {
            $this->normaliseMatchedRoute($event->route);
        });
    }

    private function normaliseMatchedRoute(Route $route): void
    {
        $staffRoute = $this->isStaffRoute($route);
        $action = $route->getAction();
        $changed = false;

        if ($staffRoute) {
            $middleware = Arr::wrap($action['middleware'] ?? []);

            $middleware = array_map(function (mixed $item) use (&$changed): mixed {
                if (! is_string($item)) {
                    return $item;
                }

                $replacement = match (true) {
                    $item === 'auth:web' => 'auth:staff',
                    $item === 'guest:web' => 'guest:staff',
                    (bool) preg_match('/^(role|permission):(.+),web$/', $item) =>
                        preg_replace('/,web$/', ',staff', $item),
                    default => $item,
                };

                $changed = $changed || $replacement !== $item;

                return $replacement;
            }, $middleware);

            if ($changed) {
                $action['middleware'] = $middleware;
            }
        }

        if ($this->shouldBridgeImpersonationRoute($route)) {
            $method = $this->isImpersonationLeaveRoute($route) ? 'leave' : 'start';
            $controller = StaffImpersonationBridgeController::class.'@'.$method;

            $action['uses'] = $controller;
            $action['controller'] = $controller;
            $changed = true;
        }

        if ($changed) {
            $route->setAction($action);
        }
    }

    private function isStaffRoute(Route $route): bool
    {
        $name = (string) ($route->getName() ?? '');
        $uri = ltrim((string) $route->uri(), '/');
        $action = (string) $route->getActionName();

        foreach (Arr::wrap(config('staff-auth.staff_route_name_prefixes', ['admin.'])) as $prefix) {
            if ($prefix !== '' && str_starts_with($name, (string) $prefix)) {
                return true;
            }
        }

        foreach (Arr::wrap(config('staff-auth.staff_path_prefixes', ['admin'])) as $prefix) {
            $prefix = trim((string) $prefix, '/');

            if ($prefix !== '' && ($uri === $prefix || str_starts_with($uri, "{$prefix}/"))) {
                return true;
            }
        }

        return str_contains($action, '\\Admin\\');
    }

    private function shouldBridgeImpersonationRoute(Route $route): bool
    {
        if (! (bool) config('staff-auth.impersonation.enabled', true)) {
            return false;
        }

        $action = (string) $route->getActionName();

        if (str_contains($action, StaffImpersonationBridgeController::class)) {
            return false;
        }

        $descriptor = Str::lower(implode(' ', [
            (string) ($route->getName() ?? ''),
            (string) $route->uri(),
            $action,
        ]));

        $looksRelated = false;

        foreach (['impersonat', 'masquerad', 'login-as', 'login_as', 'loginas', 'act-as', 'act_as'] as $token) {
            if (str_contains($descriptor, $token)) {
                $looksRelated = true;
                break;
            }
        }

        if (! $looksRelated) {
            return false;
        }

        if ($this->isImpersonationLeaveRoute($route)) {
            return true;
        }

        /*
         * A start/take route should identify its target either in the URI or
         * in a non-GET request body. This avoids rewriting an impersonation
         * settings/index page that happens to share the word.
         */
        return str_contains((string) $route->uri(), '{')
            || array_diff($route->methods(), ['GET', 'HEAD']) !== [];
    }

    private function isImpersonationLeaveRoute(Route $route): bool
    {
        $descriptor = Str::lower(implode(' ', [
            (string) ($route->getName() ?? ''),
            (string) $route->uri(),
            (string) $route->getActionName(),
        ]));

        foreach (['leave', 'stop', 'exit', 'revert', 'restore', 'destroy'] as $token) {
            if (str_contains($descriptor, $token)) {
                return true;
            }
        }

        return false;
    }
}
