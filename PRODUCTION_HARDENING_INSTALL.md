# Bandara Frozen Production Hardening Installation

This package adds production environment guidance, secure runtime checks, queue and scheduler templates, database least-privilege templates, automated backups, restore verification, and hardened Nginx/Apache/PHP examples.

## 1. Important rules before starting

- Perform this first on staging or a new production server.
- Keep the current production `APP_KEY`. Do not generate a new key for an existing installation unless a planned key-rotation project is being performed. Changing it can invalidate encrypted data, cookies, and sessions.
- Do not copy `.env.production.example` over an existing `.env`. Merge the settings and preserve all validated business configuration.
- Replace every `CHANGE_ME`, hostname, path, username, socket, and certificate placeholder.
- Keep populated environment files, MySQL option files, and backup configuration outside source control.
- Take and verify a backup before running migrations or switching traffic.

## 2. Install the code package

From the project root:

```bash
unzip Bandara_production_runtime_hardening_20260717.zip -d /var/www/bandara/current
cd /var/www/bandara/current
chmod +x scripts/*.sh
php artisan optimize:clear
```

Run the application test and dependency checks on the build/staging machine:

```bash
composer validate --strict
composer audit --locked
npm audit
npm run build
php artisan test
```

## 3. Configure the production environment

Use `.env.production.example` as a checklist. For an existing installation, edit the existing `.env` and preserve its `APP_KEY`.

Minimum production values include:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://shop.your-domain.example

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

CACHE_STORE=database
QUEUE_CONNECTION=database
QUEUE_AFTER_COMMIT=true
FILESYSTEM_DISK=local

SECURITY_TRUSTED_HOSTS=shop.your-domain.example
SECURITY_REDIRECT_HOSTS=shop.your-domain.example
SECURITY_TRUSTED_PROXIES=
SECURITY_HEADERS_ENABLED=true
SECURITY_HSTS_ENABLED=true

BANDARA_BACKUP_REQUIRED=true
BANDARA_BACKUP_DIRECTORY=/var/backups/bandara
```

Set `SECURITY_TRUSTED_PROXIES` only when a known load balancer or reverse proxy terminates TLS. Use exact IP addresses or CIDR ranges; never use `*`.

Enable HSTS only after the HTTPS certificate, redirect, domain, and all required subresources have been tested. The supplied default does not include `includeSubDomains`.

The package moves Google Translate and stock-reservation settings behind Laravel configuration so they remain available after configuration caching.

## 4. Create least-privilege MySQL accounts

Edit:

```text
deploy/mysql/create-production-users.sql
```

Replace the database name, host, usernames, and passwords. Run it as a database administrator.

Use:

- `bandara_app` for normal web, worker, and scheduler traffic.
- `bandara_deploy` only while running migrations.
- `bandara_backup` only for the backup script.

Do not use MySQL `root` as the application account.

After migration, restore the application `.env` to the runtime account immediately.

## 5. Install the web server and PHP-FPM configuration

Choose one web-server template:

```text
deploy/nginx/bandara.conf.example
deploy/apache/bandara-vhost.conf.example
```

The document root must be the Laravel `public` directory—not the repository root.

Install and adjust:

```text
deploy/php/99-bandara-production.ini.example
```

Restart PHP-FPM and the chosen web server after validating their configuration:

```bash
sudo php-fpm8.5 -t
sudo nginx -t
sudo systemctl restart php8.5-fpm nginx
```

For Apache, use its platform-specific configuration test and restart commands.

## 6. Apply filesystem permissions

Review the service user and group inside the script, then run as root:

```bash
sudo scripts/set_production_permissions.sh /var/www/bandara/current bandara www-data
scripts/check_production_permissions.sh /var/www/bandara/current
```

Expected principles:

- Source files are not writable by the web-server process.
- Only `storage` and `bootstrap/cache` are writable.
- `.env` is not accessible to other users and is not group-writable.
- The web server exposes only `public/`.

## 7. Configure queue workers

Edit and install:

```text
deploy/supervisor/bandara-worker.conf.example
```

Then:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status bandara-worker:*
```

After every deployment:

```bash
php artisan queue:restart
```

The worker timeout must remain shorter than the queue connection's `retry_after` value.

## 8. Configure the scheduler

Use exactly one scheduler mechanism.

### Cron

Install the entry from:

```text
deploy/cron/bandara-scheduler.example
```

### systemd timer

Install both:

```text
deploy/systemd/bandara-scheduler.service.example
deploy/systemd/bandara-scheduler.timer.example
```

Then:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now bandara-scheduler.timer
systemctl list-timers | grep bandara
```

Do not enable both cron and the systemd scheduler timer.

## 9. Configure backups

Create protected configuration files:

```bash
sudo install -d -m 700 -o bandara -g bandara /etc/bandara
sudo install -m 600 -o bandara -g bandara deploy/mysql/backup.env.example /etc/bandara/backup.env
sudo install -m 600 -o bandara -g bandara deploy/mysql/mysql-backup.cnf.example /etc/bandara/mysql-backup.cnf
sudo install -d -m 700 -o bandara -g bandara /var/backups/bandara
```

Edit both files and replace all placeholders. Run the first backup manually:

```bash
sudo -u bandara BANDARA_BACKUP_CONFIG=/etc/bandara/backup.env scripts/backup_production.sh
sudo -u bandara scripts/verify_production_backup.sh /var/backups/bandara/latest
```

Install the systemd backup service and timer:

```bash
sudo cp deploy/systemd/bandara-backup.service.example /etc/systemd/system/bandara-backup.service
sudo cp deploy/systemd/bandara-backup.timer.example /etc/systemd/system/bandara-backup.timer
sudo systemctl daemon-reload
sudo systemctl enable --now bandara-backup.timer
```

Copy backups to a separate encrypted off-site system. A backup stored only on the application server is not sufficient protection against host failure or compromise.

Follow `PRODUCTION_RESTORE_DRILL.md` on an isolated host before release and periodically afterward.

## 10. Deployment sequence

A safe release sequence is:

```bash
cd /var/www/bandara/current

# 1. Verify a fresh backup exists before changes.
scripts/verify_production_backup.sh /var/backups/bandara/latest

# 2. Install production PHP dependencies.
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

# 3. Deploy already-built public/build assets, or build on a controlled build host.
# npm ci && npm run build

# 4. Enter maintenance mode immediately before schema changes.
php artisan down --retry=60

# 5. Temporarily use the deploy DB account, then run migrations.
php artisan migrate --force

# 6. Restore the runtime DB account in .env and clear stale config.
php artisan config:clear

# 7. Ensure public media link exists and cache production artifacts.
php artisan storage:link
php artisan optimize

# 8. Tell long-running processes to reload code.
php artisan queue:restart
php artisan schedule:interrupt

# 9. Return service and test the HTTPS health endpoint.
php artisan up
curl --fail --silent --show-error https://shop.your-domain.example/up
```

When using symlinked releases, update template paths and confirm workers resolve the current release after `queue:restart`.

## 11. Final production audit

Run after the first verified backup and after config caching:

```bash
BANDARA_HEALTH_URL=https://shop.your-domain.example/up \
BANDARA_PREFLIGHT_RUN_TESTS=false \
scripts/production_preflight.sh /var/www/bandara/current
```

The command intentionally fails for insecure settings, missing runtime tables, stale/missing backups, unsafe permissions, or an unavailable health endpoint.

## 12. Administrator and session cleanup

Ensure `ADMIN_PASSWORD` is blank after provisioning or rotation. Then invalidate sessions created before security hardening:

```bash
php artisan session:flush --force
```

Migrate any historical public ticket attachments:

```bash
php artisan security:migrate-ticket-attachments
```

## 13. Rollback readiness

Before traffic is switched, record:

- Previous release path or Git commit.
- Database backup directory.
- Uploaded-file backup directory.
- Exact migration batch being deployed.
- Person responsible for rollback approval.

Application code can normally be rolled back quickly. Database rollback must be evaluated migration by migration; never run `migrate:rollback` blindly on production data.
