<?php

namespace App\Console\Commands;

use App\Services\BandaraCreditService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class PostBandaraCreditEarnCommand extends Command
{
    protected $signature = 'bandara-credit:post-earned
                            {order_id : Successful order ID}
                            {--dry-run : Preview only, do not write}';

    protected $description = 'Post Bandara Credit earnings for one successful order';

    public function handle(BandaraCreditService $bandaraCreditService): int
    {
        /** @var class-string<Model> $orderModelClass */
        $orderModelClass = config('bandara_credit.order_model');

        $order = $orderModelClass::query()->find($this->argument('order_id'));

        if (! $order) {
            $this->error('Order not found.');
            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $preview = $bandaraCreditService->previewEarnForOrder($order);

            $this->table(['Field', 'Value'], [
                ['Order ID', (string) $order->getKey()],
                ['Status', (string) data_get($order, config('bandara_credit.order_mapping.status'))],
                ['Eligible spend', '₹'.$preview['eligible_spend']],
                ['Base credit', '₹'.$preview['base_credit']],
                ['Repeat bonus', '₹'.$preview['repeat_bonus']],
                ['Welcome bonus', '₹'.$preview['welcome_bonus']],
                ['Total preview', '₹'.$preview['total_credit_preview']],
            ]);

            return self::SUCCESS;
        }

        $result = $bandaraCreditService->postEarnForSuccessfulOrder($order);

        if (! $result['posted']) {
            $this->warn('Nothing posted.');

            $this->table(['Field', 'Value'], [
                ['Reason', (string) ($result['reason'] ?? 'unknown')],
                ['Order ID', (string) ($result['order_id'] ?? '')],
                ['User ID', (string) ($result['user_id'] ?? '')],
                ['Wallet balance', isset($result['wallet_balance']) ? '₹'.$result['wallet_balance'] : '—'],
            ]);

            return self::SUCCESS;
        }

        $rows = collect($result['transactions_posted'])
            ->map(fn (array $row) => [$row['type'], '₹'.$row['amount']])
            ->all();

        $this->info('Bandara Credit posted successfully.');

        $this->table(['Type', 'Amount'], $rows);

        $this->line('Total posted: ₹'.$result['total_posted']);
        $this->line('Wallet balance: ₹'.$result['wallet_balance']);
        $this->line('Tier: '.ucfirst((string) $result['tier']));

        return self::SUCCESS;
    }
}