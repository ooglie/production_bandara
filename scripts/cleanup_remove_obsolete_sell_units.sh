#!/usr/bin/env bash
set -euo pipefail

# Run from the Laravel project root after applying the cleanup patch.
# It removes files from the abandoned Product Sell Unit layer and old patch/test artifacts.

rm -f app/Http/Controllers/Admin/ProductSellUnitController.php
rm -f app/Models/ProductSellUnit.php
rm -rf resources/views/admin/products/sell-units

# Old temporary patch artifacts left in the project root during development.
rm -f delivery_address_fee_fix.diff \
      slab_dropdown_outside_click_hotfix_from_current.diff \
      product_card_unified_options_ui_hotfix.diff \
      product_sellable_repack_refactor.diff \
      README_VENDOR_INVOICE_FIX.txt \
      DYNAMIC_DELIVERY_B2C_ADMIN_CHECKOUT_FIX_NOTES.md \
      after_channel_gst_pricing_normalization.sql \
      remove_obsolete_invoice_payment_widget_partials.sh

rm -f app/Services/BandaraCreditService.php.bak_rewards_runtime_20260521201723
rm -f resources/views/customer/checkout/index.blade.php.bak_rewards_runtime_20260521201723
rm -rf BandaraFrozen

# macOS metadata files that were accidentally packaged into the project.
find . -not -path './vendor/*' -not -path './node_modules/*' -name '.DS_Store' -type f -delete

echo "Obsolete Product Sell Unit files and local patch artifacts removed."
