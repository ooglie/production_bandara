<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Authentication\StaffDashboardResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class RedirectIfStaffAuthenticated
{
    public function __construct(
        private readonly StaffDashboardResolver $dashboards,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $guard = (string) config('staff-auth.staff_guard', 'staff');
        $user = Auth::guard($guard)->user();

        if ($user === null) {
            return $next($request);
        }

        return redirect()->to($this->dashboards->url($user));
    }
}
