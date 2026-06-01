@extends('layouts.customer')

@section('title', $product->name . ' - B2B Catalogue')

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

    $primaryImage = $product->primary_image;
    $images = $product->images ?? collect();
    $recipes = $product->activeRecipes ?? collect();

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

    $assignmentList = $assignments ?? collect();
    if ($assignmentList instanceof \App\Models\B2BCustomerProduct) {
        $assignmentList = collect([$assignmentList]);
    }

    $isAssigned = $assignmentList->isNotEmpty();
    $isPending = $latestRequest && $latestRequest->status === 'pending';
    // Do not expose retail stock or piece availability in the B2B catalogue/detail UI.
    // B2B options are shown as account-approved sellable units only.

    $primaryAssignment = $assignmentList->firstWhere('product_sell_unit_id', null) ?: $assignmentList->first();
    $primarySellUnit = $primaryAssignment?->sellUnit;
    $b2bPrice = $isAssigned
        ? (float) ($primarySellUnit ? $pricing->priceForSellUnit($user, $product, $primarySellUnit) : $pricing->priceFor($user, $product))
        : 0.0;
    $moq = max((float) ($primaryAssignment?->min_order_quantity ?? 1), 1);
    $moqDisplay = rtrim(rtrim(number_format($moq, 2), '0'), '.');
    $quantityValue = rtrim(rtrim(number_format($moq, 2, '.', ''), '0'), '.');

    $sellUnitLabel = match($product->sell_unit ?? 'piece') {
        'kg'   => 'Per kg',
        'pack' => 'Per pack',
        default => 'Per piece',
    };

    $gstRate = (float) ($product->effective_gst_rate ?? $product->gst_rate ?? 0);
    $originCode = strtoupper((string) ($product->country_of_origin ?? ''));
    $productWeightLabel = !empty($product->product_weight)
        ? number_format((float) $product->product_weight, 3) . ' kg'
        : null;

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

        if (! is_array($value)) {
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
@endphp

<div class="max-w-6xl mx-auto px-4 py-6 space-y-8">
    <nav class="text-[11px] text-gray-500 dark:text-gray-400">
        <a href="{{ route('b2b.dashboard') }}" class="hover:underline">B2B Dashboard</a>
        <span class="mx-1">/</span>
        <a href="{{ route('b2b.catalog.index') }}" class="hover:underline">Explore catalogue</a>
        <span class="mx-1">/</span>
        <span class="text-gray-700 dark:text-gray-200">{{ $product->name }}</span>
    </nav>

    @if(session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-[11px] text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-[11px] text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,540px)_minmax(0,1fr)] lg:items-start">
        <div class="space-y-4 lg:max-w-[540px]">
            <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900">
                <div class="relative aspect-[4/3] rounded-sm overflow-hidden bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                    @if($mainImageUrl)
                        <img id="product-main-image" src="{{ $mainImageUrl }}" alt="{{ $product->name }}" class="object-cover w-full h-full">
                    @else
                        <span class="text-[11px] text-gray-400 dark:text-gray-500">No image available</span>
                    @endif

                    <div class="absolute left-3 top-3 flex flex-wrap gap-2 text-[10px]">
                        @if($isAssigned)
                            <span class="inline-flex items-center rounded-sm bg-emerald-50 px-2 py-1 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200">In portfolio</span>
                        @elseif($isPending)
                            <span class="inline-flex items-center rounded-sm bg-amber-50 px-2 py-1 text-amber-700 dark:bg-amber-900/30 dark:text-amber-200">Request pending</span>
                        @else
                            <span class="inline-flex items-center rounded-sm bg-gray-900 px-2 py-1 text-white dark:bg-gray-100 dark:text-gray-900">Available on request</span>
                        @endif

                        @if($product->is_new)
                            <span class="inline-flex items-center rounded-sm bg-gray-900 px-2 py-1 text-white dark:bg-gray-100 dark:text-gray-900">New</span>
                        @endif

                        @if($product->is_special)
                            <span class="inline-flex items-center rounded-sm bg-amber-50 px-2 py-1 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">Special</span>
                        @endif

                        @if($product->is_featured)
                            <span class="inline-flex items-center rounded-sm bg-gray-100 px-2 py-1 text-gray-700 dark:bg-gray-800 dark:text-gray-200">Featured</span>
                        @endif
                    </div>
                </div>
            </div>

            @if($images->isNotEmpty() || $primaryImage)
                <div class="flex gap-2 overflow-x-auto pb-1">
                    @foreach($images as $image)
                        @php $thumbUrl = $imageUrl($image->file_path); @endphp
                        @if($thumbUrl)
                            <button type="button" class="gallery-thumb h-16 w-16 shrink-0 overflow-hidden rounded-sm border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900" data-image-src="{{ $thumbUrl }}">
                                <img src="{{ $thumbUrl }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                            </button>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        <div class="space-y-4">
            <div class="space-y-2">
                <h1 class="text-2xl font-semibold leading-tight text-gray-900 dark:text-gray-50">{{ $product->name }}</h1>

                @if($product->short_description)
                    <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-300">{{ $product->short_description }}</p>
                @endif
            </div>

            <div class="flex flex-wrap gap-2 text-[10px]">
                <span class="inline-flex items-center rounded-sm border border-gray-200 bg-white px-2 py-1 text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                    {{ $sellUnitLabel }}
                </span>

                @if($productWeightLabel)
                    <span class="inline-flex items-center rounded-sm border border-gray-200 bg-white px-2 py-1 text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        {{ $productWeightLabel }}
                    </span>
                @endif

                @if($originCode)
                    <span class="inline-flex items-center rounded-sm border border-gray-200 bg-white px-2 py-1 text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        Origin: {{ $originCode }}
                    </span>
                @endif
            </div>

            <div class="rounded-sm border border-gray-200 bg-white p-5 space-y-4 dark:border-gray-800 dark:bg-gray-900">
                @if($isAssigned)
                    <div class="space-y-4">
                        <div>
                            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Your B2B buying options</h2>
                            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                Options shown here are approved for your account. Stock, variants, and internal piece availability remain hidden.
                            </p>
                        </div>

                        <div class="space-y-3">
                            @foreach($assignmentList as $option)
                                @php
                                    $sellUnit = $option->sellUnit;
                                    $optionPrice = (float) ($sellUnit ? $pricing->priceForSellUnit($user, $product, $sellUnit) : $pricing->priceFor($user, $product));
                                    $optionMoq = max((float) ($option->min_order_quantity ?? 1), 1);
                                    $optionMoqDisplay = rtrim(rtrim(number_format($optionMoq, 2), '0'), '.');
                                    $optionQty = rtrim(rtrim(number_format($optionMoq, 2, '.', ''), '0'), '.');
                                    $linkedVariants = $sellUnit?->variants ?? collect();
                                    $linkedVariantId = $linkedVariants->count() === 1 ? $linkedVariants->first()?->id : null;
                                    $hasVariants = (($product->variants_count ?? 0) > 0);
                                    $canOrderOption = $optionPrice > 0 && (($sellUnit && $linkedVariantId) || (!$sellUnit && !$hasVariants));
                                    $supportsPendingWeightOrder = $optionPrice > 0 && (
                                        in_array($sellUnit?->unit_type, ['kg', 'request_piece', 'request_weight'], true)
                                        || (!$sellUnit && $hasVariants)
                                    );
                                @endphp

                                <div class="rounded-sm border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-950/40">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <div class="text-[11px] uppercase tracking-wide text-gray-400">
                                                {{ $sellUnit ? 'Sellable unit' : 'Product-level terms' }}
                                            </div>
                                            <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">
                                                {{ $sellUnit?->display_label ?? $product->name }}
                                            </div>
                                            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                                MOQ: {{ $optionMoqDisplay }}
                                                @if($sellUnit?->pricing_unit)
                                                    · Pricing: {{ str_replace('_', ' ', $sellUnit->pricing_unit) }}
                                                @endif
                                            </div>
                                        </div>

                                        <div class="text-left sm:text-right">
                                            <div class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                                                {{ $optionPrice > 0 ? '₹' . number_format($optionPrice, 2) : 'Price to be confirmed' }}
                                            </div>
                                            @if($sellUnit?->pieces_per_unit)
                                                <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                                    {{ rtrim(rtrim(number_format((float)$sellUnit->pieces_per_unit, 3), '0'), '.') }} pcs per unit
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mt-3 flex flex-wrap items-end gap-3">
                                        @if($canOrderOption)
                                            <form method="POST" action="{{ route('b2b.cart.add') }}" class="flex flex-wrap items-end gap-3">
                                                @csrf
                                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                @if($sellUnit)
                                                    <input type="hidden" name="product_sell_unit_id" value="{{ $sellUnit->id }}">
                                                @endif
                                                @if($linkedVariantId)
                                                    <input type="hidden" name="product_variant_id" value="{{ $linkedVariantId }}">
                                                @endif

                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Quantity</label>
                                                    <input
                                                        type="number"
                                                        name="quantity"
                                                        value="{{ old('quantity', $optionQty) }}"
                                                        min="{{ $optionQty }}"
                                                        step="0.01"
                                                        class="mt-1 w-24 rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-950 dark:focus:ring-gray-500"
                                                    >
                                                </div>

                                                <button type="submit" class="inline-flex items-center justify-center rounded-sm border border-gray-900 bg-gray-900 px-4 py-1.5 text-xs font-medium text-white hover:bg-gray-800 dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">
                                                    Add to B2B cart
                                                </button>
                                            </form>
                                        @elseif($supportsPendingWeightOrder)
                                            <form method="POST" action="{{ route('b2b.cart.add') }}" class="w-full rounded-sm border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900">
                                                @csrf
                                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                @if($sellUnit)
                                                    <input type="hidden" name="product_sell_unit_id" value="{{ $sellUnit->id }}">
                                                @endif

                                                <div class="mb-3">
                                                    <div class="text-xs font-semibold text-gray-900 dark:text-gray-50">Add to B2B order</div>
                                                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                                        Order by pieces or approximate kg. The final invoice will be based on actual supplied weight entered by our team.
                                                    </p>
                                                </div>

                                                <div class="grid gap-3 sm:grid-cols-2">
                                                    <label class="rounded-sm border border-gray-200 p-3 text-[11px] dark:border-gray-800">
                                                        <span class="flex items-center gap-2 font-medium text-gray-900 dark:text-gray-50">
                                                            <input type="radio" name="b2b_order_mode" value="pieces" checked>
                                                            By pieces
                                                        </span>
                                                        <input type="number" min="1" step="1" name="requested_piece_count" placeholder="e.g. 3" class="mt-2 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs dark:border-gray-700 dark:bg-gray-950">
                                                    </label>

                                                    <label class="rounded-sm border border-gray-200 p-3 text-[11px] dark:border-gray-800">
                                                        <span class="flex items-center gap-2 font-medium text-gray-900 dark:text-gray-50">
                                                            <input type="radio" name="b2b_order_mode" value="weight">
                                                            Approx. weight
                                                        </span>
                                                        <input type="number" min="0.1" step="0.001" name="requested_weight_kg" placeholder="e.g. 12" class="mt-2 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs dark:border-gray-700 dark:bg-gray-950">
                                                    </label>
                                                </div>

                                                <button type="submit" class="mt-3 inline-flex items-center justify-center rounded-sm bg-gray-900 px-4 py-2 text-xs font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900">
                                                    Add to B2B order
                                                </button>
                                            </form>
                                        @else
                                            <div class="rounded-sm border border-dashed border-gray-300 px-3 py-2 text-[11px] text-gray-600 dark:border-gray-700 dark:text-gray-300">
                                                @if($optionPrice <= 0)
                                                    B2B pricing is pending for this option.
                                                @else
                                                    This option needs a linked variant/unit setup before online B2B ordering.
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <form method="POST" action="{{ route('b2b.wishlist.store') }}">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <button class="inline-flex items-center justify-center rounded-sm border border-gray-300 px-4 py-2 text-xs hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800">Save to wishlist</button>
                        </form>
                    </div>
                @elseif($isPending)
                    <div class="space-y-2">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Request pending</h2>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Your product access request is under review. We will confirm price, MOQ, variants, and availability privately.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        <div>
                            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Request portfolio access</h2>
                            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">This product is not currently assigned to your B2B account. Request access and our team will confirm price, MOQ, and availability.</p>
                        </div>

                        <form method="POST" action="{{ route('b2b.catalog.request', ['product' => $product->slug]) }}" class="space-y-3">
                            @csrf
                            <div>
                                <label class="mb-1 block text-[11px] font-medium text-gray-700 dark:text-gray-200">Expected quantity optional</label>
                                <input type="number" step="0.01" min="0.01" name="requested_quantity" value="{{ old('requested_quantity') }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-medium text-gray-700 dark:text-gray-200">Message optional</label>
                                <textarea name="message" rows="3" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">{{ old('message') }}</textarea>
                            </div>
                            <button class="w-full rounded-full bg-gray-900 px-4 py-2 text-[11px] font-medium text-white dark:bg-gray-100 dark:text-gray-900">Submit request</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="rounded-sm border border-gray-200 bg-white overflow-hidden dark:border-gray-800 dark:bg-gray-900" data-product-tabs>
        <div class="border-b border-gray-200 px-4 dark:border-gray-800 sm:px-6">
            <div class="flex flex-wrap gap-2 py-3">
                <button type="button" class="tab-btn inline-flex items-center rounded-sm px-4 py-2 text-[11px] font-medium transition" data-tab-target="description">Description</button>
                <button type="button" class="tab-btn inline-flex items-center rounded-sm px-4 py-2 text-[11px] font-medium transition" data-tab-target="recipes">
                    Recipes
                    @if($recipes->isNotEmpty())
                        <span class="ml-2 inline-flex min-w-5 items-center justify-center rounded-sm bg-gray-100 px-1.5 py-0.5 text-[10px] dark:bg-gray-800">{{ $recipes->count() }}</span>
                    @endif
                </button>
                <button type="button" class="tab-btn inline-flex items-center rounded-sm px-4 py-2 text-[11px] font-medium transition" data-tab-target="storage">Storage & Delivery</button>
                <button type="button" class="tab-btn inline-flex items-center rounded-sm px-4 py-2 text-[11px] font-medium transition" data-tab-target="info">Product Info</button>
            </div>
        </div>

        <div class="p-4 sm:p-6">
            <div class="tab-panel space-y-4" data-tab-panel="description">
                @if($product->description)
                    <div class="prose prose-sm max-w-none text-gray-700 dark:prose-invert dark:text-gray-200">{!! nl2br(e($product->description)) !!}</div>
                @elseif($product->short_description)
                    <div class="text-sm leading-relaxed text-gray-600 dark:text-gray-300">{{ $product->short_description }}</div>
                @else
                    <div class="rounded-sm border border-dashed border-gray-300 px-4 py-5 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">Description will appear here once product details are added.</div>
                @endif

                <div class="grid gap-3 md:grid-cols-3">
                    <div class="rounded-sm border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-950/40">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">Selling unit</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $sellUnitLabel }}</div>
                    </div>
                    <div class="rounded-sm border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-950/40">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">B2B MOQ</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $isAssigned ? $moqDisplay : 'On approval' }}</div>
                    </div>
                    <div class="rounded-sm border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-950/40">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">GST</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ number_format($gstRate, 2) }}%</div>
                    </div>
                </div>
            </div>

            <div class="tab-panel hidden space-y-4" data-tab-panel="recipes">
                @if($recipes->isEmpty())
                    <div class="rounded-sm border border-dashed border-gray-300 px-4 py-5 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">Recipes for this product will appear here soon.</div>
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
                            <details class="group rounded-sm border border-gray-200 bg-gray-50 overflow-hidden dark:border-gray-800 dark:bg-gray-950/40">
                                <summary class="list-none cursor-pointer px-4 py-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex gap-4">
                                            @if($recipeImage)
                                                <div class="h-20 w-20 shrink-0 overflow-hidden rounded-sm border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                                                    <img src="{{ $recipeImage }}" alt="{{ $title }}" class="h-full w-full object-cover">
                                                </div>
                                            @endif
                                            <div class="space-y-2">
                                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $title }}</div>
                                                @if($short)
                                                    <div class="text-xs text-gray-600 dark:text-gray-300">{{ $short }}</div>
                                                @endif
                                            </div>
                                        </div>
                                        <span class="text-[11px] text-gray-400 group-open:hidden">View</span>
                                    </div>
                                </summary>
                                <div class="border-t border-gray-200 px-4 pb-4 pt-4 dark:border-gray-800">
                                    @if($description)
                                        <div class="mb-4 text-sm leading-relaxed text-gray-700 dark:text-gray-200">{!! nl2br(e($description)) !!}</div>
                                    @endif
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div>
                                            <h4 class="mb-2 text-xs font-semibold text-gray-900 dark:text-gray-50">Ingredients</h4>
                                            @if($ingredients)
                                                <ul class="list-disc space-y-1 pl-5 text-xs text-gray-600 dark:text-gray-300">
                                                    @foreach($ingredients as $ingredient)
                                                        <li>{{ $ingredient }}</li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <div class="text-xs text-gray-400">Ingredients not added yet.</div>
                                            @endif
                                        </div>
                                        <div>
                                            <h4 class="mb-2 text-xs font-semibold text-gray-900 dark:text-gray-50">Steps</h4>
                                            @if($steps)
                                                <ol class="list-decimal space-y-1 pl-5 text-xs text-gray-600 dark:text-gray-300">
                                                    @foreach($steps as $step)
                                                        <li>{{ $step }}</li>
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

            <div class="tab-panel hidden space-y-4" data-tab-panel="storage">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-sm border border-gray-200 bg-gray-50 p-4 space-y-3 dark:border-gray-800 dark:bg-gray-950/40">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Storage guidance</h3>
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                            <li>Keep frozen at or below <strong>-18°C</strong>.</li>
                            <li>Once thawed, keep refrigerated and consume promptly.</li>
                            <li>Do not refreeze after complete thawing.</li>
                            <li>Cook thoroughly before serving where applicable.</li>
                        </ul>
                    </div>
                    <div class="rounded-sm border border-gray-200 bg-gray-50 p-4 space-y-3 dark:border-gray-800 dark:bg-gray-950/40">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Delivery & support</h3>
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                            <li>Delivered in cold-chain conditions where available.</li>
                            <li>Please inspect the package promptly on delivery.</li>
                            <li>Contact support quickly if you receive a damaged or incorrect item.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="tab-panel hidden" data-tab-panel="info">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-sm border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-950/40">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">Selling unit</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $sellUnitLabel }}</div>
                    </div>
                    @if($productWeightLabel)
                        <div class="rounded-sm border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-950/40">
                            <div class="text-[10px] uppercase tracking-wide text-gray-400">Product weight</div>
                            <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $productWeightLabel }}</div>
                        </div>
                    @endif
                    <div class="rounded-sm border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-950/40">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">B2B MOQ</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $isAssigned ? $moqDisplay : 'On approval' }}</div>
                    </div>
                    <div class="rounded-sm border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-950/40">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">GST</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ number_format($gstRate, 2) }}%</div>
                    </div>
                    @if($originCode)
                        <div class="rounded-sm border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-950/40">
                            <div class="text-[10px] uppercase tracking-wide text-gray-400">Country of origin</div>
                            <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $originCode }}</div>
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
            thumbs.forEach(function (t) { t.classList.remove('ring-2', 'ring-gray-400', 'dark:ring-gray-500'); });
            thumb.classList.add('ring-2', 'ring-gray-400', 'dark:ring-gray-500');
        });
    });

    if (thumbs.length) {
        thumbs[0].classList.add('ring-2', 'ring-gray-400', 'dark:ring-gray-500');
    }

    document.querySelectorAll('[data-product-tabs]').forEach(function (root) {
        const buttons = root.querySelectorAll('[data-tab-target]');
        const panels = root.querySelectorAll('[data-tab-panel]');

        function activate(target) {
            buttons.forEach(function (btn) {
                const active = btn.getAttribute('data-tab-target') === target;
                btn.classList.toggle('bg-gray-900', active);
                btn.classList.toggle('text-white', active);
                btn.classList.toggle('dark:bg-gray-100', active);
                btn.classList.toggle('dark:text-gray-900', active);
                btn.classList.toggle('text-gray-600', !active);
                btn.classList.toggle('dark:text-gray-300', !active);
            });

            panels.forEach(function (panel) {
                panel.classList.toggle('hidden', panel.getAttribute('data-tab-panel') !== target);
            });
        }

        if (buttons.length) {
            activate(buttons[0].getAttribute('data-tab-target'));
        }

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () { activate(btn.getAttribute('data-tab-target')); });
        });
    });
})();
</script>
@endsection
