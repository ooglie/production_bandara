<?php $__env->startSection('title', $page->tr('meta_title') ?: $page->tr('title')); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-4xl mx-auto px-4 py-6 space-y-6 text-xs">
    <nav class="text-[11px] text-gray-500 dark:text-gray-400">
        <a href="<?php echo e(route('home')); ?>" class="hover:underline">Home</a>
        <span class="mx-1">/</span>
        <span class="text-gray-700 dark:text-gray-200"><?php echo e($page->tr('title')); ?></span>
    </nav>

    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 space-y-4">
        <div class="space-y-1">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">
                <?php echo e($page->tr('title')); ?>

            </h1>

            <?php if($page->tr('excerpt')): ?>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    <?php echo e($page->tr('excerpt')); ?>

                </p>
            <?php endif; ?>
        </div>

        <?php if($page->tr('content')): ?>
            <div class="prose prose-sm max-w-none text-gray-700 dark:text-gray-200 dark:prose-invert">
                <?php echo nl2br(e($page->tr('content'))); ?>

            </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.customer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/pages/show.blade.php ENDPATH**/ ?>