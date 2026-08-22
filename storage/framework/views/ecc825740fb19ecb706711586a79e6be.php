<?php $__env->startSection('title', 'Add newsletter subscriber'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $has = fn(string $r) => \Illuminate\Support\Facades\Route::has($r);

    $indexUrl = $has('admin.newsletter-subscribers.index') ? route('admin.newsletter-subscribers.index') : url()->previous();
    $storeUrl = $has('admin.newsletter-subscribers.store') ? route('admin.newsletter-subscribers.store') : '#';
?>

<div class="max-w-3xl mx-auto px-4 py-6 space-y-4">

    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Add subscriber</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Add a subscriber manually.
            </p>
        </div>

        <a href="<?php echo e($indexUrl); ?>"
           class="rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-xs hover:bg-gray-50 dark:hover:bg-gray-800">
            Back
        </a>
    </div>

    <?php if(session('status')): ?>
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <?php echo $__env->make('admin.newsletter_subscribers._form', [
        'action' => $storeUrl,
        'subscriber' => null,
        'backUrl' => $indexUrl,
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/newsletter_subscribers/create.blade.php ENDPATH**/ ?>