# Product / Inventory DB cleanup patch

Apply the files in this patch over the current project, then run:

```bash
php artisan migrate
php artisan view:clear
php artisan optimize:clear
bash scripts/cleanup_remove_obsolete_sell_units.sh
```

The cleanup script removes obsolete Product Sell Unit PHP/Blade files and old patch artifacts that cannot be deleted by simply copying files from this zip.
