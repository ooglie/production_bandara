@extends('layouts.company')

@section('title', 'Sales Summary Report')
@section('breadcrumb', 'Admin · Reports · Sales Summary')

@section('content')
@php
    $money = static fn($value) => '₹' . number_format((float) $value, 2);
@endphp

<div class="max-w-7xl mx-auto px-4 py-5 text-xs space-y-5">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Sales Summary</h1>
            <p class="text-[12px] text-gray-500 dark:text-gray-400">
                Order totals, delivery, discount, tax, payment and customer type split.
            </p>
        </div>
        <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center rounded-xl border border-gray-300 px-3 py-2 text-[12px] hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-900">
            ← Reports
        </a>
    </div>

    <form method="GET" action="{{ route('admin.reports.sales-summary') }}" class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
        <div class="grid gap-3 md:grid-cols-6 items-end">
            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">From</label>
                <input type="date" name="from" value="{{ $from }}" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[12px] dark:border-gray-700 dark:bg-gray-900">
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">To</label>
                <input type="date" name="to" value="{{ $to }}" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[12px] dark:border-gray-700 dark:bg-gray-900">
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">Order status</label>
                <select name="status" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[12px] dark:border-gray-700 dark:bg-gray-900">
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">Payment status</label>
                <select name="payment_status" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[12px] dark:border-gray-700 dark:bg-gray-900">
                    @foreach($paymentStatusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['payment_status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">Customer type</label>
                <select name="customer_type" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[12px] dark:border-gray-700 dark:bg-gray-900">
                    @foreach($customerTypeOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['customer_type'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button class="flex-1 rounded-lg border border-gray-900 bg-gray-900 px-3 py-2 text-[12px] font-semibold text-white hover:bg-gray-800 dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">
                    Apply
                </button>
                <a href="{{ route('admin.reports.sales-summary.export', request()->query()) }}" class="rounded-lg border border-gray-300 px-3 py-2 text-[12px] hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-900">
                    CSV
                </a>
            </div>
        </div>
    </form>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">Orders</div>
            <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50">{{ number_format($totals['orders']) }}</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">Revenue excluding cancelled</div>
            <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50">{{ $money($totals['revenue']) }}</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">Average order value</div>
            <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50">{{ $money($totals['average_order_value']) }}</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">Cancelled value</div>
            <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50">{{ $money($totals['cancelled_value']) }}</div>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        @foreach([
            'subtotal' => 'Subtotal',
            'discount' => 'Discounts / credits',
            'tax' => 'Tax',
            'delivery_fee' => 'Delivery fee',
            'handling_fee' => 'Handling fee',
        ] as $key => $label)
            <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
                <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ $label }}</div>
                <div class="mt-1 text-base font-semibold text-gray-900 dark:text-gray-50">{{ $money($totals[$key] ?? 0) }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        @foreach([
            'Payment method' => $paymentMethodRows,
            'Payment status' => $paymentStatusRows,
            'Customer type' => $customerTypeRows,
        ] as $title => $rows)
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950">
                <div class="border-b border-gray-200 px-4 py-3 text-sm font-semibold text-gray-900 dark:border-gray-800 dark:text-gray-50">{{ $title }}</div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-[12px]">
                        <thead class="bg-gray-50 text-[11px] uppercase text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-2 text-left">Name</th>
                                <th class="px-4 py-2 text-right">Orders</th>
                                <th class="px-4 py-2 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($rows as $row)
                                @php
                                    $name = $row->payment_method ?? $row->payment_status ?? $row->customer_type ?? 'unknown';
                                @endphp
                                <tr>
                                    <td class="px-4 py-2 text-gray-700 dark:text-gray-200">{{ ucfirst(str_replace('_', ' ', $name)) }}</td>
                                    <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-200">{{ number_format((int) $row->orders) }}</td>
                                    <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-50">{{ $money($row->amount ?? 0) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-4 py-6 text-center text-gray-500">No data for this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
