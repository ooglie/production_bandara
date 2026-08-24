# Bandara B2B Business Account Application — v1

This patch adds a controlled B2B account-application workflow without restoring the retired B2B product-request or order-request modules.

## What the module does

- Adds a public `/business-account` landing page.
- Lets a signed-in B2C customer complete a two-step business application.
- Keeps the customer as B2C while the application is pending.
- Supports draft, received, under review, additional information required, approved, rejected and withdrawn states.
- Preserves `more_information_required` when a customer merely saves edits; only **Submit for review** changes it back to submitted.
- Gives Admin and Manager users a review queue, filters, assignment, internal notes, information requests, rejection and approval controls.
- Converts `users.customer_type` only inside the approval transaction.
- Preserves the existing `Customer` role, login, addresses, orders, payments and historical Bandara Credit records.
- Creates a dedicated B2B customer profile for approved commercial terms.
- Mirrors commercial terms only into compatible existing columns/rows detected in the supplied August 23 database schema.
- Records a complete application audit timeline and sends database/email notifications when configured.

## Package layout

- `payload/` contains new Laravel files.
- `install.php` installs files, registers the service provider, optionally patches navigation and creates a timestamped backup/state record.
- `verify.php` performs PHP syntax, provider, route and database checks.
- `rollback.php` restores files from the recorded installation state. Database rollback is explicit and protected by a data-loss flag.
- `IMPLEMENTATION_AUDIT.md` records the schema/source assumptions used to build this patch.

## Before installation

1. Back up the current source and database.
2. Use the same current project represented by `Bandara_B2B_Application_Reference_20260823_111039.zip` and `bandarafrozen26.sql`.
3. Ensure the Laravel application can run normally with its configured PHP 8.5 executable.

## Install

Unzip this package anywhere, then run from Terminal:

```bash
cd "/Users/ooglie/Website/YOUR-CURRENT-BANDARA-PROJECT"
php "/path/to/Bandara_B2B_Business_Account_Application_v1_20260823/install.php" \
    --project="$PWD" \
    --migrate
```

For MAMP PHP, use the matching PHP executable, for example:

```bash
cd "/Users/ooglie/Website/YOUR-CURRENT-BANDARA-PROJECT"

"/Applications/MAMP/bin/php/php8.5.2/bin/php" \
    "/path/to/Bandara_B2B_Business_Account_Application_v1_20260823/install.php" \
    --project="$PWD" \
    --migrate
```

The installer:

1. Validates the Laravel project root.
2. Refuses to overwrite a different existing module file unless `--force` is supplied.
3. Copies the new files.
4. Registers `B2BApplicationServiceProvider` in `bootstrap/providers.php`.
5. Conservatively inserts small Blade includes into the best matching customer navigation and admin navigation files. Use `--skip-navigation` to disable this.
6. Saves all backups and installation metadata under:

```text
storage/app/bandara-patches/b2b-business-account/<timestamp>/
```

7. Clears Laravel caches.
8. With `--migrate`, runs the three migrations and processes Admin/Manager permissions.

## Verify

```bash
php "/path/to/Bandara_B2B_Business_Account_Application_v1_20260823/verify.php" \
    --project="$PWD"
```

You can also run the Laravel check directly:

```bash
php artisan bandara:b2b-applications:verify
```

Expected URLs:

```text
/business-account
/account/business-application
/admin/b2b-applications
```

## Approval behaviour

Approval is deliberately transactional. The application, approved profile and compatible commercial fields are updated together. The customer type is not changed on registration, draft save, submission, review or an information request.

Approval does **not**:

- Create a second user.
- Replace the customer’s current role.
- Delete B2C orders, addresses, invoices, payments or wallet history.
- Recreate `b2b_product_requests`, `b2b_order_requests` or their allocation tables.

The existing storefront rules should therefore begin treating the user as B2B only after approval, while historical data remains intact.

## Notifications

Database notifications are used when the existing `notifications` table is present. Email notifications are enabled by default and use the current Laravel mail configuration. To disable email notifications, set this in `config/b2b_application.php`:

```php
'notifications' => [
    'database' => true,
    'mail' => false,
],
```

WhatsApp sending is not hard-wired because the current source reference did not define one authoritative outbound WhatsApp service. The status workflow and notification data are ready for a listener to connect later.

## Navigation fallback

The installer patches navigation only when it finds a safe Blade insertion point. If it reports that no safe target was found, add these includes manually in the appropriate menus:

```blade
@include('partials.b2b-application.customer-nav-link')
```

```blade
@include('partials.b2b-application.admin-nav-link')
```

The module remains fully accessible through its URLs even when menu insertion is skipped.

## Rollback

File rollback only:

```bash
php "/path/to/Bandara_B2B_Business_Account_Application_v1_20260823/rollback.php" \
    --project="$PWD"
```

To also drop the three module tables, you must explicitly acknowledge data loss:

```bash
php "/path/to/Bandara_B2B_Business_Account_Application_v1_20260823/rollback.php" \
    --project="$PWD" \
    --database \
    --force-data-loss
```

Rollback will not overwrite a file that has been edited after installation unless `--force` is supplied. The state/backup folder is retained as an audit record.

## Automated tests included

```bash
php artisan test tests/Unit/B2BApplicationStatusTest.php
php artisan test tests/Feature/B2BApplicationWorkflowTest.php
```

The feature test covers the critical safeguards:

- Submission does not convert B2C to B2B.
- Saving requested information does not silently change the review state.
- Approval converts the customer and creates the approved B2B profile.
