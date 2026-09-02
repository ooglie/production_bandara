<?php

namespace App\Console\Commands;

use App\Services\Finance\SalaryEntryGenerator;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\Schema;

class GenerateSalaryEntriesCommand extends Command implements Isolatable
{
    protected $signature = 'finance:generate-salary-entries
                            {--month= : Salary month in YYYY-MM format; defaults to the current month}';

    protected $description = 'Create idempotent monthly salary snapshots from effective salary profiles';

    public function handle(SalaryEntryGenerator $generator): int
    {
        if (! Schema::hasTable('salary_profiles') || ! Schema::hasTable('salary_entries')) {
            $this->error('Finance tables are missing. Run php artisan migrate first.');

            return self::FAILURE;
        }

        $result = $generator->generateForMonth($this->option('month'));

        $this->table(['Metric', 'Count'], [
            ['Salary records created', $result['created']],
            ['Skipped/existing', $result['skipped']],
            ['Errors', count($result['errors'])],
        ]);

        foreach ($result['errors'] as $error) {
            $this->error($error);
        }

        return $result['errors'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
