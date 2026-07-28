#!/usr/bin/env bash
set -Eeuo pipefail

BACKUP_DIR="${1:-}"

if [[ -z "$BACKUP_DIR" ]]; then
    printf 'Usage: %s /path/to/bandara-backup-directory\n' "$0" >&2
    exit 64
fi

[[ -d "$BACKUP_DIR" ]] || { printf 'Backup directory not found: %s\n' "$BACKUP_DIR" >&2; exit 1; }

for file in database.sql.gz storage-app.tar.gz MANIFEST.txt SHA256SUMS; do
    [[ -f "$BACKUP_DIR/$file" ]] || { printf 'Missing backup file: %s\n' "$file" >&2; exit 1; }
done

(
    cd "$BACKUP_DIR"
    sha256sum --check SHA256SUMS
    gzip -t database.sql.gz
    tar -tzf storage-app.tar.gz >/dev/null
)

printf 'Backup integrity verification passed: %s\n' "$BACKUP_DIR"
printf 'A separate restore drill into a disposable database is still required.\n'
