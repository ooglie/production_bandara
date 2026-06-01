<?php

namespace App\Console\Commands;

use App\Services\BandaraCreditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileBandaraCreditWalletsCommand extends Command
{
    protected $signature = 'bandara-credit:reconcile
                            {user_id? : Reconcile a single user ID}
                            {--all : Reconcile every user that has a wallet or credit transaction}';

    protected $description = 'Recalculate Bandara Credit wallet balances and tiers from the posted transaction ledger';

    public function handle(BandaraCreditService $bandaraCreditService): int
    {
        $userId = $this->argument('user_id');

        if ($userId !== null) {
            $wallet = $bandaraCreditService->syncWalletForUser((int) $userId);

            $this->info("Reconciled user #{$wallet->user_id}: balance ₹{$wallet->balance}, tier {$wallet->tier}.");

            return self::SUCCESS;
        }

        if (! $this->option('all')) {
            $this->error('Provide a user_id or pass --all.');

            return self::FAILURE;
        }

        $userIds = collect()
            ->merge(DB::table('bandara_credit_transactions')->distinct()->pluck('user_id'))
            ->merge(DB::table('bandara_credit_wallets')->distinct()->pluck('user_id'))
            ->filter()
            ->unique()
            ->values();

        $count = 0;

        foreach ($userIds as $id) {
            $wallet = $bandaraCreditService->syncWalletForUser((int) $id);
            $count++;

            $this->line("#{$wallet->user_id}: ₹{$wallet->balance} / {$wallet->tier}");
        }

        $this->info("Reconciled {$count} Bandara Credit wallet(s).");

        return self::SUCCESS;
    }
}
