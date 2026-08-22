<?php $__env->startSection('title', 'Reward Tiers'); ?>
<?php $__env->startSection('breadcrumb', 'Admin · Rewards · Tiers'); ?>
<?php $__env->startSection('content'); ?>
<div class="space-y-4">
    <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Reward Tiers</h1>
    <?php echo $__env->make('admin.rewards._nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php if(session('status')): ?><div class="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-700"><?php echo e(session('status')); ?></div><?php endif; ?>
    <form method="POST" action="<?php echo e(route('admin.rewards.tiers.update')); ?>" class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
        <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs">
                <thead class="text-left text-gray-500"><tr><th class="p-2">Active</th><th class="p-2">Key</th><th class="p-2">Name</th><th class="p-2">Min</th><th class="p-2">Max</th><th class="p-2">Reward %</th><th class="p-2">Order</th></tr></thead>
                <tbody>
                    <?php $__currentLoopData = $tiers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $tier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr class="border-t border-gray-100 dark:border-gray-800">
                        <td class="p-2"><input type="checkbox" name="tiers[<?php echo e($i); ?>][is_active]" value="1" <?php if($tier->is_active): echo 'checked'; endif; ?>></td>
                        <td class="p-2"><input type="hidden" name="tiers[<?php echo e($i); ?>][id]" value="<?php echo e($tier->id); ?>"><input name="tiers[<?php echo e($i); ?>][key]" value="<?php echo e($tier->key); ?>" class="w-28 rounded border px-2 py-1 dark:border-gray-700 dark:bg-gray-950"></td>
                        <td class="p-2"><input name="tiers[<?php echo e($i); ?>][name]" value="<?php echo e($tier->name); ?>" class="w-32 rounded border px-2 py-1 dark:border-gray-700 dark:bg-gray-950"></td>
                        <td class="p-2"><input type="number" name="tiers[<?php echo e($i); ?>][threshold_min]" value="<?php echo e($tier->threshold_min); ?>" class="w-24 rounded border px-2 py-1 dark:border-gray-700 dark:bg-gray-950"></td>
                        <td class="p-2"><input type="number" name="tiers[<?php echo e($i); ?>][threshold_max]" value="<?php echo e($tier->threshold_max); ?>" class="w-24 rounded border px-2 py-1 dark:border-gray-700 dark:bg-gray-950" placeholder="∞"></td>
                        <td class="p-2"><input type="number" step="0.01" name="tiers[<?php echo e($i); ?>][reward_rate_percent]" value="<?php echo e($tier->reward_rate_percent); ?>" class="w-24 rounded border px-2 py-1 dark:border-gray-700 dark:bg-gray-950"></td>
                        <td class="p-2"><input type="number" name="tiers[<?php echo e($i); ?>][sort_order]" value="<?php echo e($tier->sort_order); ?>" class="w-20 rounded border px-2 py-1 dark:border-gray-700 dark:bg-gray-950"></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-xs text-gray-500">Default policy: Silver 0–999 at 1%, Gold 1000–3499 at 2%, Platinum 3500+ at 4%. Tier progress is annual tier points, not current wallet balance.</p>
        <button class="mt-4 rounded bg-gray-900 px-3 py-1.5 text-xs text-white dark:bg-gray-100 dark:text-gray-900">Save tiers</button>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/rewards/tiers.blade.php ENDPATH**/ ?>