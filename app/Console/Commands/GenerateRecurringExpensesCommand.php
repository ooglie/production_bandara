<?php

namespace App\Console\Commands;

use App\Services\Finance\RecurringExpenseGenerator;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\Schema;

class GenerateRecurringExpensesCommand extends Command implements Isolatable
{
    protected $signature = 'finance:generate-recurring-expenses
                            {--through= : Generate draft expenses due on or before YYYY-MM-DD}';

    protected $description = 'Generate reviewable draft expenses from due recurring-expense templates';

    public function handle(RecurringExpenseGenerator $generator): int
    {
        if (! Schema::hasTable('recurring_expense_templates') || ! Schema::hasTable('business_expenses')) {
            $this->error('Finance tables are missing. Run php artisan migrate first.');

            return self::FAILURE;
        }

        $result = $generator->generateDue($this->option('through'));

        $this->table(['Metric', 'Count'], [
            ['Drafts created', $result['created']],
            ['Skipped/existing', $result['skipped']],
            ['Errors', count($result['errors'])],
        ]);

        foreach ($result['errors'] as $error) {
            $this->error($error);
        }

        $this->info('Recurring templates generate drafts only; no expense was posted automatically.');

        return $result['errors'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
