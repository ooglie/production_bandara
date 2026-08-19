<?php

namespace App\Console\Commands;

use App\Services\MediaOrganizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class OrganizeMediaCommand extends Command
{
    protected $signature = 'bandara:organize-media
                            {--dry-run : Scan media references and create a private migration manifest}
                            {--copy : Copy files from legacy locations into the organized structure}
                            {--verify : Verify copied files using file size and SHA-256}
                            {--commit : Update database media paths after verification succeeds}
                            {--rollback : Restore database paths changed by a committed run}
                            {--run-id= : Run ID produced by --dry-run}
                            {--force : Skip interactive confirmation for commit or rollback}';

    protected $description = 'Safely organize Bandara product, recipe, ticket, avatar and homepage media';

    public function handle(MediaOrganizationService $organizer): int
    {
        $actions = collect(['dry-run', 'copy', 'verify', 'commit', 'rollback'])
            ->filter(fn (string $option): bool => (bool) $this->option($option))
            ->values();

        if ($actions->count() !== 1) {
            $this->error('Choose exactly one action: --dry-run, --copy, --verify, --commit, or --rollback.');
            $this->newLine();
            $this->line('Start with: php artisan bandara:organize-media --dry-run');

            return self::FAILURE;
        }

        $action = $actions->first();
        $runId = trim((string) $this->option('run-id'));

        if ($action !== 'dry-run' && $runId === '') {
            $this->error("--run-id is required for --{$action}.");

            return self::FAILURE;
        }

        $lock = Cache::lock('bandara:organize-media', 21600);

        if (! $lock->get()) {
            $this->error('Another media-organization operation is already running.');

            return self::FAILURE;
        }

        try {
            return match ($action) {
                'dry-run' => $this->runDryRun($organizer),
                'copy' => $this->runCopy($organizer, $runId),
                'verify' => $this->runVerify($organizer, $runId),
                'commit' => $this->runCommit($organizer, $runId),
                'rollback' => $this->runRollback($organizer, $runId),
                default => self::FAILURE,
            };
        } catch (\Throwable $e) {
            report($e);
            $this->error($e->getMessage());

            if ($this->output->isVerbose()) {
                $this->newLine();
                $this->line($e->getTraceAsString());
            }

            return self::FAILURE;
        } finally {
            $lock->release();
        }
    }

    protected function runDryRun(MediaOrganizationService $organizer): int
    {
        $this->info('Scanning database media references and legacy storage paths...');
        $result = $organizer->prepare();

        $this->newLine();
        $this->info('Dry run completed. No media file or database path was changed.');
        $this->line('Run ID: <fg=yellow>'.$result['run_id'].'</>');
        $this->line('Private report directory: storage/app/private/'.$result['run_directory']);
        $this->renderSummary($result['summary']);

        $this->newLine();
        $this->line('Next:');
        $this->line('  php artisan bandara:organize-media --copy --run-id='.$result['run_id']);

        return self::SUCCESS;
    }

    protected function runCopy(MediaOrganizationService $organizer, string $runId): int
    {
        $this->info("Copying files for media run {$runId}...");
        $result = $organizer->copy($runId);
        $this->renderSummary($result['summary']);

        $acceptedResults = ['copied', 'already_copied', 'already_organized', 'external', 'skipped_empty'];
        $failed = collect($result['results'])->contains(
            fn (array $row): bool => ! in_array($row['result'] ?? null, $acceptedResults, true)
        );

        if ($failed) {
            $this->warn('One or more files were not copied. Review copy-results.csv before verification.');
            return self::FAILURE;
        }

        $this->newLine();
        $this->line('Next:');
        $this->line('  php artisan bandara:organize-media --verify --run-id='.$runId);

        return self::SUCCESS;
    }

    protected function runVerify(MediaOrganizationService $organizer, string $runId): int
    {
        $this->info("Verifying files for media run {$runId}...");
        $result = $organizer->verify($runId);
        $this->renderSummary($result['summary']);

        if (! $result['success']) {
            $this->error('Verification did not pass. The database has not been changed.');
            return self::FAILURE;
        }

        $this->info('Verification passed.');
        $this->newLine();
        $this->line('Next, during a quiet maintenance window:');
        $this->line('  php artisan bandara:organize-media --commit --run-id='.$runId);

        return self::SUCCESS;
    }

    protected function runCommit(MediaOrganizationService $organizer, string $runId): int
    {
        if (! $this->option('force') && ! $this->confirm(
            'Update the database to use the verified organized media paths?',
            false
        )) {
            $this->warn('Commit cancelled.');
            return self::SUCCESS;
        }

        $result = $organizer->commit($runId);
        $this->renderSummary($result['summary']);

        if (! $result['success']) {
            $this->error('The commit completed with failures. Review commit-results.csv immediately.');
            return self::FAILURE;
        }

        $this->info('Database media paths were updated. Legacy source files were not deleted.');
        $this->line('Rollback command: php artisan bandara:organize-media --rollback --run-id='.$runId);

        return self::SUCCESS;
    }

    protected function runRollback(MediaOrganizationService $organizer, string $runId): int
    {
        if (! $this->option('force') && ! $this->confirm(
            'Restore the original database media paths for this run?',
            false
        )) {
            $this->warn('Rollback cancelled.');
            return self::SUCCESS;
        }

        $result = $organizer->rollback($runId);
        $this->renderSummary($result['summary']);

        if (! $result['success']) {
            $this->error('The rollback completed with failures. Review rollback-results.csv.');
            return self::FAILURE;
        }

        $this->info('Original database media paths were restored. Copied files were retained.');

        return self::SUCCESS;
    }

    /** @param array<string,int> $summary */
    protected function renderSummary(array $summary): void
    {
        $rows = [];

        foreach ($summary as $metric => $value) {
            $rows[] = [str_replace('_', ' ', ucfirst($metric)), (string) $value];
        }

        $this->newLine();
        $this->table(['Metric', 'Count'], $rows);
    }
}
