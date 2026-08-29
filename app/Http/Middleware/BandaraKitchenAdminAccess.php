<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class BandaraKitchenAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $staff = Auth::guard('staff')->user();

        abort_unless($staff, 401);

        $allowedRoleNames = ['admin', 'manager'];
        $roleNames = collect();

        if (method_exists($staff, 'roles')) {
            try {
                $roleNames = $staff->roles
                    ->pluck('name')
                    ->filter(fn (mixed $name): bool => is_string($name))
                    ->map(fn (string $name): string => mb_strtolower(trim($name)));
            } catch (\Throwable) {
                $roleNames = collect();
            }
        }

        if ($roleNames->intersect($allowedRoleNames)->isNotEmpty()) {
            return $next($request);
        }

        if (method_exists($staff, 'can')) {
            foreach (['manage kitchen', 'manage chefs', 'manage content'] as $permission) {
                try {
                    if ($staff->can($permission)) {
                        return $next($request);
                    }
                } catch (\Throwable) {
                    // Continue to the next compatible permission name.
                }
            }
        }

        abort(403, 'You do not have permission to manage Bandara Kitchen chefs.');
    }
}
