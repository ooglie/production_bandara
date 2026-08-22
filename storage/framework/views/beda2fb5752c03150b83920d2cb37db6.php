<?php
    $isB2BRewardsCustomer = (auth()->user()?->customer_type ?? 'b2c') === 'b2b';
    $availablePoints = (int) ($availablePoints ?? 0);
    $pendingPoints = (int) ($pendingPoints ?? 0);
    $redemptionEnabled = (bool) ($redemptionEnabled ?? ($redeemEnabled ?? false));
    $currentTier = strtolower((string) ($currentTier ?? 'silver'));
    $currentTierLabel = (string) ($currentTierLabel ?? $currentTierName ?? $tierName ?? Illuminate\Support\Str::headline($currentTier));
    $tierPoints = (int) ($tierPoints ?? $annualTierPoints ?? 0);
    $nextTier = $nextTier ?? null;
    $nextTierLabel = $nextTierLabel ?? ($nextTier ? Illuminate\Support\Str::headline((string) $nextTier) : null);
    $pointsToNextTier = (int) ($pointsToNextTier ?? $tierPointsToNext ?? 0);
    $tierProgressPercent = min(100, max(0, (float) ($tierProgressPercent ?? 0)));
    $tierRewardRatePercent = (float) ($tierRewardRatePercent ?? 0);
    $tierValidUntil = $tierValidUntil ?? null;

    $tierBadgeClass = match ($currentTier) {
        'platinum' => 'border-slate-300 bg-slate-100 text-slate-800 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100',
        'gold' => 'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200',
        default => 'border-gray-300 bg-gray-50 text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200',
    };

    $rewardsUrl = $rewardsUrl ?? (Route::has('account.rewards') ? route('account.rewards') : '#');
?>

<?php if(! $isB2BRewardsCustomer): ?>
<div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 space-y-4">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                Your rewards
            </p>
            <p class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                Track redeemable Bandara Credit and your current tier progress.
            </p>
        </div>

        <a href="<?php echo e($rewardsUrl); ?>"
           class="inline-flex items-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1 text-[10px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
            View rewards
        </a>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-3">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Current tier</div>
            <div class="mt-1 flex items-center gap-2">
                <span class="inline-flex rounded-full border px-2.5 py-1 text-[11px] font-semibold <?php echo e($tierBadgeClass); ?>">
                    <?php echo e($currentTierLabel); ?>

                </span>
                <?php if($tierRewardRatePercent > 0): ?>
                    <span class="text-[11px] text-gray-500 dark:text-gray-400"><?php echo e(rtrim(rtrim(number_format($tierRewardRatePercent, 2), '0'), '.')); ?>% back</span>
                <?php endif; ?>
            </div>
            <div class="mt-2 text-[10px] text-gray-500 dark:text-gray-400">
                <?php echo e(number_format($tierPoints)); ?> annual tier points
            </div>
        </div>

        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-3">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Available</div>
            <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">
                <?php echo e(number_format($availablePoints)); ?>

            </div>
            <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                <?php echo e($redemptionEnabled ? 'Ready to redeem' : 'Tracked for future redemption'); ?>

            </div>
        </div>

        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-3">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Pending</div>
            <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">
                <?php echo e(number_format($pendingPoints)); ?>

            </div>
            <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                Will be added after order completion
            </div>
        </div>

        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-3">
            <div class="text-[10px] uppercase tracking-wide text-gray-400"><?php echo e($nextTierLabel ? 'Next tier' : 'Highest tier'); ?></div>
            <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">
                <?php echo e($nextTierLabel ?: $currentTierLabel); ?>

            </div>
            <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                <?php if($nextTierLabel): ?>
                    <?php echo e(number_format($pointsToNextTier)); ?> points to go
                <?php else: ?>
                    You are at the top tier
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="space-y-2">
        <div class="flex items-center justify-between text-[10px] text-gray-500 dark:text-gray-400">
            <span><?php echo e($nextTierLabel ? 'Progress to '.$nextTierLabel : 'Top tier reached'); ?></span>
            <span>
                <?php if($nextTierLabel): ?>
                    <?php echo e(number_format($tierPoints)); ?> / <?php echo e(number_format($tierPoints + $pointsToNextTier)); ?> pts
                <?php else: ?>
                    100%
                <?php endif; ?>
            </span>
        </div>

        <div class="h-2 rounded-sm bg-gray-100 dark:bg-gray-800 overflow-hidden">
            <div class="h-full bg-gray-900 dark:bg-gray-100" style="width: <?php echo e($nextTierLabel ? min(100, max(0, $tierProgressPercent)) : 100); ?>%"></div>
        </div>

        <div class="text-[10px] text-gray-500 dark:text-gray-400">
            <?php if($nextTierLabel): ?>
                <?php echo e(number_format($pointsToNextTier)); ?> annual tier points to <?php echo e($nextTierLabel); ?>.
            <?php else: ?>
                You are currently at the highest Bandara Credit tier.
            <?php endif; ?>
            <?php if($tierValidUntil): ?>
                Valid until <?php echo e(\Illuminate\Support\Carbon::parse($tierValidUntil)->format('d M Y')); ?>.
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/dashboard/partials/rewards_summary.blade.php ENDPATH**/ ?>