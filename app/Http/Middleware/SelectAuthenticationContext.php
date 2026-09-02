<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class SelectAuthenticationContext
{
    public const ATTRIBUTE = 'bandara.authentication_context';

    public function handle(Request $request, Closure $next): Response
    {
        $context = $this->isStaffRequest($request) ? 'staff' : 'customer';

        $request->attributes->set(self::ATTRIBUTE, $context);

        if ($context === 'staff') {
            $this->selectStaffSession();
            Auth::shouldUse((string) config('staff-auth.staff_guard', 'staff'));
        } else {
            $this->selectCustomerSession();
            Auth::shouldUse((string) config('staff-auth.customer_guard', 'web'));
        }

        return $next($request);
    }

    public function isStaffRequest(Request $request): bool
    {
        $route = $request->route();
        $name = $route instanceof Route ? (string) ($route->getName() ?? '') : '';
        $uri = $route instanceof Route ? ltrim((string) $route->uri(), '/') : ltrim($request->path(), '/');
        $action = $route instanceof Route ? (string) $route->getActionName() : '';
        $descriptor = Str::lower(implode(' ', [$name, $uri, $action]));

        /*
         * These bridge routes must always use the customer session even when
         * the browser also carries a valid staff cookie.
         */
        if (
            str_starts_with($name, 'staff-impersonation.')
            || str_starts_with($uri, 'staff-impersonation/')
        ) {
            return false;
        }

        $host = Str::lower($request->getHost());
        $staffDomain = Str::lower((string) config('staff-auth.session.domain', ''));

        if ($staffDomain !== '' && ltrim($staffDomain, '.') === $host) {
            return true;
        }

        foreach (Arr::wrap(config('staff-auth.staff_path_prefixes', ['admin'])) as $prefix) {
            $prefix = trim((string) $prefix, '/');

            if ($prefix !== '' && ($uri === $prefix || str_starts_with($uri, "{$prefix}/"))) {
                return true;
            }
        }

        foreach (Arr::wrap(config('staff-auth.staff_route_name_prefixes', ['admin.'])) as $prefix) {
            if ($prefix !== '' && str_starts_with($name, (string) $prefix)) {
                return true;
            }
        }

        if (str_contains($action, '\\Admin\\')) {
            return true;
        }

        if ($route instanceof Route) {
            try {
                foreach ($route->gatherMiddleware() as $middleware) {
                    $middleware = Str::lower(is_string($middleware) ? $middleware : get_debug_type($middleware));

                    if (
                        $middleware === 'auth:staff'
                        || $middleware === 'guest:staff'
                        || str_starts_with($middleware, 'auth:staff,')
                    ) {
                        return true;
                    }
                }
            } catch (Throwable) {
                // Route metadata is advisory; the path/name checks remain.
            }
        }

        $staffCookiePresent = $this->requestCarriesStaffCookie($request);

        if ($staffCookiePresent) {
            foreach (Arr::wrap(config('staff-auth.shared_staff_route_tokens', [])) as $token) {
                if ($token !== '' && str_contains($descriptor, Str::lower((string) $token))) {
                    return true;
                }
            }

            /*
             * Legacy impersonation "start/take" routes can be outside /admin.
             * Leave/stop routes deliberately remain in the customer context.
             */
            if ($this->looksLikeImpersonationRoute($descriptor)) {
                if ($this->looksLikeImpersonationLeaveRoute($descriptor)) {
                    return false;
                }

                return true;
            }

            /*
             * Some existing admin layouts post to the shared /logout route.
             * The Referer plus the separate staff cookie makes that request
             * unambiguous without changing the existing Blade form.
             */
            if ($this->looksLikeSharedLogout($request, $name, $uri)) {
                if ($this->refererIsStaffArea($request) || ! $this->requestCarriesCustomerCookie($request)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function selectStaffSession(): void
    {
        $staff = (array) config('staff-auth.session', []);

        config([
            'session.cookie' => $staff['cookie'] ?? 'bandara_staff_session',
            'session.path' => $staff['path'] ?? '/',
            'session.domain' => $staff['domain'] ?? null,
            'session.secure' => $staff['secure'] ?? config('session.secure'),
            'session.http_only' => $staff['http_only'] ?? true,
            'session.same_site' => $staff['same_site'] ?? config('session.same_site', 'lax'),
        ]);
    }

    private function selectCustomerSession(): void
    {
        $customer = app()->bound('bandara.customer-session-config')
            ? (array) app('bandara.customer-session-config')
            : [];

        if ($customer !== []) {
            config([
                'session.cookie' => $customer['cookie'] ?? config('session.cookie'),
                'session.path' => $customer['path'] ?? '/',
                'session.domain' => $customer['domain'] ?? null,
                'session.secure' => $customer['secure'] ?? null,
                'session.http_only' => $customer['http_only'] ?? true,
                'session.same_site' => $customer['same_site'] ?? 'lax',
            ]);
        }
    }

    private function requestCarriesStaffCookie(Request $request): bool
    {
        $name = (string) config('staff-auth.session.cookie', 'bandara_staff_session');

        return $name !== '' && $request->cookies->has($name);
    }

    private function requestCarriesCustomerCookie(Request $request): bool
    {
        $customer = app()->bound('bandara.customer-session-config')
            ? (array) app('bandara.customer-session-config')
            : [];

        $name = (string) ($customer['cookie'] ?? '');

        return $name !== '' && $request->cookies->has($name);
    }

    private function looksLikeSharedLogout(Request $request, string $name, string $uri): bool
    {
        if (! in_array($request->getMethod(), ['POST', 'DELETE'], true)) {
            return false;
        }

        return $name === 'logout'
            || $uri === 'logout'
            || str_ends_with($name, '.logout');
    }

    private function refererIsStaffArea(Request $request): bool
    {
        $referer = (string) $request->headers->get('referer', '');

        if ($referer === '') {
            return false;
        }

        $path = ltrim((string) parse_url($referer, PHP_URL_PATH), '/');

        foreach (Arr::wrap(config('staff-auth.staff_path_prefixes', ['admin'])) as $prefix) {
            $prefix = trim((string) $prefix, '/');

            if ($prefix !== '' && ($path === $prefix || str_starts_with($path, "{$prefix}/"))) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeImpersonationRoute(string $descriptor): bool
    {
        foreach (['impersonat', 'masquerad', 'login-as', 'login_as', 'loginas', 'act-as', 'act_as'] as $token) {
            if (str_contains($descriptor, $token)) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeImpersonationLeaveRoute(string $descriptor): bool
    {
        foreach (['leave', 'stop', 'exit', 'revert', 'restore', 'destroy'] as $token) {
            if (str_contains($descriptor, $token)) {
                return true;
            }
        }

        return false;
    }
}
