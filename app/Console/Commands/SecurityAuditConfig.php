<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SecurityAuditConfig extends Command
{
    protected $signature = 'security:audit-config
        {--production : Enforce production release requirements}
        {--skip-runtime : Skip filesystem, database-table, and backup freshness checks}';

    protected $description = 'Check application and runtime configuration for common insecure deployment settings';

    public function handle(): int
    {
        $production = $this->option('production') || app()->environment('production');
        $skipRuntime = (bool) $this->option('skip-runtime');
        $failures = [];
        $warnings = [];

        $appUrl = trim((string) config('app.url'));
        $appScheme = strtolower((string) parse_url($appUrl, PHP_URL_SCHEME));
        $appHost = strtolower((string) parse_url($appUrl, PHP_URL_HOST));
        $appPath = (string) parse_url($appUrl, PHP_URL_PATH);
        $trustedHosts = array_map('strtolower', (array) config('security.trusted_hosts', []));
        $redirectHosts = array_map('strtolower', (array) config('security.redirect_hosts', []));
        $trustedProxies = config('trustedproxy.proxies');

        if ($production && (bool) config('app.debug')) {
            $failures[] = 'APP_DEBUG must be false in production.';
        }

        if ($production && app()->environment() !== 'production') {
            $failures[] = 'APP_ENV must be production for a production release.';
        }

        $this->checkApplicationKey($failures);

        if ($production && $appScheme !== 'https') {
            $failures[] = 'APP_URL must use HTTPS in production.';
        }

        if ($appHost === '') {
            $failures[] = 'APP_URL must contain a valid hostname.';
        }

        if ($production && ! in_array($appPath, ['', '/'], true)) {
            $warnings[] = 'APP_URL contains a path. Deploying Laravel at the domain root is safer and less error-prone.';
        }

        $this->checkSessions($production, $failures, $warnings);
        $this->checkHosts($production, $appHost, $trustedHosts, $redirectHosts, $failures);
        $this->checkProxies($trustedProxies, $failures, $warnings);
        $this->checkSecurityHeaders($production, $failures, $warnings);
        $this->checkOperationalDrivers($production, $failures, $warnings);
        $this->checkDatabaseConfiguration($production, $failures, $warnings);
        $this->checkMailConfiguration($production, $failures, $warnings);

        if (trim((string) config('security.administrator_provisioning.password')) !== '') {
            $failures[] = 'Remove ADMIN_PASSWORD from the environment after administrator provisioning or rotation.';
        }

        if (! $skipRuntime) {
            $this->checkFilesystemRuntime($production, $failures, $warnings);
            $this->checkRequiredTables($production, $failures, $warnings);
            $this->checkBackupRuntime($production, $failures, $warnings);
        }

        foreach ($warnings as $warning) {
            $this->warn($warning);
        }

        foreach ($failures as $failure) {
            $this->error($failure);
        }

        if ($failures !== []) {
            $this->error(sprintf(
                'Security configuration audit failed with %d issue(s) and %d warning(s).',
                count($failures),
                count($warnings)
            ));

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Security configuration audit passed with %d warning(s).',
            count($warnings)
        ));

        return self::SUCCESS;
    }

    /** @param array<int, string> $failures */
    private function checkApplicationKey(array &$failures): void
    {
        $configuredKey = trim((string) config('app.key'));

        if ($configuredKey === '') {
            $failures[] = 'APP_KEY is empty.';
            return;
        }

        $rawKey = $configuredKey;
        if (str_starts_with($configuredKey, 'base64:')) {
            $decoded = base64_decode(substr($configuredKey, 7), true);
            if ($decoded === false) {
                $failures[] = 'APP_KEY is not valid base64.';
                return;
            }
            $rawKey = $decoded;
        }

        if ((string) config('app.cipher') === 'AES-256-CBC' && strlen($rawKey) !== 32) {
            $failures[] = 'APP_KEY must contain 32 random bytes for AES-256-CBC.';
        }
    }

    /**
     * @param array<int, string> $failures
     * @param array<int, string> $warnings
     */
    private function checkSessions(bool $production, array &$failures, array &$warnings): void
    {
        if ($production && config('session.secure') !== true) {
            $failures[] = 'SESSION_SECURE_COOKIE must be true in production.';
        }

        if (config('session.http_only') !== true) {
            $failures[] = 'SESSION_HTTP_ONLY must be true.';
        }

        if (! in_array(config('session.same_site'), ['lax', 'strict'], true)) {
            $failures[] = 'SESSION_SAME_SITE must be lax or strict unless a documented cross-site flow requires none.';
        }

        if ($production && config('session.encrypt') !== true) {
            $failures[] = 'SESSION_ENCRYPT must be true in production.';
        }

        $driver = (string) config('session.driver');
        if (in_array($driver, ['array', 'cookie'], true)) {
            $failures[] = 'SESSION_DRIVER must not be array or cookie in production.';
        } elseif ($production && ! in_array($driver, ['database', 'redis'], true)) {
            $warnings[] = 'SESSION_DRIVER should normally be database or redis in production.';
        }
    }

    /**
     * @param array<int, string> $trustedHosts
     * @param array<int, string> $redirectHosts
     * @param array<int, string> $failures
     */
    private function checkHosts(
        bool $production,
        string $appHost,
        array $trustedHosts,
        array $redirectHosts,
        array &$failures
    ): void {
        if ($trustedHosts === []) {
            $failures[] = 'SECURITY_TRUSTED_HOSTS is empty.';
        } elseif ($appHost !== '' && ! in_array($appHost, $trustedHosts, true)) {
            $failures[] = 'SECURITY_TRUSTED_HOSTS must include the APP_URL hostname.';
        }

        if ($redirectHosts === []) {
            $failures[] = 'SECURITY_REDIRECT_HOSTS is empty.';
        } elseif ($appHost !== '' && ! in_array($appHost, $redirectHosts, true)) {
            $failures[] = 'SECURITY_REDIRECT_HOSTS must include the APP_URL hostname.';
        }

        foreach (array_unique(array_merge($trustedHosts, $redirectHosts)) as $host) {
            if (
                $host === ''
                || str_contains($host, '*')
                || str_contains($host, '/')
                || str_contains($host, '\\')
                || filter_var('https://'.$host, FILTER_VALIDATE_URL) === false
            ) {
                $failures[] = 'Trusted and redirect host lists must contain exact hostnames only.';
                break;
            }
        }

        if ($production) {
            foreach (['localhost', '127.0.0.1', '::1'] as $localHost) {
                if (in_array($localHost, $trustedHosts, true) || in_array($localHost, $redirectHosts, true)) {
                    $failures[] = 'Remove localhost and loopback addresses from production trusted/redirect host lists.';
                    break;
                }
            }
        }
    }

    /**
     * @param array<int, string> $failures
     * @param array<int, string> $warnings
     */
    private function checkProxies(mixed $trustedProxies, array &$failures, array &$warnings): void
    {
        if ($trustedProxies === '*' || $trustedProxies === '**') {
            $failures[] = 'SECURITY_TRUSTED_PROXIES must not trust every proxy. Use exact IP addresses or CIDR ranges.';
            return;
        }

        if (is_array($trustedProxies)) {
            foreach ($trustedProxies as $proxy) {
                if (! is_string($proxy) || trim($proxy) === '') {
                    $failures[] = 'SECURITY_TRUSTED_PROXIES contains an invalid entry.';
                    return;
                }
            }
        } elseif ($trustedProxies !== null) {
            $warnings[] = 'SECURITY_TRUSTED_PROXIES should be empty or an exact comma-separated IP/CIDR list.';
        }
    }

    /**
     * @param array<int, string> $failures
     * @param array<int, string> $warnings
     */
    private function checkSecurityHeaders(bool $production, array &$failures, array &$warnings): void
    {
        if (! config('security.headers.enabled')) {
            $failures[] = 'SECURITY_HEADERS_ENABLED must be true.';
        }

        if ($production && ! config('security.headers.hsts_enabled')) {
            $failures[] = 'SECURITY_HSTS_ENABLED must be true after HTTPS is verified.';
        }

        $hsts = strtolower((string) config('security.headers.hsts_value'));
        if ($production && ! str_contains($hsts, 'max-age=')) {
            $failures[] = 'SECURITY_HSTS_VALUE must include max-age.';
        }

        if (trim((string) config('security.headers.csp_report_only')) === '') {
            $warnings[] = 'SECURITY_CSP_REPORT_ONLY is blank. Introduce a tested report-only CSP before enforcing one.';
        }
    }

    /**
     * @param array<int, string> $failures
     * @param array<int, string> $warnings
     */
    private function checkOperationalDrivers(bool $production, array &$failures, array &$warnings): void
    {
        $queue = (string) config('queue.default');
        if ($production && in_array($queue, ['sync', 'null', 'background', 'deferred'], true)) {
            $failures[] = 'QUEUE_CONNECTION must use a durable production backend such as database, redis, or SQS.';
        }

        $queueConfig = (array) config('queue.connections.'.$queue, []);
        if ($production && array_key_exists('after_commit', $queueConfig) && $queueConfig['after_commit'] !== true) {
            $failures[] = 'QUEUE_AFTER_COMMIT must be true in production.';
        }

        $cache = (string) config('cache.default');
        if (in_array($cache, ['array', 'null'], true)) {
            $failures[] = 'CACHE_STORE must be persistent so queue restart and scheduler locks work.';
        } elseif ($production && ! in_array($cache, ['database', 'redis', 'memcached'], true)) {
            $warnings[] = 'CACHE_STORE should normally be database, redis, or memcached in production.';
        }

        if ((string) config('filesystems.default') === 'public') {
            $failures[] = 'FILESYSTEM_DISK must not default to the public disk.';
        }

        $localRoot = (string) config('filesystems.disks.local.root');
        if (! str_starts_with($this->normalisePath($localRoot), $this->normalisePath(storage_path('app/private')))) {
            $failures[] = 'The local filesystem disk must remain under storage/app/private.';
        }

        if ($production && strtolower((string) config('logging.channels.single.level')) === 'debug') {
            $warnings[] = 'The single log channel is configured at debug level.';
        }

        if ($production && (
            strtolower((string) config('logging.channels.single.level')) === 'debug'
            || strtolower((string) config('logging.channels.daily.level')) === 'debug'
        )) {
            $failures[] = 'LOG_LEVEL must not be debug in production.';
        }
    }

    /**
     * @param array<int, string> $failures
     * @param array<int, string> $warnings
     */
    private function checkDatabaseConfiguration(bool $production, array &$failures, array &$warnings): void
    {
        $connection = (string) config('database.default');
        $database = (array) config('database.connections.'.$connection, []);
        $username = strtolower(trim((string) ($database['username'] ?? '')));
        $databaseName = trim((string) ($database['database'] ?? ''));
        $host = strtolower(trim((string) ($database['host'] ?? '')));

        if ($production && in_array($connection, ['sqlite'], true)) {
            $warnings[] = 'SQLite is not recommended for this production transactional workload.';
        }

        if ($username === '' || in_array($username, ['root', 'admin', 'administrator'], true)) {
            $failures[] = 'DB_USERNAME must be a dedicated least-privilege application account, not root/admin.';
        }

        if ($databaseName === '') {
            $failures[] = 'DB_DATABASE is empty.';
        }

        if (
            $production
            && in_array($connection, ['mysql', 'mariadb'], true)
            && ! in_array($host, ['localhost', '127.0.0.1', '::1', ''], true)
            && empty($database['options'][\PDO\Mysql::ATTR_SSL_CA] ?? null)
        ) {
            $warnings[] = 'Remote MySQL is configured without MYSQL_ATTR_SSL_CA. Confirm encrypted transport separately.';
        }
    }

    /**
     * @param array<int, string> $failures
     * @param array<int, string> $warnings
     */
    private function checkMailConfiguration(bool $production, array &$failures, array &$warnings): void
    {
        if (! $production) {
            return;
        }

        $mailer = strtolower((string) config('mail.default'));
        if (in_array($mailer, ['log', 'array'], true)) {
            $failures[] = 'MAIL_MAILER must use a real production transport.';
        }

        $from = strtolower(trim((string) config('mail.from.address')));
        if ($from === '' || str_ends_with($from, '@example.com')) {
            $failures[] = 'MAIL_FROM_ADDRESS must be a real verified production sender.';
        }

        if (! filter_var($from, FILTER_VALIDATE_EMAIL)) {
            $failures[] = 'MAIL_FROM_ADDRESS is invalid.';
        }

        if ($mailer === 'sendmail') {
            $warnings[] = 'Sendmail is enabled. SMTP/API transports are easier to isolate and monitor.';
        }
    }

    /**
     * @param array<int, string> $failures
     * @param array<int, string> $warnings
     */
    private function checkFilesystemRuntime(bool $production, array &$failures, array &$warnings): void
    {
        foreach ([storage_path(), base_path('bootstrap/cache')] as $directory) {
            if (! is_dir($directory) || ! is_writable($directory)) {
                $failures[] = $directory.' must exist and be writable by the application process.';
                continue;
            }

            if ($production && $this->isWorldWritable($directory)) {
                $failures[] = $directory.' must not be world-writable.';
            }
        }

        $envPath = base_path('.env');
        if (is_file($envPath)) {
            if ($this->isWorldReadable($envPath)) {
                $failures[] = '.env must not be world-readable.';
            }
            if ($this->isGroupOrWorldWritable($envPath)) {
                $failures[] = '.env must not be group- or world-writable.';
            }
        } else {
            $warnings[] = '.env is not present. This is valid only when all settings are injected by the platform.';
        }

        foreach (['.env', 'artisan', 'composer.json', 'composer.lock'] as $sensitivePublicFile) {
            if (file_exists(public_path($sensitivePublicFile))) {
                $failures[] = 'Sensitive file is exposed under public/: '.$sensitivePublicFile;
            }
        }

        $storageLink = public_path('storage');
        if (! file_exists($storageLink) && ! is_link($storageLink)) {
            $warnings[] = 'public/storage is missing. Run php artisan storage:link if public media is required.';
        } elseif (! is_link($storageLink)) {
            $failures[] = 'public/storage must be a symbolic link, not a copied directory.';
        } else {
            $target = realpath($storageLink);
            $expected = realpath(storage_path('app/public'));
            if ($target === false || $expected === false || $target !== $expected) {
                $failures[] = 'public/storage points to an unexpected location.';
            }
        }
    }

    /**
     * @param array<int, string> $failures
     * @param array<int, string> $warnings
     */
    private function checkRequiredTables(bool $production, array &$failures, array &$warnings): void
    {
        if (! $production) {
            return;
        }

        $tables = ['sessions', 'cache', 'cache_locks', 'jobs', 'failed_jobs'];

        try {
            foreach ($tables as $table) {
                if (! Schema::hasTable($table)) {
                    $failures[] = 'Required production table is missing: '.$table;
                }
            }
        } catch (Throwable $exception) {
            $failures[] = 'Database runtime check failed: '.$exception->getMessage();
        }
    }

    /**
     * @param array<int, string> $failures
     * @param array<int, string> $warnings
     */
    private function checkBackupRuntime(bool $production, array &$failures, array &$warnings): void
    {
        if (! $production) {
            return;
        }

        if (! (bool) config('security.backup.required')) {
            $failures[] = 'BANDARA_BACKUP_REQUIRED must be true for a production release.';
            return;
        }

        $directory = rtrim((string) config('security.backup.directory'), DIRECTORY_SEPARATOR);
        if ($directory === '') {
            $failures[] = 'BANDARA_BACKUP_DIRECTORY is empty.';
            return;
        }

        $normalisedBackup = $this->normalisePath($directory);
        $normalisedPublic = $this->normalisePath(public_path());
        if (str_starts_with($normalisedBackup, $normalisedPublic.'/') || $normalisedBackup === $normalisedPublic) {
            $failures[] = 'Backups must be stored outside the public web root.';
        }

        if (! is_dir($directory)) {
            $failures[] = 'Backup directory does not exist: '.$directory;
            return;
        }

        if ($this->isWorldReadable($directory) || $this->isWorldWritable($directory)) {
            $failures[] = 'Backup directory must not be world-readable or world-writable.';
        }

        $marker = $directory.DIRECTORY_SEPARATOR.'.last-success';
        if (! is_file($marker)) {
            $failures[] = 'No successful backup marker was found at '.$marker;
            return;
        }

        $timestamp = trim((string) file_get_contents($marker));
        if (! ctype_digit($timestamp)) {
            $failures[] = 'The backup success marker is invalid.';
            return;
        }

        $maxAgeHours = max(1, (int) config('security.backup.max_age_hours', 26));
        $ageSeconds = time() - (int) $timestamp;
        if ($ageSeconds < 0 || $ageSeconds > $maxAgeHours * 3600) {
            $failures[] = sprintf('Latest verified backup is older than %d hour(s).', $maxAgeHours);
        }
    }

    private function normalisePath(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }

    private function permissionBits(string $path): int
    {
        $permissions = @fileperms($path);
        return $permissions === false ? 0 : $permissions & 0777;
    }

    private function isWorldReadable(string $path): bool
    {
        return ($this->permissionBits($path) & 0004) !== 0;
    }

    private function isWorldWritable(string $path): bool
    {
        return ($this->permissionBits($path) & 0002) !== 0;
    }

    private function isGroupOrWorldWritable(string $path): bool
    {
        return ($this->permissionBits($path) & 0022) !== 0;
    }
}
