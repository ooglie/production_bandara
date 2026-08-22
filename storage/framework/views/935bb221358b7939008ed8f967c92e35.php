<?php $__env->startSection('title', 'Bulk vendor payment'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $has = fn($r) => \Illuminate\Support\Facades\Route::has($r);
    $storeUrl = $has('admin.vendor-payments.store') ? route('admin.vendor-payments.store') : '#';
    $backUrl  = $has('admin.vendor-invoices.index') ? route('admin.vendor-invoices.index') : url()->previous();
    $vendorName = $selectedVendor?->name ?? 'Vendor';
?>

<div class="max-w-6xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Bulk payment</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Paying invoices for <span class="font-semibold"><?php echo e($vendorName); ?></span>. Each default amount equals the adjusted outstanding after posted supplier credits and debits.
            </p>
        </div>

        <a href="<?php echo e($backUrl); ?>"
           class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
            Back
        </a>
    </div>

    <?php if($errors->any()): ?>
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800 dark:border-red-800 dark:bg-red-950/30 dark:text-red-200">
            <ul class="list-disc pl-4 space-y-0.5">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e($storeUrl); ?>" class="space-y-4">
        <?php echo csrf_field(); ?>

        <input type="hidden" name="vendor_id" value="<?php echo e($selectedVendor?->id); ?>">
        <?php $__currentLoopData = $invoiceIds; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <input type="hidden" name="invoice_ids[]" value="<?php echo e($id); ?>">
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-3">
            <div class="grid gap-3 md:grid-cols-3">
                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">Payment date</label>
                    <input type="date" name="payment_date" value="<?php echo e(old('payment_date', now()->format('Y-m-d'))); ?>" required
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs">
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">Payment method</label>
                    <input type="text" name="payment_method" value="<?php echo e(old('payment_method')); ?>"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs"
                           placeholder="NEFT / RTGS / IMPS / Cheque">
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">Reference</label>
                    <input type="text" name="reference_number" value="<?php echo e(old('reference_number')); ?>"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs"
                           placeholder="UTR / Cheque number">
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                <input type="text" name="notes" value="<?php echo e(old('notes')); ?>"
                       class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs">
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
            <table class="min-w-full text-[11px]">
                <thead class="bg-gray-50 dark:bg-gray-950/40">
                <tr>
                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Invoice</th>
                    <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Original</th>
                    <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Adjustments</th>
                    <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Adjusted payable</th>
                    <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Paid</th>
                    <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Outstanding</th>
                    <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Pay now</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $inv = $r['invoice'];
                        $out = (float)$r['outstanding'];
                        $defaultPay = old('amounts.' . $inv->id, $r['default_pay']);
                    ?>
                    <tr>
                        <td class="px-3 py-2">
                            <div class="text-gray-900 dark:text-gray-50 font-medium"><?php echo e($inv->invoice_number ?? ('#'.$inv->id)); ?></div>
                            <div class="text-[10px] text-gray-400">#<?php echo e($inv->id); ?></div>
                        </td>
                        <td class="px-3 py-2 text-right">₹<?php echo e(number_format((float)$r['original_total'], 2)); ?></td>
                        <td class="px-3 py-2 text-right <?php echo e((float)$r['adjustment_total'] < 0 ? 'text-emerald-700 dark:text-emerald-300' : ''); ?>">
                            <?php echo e((float)$r['adjustment_total'] >= 0 ? '+' : ''); ?>₹<?php echo e(number_format((float)$r['adjustment_total'], 2)); ?>

                        </td>
                        <td class="px-3 py-2 text-right font-medium">₹<?php echo e(number_format((float)$r['total'], 2)); ?></td>
                        <td class="px-3 py-2 text-right">₹<?php echo e(number_format((float)$r['paid'], 2)); ?></td>
                        <td class="px-3 py-2 text-right font-medium">₹<?php echo e(number_format($out, 2)); ?></td>
                        <td class="px-3 py-2 text-right">
                            <input type="number" step="0.01" min="0" max="<?php echo e(number_format($out, 2, '.', '')); ?>"
                                   name="amounts[<?php echo e($inv->id); ?>]"
                                   value="<?php echo e($defaultPay); ?>"
                                   class="w-28 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-right">
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">
                Enter 0 to skip an invoice. A payment cannot exceed its adjusted outstanding amount.
            </div>

            <button type="submit"
                    class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                Record bulk payment
            </button>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/vendor_payments/bulk_create.blade.php ENDPATH**/ ?>