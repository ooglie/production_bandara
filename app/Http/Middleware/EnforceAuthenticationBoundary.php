<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Authentication\AccountTypeResolver;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class EnforceAuthenticationBoundary
{
    public function __construct(
        private readonly AccountTypeResolver $accounts,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $context = (string) $request->attributes->get(
            SelectAuthenticationContext::ATTRIBUTE,
            'customer'
        );

        if ($context === 'staff') {
            $this->enforceStaffBoundary($request);
        } else {
            $this->enforceCustomerBoundary($request);
        }

        return $next($request);
    }

    private function enforceStaffBoundary(Request $request): void
    {
        $guardName = (string) config('staff-auth.staff_guard', 'staff');
        $guard = Auth::guard($guardName);
        $user = $guard->user();

        if ($user !== null && ! $this->accounts->isStaff($user)) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                $this->loginField($request) => trans('auth.failed'),
            ]);
        }

        if ($user === null && $this->routeRequiresAuthentication($request)) {
            throw new AuthenticationException(
                'Unauthenticated.',
                [$guardName],
                $this->staffLoginUrl()
            );
        }
    }

    private function enforceCustomerBoundary(Request $request): void
    {
        if ($this->isCustomerLoginAttempt($request)) {
            $candidate = $this->findAttemptedUser($request);

            if ($candidate !== null && $this->accounts->isStaff($candidate)) {
                throw ValidationException::withMessages([
                    $this->loginField($request) => trans('auth.failed'),
                ]);
            }
        }

        $guardName = (string) config('staff-auth.customer_guard', 'web');
        $guard = Auth::guard($guardName);
        $user = $guard->user();

        /*
         * This removes legacy staff identities left in the old shared "web"
         * session before this patch was installed. Ordinary public pages then
         * continue as a guest; protected customer pages return to /login.
         */
        if ($user !== null && $this->accounts->isStaff($user)) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($this->routeRequiresAuthentication($request)) {
                throw new AuthenticationException(
                    'Unauthenticated.',
                    [$guardName],
                    $this->customerLoginUrl()
                );
            }
        }
    }

    private function isCustomerLoginAttempt(Request $request): bool
    {
        if ($request->getMethod() !== 'POST') {
            return false;
        }

        $route = $request->route();
        $name = $route instanceof Route ? (string) ($route->getName() ?? '') : '';
        $uri = $route instanceof Route ? trim((string) $route->uri(), '/') : trim($request->path(), '/');

        return $name === 'login'
            || $uri === 'login'
            || str_ends_with($name, '.customer.login.store');
    }

    private function findAttemptedUser(Request $request): ?object
    {
        $modelClass = $this->userModelClass();

        if ($modelClass === null || ! class_exists($modelClass)) {
            return null;
        }

        try {
            /** @var Model $model */
            $model = new $modelClass();
            $table = $model->getTable();

            foreach ($this->loginCandidates($request) as [$column, $value]) {
                if ($value === '' || ! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $user = $modelClass::query()->where($column, $value)->first();

                if ($user !== null) {
                    return $user;
                }
            }
        } catch (Throwable) {
            /*
             * The existing login controller remains the source of truth when
             * the database is unavailable or a custom user provider is used.
             */
        }

        return null;
    }

    /**
     * @return list<array{0:string,1:string}>
     */
    private function loginCandidates(Request $request): array
    {
        $candidates = [];

        foreach (Arr::wrap(config('staff-auth.login_columns', ['email'])) as $column) {
            $column = (string) $column;
            $value = trim((string) $request->input($column, ''));

            if ($value !== '') {
                $candidates[] = [$column, $value];
            }
        }

        $generic = trim((string) $request->input('login', ''));

        if ($generic !== '') {
            foreach (Arr::wrap(config('staff-auth.login_columns', ['email'])) as $column) {
                $candidates[] = [(string) $column, $generic];
            }
        }

        return array_values(array_unique($candidates, SORT_REGULAR));
    }

    private function userModelClass(): ?string
    {
        $guard = (string) config('staff-auth.customer_guard', 'web');
        $provider = (string) config("auth.guards.{$guard}.provider", 'users');
        $model = config("auth.providers.{$provider}.model");

        return is_string($model) && $model !== '' ? $model : null;
    }

    private function routeRequiresAuthentication(Request $request): bool
    {
        $route = $request->route();

        if (! $route instanceof Route) {
            return false;
        }

        $name = (string) ($route->getName() ?? '');

        if (in_array($name, Arr::wrap(config('staff-auth.public_staff_route_names', [])), true)) {
            return false;
        }

        try {
            foreach ($route->gatherMiddleware() as $middleware) {
                $middleware = Str::lower(is_string($middleware) ? $middleware : get_debug_type($middleware));

                if (
                    $middleware === 'auth'
                    || str_starts_with($middleware, 'auth:')
                    || str_starts_with($middleware, 'role:')
                    || str_starts_with($middleware, 'permission:')
                    || str_contains($middleware, 'rolemiddleware')
                    || str_contains($middleware, 'permissionmiddleware')
                    || str_contains($middleware, 'enforceadmin')
                ) {
                    return true;
                }
            }
        } catch (Throwable) {
            return false;
        }

        return false;
    }

    private function loginField(Request $request): string
    {
        foreach (Arr::wrap(config('staff-auth.login_columns', ['email'])) as $field) {
            if ($request->exists((string) $field)) {
                return (string) $field;
            }
        }

        return $request->exists('login') ? 'login' : 'email';
    }

    private function staffLoginUrl(): string
    {
        return RouteFacade::has('admin.login')
            ? route('admin.login')
            : url('/admin/login');
    }

    private function customerLoginUrl(): string
    {
        return RouteFacade::has('login')
            ? route('login')
            : url('/login');
    }
}
