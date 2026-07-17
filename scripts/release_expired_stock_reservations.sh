#!/usr/bin/env bash
set -euo pipefail

php artisan bandara:release-expired-stock-reservations "$@"
