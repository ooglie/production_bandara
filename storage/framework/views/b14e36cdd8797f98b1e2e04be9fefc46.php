<?php $__env->startSection('title', 'Expense categories'); ?>
<?php $__env->startSection('breadcrumb', 'Admin · Finance · Expense categories'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $inputClass = 'mt-1 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-950 dark:focus:ring-gray-500';
?>

<div class="space-y-4">
    <div>
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Expense categories</h1>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Manage the standard and custom categories available for business expenses.</p>
    </div>

    <?php echo $__env->make('admin.finance.partials.nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('admin.finance.partials.flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="grid gap-4 xl:grid-cols-[20rem_minmax(0,1fr)]">
        <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Add category</h2>
            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Custom categories appear alongside the standard list.</p>

            <form method="POST" action="<?php echo e(route('admin.finance.expense-categories.store')); ?>" class="mt-4 space-y-3">
                <?php echo csrf_field(); ?>
                <div>
                    <label for="new-category-name" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Name</label>
                    <input id="new-category-name" name="name" value="<?php echo e(old('name')); ?>" required maxlength="120" class="<?php echo e($inputClass); ?>">
                    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-[10px] text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label for="new-category-description" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Description</label>
                    <textarea id="new-category-description" name="description" rows="3" maxlength="2000" class="<?php echo e($inputClass); ?>"><?php echo e(old('description')); ?></textarea>
                    <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-[10px] text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label for="new-category-sort" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Sort order</label>
                    <input id="new-category-sort" type="number" name="sort_order" value="<?php echo e(old('sort_order', 1000)); ?>" min="0" class="<?php echo e($inputClass); ?>">
                    <?php $__errorArgs = ['sort_order'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-[10px] text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" <?php if((bool) old('is_active', true)): echo 'checked'; endif; ?> class="rounded border-gray-300 dark:border-gray-700">
                    Active
                </label>
                <button type="submit" class="w-full rounded border border-gray-900 bg-gray-900 px-3 py-1.5 text-[11px] font-medium text-white hover:bg-gray-800 dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">Create category</button>
            </form>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <div>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Manage categories</h2>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">System categories and categories already in use are deactivated instead of deleted.</p>
            </div>

            <div class="mt-4 space-y-3">
                <?php $__empty_1 = true; $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="rounded border border-gray-200 p-3 dark:border-gray-800">
                        <form method="POST" action="<?php echo e(route('admin.finance.expense-categories.update', $category)); ?>" class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_7rem_auto] lg:items-end">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('PUT'); ?>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="category-name-<?php echo e($category->id); ?>" class="block text-[10px] text-gray-500">Name</label>
                                    <input id="category-name-<?php echo e($category->id); ?>" name="name" value="<?php echo e($category->name); ?>" required maxlength="120" class="<?php echo e($inputClass); ?>">
                                </div>
                                <div>
                                    <label for="category-description-<?php echo e($category->id); ?>" class="block text-[10px] text-gray-500">Description</label>
                                    <input id="category-description-<?php echo e($category->id); ?>" name="description" value="<?php echo e($category->description); ?>" maxlength="2000" class="<?php echo e($inputClass); ?>">
                                </div>
                            </div>
                            <div>
                                <label for="category-sort-<?php echo e($category->id); ?>" class="block text-[10px] text-gray-500">Sort</label>
                                <input id="category-sort-<?php echo e($category->id); ?>" type="number" name="sort_order" value="<?php echo e($category->sort_order); ?>" min="0" class="<?php echo e($inputClass); ?>">
                            </div>
                            <div class="flex items-center gap-2 pb-0.5">
                                <input type="hidden" name="is_active" value="0">
                                <label class="flex items-center gap-1.5 text-[11px] text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" name="is_active" value="1" <?php if($category->is_active): echo 'checked'; endif; ?> class="rounded border-gray-300 dark:border-gray-700">
                                    Active
                                </label>
                                <button type="submit" class="rounded border border-gray-300 px-2.5 py-1.5 text-[11px] font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900">Save</button>
                            </div>
                        </form>

                        <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 pt-2 text-[10px] text-gray-500 dark:border-gray-800">
                            <span><?php echo e($category->is_system ? 'Standard category' : 'Custom category'); ?> · <?php echo e($category->expenses_count); ?> expense(s) · <?php echo e($category->recurring_templates_count); ?> recurring template(s)</span>
                            <form method="POST" action="<?php echo e(route('admin.finance.expense-categories.destroy', $category)); ?>" onsubmit="return confirm('Delete or deactivate this category?')">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="font-medium text-red-600 hover:underline dark:text-red-400"><?php echo e($category->is_system || $category->expenses_count || $category->recurring_templates_count ? 'Deactivate' : 'Delete'); ?></button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="rounded border border-dashed border-gray-300 px-3 py-8 text-center text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">No expense categories found.</div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/finance/expense-categories/index.blade.php ENDPATH**/ ?>