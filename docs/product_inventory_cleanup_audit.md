# Product / Inventory Cleanup Audit

## Final working model

- **Simple product**: one saleable product with product-level price and stock.
- **Variable pack product**: existing product variants hold pack options, pack metadata, price, and stock. Example: Dimsum Pack 10 / Pack 20.
- **Slab / weighted product**: stock comes from inventory lots and inventory pieces. The storefront slab selector shows the exact available piece weights.
- **Internal / inward product**: used for vendor invoice/raw stock and converted to saleable product or variant stock through Create Pack Stock.

## Removed from active code

The abandoned `ProductSellUnit` layer has been removed from routes, models, controllers, services, and Blade screens. The product/variant/inventory flows now use product or product variant as the stock target.

## Database cleanup

Migration `2026_06_18_000001_drop_obsolete_product_sell_units_layer.php` removes:

- `product_sell_units` table
- `product_sell_unit_id` columns and related indexes/foreign keys from cart, order, invoice, B2B pricing, vendor invoice, inventory, stock movement, and product variant tables

It also adds `b2b_customer_products.product_variant_id`, because B2B MOQ/access can now be configured at either product level or variant level.

## Kept intentionally

- Existing product variant tables and attribute mapping are kept because dimsum/pack products use variants.
- `inventory_lots`, `inventory_pieces`, and `inventory_packs` are kept because they are required for vendor inward, slabs, and repack/production stock.
- Historical migrations are kept so existing migration history remains consistent; the final cleanup migration removes the obsolete final-schema objects.
