<?php

namespace App\Http\Middleware;

use App\Support\FinanceAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFinanceCapability
{
    public function handle(Request $request, Closure $next, string $capability): Response
    {
        FinanceAccess::authorize($request->user(), $capability);

        return $next($request);
    }
}
