@extends('layouts.customer')

@section('title', config('app.name') . ' - Shop')

@section('content')
@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Facades\Route;

    $has = fn(string $r) => Route::has($r);

    $q    = request('q', '');
    $sort = request('sort', '');

    $rawCategories = request()->input('category', []);
    if (!is_array($rawCategories)) {
        $rawCategories = filled($rawCategories)
            ? explode(',', (string) $rawCategories)
            : [];
    }

    $selectedCategoryIds = collect($rawCategories)
        ->map(fn ($id) => (string) $id)
        ->filter()
        ->unique()
        ->values()
        ->all();

    $selectedCategories = collect($categories ?? [])
        ->filter(fn ($cat) => in_array((string) $cat->id, $selectedCategoryIds, true))
        ->values();

    // Guess/try common route names (adjust anytime)
    $cartAddUrl =
        $has('cart.items.store') ? route('cart.items.store')
        : ($has('cart.add') ? route('cart.add')
        : ($has('cart.store') ? route('cart.store') : null));

    $cartUrl =
        $has('cart.index') ? route('cart.index')
        : ($has('cart.show') ? route('cart.show') : null);

    $wishlistToggleUrl =
        $has('wishlist.toggle') ? route('wishlist.toggle')
        : ($has('wishlist.store') ? route('wishlist.store') : null);

    $wishlistUrl =
        $has('wishlist.index') ? route('wishlist.index')
        : ($has('wishlist') ? route('wishlist') : null);

    $loginUrl = $has('login') ? route('login') : null;

    // Helper to build URLs while preserving current query params
    $link = function(array $add = [], array $remove = []) {
        $query = request()->query();
        unset($query['page']); // always reset pagination on changes

        foreach ($remove as $k) {
            unset($query[$k]);
        }

        foreach ($add as $k => $v) {
            if ($v === null || $v === '' || (is_array($v) && count($v) === 0)) {
                unset($query[$k]);
            } else {
                $query[$k] = $v;
            }
        }

        $url = url()->current();
        $qs = http_build_query($query);

        return $qs ? ($url . '?' . $qs) : $url;
    };

    $shown = is_countable($products) ? $products->count() : 0;
    $total = (is_object($products) && method_exists($products, 'total')) ? (int) $products->total() : $shown;
    $singleCard = $shown === 1;

    $sortLabel = match($sort) {
        'price_asc'  => 'Lowest price',
        'price_desc' => 'Highest price',
        default      => 'Newest',
    };

    // Flag emoji from ISO alpha-2 country code (e.g. IN -> 🇮🇳)
    $flagEmoji = function (?string $code) {
        if (!function_exists('mb_convert_encoding')) return null;

        $code = strtoupper(trim((string) $code));
        if (!preg_match('/^[A-Z]{2}$/', $code)) return null;

        $a = 127397 + ord($code[0]);
        $b = 127397 + ord($code[1]);

        return mb_convert_encoding("&#{$a};&#{$b};", 'UTF-8', 'HTML-ENTITIES');
    };
@endphp

<div class="max-w-6xl mx-auto px-4 py-6 space-y-4">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Shop
            </h1>
            <div>
                {{-- <div class="text-[11px] font-medium text-gray-900 dark:text-gray-50">
                    Product results
                </div> --}}
                {{-- <div class="text-[10px] text-gray-500 dark:text-gray-400">
                    {{ $shown }} item(s) on this page
                </div> --}}
            </div>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                {{-- Browse all available frozen products. --}}
                @if($total > 0)
                    <span class="ml-1">Showing products {{ $shown }} of {{ $total }}.</span>
                @endif
            </p>
        </div>

        @if($total > 0)
            <div class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-1.5 text-[11px]">
                <span class="text-gray-500 dark:text-gray-400">Sorted by</span>
                <span class="ml-2 font-medium text-gray-900 dark:text-gray-50">{{ $sortLabel }}</span>
            </div>
        @endif
    </div>

    {{-- Controls --}}
    <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-3 space-y-3">

        {{-- Category chips --}}
        <div class="flex items-center gap-2 overflow-x-auto pb-1">
            <a href="{{ $link(['category' => null]) }}"
               class="shrink-0 inline-flex items-center rounded-sm border px-3 py-1 text-[11px]
                      {{ empty($selectedCategoryIds)
                            ? 'border-gray-900 bg-gray-900 text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900'
                            : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800' }}">
                All
            </a>

            @foreach($categories as $category)
                @php
                    $id = (string) $category->id;
                    $active = in_array($id, $selectedCategoryIds, true);

                    $nextCategories = $selectedCategoryIds;

                    if ($active) {
                        $nextCategories = array_values(array_filter(
                            $nextCategories,
                            fn ($v) => (string) $v !== $id
                        ));
                    } else {
                        $nextCategories[] = $id;
                    }
                @endphp

                <a href="{{ $link(['category' => $nextCategories]) }}"
                   class="shrink-0 inline-flex items-center rounded-sm border px-3 py-1 text-[11px]
                          {{ $active
                                ? 'border-gray-900 bg-gray-900 text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900'
                                : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800' }}">
                    {{ $category->name }}
                </a>
            @endforeach
        </div>

        {{-- Search + sort --}}
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-2">

            {{-- Search --}}
            <form method="GET" action="{{ url()->current() }}" class="w-full lg:max-w-md">
                @foreach($selectedCategoryIds as $selectedCategoryId)
                    <input type="hidden" name="category[]" value="{{ $selectedCategoryId }}">
                @endforeach

                @if(!empty($sort))
                    <input type="hidden" name="sort" value="{{ $sort }}">
                @endif

                <div class="relative">
                    <input
                        type="search"
                        name="q"
                        value="{{ $q }}"
                        placeholder="Search products…"
                        class="w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-4 py-2 pr-10 text-[12px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                    >

                    @if(!empty($q))
                        <a href="{{ $link(['q' => null]) }}"
                           class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
                           title="Clear search"
                           aria-label="Clear search">
                            ✕
                        </a>
                    @endif
                </div>

                <button type="submit" class="sr-only">Search</button>
            </form>

            {{-- Sort buttons --}}
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-[11px] text-gray-500 dark:text-gray-400 hidden lg:inline">
                    Sort:
                </span>

                <a href="{{ $link(['sort' => null]) }}"
                   class="inline-flex items-center rounded-sm border px-3 py-2 text-[11px]
                          {{ empty($sort)
                                ? 'border-gray-900 bg-gray-900 text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900'
                                : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800' }}">
                    Newest
                </a>

                <a href="{{ $link(['sort' => 'price_asc']) }}"
                   class="inline-flex items-center rounded-sm border px-3 py-2 text-[11px]
                          {{ $sort === 'price_asc'
                                ? 'border-gray-900 bg-gray-900 text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900'
                                : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800' }}">
                    Lowest price
                </a>

                <a href="{{ $link(['sort' => 'price_desc']) }}"
                   class="inline-flex items-center rounded-sm border px-3 py-2 text-[11px]
                          {{ $sort === 'price_desc'
                                ? 'border-gray-900 bg-gray-900 text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900'
                                : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800' }}">
                    Highest price
                </a>

                @if(!empty($q) || !empty($selectedCategoryIds) || !empty($sort))
                    <a href="{{ $link([], ['q','category','sort']) }}"
                       class="inline-flex items-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-2 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                        Clear
                    </a>
                @endif
            </div>
        </div>

        {{-- Active filters --}}
        @if(!empty($q) || !empty($selectedCategoryIds) || !empty($sort))
            <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-gray-100 dark:border-gray-800">
                <span class="text-[11px] text-gray-500 dark:text-gray-400">
                    Active Category:
                </span>

                @if(!empty($q))
                    <a href="{{ $link(['q' => null]) }}"
                       class="inline-flex items-center gap-2 rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                        Search: {{ $q }}
                        <span class="text-gray-400">✕</span>
                    </a>
                @endif

                @foreach($selectedCategories as $selectedCategory)
                    @php
                        $removeCategoryIds = array_values(array_filter(
                            $selectedCategoryIds,
                            fn ($id) => (string) $id !== (string) $selectedCategory->id
                        ));
                    @endphp

                    <a href="{{ $link(['category' => $removeCategoryIds]) }}"
                       class="inline-flex items-center gap-2 rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                        {{ $selectedCategory->name }}
                        <span class="text-gray-400">✕</span>
                    </a>
                @endforeach

                @if(!empty($sort))
                    <a href="{{ $link(['sort' => null]) }}"
                       class="inline-flex items-center gap-2 rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                        Sort: {{ $sortLabel }}
                        <span class="text-gray-400">✕</span>
                    </a>
                @endif
            </div>
        @endif
    </div>

    {{-- Status message --}}
    @if(session('status'))
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    {{-- Results --}}
    
    @if($products->isEmpty())
        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-6">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                No products found. Try changing categories, clearing filters, or add products in admin.
            </p>
        </div>
    @else
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 {{ $singleCard ? 'justify-items-start' : '' }}">
            @foreach($products as $product)
                @include('partials.home_cards.product_card', [
                    'product' => $product,
                    'cartAddUrl' => $cartAddUrl,
                    'wishlistToggleUrl' => $wishlistToggleUrl,
                    'wishlistUrl' => $wishlistUrl,
                    'loginUrl' => $loginUrl,
                    'singleCard' => $singleCard,
                    'flagEmoji' => $flagEmoji,
                ])
            @endforeach
        </div>

        <div class="mt-4">
            {{ $products->links() }}
        </div>
    @endif

</div>

{{-- ✅ Inline slab dropdown loader (works for variable products) --}}
@if(\Illuminate\Support\Facades\Route::has('product.variants.options'))
<script>
(function () {
    function setVariant(card, variantId) {
        card.querySelectorAll('input.js-variant-input').forEach(inp => inp.value = variantId || '');
        const btn = card.querySelector('form.js-cart-form button.js-cart-btn');
        if (btn) {
            btn.disabled = !variantId || btn.hasAttribute('data-force-disabled');
            btn.classList.toggle('opacity-40', btn.disabled);
        }
    }

    async function hydrateCard(card) {
        const sel = card.querySelector('select.js-variant-select');
        if (!sel) return;

        const url = sel.dataset.url;
        const hint = card.querySelector('.js-variant-hint');

        sel.innerHTML = '<option value="">Loading…</option>';
        sel.disabled = true;

        const btn = card.querySelector('form.js-cart-form button.js-cart-btn');
        if (btn) {
            btn.disabled = true;
            btn.classList.add('opacity-40');
        }

        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('bad');
            const data = await res.json();
            const list = Array.isArray(data.variants) ? data.variants : [];

            sel.innerHTML = '<option value="">Choose option</option>';

            if (!list.length) {
                sel.innerHTML = '<option value="">No slabs available</option>';
                if (hint) hint.textContent = 'No available slabs in stock.';
                sel.disabled = true;
                setVariant(card, '');
                return;
            }

            list.forEach(v => {
                const opt = document.createElement('option');
                opt.value = v.id;
                opt.textContent = v.label || ('Slab #' + v.id);
                sel.appendChild(opt);
            });

            sel.disabled = false;
            if (hint) hint.textContent = 'Select & add to cart.';
        } catch (e) {
            sel.innerHTML = '<option value="">Unable to load slabs</option>';
            sel.disabled = true;
            if (hint) hint.textContent = 'Could not load slab list.';
            setVariant(card, '');
            return;
        }

        sel.addEventListener('change', () => setVariant(card, sel.value));
    }

    document.querySelectorAll('.js-product-card').forEach(hydrateCard);
})();
</script>
@endif
@endsection