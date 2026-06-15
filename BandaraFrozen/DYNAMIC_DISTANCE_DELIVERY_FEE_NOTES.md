# Dynamic distance delivery fee

This patch extends distance-based delivery rules so a rule can calculate:

```text
base fee + fixed fee for each started km beyond the included/base distance
```

Example:

```text
Base fee: ₹49
Base covers: 3 km
Per km after: ₹12
Calculated distance: 8.2 km
Chargeable km after base: ceil(8.2 - 3) = 6
Delivery fee: ₹49 + (6 × ₹12) = ₹121
```

## Files changed

```text
app/Http/Controllers/Admin/DeliverySettingsController.php
app/Models/DeliveryDistanceRule.php
app/Services/DeliveryChargeService.php
database/migrations/2026_06_10_000002_add_included_distance_to_delivery_distance_rules.php
resources/views/admin/delivery/index.blade.php
```

## New DB column

```text
delivery_distance_rules.included_distance_km
```

Existing slab rules continue to work because `per_km_fee` can remain blank.

## How to configure one dynamic rule

In **Admin → Delivery & handling → Distance-based delivery rules**, create a rule like:

```text
Customer type: B2C
Min distance: 0
Max distance: blank
Min order: 0
Base fee: 49
Base covers km: 3
Per km after: 12
Free above: 1999
GST %: as confirmed by accounts
```

This replaces multiple distance slabs with one dynamic rule.

## Compatibility

The patch does not change Google distance calculation, zone fallback, handling charges, checkout/payment, rewards, stock, delivery-agent, or order logic.
