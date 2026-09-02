<?php $__env->startSection('title', 'Invoice ' . $invoice->invoice_number); ?>

<?php $__env->startSection('content'); ?>
<?php
    $order = $invoice->order;
    $shipping = $order?->addresses?->firstWhere('type', 'shipping');
    $billing  = $order?->addresses?->firstWhere('type', 'billing');

    $adminText = function ($value, string $fallback = '—') use (&$adminText): string {
        if ($value instanceof \Illuminate\Support\Collection) {
            $value = $value->all();
        }

        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }

        if (is_null($value)) {
            return $fallback;
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            foreach (['en', 'value', 'name', 'label', 'title'] as $preferredKey) {
                if (array_key_exists($preferredKey, $value) && ! is_array($value[$preferredKey])) {
                    return $adminText($value[$preferredKey], $fallback);
                }
            }

            $parts = [];
            foreach ($value as $item) {
                $part = $adminText($item, '');
                if ($part !== '') {
                    $parts[] = $part;
                }
            }

            $parts = array_values(array_unique($parts));
            return count($parts) ? implode(', ', $parts) : $fallback;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed !== '' && in_array($trimmed[0], ['{', '['], true)) {
                $decoded = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $adminText($decoded, $fallback);
                }
            }

            return $trimmed !== '' ? $trimmed : $fallback;
        }

        if (is_scalar($value) || $value instanceof \Stringable) {
            $text = trim((string) $value);
            return $text !== '' ? $text : $fallback;
        }

        return $fallback;
    };

    $adminNumber = function ($value, float $fallback = 0.0) use ($adminText): float {
        if (is_array($value) || $value instanceof \Illuminate\Support\Collection) {
            $value = $adminText($value, '0');
        }

        return is_numeric($value) ? (float) $value : $fallback;
    };

    $adminMoney = fn ($value): string => number_format($adminNumber($value), 2);
    $adminInt = fn ($value): string => number_format((int) round($adminNumber($value)));
    $paidAmount = (float) ($invoice->amount_paid ?? 0);
    $balanceAmount = (float) ($invoice->balance_amount ?? max(0, ($invoice->grand_total ?? 0) - $paidAmount));
?>

<div class="max-w-6xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Invoice <?php echo e($adminText($invoice->invoice_number)); ?>

                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[12px]
                    <?php if($invoice->status === 'paid'): ?>
                        bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300
                    <?php elseif($invoice->status === 'part_payment'): ?>
                        bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300
                    <?php elseif($invoice->status === 'past_due'): ?>
                        bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300
                    <?php elseif($invoice->status === 'due'): ?>
                        bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300
                    <?php else: ?>
                        bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200
                    <?php endif; ?>
                ">
                    <?php echo e(ucfirst(str_replace('_', ' ', $adminText($invoice->status, '')))); ?>

                </span>
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Order #<?php echo e($adminText($order?->order_number ?? null)); ?> ·
                Customer: <?php echo e($adminText($order?->user?->name ?? null)); ?>

            </p>
        </div>
        <div class="flex items-center gap-2">
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('manage sales')): ?>
                <?php if($balanceAmount > 0.00001): ?>
                    <form method="POST" action="<?php echo e(route('admin.invoices.payment-form')); ?>">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="invoice_ids[]" value="<?php echo e($invoice->id); ?>">
                        <input type="hidden" name="status" value="part_payment">
                        <button type="submit"
                                class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                            Record payment
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
            <a href="<?php echo e(route('admin.invoices.index')); ?>"
               class="text-[11px] text-gray-500 dark:text-gray-400 underline">
                Back to invoices
            </a>
        </div>
    </div>

    <?php if(session('status')): ?>
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            <?php echo e($adminText(session('status'))); ?>

        </div>
    <?php endif; ?>

    <div class="grid gap-4 lg:grid-cols-[2fr,1.4fr]">
        
        <div class="space-y-4">
            <div class="border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-900 px-4 py-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <h2 class="text-[11px] font-semibold text-gray-800 dark:text-gray-100 mb-1">
                        Billing address
                    </h2>
                    <?php if($billing): ?>
                        <div class="space-y-0.5 text-[11px] text-gray-700 dark:text-gray-300">
                            <div><?php echo e($adminText($billing->full_name)); ?></div>
                            <div><?php echo e($adminText($billing->phone)); ?></div>
                            <div><?php echo e($adminText($billing->address_line1)); ?></div>
                            <?php if($billing->address_line2): ?>
                                <div><?php echo e($adminText($billing->address_line2)); ?></div>
                            <?php endif; ?>
                            <div><?php echo e($adminText($billing->city)); ?>, <?php echo e($adminText($billing->state)); ?> – <?php echo e($adminText($billing->pincode)); ?></div>
                            <div><?php echo e($adminText($billing->country)); ?></div>
                            <?php if($billing->gstin): ?>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400">GSTIN: <?php echo e($adminText($billing->gstin)); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-[11px] text-gray-400 dark:text-gray-500">No billing address stored.</p>
                    <?php endif; ?>
                </div>

                <div>
                    <h2 class="text-[11px] font-semibold text-gray-800 dark:text-gray-100 mb-1">
                        Shipping address
                    </h2>
                    <?php if($shipping): ?>
                        <div class="space-y-0.5 text-[11px] text-gray-700 dark:text-gray-300">
                            <div><?php echo e($adminText($shipping->full_name)); ?></div>
                            <div><?php echo e($adminText($shipping->phone)); ?></div>
                            <div><?php echo e($adminText($shipping->address_line1)); ?></div>
                            <?php if($shipping->address_line2): ?>
                                <div><?php echo e($adminText($shipping->address_line2)); ?></div>
                            <?php endif; ?>
                            <div><?php echo e($adminText($shipping->city)); ?>, <?php echo e($adminText($shipping->state)); ?> – <?php echo e($adminText($shipping->pincode)); ?></div>
                            <div><?php echo e($adminText($shipping->country)); ?></div>
                            <?php if($shipping->gstin): ?>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400">GSTIN: <?php echo e($adminText($shipping->gstin)); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-[11px] text-gray-400 dark:text-gray-500">No shipping address stored.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-900 px-4 py-4 space-y-3">
                <h2 class="text-[11px] font-semibold text-gray-800 dark:text-gray-100">
                    Line items
                </h2>

                <div class="space-y-2">
                    <?php $__empty_1 = true; $__currentLoopData = $invoice->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="flex items-start justify-between gap-3 border-b border-gray-100 dark:border-gray-800 pb-2 last:border-b-0">
                            <div class="flex-1">
                                <div class="text-[11px] text-gray-900 dark:text-gray-50">
                                    <?php echo e($adminText($item->description)); ?>

                                </div>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                    Qty: <?php echo e($adminText($item->quantity)); ?> × ₹<?php echo e($adminMoney($item->unit_price)); ?> <span class="text-[10px] text-gray-400">excl GST</span>
                                    <?php if((float) ($item->tax_amount ?? 0) > 0): ?>
                                        · GST ₹<?php echo e($adminMoney($item->tax_amount)); ?>

                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="text-right text-[11px] text-gray-900 dark:text-gray-50">
                                ₹<?php echo e($adminMoney($item->total)); ?>

                                <div class="text-[10px] font-normal text-gray-400">incl GST</div>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            No invoice items recorded.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        
        <div class="space-y-4">
            <div class="border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-900 px-4 py-4 space-y-3">
                <h2 class="text-[11px] font-semibold text-gray-800 dark:text-gray-100">
                    Invoice summary
                </h2>

                <dl class="space-y-1 text-[11px] text-gray-700 dark:text-gray-300">
                    <div class="flex justify-between">
                        <dt>Invoice #</dt>
                        <dd><?php echo e($adminText($invoice->invoice_number)); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Order #</dt>
                        <dd><?php echo e($adminText($order?->order_number ?? null)); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Invoice date</dt>
                        <dd><?php echo e(optional($invoice->invoice_date)->format('d M Y') ?? '—'); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Due date</dt>
                        <dd><?php echo e(optional($invoice->due_date)->format('d M Y') ?? '—'); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Payment method</dt>
                        <dd><?php echo e($invoice->payment_method_label); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Payment status</dt>
                        <dd><?php echo e($invoice->payment_status_label); ?></dd>
                    </div>
                    
                </dl>

                <div class="border-t border-gray-200 dark:border-gray-800 pt-3 space-y-1 text-[11px] text-gray-700 dark:text-gray-300">
                    <div class="flex justify-between">
                        <span>Subtotal <span class="text-[10px] text-gray-400">excl GST</span></span>
                        <span>₹<?php echo e($adminMoney($invoice->subtotal)); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Discount</span>
                        <span>- ₹<?php echo e($adminMoney($invoice->discount_total)); ?></span>
                    </div>
                    <?php if((float) ($invoice->delivery_fee ?? 0) > 0): ?>
                        <div class="flex justify-between">
                            <span>Delivery fee <span class="text-[10px] text-gray-400">excl GST</span></span>
                            <span>₹<?php echo e($adminMoney($invoice->delivery_fee)); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if((float) ($invoice->handling_fee ?? 0) > 0): ?>
                        <div class="flex justify-between">
                            <span>Cold-chain handling & packing <span class="text-[10px] text-gray-400">excl GST</span></span>
                            <span>₹<?php echo e($adminMoney($invoice->handling_fee)); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(($order?->gst_type ?? null) === 'intra_state'): ?>
                        <div class="flex justify-between">
                            <span>SGST</span>
                            <span>₹<?php echo e($adminMoney($order?->sgst_amount ?? 0)); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span>CGST</span>
                            <span>₹<?php echo e($adminMoney($order?->cgst_amount ?? 0)); ?></span>
                        </div>
                    <?php elseif(($order?->gst_type ?? null) === 'inter_state'): ?>
                        <div class="flex justify-between">
                            <span>IGST</span>
                            <span>₹<?php echo e($adminMoney($order?->igst_amount ?? 0)); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="flex justify-between">
                        <span>GST total</span>
                        <span>₹<?php echo e($adminMoney($invoice->tax_total)); ?></span>
                    </div>

                    <?php if((float) ($invoice->bandara_credit_discount_total ?? 0) > 0): ?>
                        <div class="flex justify-between text-emerald-700 dark:text-emerald-300">
                            <span>Bandara Credit</span>
                            <span>- ₹<?php echo e($adminMoney($invoice->bandara_credit_discount_total)); ?></span>
                        </div>
                        <?php if((int) ($invoice->bandara_credit_points_redeemed ?? 0) > 0): ?>
                            <div class="flex justify-between text-[10px] text-emerald-600 dark:text-emerald-300/80">
                                <span>Points redeemed</span>
                                <span><?php echo e($adminInt($invoice->bandara_credit_points_redeemed)); ?> pts</span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="flex justify-between font-semibold text-gray-900 dark:text-gray-50 pt-1">
                        <span>Grand total <span class="text-[10px] font-normal text-gray-400">incl GST</span></span>
                        <span>₹<?php echo e($adminMoney($invoice->grand_total)); ?></span>
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-800 mt-2 pt-2 space-y-1">
                        <div class="flex justify-between">
                            <span>Paid</span>
                            <span>₹<?php echo e($adminMoney($paidAmount)); ?></span>
                        </div>
                        <div class="flex justify-between font-medium text-gray-900 dark:text-gray-50">
                            <span>Balance due</span>
                            <span>₹<?php echo e($adminMoney($balanceAmount)); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            

            
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-3">
            

            <?php if($invoice->payments->isEmpty()): ?>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                    No payments recorded for this invoice.
                    
                </p>
            <?php else: ?>
             <div class="flex items-center justify-between mb-2">
                <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                    Payment history
                </p>
            </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-[11px]">
                        <thead class="bg-gray-50 dark:bg-gray-950/40">
                            <tr>
                                <th class="px-3 py-1.5 text-left font-medium text-gray-500 dark:text-gray-400">Date</th>
                                <th class="px-3 py-1.5 text-left font-medium text-gray-500 dark:text-gray-400">Method</th>
                                <th class="px-3 py-1.5 text-left font-medium text-gray-500 dark:text-gray-400">Reference</th>
                                <th class="px-3 py-1.5 text-right font-medium text-gray-500 dark:text-gray-400">Applied amount</th>
                                <th class="px-3 py-1.5 text-left font-medium text-gray-500 dark:text-gray-400">Recorded by</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <?php $__currentLoopData = $invoice->payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $applied = optional($payment->pivot)->amount_applied ?? 0;
                                ?>
                                <tr>
                                    <td class="px-3 py-1.5 text-gray-700 dark:text-gray-200">
                                        <?php echo e(optional($payment->paid_at ?? $payment->created_at)->format('d M Y, H:i')); ?>

                                    </td>
                                    <td class="px-3 py-1.5 text-gray-700 dark:text-gray-200">
                                        <?php echo e(ucfirst($adminText($payment->method, ''))); ?>

                                    </td>
                                    <td class="px-3 py-1.5 text-gray-600 dark:text-gray-300">
                                        <?php echo e($adminText($payment->reference ?? $payment->transaction_id ?? null)); ?>

                                    </td>
                                    <td class="px-3 py-1.5 text-right text-gray-900 dark:text-gray-50">
                                        ₹<?php echo e($adminMoney($applied)); ?>

                                    </td>
                                    <td class="px-3 py-1.5 text-gray-600 dark:text-gray-300">
                                        <?php echo e($adminText($payment->recordedBy?->name, 'System')); ?>

                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <?php if(($invoice->paymentSubmissions ?? collect())->isNotEmpty()): ?>
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        Customer-submitted payment details
                    </p>
                    <?php if(\Illuminate\Support\Facades\Route::has('admin.invoice-payment-submissions.index')): ?>
                        <a href="<?php echo e(route('admin.invoice-payment-submissions.index', ['status' => 'pending'])); ?>" class="text-[10px] underline text-gray-500">Review all pending</a>
                    <?php endif; ?>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-[11px]">
                        <thead class="bg-gray-50 dark:bg-gray-950/40">
                            <tr>
                                <th class="px-3 py-1.5 text-left font-medium text-gray-500 dark:text-gray-400">Submitted</th>
                                <th class="px-3 py-1.5 text-left font-medium text-gray-500 dark:text-gray-400">Method</th>
                                <th class="px-3 py-1.5 text-left font-medium text-gray-500 dark:text-gray-400">Reference</th>
                                <th class="px-3 py-1.5 text-right font-medium text-gray-500 dark:text-gray-400">Amount</th>
                                <th class="px-3 py-1.5 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <?php $__currentLoopData = $invoice->paymentSubmissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $submission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td class="px-3 py-1.5 text-gray-700 dark:text-gray-200"><?php echo e(optional($submission->created_at)->format('d M Y, H:i')); ?></td>
                                    <td class="px-3 py-1.5 text-gray-700 dark:text-gray-200"><?php echo e($submission->method_label); ?></td>
                                    <td class="px-3 py-1.5 text-gray-600 dark:text-gray-300"><?php echo e($adminText($submission->reference ?? $submission->cheque_number ?? null)); ?></td>
                                    <td class="px-3 py-1.5 text-right text-gray-900 dark:text-gray-50">₹<?php echo e($adminMoney($submission->amount)); ?></td>
                                    <td class="px-3 py-1.5 text-gray-600 dark:text-gray-300"><?php echo e($submission->status_label); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/invoices/show.blade.php ENDPATH**/ ?>