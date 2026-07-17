# B2B catalogue visibility and rewards cleanup

## Behaviour

- A signed-in B2B customer sees a product only when at least one explicit B2B price is available through:
  - product-level standard B2B price;
  - an active, B2B-visible variant B2B price;
  - a valid customer-specific product/variant price;
  - an active B2B or all-customer special price.
- Retail/base pricing is never used as a B2B fallback.
- Products without B2B pricing are excluded from Shop, homepage product sections, collections, B2B Quick Order and wishlist.
- Direct product and variant-option URLs return 404 for unavailable B2B products.
- Add-to-cart remains server protected. Old retail-only cart lines are removed when a B2B cart is next synchronised.
- B2C catalogue and pricing behaviour is unchanged.

## B2B rewards UI

Bandara Credit/rewards are not rendered or initialised for B2B customers on:

- customer dashboard;
- checkout and checkout totals;
- order details;
- customer invoice details;
- invoice PDF.

Direct customer reward and reward-terms URLs redirect silently to the customer dashboard.

## Installation

Extract the ZIP directly into the Laravel project root, then run:

```bash
php artisan optimize:clear
php artisan config:clear
php artisan view:clear
```

No database migration is required.

## Validation

- PHP syntax checks passed for every modified PHP file.
- Blade directive balance and patch whitespace checks passed.
- The patch applies cleanly to the supplied July 12, 2026 source baseline.
- Feature tests are included in `tests/Feature/B2BStorefrontVisibilityTest.php`.
- A full Laravel test run was not executed because the supplied source archive excludes `vendor/` and the validation container runs PHP 8.4 rather than the project's PHP 8.5.
