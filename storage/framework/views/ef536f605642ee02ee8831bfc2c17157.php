<?php $__env->startSection('title', 'My rewards'); ?>

<?php $__env->startSection('content'); ?>
<?php
    use Illuminate\Support\Str;

    $availablePoints = (int) ($availablePoints ?? 0);
    $pendingPoints = (int) ($pendingPoints ?? 0);
    $reservedPoints = (int) ($reservedPoints ?? 0);
    $lifetimePoints = (int) ($lifetimePoints ?? 0);
    $redeemedPoints = (int) ($redeemedPoints ?? 0);

    $nextRewardAt = (int) ($nextRewardAt ?? 500);
    $pointsToNextReward = max($nextRewardAt - $availablePoints, 0);

    $progressPercent = $nextRewardAt > 0
        ? min(100, (int) round(($availablePoints / $nextRewardAt) * 100))
        : 0;

    $earnRateLabel = $earnRateLabel ?? 'Earn 1 point for every ₹100 spent';
    $redeemRuleLabel = $redeemRuleLabel ?? 'Redeem once you reach 500 points';
    $expiryLabel = $expiryLabel ?? null;

    $currentTier = strtolower((string) ($currentTier ?? 'silver'));
    $currentTierLabel = (string) ($currentTierLabel ?? Str::headline($currentTier));
    $tierPoints = (int) ($tierPoints ?? $annualTierPoints ?? 0);
    $tierRewardRatePercent = (float) ($tierRewardRatePercent ?? 1);
    $nextTier = $nextTier ?? null;
    $nextTierLabel = $nextTierLabel ?? ($nextTier ? Str::headline((string) $nextTier) : null);
    $nextTierThreshold = isset($nextTierThreshold) && $nextTierThreshold !== null ? (int) $nextTierThreshold : null;
    $pointsToNextTier = (int) ($pointsToNextTier ?? 0);
    $tierProgressPercent = min(100, max(0, (float) ($tierProgressPercent ?? 0)));
    $tierValidUntil = $tierValidUntil ?? null;

    $pointsHistory = collect($pointsHistory ?? []);
    $programEnabled = (bool) ($programEnabled ?? false);
    $eligibleUser = (bool) ($eligibleUser ?? true);
    $redemptionEnabled = (bool) ($redemptionEnabled ?? $redeemEnabled ?? false);
?>

<div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                My rewards
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Track your points, understand how rewards work, and see recent activity.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <?php if(Route::has('account.rewards.terms')): ?>
                <a href="<?php echo e(route('account.rewards.terms')); ?>"
                   class="inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-4 py-2 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                    Reward rules
                </a>
            <?php endif; ?>
            <a href="<?php echo e(route('account.dashboard')); ?>"
               class="inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-4 py-2 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                Back to dashboard
            </a>
        </div>
    </div>


    <?php if(! $programEnabled): ?>
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-[12px] text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200">
            Bandara Credit is not live yet. Your reward information will appear here once the programme is enabled.
        </div>
    <?php elseif(! $eligibleUser): ?>
        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-[12px] text-gray-600 dark:border-gray-800 dark:bg-gray-950/40 dark:text-gray-300">
            Bandara Credit is currently available only for eligible retail customer accounts.
        </div>
    <?php endif; ?>

    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Available points</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-50">
                <?php echo e(number_format($availablePoints)); ?>

            </div>
            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                <?php echo e($redemptionEnabled ? 'Ready to redeem' : 'Tracked safely for future redemption'); ?>

            </div>
        </div>

        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Current tier</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-50">
                <?php echo e($currentTierLabel); ?>

            </div>
            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                <?php echo e(number_format($tierPoints)); ?> annual tier points
                <?php if($tierRewardRatePercent > 0): ?>
                    · <?php echo e(rtrim(rtrim(number_format($tierRewardRatePercent, 2), '0'), '.')); ?>% back
                <?php endif; ?>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Pending points</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-50">
                <?php echo e(number_format($pendingPoints)); ?>

            </div>
            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                Confirmed after eligible order delivery
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Reserved points</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-50">
                <?php echo e(number_format($reservedPoints)); ?>

            </div>
            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                Held during pending checkout
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Lifetime points earned</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-50">
                <?php echo e(number_format($lifetimePoints)); ?>

            </div>
            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                Total points earned so far
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Lifetime redeemed</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-50">
                <?php echo e(number_format($redeemedPoints)); ?>

            </div>
            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                Points used on checkout
            </div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-[1.1fr,0.9fr]">
        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 space-y-4">
            <div>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                    <?php echo e($nextTierLabel ? 'Progress to '.$nextTierLabel : 'Top tier reached'); ?>

                </h2>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                    <?php if($nextTierLabel): ?>
                        <?php echo e(number_format($pointsToNextTier)); ?> annual tier points to <?php echo e($nextTierLabel); ?>.
                    <?php else: ?>
                        You are currently at the highest Bandara Credit tier.
                    <?php endif; ?>
                    <?php if($tierValidUntil): ?>
                        Valid until <?php echo e(\Illuminate\Support\Carbon::parse($tierValidUntil)->format('d M Y')); ?>.
                    <?php endif; ?>
                </p>
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between text-[10px] text-gray-500 dark:text-gray-400">
                    <span>Annual tier points</span>
                    <span>
                        <?php if($nextTierLabel): ?>
                            <?php echo e(number_format($tierPoints)); ?><?php echo e($nextTierThreshold ? ' / '.number_format($nextTierThreshold) : ''); ?> pts
                        <?php else: ?>
                            100%
                        <?php endif; ?>
                    </span>
                </div>

                <div class="h-2 rounded-sm bg-gray-100 dark:bg-gray-800 overflow-hidden">
                    <div class="h-full bg-gray-900 dark:bg-gray-100" style="width: <?php echo e($nextTierLabel ? $tierProgressPercent : 100); ?>%"></div>
                </div>

                <div class="flex items-center justify-between text-[10px] text-gray-500 dark:text-gray-400">
                    <span><?php echo e($nextTierLabel ? number_format($pointsToNextTier).' tier points to go' : 'Highest reward rate reached'); ?></span>
                    <span><?php echo e($nextTierLabel ? rtrim(rtrim(number_format($tierProgressPercent, 1), '0'), '.') : 100); ?>%</span>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                    <div class="text-[10px] uppercase tracking-wide text-gray-400">How to earn</div>
                    <div class="mt-1 text-[12px] text-gray-700 dark:text-gray-200">
                        <?php echo e($earnRateLabel); ?>

                    </div>
                </div>

                <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                    <div class="text-[10px] uppercase tracking-wide text-gray-400">How to redeem</div>
                    <div class="mt-1 text-[12px] text-gray-700 dark:text-gray-200">
                        <?php echo e($redeemRuleLabel); ?>

                    </div>
                </div>
            </div>

            <?php if($expiryLabel): ?>
                <div class="rounded-sm border border-amber-200 bg-amber-50 px-4 py-3 text-[11px] text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200">
                    <?php echo e($expiryLabel); ?>

                </div>
            <?php endif; ?>
        </div>

        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 space-y-4">
            <div>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                    Recent points activity
                </h2>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                    Latest earned, redeemed, or adjusted points.
                </p>
            </div>

            <?php if($pointsHistory->isEmpty()): ?>
                <div class="rounded-sm border border-dashed border-gray-300 dark:border-gray-700 px-4 py-5 text-[11px] text-gray-500 dark:text-gray-400">
                    No points activity yet.
                </div>
            <?php else: ?>
                <div class="space-y-2">
                    <?php $__currentLoopData = $pointsHistory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $points = (int) ($entry['points'] ?? 0);
                            $isPositive = $points >= 0;
                        ?>

                        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-[12px] font-normal text-gray-900 dark:text-gray-50">
                                        <?php if(!empty($entry['date'])): ?>
                                            
                                                
                                                   <?php echo e($entry['date']); ?>

                                                
                                            
                                        <?php endif; ?>
                                        <span class="ml-4 text-[12px] font-normal text-gray-900 dark:text-gray-50">
                                        <?php echo e($entry['title'] ?? 'Points update'); ?>

                                        </span>
                                        
                                        <?php if(!empty($entry['subtitle'])): ?>
                                                <span class="ml-4 text-[11px] font-thin text-gray-700 dark:text-gray-50">
                                                   <?php echo e($entry['subtitle']); ?>

                                                </span>
                                        <?php endif; ?>
                                        
                                    </div>
                                </div>

                                <div class="text-[12px] font-semibold <?php echo e($isPositive ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300'); ?>">
                                    <?php echo e($isPositive ? '+' : ''); ?><?php echo e(number_format($points)); ?>

                                </div>
                            </div>

                            
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.customer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/customer/account/rewards.blade.php ENDPATH**/ ?>