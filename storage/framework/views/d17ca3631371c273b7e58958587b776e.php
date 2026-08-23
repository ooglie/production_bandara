<?php $__env->startSection('title', 'My addresses'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Saved addresses
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Manage your shipping and billing addresses for faster checkout.
            </p>
        </div>
        
        <a href="<?php echo e(route('account.addresses.create')); ?>"
           class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
            Add new address
        </a>
    </div>

    <?php if($addresses->isEmpty()): ?>
        <div class="rounded border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 text-xs text-gray-500 dark:text-gray-400">
            You don’t have any saved addresses yet.
        </div>
    <?php else: ?>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <?php $__currentLoopData = $addresses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $address): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-900 px-4 py-3 text-xs flex flex-col gap-2">
                    <div class="flex items-start justify-between gap-2">
                        <div class="space-y-0.5">
                            <div class="font-medium text-gray-900 dark:text-gray-50">
                                <?php echo e($address->full_name); ?>

                            </div>
                            <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                <?php echo e($address->phone); ?>

                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <?php if($address->is_default_shipping): ?>
                                <span class="inline-flex items-center rounded-sm bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-2 py-0.5 text-[10px]">
                                    Default shipping
                                </span>
                            <?php endif; ?>
                            <?php if($address->is_default_billing): ?>
                                <span class="inline-flex items-center rounded-sm bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 px-2 py-0.5 text-[10px]">
                                    Default billing
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="text-[11px] text-gray-700 dark:text-gray-300 space-y-0.5">
                        <div><?php echo e($address->address_line1); ?></div>
                        <?php if($address->address_line2): ?>
                            <div><?php echo e($address->address_line2); ?></div>
                        <?php endif; ?>
                        <div>
                            <?php echo e($address->city); ?>,
                            <?php echo e($address->state); ?>

                            <?php if($address->pincode): ?>
                                – <?php echo e($address->pincode); ?>

                            <?php endif; ?>
                        </div>
                        <div><?php echo e($address->country); ?></div>
                        <?php if($address->gstin): ?>
                            <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                GSTIN: <?php echo e($address->gstin); ?>

                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <a href="<?php echo e(route('account.addresses.edit', $address)); ?>"
                           class="text-[11px] text-gray-600 dark:text-gray-300 underline">
                            Edit
                        </a>

                        <form method="POST" action="<?php echo e(route('account.addresses.destroy', $address)); ?>"
                              data-bandara-confirm="Remove this address?"
                              data-bandara-confirm-title="Delete address?"
                              data-bandara-confirm-text="Delete"
                              data-bandara-confirm-variant="danger">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit"
                                    class="text-[11px] text-red-600 hover:text-red-700">
                                Delete
                            </button>
                        </form>
                    </div>

                    <p class="text-[10px] text-gray-400 dark:text-gray-500">
                        This address will be available during checkout.
                    </p>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.customer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/customer/addresses/index.blade.php ENDPATH**/ ?>