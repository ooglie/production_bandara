<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB; // Add this line

class FlushSessions extends Command
{
    protected $signature = 'session:flush';
    protected $description = 'Flush all user sessions from the database';

    public function handle()
    {
        // Truncate the sessions table
        DB::table(config('session.table'))->truncate();
        $this->info('Sessions table truncated successfully.');
    }
}
