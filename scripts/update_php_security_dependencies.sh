#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

echo "Checking PHP and Composer..."
php -r 'if (version_compare(PHP_VERSION, "8.5.0", "<")) { fwrite(STDERR, "PHP 8.5 or newer is required. Current: ".PHP_VERSION.PHP_EOL); exit(1); }'
command -v composer >/dev/null 2>&1 || { echo "Composer was not found in PATH." >&2; exit 1; }

if [[ -f composer.lock ]]; then
    backup="composer.lock.security-backup.$(date +%Y%m%d_%H%M%S)"
    cp composer.lock "$backup"
    echo "Created $backup"
fi

echo "Updating only the vulnerable PHP dependency families..."
composer update \
    laravel/framework \
    guzzlehttp/guzzle \
    guzzlehttp/psr7 \
    symfony/http-foundation \
    symfony/http-kernel \
    symfony/mailer \
    symfony/mime \
    symfony/polyfill-intl-idn \
    symfony/routing \
    symfony/yaml \
    --with-all-dependencies \
    --no-interaction

echo "Auditing the updated lockfile..."
composer audit --locked
composer validate --strict

php artisan optimize:clear
php artisan test

echo "PHP security dependency update and regression tests completed successfully."
