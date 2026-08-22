<?php $__env->startSection('title', 'Invoice'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-3xl mx-auto px-4 py-6 text-xs">
    <div class="border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-900 px-5 py-5 space-y-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h1 class="text-base font-semibold text-gray-900 dark:text-gray-50">
                    Tax Invoice
                </h1>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                    Frozen – Bandara by Maytira
                </p>
            </div>
            <div class="text-right text-[11px] text-gray-700 dark:text-gray-300">
                <div>Invoice no: <?php echo e($invoice->invoice_number ?? $invoice->id); ?></div>
                <div>Date: <?php echo e(($invoice->created_at ?? now())->format('d M Y')); ?></div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 text-[11px] text-gray-700 dark:text-gray-300">
            <div>
                <div class="font-semibold mb-1">Billed to</div>
                
                <div><?php echo e(auth()->user()->name); ?></div>
                <div><?php echo e(auth()->user()->email); ?></div>
            </div>
            <div>
                <div class="font-semibold mb-1">Order details</div>
                <div>Order: <?php echo e($order->order_number ?? ('#'.$order->id)); ?></div>
                <div>Date: <?php echo e($order->created_at->format('d M Y')); ?></div>
                <div>Status: <?php echo e(ucfirst($order->status ?? 'pending')); ?></div>
                <div>Payment: <?php echo e(($order->payment_method ?? 'razorpay') === 'pay_later' ? 'Pay Later on invoice' : 'Pay Now / Razorpay'); ?></div>
            </div>
        </div>

        <div class="border-t border-gray-200 dark:border-gray-800 pt-3">
            <table class="w-full text-[11px] text-gray-700 dark:text-gray-300">
                <thead class="border-b border-gray-200 dark:border-gray-800">
                    <tr>
                        <th class="py-1 text-left">Sr. No.</th>
                        <th class="py-1 text-left">Item</th>
                        <th class="py-1 text-right">Qty</th>
                        <th class="py-1 text-right">Price excl GST (₹)</th>
                        <th class="py-1 text-right">Total excl GST (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $order->items ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            static $count = 0;
                            $count++;
                        ?>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="py-1">
                                <?php echo e($count); ?>

                            </td>
                            <td class="py-1">
                                <?php echo e($item->product_name ?? 'Item'); ?>

                            </td>
                            <td class="py-1 text-right">
                                <?php echo e($item->quantity); ?>

                            </td>
                            <td class="py-1 text-right">
                                <?php echo e(number_format($item->unit_price ?? 0, 2)); ?>

                            </td>
                            <td class="py-1 text-right">
                                <?php echo e(number_format($item->total ?? ($item->quantity * $item->unit_price), 2)); ?>

                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 dark:border-gray-800 pt-3 space-y-1 text-[11px] text-gray-700 dark:text-gray-300">
            <div class="flex justify-between">
                <span>Subtotal <span class="text-[10px] text-gray-400">excl GST</span></span>
                <span>₹<?php echo e(number_format($order->subtotal ?? 0, 2)); ?></span>
            </div>
            <div class="flex justify-between">
                <span>Tax (GST)</span>
                <span>₹<?php echo e(number_format($order->tax_total ?? 0, 2)); ?></span>
            </div>
            <?php if(!empty($order->discount_total)): ?>
                <div class="flex justify-between">
                    <span>Discount</span>
                    <span>- ₹<?php echo e(number_format($order->discount_total, 2)); ?></span>
                </div>
            <?php endif; ?>
            <div class="flex justify-between font-semibold text-gray-900 dark:text-gray-50">
                <span>Grand total <span class="text-[10px] font-normal text-gray-400">incl GST</span></span>
                <span>₹<?php echo e(number_format($order->grand_total ?? 0, 2)); ?></span>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.customer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/customer/orders/invoice.blade.php ENDPATH**/ ?>