<?php $__env->startSection('title', 'Recipes'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Recipes</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Manage reusable recipes and link them to products.
            </p>
        </div>

        <a href="<?php echo e(route('admin.recipes.create', request()->filled('product_id') ? ['product_id' => request('product_id')] : [])); ?>"
           class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
            New recipe
        </a>
    </div>

    <?php if(session('status')): ?>
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <form method="GET" class="flex flex-wrap items-center gap-2">
        <input type="text" name="q" value="<?php echo e(request('q')); ?>"
               placeholder="Search English title"
               class="rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-1.5 text-[11px]">

        <select name="product_id"
                class="rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-1.5 text-[11px]">
            <option value="">All products</option>
            <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($p->id); ?>" <?php if((int)request('product_id') === (int)$p->id): echo 'selected'; endif; ?>>
                    <?php echo e($p->name); ?>

                </option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>

        <select name="status"
                class="rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-1.5 text-[11px]">
            <option value="">All</option>
            <option value="active" <?php if(request('status') === 'active'): echo 'selected'; endif; ?>>Active</option>
            <option value="inactive" <?php if(request('status') === 'inactive'): echo 'selected'; endif; ?>>Inactive</option>
        </select>

        <button class="text-[11px] px-3 py-1.5 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
            Apply
        </button>

        <?php if(request()->query()): ?>
            <a href="<?php echo e(route('admin.recipes.index')); ?>"
               class="text-[11px] px-3 py-1.5 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                Clear
            </a>
        <?php endif; ?>
    </form>

    <?php if($selectedProduct): ?>
        <div class="rounded border border-sky-300 bg-sky-50 px-3 py-2 text-[11px] text-sky-800">
            Showing recipes linked to: <strong><?php echo e($selectedProduct->name); ?></strong>
        </div>
    <?php endif; ?>

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
        <table class="min-w-full text-[11px]">
            <thead class="bg-gray-50 dark:bg-gray-950/40">
                <tr>
                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Title</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Products</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Sort</th>
                    <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <?php $__empty_1 = true; $__currentLoopData = $recipes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $recipe): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="px-3 py-2">
                            <div class="font-medium text-gray-900 dark:text-gray-50">
                                <?php echo e($recipe->tr('title', 'en')); ?>

                            </div>
                            <div class="text-[10px] text-gray-400">
                                <?php echo e($recipe->tr('slug', 'en')); ?>

                            </div>
                        </td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                            <?php echo e($recipe->products_count); ?>

                        </td>
                        <td class="px-3 py-2">
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px]
                                <?php echo e($recipe->is_active
                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200'
                                    : 'border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400'); ?>">
                                <?php echo e($recipe->is_active ? 'Active' : 'Inactive'); ?>

                            </span>
                        </td>
                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300">
                            <?php echo e($recipe->sort_order); ?>

                        </td>
                        <td class="px-3 py-2 text-right">
                            <div class="inline-flex items-center gap-2">
                                <a href="<?php echo e(route('admin.recipes.edit', $recipe)); ?>"
                                   class="text-[11px] text-gray-700 dark:text-gray-200 underline">
                                    Edit
                                </a>

                                <form method="POST"
                                      action="<?php echo e(route('admin.recipes.destroy', $recipe)); ?>"
                                      onsubmit="return confirm('Delete this recipe?');">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit"
                                            class="text-[11px] text-red-600 dark:text-red-400 underline">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="5" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                            No recipes found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php echo e($recipes->links()); ?>

</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/recipes/index.blade.php ENDPATH**/ ?>