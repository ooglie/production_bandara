<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

final class BandaraLaunchContentSyncCommand extends Command
{
    protected $signature = 'bandara:sync-launch-content
                            {--apply : Apply exact launch-content replacements}
                            {--check : Return failure when exact retired launch content remains}
                            {--backup= : Absolute JSON backup path required with --apply}
                            {--rollback= : Restore values from an exact backup JSON path}
                            {--force : Permit rollback when a value changed after installation}';

    protected $description = 'Audit, apply, or roll back exact Bandara launch content replacements';

    /**
     * Only content-oriented tables and text columns are considered. The command
     * never scans transactional, financial, inventory, order, or user tables.
     *
     * @var array<string, array{keys: list<string>, columns: list<string>}>
     */
    private const TABLES = [
        'home_sections' => [
            'keys' => ['id', 'key'],
            'columns' => [
                'eyebrow', 'title', 'subtitle', 'body', 'cta_text', 'cta_url',
                'secondary_cta_text', 'secondary_cta_url', 'settings',
            ],
        ],
        'home_section_items' => [
            'keys' => ['id'],
            'columns' => [
                'eyebrow', 'title', 'description', 'cta_text', 'cta_url', 'settings',
            ],
        ],
        'product_collections' => [
            'keys' => ['id', 'slug'],
            'columns' => ['name', 'eyebrow', 'description', 'cta_text', 'cta_url', 'rules'],
        ],
        'pages' => [
            'keys' => ['id', 'slug', 'key'],
            'columns' => [
                'title', 'subtitle', 'excerpt', 'body', 'content', 'meta_title',
                'meta_description', 'settings',
            ],
        ],
        'content_pages' => [
            'keys' => ['id', 'slug', 'key'],
            'columns' => [
                'title', 'subtitle', 'excerpt', 'body', 'content', 'meta_title',
                'meta_description', 'settings',
            ],
        ],
        'page_sections' => [
            'keys' => ['id', 'key', 'slug'],
            'columns' => [
                'eyebrow', 'title', 'subtitle', 'body', 'content', 'cta_text',
                'cta_url', 'settings',
            ],
        ],
        'site_settings' => [
            'keys' => ['id', 'key'],
            'columns' => ['value', 'content', 'settings'],
        ],
        'settings' => [
            'keys' => ['id', 'key'],
            'columns' => ['value', 'content', 'settings'],
        ],
        'email_templates' => [
            'keys' => ['id', 'key', 'slug'],
            'columns' => ['name', 'subject', 'body', 'html_body', 'content', 'settings'],
        ],
    ];

    /** @var list<array{0: string, 1: string}> */
    private const REPLACEMENTS = [
        [
            'Quality frozen products, GST-ready invoicing, and a mobile-first shopping experience powered by Frozen by Bandara.',
            'Premium meats, seafood, cheese and speciality foods for homes, chefs and businesses—with GST-ready invoicing and a seamless mobile-first shopping experience from Bandara.',
        ],
        [
            'Quality frozen products, GST‑ready invoicing, and a mobile‑first shopping experience powered by Frozen by Bandara.',
            'Premium meats, seafood, cheese and speciality foods for homes, chefs and businesses—with GST-ready invoicing and a seamless mobile-first shopping experience from Bandara.',
        ],
        [
            'Do not create another account. Sign in with the existing B2C customer account and submit the business application. Addresses, orders and account history remain attached to the same login.',
            'You can request business access using your existing customer account. Simply sign in and submit your business details for review. Once approved, eligible wholesale pricing and business ordering features will be added to the same account—without creating a new login or losing your saved addresses and order history.',
        ],
        [
            'Use this space for serving notes, quick prep guidance, and practical suggestions that make the product feel easier to cook and easier to order again.',
            'Discover a carefully selected weekly recipe, a useful kitchen tip, and products that make it easier to prepare.',
        ],
        ['Browse chef picks', 'Explore Bandara Kitchen'],
        ['"recipe_limit": 3', '"recipe_limit": 1'],
        ['\\"recipe_limit\\": 3', '\\"recipe_limit\\": 1'],
        ['Bandara by Maytira', 'Bandara'],
        ['Bandara Frozen', 'Bandara'],
        ['Chef Spotlight', 'Bandara Kitchen'],
        ['Chef spotlight', 'Bandara Kitchen'],
        ['Recipe of the Day', 'Recipe of the Week'],
        ['Recipe of the day', 'Recipe of the week'],
        ['powered by Frozen by Bandara', 'from Bandara'],
        ['Powered by Frozen by Bandara', 'From Bandara'],
        ['Frozen by Bandara', 'Bandara'],
        ['frozen.bandara.in', 'bandara.shop'],
        ['frozen.shop', 'bandara.shop'],
    ];

    public function handle(): int
    {
        $rollbackPath = trim((string) $this->option('rollback'));

        if ($rollbackPath !== '') {
            if ($this->option('apply')) {
                $this->error('--apply and --rollback cannot be used together.');

                return self::FAILURE;
            }

            return $this->rollback($this->absolutePath($rollbackPath), (bool) $this->option('force'));
        }

        $changes = $this->discoverChanges();
        $this->renderSummary($changes);

        if (! $this->option('apply')) {
            if ($this->option('check') && $changes !== []) {
                $this->error('Exact retired launch content remains in the database.');

                return self::FAILURE;
            }

            $this->info('Dry run only. No database value was changed.');

            return self::SUCCESS;
        }

        $backupOption = trim((string) $this->option('backup'));

        if ($backupOption === '') {
            $this->error('--backup=/absolute/path/content-backup.json is required with --apply.');

            return self::FAILURE;
        }

        if ($changes === []) {
            $this->info('No database content replacements are required.');

            return self::SUCCESS;
        }

        return $this->apply($changes, $this->absolutePath($backupOption));
    }

    /**
     * @return list<array{table: string, key_column: string, key_value: mixed, column: string, old: string, new: string}>
     */
    private function discoverChanges(): array
    {
        $changes = [];

        foreach (self::TABLES as $table => $definition) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $availableColumns = Schema::getColumnListing($table);
            $keyColumn = collect($definition['keys'])
                ->first(static fn (string $column): bool => in_array($column, $availableColumns, true));
            $columns = array_values(array_intersect($definition['columns'], $availableColumns));

            if (! is_string($keyColumn) || $columns === []) {
                continue;
            }

            $rows = DB::table($table)->select(array_merge([$keyColumn], $columns))->get();

            foreach ($rows as $row) {
                foreach ($columns as $column) {
                    $value = $row->{$column} ?? null;

                    if (! is_string($value) || $value === '') {
                        continue;
                    }

                    $updated = $this->replace($value);

                    if ($updated === $value) {
                        continue;
                    }

                    $changes[] = [
                        'table' => $table,
                        'key_column' => $keyColumn,
                        'key_value' => $row->{$keyColumn},
                        'column' => $column,
                        'old' => $value,
                        'new' => $updated,
                    ];
                }
            }
        }

        return $changes;
    }

    private function replace(string $value): string
    {
        $updated = $value;

        foreach (self::REPLACEMENTS as [$old, $new]) {
            $updated = str_replace($old, $new, $updated);
        }

        return $updated;
    }

    /**
     * @param list<array{table: string, key_column: string, key_value: mixed, column: string, old: string, new: string}> $changes
     */
    private function renderSummary(array $changes): void
    {
        if ($changes === []) {
            $this->info('Database content audit: no exact retired launch content found.');

            return;
        }

        $grouped = collect($changes)
            ->groupBy('table')
            ->map(static fn ($rows): int => $rows->count())
            ->sortKeys();

        $this->table(
            ['Table', 'Values to update'],
            $grouped->map(static fn (int $count, string $table): array => [$table, $count])->values()->all(),
        );
        $this->line('Total exact value replacements: '.count($changes));
    }

    /**
     * @param list<array{table: string, key_column: string, key_value: mixed, column: string, old: string, new: string}> $changes
     */
    private function apply(array $changes, string $backupPath): int
    {
        if (is_file($backupPath)) {
            $this->error("Backup path already exists: {$backupPath}");

            return self::FAILURE;
        }

        $payload = [
            'version' => 'bandara-storefront-launch-ui-content-v1.2',
            'status' => 'pending',
            'created_at' => now()->toIso8601String(),
            'connection' => DB::getDefaultConnection(),
            'database' => DB::connection()->getDatabaseName(),
            'changes' => $changes,
        ];

        try {
            $this->writeJsonAtomically($backupPath, $payload);

            DB::transaction(function () use ($changes): void {
                foreach ($changes as $change) {
                    $query = DB::table($change['table'])
                        ->where($change['key_column'], $change['key_value']);
                    $current = (clone $query)
                        ->lockForUpdate()
                        ->value($change['column']);

                    if ((string) $current !== (string) $change['old']) {
                        throw new RuntimeException(sprintf(
                            '%s.%s row %s changed during installation.',
                            $change['table'],
                            $change['column'],
                            (string) $change['key_value'],
                        ));
                    }

                    $affected = $query->update([$change['column'] => $change['new']]);

                    if ($affected !== 1) {
                        throw new RuntimeException(sprintf(
                            '%s.%s row %s updated %d rows; expected exactly one.',
                            $change['table'],
                            $change['column'],
                            (string) $change['key_value'],
                            $affected,
                        ));
                    }
                }
            });

            $payload['status'] = 'applied';
            $payload['applied_at'] = now()->toIso8601String();
            $this->writeJsonAtomically($backupPath, $payload);
        } catch (Throwable $exception) {
            $payload['status'] = 'failed';
            $payload['error'] = $exception->getMessage();

            try {
                $this->writeJsonAtomically($backupPath, $payload);
            } catch (Throwable) {
                // Preserve the original exception below.
            }

            $this->error('Database content update failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Database content replacements applied transactionally.');
        $this->line("Backup: {$backupPath}");

        return self::SUCCESS;
    }

    private function rollback(string $backupPath, bool $force): int
    {
        if (! is_file($backupPath)) {
            $this->error("Content backup not found: {$backupPath}");

            return self::FAILURE;
        }

        try {
            /** @var array{version?: string, status?: string, connection?: string, database?: string, changes?: array<int, array<string, mixed>>} $payload */
            $payload = json_decode((string) file_get_contents($backupPath), true, 512, JSON_THROW_ON_ERROR);

            if (($payload['version'] ?? null) !== 'bandara-storefront-launch-ui-content-v1.2') {
                throw new RuntimeException('The backup belongs to a different patch.');
            }

            if (($payload['connection'] ?? null) !== DB::getDefaultConnection()) {
                throw new RuntimeException('The backup database connection does not match the current application.');
            }

            if (($payload['database'] ?? null) !== DB::connection()->getDatabaseName()) {
                throw new RuntimeException('The backup database name does not match the current application.');
            }

            if (($payload['status'] ?? null) === 'rolled_back') {
                $this->info('Database content backup was already rolled back.');

                return self::SUCCESS;
            }

            $changes = $payload['changes'] ?? [];

            foreach ($changes as $change) {
                $this->validateBackupChange($change);
            }

            DB::transaction(function () use ($changes, $force): void {
                foreach (array_reverse($changes) as $change) {
                    $query = DB::table((string) $change['table'])
                        ->where((string) $change['key_column'], $change['key_value']);
                    $current = (clone $query)
                        ->lockForUpdate()
                        ->value((string) $change['column']);

                    if ((string) $current === (string) $change['old']) {
                        // Already restored (for example after a failed transaction).
                        continue;
                    }

                    if (! $force && (string) $current !== (string) $change['new']) {
                        throw new RuntimeException(sprintf(
                            '%s.%s row %s changed after installation.',
                            $change['table'],
                            $change['column'],
                            (string) $change['key_value'],
                        ));
                    }

                    $affected = $query->update([(string) $change['column'] => $change['old']]);

                    if ($affected !== 1) {
                        throw new RuntimeException(sprintf(
                            '%s.%s row %s restored %d rows; expected exactly one.',
                            $change['table'],
                            $change['column'],
                            (string) $change['key_value'],
                            $affected,
                        ));
                    }
                }
            });

            $payload['status'] = 'rolled_back';
            $payload['rolled_back_at'] = now()->toIso8601String();
            $this->writeJsonAtomically($backupPath, $payload);
        } catch (Throwable $exception) {
            $this->error('Database content rollback failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Database content values restored from the exact backup.');

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $change */
    private function validateBackupChange(array $change): void
    {
        $table = (string) ($change['table'] ?? '');
        $keyColumn = (string) ($change['key_column'] ?? '');
        $column = (string) ($change['column'] ?? '');
        $definition = self::TABLES[$table] ?? null;

        if ($definition === null) {
            throw new RuntimeException("Backup contains a non-content table: {$table}");
        }

        if (! in_array($keyColumn, $definition['keys'], true)) {
            throw new RuntimeException("Backup contains an invalid key column for {$table}: {$keyColumn}");
        }

        if (! in_array($column, $definition['columns'], true)) {
            throw new RuntimeException("Backup contains an invalid content column for {$table}: {$column}");
        }

        foreach (['key_value', 'old', 'new'] as $required) {
            if (! array_key_exists($required, $change)) {
                throw new RuntimeException("Backup change is missing {$required}.");
            }
        }
    }

    private function absolutePath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }

    /** @param array<string, mixed> $payload */
    private function writeJsonAtomically(string $path, array $payload): void
    {
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException("Could not create backup directory: {$directory}");
        }

        $temporary = $path.'.tmp.'.bin2hex(random_bytes(5));
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;

        if (file_put_contents($temporary, $json, LOCK_EX) === false) {
            throw new RuntimeException("Could not write temporary backup: {$temporary}");
        }

        if (! rename($temporary, $path)) {
            @unlink($temporary);
            throw new RuntimeException("Could not finalize backup: {$path}");
        }
    }
}
