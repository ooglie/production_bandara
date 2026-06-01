#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

# Remove malformed/legacy duplicate migration files that Laravel maps to
# a non-existent class such as 20260113000010AddPrintTrackingToOrdersTable.
rm -f database/migrations/20260113000010_add_print_tracking_to_orders_table.php
rm -f database/migrations/20260113000010_add_print_tracking_to_orders_table.php.php
rm -f database/migrations/20260113000010AddPrintTrackingToOrdersTable.php
rm -f database/migrations/2026_01_13_000010_add_print_tracking_to_orders_table.php
rm -f database/migrations/2026_01_13_000010_add_print_tracking_to_orders_table.php.php
rm -f database/migrations/2026_01_15_001812_2026_01_13_000010_add_print_tracking_to_orders_table.php.php
rm -f database/migrations/2026_01_22_174920_2026_01_13_000010_add_print_tracking_to_orders_table.php.php

# Show remaining print-tracking migrations so the operator can confirm only the
# corrected files remain.
ls -1 database/migrations | grep -i 'print_tracking\|add_print_tracking' || true
