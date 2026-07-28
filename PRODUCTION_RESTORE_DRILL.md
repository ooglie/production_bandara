# Bandara Frozen Backup Restore Drill

A checksum-valid archive is not yet a proven backup. This drill verifies that the database dump and uploaded files can recreate an operational application in an isolated environment.

## Safety rules

- Never perform a restore drill against the live production database or live storage directory.
- Use a dedicated isolated database, hostname, credentials, and storage path.
- Disable outbound mail, WhatsApp, payment capture, webhooks, and customer notifications in the drill environment.
- Do not expose restored customer data publicly. Restrict network access and delete the drill data afterward.

## 1. Choose and verify a backup

```bash
BACKUP=/var/backups/bandara/bandara-YYYYMMDDTHHMMSSZ
scripts/verify_production_backup.sh "$BACKUP"
cat "$BACKUP/MANIFEST.txt"
```

Record the backup timestamp, application commit, and person performing the drill.

## 2. Prepare an isolated database

Create a temporary database and a temporary local user with access only to it:

```sql
CREATE DATABASE bandarafrozen_restore_test
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER 'bandara_restore_test'@'127.0.0.1'
    IDENTIFIED BY 'TEMPORARY_RANDOM_PASSWORD';

GRANT ALL PRIVILEGES ON bandarafrozen_restore_test.*
    TO 'bandara_restore_test'@'127.0.0.1';
```

## 3. Restore the database

```bash
gzip -dc "$BACKUP/database.sql.gz" \
  | mysql --host=127.0.0.1 \
          --user=bandara_restore_test \
          --password \
          bandarafrozen_restore_test
```

Validate representative records and schema state:

```bash
mysql --host=127.0.0.1 --user=bandara_restore_test --password \
  --database=bandarafrozen_restore_test \
  --execute="SELECT COUNT(*) AS users FROM users; SELECT COUNT(*) AS products FROM products; SELECT COUNT(*) AS orders FROM orders; SELECT MAX(batch) AS migration_batch FROM migrations;"
```

## 4. Restore uploaded/private files

Use a disposable directory:

```bash
RESTORE_ROOT=/srv/bandara-restore-drill
sudo rm -rf "$RESTORE_ROOT"
sudo install -d -m 700 -o "$USER" -g "$USER" "$RESTORE_ROOT"
tar -xzf "$BACKUP/storage-app.tar.gz" -C "$RESTORE_ROOT"
```

Confirm important directories exist and are not unexpectedly empty:

```bash
find "$RESTORE_ROOT/storage/app" -maxdepth 2 -type d -print
find "$RESTORE_ROOT/storage/app" -type f | wc -l
```

## 5. Boot an isolated application copy

Use the application commit recorded in `MANIFEST.txt`. Configure a drill-only `.env`:

```dotenv
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://restore-drill.internal.example
DB_DATABASE=bandarafrozen_restore_test
DB_USERNAME=bandara_restore_test
DB_PASSWORD=TEMPORARY_RANDOM_PASSWORD
MAIL_MAILER=log
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
RAZORPAY_KEY_ID=
RAZORPAY_KEY_SECRET=
SECURITY_HSTS_ENABLED=false
BANDARA_BACKUP_REQUIRED=false
```

Use the production `APP_KEY` only inside this protected drill environment if encrypted application data must be verified. Do not distribute it or write it into the drill report.

Copy the restored `storage/app` into the isolated application storage path, fix ownership, then run:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan optimize:clear
php artisan migrate:status
php artisan storage:link
php artisan test
```

Do not run migrations unless the drill specifically tests an upgrade path. A same-version restore should already match the migration state recorded in the backup.

## 6. Functional verification

At minimum verify:

- Application boots and `/up` succeeds.
- Users, roles, products, variants, inventory lots, orders, invoices, payments, and migrations have plausible counts.
- Product and category images render.
- Private ticket attachments can be downloaded only by an authorized user.
- A representative invoice PDF renders.
- Recent order and inventory records can be opened.
- No outbound notification/payment integration was triggered.

For encrypted or signed data, verify representative records can be read using the preserved application key.

## 7. Measure recovery objectives

Record:

- Time from starting the restore to database availability.
- Time to restore uploaded files.
- Time until the application passes smoke tests.
- Backup age and estimated data-loss window.
- Missing files, errors, manual steps, and documentation corrections.

These measurements establish actual recovery time and recovery point performance rather than assumed values.

## 8. Destroy the drill environment

After approval:

```sql
DROP DATABASE bandarafrozen_restore_test;
DROP USER 'bandara_restore_test'@'127.0.0.1';
```

Securely remove restored files and temporary environment files. Confirm that no drill hostname, database credentials, production key, or restored customer data remains accessible.

## 9. Acceptance criteria

The drill passes only when:

- All hashes and archive integrity checks pass.
- Database import completes without errors.
- Required uploaded/private files restore.
- Application boots with the matching release.
- Core records and protected files are usable.
- No production integrations are contacted.
- Recovery time and issues are documented.
