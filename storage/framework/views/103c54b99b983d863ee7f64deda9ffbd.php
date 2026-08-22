<?php $__env->startSection('title', 'Invoice ' . $invoice->invoice_number); ?>

<?php $__env->startSection('content'); ?>
<?php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $order = $invoice->order;
    $isB2BInvoiceCustomer = (auth()->user()?->customer_type ?? 'b2c') === 'b2b';
    $shipping = $order?->addresses?->firstWhere('type', 'shipping');
    $billing  = $order?->addresses?->firstWhere('type', 'billing');

    $invoiceStatusMeta = function (?string $status) {
        $status = strtolower((string) $status);

        return match ($status) {
            'paid' => [
                'label' => 'Paid',
                'class' => 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-800',
            ],
            'past_due' => [
                'label' => 'Past due',
                'class' => 'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/40 dark:text-red-300 dark:border-red-800',
            ],
            'due' => [
                'label' => 'Due',
                'class' => 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:border-amber-800',
            ],
            'pending' => [
                'label' => 'Pending',
                'class' => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700',
            ],
            default => [
                'label' => Str::headline(str_replace('_', ' ', $status ?: 'Unknown')),
                'class' => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700',
            ],
        };
    };

    $orderStatusMeta = function (?string $status) {
        $status = strtolower((string) $status);

        return match ($status) {
            'processing' => [
                'label' => 'Processing',
                'class' => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-900/30 dark:text-sky-300 dark:border-sky-800',
            ],
            'shipped' => [
                'label' => 'Shipped',
                'class' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800',
            ],
            'delivered' => [
                'label' => 'Delivered',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800',
            ],
            'cancelled' => [
                'label' => 'Cancelled',
                'class' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800',
            ],
            default => [
                'label' => Str::headline($status ?: 'Unknown'),
                'class' => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700',
            ],
        };
    };

    $invoiceStatus = $invoiceStatusMeta($invoice->status ?? null);
    $orderStatus = $order ? $orderStatusMeta($order->status ?? null) : null;
    $paidAmount = (float) ($invoice->amount_paid ?? 0);
    $balanceAmount = (float) ($invoice->balance_amount ?? max(0, ($invoice->grand_total ?? 0) - $paidAmount));

    $pdfUrl = null;
    if (!empty($invoice->pdf_path)) {
        $pdfPath = trim((string) $invoice->pdf_path);

        if (Str::startsWith($pdfPath, ['http://', 'https://'])) {
            $pdfUrl = $pdfPath;
        } elseif (Str::startsWith($pdfPath, '/storage/')) {
            $pdfUrl = $pdfPath;
        } elseif (Str::startsWith($pdfPath, 'storage/')) {
            $pdfUrl = '/' . ltrim($pdfPath, '/');
        } elseif (Str::startsWith($pdfPath, 'storage/app/public/')) {
            $pdfUrl = '/storage/' . ltrim(Str::after($pdfPath, 'storage/app/public/'), '/');
        } else {
            $pdfUrl = Storage::disk('public')->url($pdfPath);
        }
    }
?>

<div class="max-w-6xl mx-auto px-4 py-6 space-y-4 text-xs">    <?php if($errors->any()): ?>
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
            <ul class="list-disc list-inside space-y-0.5">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">
                    Invoice <?php echo e($invoice->invoice_number); ?>

                </h1>

                <span class="inline-flex items-center rounded-sm border px-2 py-0.5 text-[10px] font-medium <?php echo e($invoiceStatus['class']); ?>">
                    <?php echo e($invoiceStatus['label']); ?>

                </span>
            </div>

            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                Order #<?php echo e($order->order_number ?? '—'); ?>

            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="<?php echo e(route('invoices.index')); ?>"
               class="inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                Back to invoices
            </a>

            <?php if($order && Route::has('orders.show')): ?>
                <a href="<?php echo e(route('orders.show', $order)); ?>"
                   class="inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                    View order
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-[2fr,1.4fr]">
        <div class="space-y-4">
            
            <div class="border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-900 px-4 py-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <h2 class="text-[11px] font-semibold text-gray-800 dark:text-gray-100 mb-2 uppercase tracking-wide">
                        Billing address
                    </h2>

                    <?php if($billing): ?>
                        <div class="space-y-0.5 text-[11px] text-gray-700 dark:text-gray-300">
                            <div><?php echo e($billing->full_name); ?></div>
                            <div><?php echo e($billing->phone); ?></div>
                            <div><?php echo e($billing->address_line1); ?></div>
                            <?php if($billing->address_line2): ?>
                                <div><?php echo e($billing->address_line2); ?></div>
                            <?php endif; ?>
                            <div><?php echo e($billing->city); ?>, <?php echo e($billing->state); ?> – <?php echo e($billing->pincode); ?></div>
                            <div><?php echo e($billing->country); ?></div>
                            <?php if($billing->gstin): ?>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                    GSTIN: <?php echo e($billing->gstin); ?>

                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-[11px] text-gray-400 dark:text-gray-500">
                            No billing address stored.
                        </p>
                    <?php endif; ?>
                </div>

                <div>
                    <h2 class="text-[11px] font-semibold text-gray-800 dark:text-gray-100 mb-2 uppercase tracking-wide">
                        Shipping address
                    </h2>

                    <?php if($shipping): ?>
                        <div class="space-y-0.5 text-[11px] text-gray-700 dark:text-gray-300">
                            <div><?php echo e($shipping->full_name); ?></div>
                            <div><?php echo e($shipping->phone); ?></div>
                            <div><?php echo e($shipping->address_line1); ?></div>
                            <?php if($shipping->address_line2): ?>
                                <div><?php echo e($shipping->address_line2); ?></div>
                            <?php endif; ?>
                            <div><?php echo e($shipping->city); ?>, <?php echo e($shipping->state); ?> – <?php echo e($shipping->pincode); ?></div>
                            <div><?php echo e($shipping->country); ?></div>
                            <?php if($shipping->gstin): ?>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                    GSTIN: <?php echo e($shipping->gstin); ?>

                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-[11px] text-gray-400 dark:text-gray-500">
                            No shipping address stored.
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-900 px-4 py-4 space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-[11px] font-semibold text-gray-800 dark:text-gray-100 uppercase tracking-wide">
                        Line items
                    </h2>

                    <span class="text-[10px] text-gray-400">
                        <?php echo e($invoice->items->count()); ?> item(s)
                    </span>
                </div>

                <div class="space-y-2">
                    <?php $__empty_1 = true; $__currentLoopData = $invoice->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="flex items-start justify-between gap-3 border-b border-gray-100 dark:border-gray-800 pb-2 last:border-b-0">
                            <div class="flex-1">
                                <div class="text-[11px] text-gray-900 dark:text-gray-50">
                                    <?php echo e($item->description); ?>

                                </div>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                    Qty: <?php echo e($item->quantity); ?> × ₹<?php echo e(number_format($item->unit_price, 2)); ?> <span class="text-[10px] text-gray-400">excl GST</span>
                                    <?php if((float) ($item->tax_amount ?? 0) > 0): ?>
                                        · GST ₹<?php echo e(number_format($item->tax_amount, 2)); ?>

                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="text-right text-[11px] text-gray-900 dark:text-gray-50 font-medium">
                                ₹<?php echo e(number_format($item->total, 2)); ?>

                                <div class="text-[10px] font-normal text-gray-400">incl GST</div>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            No items recorded for this invoice.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        
        <div class="space-y-4">
            <div class="border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-900 px-4 py-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-[11px] font-semibold text-gray-800 dark:text-gray-100 uppercase tracking-wide">
                        Invoice summary
                    </h2>

                    <span class="inline-flex items-center rounded-sm border px-2 py-0.5 text-[10px] font-medium <?php echo e($invoiceStatus['class']); ?>">
                        <?php echo e($invoiceStatus['label']); ?>

                    </span>
                </div>

                <dl class="space-y-2 text-[11px] text-gray-700 dark:text-gray-300">
                    <div class="flex justify-between gap-3">
                        <dt>Invoice #</dt>
                        <dd class="text-right text-gray-900 dark:text-gray-50 font-medium"><?php echo e($invoice->invoice_number); ?></dd>
                    </div>

                    <div class="flex justify-between gap-3">
                        <dt>Invoice date</dt>
                        <dd class="text-right"><?php echo e(optional($invoice->invoice_date)->format('d M Y') ?? '—'); ?></dd>
                    </div>

                    <div class="flex justify-between gap-3">
                        <dt>Due date</dt>
                        <dd class="text-right"><?php echo e(optional($invoice->due_date)->format('d M Y') ?? '—'); ?></dd>
                    </div>

                    <div class="flex justify-between gap-3">
                        <dt>Payment method</dt>
                        <dd class="text-right"><?php echo e($invoice->payment_method_label); ?></dd>
                    </div>

                    <div class="flex justify-between gap-3">
                        <dt>Payment status</dt>
                        <dd class="text-right"><?php echo e($invoice->payment_status_label); ?></dd>
                    </div>

                    <div class="flex justify-between gap-3">
                        <dt>Order #</dt>
                        <dd class="text-right text-gray-900 dark:text-gray-50 font-medium"><?php echo e($order->order_number ?? '—'); ?></dd>
                    </div>

                    <?php if($orderStatus): ?>
                        <div class="flex justify-between gap-3 items-center">
                            <dt>Order status</dt>
                            <dd>
                                <span class="inline-flex items-center rounded-sm border px-2 py-0.5 text-[10px] font-medium <?php echo e($orderStatus['class']); ?>">
                                    <?php echo e($orderStatus['label']); ?>

                                </span>
                            </dd>
                        </div>
                    <?php endif; ?>
                </dl>

                <div class="border-t border-gray-200 dark:border-gray-800 pt-3 space-y-1 text-[11px] text-gray-700 dark:text-gray-300">
                    <div class="flex justify-between">
                        <span>Subtotal <span class="text-[10px] text-gray-400">excl GST</span></span>
                        <span>₹<?php echo e(number_format($invoice->subtotal, 2)); ?></span>
                    </div>

                    <div class="flex justify-between">
                        <span>Discount</span>
                        <span>- ₹<?php echo e(number_format($invoice->discount_total, 2)); ?></span>
                    </div>

                    <?php if((float) ($invoice->delivery_fee ?? 0) > 0): ?>
                        <div class="flex justify-between">
                            <span>Delivery fee <span class="text-[10px] text-gray-400">excl GST</span></span>
                            <span>₹<?php echo e(number_format($invoice->delivery_fee, 2)); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if((float) ($invoice->handling_fee ?? 0) > 0): ?>
                        <div class="flex justify-between">
                            <span>Cold-chain handling & packing <span class="text-[10px] text-gray-400">excl GST</span></span>
                            <span>₹<?php echo e(number_format($invoice->handling_fee, 2)); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="flex justify-between">
                        <span>GST</span>
                        <span>₹<?php echo e(number_format($invoice->tax_total, 2)); ?></span>
                    </div>

                    <?php if(! $isB2BInvoiceCustomer && (float) ($invoice->bandara_credit_redeemed_amount ?? 0) > 0): ?>
                        <div class="flex justify-between text-emerald-700 dark:text-emerald-300">
                            <span>Bandara Credit redeemed (<?php echo e(number_format((int) ($invoice->bandara_credit_redeemed_points ?? 0))); ?> pts)</span>
                            <span>- ₹<?php echo e(number_format($invoice->bandara_credit_redeemed_amount, 2)); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="flex justify-between font-semibold text-gray-900 dark:text-gray-50 pt-1">
                        <span>Grand total <span class="text-[10px] font-normal text-gray-400">incl GST</span></span>
                        <span>₹<?php echo e(number_format($invoice->grand_total, 2)); ?></span>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-800 mt-2 pt-2 space-y-1">
                        <div class="flex justify-between">
                            <span>Paid</span>
                            <span>₹<?php echo e(number_format($paidAmount, 2)); ?></span>
                        </div>
                        <div class="flex justify-between font-medium text-gray-900 dark:text-gray-50">
                            <span>Balance due</span>
                            <span>₹<?php echo e(number_format($balanceAmount, 2)); ?></span>
                        </div>
                    </div>
                </div>

                <?php echo $__env->make('customer.invoices.partials.payment-widget', [
                    'invoice' => $invoice,
                    'balanceAmount' => $balanceAmount,
                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                <?php if($pdfUrl): ?>
                    <div class="pt-3">
                        <a href="<?php echo e($pdfUrl); ?>"
                           class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                            Download tax invoice (PDF)
                        </a>
                    </div>
                <?php else: ?>
                    <p class="pt-3 text-[10px] text-gray-400 dark:text-gray-500">
                        PDF download will be available once invoice generation is enabled.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.customer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/customer/invoices/show.blade.php ENDPATH**/ ?>