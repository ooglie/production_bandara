<?php

$csv = static function (string $value): array {
    return array_values(array_filter(array_map(
        static fn (string $item): string => strtolower(trim($item)),
        explode(',', $value)
    )));
};

$appHost = parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost';

return [
    'trusted_hosts' => $csv((string) env(
        'SECURITY_TRUSTED_HOSTS',
        $appHost.',localhost,127.0.0.1'
    )),

    'redirect_hosts' => $csv((string) env('SECURITY_REDIRECT_HOSTS', $appHost)),

    'headers' => [
        'enabled' => env('SECURITY_HEADERS_ENABLED', true),
        'permissions_policy' => env(
            'SECURITY_PERMISSIONS_POLICY',
            'camera=(), microphone=(), geolocation=(), payment=(self)'
        ),
        'csp_report_only' => env('SECURITY_CSP_REPORT_ONLY', ''),
        'hsts_enabled' => env('SECURITY_HSTS_ENABLED', false),
        'hsts_value' => env('SECURITY_HSTS_VALUE', 'max-age=31536000'),
    ],

    /*
    | These values exist only so config-cached production checks can confirm
    | that the one-time administrator password has been removed. Application
    | code should not use this block for normal authentication.
    */
    'administrator_provisioning' => [
        'name' => env('ADMIN_NAME'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
        'promote_existing' => env('ADMIN_PROMOTE_EXISTING_USER', false),
    ],

    'backup' => [
        'required' => env('BANDARA_BACKUP_REQUIRED', false),
        'directory' => env('BANDARA_BACKUP_DIRECTORY', '/var/backups/bandara'),
        'max_age_hours' => (int) env('BANDARA_BACKUP_MAX_AGE_HOURS', 26),
    ],
];
