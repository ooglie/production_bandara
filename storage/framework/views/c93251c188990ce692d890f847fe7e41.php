<?php $__env->startSection('title', 'Product variants'); ?>

<?php $__env->startSection('breadcrumb'); ?>
    Admin · Products · <?php echo e($product->name); ?> · Variants
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                    Variants for: <?php echo e($product->name); ?>

                </h1>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                    Base SKU: <?php echo e($product->sku ?: '—'); ?> · Parent price is optional for variant products; set MRP and sell price on each active variant.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="<?php echo e(route('admin.products.index', $product)); ?>"
                   class="text-[11px] text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">
                    Products home
                </a>
                <p class="text-[11px] text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">::</p>
                <a href="<?php echo e(route('admin.products.edit', $product)); ?>"
                   class="text-[11px] text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">
                    Back to product
                </a>

                <a href="<?php echo e(route('admin.products.variants.create', $product)); ?>"
                   class="inline-flex items-center px-3 py-1.5 text-xs rounded border border-gray-300 dark:border-gray-700 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-200">
                    + New variant
                </a>
            </div>
        </div>

        <?php if(session('status')): ?>
            <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
                <?php echo e(session('status')); ?>

            </div>
        <?php endif; ?>

        <div class="overflow-x-auto border border-gray-200 dark:border-gray-800 rounded-lg text-xs">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr class="text-[11px] uppercase text-gray-500 dark:text-gray-400">
                        <th class="px-3 py-2 text-left">SKU</th>
                        <th class="px-3 py-2 text-left">Name</th>
                        <th class="px-3 py-2 text-left">Pack setup</th>
                        <th class="px-3 py-2 text-left">Visibility / options</th>
                        <th class="px-3 py-2 text-right">MRP / Price (₹)</th>
                        <th class="px-3 py-2 text-right">Stock</th>
                        <th class="px-3 py-2 text-center">Status</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-white dark:bg-gray-950">
                    <?php $__empty_1 = true; $__currentLoopData = $variants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $variant): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="px-3 py-2 align-top text-gray-800 dark:text-gray-100">
                                <?php echo e($variant->sku); ?>

                            </td>
                            <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300">
                                <?php echo e($variant->name ?: '—'); ?>

                            </td>
                            <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300">
                                <?php
                                    $packType = $variant->pack_type ?? 'quantity';
                                ?>
                                <?php if($packType === 'fixed_piece_pack'): ?>
                                    <?php echo e(rtrim(rtrim(number_format((float)($variant->pieces_per_pack ?? 0), 3), '0'), '.')); ?> pcs / pack
                                <?php elseif($packType === 'fixed_weight_pack'): ?>
                                    <?php echo e(rtrim(rtrim(number_format((float)($variant->product_weight ?? 0), 3), '0'), '.')); ?> kg / pack
                                <?php else: ?>
                                    Quantity pack
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300">
                                <?php
                                    $visibility = $variant->customer_visibility ?? 'all';
                                    $visibilityLabel = $visibility === 'b2b' ? 'B2B only' : ($visibility === 'b2c' ? 'B2C only' : 'B2C + B2B');
                                    $attributeLabels = collect($variant->attributeValues ?? [])->map(function ($row) {
                                        $attributeName = $row->attribute?->display_name ?? $row->attribute?->name ?? 'Option';
                                        $valueName = $row->name ?? $row->attributeValue?->name ?? '';
                                        return trim($attributeName . ': ' . $valueName);
                                    })->filter();
                                ?>
                                <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-[10px] text-gray-600 dark:text-gray-300">
                                    <?php echo e($visibilityLabel); ?>

                                </span>
                                <?php if($variant->inventory_can_repack ?? false): ?>
                                    <span class="ml-1 inline-flex items-center rounded-full bg-amber-50 dark:bg-amber-900/30 px-2 py-0.5 text-[10px] text-amber-700 dark:text-amber-300">
                                        Transform source
                                    </span>
                                <?php endif; ?>
                                <?php if($attributeLabels->isNotEmpty()): ?>
                                    <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                                        <?php echo e($attributeLabels->implode(' · ')); ?>

                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-2 align-top text-right text-gray-800 dark:text-gray-100">
                                <?php if(!empty($variant->mrp_price)): ?>
                                    <span class="block text-[10px] text-gray-500 dark:text-gray-400">MRP ₹<?php echo e(number_format($variant->mrp_price, 2)); ?></span>
                                <?php endif; ?>
                                ₹<?php echo e(number_format($variant->price, 2)); ?>

                            </td>
                            <td class="px-3 py-2 align-top text-right text-gray-700 dark:text-gray-300">
                                <?php echo e($variant->stock_quantity ?? '—'); ?>

                            </td>
                            <td class="px-3 py-2 align-top text-center">
                                <?php if($variant->is_active): ?>
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 px-2 py-0.5 text-[11px]">
                                        Active
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 px-2 py-0.5 text-[11px]">
                                        Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-2 align-top text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="<?php echo e(route('admin.variants.edit', $variant)); ?>"
                                       class="text-[11px] text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
                                        Edit
                                    </a>
                                    <form method="POST"
                                          action="<?php echo e(route('admin.variants.destroy', $variant)); ?>"
                                          onsubmit="return confirm('Delete this variant?');">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit"
                                                class="text-[11px] text-red-600 hover:text-red-700">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="8" class="px-3 py-6 text-center text-xs text-gray-500 dark:text-gray-400">
                                No variants for this product yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div>
            
            <?php if($variants instanceof \Illuminate\Contracts\Pagination\Paginator || $variants instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator): ?>
                <?php echo e($variants->links()); ?>

            <?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/products/variants/index.blade.php ENDPATH**/ ?>