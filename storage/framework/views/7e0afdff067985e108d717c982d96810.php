<?php $__env->startSection('title', 'Reward Campaigns'); ?>
<?php $__env->startSection('breadcrumb', 'Admin · Rewards · Campaigns'); ?>
<?php $__env->startSection('content'); ?>
<div class="space-y-4">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Reward Campaigns</h1>
        <a href="<?php echo e(route('admin.rewards.campaigns.create')); ?>" class="rounded bg-gray-900 px-3 py-1.5 text-xs text-white dark:bg-gray-100 dark:text-gray-900">+ New campaign</a>
    </div>
    <?php echo $__env->make('admin.rewards._nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php if(session('status')): ?><div class="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-700"><?php echo e(session('status')); ?></div><?php endif; ?>
    <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950">
        <table class="min-w-full text-xs">
            <thead class="text-left text-gray-500"><tr><th class="p-3">Name</th><th class="p-3">Status</th><th class="p-3">Type</th><th class="p-3">Rule</th><th class="p-3">Scope</th><th class="p-3">Dates</th><th class="p-3"></th></tr></thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $campaigns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $campaign): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="border-t border-gray-100 dark:border-gray-800">
                        <td class="p-3"><div class="font-medium text-gray-900 dark:text-gray-50"><?php echo e($campaign->name); ?></div><div class="text-gray-500"><?php echo e($campaign->slug); ?></div></td>
                        <td class="p-3"><?php echo e(str($campaign->status)->headline()); ?></td>
                        <td class="p-3"><?php echo e(str($campaign->type)->headline()); ?></td>
                        <td class="p-3"><?php echo e(number_format((float) $campaign->multiplier, 2)); ?>x <?php if($campaign->fixed_bonus_points): ?> + <?php echo e(number_format($campaign->fixed_bonus_points)); ?> fixed <?php endif; ?> <div class="text-gray-500"><?php echo e($campaign->counts_toward_tier ? 'Counts toward tier' : 'Wallet bonus only'); ?></div></td>
                        <td class="p-3"><?php echo e($campaign->products_count); ?> products · <?php echo e($campaign->categories_count); ?> categories</td>
                        <td class="p-3"><?php echo e(optional($campaign->starts_at)->format('d M Y') ?? 'Any'); ?> → <?php echo e(optional($campaign->ends_at)->format('d M Y') ?? 'Open'); ?></td>
                        <td class="p-3 text-right"><a href="<?php echo e(route('admin.rewards.campaigns.edit', $campaign)); ?>" class="text-indigo-600 hover:underline">Edit</a></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7" class="p-6 text-center text-gray-500">No reward campaigns yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php echo e($campaigns->links()); ?>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/rewards/campaigns/index.blade.php ENDPATH**/ ?>