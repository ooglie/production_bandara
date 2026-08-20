<?php $__env->startSection('title', 'Stores Dashboard'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-6xl mx-auto px-4 py-6 space-y-4">

    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Stores Dashboard</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Vendor invoices, inventory lots, production runs, and packs.
            </p>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        
        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">Vendor invoices (pending)</div>
            <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50"><?php echo e($stats['vendor_invoices_pending']); ?></div>

            <?php if($vendorInvoicesIndex): ?>
                <a href="<?php echo e($vendorInvoicesIndex); ?>"
                   class="mt-3 inline-flex text-[11px] underline text-gray-700 dark:text-gray-200">
                    Manage vendor invoices
                </a>
            <?php else: ?>
                <div class="mt-3 text-[11px] text-gray-400">Route not configured</div>
            <?php endif; ?>
        </div>

        
        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">Inventory lots (total)</div>
            <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50"><?php echo e($stats['lots_total']); ?></div>

            <?php if($inventoryLotsIndex): ?>
                <a href="<?php echo e($inventoryLotsIndex); ?>"
                   class="mt-3 inline-flex text-[11px] underline text-gray-700 dark:text-gray-200">
                    View lots
                </a>
            <?php else: ?>
                <div class="mt-3 text-[11px] text-gray-400">Route not configured</div>
            <?php endif; ?>
        </div>

        
        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">Production runs (total)</div>
            <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50"><?php echo e($stats['production_runs_total']); ?></div>

            <?php if($productionRunsIndex): ?>
                <a href="<?php echo e($productionRunsIndex); ?>"
                   class="mt-3 inline-flex text-[11px] underline text-gray-700 dark:text-gray-200">
                    Manage production/packs
                </a>
            <?php else: ?>
                <div class="mt-3 text-[11px] text-gray-400">Route not configured</div>
            <?php endif; ?>
        </div>

        
        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">Packs (available)</div>
            <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50"><?php echo e($stats['packs_available']); ?></div>

            <?php if($inventoryPacksIndex): ?>
                <a href="<?php echo e($inventoryPacksIndex); ?>"
                   class="mt-3 inline-flex text-[11px] underline text-gray-700 dark:text-gray-200">
                    Manage packs
                </a>
            <?php else: ?>
                <div class="mt-3 text-[11px] text-gray-400">Route not configured</div>
            <?php endif; ?>
        </div>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/dashboard/stores.blade.php ENDPATH**/ ?>