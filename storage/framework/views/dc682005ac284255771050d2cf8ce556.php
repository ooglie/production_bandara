<?php $__env->startSection('title', 'Edit vendor'); ?>

<?php $__env->startSection('breadcrumb', 'Admin · Vendors · Edit'); ?>

<?php $__env->startSection('content'); ?>
    <div class="space-y-4">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
            Edit vendor
        </h1>

        <?php echo $__env->make('admin.vendors._form', [
            'action' => route('admin.vendors.update', $vendor),
            'vendor' => $vendor,
        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/vendors/edit.blade.php ENDPATH**/ ?>