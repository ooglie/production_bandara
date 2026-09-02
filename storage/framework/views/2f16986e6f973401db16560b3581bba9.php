<?php $__env->startSection('title', 'Order details'); ?>

<?php $__env->startSection('content'); ?>
<?php
    use Illuminate\Support\Str;

    $invoice = $order->invoice ?? null;
    $isB2BOrderCustomer = (auth()->user()?->customer_type ?? 'b2c') === 'b2b';

    $invoiceStatus = strtolower((string) ($invoice->status ?? 'pending'));
    $orderPaymentMethod = strtolower((string) ($order->payment_method ?? 'razorpay'));
    $orderPaymentStatus = strtolower((string) ($order->payment_status ?? 'pending'));
    $orderStatusRaw = strtolower((string) ($order->status ?? 'processing'));
    $isOnlineOrder = $orderPaymentMethod !== 'pay_later';

    $canRetryOnlineOrderPayment = $isOnlineOrder
        && config('services.razorpay.key')
        && config('services.razorpay.secret')
        && $invoice
        && in_array($invoiceStatus, ['pending', 'due'], true)
        && in_array($orderPaymentStatus, ['pending', 'failed', 'expired'], true)
        && in_array($orderStatusRaw, ['pending_payment', 'payment_failed', 'payment_expired', 'processing'], true);

    $showInvoicePaymentWidget = ! $isOnlineOrder || $orderPaymentStatus === 'paid';

    $unitLabel = function (?string $u) {
        $u = strtolower((string) $u);

        return match ($u) {
            'kg' => 'kg',
            'pack' => 'pack',
            default => 'pc',
        };
    };

    $formatNumber = function ($value, int $decimals = 3) {
        $number = (float) ($value ?? 0);

        if (abs($number - round($number)) < 0.000001) {
            return number_format($number, 0, '.', '');
        }

        return rtrim(rtrim(number_format($number, $decimals, '.', ''), '0'), '.');
    };

    $orderStatusMeta = function (?string $status) {
        $status = strtolower((string) $status);

        return match ($status) {
            'pending_payment' => [
                'label' => 'Pending Payment',
                'class' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800',
            ],
            'payment_failed' => [
                'label' => 'Payment Failed',
                'class' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800',
            ],
            'payment_expired' => [
                'label' => 'Payment Expired',
                'class' => 'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-900/20 dark:text-orange-300 dark:border-orange-800',
            ],
            'processing' => [
                'label' => 'Processing',
                'class' => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-900/20 dark:text-sky-300 dark:border-sky-800',
            ],
            'shipped' => [
                'label' => 'Shipped',
                'class' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-300 dark:border-blue-800',
            ],
            'delivered' => [
                'label' => 'Delivered',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-300 dark:border-emerald-800',
            ],
            'cancelled' => [
                'label' => 'Cancelled',
                'class' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800',
            ],
            default => [
                'label' => Str::headline($status ?: 'Unknown'),
                'class' => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
            ],
        };
    };

    $invoiceStatusMeta = function ($invoice) {
        if (!$invoice || !($invoice->status ?? null)) {
            return [
                'label' => 'Not generated',
                'class' => 'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
            ];
        }

        $status = strtolower((string) $invoice->status);

        return match ($status) {
            'paid' => [
                'label' => 'Paid',
                'class' => 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800',
            ],
            'past_due' => [
                'label' => 'Past due',
                'class' => 'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800',
            ],
            'due' => [
                'label' => 'Due',
                'class' => 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800',
            ],
            'pending' => [
                'label' => 'Pending',
                'class' => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
            ],
            default => [
                'label' => Str::headline($status),
                'class' => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
            ],
        };
    };

    $orderStatus = $orderStatusMeta($order->status ?? null);
    $invoiceStatus = $invoiceStatusMeta($invoice);
    $paidAmount = $invoice ? (float) ($invoice->amount_paid ?? 0) : 0.0;
    $balanceAmount = $invoice ? (float) ($invoice->balance_amount ?? max(0, ($invoice->grand_total ?? 0) - $paidAmount)) : 0.0;

    $items = collect($order->items ?? []);
    $itemsCount = $items->sum(fn ($item) => (float) ($item->quantity ?? 0));

    $lineTotal = function ($item) {
        // if (isset($item->total) && $item->total !== null) {
        //     return (float) $item->total;
        // }

        // if (isset($item->line_total) && $item->line_total !== null) {
        //     return (float) $item->line_total;
        // }

        // if (isset($item->total_amount) && $item->total_amount !== null) {
        //     return (float) $item->total_amount;
        // }

        // if (isset($item->subtotal) && $item->subtotal !== null) {
        //     return (float) $item->subtotal;
        // }

        // return (float) ($item->unit_price ?? 0) * (float) ($item->quantity ?? 0);
        return (float) $item->subtotal;
    };
?>

<div class="max-w-6xl mx-auto px-4 py-6 space-y-5">    <?php if($errors->any()): ?>
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
            <div class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-1 text-[10px] font-medium uppercase tracking-[0.14em] text-gray-600 dark:text-gray-300">
                Order details
            </div>

            <h1 class="mt-3 text-2xl font-semibold text-gray-900 dark:text-gray-50">
                <?php echo e($order->order_number ?? ('#' . $order->id)); ?>

            </h1>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                Placed on <?php echo e($order->created_at->format('d M Y, H:i')); ?>

            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="<?php echo e(route('orders.index')); ?>"
               class="inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-2 text-[11px] font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                Back to orders
            </a>

            <?php if($invoice): ?>
                <a href="<?php echo e(route('orders.invoice', $order)); ?>"
                   class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-2 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                    Download invoice
                </a>
            <?php endif; ?>
        </div>
    </div>

    
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Order number</div>
            <div class="mt-1 text-sm font-semibold font-mono text-gray-900 dark:text-gray-50">
                <?php echo e($order->order_number ?? ('#' . $order->id)); ?>

            </div>
        </div>

        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Status</div>
            <div class="mt-2">
                <span class="inline-flex items-center rounded-sm border px-2.5 py-0.5 text-[11px] font-medium <?php echo e($orderStatus['class']); ?>">
                    <?php echo e($orderStatus['label']); ?>

                </span>
            </div>
        </div>

        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Total quantity</div>
            <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">
                <?php echo e($formatNumber($itemsCount)); ?>

            </div>
        </div>

        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Grand total</div>
            <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">
                ₹<?php echo e(number_format($order->grand_total ?? 0, 2)); ?>

            </div>
        </div>
    </div>

    
    <div class="grid gap-4 lg:grid-cols-[1.35fr,0.75fr]">
        
        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 space-y-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                        Order summary
                    </h2>
                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        Review the products, quantities, and tax details for this order.
                    </p>
                </div>
            </div>

            <div class="space-y-3">
                <?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $product = $products[$item->product_id] ?? null;
                        $sellUnit = strtolower((string) ($item->sell_unit ?? 'piece'));
                        $gstRate = (float) ($product->gst_rate ?? 0);
                        $variantLabel = $item->variant_label ?? null;
                        $itemLineTotal = $lineTotal($item);
                        $qtyText = $formatNumber($item->quantity ?? 0);
                        $weightText = $formatNumber($item->item_weight ?? 0);
                    ?>

                    <div class="rounded-sm border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-4">
                        <div class="gap-4flex justify-between">
                            <div class="min-w-0">
                                <div class="flex items-start gap-3 flex justify-between">
                                    <div class="mt-0.5 inline-flex h-6 min-w-[24px] items-center justify-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-[10px] font-medium text-gray-500 dark:text-gray-300">
                                        <?php echo e($loop->iteration); ?>

                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-50">
                                            <?php echo e($item->product_name ?? 'Item'); ?>

                                        </div>

                                        <?php if($variantLabel): ?>
                                            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                                <?php echo e($variantLabel); ?>

                                            </div>
                                        <?php endif; ?>

                                        <div class="mt-2 flex flex-wrap gap-2 text-[11px] flex justify-between">
                                            <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-0.5 text-gray-600 dark:text-gray-300">
                                                Qty <?php echo e($qtyText); ?> 
                                                &nbsp;&nbsp;&nbsp;&nbsp;
                                                <?php if(!empty($item->item_weight)): ?>
                                                    Wt <?php echo e($weightText); ?>

                                                <?php endif; ?>
                                                &nbsp;&nbsp;&nbsp;&nbsp;
                                                 GST <?php echo e($formatNumber($gstRate, 2)); ?>%
                                                &nbsp;&nbsp;&nbsp;&nbsp;
                                                 Price per <?php echo e($unitLabel($sellUnit)); ?> ₹<?php echo e(number_format($item->unit_price ?? 0, 2)); ?> 
                                            </span>

                                            

                                            <span class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50 text-left sm:text-right">
                                                 ₹<?php echo e(number_format($itemLineTotal, 2)); ?>

                                            </span>


                                            <?php if(!empty($product?->product_weight)): ?>
                                                
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                </div>
                                
                                

                            </div>

                            
                                

                                
                                
                            
                        </div>
                        
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="rounded-sm border border-dashed border-gray-300 dark:border-gray-700 px-4 py-5 text-sm text-gray-500 dark:text-gray-400">
                        No items found.
                    </div>
                <?php endif; ?>
            </div>

            
            <div class="rounded-sm border border-gray-100 dark:border-gray-800 bg-gray-100 dark:bg-black px-4 py-4 space-y-2 text-sm">
                <div class="flex justify-between text-gray-700 dark:text-gray-300">
                    <span>Subtotal <span class="text-[10px] text-gray-400">excl GST</span></span>
                    <span>₹<?php echo e(number_format($order->subtotal ?? 0, 2)); ?></span>
                </div>

                <?php if(!empty($order->discount_total)): ?>
                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>Discount</span>
                        <span>- ₹<?php echo e(number_format($order->discount_total, 2)); ?></span>
                    </div>
                <?php endif; ?>

                <?php if((float) ($order->delivery_fee ?? 0) > 0): ?>
                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>Delivery fee <span class="text-[10px] text-gray-400">excl GST</span></span>
                        <span>₹<?php echo e(number_format($order->delivery_fee, 2)); ?></span>
                    </div>
                <?php endif; ?>

                <?php if((float) ($order->handling_fee ?? 0) > 0): ?>
                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>Cold-chain handling & packing <span class="text-[10px] text-gray-400">excl GST</span></span>
                        <span>₹<?php echo e(number_format($order->handling_fee, 2)); ?></span>
                    </div>
                <?php endif; ?>

                <?php if($order->gst_type === 'intra_state'): ?>
                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>SGST</span>
                        <span>₹<?php echo e(number_format($order->sgst_amount ?? 0, 2)); ?></span>
                    </div>
                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>CGST</span>
                        <span>₹<?php echo e(number_format($order->cgst_amount ?? 0, 2)); ?></span>
                    </div>
                <?php elseif($order->gst_type === 'inter_state'): ?>
                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>IGST</span>
                        <span>₹<?php echo e(number_format($order->igst_amount ?? 0, 2)); ?></span>
                    </div>
                <?php endif; ?>

                <?php if(! $isB2BOrderCustomer && (float) ($order->bandara_credit_redeemed_amount ?? 0) > 0): ?>
                    <div class="flex justify-between text-emerald-700 dark:text-emerald-300">
                        <span>Bandara Credit redeemed (<?php echo e(number_format((int) ($order->bandara_credit_redeemed_points ?? 0))); ?> pts)</span>
                        <span>- ₹<?php echo e(number_format($order->bandara_credit_redeemed_amount, 2)); ?></span>
                    </div>
                <?php endif; ?>

                <div class="border-t border-gray-100 dark:border-gray-800 pt-2 flex justify-between font-semibold text-gray-900 dark:text-gray-50">
                    <span>Grand total <span class="text-[10px] font-normal text-gray-400">incl GST</span></span>
                    <span>₹<?php echo e(number_format($order->grand_total ?? 0, 2)); ?></span>
                </div>
            </div>
        </div>

        
        <div class="rounded-sm border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-50">
                    Invoice & payment
                </h2>
            </div>

            <?php if($invoice): ?>
                <div class="relative rounded-sm border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-4 pr-24">
                    <div class="text-[10px] uppercase tracking-wide text-gray-400">Invoice number</div>
                    <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">
                        <?php echo e($invoice->invoice_number ?? $invoice->id); ?>

                    </div>

                    <div class="mt-3 text-[10px] uppercase tracking-wide text-gray-400">Invoice date</div>
                    <div class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                        <?php echo e(($invoice->created_at ?? $order->created_at)->format('d M Y')); ?>

                    </div>

                    <div class="mt-3 text-[10px] uppercase tracking-wide text-gray-400">Invoice total</div>
                    <div class="mt-1 text-base font-semibold text-gray-900 dark:text-gray-50">
                        ₹<?php echo e(number_format($invoice->grand_total ?? $order->grand_total ?? 0, 2)); ?>

                    </div>

                    <div class="mt-3 text-[10px] uppercase tracking-wide text-gray-400">Payment method</div>
                    <div class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                        <?php echo e($invoice->payment_method_label ?? (($order->payment_method ?? 'razorpay') === 'pay_later' ? 'Pay Later on invoice' : 'Pay Now / Razorpay')); ?>

                        <?php if(($order->payment_method ?? 'razorpay') === 'pay_later' && !empty($order->payment_due_at)): ?>
                            · Due <?php echo e($order->payment_due_at->format('d M Y')); ?>

                        <?php endif; ?>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-3 text-[11px]">
                        <div>
                            <div class="text-[10px] uppercase tracking-wide text-gray-400">Paid</div>
                            <div class="mt-1 text-sm text-gray-700 dark:text-gray-300">₹<?php echo e(number_format($paidAmount, 2)); ?></div>
                        </div>
                        <div>
                            <div class="text-[10px] uppercase tracking-wide text-gray-400">Balance due</div>
                            <div class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-50">₹<?php echo e(number_format($balanceAmount, 2)); ?></div>
                        </div>
                    </div>

                    <span class="absolute right-3 top-3 inline-flex items-center rounded-sm border px-2 py-0.5 text-[10px] font-medium <?php echo e($invoiceStatus['class']); ?>">
                        <?php echo e($invoiceStatus['label']); ?>

                    </span>
                </div>

                <?php if($canRetryOnlineOrderPayment): ?>
                    <div class="mt-3 rounded-sm border border-amber-200 dark:border-amber-900/40 bg-amber-50/80 dark:bg-amber-950/20 px-3 py-3">
                        <div class="text-[11px] font-semibold text-amber-900 dark:text-amber-100">
                            Complete online payment
                        </div>
                        <p class="mt-1 text-[10px] leading-relaxed text-amber-700 dark:text-amber-300">
                            Stock is not held permanently for unpaid orders. We will check availability again and hold the stock briefly before opening Razorpay.
                        </p>
                        <a href="<?php echo e(route('orders.pay.razorpay', $order)); ?>"
                           class="mt-3 inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                            Retry payment
                        </a>
                    </div>
                <?php elseif($showInvoicePaymentWidget): ?>
                    <?php echo $__env->make('customer.invoices.partials.payment-widget', [
                        'invoice' => $invoice,
                        'balanceAmount' => $balanceAmount,
                        'paymentTitle' => 'Pay this invoice',
                        'paymentDescription' => (($order->payment_method ?? 'razorpay') === 'pay_later')
                            ? 'This order was placed on Pay Later terms. Pay online by Razorpay or submit offline payment details for approval.'
                            : 'Complete the pending invoice payment online or submit offline payment details for approval.',
                    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php endif; ?>

                <div class="flex flex-wrap gap-2">
                    <a href="<?php echo e(route('orders.invoice', $order)); ?>"
                       class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                        Download invoice (PDF)
                    </a>
                </div>
            <?php else: ?>
                <div class="rounded-sm border border-dashed border-gray-300 dark:border-gray-700 px-4 py-5 text-sm text-gray-500 dark:text-gray-400">
                    Invoice has not been generated yet. You’ll be able to download it once your order is processed.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.customer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/customer/orders/show.blade.php ENDPATH**/ ?>