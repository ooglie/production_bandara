@extends('layouts.company')

@section('title', 'Product variants')

@section('breadcrumb')
    Admin · Products · {{ $product->name }} · Variants
@endsection

@section('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                    Variants for: {{ $product->name }}
                </h1>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                    Base SKU: {{ $product->sku ?: '—' }} · Base price: ₹{{ number_format($product->base_price, 2) }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.products.index', $product) }}"
                   class="text-[11px] text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">
                    Products home
                </a>
                <p class="text-[11px] text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">::</p>
                <a href="{{ route('admin.products.edit', $product) }}"
                   class="text-[11px] text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">
                    Back to product
                </a>

                <a href="{{ route('admin.products.variants.create', $product) }}"
                   class="inline-flex items-center px-3 py-1.5 text-xs rounded border border-gray-300 dark:border-gray-700 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-200">
                    + New variant
                </a>
            </div>
        </div>

        @if(session('status'))
            <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="overflow-x-auto border border-gray-200 dark:border-gray-800 rounded-lg text-xs">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr class="text-[11px] uppercase text-gray-500 dark:text-gray-400">
                        <th class="px-3 py-2 text-left">SKU</th>
                        <th class="px-3 py-2 text-left">Name</th>
                        <th class="px-3 py-2 text-right">Price (₹)</th>
                        <th class="px-3 py-2 text-right">Stock</th>
                        <th class="px-3 py-2 text-center">Status</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-white dark:bg-gray-950">
                    @forelse($variants as $variant)
                        <tr>
                            <td class="px-3 py-2 align-top text-gray-800 dark:text-gray-100">
                                {{ $variant->sku }}
                            </td>
                            <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300">
                                {{ $variant->name ?: '—' }}
                            </td>
                            <td class="px-3 py-2 align-top text-right text-gray-800 dark:text-gray-100">
                                ₹{{ number_format($variant->price, 2) }}
                            </td>
                            <td class="px-3 py-2 align-top text-right text-gray-700 dark:text-gray-300">
                                {{ $variant->stock_quantity ?? '—' }}
                            </td>
                            <td class="px-3 py-2 align-top text-center">
                                @if($variant->is_active)
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 px-2 py-0.5 text-[11px]">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 px-2 py-0.5 text-[11px]">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-2 align-top text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('admin.variants.edit', $variant) }}"
                                       class="text-[11px] text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
                                        Edit
                                    </a>
                                    <form method="POST"
                                          action="{{ route('admin.variants.destroy', $variant) }}"
                                          onsubmit="return confirm('Delete this variant?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-[11px] text-red-600 hover:text-red-700">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-xs text-gray-500 dark:text-gray-400">
                                No variants for this product yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{-- {{ $variants->links() }} --}}
            @if($variants instanceof \Illuminate\Contracts\Pagination\Paginator || $variants instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                {{ $variants->links() }}
            @endif
        </div>
    </div>
@endsection
