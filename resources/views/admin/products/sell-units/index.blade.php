@extends('layouts.company')

@section('title', 'Product sellable units')
@section('breadcrumb', 'Admin · Products · Sellable units')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">Sellable units</h1>
            <p class="mt-1 text-[12px] text-gray-500 dark:text-gray-400">
                {{ $product->name }} · define pack/box/request units without changing the current B2C checkout flow.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.products.edit', $product) }}" class="rounded border border-gray-300 px-4 py-2 text-xs hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">Back to product</a>
            <a href="{{ route('admin.products.variants.index', $product) }}" class="rounded border border-gray-300 px-4 py-2 text-xs hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">Variants</a>
            <a href="{{ route('admin.products.sell-units.create', $product) }}" class="rounded bg-gray-900 px-4 py-2 text-xs font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">+ New sellable unit</a>
        </div>
    </div>

    @if(session('status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
        Define pack, box, kg, and request units that can be priced for retail and B2B customers. B2C storefront behavior remains unchanged unless variants are explicitly linked to these units.
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 text-xs dark:border-gray-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr class="text-left text-[11px] uppercase text-gray-500 dark:text-gray-400">
                    <th class="px-3 py-2">Unit</th>
                    <th class="px-3 py-2">Type</th>
                    <th class="px-3 py-2 text-right">Pieces</th>
                    <th class="px-3 py-2 text-right">Weight</th>
                    <th class="px-3 py-2">Channels</th>
                    <th class="px-3 py-2 text-right">Linked variants</th>
                    <th class="px-3 py-2 text-center">Status</th>
                    <th class="px-3 py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-gray-950">
                @forelse($product->sellUnits as $unit)
                    <tr>
                        <td class="px-3 py-2 align-top">
                            <div class="font-medium text-gray-900 dark:text-gray-50">{{ $unit->name }}</div>
                            <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                SKU: {{ $unit->sku ?: '—' }} · Barcode: {{ $unit->barcode ?: '—' }}
                            </div>
                            @if($unit->notes)
                                <div class="mt-1 max-w-md text-[10px] text-gray-500 dark:text-gray-400 line-clamp-2">{{ $unit->notes }}</div>
                            @endif
                        </td>
                        <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300">
                            <div>{{ str_replace('_', ' ', ucfirst($unit->unit_type)) }}</div>
                            <div class="text-[10px] text-gray-500 dark:text-gray-400">{{ str_replace('_', ' ', ucfirst($unit->pricing_unit)) }}</div>
                        </td>
                        <td class="px-3 py-2 align-top text-right text-gray-700 dark:text-gray-300">{{ $unit->pieces_per_unit !== null ? rtrim(rtrim($unit->pieces_per_unit, '0'), '.') : '—' }}</td>
                        <td class="px-3 py-2 align-top text-right text-gray-700 dark:text-gray-300">{{ $unit->weight_per_unit_kg !== null ? rtrim(rtrim($unit->weight_per_unit_kg, '0'), '.') . ' kg' : '—' }}</td>
                        <td class="px-3 py-2 align-top">
                            <div class="flex flex-wrap gap-1">
                                @if($unit->is_retail_visible)
                                    <span class="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">B2C</span>
                                @endif
                                @if($unit->is_b2b_visible)
                                    <span class="rounded-full bg-purple-50 px-2 py-0.5 text-[10px] text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">B2B</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-3 py-2 align-top text-right text-gray-700 dark:text-gray-300">{{ $unit->variants_count ?? 0 }}</td>
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
                                <form method="POST" action="{{ route('admin.sell-units.destroy', $unit) }}" onsubmit="return confirm('Remove this sellable unit? Existing variants are only unlinked, not deleted.');">
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
                            No sellable units yet. Create units such as 10pc pack, 20pc pack, box, or request by kg.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
