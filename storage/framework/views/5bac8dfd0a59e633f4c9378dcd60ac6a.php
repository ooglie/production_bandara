<?php $__env->startSection('title', 'Reset password'); ?>

<?php $__env->startSection('content'); ?>
<div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-950 px-4">
    <div class="w-full max-w-sm space-y-4">
        <div class="text-center space-y-1">
            <h1 class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                Reset your password
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Choose a new password for your account.
            </p>
        </div>

        <?php if($errors->any()): ?>
            <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
                <ul class="list-disc list-inside space-y-0.5">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo e(route('password.update')); ?>" class="space-y-3">
            <?php echo csrf_field(); ?>

            <input type="hidden" name="token" value="<?php echo e($token); ?>">
            <input type="hidden" name="email" value="<?php echo e($email); ?>">

            <div class="space-y-1">
                <label class="block text-[11px] text-gray-600 dark:text-gray-300">
                    New password
                </label>
                <input type="password" name="password" required
                       class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-[11px] text-gray-900 dark:text-gray-50 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
            </div>

            <div class="space-y-1">
                <label class="block text-[11px] text-gray-600 dark:text-gray-300">
                    Confirm new password
                </label>
                <input type="password" name="password_confirmation" required
                       class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-[11px] text-gray-900 dark:text-gray-50 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
            </div>

            <button type="submit"
                    class="w-full inline-flex items-center justify-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                Reset password
            </button>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.guest', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/auth/reser-password.blade.php ENDPATH**/ ?>