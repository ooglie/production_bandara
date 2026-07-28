# Production Runtime Hardening Summary

## Application configuration

- Added exact trusted-proxy configuration.
- Expanded `security:audit-config` into a production release gate covering HTTPS, keys, sessions, hosts, proxies, headers, queues, cache, filesystems, logging, database account use, mail, runtime tables, permissions, and backup freshness.
- Added durable queue `after_commit` configuration.
- Added production scheduler jobs for stock-reservation cleanup, rewards maintenance, password reset cleanup, and queue metadata pruning.
- Removed runtime `env()` calls from Auto Translation, Stock Reservation, and Admin provisioning so these flows continue working after `php artisan optimize` / configuration caching.
- Added unit coverage for translation and stock-reservation configuration.

## Server templates

- Nginx and Apache virtual host examples rooted only at `public/`.
- PHP 8.5 FPM production overrides.
- Supervisor queue worker configuration.
- Cron and systemd scheduler alternatives.
- Logrotate example.
- MySQL least-privilege runtime/deploy/backup users.

## Backups and operational checks

- Atomic database and `storage/app` backup script.
- SHA-256 and archive verification.
- Backup freshness marker checked by the production audit.
- Owner-only backup credentials and configuration enforcement.
- systemd backup service/timer examples.
- Restore-drill procedure and release checklist.
- Filesystem permission apply/check scripts.
- Non-mutating production preflight script.

## Important

The supplied server files are templates. Replace every hostname, path, certificate, socket, database name, account, and `CHANGE_ME` value before installation. Preserve the existing production `APP_KEY`.
