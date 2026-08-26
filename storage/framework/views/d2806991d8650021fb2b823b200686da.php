<?php $__env->startSection('title', 'Orders'); ?>

<?php $__env->startSection('content'); ?>
<?php
    use Illuminate\Support\Facades\Route;

    $status = request('status');
    $unprintedOnly = request()->boolean('unprinted');

    $printNewRouteExists = Route::has('admin.orders.print.new');
    $printBulkRouteExists = Route::has('admin.orders.print.bulk');
    $markUnprintedBulkExists = Route::has('admin.orders.markUnprinted.bulk');
    $markUnprintedSingleExists = Route::has('admin.orders.markUnprinted');
    $ordersShowExists = Route::has('admin.orders.show');
    $ordersPrintSingleExists = Route::has('admin.orders.print');

    // Use the original working route name from web.php
    $bulkStatusRouteName = 'admin.orders.bulk-status';
    $bulkStatusRouteExists = Route::has($bulkStatusRouteName);

    $statusOptions = [
        '' => 'All statuses',
        'pending_payment' => 'Pending Payment',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'payment_failed' => 'Payment Failed',
        'payment_expired' => 'Payment Expired',
    ];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-xs space-y-4">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Orders</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Manage orders, bulk actions, and printing.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <?php if($printNewRouteExists): ?>
                <a href="<?php echo e(route('admin.orders.print.new')); ?>"
                   target="_blank"
                   class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                    Print new orders
                </a>
            <?php endif; ?>

            <?php if($printBulkRouteExists): ?>
                <form id="bulk-print-form" method="POST" action="<?php echo e(route('admin.orders.print.bulk')); ?>" target="_blank" class="inline">
                    <?php echo csrf_field(); ?>
                    <button type="submit"
                        class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Print selected
                    </button>
                </form>
            <?php endif; ?>

            <?php if($markUnprintedBulkExists): ?>
                <form id="bulk-unprint-form" method="POST" action="<?php echo e(route('admin.orders.markUnprinted.bulk')); ?>" class="inline"
                      onsubmit="return confirm('Mark selected orders as unprinted?');">
                    <?php echo csrf_field(); ?>
                    <button type="submit"
                        class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Mark selected unprinted
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if(session('status')): ?>
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <?php if($errors->any()): ?>
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
            <ul class="list-disc pl-4 space-y-0.5">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    
    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-3">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            
            <form method="GET" action="<?php echo e(url()->current()); ?>"
                  class="flex flex-col gap-2 sm:flex-row sm:items-center">

                <?php $__currentLoopData = request()->except(['status','unprinted','page']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <input type="hidden" name="<?php echo e($k); ?>" value="<?php echo e($v); ?>">
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                <div class="flex items-center gap-2">
                    <label class="text-[11px] text-gray-600 dark:text-gray-300">Status</label>
                    <select name="status"
                            class="rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                        <?php $__currentLoopData = $statusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($val); ?>" <?php if((string)$status === (string)$val): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <label class="inline-flex items-center gap-2 text-[11px] text-gray-700 dark:text-gray-200">
                    <input type="checkbox" name="unprinted" value="1" <?php if($unprintedOnly): echo 'checked'; endif; ?>>
                    <span>Unprinted only</span>
                </label>

                <button type="submit"
                        class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                    Apply
                </button>

                <?php if(request()->has('status') || request()->has('unprinted')): ?>
                    <a href="<?php echo e(url()->current()); ?>"
                       class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Clear
                    </a>
                <?php endif; ?>
            </form>

            
            <div class="flex items-center gap-2">
                <?php if($bulkStatusRouteExists): ?>
                    <form id="bulk-status-form" method="POST" action="<?php echo e(route($bulkStatusRouteName)); ?>" class="flex items-center gap-2">
                        <?php echo csrf_field(); ?>
                        <label class="text-[11px] text-gray-600 dark:text-gray-300">Bulk status</label>
                        <select name="new_status"
                                class="rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                            <option value="">Choose…</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>

                        <button type="submit"
                                class="text-[11px] px-3 py-1 rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-200">
                            Update selected
                        </button>
                    </form>
                <?php else: ?>
                    <span class="text-[10px] text-gray-400">
                        Bulk status route not found (expected: <code><?php echo e($bulkStatusRouteName); ?></code>).
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    
    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-[11px]">
                <thead class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800">
                    <tr class="text-left text-gray-600 dark:text-gray-300">
                        <th class="px-3 py-2 w-10">
                            <input type="checkbox" id="select-all">
                        </th>
                        <th class="px-3 py-2 font-medium">Order</th>
                        <th class="px-3 py-2 font-medium">Customer</th>
                        <th class="px-3 py-2 font-medium">Placed</th>
                        <th class="px-3 py-2 font-medium">Status</th>
                        <th class="px-3 py-2 font-medium">Payment</th>
                        <th class="px-3 py-2 font-medium text-right">Total</th>
                        <th class="px-3 py-2 font-medium">Printed</th>
                        <th class="px-3 py-2 font-medium text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $placed = $order->placed_at ?? $order->created_at;

                        $orderLabel = $order->order_number ?? ('#'.$order->id);
                        $customerName = $order->user?->name ?? 'Customer';
                        $customerPhone = $order->user?->phone ?? null;

                        $st = (string)($order->status ?? 'processing');
                        $pay = (string)($order->payment_status ?? 'pending');
                        $total = (float)($order->grand_total ?? 0);

                        $stBadge = match($st) {
                            'delivered' => 'border-emerald-300 bg-emerald-50 text-emerald-800',
                            'shipped' => 'border-blue-300 bg-blue-50 text-blue-800',
                            'pending_payment' => 'border-amber-300 bg-amber-50 text-amber-800',
                            'payment_failed' => 'border-red-300 bg-red-50 text-red-800',
                            'payment_expired' => 'border-orange-300 bg-orange-50 text-orange-800',
                            'cancelled' => 'border-red-300 bg-red-50 text-red-800',
                            default => 'border-gray-300 bg-gray-50 text-gray-800',
                        };

                        $payBadge = match($pay) {
                            'paid' => 'border-emerald-300 bg-emerald-50 text-emerald-800',
                            'failed' => 'border-red-300 bg-red-50 text-red-800',
                            'expired' => 'border-orange-300 bg-orange-50 text-orange-800',
                            'refunded' => 'border-gray-300 bg-gray-50 text-gray-700',
                            default => 'border-yellow-300 bg-yellow-50 text-yellow-800',
                        };

                        $printedAt = $order->printed_at ?? null;
                        $printedBy = method_exists($order, 'printedBy') ? $order->printedBy : null;

                        $showUrl = $ordersShowExists ? route('admin.orders.show', $order) : url('/admin/orders/'.$order->id);
                        $printUrl = $ordersPrintSingleExists ? route('admin.orders.print', $order) : null;
                    ?>

                    <tr class="text-gray-700 dark:text-gray-200">
                        <td class="px-3 py-2">
                            <input type="checkbox" class="order-checkbox" value="<?php echo e($order->id); ?>">
                        </td>

                        <td class="px-3 py-2">
                            <a href="<?php echo e($showUrl); ?>" class="font-semibold text-gray-900 dark:text-gray-50 hover:underline">
                                <?php echo e($orderLabel); ?>

                            </a>
                            <div class="text-[10px] text-gray-400">ID: <?php echo e($order->id); ?></div>
                        </td>

                        <td class="px-3 py-2">
                            <div class="font-medium text-gray-900 dark:text-gray-50"><?php echo e($customerName); ?></div>
                            <?php if($customerPhone): ?>
                                <div class="text-[10px] text-gray-400"><?php echo e($customerPhone); ?></div>
                            <?php endif; ?>
                        </td>

                        <td class="px-3 py-2 whitespace-nowrap">
                            <?php echo e($placed?->format('d M Y')); ?>

                            <div class="text-[10px] text-gray-400"><?php echo e($placed?->format('h:i A')); ?></div>
                        </td>

                        <td class="px-3 py-2">
                            <span class="text-[10px] px-2 py-0.5 rounded-full border <?php echo e($stBadge); ?>">
                                <?php echo e(ucfirst($st)); ?>

                            </span>
                        </td>

                        <td class="px-3 py-2">
                            <span class="text-[10px] px-2 py-0.5 rounded-full border <?php echo e($payBadge); ?>">
                                <?php echo e(ucfirst($pay)); ?>

                            </span>
                        </td>

                        <td class="px-3 py-2 text-right whitespace-nowrap">
                            ₹<?php echo e(number_format($total, 2)); ?>

                        </td>

                        <td class="px-3 py-2">
                            <?php if($printedAt): ?>
                                <span class="text-[10px] px-2 py-0.5 rounded-full border border-emerald-300 bg-emerald-50 text-emerald-800">
                                    Printed
                                </span>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">
                                    <?php echo e($printedAt->format('d M, h:i A')); ?>

                                </div>
                                <?php if($printedBy): ?>
                                    <div class="text-[10px] text-gray-400">
                                        by <?php echo e($printedBy->name); ?>

                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-[10px] px-2 py-0.5 rounded-full border border-gray-300 text-gray-700 dark:text-gray-300">
                                    Unprinted
                                </span>
                            <?php endif; ?>
                        </td>

                        <td class="px-3 py-2 text-right whitespace-nowrap">
                            <a href="<?php echo e($showUrl); ?>"
                               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                View
                            </a>

                            <?php if($printUrl): ?>
                                <a href="<?php echo e($printUrl); ?>" target="_blank"
                                   class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                    Print
                                </a>
                            <?php endif; ?>

                            <?php if($printedAt && $markUnprintedSingleExists): ?>
                                <form method="POST" action="<?php echo e(route('admin.orders.markUnprinted', $order)); ?>"
                                      class="inline"
                                      onsubmit="return confirm('Mark this order as unprinted?');">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit"
                                        class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                        Mark unprinted
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="9" class="px-3 py-6 text-center text-[11px] text-gray-500 dark:text-gray-400">
                            No orders found for the current filters.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if(method_exists($orders, 'links')): ?>
            <div class="px-3 py-3 border-t border-gray-200 dark:border-gray-800">
                <?php echo e($orders->links()); ?>

            </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    const selectAll = document.getElementById('select-all');
    const boxes = () => Array.from(document.querySelectorAll('.order-checkbox'));

    function selectedIds() {
        return boxes().filter(b => b.checked).map(b => b.value);
    }

    function injectIds(form) {
        form.querySelectorAll('input[name="order_ids[]"][data-injected="1"]').forEach(el => el.remove());

        const ids = selectedIds();
        ids.forEach(id => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'order_ids[]';
            inp.value = id;
            inp.setAttribute('data-injected', '1');
            form.appendChild(inp);
        });

        return ids.length;
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            boxes().forEach(b => b.checked = selectAll.checked);
        });
    }

    const printForm = document.getElementById('bulk-print-form');
    if (printForm) {
        printForm.addEventListener('submit', function (e) {
            const count = injectIds(printForm);
            if (!count) {
                e.preventDefault();
                alert('Please select at least one order.');
            }
        });
    }

    const unprintForm = document.getElementById('bulk-unprint-form');
    if (unprintForm) {
        unprintForm.addEventListener('submit', function (e) {
            const count = injectIds(unprintForm);
            if (!count) {
                e.preventDefault();
                alert('Please select at least one order.');
            }
        });
    }

    const bulkStatusForm = document.getElementById('bulk-status-form');
    if (bulkStatusForm) {
        bulkStatusForm.addEventListener('submit', function (e) {
            const count = injectIds(bulkStatusForm);
            if (!count) {
                e.preventDefault();
                alert('Please select at least one order.');
                return;
            }

            const statusSel = bulkStatusForm.querySelector('select[name="new_status"]');
            if (statusSel && !statusSel.value) {
                e.preventDefault();
                alert('Please choose a status to apply.');
            }
        });
    }
})();
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/orders/index.blade.php ENDPATH**/ ?>