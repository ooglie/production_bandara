<?php $__env->startSection('title', 'Customer payment submissions'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 py-5 text-xs space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-50">
                Customer payment submissions
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Review bank transfer, UPI, cheque and cash payment details submitted by customers. Approving a submission records the payment against the invoice.
            </p>
        </div>
        <a href="<?php echo e(route('admin.invoices.index')); ?>"
           class="inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
            Back to invoices
        </a>
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

    <form method="GET" action="<?php echo e(route('admin.invoice-payment-submissions.index')); ?>" class="rounded border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-3 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-[10px] text-gray-500 dark:text-gray-400 mb-1">Status</label>
            <select name="status" class="rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px]">
                <option value="">Pending by default</option>
                <option value="pending" <?php if(request('status', 'pending') === 'pending'): echo 'selected'; endif; ?>>Pending</option>
                <option value="approved" <?php if(request('status') === 'approved'): echo 'selected'; endif; ?>>Approved</option>
                <option value="rejected" <?php if(request('status') === 'rejected'): echo 'selected'; endif; ?>>Rejected</option>
                <option value="cancelled" <?php if(request('status') === 'cancelled'): echo 'selected'; endif; ?>>Cancelled</option>
            </select>
        </div>
        <div>
            <label class="block text-[10px] text-gray-500 dark:text-gray-400 mb-1">Method</label>
            <select name="method" class="rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px]">
                <option value="">All</option>
                <option value="bank_transfer" <?php if(request('method') === 'bank_transfer'): echo 'selected'; endif; ?>>NEFT / RTGS / IMPS</option>
                <option value="upi" <?php if(request('method') === 'upi'): echo 'selected'; endif; ?>>UPI</option>
                <option value="cheque" <?php if(request('method') === 'cheque'): echo 'selected'; endif; ?>>Cheque</option>
                <option value="cash" <?php if(request('method') === 'cash'): echo 'selected'; endif; ?>>Cash</option>
                <option value="other" <?php if(request('method') === 'other'): echo 'selected'; endif; ?>>Other</option>
            </select>
        </div>
        <button type="submit" class="inline-flex items-center rounded-sm border border-gray-900 bg-gray-900 px-3 py-1.5 text-[11px] font-medium text-white hover:bg-gray-800">Filter</button>
    </form>

    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
        <table class="min-w-full text-xs">
            <thead class="bg-gray-50 dark:bg-gray-900/70 text-left text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">
                <tr>
                    <th class="px-3 py-2">Submitted</th>
                    <th class="px-3 py-2">Customer / invoice</th>
                    <th class="px-3 py-2">Method</th>
                    <th class="px-3 py-2 text-right">Amount</th>
                    <th class="px-3 py-2">Reference</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <?php $__empty_1 = true; $__currentLoopData = $submissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $submission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $invoice = $submission->invoice;
                        $order = $invoice?->order;
                        $customer = $submission->user ?? $order?->user;
                    ?>
                    <tr class="align-top">
                        <td class="px-3 py-3 whitespace-nowrap text-gray-500 dark:text-gray-400">
                            <?php echo e(optional($submission->created_at)->format('d M Y, H:i')); ?>

                            <div class="text-[10px]">Paid on <?php echo e(optional($submission->paid_on)->format('d M Y') ?? '—'); ?></div>
                        </td>
                        <td class="px-3 py-3">
                            <div class="font-medium text-gray-900 dark:text-gray-50"><?php echo e($customer?->name ?? '—'); ?></div>
                            <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                <?php if($invoice): ?>
                                    Invoice <a href="<?php echo e(route('admin.invoices.show', $invoice)); ?>" class="underline"><?php echo e($invoice->invoice_number); ?></a>
                                    · Balance ₹<?php echo e(number_format($invoice->balance_amount ?? 0, 2)); ?>

                                <?php else: ?>
                                    Invoice missing
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-3 py-3">
                            <?php echo e($submission->method_label); ?>

                            <?php if($submission->method === 'cheque'): ?>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                    Cheque <?php echo e($submission->cheque_number ?? '—'); ?> · <?php echo e(optional($submission->cheque_date)->format('d M Y') ?? '—'); ?>

                                </div>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400"><?php echo e($submission->cheque_bank_name); ?></div>
                            <?php elseif($submission->bank_name): ?>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400"><?php echo e($submission->bank_name); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-3 text-right font-medium text-gray-900 dark:text-gray-50">
                            ₹<?php echo e(number_format($submission->amount, 2)); ?>

                        </td>
                        <td class="px-3 py-3 text-gray-600 dark:text-gray-300">
                            <?php echo e($submission->reference ?? '—'); ?>

                            <?php if($submission->proof_path && route('admin.invoice-payment-submissions.proof', $submission)): ?>
                                <div>
                                    <a href="<?php echo e(route('admin.invoice-payment-submissions.proof', $submission)); ?>" class="text-[10px] underline" target="_blank">Download proof</a>
                                </div>
                            <?php endif; ?>
                            <?php if($submission->customer_note): ?>
                                <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400"><?php echo e($submission->customer_note); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[10px]
                                <?php if($submission->status === 'approved'): ?> bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300
                                <?php elseif($submission->status === 'rejected'): ?> bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300
                                <?php else: ?> bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300
                                <?php endif; ?>">
                                <?php echo e($submission->status_label); ?>

                            </span>
                            <?php if($submission->admin_note): ?>
                                <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400"><?php echo e($submission->admin_note); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-3 text-right">
                            <?php if($submission->status === 'pending'): ?>
                                <div class="space-y-2 min-w-[220px]">
                                    <form method="POST" action="<?php echo e(route('admin.invoice-payment-submissions.approve', $submission)); ?>" class="space-y-1">
                                        <?php echo csrf_field(); ?>
                                        <textarea name="admin_note" rows="2" placeholder="Approval note, optional" class="w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[10px]"></textarea>
                                        <button type="submit" class="w-full rounded-sm border border-emerald-700 bg-emerald-700 px-2 py-1 text-[10px] font-medium text-white hover:bg-emerald-800">Approve & apply</button>
                                    </form>
                                    <form method="POST" action="<?php echo e(route('admin.invoice-payment-submissions.reject', $submission)); ?>" class="space-y-1">
                                        <?php echo csrf_field(); ?>
                                        <textarea name="admin_note" rows="2" placeholder="Rejection reason" required class="w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[10px]"></textarea>
                                        <button type="submit" class="w-full rounded-sm border border-red-700 bg-red-700 px-2 py-1 text-[10px] font-medium text-white hover:bg-red-800">Reject</button>
                                    </form>
                                </div>
                            <?php elseif($submission->payment): ?>
                                <a href="<?php echo e(route('admin.payments.show', $submission->payment)); ?>" class="text-[10px] underline">View payment</a>
                            <?php else: ?>
                                <span class="text-[10px] text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" class="px-3 py-5 text-center text-[11px] text-gray-500 dark:text-gray-400">
                            No payment submissions found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div><?php echo e($submissions->links()); ?></div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/invoice_payment_submissions/index.blade.php ENDPATH**/ ?>