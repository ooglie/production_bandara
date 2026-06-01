<?php

namespace App\Console\Commands;

use App\Services\BandaraCreditService;
use Illuminate\Console\Command;

class ReleaseStaleBandaraCreditReservationsCommand extends Command
{
    protected $signature = 'bandara-credit:release-stale-reservations
                            {--minutes= : Release reservations older than this many minutes; defaults to config}
                            {--dry-run : Show stale reservation count without releasing anything}';

    protected $description = 'Release stale reserved Bandara Credit checkout debits';

    public function handle(BandaraCreditService $bandaraCreditService): int
    {
        $minutes = $this->option('minutes');
        $minutes = $minutes !== null && $minutes !== '' ? max(1, (int) $minutes) : null;

        if ($this->option('dry-run')) {
            $summary = $bandaraCreditService->countExpiredRedemptionReservations($minutes);

            $this->table(['Metric', 'Value'], [
                ['Reservations', number_format((int) $summary['reservations_count'])],
                ['Reserved points', number_format((int) $summary['points'])],
                ['Cutoff', optional($summary['cutoff'])->toDateTimeString()],
            ]);
            $this->warn('Dry run only. No reservations were released.');

            return self::SUCCESS;
        }

        $released = $bandaraCreditService->releaseExpiredRedemptionReservations($minutes);

        $this->info("Released {$released} reserved Bandara Credit point(s).");

        return self::SUCCESS;
    }
}
