#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="${1:-/var/www/bandara/current}"
failures=0

fail() {
    printf 'FAIL: %s\n' "$*" >&2
    failures=$((failures + 1))
}

pass() {
    printf 'PASS: %s\n' "$*"
}

[[ -d "$APP_DIR" ]] || { printf 'Application directory not found: %s\n' "$APP_DIR" >&2; exit 1; }

for path in "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"; do
    [[ -d "$path" ]] || { fail "$path does not exist"; continue; }
    [[ -w "$path" ]] || fail "$path is not writable by the current deployment user"
    mode="$(stat -c '%a' "$path")"
    [[ "${mode: -1}" == "0" ]] || fail "$path is accessible by other users (mode $mode)"
done

if [[ -f "$APP_DIR/.env" ]]; then
    mode="$(stat -c '%a' "$APP_DIR/.env")"
    [[ "${mode: -1}" == "0" ]] || fail ".env is readable/writable by other users (mode $mode)"
    [[ "${mode: -2:1}" =~ [04] ]] || fail ".env must not be group-writable or executable (mode $mode)"
else
    pass '.env is platform-injected rather than stored on disk'
fi

for sensitive in .env artisan composer.json composer.lock phpunit.xml; do
    [[ ! -e "$APP_DIR/public/$sensitive" ]] || fail "public/$sensitive must not exist"
done

if [[ -e "$APP_DIR/public/storage" || -L "$APP_DIR/public/storage" ]]; then
    [[ -L "$APP_DIR/public/storage" ]] || fail 'public/storage is not a symbolic link'
else
    printf 'WARN: public/storage is absent; run php artisan storage:link if public media is required.\n'
fi

if (( failures > 0 )); then
    printf '%d permission/security check(s) failed.\n' "$failures" >&2
    exit 1
fi

printf 'Production permission checks passed.\n'
