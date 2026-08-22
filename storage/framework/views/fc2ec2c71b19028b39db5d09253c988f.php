<?php $__env->startSection('title', 'B2B Catalog'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $fmt = fn($q) => rtrim(rtrim(number_format((float)$q, 2), '0'), '.');
?>
<div class="max-w-6xl mx-auto px-4 py-6 text-xs space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                B2B Catalog: <?php echo e($user->name); ?>

            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Assign product-level access or specific pack variants such as 10pc pack, 20pc pack, or box. MOQ and price can vary per option.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="<?php echo e(route('admin.customers.b2b-products.create', $user)); ?>"
               class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                Add option
            </a>

            <?php if(\Illuminate\Support\Facades\Route::has('admin.b2b.prices.index')): ?>
                <a href="<?php echo e(route('admin.b2b.prices.index', $user)); ?>"
                   class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                    Manage B2B prices
                </a>
            <?php endif; ?>

            <a href="<?php echo e(url()->previous()); ?>"
               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                Back
            </a>
        </div>
    </div>

    <?php if(session('status')): ?>
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
        <table class="min-w-full text-[11px]">
            <thead class="bg-gray-50 dark:bg-gray-950/40">
                <tr class="text-left text-gray-600 dark:text-gray-300">
                    <th class="px-3 py-2 font-medium">Product</th>
                    <th class="px-3 py-2 font-medium">Variant / scope</th>
                    <th class="px-3 py-2 font-medium">MOQ</th>
                    <th class="px-3 py-2 font-medium">Price</th>
                    <th class="px-3 py-2 font-medium">Active</th>
                    <th class="px-3 py-2 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $price = $priceOverrides->get($row->product_id . '|' . ((int)($row->product_variant_id ?? 0)));
                    ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                        <td class="px-3 py-2 text-gray-900 dark:text-gray-50">
                            <div class="font-medium"><?php echo e($row->product?->name ?? ('Product #' . $row->product_id)); ?></div>
                            <?php if($row->product?->sku): ?>
                                <div class="text-[10px] text-gray-400"><?php echo e($row->product->sku); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                            <?php if($row->productVariant): ?>
                                <div class="font-medium"><?php echo e($row->productVariant->name ?: ($row->productVariant->sku ?: ('Variant #' . $row->product_variant_id))); ?></div>
                                <?php if($row->productVariant->sku): ?>
                                    <div class="text-[10px] text-gray-400"><?php echo e($row->productVariant->sku); ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-gray-400">Product-level</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                            <?php echo e($fmt($row->min_order_quantity ?? 1)); ?>

                        </td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                            <?php if($price): ?>
                                ₹<?php echo e(number_format((float)$price->price, 2)); ?>

                            <?php else: ?>
                                <span class="text-gray-400">Fallback / not set</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-2">
                            <?php if($row->is_active): ?>
                                <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200">Active</span>
                            <?php else: ?>
                                <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-[10px] text-gray-600 dark:bg-gray-800 dark:text-gray-300">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-2 text-right">
                            <div class="inline-flex items-center gap-2">
                                <a href="<?php echo e(route('admin.customers.b2b-products.edit', [$user, $row])); ?>"
                                   class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                    Edit
                                </a>
                                <form method="POST" action="<?php echo e(route('admin.customers.b2b-products.destroy', [$user, $row])); ?>" onsubmit="return confirm('Remove this B2B catalog option?');">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button class="text-[11px] px-3 py-1 rounded-full border border-red-300 text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-200 dark:hover:bg-red-900/20">
                                        Remove
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                            No B2B catalog options assigned yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        <?php echo e($rows->links()); ?>

    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/customers/b2b-products/index.blade.php ENDPATH**/ ?>