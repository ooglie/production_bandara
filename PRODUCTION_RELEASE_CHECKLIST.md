# Bandara Frozen Production Release Checklist

Use this checklist for each production release. Mark every item with the person and timestamp responsible.

## Build and source integrity

- [ ] Release is built from the approved Git commit/source baseline.
- [ ] `composer validate --strict` passes.
- [ ] `composer audit --locked` reports no advisories.
- [ ] `npm audit` reports no actionable vulnerabilities.
- [ ] `npm run build` succeeds.
- [ ] `php artisan test` passes completely.
- [ ] No `.env`, SQL dump, backup, private key, or credential is inside the release archive or `public/`.

## Environment and secrets

- [ ] Existing production `APP_KEY` is preserved.
- [ ] `APP_ENV=production` and `APP_DEBUG=false`.
- [ ] `APP_URL` uses the final HTTPS hostname.
- [ ] `ADMIN_PASSWORD` is blank.
- [ ] Razorpay, mail, Google, database, and other credentials are production values and not shared development secrets.
- [ ] Secrets are readable only by the intended application/deployment users.

## Network and HTTP

- [ ] Web-server document root ends in `/public`.
- [ ] HTTP redirects to HTTPS for the exact production hostname.
- [ ] Unknown hosts are rejected.
- [ ] TLS certificate chain and renewal are working.
- [ ] `SECURITY_TRUSTED_HOSTS` and `SECURITY_REDIRECT_HOSTS` contain exact intended hosts only.
- [ ] `SECURITY_TRUSTED_PROXIES` is blank or contains exact proxy IP/CIDR values—never `*`.
- [ ] HSTS was enabled only after HTTPS was tested.
- [ ] Report-only CSP was tested with Razorpay, Google integrations, media, and dark/mobile UI.

## Sessions and account controls

- [ ] Secure, HTTP-only, encrypted, SameSite cookies are enabled.
- [ ] Database/Redis sessions and cache are available.
- [ ] Inactive users cannot log in.
- [ ] Staff roles were manually checked against direct Admin URLs.
- [ ] Pre-hardening sessions were invalidated when required.

## Database

- [ ] Runtime application account is not root and cannot alter schema.
- [ ] Deploy account is used only during migration.
- [ ] Backup account is read-only.
- [ ] A verified backup was completed immediately before migration.
- [ ] Migration output was reviewed and `php artisan migrate:status` is correct.
- [ ] Runtime account was restored after migration.

## Files and uploads

- [ ] `storage` and `bootstrap/cache` are writable by the application.
- [ ] Source files and `.env` are not writable by the web process.
- [ ] Public storage link points only to `storage/app/public`.
- [ ] Ticket attachments are private and require an authorized download route.
- [ ] Old public ticket attachments were migrated.

## Queue and scheduler

- [ ] Supervisor workers are running under the intended user.
- [ ] Worker timeout is lower than queue `retry_after`.
- [ ] `php artisan queue:restart` was run after deployment.
- [ ] Exactly one scheduler mechanism is active: cron or systemd timer.
- [ ] `php artisan schedule:list` shows stock-release, rewards, password-reset, and queue-pruning jobs.
- [ ] Scheduler and worker logs show no repeated failures.

## Backups

- [ ] Database and `storage/app` are included.
- [ ] Backup checksum verification succeeds.
- [ ] `.last-success` is fresh enough for the production audit.
- [ ] Backup credentials/config files have owner-only permissions.
- [ ] A copy exists off the application server in encrypted storage.
- [ ] A restore drill has succeeded within the organisation's required interval.

## Application smoke tests

- [ ] `/up` returns success over HTTPS.
- [ ] Guest, B2C, B2B, Admin, Manager, Accountant, CA Accountant, Stores, Support, and DeliveryBoy sign-in/authorization paths were sampled.
- [ ] B2B retail-only products remain hidden.
- [ ] B2B rewards remain hidden.
- [ ] B2C credit redemption applies and removes correctly.
- [ ] Product search works on mobile, tablet, and desktop.
- [ ] Cart, address switching, checkout, Razorpay success/failure, order, invoice, and PDF flows work.
- [ ] Private ticket attachment upload/download works.
- [ ] Production Run reversal blocks changed/downstream-used outputs.
- [ ] Transform Stock piece-carton and multi-output flow works.

## Final gate

- [ ] `scripts/production_preflight.sh` passes.
- [ ] Monitoring/alerts and log rotation are active.
- [ ] Rollback release and backup paths are recorded.
- [ ] Release owner approves traffic switch.
