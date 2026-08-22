<?php $__env->startSection('title', 'Add B2B Product'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-4xl mx-auto px-4 py-6 text-xs space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Add product to B2B catalog</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Customer: <?php echo e($user->name); ?>

            </p>
        </div>

        <a href="<?php echo e(route('admin.customers.b2b-products.index', $user)); ?>"
           class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
            Back
        </a>
    </div>

    <?php if($errors->any()): ?>
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
            <ul class="list-disc pl-4 space-y-0.5">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(route('admin.customers.b2b-products.store', $user)); ?>" class="space-y-3">
        <?php echo csrf_field(); ?>

        <?php echo $__env->make('admin.customers.b2b-products._form', ['user' => $user, 'products' => $products], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div class="flex items-center gap-2">
            <button type="submit"
                    class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                Save
            </button>
            <a href="<?php echo e(route('admin.customers.b2b-products.index', $user)); ?>"
               class="text-[11px] text-gray-500 dark:text-gray-400 hover:underline">
                Cancel
            </a>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/customers/b2b-products/create.blade.php ENDPATH**/ ?>