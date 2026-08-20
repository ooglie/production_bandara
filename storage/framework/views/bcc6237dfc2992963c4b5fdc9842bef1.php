<?php
    // Safe defaults
    $revenueToday              = $revenueToday              ?? 0;
    $revenueThisMonth          = $revenueThisMonth          ?? 0;
    $ordersTodayCount          = $ordersTodayCount          ?? 0;
    $ordersTotalCount          = $ordersTotalCount          ?? 0;
    $totalCustomers            = $totalCustomers            ?? 0;
    $activeCustomersThisMonth  = $activeCustomersThisMonth  ?? 0;

    $ordersByStatus            = $ordersByStatus            ?? [];
    $ordersByPaymentStatus     = $ordersByPaymentStatus     ?? [];

    $recentOrders              = $recentOrders              ?? collect();
    $recentCustomers           = $recentCustomers           ?? collect();
    $topCustomers              = $topCustomers              ?? collect();
    $lowStockProducts          = $lowStockProducts          ?? collect();
    $monthlySales              = $monthlySales              ?? collect();
    // Chart data
    $monthlyLabels = $monthlySales->map(function ($row) {
        try {
            return \Carbon\Carbon::createFromFormat('Y-m', $row->ym)->format('M y');
        } catch (\Exception $e) {
            return $row->ym;
        }
    });

    $monthlyValues = $monthlySales->map(fn($row) => (float) $row->total);

    $statusLabels          = ['processing', 'shipped', 'delivered', 'cancelled'];
    $statusDisplayLabels   = ['Processing', 'Shipped', 'Delivered', 'Cancelled'];
    $statusValues          = array_map(fn($s) => (int) ($ordersByStatus[$s] ?? 0), $statusLabels);

    $ordersTodayUrl = $ordersTodayCount > 0
        ? route('admin.orders.index', ['period' => 'today'])
        : null;

?>

<?php $__env->startSection('title', 'Admin dashboard'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 py-5 text-xs space-y-4">
    
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-50">
                Admin dashboard
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Quick view of sales, orders, customers and inventory.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="<?php echo e(route('admin.orders.index')); ?>"
               class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                Orders
            </a>
            <a href="<?php echo e(route('admin.products.index')); ?>"
               class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                Products
            </a>
            <a href="<?php echo e(route('admin.users.index')); ?>"
               class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                Customers
            </a>
        </div>
    </div>

    <?php if(session('status')): ?>
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    
    <div class="grid gap-2 sm:gap-3 grid-cols-2 md:grid-cols-4">
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-2.5">
            <p class="text-[10px] text-gray-500 dark:text-gray-400 mb-0.5">
                Revenue today
            </p>
            <p class="text-lg font-semibold tracking-tight text-gray-900 dark:text-gray-50">
                ₹<?php echo e(number_format($revenueToday, 0)); ?>

            </p>
            <p class="mt-0.5 text-[10px] text-gray-400">
                Paid orders placed today.
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-2.5">
            <p class="text-[10px] text-gray-500 dark:text-gray-400 mb-0.5">
                Revenue this month
            </p>
            <p class="text-lg font-semibold tracking-tight text-gray-900 dark:text-gray-50">
                ₹<?php echo e(number_format($revenueThisMonth, 0)); ?>

            </p>
            <p class="mt-0.5 text-[10px] text-gray-400">
                Since <?php echo e(now()->startOfMonth()->format('d M')); ?>.
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-2.5">
            <div class="<?php echo e($ordersTodayUrl ? 'cursor-pointer' : ''); ?>">
                <p class="text-[10px] text-gray-500 dark:text-gray-400 mb-0.5">
                    Orders today
                </p>

                <?php if($ordersTodayUrl): ?>
                    <a href="<?php echo e($ordersTodayUrl); ?>" class="inline-flex items-baseline gap-1 text-gray-900 dark:text-gray-50 hover:underline">
                        <span class="text-lg font-semibold tracking-tight">
                            <?php echo e($ordersTodayCount); ?>

                        </span>
                        <span class="text-[10px] text-gray-400">
                            view
                        </span>
                    </a>
                <?php else: ?>
                    <span class="text-lg font-semibold tracking-tight text-gray-900 dark:text-gray-50">
                        <?php echo e($ordersTodayCount); ?>

                    </span>
                <?php endif; ?>

                <p class="mt-0.5 text-[10px] text-gray-400">
                    Created since midnight.
                </p>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-2.5">
            <p class="text-[10px] text-gray-500 dark:text-gray-400 mb-0.5">
                Customers
            </p>
            <p class="text-lg font-semibold tracking-tight text-gray-900 dark:text-gray-50">
                <?php echo e($totalCustomers); ?>

            </p>
            <p class="mt-0.5 text-[10px] text-gray-400">
                <?php echo e($activeCustomersThisMonth); ?> active this month.
            </p>
        </div>
    </div>

    
    <div class="grid gap-3 lg:grid-cols-[2fr,1fr] items-stretch">
        
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-3">
            <div class="flex items-center justify-between mb-2">
                <div>
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        Revenue (last 12 months)
                    </p>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400">
                        Based on paid orders.
                    </p>
                </div>
            </div>
            <div class="h-44 sm:h-52">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-3 flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        Order status
                    </p>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400">
                        Processing, shipped, delivered, cancelled.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-24 h-24 sm:w-28 sm:h-28">
                    <canvas id="orderStatusChart"></canvas>
                </div>
                <div class="flex-1 space-y-0.5 text-[11px] text-gray-700 dark:text-gray-200">
                    <div class="flex items-center justify-between">
                        <span>Processing</span>
                        <span class="font-semibold"><?php echo e($ordersByStatus['processing'] ?? 0); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Shipped</span>
                        <span class="font-semibold"><?php echo e($ordersByStatus['shipped'] ?? 0); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Delivered</span>
                        <span class="font-semibold"><?php echo e($ordersByStatus['delivered'] ?? 0); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Cancelled</span>
                        <span class="font-semibold"><?php echo e($ordersByStatus['cancelled'] ?? 0); ?></span>
                    </div>
                    <p class="pt-1 mt-1 text-[10px] text-gray-400 border-t border-dashed border-gray-200 dark:border-gray-800">
                        Total orders: <?php echo e($ordersTotalCount); ?>

                    </p>
                </div>
            </div>
        </div>
    </div>

    
    <div class="grid gap-3 xl:grid-cols-[1.6fr,1.1fr]">
        
        <div class="space-y-3">
            
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        Recent orders
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
                                <th class="px-3 py-1.5 text-left font-medium text-gray-500 dark:text-gray-400">Order</th>
                                <th class="px-3 py-1.5 text-left font-medium text-gray-500 dark:text-gray-400">Customer</th>
                                <th class="px-3 py-1.5 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                                <th class="px-3 py-1.5 text-right font-medium text-gray-500 dark:text-gray-400">Total</th>
                                <th class="px-3 py-1.5 text-left font-medium text-gray-500 dark:text-gray-400">Placed</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <?php $__empty_1 = true; $__currentLoopData = $recentOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                                    <td class="px-3 py-1.5">
                                        <a href="<?php echo e(route('admin.orders.show', $order)); ?>"
                                           class="text-gray-900 dark:text-gray-50 hover:underline">
                                            <?php echo e($order->order_number); ?>

                                        </a>
                                        <div class="text-[10px] text-gray-400">
                                            #<?php echo e($order->id); ?>

                                        </div>
                                    </td>
                                    <td class="px-3 py-1.5 text-gray-700 dark:text-gray-200">
                                        <?php echo e($order->user?->name ?? '—'); ?>

                                    </td>
                                    <td class="px-3 py-1.5">
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px]
                                            <?php if($order->status === 'processing'): ?> border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-800 dark:bg-sky-900/30 dark:text-sky-200
                                            <?php elseif($order->status === 'shipped'): ?> border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200
                                            <?php elseif($order->status === 'delivered'): ?> border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200
                                            <?php elseif($order->status === 'cancelled'): ?> border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400
                                            <?php endif; ?>">
                                            <?php echo e(ucfirst($order->status)); ?>

                                        </span>
                                    </td>
                                    <td class="px-3 py-1.5 text-right text-gray-900 dark:text-gray-50">
                                        ₹<?php echo e(number_format($order->grand_total, 2)); ?>

                                    </td>
                                    <td class="px-3 py-1.5 text-gray-600 dark:text-gray-300">
                                        <?php echo e(optional($order->placed_at ?? $order->created_at)->format('d M, H:i')); ?>

                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="5" class="px-3 py-3 text-center text-gray-500 dark:text-gray-400">
                                        No orders yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        Top customers (by revenue)
                    </p>
                    <span class="text-[10px] text-gray-500 dark:text-gray-400">
                        Paid orders only
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-[11px]">
                        <thead class="bg-gray-50 dark:bg-gray-950/40">
                            <tr>
                                <th class="px-3 py-1.5 text-left font-medium text-gray-500 dark:text-gray-400">Customer</th>
                                <th class="px-3 py-1.5 text-right font-medium text-gray-500 dark:text-gray-400">Orders</th>
                                <th class="px-3 py-1.5 text-right font-medium text-gray-500 dark:text-gray-400">Total spent</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <?php $__empty_1 = true; $__currentLoopData = $topCustomers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td class="px-3 py-1.5 text-gray-900 dark:text-gray-50">
                                        <?php echo e($row->user?->name ?? 'User #'.$row->user_id); ?>

                                        <?php if($row->user?->email): ?>
                                            <div class="text-[10px] text-gray-400">
                                                <?php echo e($row->user->email); ?>

                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-1.5 text-right text-gray-700 dark:text-gray-200">
                                        <?php echo e($row->order_count); ?>

                                    </td>
                                    <td class="px-3 py-1.5 text-right text-gray-900 dark:text-gray-50">
                                        ₹<?php echo e(number_format($row->total_spent, 2)); ?>

                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="3" class="px-3 py-3 text-center text-gray-500 dark:text-gray-400">
                                        No customer revenue data yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        
        <div class="space-y-3">
            
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        Low stock alerts
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-[11px]">
                        <thead class="bg-gray-50 dark:bg-gray-950/40">
                            <tr>
                                <th class="px-3 py-1.5 text-left font-medium text-gray-500 dark:text-gray-400">Product</th>
                                <th class="px-3 py-1.5 text-right font-medium text-gray-500 dark:text-gray-400">Stock</th>
                                <th class="px-3 py-1.5 text-right font-medium text-gray-500 dark:text-gray-400">Threshold</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <?php $__empty_1 = true; $__currentLoopData = $lowStockProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td class="px-3 py-1.5">
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
                                    <td class="px-3 py-1.5 text-right text-gray-900 dark:text-gray-50">
                                        <?php echo e((float) $p->stock_quantity); ?>

                                    </td>
                                    <td class="px-3 py-1.5 text-right text-gray-700 dark:text-gray-200">
                                        <?php echo e((float) $p->low_stock_threshold); ?>

                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="3" class="px-3 py-3 text-center text-gray-500 dark:text-gray-400">
                                        No low stock alerts.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        New customers
                    </p>
                </div>
                <div class="space-y-1.5">
                    <?php $__empty_1 = true; $__currentLoopData = $recentCustomers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cust): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="flex items-center justify-between text-[11px] text-gray-700 dark:text-gray-200">
                            <div>
                                <p class="text-gray-900 dark:text-gray-50">
                                    <?php echo e($cust->name); ?>

                                </p>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400">
                                    <?php echo e($cust->email); ?>

                                </p>
                            </div>
                            <span class="text-[10px] text-gray-400">
                                <?php echo e($cust->created_at->format('d M')); ?>

                            </span>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            No customers yet.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function () {
        const revenueCtx = document.getElementById('revenueChart');
        if (revenueCtx) {
            const labels = <?php echo json_encode($monthlyLabels->values(), 15, 512) ?>;
            const values = <?php echo json_encode($monthlyValues->values(), 15, 512) ?>;

            new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        borderWidth: 2,
                        tension: 0.35,
                        pointRadius: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: {
                            ticks: { font: { size: 9 } },
                            grid: { display: false }
                        },
                        y: {
                            ticks: {
                                font: { size: 9 },
                                callback: function (value) {
                                    return '₹' + value;
                                }
                            },
                            grid: { borderDash: [4, 4] }
                        }
                    }
                }
            });
        }

        const statusCtx = document.getElementById('orderStatusChart');
        if (statusCtx) {
            const labels = <?php echo json_encode($statusDisplayLabels, 15, 512) ?>;
            const values = <?php echo json_encode($statusValues, 15, 512) ?>;

            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    cutout: '62%'
                }
            });
        }
    })();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/dashboard/admin.blade.php ENDPATH**/ ?>