<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): \Symfony\Component\HttpFoundation\Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('security.headers.enabled', true)) {
            return $response;
        }

        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff', false);
        $headers->set('X-Frame-Options', 'SAMEORIGIN', false);
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin', false);
        $headers->set('Permissions-Policy', (string) config(
            'security.headers.permissions_policy',
            'camera=(), microphone=(), geolocation=(), payment=(self)'
        ), false);

        $cspReportOnly = trim((string) config('security.headers.csp_report_only', ''));
        if ($cspReportOnly !== '') {
            $headers->set('Content-Security-Policy-Report-Only', $cspReportOnly, false);
        }

        if (
            config('security.headers.hsts_enabled', false)
            && $request->isSecure()
            && app()->environment('production')
        ) {
            $headers->set(
                'Strict-Transport-Security',
                (string) config('security.headers.hsts_value', 'max-age=31536000'),
                false
            );
        }

        return $response;
    }
}
