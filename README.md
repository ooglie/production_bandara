# Bandara rewards runtime hotfix v1

Fixes:

1. `Call to undefined method App\Services\BandaraCreditService::tierDefinitions()` on admin rewards pages.
2. Checkout Bandara Credit block showing redemption disabled even when `redemptionStatusForUser()` returns enabled.

Apply from the Laravel project root:

```bash
unzip -o bandara_rewards_runtime_hotfix_v1.zip -d .
php scripts/fix_bandara_rewards_checkout_and_tiers.php
php -l app/Services/BandaraCreditService.php
php -l app/Http/Controllers/Customer/CheckoutController.php
php artisan view:clear
php artisan config:clear
php artisan optimize:clear
```

The script creates timestamped `.bak_rewards_runtime_*` backups before modifying files.
