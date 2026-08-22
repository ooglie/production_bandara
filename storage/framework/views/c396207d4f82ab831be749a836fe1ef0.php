<?php $__env->startSection('title', 'Edit HSN Code'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-4xl mx-auto px-4 py-6 text-xs space-y-4">
    <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Edit HSN / GST Code</h1>

    <?php if($errors->any()): ?>
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
            <ul class="list-disc pl-4 space-y-0.5">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(route('admin.hsn-codes.update', $hsnCode)); ?>" class="space-y-3">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>

        <?php echo $__env->make('admin.hsn-codes._form', ['hsnCode' => $hsnCode], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div class="flex items-center justify-between">
            <a href="<?php echo e(route('admin.hsn-codes.index')); ?>" class="text-[11px] text-gray-500 hover:underline">Back</a>
            <button type="submit"
                    class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                Save changes
            </button>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/hsn-codes/edit.blade.php ENDPATH**/ ?>