<?php

declare(strict_types=1);

namespace App\Support\Authentication;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;

final class StaffDashboardResolver
{
    public function __construct(
        private readonly AccountTypeResolver $accounts,
    ) {
    }

    public function url(mixed $user): string
    {
        foreach (Arr::wrap(config('staff-auth.role_dashboard_routes', [])) as $mapping) {
            if (! is_array($mapping)) {
                continue;
            }

            $roles = array_values(array_filter(array_map(
                static fn (mixed $role): string => trim((string) $role),
                Arr::wrap($mapping['roles'] ?? [])
            )));

            if ($roles === [] || ! $this->accounts->hasAnyRole($user, $roles)) {
                continue;
            }

            $url = $this->firstAvailableRouteUrl(Arr::wrap($mapping['routes'] ?? []));

            if ($url !== null) {
                return $url;
            }
        }

        return $this->fallbackUrl();
    }

    public function destinationAfterLogin(Request $request, mixed $user): string
    {
        $intended = $request->session()->pull('url.intended');

        /*
         * Preserve the existing delivery-login behaviour: mobile delivery
         * users must always land on their own dashboard rather than a stale
         * intended URL from a previous browser session.
         */
        if ($this->accounts->hasAnyRole($user, [
            'DeliveryAgent',
            'Delivery Agent',
            'DeliveryBoy',
            'Delivery Boy',
        ])) {
            return $this->url($user);
        }

        if (is_string($intended) && $this->isStaffUrl($request, $intended)) {
            return $intended;
        }

        return $this->url($user);
    }

    private function fallbackUrl(): string
    {
        $url = $this->firstAvailableRouteUrl(
            Arr::wrap(config('staff-auth.dashboard_routes', []))
        );

        return $url ?? url((string) config('staff-auth.dashboard_path', '/admin'));
    }

    private function firstAvailableRouteUrl(array $routeNames): ?string
    {
        foreach ($routeNames as $routeName) {
            if (is_string($routeName) && $routeName !== '' && Route::has($routeName)) {
                return route($routeName);
            }
        }

        return null;
    }

    private function isStaffUrl(Request $request, string $candidate): bool
    {
        $candidate = trim($candidate);

        if ($candidate === '') {
            return false;
        }

        $parts = parse_url($candidate);

        if ($parts === false) {
            return false;
        }

        $host = isset($parts['host']) ? strtolower((string) $parts['host']) : null;

        if ($host !== null && $host !== strtolower($request->getHost())) {
            return false;
        }

        $path = ltrim((string) ($parts['path'] ?? ''), '/');

        foreach (Arr::wrap(config('staff-auth.staff_path_prefixes', ['admin'])) as $prefix) {
            $prefix = trim((string) $prefix, '/');

            if ($prefix !== '' && ($path === $prefix || str_starts_with($path, "{$prefix}/"))) {
                return true;
            }
        }

        return false;
    }
}
