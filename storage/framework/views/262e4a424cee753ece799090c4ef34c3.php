<?php $__env->startSection('title', 'Ticket Categories'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-6xl mx-auto px-4 py-6 text-xs space-y-4">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Ticket Categories</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">Create and manage ticket categories.</p>
        </div>
        <a href="<?php echo e(route('admin.ticket-categories.create')); ?>"
           class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
            New category
        </a>
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

    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-3">
        <form method="GET" class="flex flex-col sm:flex-row gap-2 sm:items-center">
            <input type="text" name="q" value="<?php echo e($q); ?>"
                   placeholder="Search name or slug"
                   class="w-full sm:w-72 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">

            <button class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-4 py-1.5 text-[11px] hover:bg-gray-100 dark:hover:bg-gray-800">
                Search
            </button>

            <a href="<?php echo e(route('admin.ticket-categories.index')); ?>"
               class="text-[11px] text-gray-500 dark:text-gray-400 hover:underline">
                Reset
            </a>
        </form>
    </div>

    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-[11px]">
                <thead class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800">
                    <tr class="text-left text-gray-600 dark:text-gray-300">
                        <th class="px-3 py-2 font-medium">Name</th>
                        <th class="px-3 py-2 font-medium">Slug</th>
                        <th class="px-3 py-2 font-medium">Position</th>
                        <th class="px-3 py-2 font-medium">Active</th>
                        <th class="px-3 py-2 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <?php $__empty_1 = true; $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="px-3 py-2 text-gray-900 dark:text-gray-50 font-medium"><?php echo e($cat->name); ?></td>
                            <td class="px-3 py-2 text-gray-500 dark:text-gray-400"><?php echo e($cat->slug); ?></td>
                            <td class="px-3 py-2"><?php echo e($cat->position); ?></td>
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-2 py-0.5 text-[10px]">
                                    <?php echo e($cat->is_active ? 'Yes' : 'No'); ?>

                                </span>
                            </td>
                            <td class="px-3 py-2 text-right space-x-2">
                                <a href="<?php echo e(route('admin.ticket-categories.edit', $cat)); ?>"
                                   class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1 text-[10px] hover:bg-gray-100 dark:hover:bg-gray-800">
                                    Edit
                                </a>
                                <form method="POST" action="<?php echo e(route('admin.ticket-categories.destroy', $cat)); ?>" class="inline">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit"
                                            onclick="return confirm('Delete this category?')"
                                            class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1 text-[10px] hover:bg-gray-100 dark:hover:bg-gray-800">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                                No categories found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="px-3 py-3 border-t border-gray-200 dark:border-gray-800">
            <?php echo e($categories->links()); ?>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/ticket-categories/index.blade.php ENDPATH**/ ?>