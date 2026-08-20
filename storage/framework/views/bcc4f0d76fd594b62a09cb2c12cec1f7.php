<?php $__env->startSection('title', 'Add user'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $backUrl = \Illuminate\Support\Facades\Route::has('admin.users.index')
        ? route('admin.users.index')
        : url()->previous();
?>

<div class="max-w-xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Add user</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Create a new customer or staff member, and assign roles.
            </p>
        </div>

        <a href="<?php echo e($backUrl); ?>"
           class="rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-xs hover:bg-gray-50 dark:hover:bg-gray-800">
            Back to users
        </a>
    </div>

    <?php echo $__env->make('admin.users._form', [
        'action' => route('admin.users.store'),
        'mode' => 'create',
        'user' => null,
        'roles' => $roles ?? collect(),
        'customerType' => $customerType ?? null,
        'backUrl' => $backUrl,
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/users/create.blade.php ENDPATH**/ ?>