<?php $__env->startSection('title', 'Reward reports'); ?>
<?php $__env->startSection('breadcrumb', 'Admin · Rewards · Reports'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $summary = $summary ?? [];
    $monthlyRows = collect($monthlyRows ?? []);
    $campaignRows = collect($campaignRows ?? []);
    $tierRows = collect($tierRows ?? []);
    $eligibilityAudit = $eligibilityAudit ?? [];
    $metricLabel = fn ($key) => str($key)->headline();
?>

<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Reward reports</h1>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Accounting view for reward liability, campaign performance, redemptions, reversals, and B2C-only audit checks.</p>
        </div>
        <?php if(Route::has('admin.rewards.reports.export')): ?>
            <a href="<?php echo e(route('admin.rewards.reports.export', request()->only(['from', 'to']))); ?>" class="inline-flex items-center rounded bg-gray-900 px-3 py-2 text-xs font-medium text-white dark:bg-gray-100 dark:text-gray-900">Export CSV</a>
        <?php endif; ?>
    </div>

    <?php echo $__env->make('admin.rewards._nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <form method="GET" action="<?php echo e(route('admin.rewards.reports')); ?>" class="flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-white p-4 text-xs dark:border-gray-800 dark:bg-gray-950">
        <div>
            <label class="block text-[10px] uppercase tracking-wide text-gray-500">From</label>
            <input type="date" name="from" value="<?php echo e(request('from', $from->toDateString())); ?>" class="mt-1 rounded border border-gray-300 px-2 py-1 dark:border-gray-700 dark:bg-gray-950">
        </div>
        <div>
            <label class="block text-[10px] uppercase tracking-wide text-gray-500">To</label>
            <input type="date" name="to" value="<?php echo e(request('to', $to->toDateString())); ?>" class="mt-1 rounded border border-gray-300 px-2 py-1 dark:border-gray-700 dark:bg-gray-950">
        </div>
        <button class="rounded bg-gray-900 px-3 py-1.5 text-white dark:bg-gray-100 dark:text-gray-900">Apply</button>
        <a href="<?php echo e(route('admin.rewards.reports')); ?>" class="rounded border border-gray-300 px-3 py-1.5 text-gray-600 dark:border-gray-700 dark:text-gray-300">Reset</a>
    </form>

    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('manage rewards')): ?>
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Partial refund / correction adjustment</h2>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Use this only when an order is partially refunded/cancelled or reward accounting needs a manual order-linked correction. Choose the correction type and enter a positive point amount.
                    </p>
                </div>
            </div>

            <form method="POST" action="<?php echo e(route('admin.rewards.order-adjustments.store')); ?>" class="mt-3 grid gap-3 text-xs md:grid-cols-6">
                <?php echo csrf_field(); ?>
                <div>
                    <label class="block text-[10px] uppercase tracking-wide text-gray-500">Order ID</label>
                    <input type="number" name="order_id" value="<?php echo e(old('order_id')); ?>" class="mt-1 w-full rounded border border-gray-300 px-2 py-1 dark:border-gray-700 dark:bg-gray-950" required>
                </div>
                <div>
                    <label class="block text-[10px] uppercase tracking-wide text-gray-500">Type</label>
                    <select name="adjustment_type" class="mt-1 w-full rounded border border-gray-300 px-2 py-1 dark:border-gray-700 dark:bg-gray-950" required>
                        <option value="earn_reversal">Reverse earned</option>
                        <option value="redeem_restore">Restore redeemed</option>
                        <option value="manual_credit">Manual credit</option>
                        <option value="manual_debit">Manual debit</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] uppercase tracking-wide text-gray-500">Points</label>
                    <input type="number" name="points" value="<?php echo e(old('points')); ?>" min="1" placeholder="40" class="mt-1 w-full rounded border border-gray-300 px-2 py-1 dark:border-gray-700 dark:bg-gray-950" required>
                </div>
                <div>
                    <label class="block text-[10px] uppercase tracking-wide text-gray-500">Tier points</label>
                    <input type="number" name="tier_points" value="<?php echo e(old('tier_points')); ?>" min="0" placeholder="40" class="mt-1 w-full rounded border border-gray-300 px-2 py-1 dark:border-gray-700 dark:bg-gray-950">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[10px] uppercase tracking-wide text-gray-500">Reason / note</label>
                    <div class="flex gap-2">
                        <input type="text" name="note" value="<?php echo e(old('note')); ?>" placeholder="Partial refund of item X" class="mt-1 w-full rounded border border-gray-300 px-2 py-1 dark:border-gray-700 dark:bg-gray-950" required>
                        <button class="mt-1 rounded bg-gray-900 px-3 py-1 text-white dark:bg-gray-100 dark:text-gray-900">Post</button>
                    </div>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <?php if(($eligibilityAudit['non_b2c_wallets'] ?? 0) > 0 || ($eligibilityAudit['non_b2c_transactions'] ?? 0) > 0): ?>
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200">
            B2C eligibility audit requires attention: <?php echo e(number_format($eligibilityAudit['non_b2c_wallets'] ?? 0)); ?> non-B2C wallet(s) and <?php echo e(number_format($eligibilityAudit['non_b2c_transactions'] ?? 0)); ?> non-B2C reward transaction(s). Run <code>php artisan bandara-credit:audit-eligibility --json</code> for details.
        </div>
    <?php endif; ?>

    <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-5">
        <?php $__currentLoopData = $summary; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
                <div class="text-[10px] uppercase tracking-wide text-gray-400"><?php echo e($metricLabel($key)); ?></div>
                <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50"><?php echo e(is_numeric($value) ? number_format((float) $value) : $value); ?></div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <div class="grid gap-4 xl:grid-cols-[1fr,0.8fr]">
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Monthly ledger movement</h2>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-xs dark:divide-gray-800">
                    <thead class="text-left text-gray-500"><tr><th class="px-3 py-2">Month</th><th class="px-3 py-2 text-right">Issued</th><th class="px-3 py-2 text-right">Redeemed</th><th class="px-3 py-2 text-right">Reserved</th><th class="px-3 py-2 text-right">Reversed</th><th class="px-3 py-2 text-right">Promo</th><th class="px-3 py-2 text-right">Tier pts</th></tr></thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <?php $__empty_1 = true; $__currentLoopData = $monthlyRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr><td class="px-3 py-2 font-medium"><?php echo e($row->period); ?></td><td class="px-3 py-2 text-right"><?php echo e(number_format((int) $row->issued)); ?></td><td class="px-3 py-2 text-right"><?php echo e(number_format((int) $row->redeemed)); ?></td><td class="px-3 py-2 text-right"><?php echo e(number_format((int) $row->reserved)); ?></td><td class="px-3 py-2 text-right"><?php echo e(number_format((int) $row->reversed)); ?></td><td class="px-3 py-2 text-right"><?php echo e(number_format((int) $row->promo_bonus)); ?></td><td class="px-3 py-2 text-right"><?php echo e(number_format((int) $row->tier_points)); ?></td></tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="7" class="px-3 py-5 text-center text-gray-500">No reward activity for this period.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Tier liability</h2>
            <div class="mt-3 space-y-2 text-xs">
                <?php $__empty_1 = true; $__currentLoopData = $tierRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="flex items-center justify-between rounded border border-gray-100 px-3 py-2 dark:border-gray-800"><div><div class="font-medium"><?php echo e(str($row->tier ?? 'unknown')->title()); ?></div><div class="text-gray-500"><?php echo e(number_format((int) $row->customers_count)); ?> customers</div></div><div class="text-right font-semibold"><?php echo e(number_format((int) $row->balance_sum)); ?> pts</div></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="rounded border border-dashed border-gray-300 px-3 py-4 text-gray-500 dark:border-gray-700">No wallet data yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
        <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Campaign performance</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-xs dark:divide-gray-800">
                <thead class="text-left text-gray-500"><tr><th class="px-3 py-2">Campaign</th><th class="px-3 py-2">Status</th><th class="px-3 py-2 text-right">Bonus issued</th><th class="px-3 py-2 text-right">Tier points</th><th class="px-3 py-2 text-right">Txns</th><th class="px-3 py-2 text-right">Customers</th><th class="px-3 py-2 text-right">Orders</th><th class="px-3 py-2 text-right">Budget</th></tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <?php $__empty_1 = true; $__currentLoopData = $campaignRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr><td class="px-3 py-2 font-medium"><?php echo e($row->name ?? 'Unassigned campaign'); ?></td><td class="px-3 py-2"><?php echo e(str($row->status ?? 'n/a')->headline()); ?></td><td class="px-3 py-2 text-right"><?php echo e(number_format((int) $row->bonus_issued)); ?></td><td class="px-3 py-2 text-right"><?php echo e(number_format((int) $row->tier_points)); ?></td><td class="px-3 py-2 text-right"><?php echo e(number_format((int) $row->transactions_count)); ?></td><td class="px-3 py-2 text-right"><?php echo e(number_format((int) $row->customers_count)); ?></td><td class="px-3 py-2 text-right"><?php echo e(number_format((int) $row->orders_count)); ?></td><td class="px-3 py-2 text-right"><?php echo e($row->budget_points ? number_format((int) $row->used_budget_points).' / '.number_format((int) $row->budget_points) : 'No cap'); ?></td></tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="8" class="px-3 py-5 text-center text-gray-500">No campaign data for this period.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/rewards/reports.blade.php ENDPATH**/ ?>