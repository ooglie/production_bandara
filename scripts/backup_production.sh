#!/usr/bin/env bash
set -Eeuo pipefail
umask 077

CONFIG_FILE="${BANDARA_BACKUP_CONFIG:-/etc/bandara/backup.env}"

fail() {
    printf 'Backup failed: %s\n' "$*" >&2
    exit 1
}

require_command() {
    command -v "$1" >/dev/null 2>&1 || fail "required command not found: $1"
}

[[ -f "$CONFIG_FILE" ]] || fail "configuration file not found: $CONFIG_FILE"
require_command stat
config_mode="$(stat -c '%a' "$CONFIG_FILE")"
[[ "${config_mode: -2}" == "00" ]] || fail "$CONFIG_FILE must not be accessible by group or other users (mode $config_mode)"

# The file is sourced only after its permissions are verified because it is a
# shell environment file and could otherwise execute commands as the backup user.
# shellcheck disable=SC1090
set -a
source "$CONFIG_FILE"
set +a

: "${BANDARA_APP_DIR:?Set BANDARA_APP_DIR in $CONFIG_FILE}"
: "${BANDARA_BACKUP_DIR:?Set BANDARA_BACKUP_DIR in $CONFIG_FILE}"
: "${BANDARA_DB_NAME:?Set BANDARA_DB_NAME in $CONFIG_FILE}"
: "${BANDARA_MYSQL_CNF:?Set BANDARA_MYSQL_CNF in $CONFIG_FILE}"

BANDARA_BACKUP_RETENTION_DAYS="${BANDARA_BACKUP_RETENTION_DAYS:-14}"
BANDARA_BACKUP_INCLUDE_ROUTINES="${BANDARA_BACKUP_INCLUDE_ROUTINES:-false}"
BANDARA_BACKUP_INCLUDE_EVENTS="${BANDARA_BACKUP_INCLUDE_EVENTS:-false}"

[[ -d "$BANDARA_APP_DIR" ]] || fail "application directory not found: $BANDARA_APP_DIR"
[[ -f "$BANDARA_APP_DIR/artisan" ]] || fail "artisan not found under application directory"
[[ -f "$BANDARA_MYSQL_CNF" ]] || fail "MySQL client file not found: $BANDARA_MYSQL_CNF"

require_command mysqldump
require_command gzip
require_command tar
require_command sha256sum
require_command date
require_command find
require_command flock
require_command realpath
require_command php
require_command awk

mysql_cnf_mode="$(stat -c '%a' "$BANDARA_MYSQL_CNF")"
[[ "${mysql_cnf_mode: -2}" == "00" ]] || fail "$BANDARA_MYSQL_CNF must not be accessible by group or other users (mode $mysql_cnf_mode)"

mkdir -p "$BANDARA_BACKUP_DIR"
chmod 700 "$BANDARA_BACKUP_DIR"

APP_REAL="$(realpath "$BANDARA_APP_DIR")"
BACKUP_REAL="$(realpath "$BANDARA_BACKUP_DIR")"
PUBLIC_REAL="$(realpath "$BANDARA_APP_DIR/public")"

case "$BACKUP_REAL/" in
    "$PUBLIC_REAL"/*) fail "backup directory must not be inside the public web root" ;;
esac

LOCK_FILE="$BANDARA_BACKUP_DIR/.backup.lock"
exec 9>"$LOCK_FILE"
flock -n 9 || fail "another backup is already running"

STAMP="$(date -u +'%Y%m%dT%H%M%SZ')"
FINAL_DIR="$BANDARA_BACKUP_DIR/bandara-$STAMP"
TEMP_DIR="$BANDARA_BACKUP_DIR/.bandara-$STAMP.tmp.$$"

cleanup() {
    if [[ -d "$TEMP_DIR" ]]; then
        rm -rf "$TEMP_DIR"
    fi
}
trap cleanup EXIT

mkdir -m 700 "$TEMP_DIR"

DUMP_ARGS=(
    "--defaults-extra-file=$BANDARA_MYSQL_CNF"
    --single-transaction
    --quick
    --skip-lock-tables
    --no-tablespaces
    --hex-blob
    --triggers
    --set-gtid-purged=OFF
    --default-character-set=utf8mb4
)

if [[ "$BANDARA_BACKUP_INCLUDE_ROUTINES" == "true" ]]; then
    DUMP_ARGS+=(--routines)
fi

if [[ "$BANDARA_BACKUP_INCLUDE_EVENTS" == "true" ]]; then
    DUMP_ARGS+=(--events)
fi

printf 'Creating database backup...\n'
mysqldump "${DUMP_ARGS[@]}" "$BANDARA_DB_NAME" | gzip -9 > "$TEMP_DIR/database.sql.gz"
gzip -t "$TEMP_DIR/database.sql.gz"

printf 'Creating uploaded-file backup...\n'
tar \
    --create \
    --gzip \
    --file "$TEMP_DIR/storage-app.tar.gz" \
    --directory "$APP_REAL" \
    --exclude='storage/app/backups' \
    storage/app

tar -tzf "$TEMP_DIR/storage-app.tar.gz" >/dev/null

{
    printf 'created_utc=%s\n' "$STAMP"
    printf 'database=%s\n' "$BANDARA_DB_NAME"
    printf 'application_path=%s\n' "$APP_REAL"
    printf 'php_version=%s\n' "$(php -r 'echo PHP_VERSION;' 2>/dev/null || printf unknown)"
    if [[ -f "$APP_REAL/composer.lock" ]]; then
        printf 'composer_lock_sha256=%s\n' "$(sha256sum "$APP_REAL/composer.lock" | awk '{print $1}')"
    fi
    if command -v git >/dev/null 2>&1 && git -C "$APP_REAL" rev-parse --is-inside-work-tree >/dev/null 2>&1; then
        printf 'git_commit=%s\n' "$(git -C "$APP_REAL" rev-parse HEAD)"
    fi
} > "$TEMP_DIR/MANIFEST.txt"

(
    cd "$TEMP_DIR"
    sha256sum database.sql.gz storage-app.tar.gz MANIFEST.txt > SHA256SUMS
    sha256sum --check SHA256SUMS
)

chmod 600 "$TEMP_DIR"/*
mv "$TEMP_DIR" "$FINAL_DIR"
trap - EXIT

printf '%s\n' "$(date +%s)" > "$BANDARA_BACKUP_DIR/.last-success"
chmod 600 "$BANDARA_BACKUP_DIR/.last-success"
ln -sfn "$(basename "$FINAL_DIR")" "$BANDARA_BACKUP_DIR/latest"

find "$BANDARA_BACKUP_DIR" \
    -mindepth 1 \
    -maxdepth 1 \
    -type d \
    -name 'bandara-*' \
    -mtime "+$BANDARA_BACKUP_RETENTION_DAYS" \
    -exec rm -rf -- {} +

printf 'Backup completed: %s\n' "$FINAL_DIR"
printf 'Copy this backup to an encrypted off-site location.\n'
