<?php $__env->startSection('title', 'Create variant option value'); ?>

<?php $__env->startSection('breadcrumb'); ?>
    Admin · Variant Options · <?php echo e($attribute->name); ?> · Option Values · Create
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="space-y-4">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
            Create option value – <?php echo e($attribute->name); ?>

        </h1>

        <?php echo $__env->make('admin.attributes.values._form', [
            'action'   => route('admin.attributes.values.store', $attribute),
            'attribute'=> $attribute,
            'value'    => null,
        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/attributes/values/create.blade.php ENDPATH**/ ?>