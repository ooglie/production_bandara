@extends('layouts.customer')

@section('title', $product->name)

@section('content')
@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $product->loadMissing([
        'images' => function ($q) {
            $q->orderBy('position')->orderBy('id');
        },
        'activeRecipes',
    ]);

    $variants = $variants ?? collect();

    $pieceSelector = $pieceSelector ?? ['enabled' => false];
    $hasPieceSelector = (bool) ($pieceSelector['enabled'] ?? false);

    $primaryImage = $product->primary_image;
    $images = $product->images ?? collect();
    $recipes = $product->activeRecipes ?? collect();

    $pricingService = app(\App\Services\PricingService::class);
    $priceQuote = $pricingService->quote(auth()->user(), $product);
    $isB2BPrice = ($priceQuote['customer_type'] ?? 'b2c') === 'b2b';
    $effectivePrice = (float) ($priceQuote['price'] ?? 0);
    $basePrice      = (float) ($priceQuote['compare_at_price'] ?? $product->base_price ?? 0);
    $b2bMoq         = (float) ($priceQuote['moq'] ?? 1);

    $gstRate = app(\App\Services\GstRateService::class)->rateForProduct($product, auth()->user());

    $mrpDisplay = (float) ($product->mrp_price ?? 0);
    if ($mrpDisplay > 0 && ($product->b2c_price_includes_gst ?? true) && $gstRate > 0) {
        $mrpDisplay = round($mrpDisplay * (1 + ($gstRate / 100)), 2);
    }

    $hasMrpSavings = $mrpDisplay > 0 && $mrpDisplay > $effectivePrice;
    $mrpSavings = $hasMrpSavings ? round($mrpDisplay - $effectivePrice, 2) : 0;
    $mrpSavingsPct = $hasMrpSavings && $mrpDisplay > 0
        ? round((($mrpDisplay - $effectivePrice) / $mrpDisplay) * 100)
        : 0;

    $imageUrl = function ($path) {
        if (!$path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '/storage/', '/'])) {
            return $path;
        }

        return Storage::url($path);
    };

    $mainImageUrl = $imageUrl($primaryImage)
        ?: ($images->isNotEmpty() ? $imageUrl($images->first()->file_path) : null);

    $sellUnitLabel = match($product->sell_unit ?? 'piece') {
        'kg'   => 'Per kg',
        'pack' => 'Per pack',
        default => 'Per piece',
    };

    $stockValue = (float) ($product->stock_quantity ?? 0);
    $manageStock = (bool) ($product->manage_stock ?? false);
    $inStock = !$manageStock || $stockValue > 0;

    $stockLabel = $inStock ? 'In stock' : 'Out of stock';

    $productWeightLabel = !empty($product->product_weight)
        ? number_format((float) $product->product_weight, 3) . ' kg'
        : null;

    $variantLabel = function ($variant) {
        $parts = [];

        foreach (($variant->attributeValues ?? collect()) as $value) {
            $attributeName = $value->attribute->name ?? 'Option';
            $valueName = $value->value ?? $value->name ?? '';
            if ($valueName !== '') {
                $parts[] = $attributeName . ': ' . $valueName;
            }
        }

        if (!empty($parts)) {
            return implode(' · ', $parts);
        }

        return $variant->sku ?? ('Variant ' . $variant->id);
    };

    $recipeText = function ($recipe, $field) {
        if (method_exists($recipe, 'tr')) {
            return $recipe->tr($field);
        }

        $value = $recipe->{$field} ?? null;

        if (is_array($value)) {
            return $value[app()->getLocale()] ?? $value['en'] ?? (count($value) ? reset($value) : null);
        }

        return $value;
    };

    $recipeList = function ($recipe, $field) {
        if (method_exists($recipe, 'trList')) {
            return $recipe->trList($field);
        }

        $value = $recipe->{$field} ?? [];

        if (!is_array($value)) {
            return [];
        }

        if (isset($value[app()->getLocale()]) && is_array($value[app()->getLocale()])) {
            return $value[app()->getLocale()];
        }

        if (isset($value['en']) && is_array($value['en'])) {
            return $value['en'];
        }

        return array_values($value);
    };

    $originCode = $product->country_of_origin ?? null;

    $displayVariantPrice = function ($variant) use ($product, $pricingService) {
        return round((float) $pricingService->priceFor(auth()->user(), $product, $variant), 2);
    };

    $hasVariantSelector = !$hasPieceSelector && $product->type === 'variable' && $variants->isNotEmpty();

    $variantDisplayPrices = $hasVariantSelector
        ? $variants
            ->map(fn ($variant) => $displayVariantPrice($variant))
            ->filter(fn ($price) => $price > 0)
            ->values()
        : collect();

    $selectedVariantOld = null;
    if ($hasVariantSelector && old('product_variant_id')) {
        $selectedVariantOld = $variants->firstWhere('id', (int) old('product_variant_id'));
    }

    $piecePricingRatio = ($hasPieceSelector && $effectivePrice > 0 && $mrpDisplay > $effectivePrice)
        ? ($mrpDisplay / $effectivePrice)
        : (($hasMrpSavings && $effectivePrice > 0) ? ($mrpDisplay / $effectivePrice) : 0);

    $variantMrpRatio = ($hasMrpSavings && $effectivePrice > 0)
        ? ($mrpDisplay / $effectivePrice)
        : 0;

    $formatPriceText = function (float $min, ?float $max = null) {
        $max = $max ?? $min;

        if ($max > $min + 0.009) {
            return '₹' . number_format($min, 2) . ' – ₹' . number_format($max, 2);
        }

        return '₹' . number_format($min, 2);
    };

    if ($hasPieceSelector) {
        $topPriceMin = (float) ($pieceSelector['price_min'] ?? $effectivePrice);
        $topPriceMax = (float) ($pieceSelector['price_max'] ?? $topPriceMin);
    } elseif ($hasVariantSelector && $selectedVariantOld) {
        $selectedOldPrice = $displayVariantPrice($selectedVariantOld);
        $topPriceMin = $selectedOldPrice;
        $topPriceMax = $selectedOldPrice;
    } elseif ($hasVariantSelector && $variantDisplayPrices->isNotEmpty()) {
        $topPriceMin = (float) $variantDisplayPrices->min();
        $topPriceMax = (float) $variantDisplayPrices->max();
    } else {
        $topPriceMin = $effectivePrice;
        $topPriceMax = $effectivePrice;
    }

    $topPriceText = $formatPriceText($topPriceMin, $topPriceMax);

    $topMrpText = null;
    $topSaveText = null;
    $topSavePct = 0;

    if ($piecePricingRatio > 1) {
        $mrpMin = round($topPriceMin * $piecePricingRatio, 2);
        $mrpMax = round($topPriceMax * $piecePricingRatio, 2);

        $saveMin = max(round($mrpMin - $topPriceMin, 2), 0);
        $saveMax = max(round($mrpMax - $topPriceMax, 2), 0);

        $topMrpText = $formatPriceText($mrpMin, $mrpMax);
        $topSaveText = $formatPriceText($saveMin, $saveMax);
        $topSavePct = $mrpMin > 0 ? round((($mrpMin - $topPriceMin) / $mrpMin) * 100) : 0;
    } elseif ($hasMrpSavings) {
        if ($hasVariantSelector && $topPriceMin > 0) {
            $mrpMin = round($topPriceMin * $variantMrpRatio, 2);
            $mrpMax = round($topPriceMax * $variantMrpRatio, 2);

            $saveMin = max(round($mrpMin - $topPriceMin, 2), 0);
            $saveMax = max(round($mrpMax - $topPriceMax, 2), 0);

            $topMrpText = $formatPriceText($mrpMin, $mrpMax);
            $topSaveText = $formatPriceText($saveMin, $saveMax);
            $topSavePct = $mrpMin > 0 ? round((($mrpMin - $topPriceMin) / $mrpMin) * 100) : 0;
        } else {
            $topMrpText = '₹' . number_format($mrpDisplay, 2);
            $topSaveText = '₹' . number_format($mrpSavings, 2);
            $topSavePct = $mrpSavingsPct;
        }
    }
@endphp

<div class="max-w-6xl mx-auto px-4 py-6 space-y-8">
    {{-- Breadcrumb --}}
    <nav class="text-[11px] text-gray-500 dark:text-gray-400">
        <a href="{{ route('home') }}" class="hover:underline">Home</a>
        <span class="mx-1">/</span>
        <a href="{{ route('shop.index') }}" class="hover:underline">Shop</a>
        <span class="mx-1">/</span>
        <span class="text-gray-700 dark:text-gray-200">{{ $product->name }}</span>
    </nav>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,540px)_minmax(0,1fr)] lg:items-start">
        {{-- Gallery --}}
        <div class="space-y-4 lg:max-w-[540px]">
            <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-3">
                <div class="relative aspect-[4/3] rounded-sm overflow-hidden bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                    @if($mainImageUrl)
                        <img
                            id="product-main-image"
                            src="{{ $mainImageUrl }}"
                            alt="{{ $product->name }}"
                            class="object-cover w-full h-full"
                        >
                    @else
                        <span class="text-[11px] text-gray-400 dark:text-gray-500">No image available</span>
                    @endif

                    <div class="absolute left-3 top-3 flex flex-wrap gap-2 text-[10px]">
                        @if($product->is_new)
                            <span class="inline-flex items-center rounded-sm bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-2 py-1">
                                New
                            </span>
                        @endif

                        @if($product->is_special)
                            <span class="inline-flex items-center rounded-sm bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 px-2 py-1">
                                Special
                            </span>
                        @endif

                        @if($product->is_featured)
                            <span class="inline-flex items-center rounded-sm bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 px-2 py-1">
                                Featured
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            @if($images->isNotEmpty() || $primaryImage)
                <div class="flex gap-2 overflow-x-auto pb-1">
                    @foreach($images as $image)
                        @php $thumbUrl = $imageUrl($image->file_path); @endphp
                        @if($thumbUrl)
                            <button type="button"
                                    class="gallery-thumb shrink-0 h-16 w-16 rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden"
                                    data-image-src="{{ $thumbUrl }}">
                                <img src="{{ $thumbUrl }}" alt="{{ $product->name }}" class="object-cover w-full h-full">
                            </button>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Product Summary / Purchase --}}
        <div class="space-y-4">
            <div class="space-y-2">
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-50 leading-tight">
                    {{ $product->name }}
                </h1>

                @if($product->short_description)
                    <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                        {{ $product->short_description }}
                    </p>
                @endif
            </div>

            <div class="flex flex-wrap gap-2 text-[10px]">
                <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1 text-gray-600 dark:text-gray-300">
                    {{ $sellUnitLabel }}
                </span>

                <span class="inline-flex items-center rounded-sm border px-2 py-1
                    {{ $inStock
                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200'
                        : 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200' }}">
                    {{ $stockLabel }}
                </span>

                @if($productWeightLabel)
                    <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1 text-gray-600 dark:text-gray-300">
                        {{ $productWeightLabel }}
                    </span>
                @endif

                @if($originCode)
                    <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1 text-gray-600 dark:text-gray-300">
                        Origin: {{ $originCode }}
                    </span>
                @endif
            </div>

            <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 space-y-4">
                {{-- Main top price block --}}
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-end gap-2">
                            <span id="piece-top-price" class="text-2xl font-semibold text-gray-900 dark:text-gray-50">
                                {{ $topPriceText }}
                            </span>

                            <span id="piece-top-mrp"
                                  class="text-sm text-gray-400 line-through {{ $topMrpText ? '' : 'hidden' }}">
                                {{ $topMrpText }}
                            </span>
                        </div>

                        <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                            @if($isB2BPrice)
                                Your B2B account price {{ ($priceQuote['display_price_includes_gst'] ?? false) ? 'includes GST' : 'excludes GST' }}{{ $b2bMoq > 1 ? ' · MOQ '.rtrim(rtrim(number_format($b2bMoq, 3), '0'), '.') : '' }}.
                            @else
                                Price shown {{ ($priceQuote['display_price_includes_gst'] ?? true) ? 'includes' : 'excludes' }} applicable GST.
                            @endif
                        </div>
                    </div>

                    <div id="piece-top-save-card"
                         class="{{ $topSaveText ? '' : 'hidden ' }}rounded-lg border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 px-3 py-2 text-right">
                        <div class="text-[10px] uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                            You save
                        </div>
                        <div id="piece-top-save-amount" class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">
                            {{ $topSaveText }}
                        </div>
                        <div id="piece-top-save-pct" class="text-[10px] text-emerald-600 dark:text-emerald-400">
                            {{ $topSavePct > 0 ? $topSavePct . '% off' : '' }}
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('cart.add') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                    @if($hasPieceSelector)
                        @include('products._piece_selector', ['pieceSelector' => $pieceSelector])
                    @endif

                    {{-- Variant select --}}
                    @if($hasVariantSelector)
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Choose variant
                            </label>
                            <select
                                id="product-variant-select"
                                name="product_variant_id"
                                class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                                required
                            >
                                <option value="">Select…</option>
                                @foreach($variants as $variant)
                                    @php
                                        $parts = [];
                                        foreach ($variant->attributeValues ?? [] as $value) {
                                            $parts[] = $value->attribute->name . ': ' . $value->value;
                                        }

                                        $label = $parts ? implode(' · ', $parts) : ($variant->sku ?? 'Variant '.$variant->id);
                                        $variantPrice = $displayVariantPrice($variant);
                                    @endphp
                                    <option
                                        value="{{ $variant->id }}"
                                        data-display-price="{{ number_format($variantPrice, 2, '.', '') }}"
                                        @selected((int) old('product_variant_id', 0) === (int) $variant->id)
                                    >
                                        {{ $label }} — ₹{{ number_format($variantPrice, 2) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('product_variant_id')
                                <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    {{-- Lower price block only for simple non-slab, non-variant products --}}
                    @if(!$hasPieceSelector && !$hasVariantSelector)
                        <div class="text-sm text-gray-900 dark:text-gray-50">
                            @if(! $isB2BPrice && $product->is_special && $effectivePrice < $basePrice)
                                <span class="text-base font-semibold">
                                    ₹{{ number_format($effectivePrice, 2) }}
                                </span>
                                <span class="ml-2 text-xs text-gray-400 line-through">
                                    ₹{{ number_format($basePrice, 2) }}
                                </span>
                            @else
                                <span class="text-base font-semibold">
                                    ₹{{ number_format($effectivePrice, 2) }}
                                </span>
                            @endif
                        </div>
                    @endif

                    @if(!$hasPieceSelector)
                        <div class="flex items-center gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                    Quantity
                                </label>
                                <input
                                    type="number"
                                    name="quantity"
                                    value="{{ old('quantity', 1) }}"
                                    min="1"
                                    class="mt-1 w-20 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                                >
                                @error('quantity')
                                    <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    @endif

                    <div class="flex items-end gap-3">
                        <button
                            type="submit"
                            id="add-to-cart-submit"
                            @disabled($hasPieceSelector)
                            class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-xs font-medium hover:bg-gray-800 dark:hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ $hasPieceSelector ? 'Select slab to add' : 'Add to cart' }}
                        </button>

                        @if(config('features.wishlist', true))
                            @auth
                                <button
                                    type="submit"
                                    formaction="{{ route('wishlist.store') }}"
                                    formmethod="POST"
                                    class="inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-4 py-2 text-xs hover:bg-gray-100 dark:hover:bg-gray-800">
                                    Save to wishlist
                                </button>
                            @else
                                <a href="{{ route('login') }}"
                                   class="text-[11px] text-gray-600 dark:text-gray-300 underline">
                                    Sign in to save
                                </a>
                            @endauth
                        @endif
                    </div>
                </form>

                <div class="rounded-sm border border-dashed border-gray-300 dark:border-gray-700 px-4 py-3 text-[11px] text-gray-600 dark:text-gray-300">
                    Need larger quantities, business pricing, or storage guidance?
                    @if(Route::has('tickets.create'))
                        <a href="{{ route('tickets.create') }}" class="underline font-medium">Contact support</a>.
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Product Tabs --}}
    <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden" data-product-tabs>
        <div class="border-b border-gray-200 dark:border-gray-800 px-4 sm:px-6">
            <div class="flex flex-wrap gap-2 py-3">
                <button type="button"
                        class="tab-btn inline-flex items-center rounded-sm px-4 py-2 text-[11px] font-medium transition"
                        data-tab-target="description">
                    Description
                </button>

                <button type="button"
                        class="tab-btn inline-flex items-center rounded-sm px-4 py-2 text-[11px] font-medium transition"
                        data-tab-target="recipes">
                    Recipes
                    @if($recipes->isNotEmpty())
                        <span class="ml-2 inline-flex min-w-5 items-center justify-center rounded-sm bg-gray-100 text-gray-700 dark:bg-gray-300 dark:text-gray700 px-1.5 py-0.5 text-[10px]">
                            {{ $recipes->count()}}
                        </span>
                    @endif
                </button>

                <button type="button"
                        class="tab-btn inline-flex items-center rounded-sm px-4 py-2 text-[11px] font-medium transition"
                        data-tab-target="storage">
                    Storage & Delivery
                </button>

                <button type="button"
                        class="tab-btn inline-flex items-center rounded-sm px-4 py-2 text-[11px] font-medium transition"
                        data-tab-target="info">
                    Product Info
                </button>
            </div>
        </div>

        <div class="p-4 sm:p-6">
            {{-- DESCRIPTION --}}
            <div class="tab-panel space-y-4" data-tab-panel="description">
                @if($product->description)
                    <div class="prose prose-sm max-w-none dark:prose-invert text-gray-700 dark:text-gray-200">
                        {!! nl2br(e($product->description)) !!}
                    </div>
                @elseif($product->short_description)
                    <div class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                        {{ $product->short_description }}
                    </div>
                @else
                    <div class="rounded-sm border border-dashed border-gray-300 dark:border-gray-700 px-4 py-5 text-sm text-gray-500 dark:text-gray-400">
                        Description will appear here once product details are added.
                    </div>
                @endif

                <div class="grid gap-3 md:grid-cols-3">
                    <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">Selling unit</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $sellUnitLabel }}</div>
                    </div>

                    <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">Availability</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $stockLabel }}</div>
                    </div>

                    <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">GST</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ number_format($gstRate, 2) }}%</div>
                    </div>
                </div>
            </div>

            {{-- RECIPES --}}
            <div class="tab-panel hidden space-y-4" data-tab-panel="recipes">
                @if($recipes->isEmpty())
                    <div class="rounded-sm border border-dashed border-gray-300 dark:border-gray-700 px-4 py-5 text-sm text-gray-500 dark:text-gray-400">
                        Recipes for this product will appear here soon.
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($recipes as $recipe)
                            @php
                                $title = $recipeText($recipe, 'title');
                                $short = $recipeText($recipe, 'short_description');
                                $description = $recipeText($recipe, 'description');
                                $ingredients = $recipeList($recipe, 'ingredients');
                                $steps = $recipeList($recipe, 'steps');
                                $recipeImage = $imageUrl($recipe->image_path ?? null);
                            @endphp

                            <details class="group rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 overflow-hidden">
                                <summary class="list-none cursor-pointer px-4 py-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex gap-4">
                                            @if($recipeImage)
                                                <div class="h-20 w-20 shrink-0 overflow-hidden rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
                                                    <img src="{{ $recipeImage }}"
                                                         alt="{{ $title }}"
                                                         class="h-full w-full object-cover">
                                                </div>
                                            @endif

                                            <div class="space-y-2">
                                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                                                    {{ $title }}
                                                </div>

                                                @if($short)
                                                    <div class="text-xs text-gray-600 dark:text-gray-300">
                                                        {{ $short }}
                                                    </div>
                                                @endif

                                                <div class="flex flex-wrap gap-2 text-[10px]">
                                                    @if($recipe->prep_time_minutes)
                                                        <span class="rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1 text-gray-600 dark:text-gray-300">
                                                            Prep {{ $recipe->prep_time_minutes }} mins
                                                        </span>
                                                    @endif

                                                    @if($recipe->cook_time_minutes)
                                                        <span class="rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1 text-gray-600 dark:text-gray-300">
                                                            Cook {{ $recipe->cook_time_minutes }} mins
                                                        </span>
                                                    @endif

                                                    @if($recipe->servings)
                                                        <span class="rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1 text-gray-600 dark:text-gray-300">
                                                            Serves {{ $recipe->servings }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <span class="mt-1 text-gray-400 transition group-open:rotate-180">⌄</span>
                                    </div>
                                </summary>

                                <div class="border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 space-y-4">
                                    @if($description)
                                        <div class="text-xs leading-relaxed text-gray-600 dark:text-gray-300">
                                            {!! nl2br(e($description)) !!}
                                        </div>
                                    @endif

                                    <div class="grid gap-4 lg:grid-cols-2">
                                        <div>
                                            <div class="text-[11px] font-semibold text-gray-900 dark:text-gray-50 mb-2">
                                                Ingredients
                                            </div>
                                            @if(!empty($ingredients))
                                                <ul class="space-y-1 text-xs text-gray-600 dark:text-gray-300">
                                                    @foreach($ingredients as $ingredient)
                                                        <li class="flex items-start gap-2">
                                                            <span class="mt-[5px] h-1.5 w-1.5 rounded-sm bg-gray-400"></span>
                                                            <span>{{ $ingredient }}</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <div class="text-xs text-gray-400">Ingredients not added yet.</div>
                                            @endif
                                        </div>

                                        <div>
                                            <div class="text-[11px] font-semibold text-gray-900 dark:text-gray-50 mb-2">
                                                Method
                                            </div>
                                            @if(!empty($steps))
                                                <ol class="space-y-2 text-xs text-gray-600 dark:text-gray-300">
                                                    @foreach($steps as $step)
                                                        <li class="flex items-start gap-2">
                                                            <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-sm bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 text-[10px] font-semibold">
                                                                {{ $loop->iteration }}
                                                            </span>
                                                            <span>{{ $step }}</span>
                                                        </li>
                                                    @endforeach
                                                </ol>
                                            @else
                                                <div class="text-xs text-gray-400">Cooking steps not added yet.</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </details>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- STORAGE --}}
            <div class="tab-panel hidden space-y-4" data-tab-panel="storage">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 p-4 space-y-3">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Storage guidance</h3>
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                            @foreach($product->storageGuidanceLines() as $line)
                                <li class="flex items-start gap-2">
                                    <span class="mt-[6px] h-1.5 w-1.5 rounded-sm bg-gray-400"></span>
                                    <span>{{ $line }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 p-4 space-y-3">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Delivery & support</h3>
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                            @foreach($product->deliverySupportLines() as $line)
                                <li class="flex items-start gap-2">
                                    <span class="mt-[6px] h-1.5 w-1.5 rounded-sm bg-gray-400"></span>
                                    <span>{{ $line }}</span>
                                </li>
                            @endforeach
                        </ul>

                        @if(Route::has('tickets.create'))
                            <a href="{{ route('tickets.create') }}"
                               class="inline-flex items-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] font-medium hover:bg-gray-100 dark:hover:bg-gray-800">
                                Need help? Contact support
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- PRODUCT INFO --}}
            <div class="tab-panel hidden" data-tab-panel="info">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">Selling unit</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $sellUnitLabel }}</div>
                    </div>

                    @if($productWeightLabel)
                        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                            <div class="text-[10px] uppercase tracking-wide text-gray-400">Pack / product weight</div>
                            <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $productWeightLabel }}</div>
                        </div>
                    @endif

                    <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">Availability</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $stockLabel }}</div>
                    </div>

                    <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">GST</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ number_format($gstRate, 2) }}%</div>
                    </div>

                    @if($originCode)
                        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                            <div class="text-[10px] uppercase tracking-wide text-gray-400">Country of origin</div>
                            <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $originCode }}</div>
                        </div>
                    @endif

                    @if(!empty($product->sku))
                        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                            <div class="text-[10px] uppercase tracking-wide text-gray-400">SKU</div>
                            <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $product->sku }}</div>
                        </div>
                    @endif

                    @if(!empty($product->barcode))
                        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                            <div class="text-[10px] uppercase tracking-wide text-gray-400">Barcode</div>
                            <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $product->barcode }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const mainImage = document.getElementById('product-main-image');
    const thumbs = document.querySelectorAll('.gallery-thumb');

    thumbs.forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            if (!mainImage) return;

            const nextSrc = thumb.getAttribute('data-image-src');
            if (!nextSrc) return;

            mainImage.setAttribute('src', nextSrc);

            thumbs.forEach(function (t) {
                t.classList.remove('ring-2', 'ring-gray-400', 'dark:ring-gray-500');
            });

            thumb.classList.add('ring-2', 'ring-gray-400', 'dark:ring-gray-500');
        });
    });

    if (thumbs.length) {
        thumbs[0].classList.add('ring-2', 'ring-gray-400', 'dark:ring-gray-500');
    }

    const tabRoots = document.querySelectorAll('[data-product-tabs]');

    tabRoots.forEach(function (root) {
        const buttons = root.querySelectorAll('[data-tab-target]');
        const panels = root.querySelectorAll('[data-tab-panel]');

        function activate(target) {
            buttons.forEach(function (btn) {
                const active = btn.getAttribute('data-tab-target') === target;

                btn.classList.toggle('bg-gray-900', active);
                btn.classList.toggle('text-white', active);
                btn.classList.toggle('dark:bg-gray-100', active);
                btn.classList.toggle('dark:text-gray-900', active);

                btn.classList.toggle('bg-gray-100', !active);
                btn.classList.toggle('text-gray-700', !active);
                btn.classList.toggle('dark:bg-gray-800', !active);
                btn.classList.toggle('dark:text-gray-200', !active);
            });

            panels.forEach(function (panel) {
                panel.classList.toggle('hidden', panel.getAttribute('data-tab-panel') !== target);
            });
        }

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                activate(btn.getAttribute('data-tab-target'));
            });
        });

        activate('description');
    });
})();

document.addEventListener('DOMContentLoaded', function () {
    const topPrice = document.getElementById('piece-top-price');
    const topMrp = document.getElementById('piece-top-mrp');
    const saveCard = document.getElementById('piece-top-save-card');
    const saveAmount = document.getElementById('piece-top-save-amount');
    const savePct = document.getElementById('piece-top-save-pct');

    function money(value) {
        return '₹' + Number(value).toFixed(2);
    }

    function moneyRange(min, max) {
        min = Number(min || 0);
        max = Number(max || min);

        if (max > min + 0.009) {
            return money(min) + ' – ' + money(max);
        }

        return money(min);
    }

    function updateTopPricing(minPrice, maxPrice, ratio) {
        if (!topPrice) return;

        minPrice = Number(minPrice || 0);
        maxPrice = Number(maxPrice || minPrice);
        ratio = Number(ratio || 0);

        topPrice.textContent = moneyRange(minPrice, maxPrice);

        if (!topMrp || !saveCard || !saveAmount || !savePct) {
            return;
        }

        if (ratio > 1.0001) {
            const mrpMin = minPrice * ratio;
            const mrpMax = maxPrice * ratio;
            const saveMin = Math.max(mrpMin - minPrice, 0);
            const saveMax = Math.max(mrpMax - maxPrice, 0);

            topMrp.textContent = moneyRange(mrpMin, mrpMax);
            topMrp.classList.remove('hidden');

            saveAmount.textContent = moneyRange(saveMin, saveMax);

            const pct = mrpMin > 0 ? Math.round((saveMin / mrpMin) * 100) : 0;
            savePct.textContent = pct > 0 ? (pct + '% off') : '';
            saveCard.classList.remove('hidden');
        } else {
            topMrp.textContent = '';
            topMrp.classList.add('hidden');
            saveAmount.textContent = '';
            savePct.textContent = '';
            saveCard.classList.add('hidden');
        }
    }

    // ----------------------------
    // Variant price sync
    // ----------------------------
    const variantSelect = document.getElementById('product-variant-select');
    if (variantSelect && topPrice) {
        const basePriceText = topPrice.textContent;
        const baseMrpText = topMrp ? topMrp.textContent : '';
        const baseSaveAmountText = saveAmount ? saveAmount.textContent : '';
        const baseSavePctText = savePct ? savePct.textContent : '';
        const baseSaveVisible = saveCard ? !saveCard.classList.contains('hidden') : false;
        const baseMrpVisible = topMrp ? !topMrp.classList.contains('hidden') : false;
        const variantMrpRatio = parseFloat(@json((float) $variantMrpRatio)) || 0;

        function restoreBaseVariantUI() {
            topPrice.textContent = basePriceText;

            if (topMrp) {
                topMrp.textContent = baseMrpText;
                if (baseMrpVisible) topMrp.classList.remove('hidden');
                else topMrp.classList.add('hidden');
            }

            if (saveCard && saveAmount && savePct) {
                saveAmount.textContent = baseSaveAmountText;
                savePct.textContent = baseSavePctText;
                if (baseSaveVisible) saveCard.classList.remove('hidden');
                else saveCard.classList.add('hidden');
            }
        }

        function syncVariantPrice() {
            const option = variantSelect.options[variantSelect.selectedIndex];

            if (!option || !option.value) {
                restoreBaseVariantUI();
                return;
            }

            const displayPrice = parseFloat(option.dataset.displayPrice || '0') || 0;
            updateTopPricing(displayPrice, displayPrice, variantMrpRatio);
        }

        variantSelect.addEventListener('change', syncVariantPrice);
        syncVariantPrice();
    }

    // ----------------------------
    // Piece/slab price sync
    // ----------------------------
    const pieceRoot = document.getElementById('piece-selector-root');
    if (pieceRoot && topPrice) {
        const pieceMrpRatio = parseFloat(@json((float) $piecePricingRatio)) || 0;
        const bandButtons = Array.from(pieceRoot.querySelectorAll('[data-piece-band]'));
        const bandPanels = Array.from(pieceRoot.querySelectorAll('[data-piece-band-panel]'));
        const radios = Array.from(pieceRoot.querySelectorAll('.piece-option-radio'));
        const qtySelect = document.getElementById('piece-quantity-select');
        const summary = document.getElementById('selected-piece-summary');

        function checkedRadio() {
            return radios.find(r => r.checked) || null;
        }

        function currentVisiblePanel() {
            const selected = checkedRadio();
            if (selected) {
                const panel = selected.closest('[data-piece-band-panel]');
                if (panel) return panel;
            }

            return bandPanels.find(panel => !panel.classList.contains('hidden')) || bandPanels[0] || null;
        }

        function panelRange(panel) {
            if (!panel) return { min: 0, max: 0 };

            const prices = Array.from(panel.querySelectorAll('.piece-option-radio'))
                .map(r => parseFloat(r.dataset.price || '0'))
                .filter(v => Number.isFinite(v) && v > 0);

            if (!prices.length) {
                return { min: 0, max: 0 };
            }

            return {
                min: Math.min.apply(null, prices),
                max: Math.max.apply(null, prices),
            };
        }

        function updatePieceSummaryWithoutPrice() {
            if (!summary) return;

            const selected = checkedRadio();
            if (!selected) {
                summary.textContent = 'Select a slab to continue.';
                return;
            }

            const qty = parseInt((qtySelect && qtySelect.value) ? qtySelect.value : '1', 10) || 1;
            const weightLabel = selected.dataset.weightLabel || '';

            summary.innerHTML =
                '<div class="font-medium text-gray-900 dark:text-gray-50">Selected slab: ' + weightLabel + ' × ' + qty + '</div>' +
                '<div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Choose quantity and continue to add.</div>';
        }

        function syncPiecePricingUI() {
            const selected = checkedRadio();

            if (selected) {
                const qty = parseInt((qtySelect && qtySelect.value) ? qtySelect.value : '1', 10) || 1;
                const price = parseFloat(selected.dataset.price || '0') || 0;
                const total = price * qty;

                updateTopPricing(total, total, pieceMrpRatio);
                updatePieceSummaryWithoutPrice();
                return;
            }

            const panel = currentVisiblePanel();
            const range = panelRange(panel);

            updateTopPricing(range.min, range.max, pieceMrpRatio);
            updatePieceSummaryWithoutPrice();
        }

        bandButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                setTimeout(syncPiecePricingUI, 0);
            });
        });

        radios.forEach(function (radio) {
            radio.addEventListener('change', function () {
                setTimeout(syncPiecePricingUI, 0);
            });
        });

        if (qtySelect) {
            qtySelect.addEventListener('change', function () {
                setTimeout(syncPiecePricingUI, 0);
            });
        }

        setTimeout(syncPiecePricingUI, 0);
    }
});
</script>
@endsection