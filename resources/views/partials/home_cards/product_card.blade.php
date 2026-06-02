{{-- resources/views/partials/home_cards/product_card.blade.php --}}
@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Facades\Route;

    $productUrl = $productUrl ?? route('product.show', $product);
    $showProductSelectors = $showProductSelectors ?? true;
    $showStockBadges = $showStockBadges ?? true;
    $showCountryOfOrigin = $showCountryOfOrigin ?? true;
    $priceView = $priceView ?? null;
    $priceViewData = $priceViewData ?? [];
    $actionsView = $actionsView ?? 'partials.home_cards.wishlist_cart_view';
    $actionsViewData = $actionsViewData ?? [];

    $cartAddUrl = $cartAddUrl ?? null;
    $wishlistToggleUrl = $wishlistToggleUrl ?? null;
    $wishlistUrl = $wishlistUrl ?? null;
    $loginUrl = $loginUrl ?? null;

    $pieceSelector = $product->piece_selector ?? ['enabled' => false];
    $hasPieceSelector = (bool) $showProductSelectors && (bool) data_get($pieceSelector, 'enabled', false);
    $pieceBands = data_get($pieceSelector, 'bands', []);

    $isVariable = (string)($product->type ?? 'simple') === 'variable';
    $inStock = !(bool)($product->manage_stock ?? false) || ((float)($product->stock_quantity ?? 0) > 0);

    $coCode = strtoupper(trim((string)($product->country_of_origin ?? '')));
    $flag = isset($flagEmoji) ? $flagEmoji($coCode) : null;
    $country_name = $coCode ? \Locale::getDisplayRegion('-' . $coCode, app()->getLocale()) : null;

    $cardQuote = app(\App\Services\PricingService::class)->quote(auth()->user(), $product);
    $effective = (float)($cardQuote['price'] ?? ($product->effective_price ?? 0));
    $base      = (float)($cardQuote['compare_at_price'] ?? $effective);
    $mrp       = $product->mrp_price !== null ? (float)$product->mrp_price : null;
    if ($mrp !== null && ($cardQuote['display_price_includes_gst'] ?? true)) {
        $gstRate = app(\App\Services\GstRateService::class)->rateForProduct($product, auth()->user());
        if ($gstRate > 0) {
            $mrp = round($mrp * (1 + ($gstRate / 100)), 2);
        }
    }

    $variantCount = isset($product->variants_count)
        ? (int) $product->variants_count
        : ((method_exists($product, 'variants') && $product->relationLoaded('variants')) ? $product->variants->count() : 0);

    $hasVariants = (bool) $showProductSelectors && !$hasPieceSelector && ($isVariable || $variantCount > 0);

    $variantOptionsUrl = Route::has('product.variants.options')
        ? route('product.variants.options', ['product' => $product->id])
        : null;

    $chip  = "inline-flex items-center rounded-sm px-2 py-0.5 text-[10px]";

    $actionsData = array_merge([
        'product' => $product,
        'productUrl' => $productUrl,
        'cartAddUrl' => $cartAddUrl,
        'wishlistToggleUrl' => $wishlistToggleUrl,
        'wishlistUrl' => $wishlistUrl,
        'loginUrl' => $loginUrl,
        'isVariable' => $isVariable,
        'inStock' => $inStock,
    ], $actionsViewData);

    $priceData = array_merge([
        'product' => $product,
        'productUrl' => $productUrl,
        'effective' => $effective,
        'base' => $base,
        'mrp' => $mrp,
    ], $priceViewData);
@endphp

<div class="w-full max-w-sm justify-self-start js-product-card">
    <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-3 flex flex-col h-full">

        {{-- Image --}}
        <div class="relative aspect-[4/3] rounded-sm bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-3 overflow-hidden">
            <a href="{{ $productUrl }}" title="View details" class="block h-full w-full">
                @if($product->primary_image)
                    <img
                        src="{{ Storage::url($product->primary_image) }}"
                        alt="{{ $product->name }}"
                        class="object-cover w-full h-full group-hover:scale-[1.02] transition-transform duration-300"
                        loading="lazy"
                    >
                @else
                    <div class="h-full w-full flex items-center justify-center">
                        <span class="text-[11px] text-gray-400 dark:text-gray-500">No image</span>
                    </div>
                @endif
            </a>

            {{-- Top-left badges --}}
            <div class="absolute top-2 left-2 flex flex-wrap gap-1">
                @if($product->is_featured)
                    <span class="{{ $chip }} bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900">Featured</span>
                @endif
                @if($product->is_new)
                    <span class="{{ $chip }} bg-sky-50 text-sky-700 dark:bg-sky-900/30 dark:text-sky-200">New</span>
                @endif
                @if($product->is_special)
                    <span class="{{ $chip }} bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-200">Special</span>
                @endif
                @if($showStockBadges && !$hasVariants && !$hasPieceSelector && (bool)($product->manage_stock ?? false) && !$inStock)
                    <span class="{{ $chip }} bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-200">Out of stock</span>
                @endif
            </div>
        </div>

        {{-- Content --}}
        <div class="flex-1 flex flex-col">
            <p class="text-[13px] font-semibold text-gray-900 dark:text-gray-50 leading-snug line-clamp-2">
                <a href="{{ $productUrl }}" title="View details" class="hover:underline">
                    {{ $product->name }}
                </a>
            </p>

            @if($product->short_description)
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400 line-clamp-2">
                    {{ $product->short_description }}
                </p>
            @endif

            <div class="mt-3"></div>

            {{-- Price row --}}
            <div class="mt-auto flex items-end justify-between gap-3">
                <div class="min-w-0">
                    @if($priceView)
                        @include($priceView, $priceData)
                    @elseif($hasPieceSelector && count($pieceBands))
                        <div class="flex items-center gap-2">
                            <select
                                class="h-9 max-w-[180px] truncate rounded-sm border border-gray-200 dark:border-gray-700 bg-white/90 dark:bg-gray-950/60 backdrop-blur px-3 text-[12px] text-gray-800 dark:text-gray-100 hover:bg-white dark:hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700"
                                onchange="if(this.value){ window.location = this.value; }"
                                title="Choose slab range"
                            >
                                <option value="">Choose slab</option>
                                @foreach($pieceBands as $band)
                                    <option value="{{ $productUrl }}?band={{ urlencode($band['key']) }}#piece-selector-root">
                                        {{ $band['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @elseif($hasVariants && $variantOptionsUrl)
                        <div class="flex items-center gap-2">
                            <select
                                class="js-variant-select h-9 max-w-[170px] truncate rounded-sm border border-gray-200 dark:border-gray-700 bg-white/90 dark:bg-gray-950/60 backdrop-blur px-3 text-[12px] text-gray-800 dark:text-gray-100 hover:bg-white dark:hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700"
                                data-url="{{ $variantOptionsUrl }}"
                                title="Select product"
                            >
                                <option value="">Loading…</option>
                            </select>
                        </div>
                    @else
                        <div class="flex flex-col items-start leading-tight">
                            <span class="text-[14px] font-semibold text-gray-900 dark:text-gray-50">
                                @include('partials._shop_price_or_range', ['product' => $product])
                            </span>
                        </div>
                    @endif
                </div>

                {{-- Actions --}}
                @include($actionsView, $actionsData)
            </div>

            @if($showCountryOfOrigin)
                <div class="flex flex-col items-end pt-4">
                    @if($flag)
                        <div class="inline-flex gap-1 rounded-sm bg-white/80 dark:bg-gray-950/70 backdrop-blur text-[10px] text-gray-700 dark:text-gray-200">
                            Country of origin :: {{ $country_name }}
                            <span class="text-[14px] leading-none">{!! $flag !!}</span>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
