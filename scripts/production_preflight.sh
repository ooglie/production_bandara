#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="${1:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
cd "$APP_DIR"

printf '== PHP / Composer security ==\n'
php -v
composer validate --strict
composer audit --locked

printf '\n== Application tests ==\n'
if [[ "${BANDARA_PREFLIGHT_RUN_TESTS:-false}" == "true" ]]; then
    php artisan test
else
    printf 'Skipped. Set BANDARA_PREFLIGHT_RUN_TESTS=true on a staging/build host to run tests.\n'
fi

printf '\n== Laravel production configuration ==\n'
php artisan security:audit-config --production
php artisan schedule:list

printf '\n== Filesystem permissions ==\n'
"$APP_DIR/scripts/check_production_permissions.sh" "$APP_DIR"

if [[ -n "${BANDARA_HEALTH_URL:-}" ]]; then
    printf '\n== HTTPS health endpoint ==\n'
    curl --fail --silent --show-error --location \
        --max-time 15 \
        "$BANDARA_HEALTH_URL" >/dev/null
    printf 'Health endpoint responded successfully: %s\n' "$BANDARA_HEALTH_URL"
fi

printf '\nProduction preflight completed successfully.\n'
