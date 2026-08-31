<?php $__env->startSection('title', 'Variant Options'); ?>

<?php $__env->startSection('breadcrumb', 'Admin · Variant Options'); ?>

<?php $__env->startSection('content'); ?>
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Variant Options
            </h1>

            <div class="flex items-center gap-2">
                <?php if(\Illuminate\Support\Facades\Route::has('admin.variant-option-values.index')): ?>
                    <a href="<?php echo e(route('admin.variant-option-values.index')); ?>"
                       class="inline-flex items-center px-3 py-1.5 text-xs rounded border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Manage option values
                    </a>
                <?php endif; ?>

                <a href="<?php echo e(route('admin.attributes.create')); ?>"
                   class="inline-flex items-center px-3 py-1.5 text-xs rounded border border-gray-300 dark:border-gray-700 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-200">
                    + New option group
                </a>
            </div>
        </div>

        <?php if(session('status')): ?>
            <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
                <?php echo e(session('status')); ?>

            </div>
        <?php endif; ?>
        <div class="rounded-lg border border-amber-200 bg-amber-50/70 px-4 py-3 text-[12px] text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100">
            Variant options are used only for products where customers choose a fixed option, such as
            <span class="font-semibold">Dimsum pack size</span> or
            <span class="font-semibold">Prawns size + pack weight</span>.
            Do not use them for physical pieces like pork slabs, salmon cuts, tuna, or cheese blocks.
        </div>


        <form method="GET" class="flex flex-wrap items-end gap-3 text-xs">
            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">
                    Search
                </label>
                <input
                    type="text"
                    name="q"
                    value="<?php echo e(request('q')); ?>"
                    placeholder="Name or slug"
                    class="mt-1 w-56 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
            </div>

            <div>
                <button
                    type="submit"
                    class="mt-5 inline-flex items-center px-3 py-1.5 rounded border border-gray-300 dark:border-gray-700 text-xs hover:bg-gray-100 dark:hover:bg-gray-800"
                >
                    Apply
                </button>
            </div>
        </form>

        <div class="overflow-x-auto border border-gray-200 dark:border-gray-800 rounded-lg text-xs">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr class="text-[11px] uppercase text-gray-500 dark:text-gray-400">
                        <th class="px-3 py-2 text-left">Name</th>
                        <th class="px-3 py-2 text-left">Slug</th>
                        <th class="px-3 py-2 text-left">Display</th>
                        <th class="px-3 py-2 text-center">Filterable</th>
                        <th class="px-3 py-2 text-right">Option values</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-white dark:bg-gray-950">
                    <?php $__empty_1 = true; $__currentLoopData = $attributes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attribute): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="px-3 py-2 align-top">
                                <div class="font-medium text-gray-900 dark:text-gray-50">
                                    <?php echo e($attribute->name); ?>

                                </div>
                                <?php if($attribute->display_name): ?>
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                        Label: <?php echo e($attribute->display_name); ?>

                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300">
                                <?php echo e($attribute->slug ?? '—'); ?>

                            </td>
                            <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300">
                                <?php echo e(ucfirst($attribute->frontend_type)); ?>

                            </td>
                            <td class="px-3 py-2 align-top text-center">
                                <?php if($attribute->is_filterable): ?>
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 px-2 py-0.5 text-[11px]">
                                        Yes
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 px-2 py-0.5 text-[11px]">
                                        No
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-2 align-top text-right text-gray-700 dark:text-gray-300">
                                <a href="<?php echo e(route('admin.attributes.values.index', $attribute)); ?>"
                                   class="text-[11px] text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
                                    <?php echo e($attribute->values_count); ?> option value(s)
                                </a>
                            </td>
                            <td class="px-3 py-2 align-top text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="<?php echo e(route('admin.attributes.edit', $attribute)); ?>"
                                       class="text-[11px] text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
                                        Edit
                                    </a>
                                    <form method="POST"
                                          action="<?php echo e(route('admin.attributes.destroy', $attribute)); ?>"
                                          onsubmit="return confirm('Delete this variant option group? This will also affect products/variants using it.');">
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
                            <td colspan="6" class="px-3 py-6 text-center text-xs text-gray-500 dark:text-gray-400">
                                No variant option groups defined yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div>
            <?php echo e($attributes->links()); ?>

        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/attributes/index.blade.php ENDPATH**/ ?>