# Bandara Frozen security hardening — installation

Baseline used: the July 12, 2026 Bandara Frozen source plus the accepted fixes through July 17, 2026.

This package is an additive security hardening overlay. It does not add a database migration, change storefront pricing, alter product/inventory workflows, or enable a restrictive Content Security Policy that could break Razorpay or existing inline scripts.

## 1. Back up first

Back up the application files, database, `.env`, private storage and public storage before copying the package.

## 2. Copy the package

Extract the ZIP directly into the Laravel project root so that `app/`, `bootstrap/`, `config/`, `database/`, `resources/`, `routes/`, `tests/` and `scripts/` merge into the existing folders.

Then clear stale framework state:

```bash
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
```

No `php artisan migrate` is required by this package.

## 3. Configure production security

Use the actual production hostnames—never copy the localhost values below into production:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://frozen.example.com

SESSION_HTTP_ONLY=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SESSION_ENCRYPT=true

SECURITY_HEADERS_ENABLED=true
SECURITY_TRUSTED_HOSTS=frozen.example.com,www.frozen.example.com
SECURITY_REDIRECT_HOSTS=frozen.example.com,www.frozen.example.com
SECURITY_HSTS_ENABLED=false
SECURITY_HSTS_VALUE="max-age=31536000; includeSubDomains"
SECURITY_CSP_REPORT_ONLY=
```

Keep HSTS disabled for the first verified HTTPS deployment. Enable it only after every listed hostname works exclusively over HTTPS.

The optional CSP setting is report-only. Do not enable an enforced CSP until Razorpay, images, fonts and the existing inline scripts have been inventoried and tested.

## 4. Rotate the administrator account

The old seeder fallback credentials have been removed. `DatabaseSeeder` no longer creates an administrator automatically.

Clear config cache before the one-time rotation so the seeder can read the temporary environment values:

```bash
php artisan config:clear
```

Temporarily set:

```dotenv
ADMIN_NAME="Bandara Administrator"
ADMIN_EMAIL="your-private-admin-email@example.com"
ADMIN_PASSWORD="a-new-unique-password-of-at-least-12-characters"
ADMIN_PROMOTE_EXISTING_USER=false
```

If the email already belongs to a non-Admin account, the seeder refuses to promote it unless `ADMIN_PROMOTE_EXISTING_USER=true` is set deliberately after verifying the identity.

Then run:

```bash
php artisan db:seed --class=AdminUserSeeder
```

Immediately remove `ADMIN_PASSWORD` from `.env`, clear configuration again, and rotate any other account that previously used a known/default password:

```bash
php artisan config:clear
```

The seeder rotates the password, clears the remember token and invalidates database-backed sessions for that administrator.

## 5. Move legacy ticket attachments to private storage

New ticket attachments are already stored privately. Move old files that were previously exposed through `storage/`:

```bash
php artisan security:migrate-ticket-attachments
```

The command reports missing files and exits with failure if any database attachment cannot be located. Review those records rather than ignoring the warning.

## 6. Invalidate existing sessions

For the default database session driver:

```bash
php artisan session:flush --force
```

The command intentionally refuses to run `FLUSHDB` for Redis because a shared Redis database may also contain cache, queue or unrelated application data. Clear only the session-key namespace when Redis is used, or use an isolated Redis database for sessions.

## 7. Upgrade vulnerable frontend dependencies

The supplied `package-lock.json` still contains known vulnerable build dependencies. The lockfile could not be regenerated in the review environment because its internal registry did not provide Tailwind's optional WASM package.

Run the supplied script on the normal development/build machine with public npm registry access:

```bash
bash scripts/update_frontend_security_dependencies.sh
```

Commit the resulting `package.json` and `package-lock.json`, then verify:

```bash
npm audit
npm run build
```

Do not deploy while `npm audit` still reports critical or high vulnerabilities without documenting and accepting the exact residual risk.

## 8. Audit PHP dependencies

The review environment did not contain Composer. Run this in the PHP 8.5 deployment or CI environment:

```bash
composer audit --locked
composer validate --strict
```

Resolve critical/high advisories and rebuild the production vendor directory from the committed lockfile:

```bash
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
```

## 9. Run application checks

```bash
php artisan security:audit-config --production
php artisan test --filter=SecurityHardeningTest
php artisan test --filter=SafeRedirectTest
php artisan test
```

The production config audit fails for debug mode, HTTP APP_URL, insecure cookies, missing APP_KEY, missing/mismatched trusted hosts, wildcard trusted hosts, or an administrator password left in the environment.

## 10. Cache only after validation

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Then run the production config audit once more:

```bash
php artisan security:audit-config --production
```

## 11. Server-level checks still required

Application code cannot configure all production controls. Verify separately:

- TLS certificate and HTTPS redirect
- trusted reverse-proxy headers
- Nginx/Apache denial of `.env`, Git, backups and source archives
- PHP `display_errors=Off`
- least-privilege database account
- storage/cache ownership and non-world-writable permissions
- firewall and SSH policy
- automated encrypted backups and restore testing
- queue worker isolation and process supervision
- log access/rotation and alerting
- malware scanning or content inspection policy for business documents

## Rollback

Restore the backed-up application files and clear caches. Ticket files moved from public to private storage are not automatically moved back; retaining them privately is safe even if the code overlay is rolled back, but old views would need the authorized download route to access them.
