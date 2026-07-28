# Final five test failures fix

The final five failures were caused by two test helper methods attempting to insert an obsolete `products.pricing_unit` attribute.

The current product schema intentionally uses:

- `sell_unit`
- `pack_type`
- `product_weight`
- `pieces_per_pack`

The product-level `pricing_unit` column was removed by `2026_01_27_211951_simplify_products_units_add_product_weight.php`. Variant-level and order/invoice-line `pricing_unit` fields remain valid and are not changed by this package.

This package:

1. Removes `pricing_unit` from the B2B storefront test product helper.
2. Removes `pricing_unit` from the storefront search test product helper.
3. Removes the stale product-level attribute from `Product::$fillable` and `Product::$casts` so the model matches the live database schema.

No migration is required and no storefront, cart, pricing, checkout, or production behavior is changed.

After extraction into the Laravel project root, run:

```bash
php artisan optimize:clear
php artisan test
```
