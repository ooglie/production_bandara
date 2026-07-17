@extends('layouts.company')

@section('title', 'Product Sales Report')
@section('breadcrumb', 'Admin · Reports · Product Sales')

@section('content')
@php
    $money = static fn($value) => '₹' . number_format((float) $value, 2);
    $num = static function ($value, int $decimals = 3) {
        $formatted = number_format((float) $value, $decimals, '.', '');
        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    };
@endphp

<div class="max-w-7xl mx-auto px-4 py-5 text-xs space-y-5">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Product Sales</h1>
            <p class="text-[12px] text-gray-500 dark:text-gray-400">
                Product and variant sales across simple, variant-choice and slab/catchweight items.
            </p>
        </div>
        <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center rounded-xl border border-gray-300 px-3 py-2 text-[12px] hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-900">
            ← Reports
        </a>
    </div>

    <form method="GET" action="{{ route('admin.reports.product-sales') }}" class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
        <div class="grid gap-3 md:grid-cols-8 items-end">
            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">From</label>
                <input type="date" name="from" value="{{ $from }}" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[12px] dark:border-gray-700 dark:bg-gray-900">
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">To</label>
                <input type="date" name="to" value="{{ $to }}" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[12px] dark:border-gray-700 dark:bg-gray-900">
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">Status</label>
                <select name="status" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[12px] dark:border-gray-700 dark:bg-gray-900">
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">Customer</label>
                <select name="customer_type" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[12px] dark:border-gray-700 dark:bg-gray-900">
                    @foreach($customerTypeOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['customer_type'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">Category</label>
                <select name="category_id" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[12px] dark:border-gray-700 dark:bg-gray-900">
                    <option value="0">All categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((int)($filters['category_id'] ?? 0) === (int)$category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">Product</label>
                <select name="product_id" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[12px] dark:border-gray-700 dark:bg-gray-900">
                    <option value="0">All products</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected((int)($filters['product_id'] ?? 0) === (int)$product->id)>
                            {{ $product->name }}{{ $product->sku ? ' · '.$product->sku : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button class="flex-1 rounded-lg border border-gray-900 bg-gray-900 px-3 py-2 text-[12px] font-semibold text-white hover:bg-gray-800 dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">
                    Apply
                </button>
                <a href="{{ route('admin.reports.product-sales.export', request()->query()) }}" class="rounded-lg border border-gray-300 px-3 py-2 text-[12px] hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-900">
                    CSV
                </a>
            </div>
        </div>
    </form>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">Product / variant lines</div>
            <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50">{{ number_format($summary['lines']) }}</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">Quantity sold</div>
            <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50">{{ $num($summary['quantity_sold']) }}</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">Weight sold</div>
            <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50">{{ $num($summary['weight_sold_kg']) }} kg</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">Revenue</div>
            <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50">{{ $money($summary['revenue']) }}</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">GST / tax</div>
            <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50">{{ $money($summary['tax']) }}</div>
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950">
        <table class="min-w-full text-[12px]">
            <thead class="bg-gray-50 text-[11px] uppercase text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3 text-left">Product</th>
                    <th class="px-4 py-3 text-left">Variant / option</th>
                    <th class="px-4 py-3 text-left">SKU</th>
                    <th class="px-4 py-3 text-right">Qty sold</th>
                    <th class="px-4 py-3 text-right">Kg sold</th>
                    <th class="px-4 py-3 text-right">Revenue</th>
                    <th class="px-4 py-3 text-right">GST</th>
                    <th class="px-4 py-3 text-right">Orders</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($rows as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-50">{{ $row->product_name }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $row->variant_name ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $row->sku ?: '—' }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-200">{{ $num($row->quantity_sold) }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-200">{{ $num($row->weight_sold_kg) }}</td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-gray-50">{{ $money($row->revenue) }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-200">{{ $money($row->tax) }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-200">{{ number_format((int) $row->orders_count) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">No product sales found for the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
