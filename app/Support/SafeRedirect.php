<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class SafeRedirect
{
    public static function local(Request $request, mixed $candidate): ?string
    {
        if (! is_string($candidate)) {
            return null;
        }

        $candidate = trim($candidate);

        if ($candidate === '' || str_contains($candidate, "\r") || str_contains($candidate, "\n")) {
            return null;
        }

        // Decode once so encoded protocol-relative and encoded backslash forms
        // cannot pass the local-path branch. Backslashes are not valid URL path
        // separators for this application and browsers/proxies may reinterpret them.
        $decoded = rawurldecode($candidate);

        if (
            str_contains($candidate, '\\')
            || str_contains($decoded, '\\')
            || Str::startsWith($candidate, '//')
            || Str::startsWith($decoded, '//')
        ) {
            return null;
        }

        if (Str::startsWith($candidate, '/')) {
            return $candidate;
        }

        $parts = parse_url($candidate);
        if (! is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $allowedHosts = array_filter(array_unique([
            strtolower($request->getHost()),
            strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST)),
            ...array_map('strtolower', (array) config('security.redirect_hosts', [])),
        ]));

        if (! in_array(strtolower((string) $parts['host']), $allowedHosts, true)) {
            return null;
        }

        if (isset($parts['scheme']) && ! in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)) {
            return null;
        }

        $path = $parts['path'] ?? '/';
        $decodedPath = rawurldecode($path);

        if (
            ! Str::startsWith($path, '/')
            || str_contains($path, '\\')
            || str_contains($decodedPath, '\\')
            || Str::startsWith($decodedPath, '//')
        ) {
            return null;
        }

        $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) && $parts['fragment'] !== '' ? '#' . $parts['fragment'] : '';

        return $path . $query . $fragment;
    }
}
