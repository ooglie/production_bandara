<?php $__env->startSection('title', 'All Products Report'); ?>
<?php $__env->startSection('breadcrumb', 'Admin · Reports · All Products'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-5xl mx-auto px-4 py-5 text-xs space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">All Products</h1>
            <p class="text-[12px] text-gray-500 dark:text-gray-400">
                Every product and variant in the catalogue, regardless of its current stock level.
            </p>
        </div>
        <div class="flex gap-2">
            <a href="<?php echo e(route('admin.reports.all-products.export')); ?>" class="inline-flex items-center rounded-xl border border-gray-900 bg-gray-900 px-3 py-2 text-[12px] font-semibold text-white hover:bg-gray-800 dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">
                Export CSV
            </a>
            <a href="<?php echo e(route('admin.reports.index')); ?>" class="inline-flex items-center rounded-xl border border-gray-300 px-3 py-2 text-[12px] hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-900">
                ← Reports
            </a>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-3 text-[12px] text-gray-500 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-400">
        <?php echo e(number_format($rows->total())); ?> <?php echo e(\Illuminate\Support\Str::plural('catalogue row', $rows->total())); ?> in the report.
        Vendor price is the latest non-cancelled vendor-invoice unit cost; it is ₹0.00 when unavailable.
    </div>

    <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950">
        <table class="min-w-full text-[12px]">
            <thead class="bg-gray-50 text-[11px] uppercase text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3 text-left">Product Name</th>
                    <th class="px-4 py-3 text-left">Variant</th>
                    <th class="px-4 py-3 text-left">SKU</th>
                    <th class="px-4 py-3 text-right">Vendor Price</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-50"><?php echo e($row->product_name); ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300"><?php echo e($row->variant_name ?: '—'); ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300"><?php echo e($row->sku ?: '—'); ?></td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-200">₹<?php echo e(number_format((float) $row->vendor_price, 2)); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">No products found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if($rows->hasPages()): ?>
        <div>
            <?php echo e($rows->links()); ?>

        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/reports/all-products.blade.php ENDPATH**/ ?>