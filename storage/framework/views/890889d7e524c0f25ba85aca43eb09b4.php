<?php $__env->startSection('title', 'Add B2C Customer'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $has = fn($r) => \Illuminate\Support\Facades\Route::has($r);

    $storeUrl =
        $has('admin.customers.b2c.store') ? route('admin.customers.b2c.store')
        : ($has('admin.users.store') ? route('admin.users.store') : '#');

    $backUrl =
        $has('admin.customers.b2c.index') ? route('admin.customers.b2c.index')
        : ($has('admin.users.index') ? route('admin.users.index', ['customer_type' => 'b2c']) : url()->previous());
?>

<div class="max-w-2xl mx-auto px-4 py-6 space-y-4">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Add B2C Customer</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Creates a retail customer. Role is set to <strong>Customer</strong>.
            </p>
        </div>

        <a href="<?php echo e($backUrl); ?>"
           class="rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-xs hover:bg-gray-50 dark:hover:bg-gray-800">
            Back
        </a>
    </div>

    <?php echo $__env->make('admin.customers.b2c._form', [
        'action' => $storeUrl,
        'mode' => 'create',
        'user' => null,
        'backUrl' => $backUrl,
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/customers/b2c/create.blade.php ENDPATH**/ ?>