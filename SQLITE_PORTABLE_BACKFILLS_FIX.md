# SQLite portable backfill fix

This package fixes the fresh SQLite test-database migration failures caused by MySQL-only joined UPDATE statements.

Updated migrations:

- `2026_05_31_000001_backfill_product_gst_rate_from_hsn.php`
- `2026_05_31_000002_backfill_missing_product_gst_rate_default.php`
- `2026_06_12_000003_normalize_inventory_lot_inward_modes_for_piece_selector.php`
- `2026_06_13_000001_add_pack_variant_fields_to_product_variants.php`

MySQL/MariaDB retain their existing optimized SQL. SQLite and other database drivers use portable select-then-update operations.

After extracting into the Laravel project root, run:

```bash
php artisan optimize:clear
php artisan test
```

Do not run `php artisan migrate` against an existing MySQL database solely for this test compatibility package; the affected migrations are historical and already recorded as executed.
