<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class FlushSessions extends Command
{
    protected $signature = 'session:flush {--force : Do not ask for confirmation}';
    protected $description = 'Invalidate all application sessions';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This will log out every user. Continue?')) {
            $this->warn('Session flush cancelled.');
            return self::SUCCESS;
        }

        $driver = (string) config('session.driver');

        if ($driver === 'database') {
            DB::table(config('session.table', 'sessions'))->delete();
        } elseif ($driver === 'file') {
            File::cleanDirectory((string) config('session.files'));
        } elseif ($driver === 'redis') {
            $this->error('Redis sessions are not flushed automatically because FLUSHDB may delete unrelated cache or queue data. Use an isolated Redis database and clear only its session keys during deployment.');
            return self::FAILURE;
        } else {
            $this->error("The '{$driver}' session driver is not supported by this command.");
            return self::FAILURE;
        }

        $this->info('All application sessions have been invalidated.');
        return self::SUCCESS;
    }
}
