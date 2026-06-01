<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();

            // If we are NOT impersonating and user is inactive -> kick them out
            if (!$user->is_active && ! $request->session()->has('impersonator_id')) {
                Auth::logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Your account has been suspended. Please contact support.',
                ]);
            }
        }

        return $next($request);
    }
}
