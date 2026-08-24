<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

final class AuditAuthenticationIsolation extends Command
{
    protected $signature = 'bandara:audit-auth-isolation';

    protected $description = 'Verify Bandara staff/customer guard, route and session isolation';

    public function handle(): int
    {
        $checks = [
            'Customer guard remains web' => config('staff-auth.customer_guard') === 'web',
            'Staff guard is registered' => config('auth.guards.staff.driver') === 'session',
            'Staff guard shares the existing user provider' =>
                config('auth.guards.staff.provider') === config('auth.guards.web.provider'),
            'Staff session cookie differs from customer cookie' =>
                config('staff-auth.session.cookie') !== config('session.cookie'),
            'Customer login route remains present' => Route::has('login'),
            'Staff login route is present' => Route::has('admin.login'),
            'Staff login POST route is present' => Route::has('admin.login.store'),
            'Staff logout route is present' => Route::has('admin.logout'),
            'Impersonation acceptance bridge is present' => Route::has('staff-impersonation.accept'),
            'Impersonation leave bridge is present' => Route::has('staff-impersonation.leave'),
        ];

        $failed = false;

        foreach ($checks as $label => $passed) {
            $this->line(($passed ? '<info>PASS</info>' : '<error>FAIL</error>')."  {$label}");
            $failed = $failed || ! $passed;
        }

        if ($failed) {
            $this->newLine();
            $this->error('Authentication isolation audit failed.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Authentication isolation audit passed.');

        return self::SUCCESS;
    }
}
