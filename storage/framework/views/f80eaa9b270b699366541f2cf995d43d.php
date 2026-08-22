<?php $__env->startSection('body'); ?>

<?php if(auth()->check() && session()->has('impersonator_id') && \Illuminate\Support\Facades\Route::has('impersonation.stop')): ?>
    <div class="bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-100 text-[11px] px-4 py-2 flex items-center justify-between">
        <span>
            You are currently impersonating
            <strong><?php echo e(auth()->user()->name); ?></strong>.
        </span>
        <form method="POST" action="<?php echo e(route('impersonation.stop')); ?>">
            <?php echo csrf_field(); ?>
            <button
                type="submit"
                class="underline">
                Stop impersonating
            </button>
        </form>
    </div>
<?php endif; ?>
<?php echo $__env->make('partials.nav.customer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<div class="min-h-screen flex flex-col bg-gray-50 dark:bg-gray-950">
    
    <main class="flex-1 pt-0 md:pt-14">
        <?php echo $__env->make('partials.frontend.messages', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <?php echo $__env->yieldContent('content'); ?>
    </main>

    <?php echo $__env->make('partials.footer.customer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.base', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/layouts/guest.blade.php ENDPATH**/ ?>