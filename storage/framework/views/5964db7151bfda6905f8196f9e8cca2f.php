<?php $__env->startSection('title', 'Edit address'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-3xl mx-auto px-4 py-6 space-y-4">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Edit address
            </h1>
        </div>
        <a href="<?php echo e(route('account.addresses.index')); ?>"
           class="text-[11px] text-gray-500 dark:text-gray-400 underline">
            Back to addresses
        </a>
    </div>

    <form method="POST" action="<?php echo e(route('account.addresses.update', $address)); ?>">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>
        <?php echo $__env->make('customer.addresses._form', ['address' => $address], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.customer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/customer/addresses/edit.blade.php ENDPATH**/ ?>