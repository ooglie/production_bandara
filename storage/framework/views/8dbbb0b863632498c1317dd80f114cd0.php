<?php $__env->startSection('title', 'MOQ Overrides'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $fmt = fn($q) => rtrim(rtrim(number_format((float)$q, 2), '0'), '.');
?>

<div class="max-w-7xl mx-auto px-4 py-5 text-xs space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-50">
                MOQ Overrides
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
            <a href="<?php echo e(route('admin.b2b.prices.index', $user)); ?>"
               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                Manage prices
            </a>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
        <p class="text-[11px] text-gray-600 dark:text-gray-300">
            <span class="font-medium text-gray-900 dark:text-gray-50">Option B:</span>
            If there is <span class="font-medium">no row</span> for a product, MOQ defaults to <span class="font-medium">1</span>.
            Use this screen only when a product needs MOQ > 1 for this customer.
        </p>
    </div>

    <?php if(session('status')): ?>
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <?php if($errors->any()): ?>
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
            <ul class="list-disc pl-4 space-y-0.5">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    
    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3 space-y-3">
        <div class="font-semibold text-gray-900 dark:text-gray-50">
            Add MOQ override
        </div>

        <form method="POST" action="<?php echo e(route('admin.b2b.moq.store', $user)); ?>" class="grid gap-3 md:grid-cols-4 items-end">
            <?php echo csrf_field(); ?>

            <div class="md:col-span-2">
                <label class="block text-[11px] text-gray-600 dark:text-gray-300 mb-1">Product</label>
                <select name="product_id"
                        class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]">
                    <option value="">Select…</option>
                    <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($p->id); ?>" <?php if((int)old('product_id') === (int)$p->id): echo 'selected'; endif; ?>>
                            <?php echo e($p->name); ?> <?php if($p->sku): ?> (<?php echo e($p->sku); ?>) <?php endif; ?>
                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div>
                <label class="block text-[11px] text-gray-600 dark:text-gray-300 mb-1">MOQ</label>
                <input type="number" step="0.01" min="0.01" name="min_order_quantity"
                       value="<?php echo e(old('min_order_quantity', '1')); ?>"
                       class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]">
            </div>

            <div class="flex items-center justify-between gap-3">
                <label class="inline-flex items-center gap-2 text-[11px] text-gray-700 dark:text-gray-200">
                    <input type="checkbox" name="is_active" value="1" <?php if(old('is_active', true)): echo 'checked'; endif; ?>>
                    <span>Active</span>
                </label>

                <button class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                    Save
                </button>
            </div>
        </form>
    </div>

    
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
        <table class="min-w-full text-[11px]">
            <thead class="bg-gray-50 dark:bg-gray-950/40">
            <tr>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Product</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">MOQ</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Active</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Updated</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Actions</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            <?php $__empty_1 = true; $__currentLoopData = $overrides; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                    <td class="px-3 py-2">
                        <div class="font-medium text-gray-900 dark:text-gray-50">
                            <?php echo e($row->product?->name ?? 'Product'); ?>

                        </div>
                        <div class="text-[10px] text-gray-400">
                            Product ID: <?php echo e($row->product_id); ?>

                        </div>
                    </td>

                    <td class="px-3 py-2">
                        <form method="POST" action="<?php echo e(route('admin.b2b.moq.update', [$user, $row])); ?>" class="flex items-center gap-2">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('PUT'); ?>
                            <input type="number" step="0.01" min="0.01" name="min_order_quantity"
                                   value="<?php echo e(old('min_order_quantity', $fmt($row->min_order_quantity ?? 1))); ?>"
                                   class="w-24 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px]">
                    </td>

                    <td class="px-3 py-2">
                            <label class="inline-flex items-center gap-2 text-[11px]">
                                <input type="checkbox" name="is_active" value="1" <?php if($row->is_active): echo 'checked'; endif; ?>>
                                <span class="text-gray-600 dark:text-gray-300">Active</span>
                            </label>
                    </td>

                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">
                        <?php echo e(optional($row->updated_at)->format('d M Y, H:i')); ?>

                    </td>

                    <td class="px-3 py-2">
                            <button class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                Update
                            </button>
                        </form>

                        <form method="POST" action="<?php echo e(route('admin.b2b.moq.destroy', [$user, $row])); ?>"
                              class="inline"
                              onsubmit="return confirm('Remove MOQ override? Default MOQ will be 1.');">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button class="text-[11px] px-3 py-1 rounded-full border border-red-300 text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-200 dark:hover:bg-red-900/20">
                                Remove
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="5" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                        No MOQ overrides set. Default MOQ is 1 for all products.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        <?php echo e($overrides->links()); ?>

    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/b2b/moq/index.blade.php ENDPATH**/ ?>