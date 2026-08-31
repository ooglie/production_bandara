<?php $__env->startSection('title', 'Vendors'); ?>

<?php $__env->startSection('breadcrumb', 'Admin · Vendors'); ?>

<?php $__env->startSection('content'); ?>
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Vendors
            </h1>

            <a href="<?php echo e(route('admin.vendors.create')); ?>"
               class="inline-flex items-center px-3 py-1.5 text-xs rounded border border-gray-300 dark:border-gray-700 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-200">
                + New vendor
            </a>
        </div>

        <?php if(session('status')): ?>
            <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
                <?php echo e(session('status')); ?>

            </div>
        <?php endif; ?>

        
        <form method="GET" class="flex flex-wrap items-end gap-3 text-xs">
            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">
                    Search
                </label>
                <input
                    type="text"
                    name="q"
                    value="<?php echo e(request('q')); ?>"
                    placeholder="Name, code, email, phone"
                    class="mt-1 w-64 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">
                    Status
                </label>
                <select
                    name="status"
                    class="mt-1 w-32 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                    <option value="">All</option>
                    <option value="active" <?php if(request('status') === 'active'): echo 'selected'; endif; ?>>Active</option>
                    <option value="inactive" <?php if(request('status') === 'inactive'): echo 'selected'; endif; ?>>Inactive</option>
                </select>
            </div>

            <div>
                <button
                    type="submit"
                    class="mt-5 inline-flex items-center px-3 py-1.5 rounded border border-gray-300 dark:border-gray-700 text-xs hover:bg-gray-100 dark:hover:bg-gray-800"
                >
                    Apply
                </button>
            </div>
        </form>

        
        <div class="overflow-x-auto border border-gray-200 dark:border-gray-800 rounded-lg text-xs">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr class="text-[11px] uppercase text-gray-500 dark:text-gray-400">
                        <th class="px-3 py-2 text-left">Name</th>
                        
                        
                        <th class="px-3 py-2 text-left">Location</th>
                        <th class="px-3 py-2 text-left">GST</th>
                        <th class="px-3 py-2 text-center">Status</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-white dark:bg-gray-950">
                    <?php $__empty_1 = true; $__currentLoopData = $vendors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vendor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="px-3 py-2 align-top">
                                <div class="font-medium text-gray-900 dark:text-gray-50">
                                    <?php echo e($vendor->name); ?>

                                </div>
                                <?php if($vendor->notes): ?>
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400 line-clamp-1">
                                        <?php echo e($vendor->notes); ?>

                                    </div>
                                <?php endif; ?>
                            </td>

                            

                            

                            <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300">
                                <?php if($vendor->city || $vendor->state): ?>
                                    <div><?php echo e($vendor->city); ?><?php echo e($vendor->city && $vendor->state ? ', ' : ''); ?><?php echo e($vendor->state); ?></div>
                                <?php endif; ?>
                                <?php if($vendor->country || $vendor->pincode): ?>
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                        <?php echo e($vendor->country ?? ''); ?> <?php echo e($vendor->pincode ?? ''); ?>

                                    </div>
                                <?php endif; ?>
                            </td>

                            <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300">
                                <?php echo e($vendor->gst_number ?? '—'); ?>

                            </td>

                            <td class="px-3 py-2 align-top text-center">
                                <?php if($vendor->is_active): ?>
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 px-2 py-0.5 text-[11px]">
                                        Active
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 px-2 py-0.5 text-[11px]">
                                        Inactive
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="px-3 py-2 align-top text-right">
                                <div class="inline-flex items-center gap-2">
                                    
                                    <a href="<?php echo e(route('admin.vendors.show', $vendor)); ?>"
                                       class="text-[11px] text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
                                        View
                                    </a>
                                    <a href="<?php echo e(route('admin.vendors.edit', $vendor)); ?>"
                                       class="text-[11px] text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
                                        Edit
                                    </a>
                                    
                                    <form method="POST"
                                          action="<?php echo e(route('admin.vendors.destroy', $vendor)); ?>"
                                          onsubmit="return confirm('Delete this vendor?');"
                                    >
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit"
                                                class="text-[11px] text-red-600 hover:text-red-700">
                                            Delete
                                        </button>
                                    </form><br>
                                    
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="px-3 py-6 text-center text-xs text-gray-500 dark:text-gray-400">
                                No vendors found.
                                <a href="<?php echo e(route('admin.vendors.create')); ?>" class="underline">
                                    Create the first one
                                </a>.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div>
            <?php echo e($vendors->links()); ?>

        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/vendors/index.blade.php ENDPATH**/ ?>