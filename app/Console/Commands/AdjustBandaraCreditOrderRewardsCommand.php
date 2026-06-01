<?php

namespace App\Console\Commands;

use App\Services\BandaraCreditService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AdjustBandaraCreditOrderRewardsCommand extends Command
{
    protected $signature = 'bandara-credit:adjust-order
                            {order_id : Order ID}
                            {--wallet-delta= : Wallet points delta, e.g. -40 to reverse earned credits}
                            {--tier-delta= : Tier points delta; defaults to wallet delta for earn reversals}
                            {--redeem-restore=0 : Redeemed points to restore to wallet}
                            {--refund-amount= : Refunded/cancelled merchandise value used to calculate proportional earn reversal}
                            {--note= : Audit note}
                            {--dry-run : Show the computed adjustment without writing ledger rows}';

    protected $description = 'Post audited Bandara Credit corrections for partial refunds/cancellations';

    public function handle(BandaraCreditService $bandaraCreditService): int
    {
        $orderModelClass = config('bandara_credit.order_model');
        /** @var Model|null $order */
        $order = $orderModelClass::query()->find((int) $this->argument('order_id'));

        if (! $order) {
            $this->error('Order not found.');
            return self::FAILURE;
        }

        $orderId = (int) $order->getKey();
        $eligibleSpendColumn = (string) config('bandara_credit.order_mapping.eligible_spend', 'subtotal');
        $eligibleSpend = max(0, (float) data_get($order, $eligibleSpendColumn, data_get($order, 'subtotal', 0)));

        $earnTypes = ['base_earned', 'tier_bonus', 'repeat_bonus', 'welcome_bonus', 'birthday_bonus', 'promo_bonus', 'order_reward', 'order_credit', 'earn', 'credit'];
        $postedEarn = (int) DB::table('bandara_credit_transactions')
            ->where('order_id', $orderId)->whereIn('type', $earnTypes)->where('status', 'posted')->where('amount', '>', 0)->sum('amount');
        $postedTierPoints = (int) DB::table('bandara_credit_transactions')
            ->where('order_id', $orderId)->whereIn('type', $earnTypes)->where('status', 'posted')->where('tier_points', '>', 0)->sum('tier_points');
        $alreadyReversed = abs((int) DB::table('bandara_credit_transactions')
            ->where('order_id', $orderId)->whereIn('type', ['earn_reversal', 'reversal'])->where('status', 'posted')->where('amount', '<', 0)->sum('amount'));
        $alreadyTierReversed = abs((int) DB::table('bandara_credit_transactions')
            ->where('order_id', $orderId)->whereIn('type', ['earn_reversal', 'reversal'])->where('status', 'posted')->where('tier_points', '<', 0)->sum('tier_points'));

        $outstandingEarn = max(0, $postedEarn - $alreadyReversed);
        $outstandingTierPoints = max(0, $postedTierPoints - $alreadyTierReversed);

        $walletDelta = $this->option('wallet-delta');
        $tierDelta = $this->option('tier-delta');
        $refundAmount = $this->option('refund-amount');
        $redeemRestore = max(0, (int) $this->option('redeem-restore'));

        if (($walletDelta === null || $walletDelta === '') && $refundAmount !== null && $refundAmount !== '') {
            if ($eligibleSpend <= 0 || $postedEarn <= 0) {
                $this->error('Cannot calculate proportional adjustment because eligible spend or posted earn is zero.');
                return self::FAILURE;
            }

            $ratio = min(1, max(0, ((float) $refundAmount) / $eligibleSpend));
            $walletDelta = -1 * min($outstandingEarn, (int) round($postedEarn * $ratio));
            if ($tierDelta === null || $tierDelta === '') {
                $tierDelta = -1 * min($outstandingTierPoints, (int) round($postedTierPoints * $ratio));
            }
        }

        $walletDelta = (int) $walletDelta;
        if ($tierDelta === null || $tierDelta === '') {
            $tierDelta = $walletDelta < 0 ? $walletDelta : 0;
        }
        $tierDelta = (int) $tierDelta;

        if ($walletDelta < 0) {
            $walletDelta = -1 * min(abs($walletDelta), $outstandingEarn);
        }
        if ($tierDelta < 0) {
            $tierDelta = -1 * min(abs($tierDelta), $outstandingTierPoints);
        }

        $note = (string) ($this->option('note') ?: 'Partial refund/cancellation reward adjustment');

        $this->table(['Metric', 'Value'], [
            ['Order ID', $orderId],
            ['Eligible spend', number_format($eligibleSpend, 2)],
            ['Posted earn', number_format($postedEarn)],
            ['Already reversed', number_format($alreadyReversed)],
            ['Outstanding earn', number_format($outstandingEarn)],
            ['Posted tier points', number_format($postedTierPoints)],
            ['Outstanding tier points', number_format($outstandingTierPoints)],
            ['Wallet delta to post', number_format($walletDelta)],
            ['Tier delta to post', number_format($tierDelta)],
            ['Redeemed points to restore', number_format($redeemRestore)],
        ]);

        if ($this->option('dry-run')) {
            $this->warn('Dry run only. No ledger rows were written.');
            return self::SUCCESS;
        }

        $changed = false;
        if ($walletDelta < 0 || $tierDelta < 0) {
            $result = $bandaraCreditService->postOrderRewardAdjustment(
                order: $order,
                adjustmentType: 'earn_reversal',
                points: abs($walletDelta),
                tierPoints: abs($tierDelta),
                note: $note,
                createdById: null,
                source: 'console_partial_refund_or_correction'
            );
            $changed = $changed || (($result['action'] ?? null) === 'adjusted');
        }

        if ($redeemRestore > 0) {
            $result = $bandaraCreditService->postOrderRewardAdjustment(
                order: $order,
                adjustmentType: 'redeem_restore',
                points: $redeemRestore,
                tierPoints: 0,
                note: $note,
                createdById: null,
                source: 'console_partial_refund_or_correction'
            );
            $changed = $changed || (($result['action'] ?? null) === 'adjusted');
        }

        if (! $changed) {
            $this->warn('No adjustment was posted. Check requested deltas and remaining correctable ledger.');
            return self::SUCCESS;
        }

        $this->info('Reward adjustment posted and wallet reconciled.');
        return self::SUCCESS;
    }
}
