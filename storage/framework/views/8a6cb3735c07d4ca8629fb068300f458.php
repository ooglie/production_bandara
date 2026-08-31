<?php
    $links = [
        ['label' => 'Dashboard', 'route' => 'admin.rewards.index'],
        ['label' => 'Tiers', 'route' => 'admin.rewards.tiers'],
        ['label' => 'Campaigns', 'route' => 'admin.rewards.campaigns.index'],
        ['label' => 'Customers', 'route' => 'admin.rewards.customers'],
        ['label' => 'Reports', 'route' => 'admin.rewards.reports'],
        ['label' => 'Ledger', 'route' => 'admin.rewards.ledger'],
    ];
?>

<div class="flex flex-wrap gap-2 border-b border-gray-200 pb-3 text-xs dark:border-gray-800">
    <?php $__currentLoopData = $links; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if(Route::has($link['route'])): ?>
            <a href="<?php echo e(route($link['route'])); ?>"
               class="rounded border px-3 py-1.5 <?php echo e(request()->routeIs($link['route']) || (request()->routeIs('admin.rewards.campaigns.*') && $link['route'] === 'admin.rewards.campaigns.index') ? 'border-gray-900 bg-gray-900 text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900' : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900'); ?>">
                <?php echo e($link['label']); ?>

            </a>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/rewards/_nav.blade.php ENDPATH**/ ?>