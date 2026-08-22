<?php $__env->startSection('title', 'Create variant option group'); ?>

<?php $__env->startSection('breadcrumb', 'Admin · Variant Options · Create'); ?>

<?php $__env->startSection('content'); ?>
    <div class="space-y-4">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
            Create variant option group
        </h1>

        <?php echo $__env->make('admin.attributes._form', [
            'action'    => route('admin.attributes.store'),
            'attribute' => null,
        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/attributes/create.blade.php ENDPATH**/ ?>