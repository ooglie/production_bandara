<?php $__env->startSection('title', 'Bandara Credit'); ?>
<?php $__env->startSection('breadcrumb', 'Admin · Rewards'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-4">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Bandara Credit Rewards</h1>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">B2C-only rewards dashboard, tier progress, campaign preview, and safe earn diagnostics.</p>
        </div>
        <span class="rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">B2C customers only</span>
    </div>

    <?php echo $__env->make('admin.rewards._nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php if(session('status')): ?>
        <div class="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300"><?php echo e(session('status')); ?></div>
    <?php endif; ?>
    <?php if($warning): ?>
        <div class="rounded border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200"><?php echo e($warning); ?></div>
    <?php endif; ?>

    <?php
        $stats = $dashboardStats ?? [];
        $ready = (bool) ($stats['ready'] ?? false);
        $flags = $flags ?? [];
        $topTierCustomers = collect($stats['top_tier_customers'] ?? []);
        $tierDistribution = collect($stats['tier_distribution'] ?? []);
    ?>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <div class="text-[10px] uppercase tracking-wide text-gray-500">Highest tier</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-50"><?php echo e(str($stats['highest_tier'] ?? '—')->title()); ?></div>
            <div class="mt-1 text-xs text-gray-500"><?php echo e(number_format($stats['highest_tier_count'] ?? 0)); ?> B2C customers</div>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <div class="text-[10px] uppercase tracking-wide text-gray-500">Wallet credits issued</div>
            <div class="mt-2 text-2xl font-semibold text-emerald-700 dark:text-emerald-300"><?php echo e(number_format($stats['total_provided'] ?? 0)); ?></div>
            <div class="mt-1 text-xs text-gray-500">Posted positive wallet ledger</div>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <div class="text-[10px] uppercase tracking-wide text-gray-500">Wallet credits redeemed</div>
            <div class="mt-2 text-2xl font-semibold text-rose-700 dark:text-rose-300"><?php echo e(number_format($stats['total_redeemed'] ?? 0)); ?></div>
            <div class="mt-1 text-xs text-gray-500">Posted redemption/debit ledger</div>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <div class="text-[10px] uppercase tracking-wide text-gray-500">Annual tier points</div>
            <div class="mt-2 text-2xl font-semibold text-indigo-700 dark:text-indigo-300"><?php echo e(number_format($stats['annual_tier_points'] ?? 0)); ?></div>
            <div class="mt-1 text-xs text-gray-500">Current calendar year, B2C only</div>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950 xl:col-span-2">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Tier distribution</h2>
            <div class="mt-3 space-y-2">
                <?php $__empty_1 = true; $__currentLoopData = $tierDistribution; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="flex items-center justify-between rounded border border-gray-100 px-3 py-2 text-xs dark:border-gray-800">
                        <div class="font-medium text-gray-800 dark:text-gray-100"><?php echo e(str($tier['tier'] ?? '')->title()); ?></div>
                        <div class="text-gray-500"><?php echo e(number_format($tier['customers_count'] ?? 0)); ?> customers · <?php echo e(number_format($tier['balance_sum'] ?? 0)); ?> outstanding</div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="text-xs text-gray-500">No wallet data yet.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Operational flags</h2>
            <div class="mt-3 space-y-1 text-xs text-gray-600 dark:text-gray-300">
                <?php $__currentLoopData = $flags; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="flex justify-between gap-3"><span><?php echo e(str($key)->headline()); ?></span><span class="font-mono"><?php echo e($value ? 'on' : 'off'); ?></span></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Top customers in highest tier</h2>
            <div class="mt-3 space-y-2 text-xs">
                <?php $__empty_1 = true; $__currentLoopData = $topTierCustomers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="flex items-center justify-between rounded border border-gray-100 px-3 py-2 dark:border-gray-800">
                        <div>
                            <div class="font-medium text-gray-900 dark:text-gray-50"><?php echo e($row->name ?? ('User #'.$row->user_id)); ?></div>
                            <div class="text-gray-500"><?php echo e($row->email); ?></div>
                        </div>
                        <div class="text-right"><div><?php echo e(str($row->tier)->title()); ?></div><div class="text-gray-500"><?php echo e(number_format($row->balance)); ?> pts</div></div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="text-gray-500">No top-tier B2C customers yet.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Active campaigns</h2>
            <div class="mt-3 space-y-2 text-xs">
                <?php $__empty_1 = true; $__currentLoopData = $activeCampaigns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $campaign): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="rounded border border-gray-100 px-3 py-2 dark:border-gray-800">
                        <div class="flex justify-between gap-3"><span class="font-medium text-gray-900 dark:text-gray-50"><?php echo e($campaign->name); ?></span><span><?php echo e(number_format((float) $campaign->multiplier, 2)); ?>x</span></div>
                        <div class="mt-1 text-gray-500"><?php echo e(str($campaign->type)->headline()); ?> · <?php echo e($campaign->counts_toward_tier ? 'Counts toward tier' : 'Wallet bonus only'); ?></div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="text-gray-500">No active reward campaigns.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
        <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Reward preview</h2>
        <form method="GET" action="<?php echo e(route('admin.rewards.index')); ?>" class="mt-3 flex flex-wrap items-end gap-3 text-xs">
            <div><label class="block text-[10px] text-gray-500">B2C user ID</label><input name="user_id" value="<?php echo e(request('user_id')); ?>" class="w-32 rounded border border-gray-300 px-2 py-1 dark:border-gray-700 dark:bg-gray-950"></div>
            <div><label class="block text-[10px] text-gray-500">Order ID</label><input name="order_id" value="<?php echo e(request('order_id')); ?>" class="w-32 rounded border border-gray-300 px-2 py-1 dark:border-gray-700 dark:bg-gray-950"></div>
            <button class="rounded bg-gray-900 px-3 py-1.5 text-white dark:bg-gray-100 dark:text-gray-900">Preview</button>
        </form>
        <?php if($user): ?>
            <div class="mt-4 grid gap-3 md:grid-cols-3 text-xs">
                <div class="rounded border border-gray-100 p-3 dark:border-gray-800"><div class="text-gray-500">Customer</div><div class="mt-1 font-medium"><?php echo e($user->name); ?> · <?php echo e(strtoupper($user->customer_type ?? 'n/a')); ?></div></div>
                <div class="rounded border border-gray-100 p-3 dark:border-gray-800"><div class="text-gray-500">Wallet</div><div class="mt-1 font-medium"><?php echo e(number_format($walletSnapshot['balance'] ?? 0)); ?> pts · <?php echo e(str($walletSnapshot['tier'] ?? 'silver')->title()); ?></div></div>
                <div class="rounded border border-gray-100 p-3 dark:border-gray-800"><div class="text-gray-500">Tier points</div><div class="mt-1 font-medium"><?php echo e(number_format($tierPreview['tier_points'] ?? 0)); ?> / next <?php echo e($tierPreview['next_tier'] ? str($tierPreview['next_tier'])->title() : '—'); ?></div></div>
            </div>
        <?php endif; ?>
        <?php if($orderPreview): ?>
            <div class="mt-4 grid gap-3 md:grid-cols-5 text-xs">
                <?php $__currentLoopData = ['base_credit' => 'Base', 'tier_bonus' => 'Tier bonus', 'promo_bonus' => 'Promo', 'total_credit_preview' => 'Wallet total', 'tier_points_preview' => 'Tier points']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="rounded border border-gray-100 p-3 dark:border-gray-800"><div class="text-gray-500"><?php echo e($label); ?></div><div class="mt-1 text-lg font-semibold"><?php echo e(number_format($orderPreview[$key] ?? 0)); ?></div></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/rewards/index.blade.php ENDPATH**/ ?>