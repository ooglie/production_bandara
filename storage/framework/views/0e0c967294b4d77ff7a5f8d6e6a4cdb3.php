<?php $__env->startSection('title', 'Edit Page'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-5xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Edit Page</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Update static page content and metadata.
            </p>
        </div>
    </div>

    <?php echo $__env->make('admin.pages._form', [
        'action' => route('admin.pages.update', $page),
        'page' => $page,
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/pages/edit.blade.php ENDPATH**/ ?>