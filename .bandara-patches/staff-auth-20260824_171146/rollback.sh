#!/bin/bash
set -Eeuo pipefail
export PATH="/usr/bin:/bin:/usr/sbin:/sbin:/opt/homebrew/bin:/usr/local/bin"
ROOT="/Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen"
BACKUP_FILES="/Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/.bandara-patches/staff-auth-20260824_171146/backup"
MANIFEST="/Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/.bandara-patches/staff-auth-20260824_171146/PATCH_MANIFEST.tsv"

while IFS=$'\t' read -r status original patched rel; do
    [[ "${status}" == "STATUS" ]] && continue
    if [[ -f "${BACKUP_FILES}/${rel}" ]]; then
        mkdir -p "$(dirname "${ROOT}/${rel}")"
        cp -p "${BACKUP_FILES}/${rel}" "${ROOT}/${rel}"
    elif [[ "${status}" == "ADD" ]]; then
        rm -f "${ROOT}/${rel}"
    fi
done < "${MANIFEST}"

PHP_BIN=""
for candidate in /Applications/MAMP/bin/php/php8.5.*/bin/php /opt/homebrew/bin/php /usr/local/bin/php /usr/bin/php; do
    if [[ -x "${candidate}" ]]; then PHP_BIN="${candidate}"; break; fi
done
if [[ -n "${PHP_BIN}" ]]; then
    cd "${ROOT}"
    "${PHP_BIN}" artisan optimize:clear || true
fi
printf '\nRollback completed.\n'
