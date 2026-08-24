<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Checkout stock holds are intentionally short. Release expired reservations
// promptly even when no new checkout request arrives to trigger lazy cleanup.
Schedule::command('bandara:release-expired-stock-reservations')
    ->everyMinute()
    ->onOneServer()
    ->withoutOverlapping();

// Rewards production hardening jobs.
Schedule::command('bandara-credit:release-stale-reservations')
    ->everyThirtyMinutes()
    ->onOneServer()
    ->withoutOverlapping();

Schedule::command('bandara-credit:reconcile --all')
    ->dailyAt('02:00')
    ->onOneServer()
    ->withoutOverlapping();

Schedule::command('bandara-credit:audit-eligibility')
    ->dailyAt('02:30')
    ->onOneServer()
    ->withoutOverlapping();

// Remove expired password-reset tokens and old queue metadata.
Schedule::command('auth:clear-resets')
    ->hourly()
    ->onOneServer()
    ->withoutOverlapping();

Schedule::command('queue:prune-failed --hours=168')
    ->dailyAt('03:10')
    ->onOneServer()
    ->withoutOverlapping();

Schedule::command('queue:prune-batches --hours=48 --unfinished=168 --cancelled=168')
    ->dailyAt('03:20')
    ->onOneServer()
    ->withoutOverlapping();

// BANDARA_FINANCE_V1_SCHEDULE_START
require __DIR__.'/finance-console.php';
// BANDARA_FINANCE_V1_SCHEDULE_END
