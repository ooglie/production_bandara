<?php

use Illuminate\Support\Facades\Schedule;

// Both generators are deliberately idempotent. Running them daily catches a
// missed scheduler window without duplicating salary months or recurring dates.
Schedule::command('finance:generate-recurring-expenses')
    ->dailyAt('01:15')
    ->withoutOverlapping(30);

Schedule::command('finance:generate-salary-entries')
    ->dailyAt('01:25')
    ->withoutOverlapping(30);
