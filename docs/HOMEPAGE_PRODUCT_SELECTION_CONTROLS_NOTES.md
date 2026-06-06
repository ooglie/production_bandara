# Homepage Product Selection Controls

This patch is the next phase after the DB-driven homepage foundation.

## Purpose

The homepage was already editable through `home_sections` and `home_section_items`, but product/category/collection behaviour still required editing raw settings JSON. This patch adds safer admin controls for common homepage display rules and extends the homepage product showcase to support manual, category, and collection-driven product sources.

## Changed files

```text
app/Http/Controllers/Admin/HomeSectionController.php
app/Services/HomePageService.php
resources/views/admin/home/sections/edit.blade.php
```

No database migration is required.

## Product showcase source modes

Admin can now configure the `product_showcase` section from the UI using:

```text
Featured + new + special products
Featured products only
New products only
Special products only
Latest active products
Products from one category
Products from one collection
Manual products from section items
```

Settings are still stored in `home_sections.settings` JSON, but common fields are now editable without writing JSON manually.

## Manual homepage products

For manual product selection:

1. Edit the homepage product showcase section.
2. Set Product source to `Manual products from section items`.
3. Add section items linked to products.
4. Use item sort order to control display order.

The homepage only renders active products.

## Category/collection source

For product showcase sections, admin can now select:

```text
Category source
Collection source
Limit
```

The homepage service reads these settings and builds the product grid accordingly.

## Other display-rule controls

The edit screen also adds friendly controls for:

```text
Category section limit and product-count visibility
Collection section home_section key and limit
Chef picks collection home_section key and recipe limit
```

Advanced settings JSON remains available for extra configuration.

## Linked section items

Homepage items can now link directly to:

```text
Product
Category
Collection
```

This is mainly used for manual product showcase ordering, but it also makes section items more reusable in future homepage blocks.

## What this patch does not touch

```text
Storefront pricing
B2C/B2B GST logic
Cart/checkout
Razorpay
Rewards
Pay Later
Stock/inventory
Vendor invoices
```

## Validation performed

```text
PHP syntax lint passed:
- app/Http/Controllers/Admin/HomeSectionController.php
- app/Services/HomePageService.php
- resources/views/admin/home/sections/edit.blade.php

git diff --check passed
```

Run locally after applying:

```bash
php artisan optimize:clear
php artisan route:list
php artisan view:cache
```
