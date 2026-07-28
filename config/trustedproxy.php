<?php

$raw = trim((string) env('SECURITY_TRUSTED_PROXIES', ''));

if ($raw === '') {
    $proxies = null;
} elseif ($raw === '*' || $raw === '**') {
    $proxies = $raw;
} else {
    $proxies = array_values(array_filter(array_map(
        static fn (string $proxy): string => trim($proxy),
        explode(',', $raw)
    )));
}

return [
    /*
    | Trust no proxy by default. When TLS is terminated by a known reverse
    | proxy or load balancer, provide only its IP addresses or CIDR ranges.
    | Laravel's built-in TrustProxies middleware reads this configuration.
    */
    'proxies' => $proxies,
];
