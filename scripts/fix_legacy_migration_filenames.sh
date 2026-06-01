#!/usr/bin/env bash
set -euo pipefail

if [[ ! -d database/migrations ]]; then
  echo "Run this from the Laravel project root." >&2
  exit 1
fi

echo "Cleaning duplicate .php.php migration files..."
find database/migrations -maxdepth 1 -type f -name '*.php.php' -print0 | while IFS= read -r -d '' file; do
  target="${file%.php}"
  if [[ -f "$target" ]]; then
    echo "Removing duplicate: $file"
    rm -f "$file"
  else
    echo "Renaming: $file -> $target"
    mv "$file" "$target"
  fi
done

# Remove known malformed, undated, or duplicate legacy files if they exist.
# The real/safe migrations are supplied with dated filenames in this package.
for file in \
  database/migrations/20260113000010_add_print_tracking_to_orders_table.php \
  database/migrations/2026_01_13_000010_add_print_tracking_to_orders_table.php \
  database/migrations/add_print_tracking_to_orders_table.php \
  database/migrations/add_item_weight_to_order_items_table.php \
  database/migrations/add_item_weight_to_order_items.php
  do
    if [[ -f "$file" ]]; then
      echo "Removing malformed legacy migration: $file"
      rm -f "$file"
    fi
  done

echo "Remaining print/item-weight related migrations:"
ls -1 database/migrations | grep -Ei 'print|tracking|item_weight|order_items' || true
