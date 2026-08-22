<?php $__env->startSection('title', 'Product Sales Report'); ?>
<?php $__env->startSection('breadcrumb', 'Admin · Reports · Product Sales'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $money = static fn($value) => '₹' . number_format((float) $value, 2);
    $num = static function ($value, int $decimals = 3) {
        $formatted = number_format((float) $value, $decimals, '.', '');
        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    };
?>

<div class="max-w-7xl mx-auto px-4 py-5 text-xs space-y-5">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Product Sales</h1>
            <p class="text-[12px] text-gray-500 dark:text-gray-400">
                Product and variant sales across simple, variant-choice and slab/catchweight items.
            </p>
        </div>
        <a href="<?php echo e(route('admin.reports.index')); ?>" class="inline-flex items-center rounded-xl border border-gray-300 px-3 py-2 text-[12px] hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-900">
            ← Reports
        </a>
    </div>

    <form method="GET" action="<?php echo e(route('admin.reports.product-sales')); ?>" class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
        <div class="grid gap-3 md:grid-cols-8 items-end">
            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">From</label>
                <input type="date" name="from" value="<?php echo e($from); ?>" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[12px] dark:border-gray-700 dark:bg-gray-900">
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">To</label>
                <input type="date" name="to" value="<?php echo e($to); ?>" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[12px] dark:border-gray-700 dark:bg-gray-900">
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">Status</label>
                <select name="status" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[12px] dark:border-gray-700 dark:bg-gray-900">
                    <?php $__currentLoopData = $statusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($value); ?>" <?php if(($filters['status'] ?? '') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">Customer</label>
                <select name="customer_type" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[12px] dark:border-gray-700 dark:bg-gray-900">
                    <?php $__currentLoopData = $customerTypeOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($value); ?>" <?php if(($filters['customer_type'] ?? '') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">Category</label>
                <select name="category_id" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[12px] dark:border-gray-700 dark:bg-gray-900">
                    <option value="0">All categories</option>
                    <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($category->id); ?>" <?php if((int)($filters['category_id'] ?? 0) === (int)$category->id): echo 'selected'; endif; ?>><?php echo e($category->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">Product</label>
                <select name="product_id" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[12px] dark:border-gray-700 dark:bg-gray-900">
                    <option value="0">All products</option>
                    <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($product->id); ?>" <?php if((int)($filters['product_id'] ?? 0) === (int)$product->id): echo 'selected'; endif; ?>>
                            <?php echo e($product->name); ?><?php echo e($product->sku ? ' · '.$product->sku : ''); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="flex gap-2">
                <button class="flex-1 rounded-lg border border-gray-900 bg-gray-900 px-3 py-2 text-[12px] font-semibold text-white hover:bg-gray-800 dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">
                    Apply
                </button>
                <a href="<?php echo e(route('admin.reports.product-sales.export', request()->query())); ?>" class="rounded-lg border border-gray-300 px-3 py-2 text-[12px] hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-900">
                    CSV
                </a>
            </div>
        </div>
    </form>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">Product / variant lines</div>
            <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50"><?php echo e(number_format($summary['lines'])); ?></div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">Quantity sold</div>
            <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50"><?php echo e($num($summary['quantity_sold'])); ?></div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">Weight sold</div>
            <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50"><?php echo e($num($summary['weight_sold_kg'])); ?> kg</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">Revenue</div>
            <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50"><?php echo e($money($summary['revenue'])); ?></div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">GST / tax</div>
            <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50"><?php echo e($money($summary['tax'])); ?></div>
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950">
        <table class="min-w-full text-[12px]">
            <thead class="bg-gray-50 text-[11px] uppercase text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3 text-left">Product</th>
                    <th class="px-4 py-3 text-left">Variant / option</th>
                    <th class="px-4 py-3 text-left">SKU</th>
                    <th class="px-4 py-3 text-right">Qty sold</th>
                    <th class="px-4 py-3 text-right">Kg sold</th>
                    <th class="px-4 py-3 text-right">Revenue</th>
                    <th class="px-4 py-3 text-right">GST</th>
                    <th class="px-4 py-3 text-right">Orders</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-50"><?php echo e($row->product_name); ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300"><?php echo e($row->variant_name ?: '—'); ?></td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400"><?php echo e($row->sku ?: '—'); ?></td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-200"><?php echo e($num($row->quantity_sold)); ?></td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-200"><?php echo e($num($row->weight_sold_kg)); ?></td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-gray-50"><?php echo e($money($row->revenue)); ?></td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-200"><?php echo e($money($row->tax)); ?></td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-200"><?php echo e(number_format((int) $row->orders_count)); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">No product sales found for the selected filters.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/reports/product-sales.blade.php ENDPATH**/ ?>