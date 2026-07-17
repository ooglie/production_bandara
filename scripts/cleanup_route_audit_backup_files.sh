#!/usr/bin/env bash
set -euo pipefail

rm -f "resources/views/home.blade copy.php"
rm -f "resources/views/customer/checkout/index.blade.php.orig"

echo "Removed obsolete Blade backup files used only during earlier patching."
