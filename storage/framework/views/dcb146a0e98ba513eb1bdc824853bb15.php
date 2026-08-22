<?php $__env->startSection('title', 'Verify email'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-md mx-auto px-4 py-10">
    <div class="border border-gray-200 dark:border-gray-800 rounded-2xl bg-white dark:bg-gray-900 px-5 py-6 space-y-5">
        <div class="space-y-1">
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Verify your email address
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                We’ve sent a verification link to <span class="font-medium"><?php echo e(auth()->user()->email); ?></span>.
                Please verify your email before placing orders.
            </p>
        </div>

        <form method="POST" action="<?php echo e(route('verification.send')); ?>" class="space-y-3">
            <?php echo csrf_field(); ?>
            <button
                type="submit"
                class="w-full inline-flex items-center justify-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-xs font-medium hover:bg-gray-800 dark:hover:bg-gray-200"
            >
                Resend verification email
            </button>
        </form>

        <form method="POST" action="<?php echo e(route('logout')); ?>" class="space-y-3">
            <?php echo csrf_field(); ?>
            <button
                type="submit"
                class="w-full inline-flex items-center justify-center rounded-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 px-4 py-2 text-xs font-medium hover:bg-gray-50 dark:hover:bg-gray-800"
            >
                Log out
            </button>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.customer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/auth/verify-email.blade.php ENDPATH**/ ?>