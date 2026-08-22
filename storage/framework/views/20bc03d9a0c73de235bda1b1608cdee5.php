<?php $__env->startSection('title', 'Customer Prices'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $fmtDate = function($d) {
        if (!$d) return '—';
        try {
            return \Carbon\Carbon::parse($d)->format('d M Y');
        } catch (\Throwable $e) {
            return (string) $d;
        }
    };
?>

<div class="max-w-7xl mx-auto px-4 py-5 text-xs space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-50">
                Customer-specific Prices
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Customer: <span class="text-gray-900 dark:text-gray-50 font-medium"><?php echo e($user->name ?? '—'); ?></span>
                <span class="text-gray-400">(#<?php echo e($user->id); ?>)</span>
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="<?php echo e(route('admin.b2b.customers.index')); ?>"
               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                Back
            </a>
            <a href="<?php echo e(route('admin.b2b.moq.index', $user)); ?>"
               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                MOQ
            </a>
            <a href="<?php echo e(route('admin.b2b.prices.create', $user)); ?>"
               class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                Add price
            </a>
        </div>
    </div>

    <?php if(session('status')): ?>
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    
    <form method="GET" class="flex flex-wrap items-center gap-2">
        <span class="text-[11px] text-gray-600 dark:text-gray-300">Filter by product:</span>
        <select name="product_id"
                class="rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px]"
                onchange="this.form.submit()">
            <option value="">All products</option>
            <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($p->id); ?>" <?php if((int)$productId === (int)$p->id): echo 'selected'; endif; ?>>
                    <?php echo e($p->name); ?> <?php if($p->sku): ?> (<?php echo e($p->sku); ?>) <?php endif; ?>
                </option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>

        <?php if($productId): ?>
            <a href="<?php echo e(route('admin.b2b.prices.index', $user)); ?>"
               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                Clear
            </a>
        <?php endif; ?>
    </form>

    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
        <p class="text-[11px] text-gray-600 dark:text-gray-300">
            Pricing priority: <span class="font-medium text-gray-900 dark:text-gray-50">Variant override</span> (if set) → <span class="font-medium text-gray-900 dark:text-gray-50">Product override</span> → product base pricing.
            For overlapping valid periods, the latest <code>valid_from</code> wins.
        </p>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
        <table class="min-w-full text-[11px]">
            <thead class="bg-gray-50 dark:bg-gray-950/40">
            <tr>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Product</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Scope</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Price</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Validity</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Active</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Actions</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            <?php $__empty_1 = true; $__currentLoopData = $prices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                    <td class="px-3 py-2">
                        <div class="font-medium text-gray-900 dark:text-gray-50">
                            <?php echo e($row->product?->name ?? 'Product'); ?>

                        </div>
                        <div class="text-[10px] text-gray-400">
                            Product ID: <?php echo e($row->product_id); ?>

                        </div>
                    </td>
                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                        <?php if($row->product_variant_id): ?>
                            <?php echo e($row->productVariant?->name ?: ($row->productVariant?->sku ?? ('Variant #' . $row->product_variant_id))); ?>

                            <div class="text-[10px] text-gray-400">Variant</div>
                        <?php else: ?>
                            <span class="text-[10px] px-2 py-0.5 rounded-full border border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300">
                                Product-level
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-2 text-gray-900 dark:text-gray-50">
                        ₹<?php echo e(number_format((float)$row->price, 2)); ?>

                        <span class="text-[10px] text-gray-400"><?php echo e($row->currency ?? 'INR'); ?></span>
                    </td>
                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                        <div class="text-[11px]">
                            <?php echo e($fmtDate($row->valid_from)); ?> → <?php echo e($fmtDate($row->valid_to)); ?>

                        </div>
                    </td>
                    <td class="px-3 py-2">
                        <?php if($row->is_active): ?>
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                                Active
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400">
                                Inactive
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-2">
                        <div class="flex flex-wrap gap-2">
                            <a href="<?php echo e(route('admin.b2b.prices.edit', [$user, $row])); ?>"
                               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                Edit
                            </a>

                            <form method="POST" action="<?php echo e(route('admin.b2b.prices.destroy', [$user, $row])); ?>"
                                  onsubmit="return confirm('Delete this price override?');">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button class="text-[11px] px-3 py-1 rounded-full border border-red-300 text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-200 dark:hover:bg-red-900/20">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="6" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                        No customer-specific prices set yet.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        <?php echo e($prices->links()); ?>

    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/b2b/prices/index.blade.php ENDPATH**/ ?>