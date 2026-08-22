<?php $__env->startSection('title', 'HSN / GST Codes'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 py-6 text-xs space-y-4">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">HSN / GST Codes</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Manage GST rates via HSN codes and link products to them.
            </p>
        </div>

        <a href="<?php echo e(route('admin.hsn-codes.create')); ?>"
           class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
            New HSN code
        </a>
    </div>

    <?php if(session('status')): ?>
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <?php if($errors->any()): ?>
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
            <ul class="list-disc pl-4 space-y-0.5">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="GET" action="<?php echo e(route('admin.hsn-codes.index')); ?>" class="flex items-center gap-2">
        <input type="text" name="q" value="<?php echo e($q ?? ''); ?>"
               class="w-full sm:w-80 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[11px]"
               placeholder="Search code or name...">
        <button class="rounded-full border border-gray-300 dark:border-gray-700 px-4 py-2 text-[11px] hover:bg-gray-100 dark:hover:bg-gray-800">
            Search
        </button>
    </form>

    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-[11px]">
                <thead class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800">
                <tr class="text-left text-gray-600 dark:text-gray-300">
                    <th class="px-3 py-2 font-medium">HSN Code</th>
                    <th class="px-3 py-2 font-medium">GST %</th>
                    <th class="px-3 py-2 font-medium">Name</th>
                    <th class="px-3 py-2 font-medium">Products</th>
                    <th class="px-3 py-2 font-medium">Active</th>
                    <th class="px-3 py-2 font-medium text-right">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <?php $__empty_1 = true; $__currentLoopData = $hsnCodes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="text-gray-700 dark:text-gray-200">
                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-gray-50"><?php echo e($row->code); ?></td>
                        <td class="px-3 py-2"><?php echo e(number_format((float)$row->gst_rate, 2)); ?>%</td>
                        <td class="px-3 py-2"><?php echo e($row->name); ?></td>
                        <td class="px-3 py-2"><?php echo e((int) $row->products_count); ?></td>
                        <td class="px-3 py-2">
                            <?php if($row->is_active): ?>
                                <span class="text-[10px] px-2 py-0.5 rounded-full border border-emerald-300 bg-emerald-50 text-emerald-800">Yes</span>
                            <?php else: ?>
                                <span class="text-[10px] px-2 py-0.5 rounded-full border border-gray-300 text-gray-600 dark:text-gray-300">No</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-2 text-right">
                            <div class="inline-flex items-center gap-2">
                                <a href="<?php echo e(route('admin.hsn-codes.edit', $row)); ?>"
                                   class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                    Edit
                                </a>

                                <form method="POST" action="<?php echo e(route('admin.hsn-codes.destroy', $row)); ?>"
                                      onsubmit="return confirm('Delete this HSN code?');">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit"
                                            class="text-[11px] px-3 py-1 rounded-full border border-red-300 text-red-700 hover:bg-red-50">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                            No HSN codes yet. Create your first one.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <?php echo e($hsnCodes->links()); ?>

    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/hsn-codes/index.blade.php ENDPATH**/ ?>