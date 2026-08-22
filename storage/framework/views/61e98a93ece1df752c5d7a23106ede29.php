<?php $__env->startSection('title', 'Upload product images'); ?>

<?php $__env->startSection('breadcrumb'); ?>
    Admin · Products · <?php echo e($product->name); ?> · Images · Upload
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="space-y-4">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
            Upload images – <?php echo e($product->name); ?>

        </h1>

        <?php echo $__env->make('admin.products.images._form', [
            'action' => route('admin.products.images.store', $product),
            'product'=> $product,
            'image'  => null,
        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/products/images/create.blade.php ENDPATH**/ ?>