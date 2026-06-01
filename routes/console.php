<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Rewards production hardening jobs. These run only when the Laravel scheduler
// is installed on the server, e.g. `php artisan schedule:run` every minute.
Schedule::command('bandara-credit:release-stale-reservations')->everyThirtyMinutes()->withoutOverlapping();
Schedule::command('bandara-credit:reconcile --all')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('bandara-credit:audit-eligibility')->dailyAt('02:30')->withoutOverlapping();
