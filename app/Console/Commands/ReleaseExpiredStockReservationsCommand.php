<?php

namespace App\Console\Commands;

use App\Models\StockReservation;
use App\Services\StockReservationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class ReleaseExpiredStockReservationsCommand extends Command
{
    protected $signature = 'bandara:release-expired-stock-reservations
                            {--dry-run : Show expired reservation count without changing anything}';

    protected $description = 'Release short checkout stock reservations that have expired';

    public function handle(StockReservationService $reservations): int
    {
        if (! Schema::hasTable('stock_reservations')) {
            $this->warn('stock_reservations table is missing. Run php artisan migrate first.');
            return self::SUCCESS;
        }

        $query = StockReservation::query()
            ->where('status', 'reserved')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());

        if ($this->option('dry-run')) {
            $this->table(['Metric', 'Value'], [
                ['Expired reservations', (string) (clone $query)->count()],
                ['Reserved quantity', (string) round((float) (clone $query)->sum('quantity'), 3)],
            ]);
            $this->warn('Dry run only. No reservations were released.');
            return self::SUCCESS;
        }

        $released = $reservations->releaseExpired();
        $this->info("Released {$released} expired stock reservation(s).");

        return self::SUCCESS;
    }
}
