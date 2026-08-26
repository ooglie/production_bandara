<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class VerifyB2BApplication extends Command
{
    protected $signature = 'bandara:b2b-applications:verify {--json : Return machine-readable JSON}';
    protected $description = 'Verify the B2B business-account application module.';

    public function handle(): int
    {
        $checks = [
            'table.b2b_applications' => Schema::hasTable('b2b_applications'),
            'table.b2b_application_histories' => Schema::hasTable('b2b_application_histories'),
            'table.b2b_customer_profiles' => Schema::hasTable('b2b_customer_profiles'),
            'column.users.customer_type' => Schema::hasTable('users') && Schema::hasColumn('users', 'customer_type'),
            'route.business-account.index' => Route::has('business-account.index'),
            'route.account.business-application.show' => Route::has('account.business-application.show'),
            'route.admin.b2b-applications.index' => Route::has('admin.b2b-applications.index'),
            'config.customer_type.b2b' => filled(config('b2b_application.customer_type.b2b')),
            'config.location.states' => filled(config('b2b_application.location.states.table')),
            'config.location.cities' => filled(config('b2b_application.location.cities.table')),
        ];

        if ($this->option('json')) {
            $this->line(json_encode([
                'ok' => ! in_array(false, $checks, true),
                'checks' => $checks,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            foreach ($checks as $name => $passed) {
                $passed ? $this->components->info($name) : $this->components->error($name);
            }
        }

        return in_array(false, $checks, true) ? self::FAILURE : self::SUCCESS;
    }
}
