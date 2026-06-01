#!/usr/bin/env bash
set -euo pipefail
# Run this from the Laravel project root if you used the replacement zip.
# The git patch removes these files automatically; this script is only for manual replacement installs.
files=(
  'app/Http/Controllers/Admin/B2BOrderRequestController.php'
  'app/Http/Controllers/Customer/B2BOrderRequestController.php'
  'app/Models/B2BOrderItemAllocation.php'
  'app/Models/B2BOrderRequest.php'
  'app/Models/B2BOrderRequestItem.php'
  'app/Services/B2BOrderRequestFinalizationService.php'
  'database/migrations/2026_05_25_000001_create_b2b_order_requests_table.php'
  'database/migrations/2026_05_25_000002_create_b2b_order_request_items_table.php'
  'database/migrations/2026_05_26_000001_create_b2b_order_item_allocations_table.php'
  'database/migrations/2026_05_27_000001_finalize_b2b_order_requests.php'
  'resources/views/admin/b2b/order-requests/index.blade.php'
  'resources/views/admin/b2b/order-requests/show.blade.php'
  'resources/views/b2b/requests/index.blade.php'
)
for f in "${files[@]}"; do
  if [ -e "$f" ]; then
    rm -rf "$f"
    echo "removed $f"
  fi
done
# Remove now-empty obsolete view directories if present.
rmdir resources/views/admin/b2b/order-requests 2>/dev/null || true
rmdir resources/views/b2b/requests 2>/dev/null || true
echo "Obsolete B2B request/allocation module files removed."
