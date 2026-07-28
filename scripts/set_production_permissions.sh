#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="${1:-/var/www/bandara/current}"
APP_OWNER="${BANDARA_APP_OWNER:-bandara}"
WEB_GROUP="${BANDARA_WEB_GROUP:-www-data}"

if [[ "$EUID" -ne 0 ]]; then
    printf 'Run this script as root so ownership can be applied safely.\n' >&2
    exit 1
fi

[[ -d "$APP_DIR" ]] || { printf 'Application directory not found: %s\n' "$APP_DIR" >&2; exit 1; }
[[ -f "$APP_DIR/artisan" ]] || { printf 'artisan not found in %s\n' "$APP_DIR" >&2; exit 1; }

chown -R "$APP_OWNER:$WEB_GROUP" "$APP_DIR"

# Source code is readable by the deploy user and web group, but not by others.
find "$APP_DIR" -xdev -type d -exec chmod 0750 {} +
find "$APP_DIR" -xdev -type f -exec chmod 0640 {} +
chmod 0750 "$APP_DIR/artisan"
if [[ -d "$APP_DIR/scripts" ]]; then
    find "$APP_DIR/scripts" -maxdepth 1 -type f -name '*.sh' -exec chmod 0750 {} +
fi

# Laravel's only writable application directories.
find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type d -exec chmod 0770 {} +
find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type f -exec chmod 0660 {} +

if [[ -f "$APP_DIR/.env" ]]; then
    chown "$APP_OWNER:$WEB_GROUP" "$APP_DIR/.env"
    chmod 0640 "$APP_DIR/.env"
fi

# Public assets need no write permission from the web process.
find "$APP_DIR/public" -xdev -type d -exec chmod 0750 {} +
find "$APP_DIR/public" -xdev -type f -exec chmod 0640 {} +

printf 'Production permissions applied to %s\n' "$APP_DIR"
printf 'Owner: %s | Web group: %s\n' "$APP_OWNER" "$WEB_GROUP"
