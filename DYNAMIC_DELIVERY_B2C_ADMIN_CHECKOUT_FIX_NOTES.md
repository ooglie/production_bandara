# Dynamic Delivery Fee B2C/Admin/Checkout Fix

## Purpose

This is a focused correction for the dynamic distance delivery-fee rollout.

It fixes two issues:

1. The admin distance-rule UI was not clear enough for adding base-fee + per-km pricing.
2. Distance delivery rules should affect B2C/guest checkout only; B2B delivery fee must remain waived.

## Behaviour

### B2C / Guest

Distance rules can now be configured as either:

- fixed slab fee, or
- dynamic fee: base fee + fee per started km after base-covered distance.

Example:

- Base fee: ₹49
- Base covers: 3 km
- Fee per started km after: ₹12
- Delivery distance: 8.2 km

Calculation:

- Chargeable distance after base = 8.2 - 3 = 5.2 km
- Started km units = 6
- Delivery fee = ₹49 + 6 × ₹12 = ₹121

### B2B

B2B checkout ignores distance and pincode delivery rules and keeps delivery fee at ₹0.

Handling rules remain independent, but delivery fee source will be `b2b_waived`.

## Files changed

- `app/Services/DeliveryChargeService.php`
- `resources/views/admin/delivery/index.blade.php`
- `resources/views/customer/cart/index.blade.php`
- `resources/views/customer/checkout/index.blade.php`

## No migration required

The database columns required for dynamic pricing already exist:

- `delivery_distance_rules.delivery_fee`
- `delivery_distance_rules.included_distance_km`
- `delivery_distance_rules.per_km_fee`

## Apply

```bash
git apply dynamic_delivery_b2c_admin_checkout_fix.patch
php artisan optimize:clear
php artisan config:clear
php artisan route:list
php artisan view:cache
```

If patch context fails, use the replacement files zip.

## Test checklist

1. Go to **Admin → Delivery & handling → Distance-based delivery rules**.
2. Add a B2C rule:
   - Min distance: `0`
   - Max distance: blank
   - Base fee: `49`
   - Base covers km: `3`
   - Fee per started km after: `12`
   - Free delivery above: optional
3. Checkout as B2C with a Google-calculated distance such as `8.2 km`.
4. Confirm fee equals ₹121.
5. Checkout as B2B with the same address.
6. Confirm delivery fee remains ₹0.
7. Confirm cart/checkout show the dynamic fee formula for B2C.
