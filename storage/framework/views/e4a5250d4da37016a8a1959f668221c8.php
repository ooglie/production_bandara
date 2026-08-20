<?php $__env->startSection('title', 'Product Labels'); ?>

<?php $__env->startSection('content'); ?>
    <div class="max-w-7xl mx-auto space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Product Labels</h1>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Generate exact 4 × 3 inch labels from product and company data.
                </p>
            </div>

            <form method="GET" action="<?php echo e(route('admin.labels.index')); ?>" class="flex items-center gap-2">
                <input type="search"
                       name="q"
                       value="<?php echo e($search); ?>"
                       placeholder="Search name or SKU"
                       class="w-64 rounded-md border border-gray-300 bg-white px-3 py-2 text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                <button type="submit" class="rounded-md bg-gray-900 px-3 py-2 text-xs font-medium text-white dark:bg-gray-100 dark:text-gray-900">
                    Search
                </button>
            </form>
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950">
            <table class="min-w-full divide-y divide-gray-200 text-xs dark:divide-gray-800">
                <thead class="bg-gray-50 text-[11px] uppercase text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3 text-left">Product</th>
                        <th class="px-4 py-3 text-left">Category</th>
                        <th class="px-4 py-3 text-left">Origin</th>
                        <th class="px-4 py-3 text-right">MRP</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    <?php $__empty_1 = true; $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 dark:text-gray-50"><?php echo e($product->name); ?></div>
                                <div class="mt-0.5 text-[10px] text-gray-400"><?php echo e($product->sku ?: 'No SKU'); ?></div>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                <?php echo e($product->categories->pluck('name')->implode(', ') ?: 'Uncategorised'); ?>

                            </td>
                            <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-300">
                                <?php echo e($product->country_of_origin ?: '—'); ?>

                            </td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-200">
                                <?php echo e($product->label_mrp > 0 ? '₹' . number_format((float) $product->label_mrp, 2) : '—'); ?>

                            </td>
                            <td class="px-4 py-3 text-right">
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('manage labels')): ?>
                                    <div class="inline-flex flex-wrap justify-end gap-2">
                                        <?php if($product->label_batch_enabled): ?>
                                            <a href="<?php echo e(route('admin.labels.batch.edit', $product)); ?>"
                                               class="inline-flex rounded-md bg-gray-900 px-3 py-1.5 text-[11px] font-medium text-white dark:bg-gray-100 dark:text-gray-900">
                                                Batch by weight
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?php echo e(route('admin.labels.edit', $product)); ?>"
                                           class="inline-flex rounded-md border border-gray-300 px-3 py-1.5 text-[11px] font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">
                                            Single label
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">No products found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php echo e($products->links()); ?>

    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/product-labels/index.blade.php ENDPATH**/ ?>