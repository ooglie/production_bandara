# SQLite test migration follow-up

This package fixes fresh SQLite test migrations without changing existing MySQL production data.

## Fixed

1. Drops `recipes_slug_index` before replacing the indexed string `recipes.slug` column with a JSON multilingual column.
2. Prevents duplicate legacy product-variant attribute migrations from adding the same foreign key repeatedly on SQLite.
3. Keeps the obsolete product sell-unit cleanup on MySQL/MariaDB only; SQLite receives the replacement `product_variant_id` compatibility column without executing MySQL `information_schema` queries.

## Install

Extract the package into the Laravel project root, replacing the included migration files.

Then run:

```bash
php artisan optimize:clear
php artisan test
```

No migration should be run against an existing production database for this package. These changes affect fresh migration execution used by SQLite tests and new installations. Existing MySQL migration history is unchanged.
