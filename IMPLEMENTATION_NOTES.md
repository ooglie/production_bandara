# Transform Stock exact output products fix

This update removes the global output product catalogue from the Transform Stock page.

The browser now requests the exact output products only after a source lot is selected:

- the selected source product itself (for same-product variant repacking such as 240pc -> 10pc/20pc), and
- products explicitly mapped in `product_transformations` with the selected source product as `source_product_id`.

Files changed:

- `app/Http/Controllers/Admin/InventoryPackController.php`
- `resources/views/admin/inventory/packs/create.blade.php`
- `routes/web.php`

After copying, run:

```bash
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
```

The ZIP is intentionally rooted at `app/`, `resources/`, and `routes/`; extract it directly into the Laravel project root.
