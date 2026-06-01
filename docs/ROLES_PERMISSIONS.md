# Bandara roles and permissions normalization

## Problem found

The database uses Spatie's standard permission tables, which is correct structurally:

- `roles`
- `permissions`
- `model_has_roles`
- `model_has_permissions`
- `role_has_permissions`

The issue was not the table structure. The issue was that role/permission data and application checks were inconsistent:

- `CAAccountant`, `CA-Accountant`, and `Account` were used in different places.
- Support ticket code checked lowercase roles such as `support`, `manager`, and `admin`, while actual roles are title case.
- The role-permission matrix did not contain every permission used by the UI/routes.
- Admin did not consistently include all permissions once newer permissions were added.
- Stores/Content navigation was role-driven in places instead of permission-driven.
- The roles seeder only defined the older minimal permission set.

## Canonical roles

These are now the canonical roles:

| Role | Purpose |
|---|---|
| `Admin` | Full system access. Always synced to every permission. |
| `Manager` | Operations manager for catalog, orders, customers, vendors, stores, marketing, rewards and reports. |
| `Support` | Ticket operations with customer/order context. |
| `Accountant` | Finance operations: invoices, payments, vendor payments, reports and order/customer context. |
| `CAAccountant` | CA/accounting view access for invoices, payments, reports and order/customer context. |
| `Stores` | Stores/inventory/production and vendor invoice support. |
| `Customer` | Frontend customer role; no back-office permissions. |

Legacy aliases are merged into these roles:

- `CA-Accountant` -> `CAAccountant`
- `CA Accountant` -> `CAAccountant`
- `Account` -> `Accountant`
- lowercase `admin`, `manager`, `support`, `accountant`, `stores`, `customer` -> canonical title-case names

## Permission naming

Permission names intentionally remain in the current app format:

```text
view products
manage products
view orders
manage orders
...
```

This avoids a risky broad rewrite to dot-style names such as `products.view` while keeping all existing `@can`, `can:`, and Spatie checks compatible.

## Canonical permission groups

The matrix is now centralized in:

```text
config/fb_permissions.php
```

The matrix includes these modules:

```text
products
orders
invoices
customers
vendors
coupons
payments
stores
tickets
marketing
content
rewards
users
settings
reports
```

Legacy permissions retained for compatibility:

```text
create vendor invoice
manage vendor payments
manage sales
```

## Data repair

The normalization migration is:

```text
database/migrations/2026_05_20_000002_normalize_roles_permissions.php
```

It safely:

1. Creates any missing canonical permissions.
2. Creates any missing canonical roles.
3. Merges legacy role aliases into canonical roles.
4. Moves user role assignments from alias roles to canonical roles.
5. Moves role-permission rows from alias roles to canonical roles.
6. Re-syncs role permissions from the canonical map.
7. Flushes the Spatie permission cache.

## Seeder repair

The seeder now reads the same central config:

```text
database/seeders/RolesAndPermissionsSeeder.php
```

Use it for fresh installs or for manually re-syncing access control:

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan permission:cache-reset
```

## Apply steps

After applying this patch/package:

```bash
php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan permission:cache-reset
```

If `permission:cache-reset` is unavailable in your installed Spatie version, run:

```bash
php artisan cache:forget spatie.permission.cache
php artisan optimize:clear
```

## Expected verification

```bash
php artisan tinker
```

```php
Spatie\Permission\Models\Role::with('permissions')->orderBy('name')->get(['id','name']);
Spatie\Permission\Models\Permission::orderBy('name')->pluck('name');
```

Expected:

- `Admin` has all permissions.
- `Manager` has broad operational access but not `manage users` or `manage settings`.
- `Support` has tickets plus view customer/order context.
- `Accountant` has finance/report permissions.
- `CAAccountant` exists with no hyphen and receives accounting view permissions.
- `Stores` has stores/inventory and vendor invoice permissions.
- `Customer` has no back-office permissions.
- No `CA-Accountant`, `Account`, lowercase `support`, lowercase `manager`, or lowercase `admin` roles remain.
