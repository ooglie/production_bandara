<?php $__env->startSection('title', 'Invoices'); ?>

<?php
    $availableMonths = $availableMonths ?? collect();
?>

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 py-5 text-xs space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-1">
        <div>
            <h1 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-50">
                Invoices
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Filter by customer, month and status. Choose “Record part payment” or “Record full payment” to enter the received amount.
            </p>
        </div>
        <?php if(\Illuminate\Support\Facades\Route::has('admin.invoice-payment-submissions.index')): ?>
            <a href="<?php echo e(route('admin.invoice-payment-submissions.index')); ?>"
               class="inline-flex items-center justify-center rounded-sm border border-amber-700 bg-amber-700 px-3 py-1.5 text-[11px] font-medium text-white hover:bg-amber-800">
                Review payment approvals
            </a>
        <?php endif; ?>
    </div>

    <?php if(session('status')): ?>
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <?php if($errors->any()): ?>
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
            <ul class="list-disc list-inside space-y-0.5">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
        <form method="GET" action="<?php echo e(route('admin.invoices.index')); ?>"
              class="flex flex-wrap items-center gap-2 text-[11px]">
            <span class="text-gray-600 dark:text-gray-300">
                Filter:
            </span>

            
            <select name="customer_id"
                    class="rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 px-2 py-1 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                <option value="">All customers</option>
                <?php $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($customer->id); ?>" <?php if(request('customer_id') == $customer->id): echo 'selected'; endif; ?>>
                        <?php echo e($customer->name); ?> <?php if($customer->email): ?> (<?php echo e($customer->email); ?>) <?php endif; ?>
                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>

            
            <select name="month"
                    class="rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 px-2 py-1 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                <option value="">All months</option>
                <?php $__currentLoopData = $availableMonths; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ym): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        try {
                            $label = \Carbon\Carbon::createFromFormat('Y-m', $ym)->format('M Y');
                        } catch (\Exception $e) {
                            $label = $ym;
                        }
                    ?>
                    <option value="<?php echo e($ym); ?>" <?php if(request('month') === $ym): echo 'selected'; endif; ?>>
                        <?php echo e($label); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>

            
            <select name="status"
                    class="rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 px-2 py-1 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                <option value="">All statuses</option>
                <option value="pending"      <?php if(request('status') === 'pending'): echo 'selected'; endif; ?>>Pending</option>
                <option value="due"          <?php if(request('status') === 'due'): echo 'selected'; endif; ?>>Due</option>
                <option value="part_payment" <?php if(request('status') === 'part_payment'): echo 'selected'; endif; ?>>Part payment</option>
                <option value="past_due"     <?php if(request('status') === 'past_due'): echo 'selected'; endif; ?>>Past due</option>
                <option value="paid"         <?php if(request('status') === 'paid'): echo 'selected'; endif; ?>>Paid</option>
            </select>

            <button type="submit"
                    class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                Apply
            </button>

            <?php if(request()->hasAny(['customer_id', 'month', 'status'])): ?>
                <a href="<?php echo e(route('admin.invoices.index')); ?>"
                   class="text-[10px] text-gray-400 hover:underline">
                    Reset
                </a>
            <?php endif; ?>
        </form>
    </div>

    
    
    <form method="POST" id="invoice-bulk-form" action="<?php echo e(route('admin.invoices.bulk-status')); ?>">
        <?php echo csrf_field(); ?>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('manage sales')): ?>
        
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-2">
            <div class="flex flex-wrap items-center gap-2 text-[11px]">
                <span class="text-gray-600 dark:text-gray-300">
                    Bulk action:
                </span>

                <select name="status"
                        id="bulk-status"
                        class="rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                    <option value="pending">Mark pending</option>
                    <option value="due">Mark due</option>
                    <option value="past_due">Mark past due</option>
                    <option value="part_payment">Record part payment</option>
                    <option value="paid">Record full payment</option>
                </select>

                <button type="submit"
                        id="bulk-submit"
                        class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                    Update selected
                </button>
            </div>

            <p class="text-[10px] text-gray-400">
                To enter an amount, select invoice(s), choose <strong>Record part payment</strong> or <strong>Record full payment</strong>, and click “Update selected”.
            </p>
        </div>
        <?php endif; ?>
        
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
            <table class="min-w-full text-[11px]">
                <thead class="bg-gray-50 dark:bg-gray-950/40">
                    <tr>
                        <th class="px-3 py-2 text-left">
                            <input type="checkbox" id="select-all-invoices"
                                   class="h-3 w-3 rounded border-gray-300 dark:border-gray-600">
                        </th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Invoice</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Customer</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Method</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Total</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Paid</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Balance</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Order</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <?php $__empty_1 = true; $__currentLoopData = $invoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invoice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $paid    = $invoice->amount_paid ?? 0;
                            $balance = $invoice->balance_amount ?? ($invoice->grand_total - $paid);
                        ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                            <td class="px-3 py-2">
                                <input type="checkbox"
                                       name="invoice_ids[]"
                                       value="<?php echo e($invoice->id); ?>"
                                       class="invoice-checkbox h-3 w-3 rounded border-gray-300 dark:border-gray-600">
                            </td>
                            <td class="px-3 py-2">
                                <a href="<?php echo e(route('admin.invoices.show', $invoice)); ?>"
                                   class="text-gray-900 dark:text-gray-50 hover:underline">
                                    <?php echo e($invoice->invoice_number ?? ('INV-'.$invoice->id)); ?>

                                </a>
                                <div class="text-[10px] text-gray-400">
                                    #<?php echo e($invoice->id); ?>

                                </div>
                            </td>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                                <?php echo e($invoice->order?->user?->name ?? '—'); ?>

                                <?php if($invoice->order?->user?->email): ?>
                                    <div class="text-[10px] text-gray-400">
                                        <?php echo e($invoice->order->user->email); ?>

                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px]
                                    <?php if($invoice->status === 'paid'): ?> border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200
                                    <?php elseif($invoice->status === 'part_payment'): ?> border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-800 dark:bg-sky-900/30 dark:text-sky-200
                                    <?php elseif($invoice->status === 'past_due'): ?> border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200
                                    <?php elseif($invoice->status === 'due'): ?> border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200
                                    <?php else: ?> border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400
                                    <?php endif; ?>">
                                    <?php echo e(ucfirst(str_replace('_', ' ', $invoice->status))); ?>

                                </span>
                            </td>
                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300">
                                <?php echo e($invoice->payment_method_label); ?>

                                <?php if($invoice->due_date && $invoice->is_pay_later): ?>
                                    <div class="text-[10px] text-gray-400">Due <?php echo e($invoice->due_date->format('d M Y')); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-2 text-right text-gray-900 dark:text-gray-50">
                                ₹<?php echo e(number_format($invoice->grand_total, 2)); ?>

                                <div class="text-[10px] text-gray-400">incl GST</div>
                            </td>
                            <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200">
                                ₹<?php echo e(number_format($paid, 2)); ?>

                            </td>
                            <td class="px-3 py-2 text-right text-gray-900 dark:text-gray-50">
                                ₹<?php echo e(number_format($balance, 2)); ?>

                            </td>
                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300">
                                <?php if($invoice->order): ?>
                                    <a href="<?php echo e(route('admin.orders.show', $invoice->order)); ?>"
                                       class="text-gray-700 dark:text-gray-200 hover:underline">
                                        <?php echo e($invoice->order->order_number); ?>

                                    </a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="9" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">
                                No invoices found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        
        <div class="mt-3">
            <?php echo e($invoices->links()); ?>

        </div>
    </form>
</div>

<script>
    (function () {
        const selectAll = document.getElementById('select-all-invoices');
        const checkboxes = document.querySelectorAll('.invoice-checkbox');
        const bulkForm = document.getElementById('invoice-bulk-form');
        const bulkStatus = document.getElementById('bulk-status');

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                checkboxes.forEach(cb => cb.checked = selectAll.checked);
            });
        }

        if (bulkForm && bulkStatus) {
            bulkForm.addEventListener('submit', function (e) {
                // Ensure at least one invoice selected
                const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
                if (!anyChecked) {
                    e.preventDefault();
                    alert('Please select at least one invoice.');
                    return;
                }

                const status = bulkStatus.value;

                // Payment-backed statuses go to the payment-entry form instead of changing status only
                if (status === 'paid' || status === 'part_payment') {
                    bulkForm.action = "<?php echo e(route('admin.invoices.payment-form')); ?>";
                } else {
                    bulkForm.action = "<?php echo e(route('admin.invoices.bulk-status')); ?>";
                }
            });
        }
    })();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/invoices/index.blade.php ENDPATH**/ ?>