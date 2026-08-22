<?php $__env->startSection('title', 'Reward Ledger'); ?>
<?php $__env->startSection('breadcrumb', 'Admin · Rewards · Ledger'); ?>
<?php $__env->startSection('content'); ?>
<div class="space-y-4">
    <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Reward Ledger</h1>
    <?php echo $__env->make('admin.rewards._nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <form method="GET" class="flex flex-wrap gap-2 text-xs"><input name="user_id" value="<?php echo e(request('user_id')); ?>" placeholder="User ID" class="rounded border px-2 py-1 dark:border-gray-700 dark:bg-gray-950"><input name="type" value="<?php echo e(request('type')); ?>" placeholder="Type" class="rounded border px-2 py-1 dark:border-gray-700 dark:bg-gray-950"><input name="status" value="<?php echo e(request('status')); ?>" placeholder="Status" class="rounded border px-2 py-1 dark:border-gray-700 dark:bg-gray-950"><button class="rounded bg-gray-900 px-3 py-1 text-white dark:bg-gray-100 dark:text-gray-900">Filter</button></form>
    <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950 overflow-x-auto">
        <table class="min-w-full text-xs"><thead class="text-left text-gray-500"><tr><th class="p-3">Date</th><th class="p-3">Customer</th><th class="p-3">Type</th><th class="p-3">Status</th><th class="p-3">Wallet</th><th class="p-3">Tier pts</th><th class="p-3">Campaign</th><th class="p-3">Note</th></tr></thead><tbody><?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr class="border-t border-gray-100 dark:border-gray-800"><td class="p-3"><?php echo e(optional($tx->created_at)->format('d M Y H:i')); ?></td><td class="p-3"><?php echo e($tx->user?->name ?? ('User #'.$tx->user_id)); ?></td><td class="p-3"><?php echo e($tx->type); ?></td><td class="p-3"><?php echo e($tx->status); ?></td><td class="p-3 font-mono"><?php echo e($tx->amount > 0 ? '+' : ''); ?><?php echo e(number_format($tx->amount)); ?></td><td class="p-3 font-mono"><?php echo e($tx->tier_points > 0 ? '+' : ''); ?><?php echo e(number_format($tx->tier_points)); ?></td><td class="p-3"><?php echo e($tx->campaign?->name ?? '—'); ?></td><td class="p-3 text-gray-500"><?php echo e(Str::limit($tx->note, 80)); ?></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="8" class="p-6 text-center text-gray-500">No ledger entries.</td></tr><?php endif; ?></tbody></table>
    </div>
    <?php echo e($transactions->links()); ?>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/rewards/ledger.blade.php ENDPATH**/ ?>