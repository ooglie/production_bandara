<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class RedirectBusinessAccountIntent
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->hasSession() || ! $request->user() || $request->expectsJson()) {
            return $response;
        }

        $key = (string) config(
            'b2b_application_corrective.entry_intent.session_key',
            'bandara.business_account_intent',
        );
        $intent = $request->session()->get($key);

        if (! is_array($intent)) {
            return $response;
        }

        $startedAt = (int) ($intent['started_at'] ?? 0);
        $ttl = max(60, (int) config('b2b_application_corrective.entry_intent.ttl_seconds', 7200));

        if ($startedAt < 1 || (now()->timestamp - $startedAt) > $ttl) {
            $request->session()->forget($key);

            return $response;
        }

        $routeName = $request->route()?->getName();

        if (is_string($routeName) && (
            $routeName === 'business-account.continue'
            || str_starts_with($routeName, 'account.business-application.')
            || str_starts_with($routeName, 'admin.')
            || $routeName === 'logout'
        )) {
            if ($routeName === 'business-account.continue') {
                $request->session()->forget($key);
            }

            return $response;
        }

        if (! Route::has('business-account.continue')) {
            return $response;
        }

        $request->session()->forget($key);

        return redirect()->route('business-account.continue');
    }
}
