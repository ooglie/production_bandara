# Bandara Frozen stabilization notes

Target stack for this handoff:

- Laravel 13
- PHP 8.5 (`composer.json` requires `^8.5`)
- Node/Vite frontend installed from `package-lock.json`

## Fresh setup

```bash
cp .env.example .env
composer install
php artisan key:generate
npm ci
npm run build
php artisan migrate
php artisan storage:link
php artisan optimize:clear
```

For the uploaded SQL dump, import the dump first and then run:

```bash
php artisan migrate
```

The clean archive places the uploaded DB dump at:

```text
database/dumps/bandara_frozen.sql
```

The migration `2026_05_19_000001_repair_stabilization_schema_gaps.php` repairs tables/columns that were marked as migrated in the dump but were missing from the actual schema, including `inventory_packs`, `languages`, `pages`, and `product_translations`.

## Stabilization fixes included

- Kept the Laravel 13 / PHP 8.5 dependency direction.
- Renamed the reset-password Blade file to match `auth.reset-password`.
- Added the missing inventory-lot show page and linked it from the lots index.
- Guarded the Stores dashboard so missing optional tables do not crash it.
- Added a schema-repair migration for missing handoff DB tables/columns.
- Normalized coupon handling around the SQL dump shape: `flat` / `percent`, `ends_at`, `usage_limit_per_user`.
- Fixed attribute-value edit/update/delete route names for Laravel shallow nested resource routes.
- Fixed Google Translate configuration to use `config('services.google_translate.key')` with `.env.example` placeholders.
- Added B2B customer product routes for the existing controller/views.
- Added the existing impersonation controller actions to routes and exposed the admin list action.
- Added the missing Admin Pages controller/model wiring for the existing pages views.
- Fixed storefront product links so `product.show` receives the slug parameter expected by `product/{product:slug}`.
- Cleaned handoff ignores for local artifacts and generated/cache files.

## Checks run in this environment

```bash
php artisan route:list --json
# Passed: 267 routes generated

php -l <changed PHP files>
# Passed for changed controllers, services, model, routes, and migrations

Blade compile/lint check
# Passed for 183 Blade files

Static controller/route view lookup
# Passed: 143 view references checked, 0 missing view files
```

`php artisan view:cache`, `php artisan test`, and full PHPUnit could not be completed in this sandbox because the PHP runtime here lacks required extensions including `dom`, `mbstring`, `xml`, and `xmlwriter`. Composer is also not installed in this sandbox, so run `composer install` / `composer update` again on the PHP 8.5 development environment before final commit.

## Required PHP extensions

Install the standard Laravel runtime/test extensions for PHP 8.5, including at least:

- `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `tokenizer`, `xml`, `xmlwriter`
- `pdo_sqlite` for the current in-memory PHPUnit configuration

## Handoff cleanup

The clean archives intentionally exclude local/runtime artifacts such as `.env`, `.git`, `vendor/`, `node_modules/`, `public/build/`, cached views, logs, generated `public/storage` symlink, `.DS_Store`, and `__MACOSX`.

Two clean archives are provided:

- Full stabilized package: source plus uploaded/public media assets plus `database/dumps/bandara_frozen.sql`.
- Source-only stabilized package: source plus `database/dumps/bandara_frozen.sql`, without uploaded/public media assets.

After using either archive, reinstall dependencies from lock files:

```bash
composer install
npm ci
```
