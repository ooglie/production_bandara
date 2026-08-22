<?php $__env->startSection('title', 'Record payment'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-4xl mx-auto px-4 py-5 text-xs space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-1">
        <div>
            <h1 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-50">
                Record payment for selected invoices
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Review the invoices below and enter the actual amount received. The payment will be allocated automatically and invoice status will become part payment or paid based on the amount.
            </p>
        </div>
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

    
    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-3">
        <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50 mb-2">
            Selected invoices
        </p>
        <div class="overflow-x-auto">
            <table class="min-w-full text-[11px]">
                <thead class="bg-gray-50 dark:bg-gray-950/40">
                    <tr>
                        <th class="px-3 py-1.5 text-left font-medium text-gray-500 dark:text-gray-400">Invoice</th>
                        <th class="px-3 py-1.5 text-left font-medium text-gray-500 dark:text-gray-400">Customer</th>
                        <th class="px-3 py-1.5 text-right font-medium text-gray-500 dark:text-gray-400">Total</th>
                        <th class="px-3 py-1.5 text-right font-medium text-gray-500 dark:text-gray-400">Paid</th>
                        <th class="px-3 py-1.5 text-right font-medium text-gray-500 dark:text-gray-400">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <?php
                        $totalOutstanding = $totalOutstanding ?? 0;
                        $requestedStatus = $requestedStatus ?? 'part_payment';
                        $defaultAmount = $requestedStatus === 'paid' ? $totalOutstanding : null;
                    ?>
                    <?php $__currentLoopData = $invoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invoice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $paid    = $invoice->amount_paid ?? 0;
                            $balance = $invoice->balance_amount ?? ($invoice->grand_total - $paid);
                        ?>
                        <tr>
                            <td class="px-3 py-1.5">
                                <a href="<?php echo e(route('admin.invoices.show', $invoice)); ?>"
                                   class="text-gray-900 dark:text-gray-50 hover:underline">
                                    <?php echo e($invoice->invoice_number ?? ('INV-'.$invoice->id)); ?>

                                </a>
                                <div class="text-[10px] text-gray-400">#<?php echo e($invoice->id); ?></div>
                            </td>
                            <td class="px-3 py-1.5 text-gray-700 dark:text-gray-200">
                                <?php echo e($invoice->order?->user?->name ?? '—'); ?>

                                <?php if($invoice->order?->user?->email): ?>
                                    <div class="text-[10px] text-gray-400">
                                        <?php echo e($invoice->order->user->email); ?>

                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-1.5 text-right text-gray-900 dark:text-gray-50">
                                ₹<?php echo e(number_format($invoice->grand_total, 2)); ?>

                            </td>
                            <td class="px-3 py-1.5 text-right text-gray-700 dark:text-gray-200">
                                ₹<?php echo e(number_format($paid, 2)); ?>

                            </td>
                            <td class="px-3 py-1.5 text-right text-gray-900 dark:text-gray-50">
                                ₹<?php echo e(number_format($balance, 2)); ?>

                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-950/40">
                    <tr>
                        <td colspan="4" class="px-3 py-1.5 text-right text-[11px] font-medium text-gray-600 dark:text-gray-300">
                            Total outstanding
                        </td>
                        <td class="px-3 py-1.5 text-right text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                            ₹<?php echo e(number_format($totalOutstanding, 2)); ?>

                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    
    <form method="POST" action="<?php echo e(route('admin.invoices.record-payment')); ?>" class="space-y-3">
        <?php echo csrf_field(); ?>

        <?php $__currentLoopData = $invoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invoice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <input type="hidden" name="invoice_ids[]" value="<?php echo e($invoice->id); ?>">
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-3 space-y-3">
            <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                Payment details
            </p>

            <div class="grid sm:grid-cols-2 gap-3">
                <div class="space-y-1">
                    <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                        Payment method
                    </label>
                    <select name="payment_method" id="payment_method"
                            class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                           required>
                        <option value="cash">Cash</option>
                        <option value="cheque">Cheque</option>
                        <option value="bank_transfer">Bank transfer</option>
                        <option value="upi">UPI</option>
                        <option value="razorpay">Razorpay (manual)</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                        Amount received
                        <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.01" min="0.01"
                           max="<?php echo e(number_format($totalOutstanding, 2, '.', '')); ?>"
                           name="amount_received"
                           value="<?php echo e(old('amount_received', $defaultAmount)); ?>"
                           required
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                    <p class="text-[10px] text-gray-400 dark:text-gray-500">
                        Enter the actual amount received. Less than the balance will mark the invoice as part payment; full balance will mark it paid.
                    </p>
                </div>

                <div class="space-y-1">
                    <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                        Reference / UTR / POS ID
                    </label>
                    <input type="text"
                           name="reference"
                           value="<?php echo e(old('reference')); ?>"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>

                <div class="space-y-1">
                    <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                        Received date
                    </label>
                    <input type="date"
                           name="received_date"
                           value="<?php echo e(old('received_date', now()->toDateString())); ?>"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>
            </div>

            
            <div id="cheque-fields" class="grid sm:grid-cols-2 gap-3 hidden">
                <div class="space-y-1">
                    <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                        Cheque number
                    </label>
                    <input type="text"
                           name="cheque_number"
                           value="<?php echo e(old('cheque_number')); ?>"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>
                <div class="space-y-1">
                    <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                        Cheque date
                    </label>
                    <input type="date"
                           name="cheque_date"
                           value="<?php echo e(old('cheque_date')); ?>"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>
                <div class="space-y-1">
                    <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                        Bank name
                    </label>
                    <input type="text"
                           name="cheque_bank_name"
                           value="<?php echo e(old('cheque_bank_name')); ?>"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>
                <div class="space-y-1">
                    <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                        Branch
                    </label>
                    <input type="text"
                           name="cheque_branch_name"
                           value="<?php echo e(old('cheque_branch_name')); ?>"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>
            </div>

            <div class="space-y-1">
                <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                    Notes (optional)
                </label>
                <textarea name="notes"
                          rows="2"
                          class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"><?php echo e(old('notes')); ?></textarea>
            </div>
        </div>

        <div class="flex items-center justify-between gap-2 pt-1">
            <a href="<?php echo e(route('admin.invoices.index')); ?>"
               class="text-[11px] text-gray-500 dark:text-gray-400 hover:underline">
                ← Back to invoices
            </a>
            <button type="submit"
                    class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                Save payment and update invoices
            </button>
        </div>
    </form>
</div>

<script>
    (function () {
        const paymentMethodSelect = document.getElementById('payment_method');
        const chequeFields = document.getElementById('cheque-fields');

        if (paymentMethodSelect && chequeFields) {
            function toggleChequeFields() {
                if (paymentMethodSelect.value === 'cheque') {
                    chequeFields.classList.remove('hidden');
                } else {
                    chequeFields.classList.add('hidden');
                }
            }

            paymentMethodSelect.addEventListener('change', toggleChequeFields);
            toggleChequeFields();
        }
    })();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/invoices/record-payment.blade.php ENDPATH**/ ?>