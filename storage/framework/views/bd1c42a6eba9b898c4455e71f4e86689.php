<?php $__env->startSection('title', 'Pages'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Pages</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Manage About, Terms, Privacy and other static pages.
            </p>
        </div>

        <a href="<?php echo e(route('admin.pages.create')); ?>"
           class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
            New page
        </a>
    </div>

    <?php if(session('status')): ?>
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <form method="GET" class="flex flex-wrap items-center gap-2">
        <input type="text" name="q" value="<?php echo e(request('q')); ?>"
               placeholder="Search key / title"
               class="rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-1.5 text-[11px]">

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
            <a href="<?php echo e(route('admin.pages.index')); ?>"
               class="text-[11px] px-3 py-1.5 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                Clear
            </a>
        <?php endif; ?>
    </form>

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
        <table class="min-w-full text-[11px]">
            <thead class="bg-gray-50 dark:bg-gray-950/40">
                <tr>
                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Key</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Title</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Sort</th>
                    <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <?php $__empty_1 = true; $__currentLoopData = $pages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                            <?php echo e($page->key); ?>

                        </td>
                        <td class="px-3 py-2">
                            <div class="font-medium text-gray-900 dark:text-gray-50"><?php echo e($page->tr('title', 'en')); ?></div>
                            <div class="text-[10px] text-gray-400"><?php echo e($page->tr('slug', 'en')); ?></div>
                        </td>
                        <td class="px-3 py-2">
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px]
                                <?php echo e($page->is_active
                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200'
                                    : 'border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400'); ?>">
                                <?php echo e($page->is_active ? 'Active' : 'Inactive'); ?>

                            </span>
                        </td>
                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300">
                            <?php echo e($page->sort_order); ?>

                        </td>
                        <td class="px-3 py-2 text-right">
                            <div class="inline-flex items-center gap-2">
                                <a href="<?php echo e(route('admin.pages.edit', $page)); ?>"
                                   class="text-[11px] text-gray-700 dark:text-gray-200 underline">
                                    Edit
                                </a>

                                <form method="POST"
                                      action="<?php echo e(route('admin.pages.destroy', $page)); ?>"
                                      onsubmit="return confirm('Delete this page?');">
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
                            No pages found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php echo e($pages->links()); ?>

</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/pages/index.blade.php ENDPATH**/ ?>