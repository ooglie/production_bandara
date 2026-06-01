@extends('layouts.company')

@section('title', 'Customer Prices')

@section('content')
@php
    $fmtDate = function($d) {
        if (!$d) return '—';
        try {
            return \Carbon\Carbon::parse($d)->format('d M Y');
        } catch (\Throwable $e) {
            return (string) $d;
        }
    };
@endphp

<div class="max-w-7xl mx-auto px-4 py-5 text-xs space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-50">
                Customer-specific Prices
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Customer: <span class="text-gray-900 dark:text-gray-50 font-medium">{{ $user->name ?? '—' }}</span>
                <span class="text-gray-400">(#{{ $user->id }})</span>
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.b2b.customers.index') }}"
               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                Back
            </a>
            <a href="{{ route('admin.b2b.moq.index', $user) }}"
               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                MOQ
            </a>
            <a href="{{ route('admin.b2b.prices.create', $user) }}"
               class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                Add price
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    {{-- Filter --}}
    <form method="GET" class="flex flex-wrap items-center gap-2">
        <span class="text-[11px] text-gray-600 dark:text-gray-300">Filter by product:</span>
        <select name="product_id"
                class="rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px]"
                onchange="this.form.submit()">
            <option value="">All products</option>
            @foreach($products as $p)
                <option value="{{ $p->id }}" @selected((int)$productId === (int)$p->id)>
                    {{ $p->name }} @if($p->sku) ({{ $p->sku }}) @endif
                </option>
            @endforeach
        </select>

        @if($productId)
            <a href="{{ route('admin.b2b.prices.index', $user) }}"
               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                Clear
            </a>
        @endif
    </form>

    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
        <p class="text-[11px] text-gray-600 dark:text-gray-300">
            Pricing priority: <span class="font-medium text-gray-900 dark:text-gray-50">Variant override</span> (if set) → <span class="font-medium text-gray-900 dark:text-gray-50">Product override</span> → product base pricing.
            For overlapping valid periods, the latest <code>valid_from</code> wins.
        </p>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
        <table class="min-w-full text-[11px]">
            <thead class="bg-gray-50 dark:bg-gray-950/40">
            <tr>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Product</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Scope</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Price</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Validity</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Active</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Actions</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse($prices as $row)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                    <td class="px-3 py-2">
                        <div class="font-medium text-gray-900 dark:text-gray-50">
                            {{ $row->product?->name ?? 'Product' }}
                        </div>
                        <div class="text-[10px] text-gray-400">
                            Product ID: {{ $row->product_id }}
                        </div>
                    </td>
                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                        @if($row->product_sell_unit_id)
                            <div class="font-medium">{{ $row->sellUnit?->display_label ?? ('Sell unit #' . $row->product_sell_unit_id) }}</div>
                            <div class="text-[10px] text-gray-400">Sellable unit</div>
                        @elseif($row->product_variant_id)
                            {{ $row->productVariant?->sku ?? ('Variant #' . $row->product_variant_id) }}
                        @else
                            <span class="text-[10px] px-2 py-0.5 rounded-full border border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300">
                                Product-level
                            </span>
                        @endif
                    </td>
                    <td class="px-3 py-2 text-gray-900 dark:text-gray-50">
                        ₹{{ number_format((float)$row->price, 2) }}
                        <span class="text-[10px] text-gray-400">{{ $row->currency ?? 'INR' }}</span>
                    </td>
                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                        <div class="text-[11px]">
                            {{ $fmtDate($row->valid_from) }} → {{ $fmtDate($row->valid_to) }}
                        </div>
                    </td>
                    <td class="px-3 py-2">
                        @if($row->is_active)
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                                Active
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400">
                                Inactive
                            </span>
                        @endif
                    </td>
                    <td class="px-3 py-2">
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('admin.b2b.prices.edit', [$user, $row]) }}"
                               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                Edit
                            </a>

                            <form method="POST" action="{{ route('admin.b2b.prices.destroy', [$user, $row]) }}"
                                  onsubmit="return confirm('Delete this price override?');">
                                @csrf
                                @method('DELETE')
                                <button class="text-[11px] px-3 py-1 rounded-full border border-red-300 text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-200 dark:hover:bg-red-900/20">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                        No customer-specific prices set yet.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $prices->links() }}
    </div>
</div>
@endsection
