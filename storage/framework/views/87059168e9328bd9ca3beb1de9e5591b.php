<?php $__env->startSection('title', 'Edit variant option group'); ?>

<?php $__env->startSection('breadcrumb', 'Admin · Variant Options · Edit'); ?>

<?php $__env->startSection('content'); ?>
    <div class="space-y-4">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
            Edit variant option group
        </h1>

        <?php echo $__env->make('admin.attributes._form', [
            'action'    => route('admin.attributes.update', $attribute),
            'attribute' => $attribute,
        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/attributes/edit.blade.php ENDPATH**/ ?>