@extends('layouts.customer')

@section('title', 'Cart')

@section('content')
@php
    $fmtQty = function ($qty) {
        $n = (float) $qty;
        return rtrim(rtrim(number_format($n, 2), '0'), '.');
    };

    $fmtW = function ($kg) {
        if ($kg === null) return '—';
        $n = (float) $kg;
        return rtrim(rtrim(number_format($n, 3), '0'), '.') . ' kg';
    };

    $unitLabel = function (?string $u) {
        $u = strtolower((string)$u);
        return $u === 'kg' ? 'kg' : 'pc';
    };

    $isB2BCart = auth()->check() && ((auth()->user()->customer_type ?? 'b2c') === 'b2b');
    $cartRoute = function (string $name, $parameter = null) use ($isB2BCart) {
        $routeName = $isB2BCart ? 'b2b.' . $name : $name;
        return $parameter === null ? route($routeName) : route($routeName, $parameter);
    };

    $groupedRows = collect();

    if (!empty($items) && $items->count() > 0) {
        $groupedRows = $items->groupBy(function ($it) {
            if (!empty($it->is_piece_selected) && !empty($it->selected_piece_meta['weight_kg'])) {
                return 'piece:' . $it->product_id . '|' . ($it->product_variant_id ?? 0) . '|' . number_format((float) $it->selected_piece_meta['weight_kg'], 3, '.', '');
            }

            return 'item:' . $it->id;
        })->values();
    }
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-xs space-y-4">

    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Your Cart</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Review items before checkout.
            </p>
        </div>

        <div class="flex items-center gap-2">
            @if(\Illuminate\Support\Facades\Route::has('shop.index'))
                <a href="{{ route('shop.index') }}"
                   class="text-[11px] px-3 py-1 rounded-sm border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                    Continue shopping
                </a>
            @else
                <a href="{{ route('home') }}"
                   class="text-[11px] px-3 py-1 rounded-sm border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                    Home
                </a>
            @endif
        </div>
    </div>

    @if(session('status'))
        <div class="rounded-sm border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if(!empty($pricingUpdatedCount) && $pricingUpdatedCount > 0)
        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-2 text-[11px] text-gray-700 dark:text-gray-200">
            Prices were updated for {{ $pricingUpdatedCount }} item(s) based on latest product pricing.
        </div>
    @endif

    @if(!empty($couponNotice))
        <div class="rounded-sm border border-yellow-300 bg-yellow-50 px-3 py-2 text-[11px] text-yellow-800">
            {{ $couponNotice }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-sm border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
            <ul class="list-disc pl-4 space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Empty cart --}}
    @if(!$cart || $items->count() === 0)
        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6">
            <p class="text-gray-700 dark:text-gray-200">Your cart is empty.</p>
            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                Add products to your cart and they will appear here.
            </p>
        </div>
    @else
        <div class="grid gap-4 lg:grid-cols-3">

            {{-- Items --}}
            <div class="lg:col-span-2 rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-[11px]">
                        <thead class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800">
                        <tr class="text-left text-gray-600 dark:text-gray-300">
                            <th class="px-3 py-2 font-medium">#</th>
                            <th class="px-3 py-2 font-medium">Item</th>
                            <th class="px-3 py-2 font-medium">Qty</th>
                            <th class="px-3 py-2 font-medium">Weight</th>
                            <th class="px-3 py-2 font-medium">Unit</th>
                            <th class="px-3 py-2 font-medium text-right">Total</th>
                            <th class="px-3 py-2 font-medium text-right">Remove</th>
                        </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($groupedRows as $rowGroup)
                            @php
                                /** @var \App\Models\CartItem $representative */
                                $representative = $rowGroup->first();
                                $product = $representative->product;
                                $variant = $representative->productVariant;

                                $name = $product?->name ?? 'Product';
                                $variantLabel = $variant ? ($variant->sku ?? ('Variant #' . $variant->id)) : null;
                                $gstRate = (float)($product?->gst_rate ?? 0);

                                $sellUnit = strtolower((string)($product?->sell_unit ?? 'piece'));
                                $isKg = $sellUnit === 'kg';

                                $isPieceGroup = (bool) ($representative->is_piece_selected ?? false);
                                $pieceMeta = $representative->selected_piece_meta ?? null;

                                $groupQty = $rowGroup->count();

                                $unitPrice = (float) ($representative->unit_price ?? 0);
                                $lineTotal = (float) $rowGroup->sum(fn ($it) => (float) ($it->total ?? 0));

                                $packWeight = (float)($product?->product_weight ?? 0);

                                if ($isPieceGroup) {
                                    $weightPerPiece = (float) ($pieceMeta['weight_kg'] ?? 0);
                                    $weightLabel = $pieceMeta['weight_label'] ?? $fmtW($weightPerPiece);
                                    $itemWeight = $weightPerPiece * $groupQty;
                                    $qtyDisplay = $groupQty;
                                    $perSlabPrice = (float) ($representative->total ?? 0);
                                } else {
                                    $qtyRaw = (float) ($representative->quantity ?? 1);

                                    if ($isKg) {
                                        $qtyDisplay = $fmtQty($qtyRaw);
                                        $itemWeight = (float) ($representative->item_weight ?? $qtyRaw);
                                    } else {
                                        $qtyInt = (int) max(round($qtyRaw), 1);
                                        $qtyDisplay = $qtyInt;
                                        $itemWeight = (float) ($representative->item_weight ?? ($qtyInt * $packWeight));
                                    }

                                    $perSlabPrice = null;
                                    $weightLabel = null;
                                }

                                static $counter = 0;
                                $counter++;

                                $manageStock = false;
                                $available = null;

                                if (!$isPieceGroup) {
                                    if ($variant && (bool)($variant->manage_stock ?? false)) {
                                        $manageStock = true;
                                        $available = (float)($variant->stock_quantity ?? 0);
                                    } elseif ($product && (bool)($product->manage_stock ?? false)) {
                                        $manageStock = true;
                                        $available = (float)($product->stock_quantity ?? 0);
                                    } elseif ($variant && $variant->stock_quantity !== null && (float)$variant->stock_quantity > 0) {
                                        $manageStock = true;
                                        $available = (float) $variant->stock_quantity;
                                    } elseif ($product && $product->stock_quantity !== null && (float)$product->stock_quantity > 0) {
                                        $manageStock = true;
                                        $available = (float) $product->stock_quantity;
                                    }
                                }

                                $maxQty = null;
                                if ($manageStock) {
                                    $available = max((float)$available, 0);
                                    $maxQty = $isKg ? round($available, 2) : (float) max((int) floor($available), 0);
                                }

                                $qtyForMath = $isPieceGroup
                                    ? (float) $groupQty
                                    : ($isKg ? (float) $representative->quantity : (float) max((int) round((float) $representative->quantity), 1));

                                $step = $isKg ? 0.01 : 1;
                                $minQty = $isKg ? 0.01 : 1;

                                $decDisabled = (!$isPieceGroup && ($qtyForMath <= ($minQty + 1e-9)));
                                $decQty = $isKg
                                    ? round(max($qtyForMath - $step, $minQty), 2)
                                    : (float) max((int) round($qtyForMath - $step), 1);
                                $incQty = $isKg
                                    ? round($qtyForMath + $step, 2)
                                    : (float) ((int) round($qtyForMath + $step));

                                $atMax = (!$isPieceGroup && $maxQty !== null && $maxQty > 0 && $qtyForMath >= ($maxQty - 1e-9));

                                $displayUnitPrice = $unitPrice;
                                $displayLineTotal = $lineTotal;
                                $displayPriceNote = null;

                                if ($product) {
                                    $quote = app(\App\Services\PricingService::class)->quote(auth()->user(), $product, $variant);
                                    $displayUnitPrice = (float) ($quote['price'] ?? $unitPrice);
                                    $displayPriceNote = ($quote['display_price_includes_gst'] ?? false) ? 'incl GST' : 'excl GST';

                                    if ($isPieceGroup) {
                                        $displayTaxMultiplier = ($quote['display_price_includes_gst'] ?? false)
                                            ? (1 + max($gstRate, 0) / 100)
                                            : 1;
                                        $perSlabPrice = round((float) ($representative->total ?? 0) * $displayTaxMultiplier, 2);
                                        $displayLineTotal = round($lineTotal * $displayTaxMultiplier, 2);
                                    } else {
                                        $pricingUnit = strtolower((string) ($variant?->pricing_unit ?? ($product?->pricing_unit ?? ($isKg ? 'kg' : 'pack'))));
                                        $displayLineTotal = $pricingUnit === 'kg'
                                            ? round(max((float) $itemWeight, 0) * $displayUnitPrice, 2)
                                            : round($qtyForMath * $displayUnitPrice, 2);
                                    }
                                }
                            @endphp

                            <tr class="text-gray-700 dark:text-gray-200">
                                <td class="px-3 py-2 whitespace-nowrap align-top">{{ $counter }}</td>

                                <td class="px-3 py-2">
                                    <div class="font-medium text-gray-900 dark:text-gray-50">{{ $name }}</div>

                                    @if(!$isB2BCart && $variantLabel)
                                        <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                                            {{ $variantLabel }}
                                        </div>
                                    @endif

                                    @if(!$isB2BCart && $isPieceGroup && !empty($pieceMeta))
                                        <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                                            Selected slab size: {{ $weightLabel }}
                                        </div>
                                    @elseif(!$isB2BCart && !empty($product?->product_weight))
                                        <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                                            Pack wt: {{ $fmtW($product->product_weight) }}
                                        </div>
                                    @endif

                                    <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                                        GST {{ $gstRate }}%
                                    </div>
                                </td>

                                <td class="px-3 py-2">
                                    @if($isPieceGroup)
                                        <div class="flex items-center gap-1">
                                            {{-- Remove one slab --}}
                                            <form method="POST" action="{{ $cartRoute('cart.update', $representative->id) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="quantity" value="{{ $groupQty }}">
                                                <input type="hidden" name="piece_group_action" value="dec">
                                                <button type="submit"
                                                        title="Remove one slab"
                                                        class="h-5 w-5 inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                                    –
                                                </button>
                                            </form>

                                            <span class="inline-flex items-center justify-center min-w-[28px] px-2 py-1 rounded-sm border border-gray-300 dark:border-gray-700 text-[11px]">
                                                {{ $groupQty }}
                                            </span>

                                            {{-- Add one more same-size slab --}}
                                            <form method="POST" action="{{ $cartRoute('cart.update', $representative->id) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="quantity" value="{{ $groupQty }}">
                                                <input type="hidden" name="piece_group_action" value="inc">
                                                <button type="submit"
                                                        title="Add one more slab of same size"
                                                        class="h-5 w-5 inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                                    +
                                                </button>
                                            </form>
                                        </div>

                                        <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                                            Exact-size grouped selection
                                        </div>
                                    @else
                                        <div class="flex items-center gap-1">
                                            {{-- Decrease --}}
                                            <form method="POST" action="{{ $cartRoute('cart.update', $representative->id) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="quantity" value="{{ $decQty }}">
                                                <button type="submit"
                                                        @disabled($decDisabled)
                                                        title="{{ $decDisabled ? 'Minimum reached' : 'Decrease quantity' }}"
                                                        class="h-5 w-5 inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-transparent">
                                                    –
                                                </button>
                                            </form>

                                            {{-- Manual update --}}
                                            <form method="POST"
                                                  action="{{ $cartRoute('cart.update', $representative->id) }}"
                                                  class="flex items-center gap-1 js-qty-update"
                                                  data-sell-unit="{{ $sellUnit }}"
                                                  data-min="{{ $minQty }}"
                                                  data-step="{{ $step }}"
                                                  @if(!$isB2BCart && $maxQty !== null && $maxQty > 0) data-max="{{ $maxQty }}" @endif>
                                                @csrf
                                                @method('PATCH')

                                                <input type="number"
                                                       name="quantity"
                                                       step="{{ $step }}"
                                                       min="{{ $minQty }}"
                                                       @if(!$isB2BCart && $maxQty !== null && $maxQty > 0) max="{{ $maxQty }}" @endif
                                                       value="{{ $qtyDisplay }}"
                                                       class="w-20 rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px]">
                                                <button type="submit"
                                                        class="px-2 py-1 rounded-sm border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 text-[10px]">
                                                    Update
                                                </button>
                                            </form>

                                            {{-- Increase --}}
                                            <form method="POST"
                                                  action="{{ $cartRoute('cart.update', $representative->id) }}"
                                                  class="js-inc-form"
                                                  data-current="{{ $qtyForMath }}"
                                                  data-step="{{ $step }}"
                                                  @if(!$isB2BCart && $maxQty !== null && $maxQty > 0) data-max="{{ $maxQty }}" @endif>
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="quantity" value="{{ $incQty }}">
                                                <button type="submit"
                                                        title="{{ $atMax ? 'Limited stock of this product available' : 'Increase quantity' }}"
                                                        class="h-5 w-5 inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 {{ $atMax ? 'opacity-40' : '' }}">
                                                    +
                                                </button>
                                            </form>
                                        </div>

                                        @if(!$isB2BCart && $maxQty !== null && $maxQty > 0)
                                            <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                                                Max: {{ $isKg ? $fmtQty($maxQty) : (int)$maxQty }} {{ $unitLabel($sellUnit) }}
                                            </div>
                                        @endif
                                    @endif
                                </td>

                                <td class="px-3 py-2 whitespace-nowrap">
                                    @if($isPieceGroup)
                                        <div>{{ $weightLabel }} each</div>
                                        @if($groupQty > 1)
                                            <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                                                {{ $fmtW($itemWeight) }} total
                                            </div>
                                        @endif
                                    @else
                                        {{ $fmtW($itemWeight) }}
                                    @endif
                                </td>

                                <td class="px-3 py-2 whitespace-nowrap">
                                    @if($isPieceGroup)
                                        ₹{{ number_format($perSlabPrice, 2) }} / slab
                                        @if($displayPriceNote)
                                            <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">{{ $displayPriceNote }}</div>
                                        @endif
                                    @else
                                        ₹{{ number_format($displayUnitPrice, 2) }}
                                        @if($displayPriceNote)
                                            <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">{{ $displayPriceNote }}</div>
                                        @endif
                                    @endif
                                </td>

                                <td class="px-3 py-2 whitespace-nowrap text-right">
                                    ₹{{ number_format($displayLineTotal, 2) }}
                                </td>

                                <td class="px-3 py-2 whitespace-nowrap text-right">
                                    @if($isPieceGroup)
                                        <form method="POST" action="{{ $cartRoute('cart.update', $representative->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="quantity" value="{{ $groupQty }}">
                                            <input type="hidden" name="piece_group_action" value="remove_all">
                                            <button type="submit"
                                                    onclick="return confirm('Remove all slabs of this size from cart?')"
                                                    class="inline-flex items-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1 text-[10px] hover:bg-gray-100 dark:hover:bg-gray-800">
                                                Remove all
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ $cartRoute('cart.destroy', $representative->id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    onclick="return confirm('Remove this item from cart?')"
                                                    class="inline-flex items-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1 text-[10px] hover:bg-gray-100 dark:hover:bg-gray-800">
                                                Remove
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>

                    </table>
                </div>
            </div>

            {{-- Summary --}}
            <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-3">
                <h2 class="font-semibold text-gray-900 dark:text-gray-50">Summary</h2>

                <div class="flex items-center justify-between text-[11px]">
                    <span class="text-gray-600 dark:text-gray-300">Subtotal <span class="text-[10px] text-gray-400">(excl GST)</span></span>
                    <span class="text-gray-900 dark:text-gray-50">₹{{ number_format($subtotal, 2) }}</span>
                </div>

                {{-- Coupon --}}
                @if(!$isB2BCart)
                    <div class="rounded-sm border border-gray-200 dark:border-gray-800 p-3 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] text-gray-600 dark:text-gray-300">Coupon</span>
                            @if(!empty($coupon))
                                <span class="text-[10px] rounded-sm border border-gray-300 dark:border-gray-700 px-2 py-0.5">
                                    {{ $coupon->code }}
                                </span>
                            @endif
                        </div>

                        @if(!empty($coupon))
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="text-gray-500 dark:text-gray-400">Discount</span>
                                <span class="text-gray-900 dark:text-gray-50">-₹{{ number_format($discount ?? 0, 2) }}</span>
                            </div>

                            <form method="POST" action="{{ route('cart.coupon.remove') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="w-full inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-4 py-2 text-[11px] hover:bg-gray-100 dark:hover:bg-gray-800">
                                    Remove coupon
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('cart.coupon.apply') }}" class="space-y-2">
                                @csrf
                                <input type="text" name="coupon_code" value="{{ old('coupon_code') }}"
                                       placeholder="Enter coupon code"
                                       class="w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]">
                                <button type="submit"
                                        class="w-full inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-4 py-2 text-[11px] hover:bg-gray-100 dark:hover:bg-gray-800">
                                    Apply coupon
                                </button>
                            </form>
                        @endif
                    </div>
                @endif

                <div class="flex items-center justify-between text-[11px]">
                    <span class="text-gray-600 dark:text-gray-300">Total after discount <span class="text-[10px] text-gray-400">(excl GST)</span></span>
                    <span class="text-gray-900 dark:text-gray-50">₹{{ number_format($totalAfterDiscount ?? $subtotal, 2) }}</span>
                </div>

                <div class="text-[10px] text-gray-500 dark:text-gray-400">
                    Item rows show your customer-facing price mode. The summary is ex-GST; taxes and shipping are calculated at checkout.
                </div>

                @auth
                    @if(auth()->user()->hasVerifiedEmail())
                        <a href="{{ $isB2BCart && \Illuminate\Support\Facades\Route::has('b2b.checkout.index') ? route('b2b.checkout.index') : route('checkout.index') }}"
                           class="w-full inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                            Proceed to checkout
                        </a>
                    @else
                        <a href="{{ route('verification.notice') }}"
                           class="w-full inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-4 py-2 text-[11px] hover:bg-gray-100 dark:hover:bg-gray-800">
                            Verify email to checkout
                        </a>
                    @endif
                @else
                    <a href="{{ $isB2BCart && \Illuminate\Support\Facades\Route::has('b2b.checkout.index') ? route('b2b.checkout.index') : route('checkout.index') }}"
                       class="w-full inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                        Sign in to checkout
                    </a>
                @endauth
            </div>

        </div>

        {{-- Stock limit + normalization --}}
        <script>
        (function () {
            const LIMITED_MSG = "Limited stock of this product available";

            document.querySelectorAll('form.js-inc-form').forEach((form) => {
                form.addEventListener('submit', (e) => {
                    const max = parseFloat(form.dataset.max || '');
                    const current = parseFloat(form.dataset.current || '');
                    const step = parseFloat(form.dataset.step || '1');

                    if (Number.isFinite(max) && max > 0 && Number.isFinite(current) && Number.isFinite(step)) {
                        if ((current + step) > (max + 1e-9)) {
                            e.preventDefault();
                            alert(LIMITED_MSG);
                        }
                    }
                });
            });

            document.querySelectorAll('form.js-qty-update').forEach((form) => {
                form.addEventListener('submit', () => {
                    const sellUnit = (form.dataset.sellUnit || 'piece').toLowerCase();
                    const min = parseFloat(form.dataset.min || '1');
                    const max = parseFloat(form.dataset.max || '');
                    const step = parseFloat(form.dataset.step || (sellUnit === 'kg' ? '0.01' : '1'));

                    const input = form.querySelector('input[name="quantity"]');
                    if (!input) return;

                    let v = parseFloat(input.value || '');
                    if (!Number.isFinite(v)) return;

                    if (sellUnit !== 'kg') {
                        v = Math.round(v);
                        if (v < 1) v = 1;
                    } else {
                        v = Math.round(v * 100) / 100;
                        if (v < min) v = min;
                        if (Number.isFinite(step) && step > 0) {
                            v = Math.round(v / step) * step;
                            v = Math.round(v * 100) / 100;
                        }
                    }

                    if (Number.isFinite(max) && max >= 0 && v > max) {
                        alert(LIMITED_MSG);
                        v = max;
                    }

                    input.value = String(v);
                });
            });
        })();
        </script>

    @endif
</div>
@endsection