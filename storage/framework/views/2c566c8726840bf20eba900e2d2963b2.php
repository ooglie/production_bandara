<?php $__env->startSection('title', 'Homepage Sections'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">Homepage</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Manage home page sections, copy, CTAs, images, linked records, scheduling, and display order.
            </p>
        </div>
        <a href="<?php echo e(route('home')); ?>" target="_blank" class="inline-flex items-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
            Preview homepage
        </a>
    </div>

    <?php if(session('success')): ?>
        <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-300">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-950/40">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Section</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Items</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Order</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <?php $__empty_1 = true; $__currentLoopData = $sections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $section): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php ($previewUrl = route('home') . '#home-section-' . $section->key); ?>
                        <tr>
                            <td class="px-4 py-4">
                                <div class="font-medium text-gray-900 dark:text-gray-50"><?php echo e($section->title ?: $section->key); ?></div>
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400"><?php echo e($section->key); ?></div>
                                <?php if($section->starts_at || $section->ends_at): ?>
                                    <div class="mt-1 text-[11px] text-gray-400 dark:text-gray-500">
                                        <?php if($section->starts_at): ?> Starts <?php echo e($section->starts_at->format('d M Y H:i')); ?> <?php endif; ?>
                                        <?php if($section->ends_at): ?> · Ends <?php echo e($section->ends_at->format('d M Y H:i')); ?> <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300"><?php echo e($section->type); ?></td>
                            <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300"><?php echo e($section->items_count); ?></td>
                            <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300"><?php echo e($section->sort_order); ?></td>
                            <td class="px-4 py-4">
                                <div class="flex flex-col gap-1">
                                    <?php if($section->is_active): ?>
                                        <span class="inline-flex w-max rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300">Active</span>
                                    <?php else: ?>
                                        <span class="inline-flex w-max rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">Inactive</span>
                                    <?php endif; ?>

                                    <?php if($section->isCurrentlyVisible()): ?>
                                        <span class="inline-flex w-max rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-medium text-sky-700 dark:bg-sky-900/30 dark:text-sky-300">Visible now</span>
                                    <?php elseif($section->is_active): ?>
                                        <span class="inline-flex w-max rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">Scheduled / outside window</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <form method="POST" action="<?php echo e(route('admin.home-sections.move-up', $section)); ?>"><?php echo csrf_field(); ?><button class="rounded-xl border border-gray-300 px-2.5 py-1.5 text-xs text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800" title="Move up">↑</button></form>
                                    <form method="POST" action="<?php echo e(route('admin.home-sections.move-down', $section)); ?>"><?php echo csrf_field(); ?><button class="rounded-xl border border-gray-300 px-2.5 py-1.5 text-xs text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800" title="Move down">↓</button></form>
                                    <form method="POST" action="<?php echo e(route('admin.home-sections.toggle', $section)); ?>"><?php echo csrf_field(); ?><button class="rounded-xl border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"><?php echo e($section->is_active ? 'Disable' : 'Enable'); ?></button></form>
                                    <a href="<?php echo e($previewUrl); ?>" target="_blank" class="inline-flex rounded-xl border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Preview</a>
                                    <a href="<?php echo e(route('admin.home-sections.edit', $section)); ?>" class="inline-flex rounded-xl bg-gray-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900">Edit</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                No home sections found. Run migrations to create the default home page records.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/home/sections/index.blade.php ENDPATH**/ ?>