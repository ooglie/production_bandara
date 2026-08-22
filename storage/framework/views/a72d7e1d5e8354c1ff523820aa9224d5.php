<?php
    $ordersTodayUrl = $ordersTodayCount > 0
        ? route('admin.orders.index', ['period' => 'today'])
        : null;
?>

<?php $__env->startSection('title', 'Manager dashboard'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 py-6 text-xs space-y-4">
    <div class="flex items-center justify-between gap-3 mb-2">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Operations dashboard
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Overview of orders, stock health and support load.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="<?php echo e(route('admin.orders.index')); ?>"
               class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                View all orders
            </a>
            <a href="<?php echo e(route('admin.vendor-invoices.index')); ?>"
               class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                Vendor invoices
            </a>
            <a href="<?php echo e(route('support.tickets.index')); ?>"
               class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                Support tickets
            </a>
        </div>
    </div>

    <?php if(session('status')): ?>
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    
    <div class="grid gap-3 md:grid-cols-3">
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Orders today
            </p>

            <?php if($ordersTodayUrl): ?>
                <a href="<?php echo e($ordersTodayUrl); ?>"
                class="mt-1 inline-flex items-baseline gap-1 text-gray-900 dark:text-gray-50 hover:underline">
                    <span class="text-2xl font-semibold">
                        <?php echo e($ordersTodayCount); ?>

                    </span>
                    <span class="text-[10px] text-gray-400">
                        view
                    </span>
                </a>
            <?php else: ?>
                <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-50">
                    <?php echo e($ordersTodayCount); ?>

                </p>
            <?php endif; ?>

            <p class="mt-1 text-[10px] text-gray-400">
                New orders created since midnight.
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Orders by status
            </p>
            <div class="mt-2 grid grid-cols-2 gap-2 text-[11px] text-gray-700 dark:text-gray-200">
                <div>
                    <p>Processing</p>
                    <p class="font-semibold">
                        <?php echo e($ordersByStatus['processing'] ?? 0); ?>

                    </p>
                </div>
                <div>
                    <p>Shipped</p>
                    <p class="font-semibold">
                        <?php echo e($ordersByStatus['shipped'] ?? 0); ?>

                    </p>
                </div>
                <div>
                    <p>Delivered</p>
                    <p class="font-semibold">
                        <?php echo e($ordersByStatus['delivered'] ?? 0); ?>

                    </p>
                </div>
                <div>
                    <p>Cancelled</p>
                    <p class="font-semibold">
                        <?php echo e($ordersByStatus['cancelled'] ?? 0); ?>

                    </p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Support load
            </p>
            <div class="mt-2 space-y-1 text-[11px] text-gray-700 dark:text-gray-200">
                <div class="flex items-center justify-between">
                    <span>Open tickets</span>
                    <span class="font-semibold"><?php echo e($openTicketsCount); ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span>Unassigned tickets</span>
                    <span class="font-semibold"><?php echo e($unassignedTicketsCount); ?></span>
                </div>
            </div>
        </div>
    </div>

    
    <div class="grid gap-3 lg:grid-cols-[2fr,1.3fr]">
        
        <div class="space-y-3">
            
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        Open orders (processing / shipped)
                    </p>
                    <a href="<?php echo e(route('admin.orders.index')); ?>"
                       class="text-[10px] text-gray-500 dark:text-gray-400 hover:underline">
                        View all
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-[11px]">
                        <thead class="bg-gray-50 dark:bg-gray-950/40">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Order</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Customer</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Total</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Placed</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <?php $__empty_1 = true; $__currentLoopData = $openOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                                    <td class="px-3 py-2">
                                        <a href="<?php echo e(route('admin.orders.show', $order)); ?>"
                                           class="text-gray-900 dark:text-gray-50 hover:underline">
                                            <?php echo e($order->order_number); ?>

                                        </a>
                                        <div class="text-[10px] text-gray-400">
                                            #<?php echo e($order->id); ?>

                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                                        <?php echo e($order->user?->name ?? '—'); ?>

                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px]
                                            <?php if($order->status === 'processing'): ?> border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-800 dark:bg-sky-900/30 dark:text-sky-200
                                            <?php elseif($order->status === 'shipped'): ?> border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200
                                            <?php elseif($order->status === 'delivered'): ?> border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200
                                            <?php elseif($order->status === 'cancelled'): ?> border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400
                                            <?php endif; ?>">
                                            <?php echo e(ucfirst($order->status)); ?>

                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-right text-gray-900 dark:text-gray-50">
                                        ₹<?php echo e(number_format($order->grand_total, 2)); ?>

                                    </td>
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">
                                        <?php echo e(optional($order->placed_at ?? $order->created_at)->format('d M Y, H:i')); ?>

                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="5" class="px-3 py-3 text-center text-gray-500 dark:text-gray-400">
                                        No open orders right now.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        Recent support tickets
                    </p>
                    <a href="<?php echo e(route('support.tickets.index')); ?>"
                       class="text-[10px] text-gray-500 dark:text-gray-400 hover:underline">
                        View all tickets
                    </a>
                </div>

                <div class="space-y-2">
                    <?php $__empty_1 = true; $__currentLoopData = $recentTickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <a href="<?php echo e(route('support.tickets.show', $ticket)); ?>"
                           class="block rounded-lg px-3 py-2 border border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900/40">
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <p class="text-[11px] text-gray-900 dark:text-gray-50">
                                        <?php echo e($ticket->subject); ?>

                                    </p>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400">
                                        <?php echo e($ticket->user?->name ?? 'Customer #'.$ticket->user_id); ?>

                                    </p>
                                </div>
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px]
                                    <?php if($ticket->status === 'awaiting_customer_reply'): ?> border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200
                                    <?php elseif($ticket->status === 'resolved'): ?> border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200
                                    <?php elseif($ticket->status === 'closed'): ?> border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400
                                    <?php else: ?> border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-800 dark:bg-sky-900/30 dark:text-sky-200
                                    <?php endif; ?>">
                                    <?php echo e(ucfirst(str_replace('_', ' ', $ticket->status))); ?>

                                </span>
                            </div>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            No recent tickets.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        
        <div class="space-y-3">
            
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        Low stock alerts
                    </p>
                    <a href="<?php echo e(route('admin.products.index')); ?>"
                       class="text-[10px] text-gray-500 dark:text-gray-400 hover:underline">
                        View all products
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-[11px]">
                        <thead class="bg-gray-50 dark:bg-gray-950/40">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Product</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Stock</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Threshold</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <?php $__empty_1 = true; $__currentLoopData = $lowStockProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td class="px-3 py-2">
                                        <a href="<?php echo e(route('admin.products.edit', $p)); ?>"
                                           class="text-gray-900 dark:text-gray-50 hover:underline">
                                            <?php echo e($p->name); ?>

                                        </a>
                                        <?php if($p->sku): ?>
                                            <div class="text-[10px] text-gray-400">
                                                SKU: <?php echo e($p->sku); ?>

                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-2 text-right text-gray-900 dark:text-gray-50">
                                        <?php echo e((float) $p->stock_quantity); ?>

                                    </td>
                                    <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200">
                                        <?php echo e((float) $p->low_stock_threshold); ?>

                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="3" class="px-3 py-3 text-center text-gray-500 dark:text-gray-400">
                                        No low stock alerts based on current thresholds.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        Recent vendor invoices
                    </p>
                    <a href="<?php echo e(route('admin.vendor-invoices.index', ['vendor_id' => $recentVendorInvoices->first()?->vendor_id])); ?>"
                       class="text-[10px] text-gray-500 dark:text-gray-400 hover:underline">
                        View all
                    </a>
                </div>

                <div class="space-y-1">
                    <?php $__empty_1 = true; $__currentLoopData = $recentVendorInvoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $inv): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="flex items-center justify-between text-[11px] text-gray-700 dark:text-gray-200">
                            <div>
                                <p class="text-gray-900 dark:text-gray-50">
                                    <?php echo e($inv->invoice_number); ?>

                                </p>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400">
                                    <?php echo e($inv->vendor?->name ?? 'Vendor #'.$inv->vendor_id); ?>

                                    · <?php echo e(optional($inv->invoice_date)->format('d M Y')); ?>

                                </p>
                            </div>
                            <span class="text-[11px] font-medium text-gray-900 dark:text-gray-50">
                                ₹<?php echo e(number_format($inv->total_amount, 2)); ?>

                            </span>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            No vendor invoices yet.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/dashboard/manage.blade.php ENDPATH**/ ?>