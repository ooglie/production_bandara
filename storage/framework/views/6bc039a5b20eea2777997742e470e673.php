<?php $__env->startSection('title', 'Vendor outstanding summary'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $has = fn(string $r) => \Illuminate\Support\Facades\Route::has($r);
    $backUrl = $has('admin.vendor-invoices.index') ? route('admin.vendor-invoices.index') : url()->previous();
?>

<div class="max-w-6xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Adjusted payable by vendor</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Posted supplier credits and debits are included. Vendor credit shows amounts already paid above the adjusted payable.
            </p>
        </div>

        <a href="<?php echo e($backUrl); ?>"
           class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
            Back
        </a>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">Total adjusted outstanding</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-50">
                ₹<?php echo e(number_format((float)$totalOutstandingAllVendors, 2)); ?>

            </div>
        </div>
        <div class="rounded-2xl border border-amber-200 dark:border-amber-900 bg-amber-50/60 dark:bg-amber-950/20 p-4">
            <div class="text-[11px] text-amber-800 dark:text-amber-300">Vendor credit / refund receivable</div>
            <div class="mt-1 text-2xl font-semibold text-amber-900 dark:text-amber-200">
                ₹<?php echo e(number_format((float)($totalVendorCreditDue ?? 0), 2)); ?>

            </div>
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
        <table class="min-w-full text-[11px]">
            <thead class="bg-gray-50 dark:bg-gray-950/40">
            <tr>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Vendor</th>
                <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Invoices</th>
                <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Adjusted payable</th>
                <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Paid</th>
                <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Outstanding</th>
                <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Vendor credit</th>
                <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Action</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $vendorInvoicesUrl = \Illuminate\Support\Facades\Route::has('admin.vendor-invoices.index')
                        ? route('admin.vendor-invoices.index', ['vendor_id' => $r->vendor_id])
                        : '#';
                ?>
                <tr>
                    <td class="px-3 py-2 text-gray-900 dark:text-gray-50 font-medium"><?php echo e($r->vendor_name); ?></td>
                    <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200"><?php echo e((int)$r->inv_count); ?></td>
                    <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200">₹<?php echo e(number_format((float)$r->inv_total, 2)); ?></td>
                    <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200">₹<?php echo e(number_format((float)$r->paid_total, 2)); ?></td>
                    <td class="px-3 py-2 text-right">
                        <span class="font-semibold text-gray-900 dark:text-gray-50">₹<?php echo e(number_format((float)$r->outstanding_total, 2)); ?></span>
                    </td>
                    <td class="px-3 py-2 text-right">
                        <?php if((float)($r->vendor_credit_due ?? 0) > 0.005): ?>
                            <span class="font-semibold text-amber-800 dark:text-amber-300">₹<?php echo e(number_format((float)$r->vendor_credit_due, 2)); ?></span>
                        <?php else: ?>
                            <span class="text-gray-400">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-2 text-right">
                        <a href="<?php echo e($vendorInvoicesUrl); ?>"
                           class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                            View invoices
                        </a>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="7" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                        No outstanding payable or vendor credit found.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/vendor_invoices/outstanding.blade.php ENDPATH**/ ?>