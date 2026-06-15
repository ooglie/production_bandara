@extends('layouts.company')

@section('title', 'Product sellable formats')
@section('breadcrumb', 'Admin · Products · Sellable formats')

@section('content')
@php
    $gstRate = (float) ($product->effective_gst_rate ?? $product->gst_rate ?? 0);
    $factor = 1 + ($gstRate / 100);
@endphp

<div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">Sellable formats</h1>
            <p class="mt-1 text-[12px] text-gray-500 dark:text-gray-400">
                {{ $product->name }} · define exactly how customers buy this item: fixed weight packs, fixed piece packs, or variable-weight prepacked formats.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.products.edit', $product) }}" class="rounded border border-gray-300 px-4 py-2 text-xs hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">Back to product</a>
            <a href="{{ route('admin.products.sell-units.create', $product) }}" class="rounded bg-gray-900 px-4 py-2 text-xs font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">+ New format</a>
        </div>
    </div>

    @if(session('status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-xs text-blue-800 dark:border-blue-900/60 dark:bg-blue-950/30 dark:text-blue-200">
        Vendor invoices receive bulk or finished stock. Repack/production converts bulk lots into these formats. Sellable formats are admin/inventory records only and do not create storefront variant dropdowns.
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 text-xs dark:border-gray-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr class="text-left text-[11px] uppercase text-gray-500 dark:text-gray-400">
                    <th class="px-3 py-2">Format</th>
                    <th class="px-3 py-2">Type</th>
                    <th class="px-3 py-2 text-right">Pack value</th>
                    <th class="px-3 py-2 text-right">MRP</th>
                    <th class="px-3 py-2 text-right">Sell price</th>
                    <th class="px-3 py-2">Channels</th>
                    <th class="px-3 py-2 text-center">Status</th>
                    <th class="px-3 py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-gray-950">
                @forelse($product->sellUnits as $unit)
                    @php
                        $baseDisplay = $unit->base_price !== null
                            ? ((bool) ($unit->b2c_price_includes_gst ?? true) && $factor > 0 ? (float) $unit->base_price * $factor : (float) $unit->base_price)
                            : null;
                        $mrpDisplay = $unit->mrp_price !== null
                            ? ((bool) ($unit->b2c_price_includes_gst ?? true) && $factor > 0 ? (float) $unit->mrp_price * $factor : (float) $unit->mrp_price)
                            : null;
                        $saleTypeLabel = $unit->sale_type_label ?? str_replace('_', ' ', ucfirst($unit->sale_type ?? 'fixed piece pack'));
                        $packValue = $unit->pieces_per_unit !== null
                            ? rtrim(rtrim($unit->pieces_per_unit, '0'), '.') . ' pcs'
                            : ($unit->weight_per_unit_kg !== null ? rtrim(rtrim($unit->weight_per_unit_kg, '0'), '.') . ' kg' : '—');
                    @endphp
                    <tr>
                        <td class="px-3 py-2 align-top">
                            <div class="font-medium text-gray-900 dark:text-gray-50">{{ $unit->name }}</div>
                            <div class="text-[10px] text-gray-500 dark:text-gray-400">SKU: {{ $unit->sku ?: 'auto' }} · Barcode: {{ $unit->barcode ?: '—' }}</div>
                            @if($unit->notes)
                                <div class="mt-1 max-w-md text-[10px] text-gray-500 dark:text-gray-400 line-clamp-2">{{ $unit->notes }}</div>
                            @endif
                        </td>
                        <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300">{{ $saleTypeLabel }}</td>
                        <td class="px-3 py-2 align-top text-right text-gray-700 dark:text-gray-300">{{ $packValue }}</td>
                        <td class="px-3 py-2 align-top text-right text-gray-700 dark:text-gray-300">{{ $mrpDisplay !== null ? '₹' . number_format($mrpDisplay, 2) : '—' }}</td>
                        <td class="px-3 py-2 align-top text-right text-gray-700 dark:text-gray-300">{{ $baseDisplay !== null ? '₹' . number_format($baseDisplay, 2) : '—' }}</td>
                        <td class="px-3 py-2 align-top">
                            <div class="flex flex-wrap gap-1">
                                @if($unit->is_retail_visible)<span class="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">B2C</span>@endif
                                @if($unit->is_b2b_visible)<span class="rounded-full bg-purple-50 px-2 py-0.5 text-[10px] text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">B2B</span>@endif
                            </div>
                        </td>
                        <td class="px-3 py-2 align-top text-center">
                            @if($unit->is_active)
                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">Active</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] text-gray-600 dark:bg-gray-800 dark:text-gray-300">Inactive</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 align-top text-right">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route('admin.sell-units.edit', $unit) }}" class="text-[11px] text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-100">Edit</a>
                                <form method="POST" action="{{ route('admin.sell-units.destroy', $unit) }}" onsubmit="return confirm('Remove this sellable format?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-[11px] text-red-600 hover:text-red-700">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-3 py-8 text-center text-xs text-gray-500 dark:text-gray-400">
                            No sellable formats yet. Create 500g packs, 1kg slabs, 10pc packs, B2B boxes, etc.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
