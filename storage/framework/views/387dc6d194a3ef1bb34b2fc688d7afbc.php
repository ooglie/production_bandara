<?php $__env->startSection('title', 'New newsletter campaign'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-4xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                New newsletter campaign
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Compose a campaign and later send it to all active subscribers.
            </p>
        </div>
        <a href="<?php echo e(route('admin.newsletter-campaigns.index')); ?>"
           class="text-[11px] text-gray-500 dark:text-gray-400 underline">
            Back to campaigns
        </a>
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

    <form method="POST" action="<?php echo e(route('admin.newsletter-campaigns.store')); ?>">
        <?php echo $__env->make('admin.newsletter_campaigns._form', ['campaign' => new \App\Models\NewsletterCampaign()], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/newsletter_campaigns/create.blade.php ENDPATH**/ ?>