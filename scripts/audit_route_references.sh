#!/usr/bin/env bash
set -euo pipefail

php artisan bandara:audit-route-references --fail-on-missing
