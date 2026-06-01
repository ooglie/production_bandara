@extends('layouts.company')

@php
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

@endphp

@section('title', 'Admin dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-5 text-xs space-y-4">
    {{-- Header --}}
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
            <a href="{{ route('admin.orders.index') }}"
               class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                Orders
            </a>
            <a href="{{ route('admin.products.index') }}"
               class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                Products
            </a>
            <a href="{{ route('admin.users.index') }}"
               class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                Customers
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    {{-- Small KPI strip --}}
    <div class="grid gap-2 sm:gap-3 grid-cols-2 md:grid-cols-4">
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-2.5">
            <p class="text-[10px] text-gray-500 dark:text-gray-400 mb-0.5">
                Revenue today
            </p>
            <p class="text-lg font-semibold tracking-tight text-gray-900 dark:text-gray-50">
                ₹{{ number_format($revenueToday, 0) }}
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
                ₹{{ number_format($revenueThisMonth, 0) }}
            </p>
            <p class="mt-0.5 text-[10px] text-gray-400">
                Since {{ now()->startOfMonth()->format('d M') }}.
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-2.5">
            <div class="{{ $ordersTodayUrl ? 'cursor-pointer' : '' }}">
                <p class="text-[10px] text-gray-500 dark:text-gray-400 mb-0.5">
                    Orders today
                </p>

                @if($ordersTodayUrl)
                    <a href="{{ $ordersTodayUrl }}" class="inline-flex items-baseline gap-1 text-gray-900 dark:text-gray-50 hover:underline">
                        <span class="text-lg font-semibold tracking-tight">
                            {{ $ordersTodayCount }}
                        </span>
                        <span class="text-[10px] text-gray-400">
                            view
                        </span>
                    </a>
                @else
                    <span class="text-lg font-semibold tracking-tight text-gray-900 dark:text-gray-50">
                        {{ $ordersTodayCount }}
                    </span>
                @endif

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
                {{ $totalCustomers }}
            </p>
            <p class="mt-0.5 text-[10px] text-gray-400">
                {{ $activeCustomersThisMonth }} active this month.
            </p>
        </div>
    </div>

    {{-- Charts row --}}
    <div class="grid gap-3 lg:grid-cols-[2fr,1fr] items-stretch">
        {{-- Revenue chart --}}
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

        {{-- Status chart + counters --}}
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
                        <span class="font-semibold">{{ $ordersByStatus['processing'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Shipped</span>
                        <span class="font-semibold">{{ $ordersByStatus['shipped'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Delivered</span>
                        <span class="font-semibold">{{ $ordersByStatus['delivered'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Cancelled</span>
                        <span class="font-semibold">{{ $ordersByStatus['cancelled'] ?? 0 }}</span>
                    </div>
                    <p class="pt-1 mt-1 text-[10px] text-gray-400 border-t border-dashed border-gray-200 dark:border-gray-800">
                        Total orders: {{ $ordersTotalCount }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Lower section: tables / lists --}}
    <div class="grid gap-3 xl:grid-cols-[1.6fr,1.1fr]">
        {{-- LEFT: recent orders + top customers --}}
        <div class="space-y-3">
            {{-- Recent orders --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        Recent orders
                    </p>
                    <a href="{{ route('admin.orders.index') }}"
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
                            @forelse($recentOrders as $order)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                                    <td class="px-3 py-1.5">
                                        <a href="{{ route('admin.orders.show', $order) }}"
                                           class="text-gray-900 dark:text-gray-50 hover:underline">
                                            {{ $order->order_number }}
                                        </a>
                                        <div class="text-[10px] text-gray-400">
                                            #{{ $order->id }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-1.5 text-gray-700 dark:text-gray-200">
                                        {{ $order->user?->name ?? '—' }}
                                    </td>
                                    <td class="px-3 py-1.5">
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px]
                                            @if($order->status === 'processing') border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-800 dark:bg-sky-900/30 dark:text-sky-200
                                            @elseif($order->status === 'shipped') border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200
                                            @elseif($order->status === 'delivered') border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200
                                            @elseif($order->status === 'cancelled') border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400
                                            @endif">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-1.5 text-right text-gray-900 dark:text-gray-50">
                                        ₹{{ number_format($order->grand_total, 2) }}
                                    </td>
                                    <td class="px-3 py-1.5 text-gray-600 dark:text-gray-300">
                                        {{ optional($order->placed_at ?? $order->created_at)->format('d M, H:i') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-3 text-center text-gray-500 dark:text-gray-400">
                                        No orders yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Top customers --}}
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
                            @forelse($topCustomers as $row)
                                <tr>
                                    <td class="px-3 py-1.5 text-gray-900 dark:text-gray-50">
                                        {{ $row->user?->name ?? 'User #'.$row->user_id }}
                                        @if($row->user?->email)
                                            <div class="text-[10px] text-gray-400">
                                                {{ $row->user->email }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-1.5 text-right text-gray-700 dark:text-gray-200">
                                        {{ $row->order_count }}
                                    </td>
                                    <td class="px-3 py-1.5 text-right text-gray-900 dark:text-gray-50">
                                        ₹{{ number_format($row->total_spent, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-3 py-3 text-center text-gray-500 dark:text-gray-400">
                                        No customer revenue data yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- RIGHT: stock + new customers --}}
        <div class="space-y-3">
            {{-- Low stock --}}
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
                            @forelse($lowStockProducts as $p)
                                <tr>
                                    <td class="px-3 py-1.5">
                                        <a href="{{ route('admin.products.edit', $p) }}"
                                           class="text-gray-900 dark:text-gray-50 hover:underline">
                                            {{ $p->name }}
                                        </a>
                                        @if($p->sku)
                                            <div class="text-[10px] text-gray-400">
                                                SKU: {{ $p->sku }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-1.5 text-right text-gray-900 dark:text-gray-50">
                                        {{ (float) $p->stock_quantity }}
                                    </td>
                                    <td class="px-3 py-1.5 text-right text-gray-700 dark:text-gray-200">
                                        {{ (float) $p->low_stock_threshold }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-3 py-3 text-center text-gray-500 dark:text-gray-400">
                                        No low stock alerts.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- New customers --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        New customers
                    </p>
                </div>
                <div class="space-y-1.5">
                    @forelse($recentCustomers as $cust)
                        <div class="flex items-center justify-between text-[11px] text-gray-700 dark:text-gray-200">
                            <div>
                                <p class="text-gray-900 dark:text-gray-50">
                                    {{ $cust->name }}
                                </p>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400">
                                    {{ $cust->email }}
                                </p>
                            </div>
                            <span class="text-[10px] text-gray-400">
                                {{ $cust->created_at->format('d M') }}
                            </span>
                        </div>
                    @empty
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            No customers yet.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Charts --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function () {
        const revenueCtx = document.getElementById('revenueChart');
        if (revenueCtx) {
            const labels = @json($monthlyLabels->values());
            const values = @json($monthlyValues->values());

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
            const labels = @json($statusDisplayLabels);
            const values = @json($statusValues);

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
@endsection
