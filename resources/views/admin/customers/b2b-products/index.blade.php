@extends('layouts.company')

@section('title', 'B2B Catalog')

@section('content')
@php
    $fmt = fn($q) => rtrim(rtrim(number_format((float)$q, 2), '0'), '.');
@endphp
<div class="max-w-6xl mx-auto px-4 py-6 text-xs space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                B2B Catalog: {{ $user->name }}
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Assign product-level access or specific sellable units such as 10pc pack, 20pc pack, or box. MOQ and price can vary per option.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.customers.b2b-products.create', $user) }}"
               class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                Add option
            </a>

            @if(\Illuminate\Support\Facades\Route::has('admin.b2b.prices.index'))
                <a href="{{ route('admin.b2b.prices.index', $user) }}"
                   class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                    Manage B2B prices
                </a>
            @endif

            <a href="{{ url()->previous() }}"
               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                Back
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
        <table class="min-w-full text-[11px]">
            <thead class="bg-gray-50 dark:bg-gray-950/40">
                <tr class="text-left text-gray-600 dark:text-gray-300">
                    <th class="px-3 py-2 font-medium">Product</th>
                    <th class="px-3 py-2 font-medium">Sellable unit</th>
                    <th class="px-3 py-2 font-medium">MOQ</th>
                    <th class="px-3 py-2 font-medium">Price</th>
                    <th class="px-3 py-2 font-medium">Active</th>
                    <th class="px-3 py-2 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($rows as $row)
                    @php
                        $price = $priceOverrides->get($row->product_id . '|' . ((int)($row->product_sell_unit_id ?? 0)));
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                        <td class="px-3 py-2 text-gray-900 dark:text-gray-50">
                            <div class="font-medium">{{ $row->product?->name ?? ('Product #' . $row->product_id) }}</div>
                            @if($row->product?->sku)
                                <div class="text-[10px] text-gray-400">{{ $row->product->sku }}</div>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                            @if($row->sellUnit)
                                <div class="font-medium">{{ $row->sellUnit->display_label }}</div>
                                <div class="text-[10px] text-gray-400">{{ $row->sellUnit->pricing_unit }}</div>
                            @else
                                <span class="text-gray-400">Product-level</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                            {{ $fmt($row->min_order_quantity ?? 1) }}
                        </td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                            @if($price)
                                ₹{{ number_format((float)$price->price, 2) }}
                            @else
                                <span class="text-gray-400">Fallback / not set</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            @if($row->is_active)
                                <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200">Active</span>
                            @else
                                <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-[10px] text-gray-600 dark:bg-gray-800 dark:text-gray-300">Inactive</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-right">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route('admin.customers.b2b-products.edit', [$user, $row]) }}"
                                   class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('admin.customers.b2b-products.destroy', [$user, $row]) }}" onsubmit="return confirm('Remove this B2B catalog option?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-[11px] px-3 py-1 rounded-full border border-red-300 text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-200 dark:hover:bg-red-900/20">
                                        Remove
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                            No B2B catalog options assigned yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $rows->links() }}
    </div>
</div>
@endsection
