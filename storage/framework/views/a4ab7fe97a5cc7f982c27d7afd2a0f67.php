<?php $__env->startSection('title', 'Create Collection'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">Create collection</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Build a curated product set for occasions, chef picks, or homepage campaigns.
            </p>
        </div>

        <a
            href="<?php echo e(route('admin.product-collections.index')); ?>"
            class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
        >
            Back
        </a>
    </div>

    <form method="POST" action="<?php echo e(route('admin.product-collections.store')); ?>" enctype="multipart/form-data" class="space-y-6">
        <?php echo csrf_field(); ?>
        <?php echo $__env->make('admin.product_collections._form', [
            'productCollection' => $productCollection,
            'products' => $products,
        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </form>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/product_collections/create.blade.php ENDPATH**/ ?>