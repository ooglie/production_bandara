@php
    $user = auth()->user();
    $q = trim((string) request('b2b_q', ''));

    /** @var \App\Services\PricingService $pricing */
    $pricing = app(\App\Services\PricingService::class);

    // Unified storefront: all active products may appear for B2B users.
    // Customer-specific rows override price/MOQ; they no longer gate catalogue visibility.
    $products = \App\Models\Product::query()
        ->where('is_active', true)
        ->when($q !== '', function ($p) use ($q) {
            $p->where(function ($x) use ($q) {
                $x->where('name', 'like', '%' . $q . '%')
                  ->orWhere('sku', 'like', '%' . $q . '%')
                  ->orWhere('slug', 'like', '%' . $q . '%');
            });
        })
        ->orderBy('name')
        ->paginate(15, ['*'], 'b2b_page')
        ->withQueryString();

    $productIds = $products->getCollection()->pluck('id')->all();

    $variantsByProduct = collect();
    if (!empty($productIds)) {
        $variantsByProduct = \App\Models\ProductVariant::query()
            ->whereIn('product_id', $productIds)
            ->orderBy('id')
            ->get(['id','product_id','sku'])
            ->groupBy('product_id');
    }
@endphp

<div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-3">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                Quick Order (B2B)
            </h2>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                All active catalogue products are shown. Your B2B price and MOQ are applied automatically.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('cart.index') }}"
               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                View cart
            </a>
        </div>
    </div>

    {{-- Search --}}
    <form method="GET" action="{{ url()->current() }}" class="flex flex-col sm:flex-row gap-2 sm:items-center">
        <div class="flex-1">
            <input type="text" name="b2b_q" value="{{ $q }}"
                   placeholder="Search products…"
                   class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
        </div>
        <button type="submit"
                class="text-[11px] px-4 py-2 rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-200">
            Search
        </button>

        @if($q !== '')
            <a href="{{ url()->current() }}"
               class="text-[11px] px-4 py-2 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                Clear
            </a>
        @endif
    </form>

    @if($errors->has('quick_order'))
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
            {{ $errors->first('quick_order') }}
        </div>
    @endif

    @if($products->count() === 0)
        <div class="rounded border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 p-4">
            <p class="text-[11px] text-gray-600 dark:text-gray-300">
                No active products found for this search.
            </p>
        </div>
    @else

        <form method="POST" action="{{ route('dashboard.b2b.quickAdd') }}" class="space-y-3">
            @csrf

            <div class="flex items-center justify-between">
                <label class="inline-flex items-center gap-2 text-[11px] text-gray-700 dark:text-gray-200">
                    <input type="checkbox" id="b2b-select-all">
                    <span>Select all</span>
                </label>

                <button type="submit"
                        class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                    Add selected to cart
                </button>
            </div>

            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
                <table class="min-w-full text-[11px]">
                    <thead class="bg-gray-50 dark:bg-gray-950/40">
                    <tr class="text-left text-gray-600 dark:text-gray-300">
                        <th class="px-3 py-2 font-medium">Select</th>
                        <th class="px-3 py-2 font-medium">Product</th>
                        <th class="px-3 py-2 font-medium">Variant</th>
                        <th class="px-3 py-2 font-medium">MOQ</th>
                        <th class="px-3 py-2 font-medium">Price</th>
                        <th class="px-3 py-2 font-medium">Qty</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">

                    @foreach($products as $i => $p)
                        @php
                            $pid = (int) $p->id;
                            $productQuote = $pricing->quote($user, $p);
                            $moq = (float) ($productQuote['moq'] ?? 1);
                            if ($moq <= 0) $moq = 1;

                            $variants = $variantsByProduct->get($pid, collect());
                            $isVariable = ((string)($p->type ?? 'simple') !== 'simple') && $variants->count() > 0;

                            $basePrice = (float) $pricing->priceFor($user, $p, null);
                            $priceLabel = $isVariable ? ('From ₹' . number_format($basePrice, 2)) : ('₹' . number_format($basePrice, 2));

                            // Unique index per page so inputs don't collide
                            $rowIndex = $i;
                        @endphp

                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                            <td class="px-3 py-2 align-top">
                                <input type="checkbox"
                                       class="b2b-line-check"
                                       name="lines[{{ $rowIndex }}][enabled]"
                                       value="1">
                                <input type="hidden" name="lines[{{ $rowIndex }}][product_id]" value="{{ $pid }}">
                            </td>

                            <td class="px-3 py-2 align-top text-gray-900 dark:text-gray-50">
                                <div class="font-medium">{{ $p->name }}</div>
                                @if(!empty($p->sku))
                                    <div class="text-[10px] text-gray-400">{{ $p->sku }}</div>
                                @endif
                            </td>

                            <td class="px-3 py-2 align-top">
                                @if($isVariable)
                                    <select name="lines[{{ $rowIndex }}][product_variant_id]"
                                            class="b2b-variant-select w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                                            data-price-target="b2b-price-{{ $rowIndex }}">
                                        <option value="">Select variant…</option>
                                        @foreach($variants as $v)
                                            @php
                                                $vId = (int) $v->id;
                                                $vLabel = $v->sku ?? ('Variant #' . $vId);
                                                $vPrice = (float) $pricing->priceFor($user, $p, $v);
                                            @endphp
                                            <option value="{{ $vId }}" data-price="{{ number_format($vPrice, 2, '.', '') }}">
                                                {{ $vLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <span class="text-[10px] text-gray-400">—</span>
                                @endif
                            </td>

                            <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-200">
                                {{ rtrim(rtrim(number_format($moq, 2), '0'), '.') }}
                            </td>

                            <td class="px-3 py-2 align-top text-gray-900 dark:text-gray-50">
                                <span id="b2b-price-{{ $rowIndex }}">{{ $priceLabel }}</span>
                            </td>

                            <td class="px-3 py-2 align-top">
                                <input type="number"
                                       step="0.01"
                                       min="{{ number_format($moq, 2, '.', '') }}"
                                       name="lines[{{ $rowIndex }}][quantity]"
                                       value="{{ number_format($moq, 2, '.', '') }}"
                                       class="w-28 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                            </td>
                        </tr>
                    @endforeach

                    </tbody>
                </table>
            </div>

            <div class="text-[10px] text-gray-500 dark:text-gray-400">
                Tip: If you enter less than MOQ, it will be automatically increased to MOQ when added to cart.
            </div>

            <div class="flex items-center justify-end">
                <button type="submit"
                        class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                    Add selected to cart
                </button>
            </div>
        </form>

        <div>
            {{ $products->links() }}
        </div>
    @endif
</div>

<script>
(function () {
    // Select all
    const selectAll = document.getElementById('b2b-select-all');
    const checks = document.querySelectorAll('.b2b-line-check');

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checks.forEach(cb => cb.checked = selectAll.checked);
        });
    }

    // Variant price display
    document.querySelectorAll('.b2b-variant-select').forEach(function (sel) {
        sel.addEventListener('change', function () {
            const targetId = sel.getAttribute('data-price-target');
            const target = document.getElementById(targetId);
            if (!target) return;

            const opt = sel.options[sel.selectedIndex];
            const price = opt ? opt.getAttribute('data-price') : null;

            if (price) {
                target.textContent = '₹' + parseFloat(price).toFixed(2);
            }
        });
    });
})();
</script>
