<?php $__env->startSection('title', 'Edit coupon'); ?>

<?php $__env->startSection('breadcrumb', 'Admin · Coupons · Edit'); ?>

<?php $__env->startSection('content'); ?>
    <div class="space-y-4">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
            Edit coupon
        </h1>

        <?php echo $__env->make('admin.coupons._form', [
            'action' => route('admin.coupons.update', $coupon),
            'coupon' => $coupon,
        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/coupons/edit.blade.php ENDPATH**/ ?>