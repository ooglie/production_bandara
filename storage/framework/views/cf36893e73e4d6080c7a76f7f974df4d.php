<?php $__env->startSection('title', 'Variant option values'); ?>

<?php $__env->startSection('breadcrumb'); ?>
    Admin · Variant Options · <?php echo e($attribute->name); ?> · Option Values
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                    Option values – <?php echo e($attribute->name); ?>

                </h1>
                <?php if($attribute->display_name): ?>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">
                        Display name: <?php echo e($attribute->display_name); ?>

                    </p>
                <?php endif; ?>
            </div>

            <div class="flex items-center gap-2">
                <a href="<?php echo e(route('admin.attributes.index')); ?>"
                   class="text-[11px] text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">
                    Back to variant options
                </a>

                <?php if(\Illuminate\Support\Facades\Route::has('admin.variant-option-values.index')): ?>
                    <a href="<?php echo e(route('admin.variant-option-values.index', ['attribute_id' => $attribute->id])); ?>"
                       class="text-[11px] text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">
                        All values screen
                    </a>
                <?php endif; ?>

                <a href="<?php echo e(route('admin.attributes.values.create', $attribute)); ?>"
                   class="inline-flex items-center px-3 py-1.5 text-xs rounded border border-gray-300 dark:border-gray-700 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-200">
                    + New option value
                </a>
            </div>
        </div>

        <?php if(session('status')): ?>
            <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
                <?php echo e(session('status')); ?>

            </div>
        <?php endif; ?>

        <?php if(session('error')): ?>
            <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
                <?php echo e(session('error')); ?>

            </div>
        <?php endif; ?>

        <div class="overflow-x-auto border border-gray-200 dark:border-gray-800 rounded-lg text-xs">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr class="text-[11px] uppercase text-gray-500 dark:text-gray-400">
                        <th class="px-3 py-2 text-left">Name</th>
                        <th class="px-3 py-2 text-left">Value</th>
                        <th class="px-3 py-2 text-right">Position</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-white dark:bg-gray-950">
                    <?php $__empty_1 = true; $__currentLoopData = $values; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="px-3 py-2 align-top text-gray-800 dark:text-gray-100">
                                <?php echo e($value->name); ?>

                            </td>
                            <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300">
                                <?php echo e($value->value ?? '—'); ?>

                            </td>
                            <td class="px-3 py-2 align-top text-right text-gray-700 dark:text-gray-300">
                                <?php echo e($value->position ?? 0); ?>

                            </td>
                            <td class="px-3 py-2 align-top text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="<?php echo e(route('admin.values.edit', $value)); ?>"
                                       class="text-[11px] text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
                                        Edit
                                    </a>
                                    <form method="POST"
                                          action="<?php echo e(route('admin.values.destroy', $value)); ?>"
                                          onsubmit="return confirm('Delete this option value?');">
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
                            <td colspan="4" class="px-3 py-6 text-center text-xs text-gray-500 dark:text-gray-400">
                                No option values defined for this group.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div>
            <?php echo e($values->links()); ?>

        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/attributes/values/index.blade.php ENDPATH**/ ?>