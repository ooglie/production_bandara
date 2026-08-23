<?php $__env->startSection('title', 'Inventory Stock Report'); ?>
<?php $__env->startSection('breadcrumb', 'Admin · Reports · Inventory Stock'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $labelClass = static function ($type) {
        return match ($type) {
            'variant' => 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-200',
            'lot' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200',
            'piece' => 'border-purple-200 bg-purple-50 text-purple-700 dark:border-purple-900 dark:bg-purple-950/30 dark:text-purple-200',
            'pack' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200',
            default => 'border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300',
        };
    };
?>

<div class="max-w-7xl mx-auto px-4 py-5 text-xs space-y-5">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Inventory Stock</h1>
            <p class="text-[12px] text-gray-500 dark:text-gray-400">
                Combined view of product, variant, lot, piece and pack stock.
            </p>
        </div>
        <a href="<?php echo e(route('admin.reports.index')); ?>" class="inline-flex items-center rounded-xl border border-gray-300 px-3 py-2 text-[12px] hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-900">
            ← Reports
        </a>
    </div>

    <form method="GET" action="<?php echo e(route('admin.reports.inventory-stock')); ?>" class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
        <div class="grid gap-3 md:grid-cols-8 items-end">
            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">Stock type</label>
                <select name="stock_type" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[12px] dark:border-gray-700 dark:bg-gray-900">
                    <option value="">All stock types</option>
                    <?php $__currentLoopData = $stockTypeOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($value); ?>" <?php if(($filters['stock_type'] ?? '') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">Status</label>
                <select name="status" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[12px] dark:border-gray-700 dark:bg-gray-900">
                    <option value="all" <?php if(($filters['status'] ?? 'all') === 'all'): echo 'selected'; endif; ?>>All</option>
                    <option value="available" <?php if(($filters['status'] ?? '') === 'available'): echo 'selected'; endif; ?>>Available only</option>
                    <option value="zero" <?php if(($filters['status'] ?? '') === 'zero'): echo 'selected'; endif; ?>>Zero / depleted</option>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">Expiry</label>
                <select name="expiry" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[12px] dark:border-gray-700 dark:bg-gray-900">
                    <option value="" <?php if(($filters['expiry'] ?? '') === ''): echo 'selected'; endif; ?>>Any expiry</option>
                    <option value="expired" <?php if(($filters['expiry'] ?? '') === 'expired'): echo 'selected'; endif; ?>>Expired</option>
                    <option value="next_7" <?php if(($filters['expiry'] ?? '') === 'next_7'): echo 'selected'; endif; ?>>Next 7 days</option>
                    <option value="next_15" <?php if(($filters['expiry'] ?? '') === 'next_15'): echo 'selected'; endif; ?>>Next 15 days</option>
                    <option value="next_30" <?php if(($filters['expiry'] ?? '') === 'next_30'): echo 'selected'; endif; ?>>Next 30 days</option>
                    <option value="next_60" <?php if(($filters['expiry'] ?? '') === 'next_60'): echo 'selected'; endif; ?>>Next 60 days</option>
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
            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">Rows</label>
                <input type="number" name="limit" min="1" max="<?php echo e($maxLimit); ?>" value="<?php echo e($limit); ?>" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[12px] dark:border-gray-700 dark:bg-gray-900">
            </div>
            <div class="flex gap-2">
                <button class="flex-1 rounded-lg border border-gray-900 bg-gray-900 px-3 py-2 text-[12px] font-semibold text-white hover:bg-gray-800 dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">
                    Apply
                </button>
                <a href="<?php echo e(route('admin.reports.inventory-stock.export', request()->query())); ?>" class="rounded-lg border border-gray-300 px-3 py-2 text-[12px] hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-900">
                    CSV
                </a>
            </div>
        </div>
    </form>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <?php $__currentLoopData = [
            'product_rows' => 'Products',
            'variant_rows' => 'Variants',
            'available_lots' => 'Available lots',
            'available_pieces' => 'Available pieces',
            'available_packs' => 'Available packs',
        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
                <div class="text-[11px] text-gray-500 dark:text-gray-400"><?php echo e($label); ?></div>
                <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50"><?php echo e(number_format((int) ($summary[$key] ?? 0))); ?></div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-3 text-[12px] text-gray-500 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-400">
        Showing up to <?php echo e(number_format($limit)); ?> rows. Use CSV export for the same filtered result set up to <?php echo e(number_format($maxLimit)); ?> rows.
    </div>

    <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950">
        <table class="min-w-full text-[12px]">
            <thead class="bg-gray-50 text-[11px] uppercase text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3 text-left">Type</th>
                    <th class="px-4 py-3 text-left">Product</th>
                    <th class="px-4 py-3 text-left">Variant</th>
                    <th class="px-4 py-3 text-left">Reference</th>
                    <th class="px-4 py-3 text-left">Batch</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-right">Qty</th>
                    <th class="px-4 py-3 text-right">Kg</th>
                    <th class="px-4 py-3 text-right">Pieces</th>
                    <th class="px-4 py-3 text-right">Packs</th>
                    <th class="px-4 py-3 text-left">Expiry</th>
                    <th class="px-4 py-3 text-left">Source</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full border px-2 py-0.5 text-[11px] <?php echo e($labelClass($row['stock_type'])); ?>">
                                <?php echo e($row['type_label']); ?>

                            </span>
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-50"><?php echo e($row['product_name']); ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300"><?php echo e($row['variant_name'] ?: '—'); ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300"><?php echo e($row['reference'] ?: '—'); ?></td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400"><?php echo e($row['batch'] ?: '—'); ?></td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400"><?php echo e($row['status'] ?: '—'); ?></td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-200"><?php echo e($row['quantity'] !== '' ? $row['quantity'] : '—'); ?></td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-200"><?php echo e($row['weight_kg'] !== '' ? $row['weight_kg'] : '—'); ?></td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-200"><?php echo e($row['pieces'] !== '' ? $row['pieces'] : '—'); ?></td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-200"><?php echo e($row['packs'] !== '' ? $row['packs'] : '—'); ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300"><?php echo e($row['expiry_date'] ?: '—'); ?></td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400"><?php echo e($row['source'] ?: '—'); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="12" class="px-4 py-8 text-center text-gray-500">No stock rows found for the selected filters.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/reports/inventory-stock.blade.php ENDPATH**/ ?>