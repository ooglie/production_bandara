# Transform Stock — associated outputs only

This incremental update must be applied after the previous Transform Stock source-lot balance fix.

## Changed files

- `app/Http/Controllers/Admin/InventoryPackController.php`
- `resources/views/admin/inventory/packs/create.blade.php`

## Behaviour

- The Output product dropdown no longer contains the complete product catalogue.
- After selecting a source lot, it contains only:
  - the source product itself, so another variant of the same product can be produced; and
  - products explicitly mapped through `product_transformations` from the source product.
- The previous “Show all output products” override has been removed.
- The controller loads only source/mapped products for this page and rejects unrelated output products server-side.

## Installation

Copy the two application files to the matching project paths, then run:

```bash
php artisan view:clear
php artisan optimize:clear
```

No migration is required.
