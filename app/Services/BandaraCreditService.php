<?php

namespace App\Services;

use App\Models\BandaraCreditCampaign;
use App\Models\BandaraCreditTier;
use App\Models\BandaraCreditTransaction;
use App\Models\BandaraCreditWallet;
use App\Models\User;
use App\Notifications\BandaraCreditAccountNotification;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BandaraCreditService
{
    public function getOrCreateWallet(User|int $user): BandaraCreditWallet
    {
        $userId = $this->resolveUserId($user);

        return BandaraCreditWallet::firstOrCreate(
            ['user_id' => $userId],
            [
                'balance' => 0,
                'tier' => 'silver',
            ]
        );
    }

    public function currentBalance(User|int $user): int
    {
        if (! $this->isEligibleUserForBandaraCredit($user)) {
            return 0;
        }

        return (int) $this->getOrCreateWallet($user)->balance;
    }

    public function previewEarnForOrder(Model|array $order): array
    {
        $userId = $this->nullableInt($this->orderValue($order, 'user_id'));
        $orderId = $this->extractOrderId($order);

        $eligibleSpend = $this->normalizeMoney(
            $this->orderValue($order, 'eligible_spend', 0)
        );

        $placedAt = $this->normalizeDate(
            $this->orderValue($order, 'placed_at', now())
        );

        $perAmountSpent = max(1, (int) config('bandara_credit.earning.per_amount_spent', 100));
        $creditAmount = max(1, (int) config('bandara_credit.earning.credit_amount', 1));
        $repeatWindowDays = (int) config('bandara_credit.earning.repeat_window_days', 10);
        $welcomeCredit = (int) config('bandara_credit.earning.welcome_credit', 100);
        $welcomeMinOrderValue = (int) config('bandara_credit.earning.welcome_min_order_value', 999);

        $rules = [
            'per_amount_spent' => $perAmountSpent,
            'credit_amount' => $creditAmount,
            'repeat_window_days' => $repeatWindowDays,
            'welcome_credit' => $welcomeCredit,
            'welcome_min_order_value' => $welcomeMinOrderValue,
        ];

        $baseCredit = intdiv($eligibleSpend, $perAmountSpent) * $creditAmount;

        if ($userId !== null && ! $this->isEligibleUserForBandaraCredit($userId)) {
            return [
                'eligible_user' => false,
                'eligibility_reason' => 'b2c_customers_only',
                'eligible_spend' => $eligibleSpend,
                'base_credit' => 0,
                'tier_bonus' => 0,
                'repeat_bonus' => 0,
                'welcome_bonus' => 0,
                'promo_bonus' => 0,
                'promo_campaign' => null,
                'total_credit_preview' => 0,
                'tier_points_preview' => 0,
                'qualifies_repeat_bonus' => false,
                'qualifies_welcome_bonus' => false,
                'rules' => $rules,
                'components' => [],
            ];
        }

        $tierPreview = $userId !== null
            ? $this->previewTierForUser($userId)
            : $this->previewTierForPoints(0);

        $tierKey = (string) ($tierPreview['tier'] ?? 'silver');
        $tierRatePercent = $this->rewardRateForTier($tierKey);
        $tierTotalCredit = (int) floor(($eligibleSpend * $tierRatePercent) / 100);
        $tierBonus = max(0, $tierTotalCredit - $baseCredit);

        $qualifiesRepeat = false;
        if ($userId !== null && (bool) config('bandara_credit.repeat_bonus_enabled', true)) {
            $qualifiesRepeat = $this->qualifiesForRepeatBonus(
                userId: $userId,
                placedAt: $placedAt,
                excludeOrderId: $orderId
            );
        }

        $qualifiesWelcome = false;
        if ($userId !== null && (bool) config('bandara_credit.welcome_bonus_enabled', true)) {
            $qualifiesWelcome = $this->qualifiesForWelcomeBonus(
                userId: $userId,
                eligibleSpend: $eligibleSpend,
                minimumOrderValue: $welcomeMinOrderValue,
                placedAt: $placedAt,
                excludeOrderId: $orderId
            );
        }

        $repeatBonus = $qualifiesRepeat ? $baseCredit : 0;
        $welcomeBonus = $qualifiesWelcome ? $welcomeCredit : 0;
        $normalRewardTotal = $baseCredit + $tierBonus;
        $campaignPreview = $this->previewBestCampaignBonus(
            order: $order,
            userId: $userId,
            tier: $tierKey,
            normalRewardTotal: $normalRewardTotal,
            eligibleSpend: $eligibleSpend
        );
        $promoBonus = (int) ($campaignPreview['amount'] ?? 0);
        $promoTierPoints = (int) ($campaignPreview['tier_points'] ?? 0);

        $components = [
            'base_earned' => [
                'amount' => $baseCredit,
                'tier_points' => $baseCredit,
            ],
            'tier_bonus' => [
                'amount' => $tierBonus,
                'tier_points' => $tierBonus,
                'meta' => [
                    'tier' => $tierKey,
                    'tier_rate_percent' => $tierRatePercent,
                ],
            ],
            'repeat_bonus' => [
                'amount' => $repeatBonus,
                'tier_points' => 0,
            ],
            'welcome_bonus' => [
                'amount' => $welcomeBonus,
                'tier_points' => 0,
            ],
            'promo_bonus' => [
                'amount' => $promoBonus,
                'tier_points' => $promoTierPoints,
                'campaign_id' => $campaignPreview['campaign_id'] ?? null,
                'meta' => $campaignPreview['meta'] ?? [],
            ],
        ];

        return [
            'eligible_user' => $userId === null ? null : true,
            'eligibility_reason' => null,
            'eligible_spend' => $eligibleSpend,
            'base_credit' => $baseCredit,
            'tier_bonus' => $tierBonus,
            'repeat_bonus' => $repeatBonus,
            'welcome_bonus' => $welcomeBonus,
            'promo_bonus' => $promoBonus,
            'promo_tier_points' => $promoTierPoints,
            'promo_campaign' => $campaignPreview['campaign'] ?? null,
            'total_credit_preview' => $baseCredit + $tierBonus + $repeatBonus + $welcomeBonus + $promoBonus,
            'tier_points_preview' => $baseCredit + $tierBonus + $promoTierPoints,
            'tier' => $tierKey,
            'tier_rate_percent' => $tierRatePercent,
            'qualifies_repeat_bonus' => $qualifiesRepeat,
            'qualifies_welcome_bonus' => $qualifiesWelcome,
            'rules' => $rules,
            'components' => $components,
        ];
    }

    public function previewTierForUser(User|int $user): array
    {
        $userId = $this->resolveUserId($user);

        if (! $this->isEligibleUserForBandaraCredit($userId)) {
            return [
                'eligible_user' => false,
                'tier' => 'ineligible',
                'tier_points' => 0,
                'rolling_spend' => 0,
                'birthday_credit' => 0,
                'current_tier_min_threshold' => null,
                'next_tier' => null,
                'next_tier_threshold' => null,
                'amount_to_next_tier' => 0,
                'progress_percentage' => 0.0,
                'tier_source' => 'ineligible',
                'tier_valid_until' => null,
            ];
        }

        $tierPoints = $this->annualTierPointsForUser($userId);
        $annualPreview = $this->previewTierForPoints($tierPoints);
        $annualPreview['eligible_user'] = true;
        $annualPreview['tier_source'] = 'annual_points';
        $annualPreview['tier_valid_until'] = null;

        $retained = $this->retainedTierForUser($userId);
        if ($retained && $this->tierRank((string) $retained->tier) > $this->tierRank((string) $annualPreview['tier'])) {
            $retainedPreview = $this->previewTierForPoints($tierPoints);
            $retainedPreview['eligible_user'] = true;
            $retainedPreview['tier'] = (string) $retained->tier;
            $retainedPreview['tier_source'] = 'retained_status';
            $retainedPreview['tier_valid_until'] = (string) $retained->valid_until;
            $retainedPreview['tier_points'] = $tierPoints;
            $retainedPreview['rolling_spend'] = $tierPoints;

            return $retainedPreview;
        }

        return $annualPreview;
    }

    public function previewTierForSpend(int|float $rollingSpend): array
    {
        // Backward-compatible wrapper. New rewards logic uses annual tier points,
        // not annual spend or current wallet balance.
        return $this->previewTierForPoints((int) floor(max(0, (float) $rollingSpend)));
    }

    /**
     * Order lifecycle entry point used by controllers. This is intentionally
     * separate from the manual CLI post method so AUTO_POST can control
     * automatic queue/post writes from order status changes. Cancellation
     * reversals are corrective accounting and remain available.
     */
    public function syncOrderLifecycle(Model $order, ?string $previousStatus = null): array
    {
        $status = strtolower((string) $this->orderValue($order, 'status', ''));
        $successfulStatuses = array_map(
            fn ($value) => strtolower((string) $value),
            (array) config('bandara_credit.successful_statuses', ['delivered', 'completed'])
        );

        $cancelledStatuses = array_map(
            fn ($value) => strtolower((string) $value),
            (array) config('bandara_credit.cancelled_statuses', ['cancelled'])
        );

        if (in_array($status, $cancelledStatuses, true)) {
            return $this->cancelEarnForOrder($order, respectAutoPost: false);
        }

        if (in_array($status, $successfulStatuses, true)) {
            return $this->postEarnForSuccessfulOrder($order, respectAutoPost: true);
        }

        return $this->queueEarnForOrder($order, respectAutoPost: true);
    }

    public function queueEarnForOrder(Model $order, bool $respectAutoPost = false): array
    {
        $guard = $this->earningGuard(
            order: $order,
            requireSuccessful: false,
            respectAutoPost: $respectAutoPost
        );

        $preview = $this->safePreview($order);

        if (! $guard['allowed']) {
            return $this->writeResult(
                action: $guard['reason'] === 'shadow_mode' ? 'shadow' : 'skipped',
                reason: $guard['reason'],
                orderId: $guard['order_id'],
                userId: $guard['user_id'],
                preview: $preview
            );
        }

        $components = $this->earnComponentsForPreview($preview);
        $total = $this->componentAmountTotal($components);

        if ($total <= 0) {
            return $this->writeResult(
                action: 'skipped',
                reason: 'nothing_to_queue',
                orderId: $guard['order_id'],
                userId: $guard['user_id'],
                preview: $preview
            );
        }

        return DB::transaction(function () use ($guard, $order, $preview, $components) {
            $orderId = (int) $guard['order_id'];
            $userId = (int) $guard['user_id'];
            $created = [];
            $updated = [];
            $skipped = [];

            if ($this->hasPostedLegacyEarnForOrder($orderId, $userId)) {
                return $this->writeResult(
                    action: 'skipped',
                    reason: 'legacy_reward_already_posted',
                    orderId: $orderId,
                    userId: $userId,
                    preview: $preview,
                    wallet: $this->syncWalletForUser($userId)
                );
            }

            $this->cancelLegacyPendingOrderReward($orderId, $userId);

            foreach ($components as $type => $component) {
                $amount = $this->componentAmount($component);
                $tierPoints = $this->componentTierPoints($component);
                $campaignId = $this->componentCampaignId($component);
                $componentMeta = $this->componentMeta($component);

                if ($amount <= 0) {
                    continue;
                }

                $tx = $this->lockTransactionByKey($this->transactionKey($orderId, $type));
                $payload = $this->transactionPayload(
                    userId: $userId,
                    orderId: $orderId,
                    type: $type,
                    amount: $amount,
                    status: 'pending',
                    key: $this->transactionKey($orderId, $type),
                    note: 'Pending Bandara Credit for order #'.$this->orderLabel($order),
                    meta: $this->previewMeta($preview) + ['source' => 'order_lifecycle'] + $componentMeta,
                    tierPoints: $tierPoints,
                    campaignId: $campaignId
                );

                if ($tx) {
                    if ($tx->status === 'posted') {
                        $skipped[] = ['type' => $type, 'amount' => (int) $tx->amount, 'reason' => 'already_posted'];
                        continue;
                    }

                    $dirty = (int) $tx->amount !== $amount || (string) $tx->status !== 'pending';

                    $tx->forceFill($payload)->save();

                    if ($dirty) {
                        $updated[] = ['type' => $type, 'amount' => $amount, 'tier_points' => $tierPoints, 'campaign_id' => $campaignId];
                    }

                    continue;
                }

                BandaraCreditTransaction::query()->create($payload);
                $created[] = ['type' => $type, 'amount' => $amount, 'tier_points' => $tierPoints, 'campaign_id' => $campaignId];
            }

            $changed = count($created) + count($updated);

            return $this->writeResult(
                action: $changed > 0 ? 'queued' : 'skipped',
                reason: $changed > 0 ? null : 'already_queued_or_posted',
                orderId: $orderId,
                userId: $userId,
                preview: $preview,
                wallet: $this->walletSnapshot($userId),
                extra: [
                    'queued' => $changed > 0,
                    'transactions_queued' => array_values(array_merge($created, $updated)),
                    'queued_components' => array_values(array_merge($created, $updated)),
                    'skipped_components' => $skipped,
                    'total_queued' => (int) array_sum(array_column(array_merge($created, $updated), 'amount')),
                ]
            );
        });
    }

    public function canPostEarnForOrder(Model $order): bool
    {
        return $this->earningGuard(
            order: $order,
            requireSuccessful: true,
            respectAutoPost: false
        )['allowed'];
    }

    public function postEarnForSuccessfulOrder(Model $order, bool $respectAutoPost = false): array
    {
        $guard = $this->earningGuard(
            order: $order,
            requireSuccessful: true,
            respectAutoPost: $respectAutoPost
        );

        $preview = $this->safePreview($order);

        if (! $guard['allowed']) {
            return $this->writeResult(
                action: $guard['reason'] === 'shadow_mode' ? 'shadow' : 'skipped',
                reason: $guard['reason'],
                orderId: $guard['order_id'],
                userId: $guard['user_id'],
                preview: $preview,
                legacyKeys: true
            );
        }

        $components = $this->earnComponentsForPreview($preview);
        $total = $this->componentAmountTotal($components);

        if ($total <= 0) {
            return $this->writeResult(
                action: 'skipped',
                reason: 'nothing_to_post',
                orderId: $guard['order_id'],
                userId: $guard['user_id'],
                preview: $preview,
                legacyKeys: true
            );
        }

        return DB::transaction(function () use ($guard, $order, $preview, $components) {
            $orderId = (int) $guard['order_id'];
            $userId = (int) $guard['user_id'];
            $posted = [];
            $skipped = [];
            $releasedReversals = 0;

            $wallet = $this->lockOrCreateWallet($userId);

            if ($this->hasPostedLegacyEarnForOrder($orderId, $userId)) {
                $wallet = $this->syncWalletForUser($userId);

                return $this->writeResult(
                    action: 'skipped',
                    reason: 'legacy_reward_already_posted',
                    orderId: $orderId,
                    userId: $userId,
                    preview: $preview,
                    wallet: $wallet,
                    legacyKeys: true
                );
            }

            $this->cancelLegacyPendingOrderReward($orderId, $userId);
            $releasedReversals = $this->releaseEarnReversalsForOrder($orderId, $userId);

            foreach ($components as $type => $component) {
                $amount = $this->componentAmount($component);
                $tierPoints = $this->componentTierPoints($component);
                $campaignId = $this->componentCampaignId($component);
                $componentMeta = $this->componentMeta($component);

                if ($amount <= 0) {
                    continue;
                }

                $key = $this->transactionKey($orderId, $type);
                $tx = $this->lockTransactionByKey($key);
                $payload = $this->transactionPayload(
                    userId: $userId,
                    orderId: $orderId,
                    type: $type,
                    amount: $amount,
                    status: 'posted',
                    key: $key,
                    note: 'Bandara Credit earned for order #'.$this->orderLabel($order),
                    meta: $this->previewMeta($preview) + ['source' => 'order_lifecycle'] + $componentMeta,
                    tierPoints: $tierPoints,
                    campaignId: $campaignId
                );

                if ($tx) {
                    if ($tx->status === 'posted') {
                        $skipped[] = ['type' => $type, 'amount' => (int) $tx->amount, 'reason' => 'already_posted'];
                        continue;
                    }

                    $tx->forceFill($payload)->save();
                    $posted[] = ['type' => $type, 'amount' => $amount, 'tier_points' => $tierPoints, 'campaign_id' => $campaignId];
                    continue;
                }

                BandaraCreditTransaction::query()->create($payload);
                $posted[] = ['type' => $type, 'amount' => $amount, 'tier_points' => $tierPoints, 'campaign_id' => $campaignId];
            }

            $wallet = $this->syncWalletForUser($userId);

            $totalPosted = (int) array_sum(array_column($posted, 'amount'));
            $changed = $totalPosted > 0 || $releasedReversals > 0;

            if ($totalPosted > 0) {
                $this->notifyRewardUser(
                    $userId,
                    'Bandara Credit earned',
                    number_format($totalPosted).' Bandara Credit point'.($totalPosted === 1 ? '' : 's').' posted for order #'.$this->orderLabel($order).'.',
                    ['order_id' => $orderId, 'points' => $totalPosted, 'event' => 'earned']
                );
            }

            return $this->writeResult(
                action: $changed ? 'posted' : 'skipped',
                reason: $changed ? null : 'already_posted_or_nothing_to_post',
                orderId: $orderId,
                userId: $userId,
                preview: $preview,
                wallet: $wallet,
                extra: [
                    'posted' => $changed,
                    'transactions_posted' => $posted,
                    'skipped_components' => $skipped,
                    'total_posted' => $totalPosted,
                    'released_reversals' => $releasedReversals,
                ],
                legacyKeys: true
            );
        });
    }

    public function cancelEarnForOrder(Model $order, bool $respectAutoPost = false): array
    {
        $guard = $this->cancellationGuard($order, $respectAutoPost);

        if (! $guard['allowed']) {
            return $this->writeResult(
                action: $guard['reason'] === 'shadow_mode' ? 'shadow' : 'skipped',
                reason: $guard['reason'],
                orderId: $guard['order_id'],
                userId: $guard['user_id'],
                legacyKeys: true
            );
        }

        return DB::transaction(function () use ($guard, $order) {
            $orderId = (int) $guard['order_id'];
            $userId = (int) $guard['user_id'];

            $cancelledPending = $this->cancelPendingEarnTransactions($orderId, $userId);
            $legacyPendingCancelled = $this->cancelLegacyPendingOrderReward($orderId, $userId);
            $reversalChanged = 0;
            $reversals = [];

            $postedEarnTransactions = BandaraCreditTransaction::query()
                ->where('user_id', $userId)
                ->where('order_id', $orderId)
                ->whereIn('type', $this->allPositiveEarnTypes())
                ->where('status', 'posted')
                ->where('amount', '>', 0)
                ->lockForUpdate()
                ->get();

            foreach ($postedEarnTransactions as $postedTx) {
                $amountToReverse = abs((int) $postedTx->amount);
                $tierPointsToReverse = abs((int) ($postedTx->tier_points ?? 0));
                if ($amountToReverse <= 0) {
                    continue;
                }

                $key = $this->reversalKey($orderId, (string) $postedTx->type);
                $tx = $this->lockTransactionByKey($key);
                $payload = $this->transactionPayload(
                    userId: $userId,
                    orderId: $orderId,
                    type: 'earn_reversal',
                    amount: -1 * $amountToReverse,
                    status: 'posted',
                    key: $key,
                    note: 'Bandara Credit reversed for cancelled order #'.$this->orderLabel($order),
                    meta: [
                        'source' => 'order_cancellation',
                        'reverses_transaction_id' => $postedTx->id,
                        'reverses_type' => (string) $postedTx->type,
                    ],
                    tierPoints: -1 * $tierPointsToReverse
                );

                if ($tx) {
                    if ((string) $tx->status !== 'posted' || (int) $tx->amount !== -1 * $amountToReverse) {
                        $tx->forceFill($payload)->save();
                        $reversalChanged += $amountToReverse;
                    }
                } else {
                    BandaraCreditTransaction::query()->create($payload);
                    $reversalChanged += $amountToReverse;
                }

                $reversals[] = [
                    'type' => (string) $postedTx->type,
                    'amount' => $amountToReverse,
                ];
            }

            $releasedRedemption = $this->releaseReservedRedemptionTransactions($orderId, $userId, 'order_cancelled');
            $restoredRedemption = $this->reversePostedRedemptionTransactions($order, $orderId, $userId, 'order_cancelled');

            $changed = $cancelledPending + $legacyPendingCancelled + $reversalChanged + $releasedRedemption + $restoredRedemption;
            $wallet = $changed > 0
                ? $this->syncWalletForUser($userId)
                : $this->walletSnapshot($userId);

            return $this->writeResult(
                action: $changed > 0 ? 'cancelled' : 'skipped',
                reason: $changed > 0 ? null : 'nothing_to_cancel',
                orderId: $orderId,
                userId: $userId,
                wallet: $wallet,
                extra: [
                    'cancelled_pending' => $cancelledPending + $legacyPendingCancelled,
                    'reversed_posted' => $reversalChanged,
                    'total_reversed' => $reversalChanged,
                    'transactions_reversed' => $reversals,
                    'redemption_released' => $releasedRedemption,
                    'redemption_restored' => $restoredRedemption,
                ],
                legacyKeys: true
            );
        });
    }

    public function syncWalletForUser(User|int $user): BandaraCreditWallet
    {
        $userId = $this->resolveUserId($user);
        $wallet = $this->lockOrCreateWallet($userId);

        $balance = (int) BandaraCreditTransaction::query()
            ->where('user_id', $userId)
            ->where('status', 'posted')
            ->sum('amount');

        $tierPreview = $this->previewTierForUser($userId);
        $tier = ($tierPreview['eligible_user'] ?? false)
            ? (string) ($tierPreview['tier'] ?? 'silver')
            : (string) $wallet->tier;

        $wallet->forceFill([
            'balance' => max($balance, 0),
            'tier' => $tier,
        ])->save();

        $this->recordTierAchievement($userId, $tier, (int) ($tierPreview['tier_points'] ?? 0));

        return $wallet->refresh();
    }

    /**
     * Order-linked reward adjustment for partial refunds/cancellations and
     * support/admin corrections. Writes only offsetting ledger entries.
     */
    public function postOrderRewardAdjustment(
        Model $order,
        string $adjustmentType,
        int $points,
        int $tierPoints = 0,
        ?string $note = null,
        ?int $createdById = null,
        string $source = 'admin_order_reward_adjustment'
    ): array {
        $orderId = $this->extractOrderId($order);
        $userId = $this->nullableInt($this->orderValue($order, 'user_id'));
        $points = max(0, abs($points));
        $tierPoints = abs($tierPoints);

        if ($orderId === null || $userId === null) {
            return $this->writeResult('skipped', 'missing_order_or_user', $orderId, $userId);
        }

        if (! $this->isEligibleUserForBandaraCredit($userId)) {
            return $this->writeResult('skipped', 'user_not_eligible', $orderId, $userId);
        }

        if ($points <= 0 && $tierPoints <= 0) {
            return $this->writeResult('skipped', 'nothing_to_adjust', $orderId, $userId);
        }

        $adjustmentType = strtolower(trim($adjustmentType));

        [$type, $amount, $tierDelta] = match ($adjustmentType) {
            'earn_reversal', 'partial_refund', 'partial_cancellation' => ['earn_reversal', -1 * $points, -1 * ($tierPoints ?: $points)],
            'redeem_restore', 'redeem_reversal', 'redemption_restore' => ['redeem_reversal', $points, 0],
            'manual_debit', 'admin_debit' => ['admin_debit', -1 * $points, -1 * $tierPoints],
            default => ['manual_credit', $points, $tierPoints],
        };

        // Guard corrections so admin/support cannot accidentally reverse more
        // than the order has earned, or restore more redemption than was posted.
        if ($type === 'earn_reversal') {
            $postedEarn = (int) BandaraCreditTransaction::query()
                ->where('user_id', $userId)
                ->where('order_id', $orderId)
                ->whereIn('type', $this->allPositiveEarnTypes())
                ->where('status', 'posted')
                ->where('amount', '>', 0)
                ->sum('amount');

            $alreadyReversed = abs((int) BandaraCreditTransaction::query()
                ->where('user_id', $userId)
                ->where('order_id', $orderId)
                ->whereIn('type', ['earn_reversal', 'reversal'])
                ->where('status', 'posted')
                ->where('amount', '<', 0)
                ->sum('amount'));

            $postedTier = (int) BandaraCreditTransaction::query()
                ->where('user_id', $userId)
                ->where('order_id', $orderId)
                ->whereIn('type', $this->allPositiveEarnTypes())
                ->where('status', 'posted')
                ->where('tier_points', '>', 0)
                ->sum('tier_points');

            $alreadyTierReversed = abs((int) BandaraCreditTransaction::query()
                ->where('user_id', $userId)
                ->where('order_id', $orderId)
                ->whereIn('type', ['earn_reversal', 'reversal'])
                ->where('status', 'posted')
                ->where('tier_points', '<', 0)
                ->sum('tier_points'));

            $amount = -1 * min(abs($amount), max(0, $postedEarn - $alreadyReversed));
            $tierDelta = -1 * min(abs($tierDelta), max(0, $postedTier - $alreadyTierReversed));
        }

        if ($type === 'redeem_reversal') {
            $postedRedeemed = abs((int) BandaraCreditTransaction::query()
                ->where('user_id', $userId)
                ->where('order_id', $orderId)
                ->whereIn('type', $this->postedRedemptionTypes())
                ->where('status', 'posted')
                ->where('amount', '<', 0)
                ->sum('amount'));

            $alreadyRestored = (int) BandaraCreditTransaction::query()
                ->where('user_id', $userId)
                ->where('order_id', $orderId)
                ->whereIn('type', $this->redemptionReversalTypes())
                ->where('status', 'posted')
                ->where('amount', '>', 0)
                ->sum('amount');

            $amount = min($amount, max(0, $postedRedeemed - $alreadyRestored));
            $tierDelta = 0;
        }

        if ($amount === 0 && $tierDelta === 0) {
            return $this->writeResult('skipped', 'nothing_to_adjust', $orderId, $userId);
        }

        return DB::transaction(function () use ($order, $orderId, $userId, $type, $amount, $tierDelta, $note, $createdById, $source, $adjustmentType) {
            $this->lockOrCreateWallet($userId);

            $tx = BandaraCreditTransaction::query()->create($this->transactionPayload(
                userId: $userId,
                orderId: $orderId,
                type: $type,
                amount: $amount,
                status: 'posted',
                key: 'order-adjustment:'.$orderId.':'.Str::uuid()->toString(),
                note: $note ?: 'Bandara Credit correction for order #'.$this->orderLabel($order),
                meta: [
                    'source' => $source,
                    'adjustment_type' => $adjustmentType,
                    'order_label' => $this->orderLabel($order),
                    'created_by_id' => $createdById,
                ],
                tierPoints: $tierDelta,
                createdById: $createdById
            ));

            $wallet = $this->syncWalletForUser($userId);

            $this->notifyRewardUser(
                $userId,
                $amount >= 0 ? 'Bandara Credit adjusted' : 'Bandara Credit reversed',
                ($amount >= 0 ? '+' : '').number_format($amount).' Bandara Credit point'.(abs($amount) === 1 ? '' : 's').' adjusted for order #'.$this->orderLabel($order).'.',
                ['order_id' => $orderId, 'points_delta' => $amount, 'tier_points_delta' => $tierDelta, 'event' => 'order_adjustment']
            );

            return $this->writeResult(
                action: 'adjusted',
                reason: null,
                orderId: $orderId,
                userId: $userId,
                wallet: $wallet,
                extra: [
                    'transaction_id' => $tx->id,
                    'type' => $type,
                    'wallet_points_delta' => $amount,
                    'tier_points_delta' => $tierDelta,
                ]
            );
        });
    }

    public function countExpiredRedemptionReservations(?int $olderThanMinutes = null): array
    {
        $olderThanMinutes = $olderThanMinutes ?? max(1, (int) config('bandara_credit.redemption.reservation_ttl_minutes', 120));
        $cutoff = now()->subMinutes($olderThanMinutes);

        if (! Schema::hasTable('bandara_credit_transactions')) {
            return ['reservations_count' => 0, 'points' => 0, 'cutoff' => $cutoff];
        }

        $query = BandaraCreditTransaction::query()
            ->whereIn('type', $this->reservedRedemptionTypes())
            ->where('status', 'reserved')
            ->where('amount', '<', 0)
            ->where('created_at', '<=', $cutoff);

        return [
            'reservations_count' => (int) (clone $query)->count(),
            'points' => abs((int) (clone $query)->sum('amount')),
            'cutoff' => $cutoff,
        ];
    }

    public function b2cEligibilityAudit(): array
    {
        $empty = [
            'non_b2c_wallets' => 0,
            'non_b2c_transactions' => 0,
            'non_b2c_posted_positive_points' => 0,
            'non_b2c_posted_redeemed_points' => 0,
            'non_b2c_reserved_redemption_points' => 0,
            'non_b2c_orders_with_credit_discount' => 0,
            'sample_users' => [],
        ];

        if (! Schema::hasTable('users')) {
            return $empty;
        }

        $b2cValue = (string) config('bandara_credit.eligibility.b2c_value', 'b2c');
        $nonB2cIds = User::query()
            ->where(fn ($query) => $query->whereNull('customer_type')->orWhere('customer_type', '!=', $b2cValue))
            ->pluck('id');

        if ($nonB2cIds->isEmpty()) {
            return $empty;
        }

        $result = $empty;

        if (Schema::hasTable('bandara_credit_wallets')) {
            $result['non_b2c_wallets'] = (int) DB::table('bandara_credit_wallets')->whereIn('user_id', $nonB2cIds)->count();
        }

        if (Schema::hasTable('bandara_credit_transactions')) {
            $tx = DB::table('bandara_credit_transactions')->whereIn('user_id', $nonB2cIds);
            $result['non_b2c_transactions'] = (int) (clone $tx)->count();
            $result['non_b2c_posted_positive_points'] = (int) (clone $tx)->where('status', 'posted')->where('amount', '>', 0)->sum('amount');
            $result['non_b2c_posted_redeemed_points'] = abs((int) (clone $tx)->where('status', 'posted')->where('amount', '<', 0)->sum('amount'));
            $result['non_b2c_reserved_redemption_points'] = abs((int) (clone $tx)->where('status', 'reserved')->where('amount', '<', 0)->sum('amount'));
        }

        if (Schema::hasTable('orders')) {
            $ordersQuery = DB::table('orders')->whereIn('user_id', $nonB2cIds);

            if (Schema::hasColumn('orders', 'bandara_credit_redeemed_points')) {
                $result['non_b2c_orders_with_credit_discount'] = (int) (clone $ordersQuery)
                    ->where('bandara_credit_redeemed_points', '>', 0)
                    ->count();
            } elseif (Schema::hasColumn('orders', 'bandara_credit_redeemed_amount')) {
                $result['non_b2c_orders_with_credit_discount'] = (int) (clone $ordersQuery)
                    ->where('bandara_credit_redeemed_amount', '>', 0)
                    ->count();
            } elseif (Schema::hasColumn('orders', 'bandara_credit_discount_total')) {
                $result['non_b2c_orders_with_credit_discount'] = (int) (clone $ordersQuery)
                    ->where('bandara_credit_discount_total', '>', 0)
                    ->count();
            }
        }

        $result['sample_users'] = User::query()
            ->whereIn('id', $nonB2cIds)
            ->where(function ($query) {
                if (Schema::hasTable('bandara_credit_wallets')) {
                    $query->whereExists(function ($sub) {
                        $sub->selectRaw('1')
                            ->from('bandara_credit_wallets')
                            ->whereColumn('bandara_credit_wallets.user_id', 'users.id');
                    });
                }

                if (Schema::hasTable('bandara_credit_transactions')) {
                    $query->orWhereExists(function ($sub) {
                        $sub->selectRaw('1')
                            ->from('bandara_credit_transactions')
                            ->whereColumn('bandara_credit_transactions.user_id', 'users.id');
                    });
                }
            })
            ->limit(10)
            ->get(['id', 'name', 'email', 'customer_type'])
            ->map(fn (User $user) => [
                'id' => (int) $user->id,
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'customer_type' => (string) $user->customer_type,
            ])
            ->all();

        return $result;
    }

    public function redemptionEnabled(): bool
    {
        return (bool) config('bandara_credit.enabled', false)
            && ! (bool) config('bandara_credit.shadow_mode', true)
            && (bool) config('bandara_credit.redeem_enabled', false);
    }

    public function availableRedeemablePoints(User|int $user): int
    {
        $userId = $this->resolveUserId($user);

        if (! $this->isEligibleUserForBandaraCredit($userId)) {
            return 0;
        }

        $wallet = $this->getOrCreateWallet($userId);

        return max(0, (int) $wallet->balance - $this->reservedRedemptionPointsForUser($userId));
    }

    public function redemptionQuoteForCheckout(User|int $user, float|int $orderAmount, int $requestedPoints = 0, array $context = []): array
    {
        $userId = $this->resolveUserId($user);
        $orderAmount = round(max(0, (float) $orderAmount), 2);
        $requestedPoints = max(0, (int) $requestedPoints);

        $pointValue = max(0.01, (float) config('bandara_credit.redemption.point_value', 1));
        $minimumPoints = max(0, (int) config('bandara_credit.redemption.minimum_points', 500));
        $maxOrderPercent = max(0, min(100, (float) config('bandara_credit.redemption.max_order_percent', 20)));
        $reservedPoints = $this->reservedRedemptionPointsForUser($userId);
        $wallet = $this->getOrCreateWallet($userId);
        $availablePoints = max(0, (int) $wallet->balance - $reservedPoints);

        $enabled = $this->redemptionEnabled();
        $eligibleUser = $this->isEligibleUserForBandaraCredit($userId);
        $maxRedeemAmountByOrder = round($orderAmount * ($maxOrderPercent / 100), 2);
        $maxRedeemAmount = min($orderAmount, $maxRedeemAmountByOrder);
        $maxPointsByOrder = (int) floor($maxRedeemAmount / $pointValue);
        $maxRedeemablePoints = max(0, min($availablePoints, $maxPointsByOrder));

        $reason = null;
        $message = null;
        $pointsToRedeem = 0;

        if (! $enabled) {
            $reason = 'redemption_disabled';
            $message = 'Bandara Credit redemption is not enabled yet.';
        } elseif (! $eligibleUser) {
            $reason = 'user_not_eligible';
            $message = 'Bandara Credit redemption is available only for eligible customer accounts.';
        } elseif ($orderAmount <= 0) {
            $reason = 'order_amount_not_eligible';
            $message = 'This order amount is not eligible for Bandara Credit redemption.';
        } elseif ($availablePoints <= 0) {
            $reason = 'no_available_points';
            $message = 'You do not have available Bandara Credit to redeem right now.';
        } elseif ($minimumPoints > 0 && $availablePoints < $minimumPoints) {
            $reason = 'minimum_balance_not_met';
            $message = 'You need at least '.number_format($minimumPoints).' Bandara Credit points to redeem.';
        } elseif ($maxRedeemablePoints <= 0) {
            $reason = 'order_cap_zero';
            $message = 'Bandara Credit cannot be applied to this order amount.';
        } elseif ($requestedPoints > 0) {
            if ($minimumPoints > 0 && $requestedPoints < $minimumPoints) {
                $reason = 'requested_below_minimum';
                $message = 'Please redeem at least '.number_format($minimumPoints).' Bandara Credit points.';
            } else {
                $pointsToRedeem = min($requestedPoints, $maxRedeemablePoints);

                if ($requestedPoints > $maxRedeemablePoints) {
                    $reason = 'requested_capped';
                    $message = 'Applied the maximum eligible Bandara Credit for this order.';
                }
            }
        }

        $redeemAmount = round(min($orderAmount, $pointsToRedeem * $pointValue), 2);

        return [
            'enabled' => $enabled,
            'eligible_user' => $eligibleUser,
            'can_redeem' => $enabled && $eligibleUser && $maxRedeemablePoints > 0 && ($minimumPoints <= 0 || $availablePoints >= $minimumPoints),
            'reason' => $reason,
            'message' => $message,
            'wallet_balance' => (int) $wallet->balance,
            'available_points' => $availablePoints,
            'reserved_points' => $reservedPoints,
            'minimum_points' => $minimumPoints,
            'point_value' => $pointValue,
            'max_order_percent' => $maxOrderPercent,
            'max_points_by_order' => $maxPointsByOrder,
            'max_redeemable_points' => $maxRedeemablePoints,
            'requested_points' => $requestedPoints,
            'points_to_redeem' => $pointsToRedeem,
            'redeem_amount' => $redeemAmount,
            'order_amount_before_credit' => $orderAmount,
            'order_amount_after_credit' => round(max(0, $orderAmount - $redeemAmount), 2),
            'context' => $context,
        ];
    }

    public function reserveRedemptionForOrder(Model $order, int $points, ?float $amount = null, array $meta = []): array
    {
        $orderId = $order->exists ? (int) $order->getKey() : null;
        $userId = $this->nullableInt($this->orderValue($order, 'user_id'));
        $points = max(0, (int) $points);

        if (! $this->redemptionEnabled()) {
            return $this->writeResult('skipped', 'redemption_disabled', $orderId, $userId);
        }

        if ($orderId === null || $userId === null) {
            return $this->writeResult('skipped', 'missing_order_or_user', $orderId, $userId);
        }

        if ($points <= 0) {
            return $this->writeResult('skipped', 'nothing_to_reserve', $orderId, $userId);
        }

        return DB::transaction(function () use ($order, $orderId, $userId, $points, $amount, $meta) {
            $wallet = $this->lockOrCreateWallet($userId);
            $reservedForOtherOrders = $this->reservedRedemptionPointsForUser($userId, $orderId, lock: true);
            $availablePoints = max(0, (int) $wallet->balance - $reservedForOtherOrders);

            if ($points > $availablePoints) {
                return $this->writeResult(
                    action: 'skipped',
                    reason: 'insufficient_available_points',
                    orderId: $orderId,
                    userId: $userId,
                    wallet: $wallet,
                    extra: [
                        'requested_points' => $points,
                        'available_points' => $availablePoints,
                    ]
                );
            }

            $pointValue = max(0.01, (float) config('bandara_credit.redemption.point_value', 1));
            $redeemAmount = round((float) ($amount ?? ($points * $pointValue)), 2);
            $key = $this->redemptionReserveKey($orderId);
            $expiresAt = now()->addMinutes(max(1, (int) config('bandara_credit.redemption.reservation_ttl_minutes', 120)));

            $payload = $this->transactionPayload(
                userId: $userId,
                orderId: $orderId,
                type: 'redeem_reserved',
                amount: -1 * $points,
                status: 'reserved',
                key: $key,
                note: 'Bandara Credit reserved for order #'.$this->orderLabel($order),
                meta: array_filter($meta + [
                    'source' => 'checkout',
                    'point_value' => $pointValue,
                    'redeem_amount' => $redeemAmount,
                    'reserved_at' => now()->toDateTimeString(),
                    'expires_at' => $expiresAt->toDateTimeString(),
                ], fn ($value) => $value !== null)
            );

            $tx = $this->lockTransactionByKey($key);

            if ($tx && $tx->status === 'posted') {
                return $this->writeResult('skipped', 'redemption_already_posted', $orderId, $userId, wallet: $wallet);
            }

            if ($tx) {
                $tx->forceFill($payload)->save();
            } else {
                $tx = BandaraCreditTransaction::query()->create($payload);
            }

            return $this->writeResult(
                action: 'reserved',
                reason: null,
                orderId: $orderId,
                userId: $userId,
                wallet: $wallet,
                extra: [
                    'transaction_id' => $tx->id,
                    'points_reserved' => $points,
                    'redeem_amount' => $redeemAmount,
                    'available_points_after_reservation' => max(0, $availablePoints - $points),
                ]
            );
        });
    }

    public function postReservedRedemptionForOrder(Model $order, mixed $payment = null): array
    {
        $orderId = $order->exists ? (int) $order->getKey() : null;
        $userId = $this->nullableInt($this->orderValue($order, 'user_id'));

        if ($orderId === null || $userId === null) {
            return $this->writeResult('skipped', 'missing_order_or_user', $orderId, $userId);
        }

        return DB::transaction(function () use ($order, $orderId, $userId, $payment) {
            $reserved = BandaraCreditTransaction::query()
                ->where('user_id', $userId)
                ->where('order_id', $orderId)
                ->where('type', 'redeem_reserved')
                ->where('status', 'reserved')
                ->where('amount', '<', 0)
                ->lockForUpdate()
                ->get();

            $postedPoints = 0;

            foreach ($reserved as $tx) {
                $points = abs((int) $tx->amount);

                if ($points <= 0) {
                    continue;
                }

                $meta = $tx->meta ?? [];
                $meta['posted_at'] = now()->toDateTimeString();
                if ($payment !== null) {
                    $meta['payment_id'] = data_get($payment, 'id');
                    $meta['payment_transaction_id'] = data_get($payment, 'transaction_id');
                }

                $tx->forceFill([
                    'type' => 'redeemed',
                    'status' => 'posted',
                    'note' => 'Bandara Credit redeemed for order #'.$this->orderLabel($order),
                    'meta' => $meta,
                ])->save();

                $postedPoints += $points;
            }

            $wallet = $postedPoints > 0
                ? $this->syncWalletForUser($userId)
                : $this->walletSnapshot($userId);

            if ($postedPoints > 0) {
                $this->notifyRewardUser(
                    $userId,
                    'Bandara Credit redeemed',
                    number_format($postedPoints).' Bandara Credit point'.($postedPoints === 1 ? '' : 's').' redeemed for order #'.$this->orderLabel($order).'.',
                    ['order_id' => $orderId, 'points' => $postedPoints, 'event' => 'redeemed']
                );
            }

            return $this->writeResult(
                action: $postedPoints > 0 ? 'posted' : 'skipped',
                reason: $postedPoints > 0 ? null : 'no_reserved_redemption',
                orderId: $orderId,
                userId: $userId,
                wallet: $wallet,
                extra: ['points_posted' => $postedPoints]
            );
        });
    }

    public function releaseReservedRedemptionForOrder(Model $order, string $reason = 'released'): array
    {
        $orderId = $order->exists ? (int) $order->getKey() : null;
        $userId = $this->nullableInt($this->orderValue($order, 'user_id'));

        if ($orderId === null || $userId === null) {
            return $this->writeResult('skipped', 'missing_order_or_user', $orderId, $userId);
        }

        return DB::transaction(function () use ($orderId, $userId, $reason) {
            $released = $this->releaseReservedRedemptionTransactions($orderId, $userId, $reason);
            $wallet = $this->walletSnapshot($userId);

            if ($released > 0) {
                $this->notifyRewardUser(
                    $userId,
                    'Bandara Credit reservation released',
                    number_format($released).' reserved Bandara Credit point'.($released === 1 ? '' : 's').' released for order #'.$orderId.'.',
                    ['order_id' => $orderId, 'points' => $released, 'event' => 'reservation_released', 'reason' => $reason]
                );
            }

            return $this->writeResult(
                action: $released > 0 ? 'released' : 'skipped',
                reason: $released > 0 ? null : 'no_reserved_redemption',
                orderId: $orderId,
                userId: $userId,
                wallet: $wallet,
                extra: ['points_released' => $released]
            );
        });
    }

    public function reversePostedRedemptionForOrder(Model $order, string $reason = 'order_cancelled'): array
    {
        $orderId = $order->exists ? (int) $order->getKey() : null;
        $userId = $this->nullableInt($this->orderValue($order, 'user_id'));

        if ($orderId === null || $userId === null) {
            return $this->writeResult('skipped', 'missing_order_or_user', $orderId, $userId);
        }

        return DB::transaction(function () use ($order, $orderId, $userId, $reason) {
            $restored = $this->reversePostedRedemptionTransactions($order, $orderId, $userId, $reason);
            $wallet = $restored > 0 ? $this->syncWalletForUser($userId) : $this->walletSnapshot($userId);

            if ($restored > 0) {
                $this->notifyRewardUser(
                    $userId,
                    'Bandara Credit restored',
                    number_format($restored).' Bandara Credit point'.($restored === 1 ? '' : 's').' restored for order #'.$this->orderLabel($order).'.',
                    ['order_id' => $orderId, 'points' => $restored, 'event' => 'redemption_restored', 'reason' => $reason]
                );
            }

            return $this->writeResult(
                action: $restored > 0 ? 'reversed' : 'skipped',
                reason: $restored > 0 ? null : 'no_posted_redemption',
                orderId: $orderId,
                userId: $userId,
                wallet: $wallet,
                extra: ['points_restored' => $restored]
            );
        });
    }

    public function cancelRedemptionForOrder(Model $order, string $reason = 'order_cancelled'): array
    {
        $orderId = $order->exists ? (int) $order->getKey() : null;
        $userId = $this->nullableInt($this->orderValue($order, 'user_id'));

        if ($orderId === null || $userId === null) {
            return $this->writeResult('skipped', 'missing_order_or_user', $orderId, $userId);
        }

        return DB::transaction(function () use ($order, $orderId, $userId, $reason) {
            $released = $this->releaseReservedRedemptionTransactions($orderId, $userId, $reason);
            $restored = $this->reversePostedRedemptionTransactions($order, $orderId, $userId, $reason);
            $wallet = ($released + $restored) > 0 ? $this->syncWalletForUser($userId) : $this->walletSnapshot($userId);

            return $this->writeResult(
                action: ($released + $restored) > 0 ? 'cancelled' : 'skipped',
                reason: ($released + $restored) > 0 ? null : 'no_redemption_to_cancel',
                orderId: $orderId,
                userId: $userId,
                wallet: $wallet,
                extra: [
                    'points_released' => $released,
                    'points_restored' => $restored,
                ]
            );
        });
    }

    public function snapshotForUser(int $userId): array
    {
        $programEnabled = (bool) config('bandara_credit.enabled', false);
        $redeemEnabled = $programEnabled
            && ! (bool) config('bandara_credit.shadow_mode', true)
            && (bool) config('bandara_credit.redeem_enabled', false);

        if (! $programEnabled || ! $this->isEligibleUserForBandaraCredit($userId)) {
            return [
                'programEnabled' => $programEnabled,
                'redeemEnabled' => $redeemEnabled,
                'redemptionEnabled' => $redeemEnabled,
                'eligibleUser' => false,
                'availablePoints' => 0,
                'walletBalance' => 0,
                'reservedPoints' => 0,
                'pendingPoints' => 0,
                'lifetimePoints' => 0,
                'redeemedPoints' => 0,
                'pointsHistory' => [],
                'nextRewardAt' => (int) config('bandara_credit.next_reward_at', config('bandara_credit.redemption.minimum_points', 500)),
                'currentTier' => 'ineligible',
                'earnEnabled' => (bool) config('bandara_credit.earn_enabled', false),
            ];
        }

        $wallet = DB::table('bandara_credit_wallets')
            ->where('user_id', $userId)
            ->first();

        $walletBalance = (int) ($wallet->balance ?? 0);
        $reservedRedemptionPoints = $this->reservedRedemptionPointsForUser($userId);
        $availablePoints = max(0, $walletBalance - $reservedRedemptionPoints);
        $tierPreview = $this->previewTierForUser($userId);
        $currentTier = strtolower((string) (($tierPreview['tier'] ?? null) ?: ($wallet->tier ?? 'silver')));
        $currentTierLabel = (string) ($tierPreview['tier_name'] ?? Str::headline($currentTier));
        $tierPoints = (int) ($tierPreview['tier_points'] ?? 0);
        $nextTier = $tierPreview['next_tier'] ?? null;
        $nextTierLabel = $nextTier ? Str::headline((string) $nextTier) : null;
        $pointsToNextTier = (int) ($tierPreview['amount_to_next_tier'] ?? 0);
        $tierProgressPercent = (float) ($tierPreview['progress_percentage'] ?? 0);
        $tierRewardRatePercent = (float) ($tierPreview['reward_rate_percent'] ?? $this->rewardRateForTier($currentTier));

        $pendingPoints = (int) DB::table('bandara_credit_transactions')
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'reserved'])
            ->where('amount', '>', 0)
            ->sum('amount');

        if ((bool) config('bandara_credit.earn_enabled', false)) {
            $queuedOrderIds = DB::table('bandara_credit_transactions')
                ->where('user_id', $userId)
                ->whereIn('status', ['pending', 'reserved'])
                ->whereNotNull('order_id')
                ->pluck('order_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $orderModel = config('bandara_credit.order_model');
            $orderInstance = new $orderModel();

            $pendingEstimate = (int) round(
                $this->pendingOrdersForUser($userId)
                    ->when(! empty($queuedOrderIds), function (Builder $query) use ($queuedOrderIds, $orderInstance) {
                        $query->whereNotIn($orderInstance->getKeyName(), $queuedOrderIds);
                    })
                    ->get()
                    ->sum(function ($order) {
                        $preview = $this->previewEarnForOrder($order);

                        return (int) ($preview['total_credit_preview'] ?? 0);
                    })
            );

            $pendingPoints += $pendingEstimate;
        }

        $lifetimePoints = (int) DB::table('bandara_credit_transactions')
            ->where('bandara_credit_transactions.user_id', $userId)
            ->where('bandara_credit_transactions.amount', '>', 0)
            ->where('bandara_credit_transactions.status', 'posted')
            ->whereNotIn('bandara_credit_transactions.type', $this->redemptionReversalTypes())
            ->sum('bandara_credit_transactions.amount');

        $redeemedPoints = abs((int) DB::table('bandara_credit_transactions')
            ->where('bandara_credit_transactions.user_id', $userId)
            ->whereIn('bandara_credit_transactions.type', $this->postedRedemptionTypes())
            ->where('bandara_credit_transactions.status', 'posted')
            ->where('bandara_credit_transactions.amount', '<', 0)
            ->sum('bandara_credit_transactions.amount'));

        $pointsHistory = DB::table('bandara_credit_transactions')
            ->leftJoin('orders', 'orders.id', '=', 'bandara_credit_transactions.order_id')
            ->where('bandara_credit_transactions.user_id', $userId)
            ->select([
                'bandara_credit_transactions.id as tx_id',
                'bandara_credit_transactions.order_id as tx_order_id',
                'bandara_credit_transactions.amount as tx_amount',
                'bandara_credit_transactions.type as tx_type',
                'bandara_credit_transactions.status as tx_status',
                'bandara_credit_transactions.note as tx_note',
                'bandara_credit_transactions.created_at as tx_created_at',
                'orders.order_number as order_number',
            ])
            ->orderByDesc('bandara_credit_transactions.created_at')
            ->orderByDesc('bandara_credit_transactions.id')
            ->limit(max(1, (int) config('bandara_credit.history_limit', 8)))
            ->get()
            ->map(function ($tx) {
                $status = strtolower((string) ($tx->tx_status ?? 'posted'));
                $points = (int) ($tx->tx_amount ?? 0);

                $subtitleParts = [];

                if (! empty($tx->tx_order_id)) {
                    $subtitleParts[] = 'Order #'.($tx->order_number ?? $tx->tx_order_id);
                }

                if (! empty($tx->tx_note)) {
                    $subtitleParts[] = Str::limit((string) $tx->tx_note, 60);
                }

                if ($status !== 'posted') {
                    $subtitleParts[] = Str::headline($status);
                }

                return [
                    'title' => $this->titleForTransaction($tx->tx_type ?? null, $points),
                    'order_number' => $tx->order_number ?? null,
                    'subtitle' => collect($subtitleParts)->filter()->implode(' • '),
                    'points' => $points,
                    'status' => $status,
                    'date' => ! empty($tx->tx_created_at)
                        ? Carbon::parse($tx->tx_created_at)->format('d M Y')
                        : null,
                ];
            })
            ->values()
            ->all();

        return [
            'programEnabled' => true,
            'redeemEnabled' => $redeemEnabled,
            'redemptionEnabled' => $redeemEnabled,
            'earnEnabled' => (bool) config('bandara_credit.earn_enabled', false),
            'eligibleUser' => true,
            'availablePoints' => $availablePoints,
            'walletBalance' => $walletBalance,
            'reservedPoints' => $reservedRedemptionPoints,
            'pendingPoints' => $pendingPoints,
            'lifetimePoints' => $lifetimePoints,
            'redeemedPoints' => $redeemedPoints,
            'pointsHistory' => $pointsHistory,
            'nextRewardAt' => (int) config('bandara_credit.next_reward_at', config('bandara_credit.redemption.minimum_points', 500)),
            'minimumRedeemPoints' => (int) config('bandara_credit.redemption.minimum_points', 500),
            'pointValue' => (float) config('bandara_credit.redemption.point_value', 1),
            'maxOrderPercent' => (float) config('bandara_credit.redemption.max_order_percent', 20),
            'currentTier' => $currentTier,
            'currentTierLabel' => $currentTierLabel,
            'currentTierName' => $currentTierLabel,
            'tierName' => $currentTierLabel,
            'annualTierPoints' => $tierPoints,
            'tierPoints' => $tierPoints,
            'tierSource' => (string) ($tierPreview['tier_source'] ?? 'annual_points'),
            'tierValidUntil' => $tierPreview['tier_valid_until'] ?? null,
            'nextTier' => $nextTier,
            'nextTierLabel' => $nextTierLabel,
            'nextTierThreshold' => $tierPreview['next_tier_threshold'] ?? null,
            'pointsToNextTier' => $pointsToNextTier,
            'tierProgressPercent' => $tierProgressPercent,
            'tierRewardRatePercent' => $tierRewardRatePercent,
        ];
    }

    protected function earningGuard(Model $order, bool $requireSuccessful, bool $respectAutoPost): array
    {
        $orderId = $order->exists ? (int) $order->getKey() : null;
        $userId = $this->nullableInt($this->orderValue($order, 'user_id'));
        $status = strtolower((string) $this->orderValue($order, 'status', ''));

        if (! (bool) config('bandara_credit.enabled', false)) {
            return $this->guardResult(false, 'feature_disabled', $orderId, $userId);
        }

        if ($respectAutoPost && ! (bool) config('bandara_credit.auto_post_enabled', false)) {
            return $this->guardResult(false, 'auto_post_disabled', $orderId, $userId);
        }

        if (! (bool) config('bandara_credit.earn_enabled', false)) {
            return $this->guardResult(false, 'earn_disabled', $orderId, $userId);
        }

        if ((bool) config('bandara_credit.shadow_mode', true)) {
            return $this->guardResult(false, 'shadow_mode', $orderId, $userId);
        }

        if ($orderId === null) {
            return $this->guardResult(false, 'missing_order_id', null, $userId);
        }

        if ($userId === null) {
            return $this->guardResult(false, 'missing_user_id', $orderId, null);
        }

        if (! $this->isEligibleUserForBandaraCredit($userId)) {
            return $this->guardResult(false, 'user_not_eligible', $orderId, $userId);
        }

        if ($requireSuccessful && ! in_array(
            $status,
            array_map(fn ($value) => strtolower((string) $value), (array) config('bandara_credit.successful_statuses', ['delivered', 'completed'])),
            true
        )) {
            return $this->guardResult(false, 'order_not_successful', $orderId, $userId);
        }

        return $this->guardResult(true, null, $orderId, $userId);
    }

    protected function cancellationGuard(Model $order, bool $respectAutoPost): array
    {
        $orderId = $order->exists ? (int) $order->getKey() : null;
        $userId = $this->nullableInt($this->orderValue($order, 'user_id'));

        if ($orderId === null) {
            return $this->guardResult(false, 'missing_order_id', null, $userId);
        }

        if ($userId === null) {
            return $this->guardResult(false, 'missing_user_id', $orderId, null);
        }

        // Cancellation is corrective accounting. It must be allowed even if
        // auto-posting, earning, or shadow mode have since been switched off;
        // otherwise already-posted credits could remain spendable after a
        // cancelled order.
        return $this->guardResult(true, null, $orderId, $userId);
    }

    protected function guardResult(bool $allowed, ?string $reason, ?int $orderId, ?int $userId): array
    {
        return [
            'allowed' => $allowed,
            'reason' => $reason,
            'order_id' => $orderId,
            'user_id' => $userId,
        ];
    }

    protected function writeResult(
        string $action,
        ?string $reason,
        ?int $orderId,
        ?int $userId,
        ?array $preview = null,
        ?BandaraCreditWallet $wallet = null,
        array $extra = [],
        bool $legacyKeys = false
    ): array {
        $base = [
            'action' => $action,
            'reason' => $reason,
            'order_id' => $orderId,
            'user_id' => $userId,
            'wallet_balance' => $wallet ? (int) $wallet->balance : null,
            'tier' => $wallet ? (string) $wallet->tier : null,
            'preview' => $preview,
        ];

        if ($legacyKeys) {
            $base += [
                'posted' => $action === 'posted',
                'cancelled' => $action === 'cancelled',
                'transactions_posted' => $extra['transactions_posted'] ?? [],
                'transactions_reversed' => $extra['transactions_reversed'] ?? [],
                'total_posted' => (int) ($extra['total_posted'] ?? 0),
                'total_reversed' => (int) ($extra['total_reversed'] ?? ($extra['reversed_posted'] ?? 0)),
            ];
        }

        return array_merge($base, $extra);
    }

    protected function safePreview(Model $order): array
    {
        try {
            return $this->previewEarnForOrder($order);
        } catch (\Throwable) {
            return [
                'eligible_user' => null,
                'eligible_spend' => 0,
                'base_credit' => 0,
                'repeat_bonus' => 0,
                'welcome_bonus' => 0,
                'total_credit_preview' => 0,
            ];
        }
    }

    protected function earnComponentsForPreview(array $preview): array
    {
        $components = (array) ($preview['components'] ?? []);

        if (! empty($components)) {
            return $components;
        }

        return [
            'base_earned' => [
                'amount' => max(0, (int) ($preview['base_credit'] ?? 0)),
                'tier_points' => max(0, (int) ($preview['base_credit'] ?? 0)),
            ],
            'tier_bonus' => [
                'amount' => max(0, (int) ($preview['tier_bonus'] ?? 0)),
                'tier_points' => max(0, (int) ($preview['tier_bonus'] ?? 0)),
            ],
            'repeat_bonus' => [
                'amount' => max(0, (int) ($preview['repeat_bonus'] ?? 0)),
                'tier_points' => 0,
            ],
            'welcome_bonus' => [
                'amount' => max(0, (int) ($preview['welcome_bonus'] ?? 0)),
                'tier_points' => 0,
            ],
            'promo_bonus' => [
                'amount' => max(0, (int) ($preview['promo_bonus'] ?? 0)),
                'tier_points' => max(0, (int) ($preview['promo_tier_points'] ?? 0)),
            ],
        ];
    }

    protected function transactionPayload(
        int $userId,
        int $orderId,
        string $type,
        int $amount,
        string $status,
        string $key,
        ?string $note = null,
        array $meta = [],
        int $tierPoints = 0,
        ?int $campaignId = null,
        ?int $createdById = null,
        mixed $expiresAt = null
    ): array {
        return [
            'user_id' => $userId,
            'order_id' => $orderId,
            'campaign_id' => $campaignId,
            'amount' => $amount,
            'tier_points' => $tierPoints,
            'type' => $type,
            'status' => $status,
            'idempotency_key' => $key,
            'meta' => empty($meta) ? null : $meta,
            'note' => $note,
            'expires_at' => $expiresAt,
            'created_by_id' => $createdById,
        ];
    }

    protected function previewMeta(array $preview): array
    {
        return [
            'eligible_spend' => (int) ($preview['eligible_spend'] ?? 0),
            'base_credit' => (int) ($preview['base_credit'] ?? 0),
            'repeat_bonus' => (int) ($preview['repeat_bonus'] ?? 0),
            'welcome_bonus' => (int) ($preview['welcome_bonus'] ?? 0),
            'total_credit_preview' => (int) ($preview['total_credit_preview'] ?? 0),
        ];
    }

    protected function transactionKey(int $orderId, string $type): string
    {
        return "order:{$orderId}:{$type}";
    }

    protected function reversalKey(int $orderId, string $type): string
    {
        return "order:{$orderId}:{$type}:reversal";
    }

    protected function legacyOrderRewardKey(int $orderId): string
    {
        return 'order-reward:'.$orderId;
    }

    protected function lockTransactionByKey(string $key): ?BandaraCreditTransaction
    {
        return BandaraCreditTransaction::query()
            ->where('idempotency_key', $key)
            ->lockForUpdate()
            ->first();
    }

    protected function orderEarnTypes(): array
    {
        return ['base_earned', 'tier_bonus', 'repeat_bonus', 'welcome_bonus', 'birthday_bonus', 'promo_bonus'];
    }

    protected function legacyEarnTypes(): array
    {
        return ['order_reward', 'order_credit', 'earn', 'credit'];
    }

    protected function allPositiveEarnTypes(): array
    {
        return array_values(array_unique(array_merge($this->orderEarnTypes(), $this->legacyEarnTypes())));
    }

    protected function hasPostedLegacyEarnForOrder(int $orderId, int $userId): bool
    {
        return BandaraCreditTransaction::query()
            ->where('user_id', $userId)
            ->where('order_id', $orderId)
            ->whereIn('type', $this->legacyEarnTypes())
            ->where('status', 'posted')
            ->exists();
    }

    protected function cancelLegacyPendingOrderReward(int $orderId, int $userId): int
    {
        return BandaraCreditTransaction::query()
            ->where('user_id', $userId)
            ->where('idempotency_key', $this->legacyOrderRewardKey($orderId))
            ->whereIn('status', ['pending', 'reserved'])
            ->update([
                'status' => 'cancelled',
                'updated_at' => now(),
            ]);
    }

    protected function cancelPendingEarnTransactions(int $orderId, int $userId): int
    {
        return BandaraCreditTransaction::query()
            ->where('user_id', $userId)
            ->where('order_id', $orderId)
            ->whereIn('type', $this->orderEarnTypes())
            ->whereIn('status', ['pending', 'reserved'])
            ->update([
                'status' => 'cancelled',
                'updated_at' => now(),
            ]);
    }

    protected function postedEarnTotalForOrder(int $orderId, int $userId): int
    {
        return (int) BandaraCreditTransaction::query()
            ->where('user_id', $userId)
            ->where('order_id', $orderId)
            ->whereIn('type', $this->allPositiveEarnTypes())
            ->where('status', 'posted')
            ->where('amount', '>', 0)
            ->sum('amount');
    }

    protected function releaseEarnReversalsForOrder(int $orderId, int $userId): int
    {
        $reversals = BandaraCreditTransaction::query()
            ->where('user_id', $userId)
            ->where('order_id', $orderId)
            ->where('type', 'earn_reversal')
            ->where('status', 'posted')
            ->where('amount', '<', 0)
            ->lockForUpdate()
            ->get();

        $released = (int) $reversals->sum(fn (BandaraCreditTransaction $tx) => abs((int) $tx->amount));

        foreach ($reversals as $tx) {
            $tx->forceFill([
                'status' => 'reversed',
            ])->save();
        }

        return $released;
    }

    protected function redemptionReserveKey(int $orderId): string
    {
        return "order:{$orderId}:redeem_reserved";
    }

    protected function redemptionReversalKey(int $orderId, int $transactionId): string
    {
        return "order:{$orderId}:redeem_reversal:{$transactionId}";
    }

    protected function reservedRedemptionTypes(): array
    {
        return ['redeem_reserved'];
    }

    protected function postedRedemptionTypes(): array
    {
        return ['redeemed', 'redeem', 'redemption', 'debit', 'use', 'admin_debit'];
    }

    protected function redemptionReversalTypes(): array
    {
        return ['redeem_reversal', 'redeem_release', 'refund_credit'];
    }

    protected function reservedRedemptionPointsForUser(int $userId, ?int $excludeOrderId = null, bool $lock = false): int
    {
        $query = BandaraCreditTransaction::query()
            ->where('user_id', $userId)
            ->whereIn('type', $this->reservedRedemptionTypes())
            ->where('status', 'reserved')
            ->where('amount', '<', 0);

        if ($excludeOrderId !== null) {
            $query->where(function (Builder $query) use ($excludeOrderId) {
                $query->whereNull('order_id')
                    ->orWhere('order_id', '!=', $excludeOrderId);
            });
        }

        if ($lock) {
            $transactions = $query->lockForUpdate()->get(['amount']);

            return abs((int) $transactions->sum('amount'));
        }

        return abs((int) $query->sum('amount'));
    }

    protected function releaseReservedRedemptionTransactions(int $orderId, int $userId, string $reason): int
    {
        $transactions = BandaraCreditTransaction::query()
            ->where('user_id', $userId)
            ->where('order_id', $orderId)
            ->whereIn('type', $this->reservedRedemptionTypes())
            ->where('status', 'reserved')
            ->where('amount', '<', 0)
            ->lockForUpdate()
            ->get();

        $released = 0;

        foreach ($transactions as $tx) {
            $released += abs((int) $tx->amount);
            $meta = $tx->meta ?? [];
            $meta['released_at'] = now()->toDateTimeString();
            $meta['release_reason'] = $reason;

            $tx->forceFill([
                'status' => 'cancelled',
                'note' => trim(($tx->note ? $tx->note.' | ' : '').'Reservation released: '.$reason),
                'meta' => $meta,
            ])->save();
        }

        return $released;
    }

    protected function reversePostedRedemptionTransactions(Model $order, int $orderId, int $userId, string $reason): int
    {
        $postedDebits = BandaraCreditTransaction::query()
            ->where('user_id', $userId)
            ->where('order_id', $orderId)
            ->whereIn('type', $this->postedRedemptionTypes())
            ->where('status', 'posted')
            ->where('amount', '<', 0)
            ->lockForUpdate()
            ->get();

        $restored = 0;

        foreach ($postedDebits as $postedTx) {
            $points = abs((int) $postedTx->amount);
            if ($points <= 0) {
                continue;
            }

            $key = $this->redemptionReversalKey($orderId, (int) $postedTx->id);
            $existing = $this->lockTransactionByKey($key);

            if ($existing && $existing->status === 'posted') {
                continue;
            }

            $payload = $this->transactionPayload(
                userId: $userId,
                orderId: $orderId,
                type: 'redeem_reversal',
                amount: $points,
                status: 'posted',
                key: $key,
                note: 'Bandara Credit redemption reversed for order #'.$this->orderLabel($order),
                meta: [
                    'source' => $reason,
                    'reverses_transaction_id' => $postedTx->id,
                    'reverses_type' => (string) $postedTx->type,
                ]
            );

            if ($existing) {
                $existing->forceFill($payload)->save();
            } else {
                BandaraCreditTransaction::query()->create($payload);
            }

            $restored += $points;
        }

        return $restored;
    }

    public function releaseExpiredRedemptionReservations(?int $olderThanMinutes = null): int
    {
        $olderThanMinutes = $olderThanMinutes ?? max(1, (int) config('bandara_credit.redemption.reservation_ttl_minutes', 120));
        $cutoff = now()->subMinutes($olderThanMinutes);

        $reservations = BandaraCreditTransaction::query()
            ->whereIn('type', $this->reservedRedemptionTypes())
            ->where('status', 'reserved')
            ->where('amount', '<', 0)
            ->where('created_at', '<=', $cutoff)
            ->get();

        $released = 0;

        foreach ($reservations as $reservation) {
            if (! $this->canReleaseExpiredRedemptionReservation($reservation)) {
                continue;
            }

            $released += $this->releaseReservedRedemptionTransactions(
                (int) $reservation->order_id,
                (int) $reservation->user_id,
                'reservation_expired'
            );
        }

        return $released;
    }

    protected function canReleaseExpiredRedemptionReservation(BandaraCreditTransaction $reservation): bool
    {
        $orderId = $this->nullableInt($reservation->order_id);

        if ($orderId === null) {
            return true;
        }

        /** @var class-string<Model>|null $orderModel */
        $orderModel = config('bandara_credit.order_model');
        if (! is_string($orderModel) || ! class_exists($orderModel)) {
            return false;
        }

        $order = $orderModel::query()->find($orderId);
        if (! $order) {
            return true;
        }

        $orderStatus = strtolower((string) $this->orderValue($order, 'status', ''));
        $paymentStatus = strtolower((string) data_get($order, 'payment_status', ''));
        $cancelledStatuses = array_map(
            fn ($value) => strtolower((string) $value),
            (array) config('bandara_credit.cancelled_statuses', ['cancelled'])
        );

        return in_array($orderStatus, $cancelledStatuses, true)
            || in_array($paymentStatus, ['failed', 'refunded', 'cancelled'], true);
    }

    protected function walletSnapshot(int $userId): ?BandaraCreditWallet
    {
        return BandaraCreditWallet::query()->where('user_id', $userId)->first();
    }

    protected function lockOrCreateWallet(int $userId): BandaraCreditWallet
    {
        $now = now();

        BandaraCreditWallet::query()->insertOrIgnore([
            'user_id' => $userId,
            'balance' => 0,
            'tier' => 'silver',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return BandaraCreditWallet::query()
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    protected function titleForTransaction(?string $type, int $points): string
    {
        $type = strtolower(trim((string) $type));

        return match ($type) {
            'base_earned' => 'Order credit earned',
            'tier_bonus' => 'Tier bonus earned',
            'promo_bonus' => 'Promotional bonus earned',
            'repeat_bonus' => 'Repeat order bonus',
            'welcome_bonus' => 'Welcome bonus',
            'birthday_bonus' => 'Birthday bonus',
            'earn_reversal' => 'Credit reversed',
            'redeem_reserved' => 'Credit reserved',
            'redeemed' => 'Reward redeemed',
            'redeem_reversal' => 'Credit restored',
            'redeem_release' => 'Credit restored',
            'order_reward', 'order_credit', 'earn', 'credit' => 'Order reward credited',
            'redeem', 'redemption', 'debit', 'use' => 'Reward redeemed',
            'refund_credit' => 'Refund credit added',
            'expiry', 'expired' => 'Credit expired',
            'adjustment', 'manual_adjustment', 'manual_credit', 'admin_credit' => $points >= 0 ? 'Manual credit added' : 'Manual adjustment',
            'admin_debit' => 'Manual debit',
            default => $points >= 0
                ? Str::headline($type ?: 'Credit added')
                : Str::headline($type ?: 'Credit used'),
        };
    }


    protected function notifyRewardUser(int $userId, string $title, string $message, array $payload = []): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $user = User::query()->find($userId);
        if (! $user) {
            return;
        }

        try {
            $user->notify(new BandaraCreditAccountNotification($title, $message, $payload));
        } catch (\Throwable) {
            // Notifications are customer communication, not accounting. Never
            // fail ledger writes because notification delivery/storage failed.
        }
    }

    public function isEligibleUserForBandaraCredit(User|int|null $user): bool
    {
        if ($user === null) {
            return false;
        }

        $userModel = $user instanceof User ? $user : User::query()->find($user);

        if (! $userModel) {
            return false;
        }

        $mode = (string) config('bandara_credit.eligibility.mode', 'b2c');
        $isB2c = $this->isB2cUser($userModel);

        // Bandara Credit is a B2C-only programme. B2B users can still carry
        // the Customer role in this project, so every mode is capped by the
        // customer_type check to avoid accidental B2B reward eligibility.
        if (! $isB2c) {
            return false;
        }

        if ($mode === 'all') {
            return true;
        }

        if ($mode === 'b2c') {
            return $isB2c;
        }

        if ($mode === 'column') {
            return $isB2c;
        }

        $allowedRoles = (array) config('bandara_credit.eligibility.allowed_roles', ['Customer']);

        try {
            $hasAllowedRole = method_exists($userModel, 'hasAnyRole')
                ? $userModel->hasAnyRole($allowedRoles)
                : false;
        } catch (\Throwable) {
            $hasAllowedRole = false;
        }

        // B2B customers also use the Customer role in this project, so role
        // checks must still be filtered through customer_type when available.
        return $hasAllowedRole && $isB2c;
    }

    protected function qualifiesForRepeatBonus(
        int $userId,
        CarbonInterface $placedAt,
        ?int $excludeOrderId = null
    ): bool {
        $previousOrder = $this->successfulOrdersForUser($userId, $excludeOrderId)
            ->where($this->orderColumn('placed_at'), '<', $placedAt)
            ->orderByDesc($this->orderColumn('placed_at'))
            ->first();

        if (! $previousOrder) {
            return false;
        }

        $previousPlacedAt = $this->normalizeDate(
            data_get($previousOrder, $this->orderColumn('placed_at'))
        );

        return $previousPlacedAt->diffInDays($placedAt)
            <= (int) config('bandara_credit.earning.repeat_window_days', 10);
    }

    protected function qualifiesForWelcomeBonus(
        int $userId,
        int $eligibleSpend,
        int $minimumOrderValue,
        CarbonInterface $placedAt,
        ?int $excludeOrderId = null
    ): bool {
        if ($eligibleSpend < $minimumOrderValue) {
            return false;
        }

        if ($this->hasActiveWelcomeBonusForUser($userId, $excludeOrderId)) {
            return false;
        }

        return ! $this->successfulOrdersForUser($userId, $excludeOrderId)
            ->where($this->orderColumn('placed_at'), '<', $placedAt)
            ->exists();
    }

    protected function hasActiveWelcomeBonusForUser(int $userId, ?int $excludeOrderId = null): bool
    {
        try {
            $orderModel = config('bandara_credit.order_model');
            $orderInstance = new $orderModel();
            $ordersTable = $orderInstance->getTable();
            $orderKey = $orderInstance->getKeyName();
            $statusColumn = $this->orderColumn('status');
            $cancelledStatuses = (array) config('bandara_credit.cancelled_statuses', ['cancelled']);

            $query = BandaraCreditTransaction::query()
                ->where('bandara_credit_transactions.user_id', $userId)
                ->where('bandara_credit_transactions.type', 'welcome_bonus')
                ->whereIn('bandara_credit_transactions.status', ['pending', 'posted', 'reserved'])
                ->leftJoin($ordersTable, $ordersTable.'.'.$orderKey, '=', 'bandara_credit_transactions.order_id')
                ->where(function (Builder $query) use ($ordersTable, $statusColumn, $cancelledStatuses) {
                    $query->whereNull('bandara_credit_transactions.order_id')
                        ->orWhereNull($ordersTable.'.'.$statusColumn)
                        ->orWhereNotIn($ordersTable.'.'.$statusColumn, $cancelledStatuses);
                });

            if ($excludeOrderId !== null) {
                $query->where(function (Builder $query) use ($excludeOrderId) {
                    $query->whereNull('bandara_credit_transactions.order_id')
                        ->orWhere('bandara_credit_transactions.order_id', '!=', $excludeOrderId);
                });
            }

            return $query->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    protected function successfulOrdersForUser(int $userId, ?int $excludeOrderId = null): Builder
    {
        $orderModel = config('bandara_credit.order_model');
        $orderInstance = new $orderModel();

        /** @var Builder $query */
        $query = $orderModel::query();

        $query
            ->where($this->orderColumn('user_id'), $userId)
            ->whereIn(
                $this->orderColumn('status'),
                (array) config('bandara_credit.successful_statuses', ['delivered', 'completed'])
            );

        if ($excludeOrderId !== null) {
            $query->where($orderInstance->getKeyName(), '!=', $excludeOrderId);
        }

        return $query;
    }

    protected function pendingOrdersForUser(int $userId): Builder
    {
        $orderModel = config('bandara_credit.order_model');

        /** @var Builder $query */
        $query = $orderModel::query();

        $statusColumn = $this->orderColumn('status');
        $userColumn = $this->orderColumn('user_id');

        $successfulStatuses = (array) config('bandara_credit.successful_statuses', ['delivered', 'completed']);
        $excludedStatuses = array_values(array_unique(array_merge($successfulStatuses, ['cancelled'])));

        $query->where($userColumn, $userId);

        $query->where(function ($q) use ($statusColumn, $excludedStatuses) {
            $q->whereNull($statusColumn)
              ->orWhereNotIn($statusColumn, $excludedStatuses);
        });

        return $query;
    }

    protected function orderColumn(string $key): string
    {
        return (string) config("bandara_credit.order_mapping.{$key}");
    }

    protected function orderValue(Model|array $order, string $key, mixed $default = null): mixed
    {
        $column = $this->orderColumn($key);

        if (is_array($order)) {
            return $order[$column] ?? $order[$key] ?? $default;
        }

        return data_get($order, $column, data_get($order, $key, $default));
    }

    protected function extractOrderId(Model|array $order): ?int
    {
        if (is_array($order)) {
            return isset($order['id']) ? (int) $order['id'] : null;
        }

        return $order->exists ? (int) $order->getKey() : null;
    }

    protected function resolveUserId(User|int $user): int
    {
        return $user instanceof User ? (int) $user->getKey() : (int) $user;
    }

    protected function normalizeMoney(mixed $value): int
    {
        return max(0, (int) floor((float) $value));
    }

    protected function normalizeDate(mixed $value): Carbon
    {
        if ($value instanceof CarbonInterface) {
            return Carbon::instance($value);
        }

        return Carbon::parse($value);
    }

    protected function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    public function previewTierForPoints(int|float $tierPoints): array
    {
        $tierPoints = max(0, (int) floor((float) $tierPoints));
        $birthdayEnabled = (bool) config('bandara_credit.birthday_bonus_enabled', true);
        $tiersEnabled = (bool) config('bandara_credit.tiers_enabled', true);
        $tiers = $this->tierDefinitions();

        if (! $tiersEnabled || $tiers->isEmpty()) {
            $silver = $tiers->firstWhere('key', 'silver') ?? ['key' => 'silver', 'name' => 'Silver', 'threshold_min' => 0, 'threshold_max' => null, 'birthday_credit' => 0, 'reward_rate_percent' => 1];

            return [
                'tier' => 'silver',
                'tier_name' => (string) ($silver['name'] ?? 'Silver'),
                'tier_points' => $tierPoints,
                'rolling_spend' => $tierPoints,
                'birthday_credit' => (int) ($silver['birthday_credit'] ?? 0),
                'current_tier_min_threshold' => 0,
                'current_tier_max_threshold' => null,
                'next_tier' => null,
                'next_tier_name' => null,
                'next_tier_threshold' => null,
                'amount_to_next_tier' => 0,
                'progress_percentage' => 100.0,
                'reward_rate_percent' => (float) ($silver['reward_rate_percent'] ?? 1),
            ];
        }

        $current = $this->tierDefinitionForPoints($tierPoints) ?? $tiers->first();
        $next = $tiers
            ->filter(fn (array $tier) => (int) $tier['threshold_min'] > (int) $current['threshold_min'])
            ->sortBy('threshold_min')
            ->first();

        $birthdayCredit = $birthdayEnabled ? (int) ($current['birthday_credit'] ?? 0) : 0;
        $nextThreshold = $next ? (int) $next['threshold_min'] : null;

        return [
            'tier' => (string) $current['key'],
            'tier_name' => (string) ($current['name'] ?? Str::headline((string) $current['key'])),
            'tier_points' => $tierPoints,
            'rolling_spend' => $tierPoints,
            'birthday_credit' => $birthdayCredit,
            'current_tier_min_threshold' => (int) $current['threshold_min'],
            'current_tier_max_threshold' => $current['threshold_max'] === null ? null : (int) $current['threshold_max'],
            'next_tier' => $next['key'] ?? null,
            'next_tier_name' => $next['name'] ?? null,
            'next_tier_threshold' => $nextThreshold,
            'amount_to_next_tier' => $nextThreshold === null ? 0 : max(0, $nextThreshold - $tierPoints),
            'progress_percentage' => $this->progressPercentage(
                current: $tierPoints,
                currentTierMin: (int) $current['threshold_min'],
                nextTierThreshold: $nextThreshold
            ),
            'reward_rate_percent' => (float) ($current['reward_rate_percent'] ?? $this->rewardRateForTier((string) $current['key'])),
        ];
    }

    public function annualTierPointsForUser(int $userId, ?int $year = null): int
    {
        if (! Schema::hasTable('bandara_credit_transactions')) {
            return 0;
        }

        $year ??= (int) now()->year;
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end = Carbon::create($year, 12, 31)->endOfDay();
        $hasTierPointsColumn = Schema::hasColumn('bandara_credit_transactions', 'tier_points');

        $baseQuery = fn () => DB::table('bandara_credit_transactions')
            ->where('user_id', $userId)
            ->where('status', 'posted')
            ->whereBetween('created_at', [$start, $end]);

        $explicitTierPoints = $hasTierPointsColumn
            ? (float) $baseQuery()
                ->whereNotNull('tier_points')
                ->where('tier_points', '!=', 0)
                ->sum('tier_points')
            : 0.0;

        // Legacy rows created before the tier_points column existed have a zero
        // tier_points value. Only normal earn/tier rows count as fallback.
        // Promo, welcome, repeat, birthday, redemption, and manual rows are
        // excluded unless they explicitly carry a non-zero tier_points value.
        $legacyTierQuery = $baseQuery()
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->whereIn('type', ['base_earned', 'tier_bonus', 'order_reward', 'order_credit', 'earn', 'earned', 'credit'])
                        ->where('amount', '>', 0);
                })->orWhere(function ($query) {
                    $query->whereIn('type', ['earn_reversal', 'reversal', 'partial_refund', 'partial_cancellation'])
                        ->where('amount', '<', 0);
                });
            });

        if ($hasTierPointsColumn) {
            $legacyTierQuery->where(function ($query) {
                $query->whereNull('tier_points')->orWhere('tier_points', 0);
            });
        }

        $legacyTierPoints = (float) $legacyTierQuery->sum('amount');

        return max(0, (int) floor($explicitTierPoints + $legacyTierPoints));
    }

    public function tierDefinitions()
    {
        $defaults = collect([
            [
                'key' => 'silver',
                'name' => 'Silver',
                'threshold_min' => (int) config('bandara_credit.tiers.silver.threshold', 0),
                'threshold_max' => (int) config('bandara_credit.tiers.silver.max_threshold', 999),
                'reward_rate_percent' => (float) config('bandara_credit.tiers.silver.rate_percent', 1),
                'birthday_credit' => (int) config('bandara_credit.tiers.silver.birthday_credit', 100),
            ],
            [
                'key' => 'gold',
                'name' => 'Gold',
                'threshold_min' => (int) config('bandara_credit.tiers.gold.threshold', 1000),
                'threshold_max' => (int) config('bandara_credit.tiers.gold.max_threshold', 3499),
                'reward_rate_percent' => (float) config('bandara_credit.tiers.gold.rate_percent', 2),
                'birthday_credit' => (int) config('bandara_credit.tiers.gold.birthday_credit', 150),
            ],
            [
                'key' => 'platinum',
                'name' => 'Platinum',
                'threshold_min' => (int) config('bandara_credit.tiers.platinum.threshold', 3500),
                'threshold_max' => null,
                'reward_rate_percent' => (float) config('bandara_credit.tiers.platinum.rate_percent', 4),
                'birthday_credit' => (int) config('bandara_credit.tiers.platinum.birthday_credit', 200),
            ],
        ])->values();

        if (! Schema::hasTable('bandara_credit_tiers')) {
            return $defaults;
        }

        $rows = BandaraCreditTier::query()
            ->where('is_active', true)
            ->orderBy('threshold_min')
            ->orderBy('sort_order')
            ->get(['key', 'name', 'threshold_min', 'threshold_max', 'reward_rate_percent']);

        if ($rows->isEmpty()) {
            return $defaults;
        }

        $definitions = $rows
            ->map(fn (BandaraCreditTier $tier) => [
                'key' => strtolower((string) $tier->key),
                'name' => (string) $tier->name,
                'threshold_min' => max(0, (int) $tier->threshold_min),
                'threshold_max' => $tier->threshold_max === null ? null : max(0, (int) $tier->threshold_max),
                'reward_rate_percent' => max(0, (float) $tier->reward_rate_percent),
                'birthday_credit' => (int) config('bandara_credit.tiers.'.strtolower((string) $tier->key).'.birthday_credit', 0),
            ])
            ->filter(fn (array $tier) => in_array($tier['key'], ['silver', 'gold', 'platinum'], true))
            ->unique('key')
            ->sortBy('threshold_min')
            ->values();

        // If old/partial data leaves tiers overlapping at threshold 0, a 576-point
        // customer can be incorrectly treated as Platinum. Fall back to the canonical
        // config defaults unless the DB definitions form a coherent ascending ladder.
        if ($definitions->count() !== 3 || ! $this->tierDefinitionsAreCoherent($definitions)) {
            return $defaults;
        }

        return $definitions;
    }

    protected function tierDefinitionForPoints(int $tierPoints): ?array
    {
        return $this->tierDefinitions()
            ->filter(function (array $tier) use ($tierPoints) {
                $min = (int) $tier['threshold_min'];
                $max = $tier['threshold_max'] === null ? null : (int) $tier['threshold_max'];

                return $tierPoints >= $min && ($max === null || $tierPoints <= $max);
            })
            ->sortByDesc('threshold_min')
            ->first();
    }

    protected function tierDefinitionsAreCoherent($definitions): bool
    {
        $definitions = collect($definitions)->values();
        $required = ['silver', 'gold', 'platinum'];

        if ($definitions->pluck('key')->sort()->values()->all() !== collect($required)->sort()->values()->all()) {
            return false;
        }

        $ordered = $definitions->sortBy('threshold_min')->values();
        if ($ordered->pluck('key')->values()->all() !== $required) {
            return false;
        }

        $previousMin = null;
        foreach ($ordered as $tier) {
            $min = (int) $tier['threshold_min'];
            $max = $tier['threshold_max'] === null ? null : (int) $tier['threshold_max'];

            if ($previousMin !== null && $min <= $previousMin) {
                return false;
            }

            if ($max !== null && $max < $min) {
                return false;
            }

            $previousMin = $min;
        }

        return true;
    }

    protected function rewardRateForTier(string $tier): float
    {
        $definition = $this->tierDefinitions()->firstWhere('key', strtolower($tier));

        return (float) ($definition['reward_rate_percent'] ?? config('bandara_credit.tiers.silver.rate_percent', 1));
    }

    protected function componentAmount(mixed $component): int
    {
        return max(0, (int) (is_array($component) ? ($component['amount'] ?? 0) : $component));
    }

    protected function componentTierPoints(mixed $component): int
    {
        return (int) (is_array($component) ? ($component['tier_points'] ?? 0) : 0);
    }

    protected function componentCampaignId(mixed $component): ?int
    {
        $campaignId = is_array($component) ? ($component['campaign_id'] ?? null) : null;

        return $campaignId ? (int) $campaignId : null;
    }

    protected function componentMeta(mixed $component): array
    {
        return is_array($component) ? (array) ($component['meta'] ?? []) : [];
    }

    protected function componentAmountTotal(array $components): int
    {
        return (int) collect($components)->sum(fn ($component) => $this->componentAmount($component));
    }

    protected function previewBestCampaignBonus(Model|array $order, ?int $userId, string $tier, int $normalRewardTotal, int $eligibleSpend): array
    {
        if (! (bool) config('bandara_credit.campaigns.enabled', true) || $userId === null || $normalRewardTotal <= 0) {
            return ['amount' => 0, 'tier_points' => 0];
        }

        if (! Schema::hasTable('bandara_credit_campaigns')) {
            return ['amount' => 0, 'tier_points' => 0];
        }

        $now = now();
        $campaigns = BandaraCreditCampaign::query()
            ->where('status', 'active')
            ->where(function ($query) use ($now) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->orderByDesc('multiplier')
            ->orderByDesc('fixed_bonus_points')
            ->get();

        $best = ['amount' => 0, 'tier_points' => 0];

        foreach ($campaigns as $campaign) {
            $eligibleTiers = array_filter((array) ($campaign->eligible_tiers ?? []));
            if ($eligibleTiers && ! in_array($tier, $eligibleTiers, true)) {
                continue;
            }

            if ($campaign->min_order_amount !== null && $eligibleSpend < (float) $campaign->min_order_amount) {
                continue;
            }

            $scopeSpend = $this->campaignEligibleSpend($campaign, $order, $eligibleSpend);
            if ($scopeSpend <= 0) {
                continue;
            }

            $baseForScope = $eligibleSpend > 0
                ? (int) floor($normalRewardTotal * ($scopeSpend / $eligibleSpend))
                : $normalRewardTotal;

            $bonus = (int) ($campaign->fixed_bonus_points ?? 0);
            $multiplier = max(1, (float) $campaign->multiplier);
            if ($multiplier > 1) {
                $bonus += (int) floor($baseForScope * ($multiplier - 1));
            }

            if ($campaign->max_bonus_per_order !== null) {
                $bonus = min($bonus, (int) $campaign->max_bonus_per_order);
            }

            if ($campaign->max_bonus_per_customer !== null) {
                $alreadyEarned = (int) BandaraCreditTransaction::query()
                    ->where('user_id', $userId)
                    ->where('campaign_id', $campaign->id)
                    ->whereIn('status', ['pending', 'reserved', 'posted'])
                    ->sum('amount');
                $bonus = min($bonus, max(0, (int) $campaign->max_bonus_per_customer - $alreadyEarned));
            }

            if ($campaign->budget_points !== null) {
                $budgetAlreadyReservedOrIssued = max(
                    (int) $campaign->used_budget_points,
                    (int) BandaraCreditTransaction::query()
                        ->where('campaign_id', $campaign->id)
                        ->where('amount', '>', 0)
                        ->whereIn('status', ['pending', 'reserved', 'posted'])
                        ->sum('amount')
                );

                $bonus = min($bonus, max(0, (int) $campaign->budget_points - $budgetAlreadyReservedOrIssued));
            }

            if ($bonus <= 0 || $bonus <= (int) ($best['amount'] ?? 0)) {
                continue;
            }

            $countsTowardTier = (bool) $campaign->counts_toward_tier;
            $best = [
                'amount' => $bonus,
                'tier_points' => $countsTowardTier ? $bonus : 0,
                'campaign_id' => (int) $campaign->id,
                'campaign' => $campaign,
                'meta' => [
                    'campaign_id' => (int) $campaign->id,
                    'campaign_name' => (string) $campaign->name,
                    'campaign_type' => (string) $campaign->type,
                    'campaign_multiplier' => (float) $campaign->multiplier,
                    'counts_toward_tier' => $countsTowardTier,
                    'source' => 'reward_campaign',
                ],
            ];
        }

        return $best;
    }

    protected function campaignEligibleSpend(BandaraCreditCampaign $campaign, Model|array $order, int $fallbackSpend): int
    {
        if (! in_array((string) $campaign->type, ['product', 'category'], true)) {
            return $fallbackSpend;
        }

        $items = $this->orderItemsForCampaign($order);
        if ($items->isEmpty()) {
            return 0;
        }

        if ((string) $campaign->type === 'product') {
            $productIds = $campaign->products()->pluck('products.id')->map(fn ($id) => (int) $id)->all();
            if (empty($productIds)) {
                return 0;
            }

            return (int) floor($items->whereIn('product_id', $productIds)->sum('subtotal'));
        }

        $categoryIds = $campaign->categories()->pluck('categories.id')->map(fn ($id) => (int) $id)->all();
        if (empty($categoryIds)) {
            return 0;
        }

        $productIds = DB::table('category_product')
            ->whereIn('category_id', $categoryIds)
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return (int) floor($items->whereIn('product_id', $productIds)->sum('subtotal'));
    }

    protected function orderItemsForCampaign(Model|array $order)
    {
        if (is_array($order)) {
            return collect($order['items'] ?? []);
        }

        if ($order->relationLoaded('items')) {
            return collect($order->getRelation('items'));
        }

        if ($order->exists && Schema::hasTable('order_items')) {
            return DB::table('order_items')
                ->where('order_id', $order->getKey())
                ->get();
        }

        return collect();
    }

    protected function isB2cUser(User $user): bool
    {
        $column = (string) config('bandara_credit.eligibility.column', 'customer_type');
        $b2cValue = (string) config('bandara_credit.eligibility.b2c_value', 'b2c');

        if (Schema::hasColumn($user->getTable(), $column)) {
            return strtolower((string) $user->getAttribute($column)) === strtolower($b2cValue);
        }

        try {
            return method_exists($user, 'hasRole') ? $user->hasRole('Customer') : false;
        } catch (\Throwable) {
            return false;
        }
    }


    protected function retainedTierForUser(int $userId): ?object
    {
        if (! Schema::hasTable('bandara_credit_tier_histories')) {
            return null;
        }

        $definitions = $this->tierDefinitions()->keyBy('key');

        return DB::table('bandara_credit_tier_histories')
            ->where('user_id', $userId)
            ->whereDate('valid_until', '>=', now()->toDateString())
            ->orderByDesc('valid_until')
            ->get()
            ->filter(function ($row) use ($definitions) {
                $tier = strtolower((string) ($row->tier ?? ''));

                if ($this->tierRank($tier) <= $this->tierRank('silver')) {
                    return false;
                }

                $definition = $definitions->get($tier);

                if (! $definition) {
                    return false;
                }

                $threshold = (int) ($definition['threshold_min'] ?? 0);
                $qualifiedPoints = (int) ($row->tier_points_at_qualification ?? 0);

                // Important safety guard: old/handoff bugs could create a
                // retained Gold/Platinum history row even when the customer
                // never reached that tier. Do not let an invalid retained row
                // override the current annual points calculation.
                return $qualifiedPoints >= $threshold;
            })
            ->sortByDesc(fn ($row) => $this->tierRank((string) $row->tier))
            ->first();
    }

    protected function recordTierAchievement(int $userId, string $tier, int $tierPoints): void
    {
        if (! Schema::hasTable('bandara_credit_tier_histories')) {
            return;
        }

        if ($this->tierRank($tier) <= $this->tierRank('silver')) {
            return;
        }

        $threshold = (int) ($this->tierDefinitions()->firstWhere('key', $tier)['threshold_min'] ?? 0);
        if ($tierPoints < $threshold) {
            return;
        }

        $year = (int) now()->year;
        DB::table('bandara_credit_tier_histories')->updateOrInsert(
            [
                'user_id' => $userId,
                'tier' => $tier,
                'qualified_year' => $year,
            ],
            [
                'tier_points_at_qualification' => $tierPoints,
                'valid_from' => now()->toDateString(),
                'valid_until' => Carbon::create($year + 1, 12, 31)->toDateString(),
                'achieved_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    protected function tierRank(string $tier): int
    {
        $keys = $this->tierDefinitions()->pluck('key')->values()->all();
        $rank = array_flip($keys);

        return (int) ($rank[strtolower($tier)] ?? 0);
    }

    protected function progressPercentage(
        int $current,
        int $currentTierMin,
        ?int $nextTierThreshold
    ): float {
        if ($nextTierThreshold === null || $nextTierThreshold <= $currentTierMin) {
            return 100.0;
        }

        $progress = (($current - $currentTierMin) / ($nextTierThreshold - $currentTierMin)) * 100;

        return round(max(0, min(100, $progress)), 2);
    }

    protected function orderLabel(Model $order): string
    {
        return (string) ($order->getAttribute('order_number') ?: $order->getKey());
    }

    public function checkoutRedemptionViewState(\App\Models\User|int $user, int|float $orderAmount, ?int $requestedPoints = null, array $context = []): array
    {
        if (method_exists($this, 'redemptionQuoteForCheckout')) {
            $quote = $this->redemptionQuoteForCheckout($user, (float) $orderAmount, max(0, (int) ($requestedPoints ?? 0)), $context);
        } elseif (method_exists($this, 'previewRedemptionForAmount')) {
            $quote = $this->previewRedemptionForAmount($user, (float) $orderAmount, $requestedPoints);
        } else {
            $quote = [];
        }

        $status = method_exists($this, 'redemptionStatusForUser')
            ? $this->redemptionStatusForUser($user)
            : [
                'enabled' => method_exists($this, 'redemptionEnabledForUser') ? $this->redemptionEnabledForUser($user) : false,
                'program_enabled' => method_exists($this, 'redemptionProgramEnabled') ? $this->redemptionProgramEnabled() : (bool) config('bandara_credit.redeem_enabled', false),
                'shadow_mode' => (bool) config('bandara_credit.shadow_mode', true),
                'redeem_enabled' => (bool) config('bandara_credit.redeem_enabled', false),
                'eligible_user' => method_exists($this, 'isEligibleUserForBandaraCredit') ? $this->isEligibleUserForBandaraCredit($user) : false,
                'reason' => null,
                'message' => null,
            ];

        $programEnabled = (bool) ($status['program_enabled'] ?? $quote['program_enabled'] ?? $quote['enabled'] ?? false);
        $eligibleUser = (bool) ($status['eligible_user'] ?? $quote['eligible_user'] ?? false);
        $redemptionEnabled = (bool) ($status['enabled'] ?? $quote['redemption_enabled'] ?? ($programEnabled && $eligibleUser));

        $availablePoints = (int) ($quote['available_points'] ?? 0);
        if ($availablePoints <= 0 && method_exists($this, 'availableRedeemablePoints')) {
            $availablePoints = (int) $this->availableRedeemablePoints($user);
        } elseif ($availablePoints <= 0 && method_exists($this, 'currentBalance')) {
            $availablePoints = (int) $this->currentBalance($user);
        }

        $maxRedeemablePoints = (int) ($quote['max_redeemable_points'] ?? $quote['max_points_by_order'] ?? 0);
        if ($availablePoints > 0 && $maxRedeemablePoints <= 0) {
            $pointValue = max(0.01, (float) config('bandara_credit.redemption.point_value', 1));
            $maxOrderPercentage = (float) config('bandara_credit.redemption.max_order_percentage', config('bandara_credit.redemption.max_order_percent', 20));
            $minimumPayable = (float) config('bandara_credit.redemption.minimum_payable_amount', 1);
            $orderAmount = max(0, (float) $orderAmount);
            $maxAmount = max(0, min($orderAmount * ($maxOrderPercentage / 100), $orderAmount - $minimumPayable));
            $maxRedeemablePoints = max(0, min($availablePoints, (int) floor($maxAmount / $pointValue)));
        }

        $messages = array_values(array_filter((array) ($quote['messages'] ?? [])));
        $message = $quote['message'] ?? $status['message'] ?? null;
        if ($message) {
            $messages[] = (string) $message;
        }

        if ($redemptionEnabled && $eligibleUser) {
            $messages = array_values(array_filter($messages, fn ($line) => ! str_contains(strtolower((string) $line), 'redemption is currently disabled')));
        }

        if (empty($messages)) {
            if (! $programEnabled) {
                $messages[] = 'Bandara Credit redemption is currently disabled. You can still earn credits on eligible orders.';
            } elseif (! $eligibleUser) {
                $messages[] = 'Bandara Credit redemption is available only for eligible B2C customer accounts.';
            } elseif ($availablePoints <= 0) {
                $messages[] = 'You do not currently have available Bandara Credit to redeem.';
            } elseif ($maxRedeemablePoints <= 0) {
                $messages[] = 'Bandara Credit cannot be applied to this order amount.';
            }
        }

        $pointValue = (float) ($quote['point_value'] ?? config('bandara_credit.redemption.point_value', 1));
        $redeemAmount = (float) ($quote['redeem_amount'] ?? $quote['applied_amount'] ?? 0);
        $maxAmount = (float) ($quote['max_redeemable_amount'] ?? $quote['max_redeem_amount'] ?? ($maxRedeemablePoints * $pointValue));
        $requested = max(0, (int) ($quote['requested_points'] ?? $requestedPoints ?? 0));
        $applied = max(0, (int) ($quote['points_to_redeem'] ?? $quote['applied_points'] ?? 0));

        return array_merge($quote, [
            'enabled' => $redemptionEnabled,
            'program_enabled' => $programEnabled,
            'redemption_enabled' => $redemptionEnabled,
            'eligible_user' => $eligibleUser,
            'can_redeem' => $redemptionEnabled && $eligibleUser && $maxRedeemablePoints > 0,
            'available_points' => $availablePoints,
            'reserved_points' => (int) ($quote['reserved_points'] ?? (method_exists($this, 'reservedRedemptionPointsForUser') ? $this->reservedRedemptionPointsForUser($user) : 0)),
            'minimum_points' => (int) ($quote['minimum_points'] ?? config('bandara_credit.redemption.minimum_points', 1)),
            'point_value' => $pointValue,
            'max_redeemable_points' => $maxRedeemablePoints,
            'max_redeemable_amount' => $maxAmount,
            'requested_points' => $requested,
            'points_to_redeem' => $applied,
            'applied_points' => $applied,
            'redeem_amount' => $redeemAmount,
            'applied_amount' => $redeemAmount,
            'messages' => $messages,
            'message' => $messages[0] ?? null,
            'reason' => $quote['reason'] ?? $status['reason'] ?? null,
        ]);
    }

}
