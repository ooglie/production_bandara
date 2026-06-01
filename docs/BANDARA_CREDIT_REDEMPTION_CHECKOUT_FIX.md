# Bandara Credit checkout redemption enabled fix

This hotfix addresses checkout showing redemption as disabled even when the feature appears enabled.

## Main fixes

- Supports `BANDARA_CREDIT_ELIGIBILITY_MODE=b2c` as an alias for customer-type based B2C eligibility.
- Keeps rewards B2C-only using `users.customer_type = b2c`.
- Shows the actual checkout redemption reason instead of always showing a generic disabled message.
- Shows available wallet points for eligible B2C users even if redemption is disabled by config/shadow mode.

## Required env values

```env
BANDARA_CREDIT_ENABLED=true
BANDARA_CREDIT_SHADOW_MODE=false
BANDARA_CREDIT_REDEEM_ENABLED=true
BANDARA_CREDIT_REDEEM_MINIMUM_POINTS=1
BANDARA_CREDIT_ELIGIBILITY_MODE=b2c
BANDARA_CREDIT_ELIGIBILITY_COLUMN=customer_type
BANDARA_CREDIT_ELIGIBILITY_B2C_VALUE=b2c
```

Then run:

```bash
php artisan optimize:clear
php artisan config:clear
php artisan view:clear
```

## Quick diagnostic

```bash
php artisan tinker
```

```php
$user = App\Models\User::where('email', 'CUSTOMER_EMAIL_HERE')->first();
app(App\Services\BandaraCreditService::class)->redemptionStatusForUser($user);
config('bandara_credit.enabled');
config('bandara_credit.shadow_mode');
config('bandara_credit.redeem_enabled');
```
