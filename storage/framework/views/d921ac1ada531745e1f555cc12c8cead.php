<?php $__env->startSection('title', 'My invoices'); ?>

<?php $__env->startSection('content'); ?>
<?php
    use Illuminate\Support\Facades\Route;
?>
<div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                My invoices
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                View invoices for your orders. Payment may still be pending.
            </p>
        </div>
        <a href="<?php echo e(route('orders.index')); ?>"
           class="text-[11px] text-gray-500 dark:text-gray-400 underline">
            Back to orders
        </a>
    </div>

    <div class="border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-900 overflow-hidden">
        <table class="w-full text-xs">
            <thead class="bg-gray-50 dark:bg-gray-900/60 border-b border-gray-200 dark:border-gray-800">
                <tr class="text-left text-[11px] text-gray-500 dark:text-gray-400">
                    <th class="px-3 py-2.5">Invoice #</th>
                    <th class="px-3 py-2.5">Order #</th>
                    <th class="px-3 py-2.5">Date</th>
                    <th class="px-3 py-2.5">Payment</th>
                    <th class="px-3 py-2.5">Status</th>
                    <th class="px-3 py-2.5 text-right">Total</th>
                    <th class="px-3 py-2.5 text-right">Balance</th>
                    <th class="px-3 py-2.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $invoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invoice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $invoicePaidAmount = (float) ($invoice->amount_paid ?? 0);
                        $invoiceBalanceAmount = (float) ($invoice->balance_amount ?? max(0, ($invoice->grand_total ?? 0) - $invoicePaidAmount));
                    ?>
                    <tr class="border-t border-gray-100 dark:border-gray-800">
                        <td class="px-3 py-2 align-top">
                            <?php echo e($invoice->invoice_number); ?>

                        </td>
                        <td class="px-3 py-2 align-top">
                            <?php echo e($invoice->order->order_number ?? '—'); ?>

                        </td>
                        <td class="px-3 py-2 align-top">
                            <?php echo e(optional($invoice->invoice_date)->format('d M Y') ?? '—'); ?>

                        </td>
                        <td class="px-3 py-2 align-top text-[11px] text-gray-600 dark:text-gray-300">
                            <div><?php echo e($invoice->payment_method_label); ?></div>
                            <?php if($invoice->due_date && $invoice->is_pay_later): ?>
                                <div class="text-[10px] text-gray-400">Due <?php echo e($invoice->due_date->format('d M Y')); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-2 align-top">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px]
                                <?php if($invoice->status === 'paid'): ?>
                                    bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300
                                <?php elseif($invoice->status === 'past_due'): ?>
                                    bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300
                                <?php elseif($invoice->status === 'due'): ?>
                                    bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300
                                <?php else: ?>
                                    bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200
                                <?php endif; ?>
                            ">
                                <?php echo e(ucfirst(str_replace('_', ' ', $invoice->status))); ?>

                            </span>
                        </td>
                        <td class="px-3 py-2 align-top text-right">
                            ₹<?php echo e(number_format($invoice->grand_total, 2)); ?>

                            <div class="text-[10px] text-gray-400">incl GST</div>
                        </td>
                        <td class="px-3 py-2 align-top text-right">
                            ₹<?php echo e(number_format($invoiceBalanceAmount ?? 0, 2)); ?>

                        </td>
                        <td class="px-3 py-2 align-top text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="<?php echo e(route('invoices.show', $invoice)); ?>"
                                   class="text-[11px] text-gray-700 dark:text-gray-200 underline">
                                    View
                                </a>
                                <?php if(($invoiceBalanceAmount ?? 0) > 0.00001 && Route::has('invoices.pay.razorpay')): ?>
                                    <a href="<?php echo e(route('invoices.show', $invoice)); ?>"
                                       class="inline-flex items-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-2 py-0.5 text-[10px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                                        Pay / part pay
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="8" class="px-3 py-4 text-center text-[11px] text-gray-500 dark:text-gray-400">
                            You have no invoices yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="px-3 py-2 border-t border-gray-100 dark:border-gray-800">
            <?php echo e($invoices->links()); ?>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.customer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/customer/invoices/index.blade.php ENDPATH**/ ?>