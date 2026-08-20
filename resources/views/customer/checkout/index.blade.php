@extends('layouts.customer')

@section('title', 'Checkout')

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

    $isB2BCheckoutUser = (auth()->user()?->customer_type ?? 'b2c') === 'b2b';
    $gstContext = is_array($gstContext ?? null) ? $gstContext : [];
    $gstContextError = $gstContextError ?? null;
    $profileGstin = trim((string) ($profileGstin ?? auth()->user()?->gst_number ?? ''));
    $selectedBillingAddressId = (int) ($selectedBillingAddressId ?? $selectedAddressId ?? 0);

    $placeUrl = \Illuminate\Support\Facades\Route::has('checkout.place')
        ? route('checkout.place')
        : null;

    $backUrl = \Illuminate\Support\Facades\Route::has('cart.index') ? route('cart.index') : url('/');

    $backLabel = 'Back to cart';

    $cartRoute = function (string $name, $parameter = null) use ($isB2BCheckoutUser) {
        $routeName = $isB2BCheckoutUser && \Illuminate\Support\Facades\Route::has('b2b.' . $name)
            ? 'b2b.' . $name
            : $name;

        return $parameter === null ? route($routeName) : route($routeName, $parameter);
    };

    $checkoutReturnTo = request()->getRequestUri();
    $cartBulkDestroyUrl = \Illuminate\Support\Facades\Route::has($isB2BCheckoutUser ? 'b2b.cart.bulk-destroy' : 'cart.bulk-destroy')
        ? $cartRoute('cart.bulk-destroy')
        : url('/cart/items');
    $cartDestroyUrl = fn ($itemId) => $cartRoute('cart.destroy', $itemId);

    $bandaraCredit = $bandaraCreditRedemption ?? [];
    $bandaraCreditEnabled = (bool) ($bandaraCredit['enabled'] ?? false);
    $bandaraCreditAvailable = (int) ($bandaraCredit['available_points'] ?? 0);
    $bandaraCreditReserved = (int) ($bandaraCredit['reserved_points'] ?? 0);
    $bandaraCreditMinimum = (int) ($bandaraCredit['minimum_points'] ?? 0);
    $bandaraCreditMaxPoints = (int) ($bandaraCredit['max_redeemable_points'] ?? 0);
    $bandaraCreditMaxAmount = (float) ($bandaraCredit['max_redeemable_amount'] ?? 0);
    $bandaraCreditRequested = old('bandara_credit_points', request('bandara_credit_points', 0));

    $payLaterOption = $payLater ?? ($payLaterOption ?? []);
    $payLaterEligible = (bool) ($payLaterOption['eligible'] ?? false);
    $requestedPaymentMethod = old('payment_method', request('payment_method', 'razorpay'));
    $selectedPaymentMethod = ($requestedPaymentMethod === 'pay_later' && $payLaterEligible) ? 'pay_later' : 'razorpay';

    // BANDARA_CREDIT_RUNTIME_NORMALIZER_V1
    // Normalize Bandara Credit state across older/newer checkout controllers.
    // This prevents a stale/empty view array from rendering redemption as disabled
    // when App\Services\BandaraCreditService says redemption is enabled.
    $bandaraCreditState = [];
    foreach ([$bandaraCredit ?? null, $bandaraCreditRedemption ?? null, $bandaraCreditQuote ?? null] as $candidate) {
        if (is_array($candidate) && ! empty($candidate)) {
            $bandaraCreditState = array_merge($bandaraCreditState, $candidate);
        }
    }

    try {
        $bandaraCreditUser = auth()->user();
        if ($bandaraCreditUser && ! $isB2BCheckoutUser) {
            $bandaraCreditService = app(\App\Services\BandaraCreditService::class);
            $bandaraCreditStatus = method_exists($bandaraCreditService, 'redemptionStatusForUser')
                ? (array) $bandaraCreditService->redemptionStatusForUser($bandaraCreditUser)
                : [];

            $bandaraOrderAmount = (float) ($grandTotalBeforeBandaraCredit ?? $grandTotal ?? $cartTotal ?? $subtotal ?? 0);
            $bandaraRequestedPoints = old('bandara_credit_points', request('bandara_credit_points', $bandaraCreditState['requested_points'] ?? $bandaraCreditState['applied_points'] ?? null));
            $bandaraRequestedPoints = $bandaraRequestedPoints === null || $bandaraRequestedPoints === ''
                ? (int) ($bandaraCreditState['requested_points'] ?? $bandaraCreditState['applied_points'] ?? 0)
                : max(0, (int) $bandaraRequestedPoints);

            if (method_exists($bandaraCreditService, 'redemptionQuoteForCheckout')) {
                $bandaraFreshQuote = (array) $bandaraCreditService->redemptionQuoteForCheckout(
                    $bandaraCreditUser,
                    $bandaraOrderAmount,
                    $bandaraRequestedPoints,
                    ['source' => 'checkout_view_normalizer']
                );
            } elseif (method_exists($bandaraCreditService, 'previewRedemptionForAmount')) {
                $bandaraFreshQuote = (array) $bandaraCreditService->previewRedemptionForAmount(
                    $bandaraCreditUser,
                    $bandaraOrderAmount,
                    $bandaraRequestedPoints
                );
            } else {
                $bandaraFreshQuote = [];
            }

            $bandaraCreditState = array_merge($bandaraCreditState, $bandaraFreshQuote);

            if ($bandaraCreditStatus) {
                $bandaraCreditState['program_enabled'] = (bool) ($bandaraCreditStatus['program_enabled'] ?? $bandaraCreditState['program_enabled'] ?? $bandaraCreditState['enabled'] ?? false);
                $bandaraCreditState['shadow_mode'] = (bool) ($bandaraCreditStatus['shadow_mode'] ?? $bandaraCreditState['shadow_mode'] ?? false);
                $bandaraCreditState['redeem_enabled'] = (bool) ($bandaraCreditStatus['redeem_enabled'] ?? $bandaraCreditState['redeem_enabled'] ?? false);
                $bandaraCreditState['eligible_user'] = (bool) ($bandaraCreditStatus['eligible_user'] ?? $bandaraCreditState['eligible_user'] ?? false);
                $bandaraCreditState['enabled'] = (bool) ($bandaraCreditStatus['enabled'] ?? $bandaraCreditState['enabled'] ?? false);
                $bandaraCreditState['redemption_enabled'] = (bool) ($bandaraCreditStatus['enabled'] ?? $bandaraCreditState['redemption_enabled'] ?? $bandaraCreditState['enabled'] ?? false);

                if (! empty($bandaraCreditStatus['reason'])) {
                    $bandaraCreditState['reason'] = $bandaraCreditStatus['reason'];
                }

                if (! empty($bandaraCreditStatus['message']) && empty($bandaraCreditState['message'])) {
                    $bandaraCreditState['message'] = $bandaraCreditStatus['message'];
                }
            }
        }
    } catch (\Throwable $e) {
        $bandaraCreditState = is_array($bandaraCreditState) ? $bandaraCreditState : [];
    }

    if ($isB2BCheckoutUser) {
        $bandaraCreditState = [];
    }

    $bandaraCredit = $bandaraCreditRedemption = $bandaraCreditQuote = $bandaraCreditState;
    $bandaraCreditProgramEnabled = (bool) ($bandaraCreditState['program_enabled'] ?? ($bandaraCreditState['enabled'] ?? false));
    $bandaraCreditEligibleUser = (bool) ($bandaraCreditState['eligible_user'] ?? false);
    $bandaraCreditEnabled = (bool) ($bandaraCreditState['redemption_enabled'] ?? ($bandaraCreditState['enabled'] ?? false));
    $bandaraCreditCanRedeem = (bool) ($bandaraCreditState['can_redeem'] ?? ($bandaraCreditEnabled && (int) ($bandaraCreditState['max_redeemable_points'] ?? $bandaraCreditState['max_points'] ?? 0) > 0));
    $bandaraCreditAvailable = (int) ($bandaraCreditState['available_points'] ?? $bandaraCreditState['availablePoints'] ?? 0);
    $bandaraCreditReserved = (int) ($bandaraCreditState['reserved_points'] ?? $bandaraCreditState['reservedPoints'] ?? 0);
    $bandaraCreditMinimum = (int) ($bandaraCreditState['minimum_points'] ?? $bandaraCreditState['minimumPoints'] ?? 0);
    $bandaraCreditMaxPoints = (int) ($bandaraCreditState['max_redeemable_points'] ?? $bandaraCreditState['max_points'] ?? 0);
    $bandaraCreditPointValue = (float) ($bandaraCreditState['point_value'] ?? 1);
    $bandaraCreditMaxAmount = (float) ($bandaraCreditState['max_redeem_amount'] ?? $bandaraCreditState['max_amount'] ?? ($bandaraCreditMaxPoints * max($bandaraCreditPointValue, 0)));
    $bandaraCreditRequested = (int) ($bandaraCreditState['requested_points'] ?? $bandaraCreditState['applied_points'] ?? 0);
    $bandaraCreditAppliedPoints = (int) ($bandaraCreditState['points_to_redeem'] ?? $bandaraCreditState['applied_points'] ?? 0);
    $bandaraCreditAppliedAmount = (float) ($bandaraCreditState['redeem_amount'] ?? $bandaraCreditState['applied_amount'] ?? 0);
    $bandaraCreditState['applied_points'] = $bandaraCreditAppliedPoints;
    $bandaraCreditState['applied_amount'] = $bandaraCreditAppliedAmount;
    $bandaraCreditState['remaining_payable'] = (float) ($bandaraCreditState['order_amount_after_credit'] ?? max(0, ($grandTotal ?? 0) - $bandaraCreditAppliedAmount));
    $bandaraCreditAmount = (float) ($bandaraCreditAmount ?? $bandaraCreditAppliedAmount ?? 0);
    $bandaraCredit = $bandaraCreditRedemption = $bandaraCreditQuote = $bandaraCreditState;

    $bandaraCreditMessages = array_values(array_filter((array) ($bandaraCreditState['messages'] ?? [])));
    $bandaraCreditMessage = $bandaraCreditState['message'] ?? ($bandaraCreditMessages[0] ?? null);

    if ($bandaraCreditEnabled && is_string($bandaraCreditMessage) && str_contains(strtolower($bandaraCreditMessage), 'redemption is currently disabled')) {
        $bandaraCreditMessage = null;
    }
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-xs space-y-4">

    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Checkout</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Confirm address and review items (with weight).
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ $backUrl }}"
               class="text-[11px] px-3 py-1 rounded-sm border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                {{ $backLabel }}
            </a>
        </div>
    </div>

    @if(!empty($pricingUpdatedCount) && $pricingUpdatedCount > 0)
        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-2 text-[11px] text-gray-700 dark:text-gray-200">
            Your cart was updated for {{ $pricingUpdatedCount }} item(s) based on current availability and pricing.
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

    <div class="grid gap-4 lg:grid-cols-3">

        {{-- Items --}}
        <div class="lg:col-span-2 rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <form id="checkout-bulk-remove-form"
                  method="POST"
                  action="{{ $cartBulkDestroyUrl }}"
                  data-bandara-confirm="Remove the selected item(s) from cart?"
                  data-bandara-confirm-title="Remove selected items?"
                  data-bandara-confirm-text="Remove selected"
                  data-bandara-confirm-variant="danger">
                @csrf
                @method('DELETE')
                <input type="hidden" name="return_to" value="{{ $checkoutReturnTo }}">
            </form>

            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="font-semibold text-gray-900 dark:text-gray-50">Items</div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400">
                        Review cart items before placing the order.
                    </div>
                </div>

                <button type="submit"
                        form="checkout-bulk-remove-form"
                        data-checkout-bulk-remove-button
                        disabled
                        class="inline-flex items-center justify-center rounded-sm border border-red-200 dark:border-red-900 px-3 py-1 text-[10px] font-medium text-red-700 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-950/30 disabled:cursor-not-allowed disabled:opacity-50">
                    Remove selected
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-[11px]">
                    <thead class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800">
                    <tr class="text-left text-gray-600 dark:text-gray-300">
                        <th class="px-4 py-2 font-medium w-14">
                            <input type="checkbox"
                                   data-checkout-select-all
                                   class="rounded border-gray-300 dark:border-gray-700"
                                   aria-label="Select all checkout items">
                        </th>
                        <th class="px-4 py-2 font-medium">Item</th>
                        <th class="px-4 py-2 font-medium">Qty</th>
                        @if(!$isB2BCheckoutUser)
                            <th class="px-4 py-2 font-medium">Weight</th>
                        @endif
                        <th class="px-4 py-2 font-medium">Unit</th>
                        <th class="px-4 py-2 font-medium text-right">Total</th>
                        <th class="px-4 py-2 font-medium text-right">Remove</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($items as $it)
                        @php
                            $p = $it->product;

                            $sellUnit = strtolower((string)($it->sell_unit ?? $p?->sell_unit ?? 'pc'));
                            $isKg = $sellUnit === 'kg';

                            $qty = (float) ($it->quantity ?? 0);
                            $pw  = (float) ($p?->product_weight ?? 0);
                            $gstRate = app(\App\Services\GstRateService::class)->rateForProduct($p, auth()->user());

                            // FIX:
                            // For production/slab items, selected weight is stored in cart_items.item_weight.
                            // Only fall back to qty * product_weight when item_weight is empty.
                            $storedWeight = (float) ($it->item_weight ?? 0);
                            $lineWeight = $storedWeight > 0 ? $storedWeight : ($qty * $pw);

                            $displayUnitPrice = (float) ($it->unit_price ?? 0);
                            $displayLineTotal = (float) ($it->total ?? 0);
                            $displayPriceNote = null;

                            $variantLabel = null;

                            if ($p) {
                                $variant = $it->productVariant ?? null;
                                if ($variant) {
                                    $variantName = trim((string) ($variant->name ?? ''));
                                    $packType = (string) ($variant->pack_type ?? '');
                                    if ($variantName !== '') {
                                        $variantLabel = $variantName;
                                    } elseif ($packType === 'fixed_piece_pack' && (float) ($variant->pieces_per_pack ?? 0) > 0) {
                                        $variantLabel = rtrim(rtrim(number_format((float) $variant->pieces_per_pack, 3), '0'), '.') . ' pcs pack';
                                    } elseif ($packType === 'fixed_weight_pack' && (float) ($variant->product_weight ?? 0) > 0) {
                                        $variantLabel = rtrim(rtrim(number_format((float) $variant->product_weight, 3), '0'), '.') . ' kg pack';
                                    } else {
                                        $variantLabel = $variant->sku ?? ('Variant #' . $variant->id);
                                    }
                                }
                                $quote = app(\App\Services\PricingService::class)->quote(auth()->user(), $p, $variant);
                                $displayUnitPrice = (float) ($quote['price'] ?? $displayUnitPrice);
                                $pricingUnit = strtolower((string) ($variant?->pricing_unit ?? ($p?->pricing_unit ?? ($isKg ? 'kg' : 'pack'))));
                                $displayLineTotal = $pricingUnit === 'kg'
                                    ? round(max((float) $lineWeight, 0) * $displayUnitPrice, 2)
                                    : round($qty * $displayUnitPrice, 2);
                                $displayPriceNote = ($quote['display_price_includes_gst'] ?? false) ? 'incl GST' : 'excl GST';
                            }
                        @endphp

                        <tr class="text-gray-700 dark:text-gray-200">
                            <td class="px-4 py-2 whitespace-nowrap align-top">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox"
                                           name="cart_item_ids[]"
                                           value="{{ $it->id }}"
                                           form="checkout-bulk-remove-form"
                                           data-checkout-item-checkbox
                                           class="rounded border-gray-300 dark:border-gray-700"
                                           aria-label="Select {{ $p?->name ?? 'product' }} for removal">
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400">{{ $loop->iteration }}</span>
                                </div>
                            </td>

                            <td class="px-4 py-2">
                                <div class="font-medium text-gray-900 dark:text-gray-50">
                                    {{ $p?->name ?? 'Product' }}
                                </div>
                                @if($variantLabel)
                                    <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">{{ $variantLabel }}</div>
                                @endif

                                <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                    @if(!$isB2BCheckoutUser && !empty($p?->product_weight))
                                        Pack wt: {{ $fmtW($p->product_weight) }}
                                    @endif
                                    <br>
                                    Gst: {{ $gstRate }}%
                                </div>
                            </td>

                            <td class="px-4 py-2 whitespace-nowrap">
                                {{ $isKg ? $fmtQty($qty) : (int) $qty }} {{ $unitLabel($sellUnit) }}
                            </td>

                            @if(!$isB2BCheckoutUser)
                                <td class="px-4 py-2 whitespace-nowrap">
                                    {{ $fmtW($lineWeight) }}
                                </td>
                            @endif

                            <td class="px-4 py-2 whitespace-nowrap">
                                ₹{{ number_format($displayUnitPrice, 2) }}
                                @if($displayPriceNote)
                                    <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">{{ $displayPriceNote }}</div>
                                @endif
                            </td>

                            <td class="px-4 py-2 whitespace-nowrap text-right">
                                ₹{{ number_format($displayLineTotal, 2) }}
                            </td>

                            <td class="px-4 py-2 whitespace-nowrap text-right">
                                <form method="POST"
                                      action="{{ $cartDestroyUrl($it->id) }}"
                                      data-bandara-confirm="Remove this item from cart?"
                                      data-bandara-confirm-title="Remove item?"
                                      data-bandara-confirm-text="Remove"
                                      data-bandara-confirm-variant="danger">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="return_to" value="{{ $checkoutReturnTo }}">
                                    <button type="submit"
                                            class="inline-flex items-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1 text-[10px] hover:bg-gray-100 dark:hover:bg-gray-800">
                                        Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Summary + Place Order --}}
        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-3">

            {{-- <div>
                <div class="font-semibold text-gray-900 dark:text-gray-50">Delivery address</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Used to calculate GST</div>
            </div> --}}

            @if($placeUrl)
                <form method="POST" action="{{ $placeUrl }}" class="space-y-3" data-checkout-form>
                    @csrf

                    <input type="hidden" name="return_to" value="{{ $checkoutReturnTo }}">

                    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 sm:p-5">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                Delivery address
            </h2>
            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                Select the shipping address for this order.
            </p>
        </div>

        {{-- @if(!empty($addressCreateUrl))
            <a href="{{ $addressCreateUrl }}"
               class="inline-flex items-center rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                Add address
            </a>
        @endif --}}
    </div>

    @if($addresses->isEmpty())
        <div class="rounded-xl border border-amber-300 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/20 px-3 py-2 text-[11px] text-amber-800 dark:text-amber-300">
        You need at least one saved address before you can place this order.
    </div>
    @else
        <div class="mt-4 space-y-3">
            @foreach($addresses as $address)
                <label class="block cursor-pointer rounded-2xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-900">
                    <div class="flex items-start gap-3">
                        <input
                            type="radio"
                            name="address_id"
                            value="{{ $address->id }}"
                            class="mt-1 rounded border-gray-300 dark:border-gray-700"
                            @checked((int) old('address_id', $selectedAddressId) === (int) $address->id)
                            data-checkout-address-radio
                        >

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-50">
                                    {{ $address->full_name }}
                                </div>

                                @if($address->is_default_shipping)
                                    <span class="rounded-full bg-sky-100 dark:bg-sky-900/40 px-2 py-0.5 text-[10px] font-medium text-sky-700 dark:text-sky-300">
                                        Default shipping
                                    </span>
                                @endif

                                @if($address->is_default_billing)
                                    <span class="rounded-full bg-violet-100 dark:bg-violet-900/40 px-2 py-0.5 text-[10px] font-medium text-violet-700 dark:text-violet-300">
                                        Default billing
                                    </span>
                                @endif
                            </div>

                            <div class="mt-1 text-xs text-gray-600 dark:text-gray-300 leading-relaxed">
                                {{ $address->address_line1 }}
                                @if($address->address_line2), {{ $address->address_line2 }} @endif
                                <br>
                                {{ $address->city }}, {{ $address->state }} - {{ $address->pincode }}
                                <br>
                                {{ $address->country ?? 'India' }}
                                <br>
                                Phone: {{ $address->phone }}
                            </div>

                            @if(!empty($address->gstin))
                                <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                    GSTIN: {{ $address->gstin }}
                                </div>
                            @endif
                        </div>
                    </div>
                </label>
            @endforeach
        </div>

        @error('address_id')
            <p class="mt-2 text-[11px] text-red-600">{{ $message }}</p>
        @enderror

        <div class="mt-3">
            <a href="{{ $addressCreateUrl ?? route('account.addresses.create', ['return_to' => request()->fullUrl()]) }}"
               class="text-[11px] text-gray-600 dark:text-gray-300 hover:underline">
                Add another address
            </a>
        </div>
    @endif
</div>

                    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 sm:p-5">
                        <div>
                            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                                Billing / GST address
                            </h2>
                            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                Select the address to which the tax invoice will be raised. A saved GSTIN on this address takes priority; otherwise your account GSTIN is used.
                            </p>
                        </div>

                        @if($addresses->isNotEmpty())
                            <div class="mt-4 space-y-3">
                                @foreach($addresses as $billingOption)
                                    <label class="block cursor-pointer rounded-2xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-900">
                                        <div class="flex items-start gap-3">
                                            <input
                                                type="radio"
                                                name="billing_address_id"
                                                value="{{ $billingOption->id }}"
                                                class="mt-1 rounded border-gray-300 dark:border-gray-700"
                                                @checked((int) old('billing_address_id', $selectedBillingAddressId) === (int) $billingOption->id)
                                                data-checkout-billing-address-radio
                                            >

                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-50">
                                                        {{ $billingOption->full_name }}
                                                    </div>

                                                    @if($billingOption->is_default_billing)
                                                        <span class="rounded-full bg-violet-100 dark:bg-violet-900/40 px-2 py-0.5 text-[10px] font-medium text-violet-700 dark:text-violet-300">
                                                            Default billing
                                                        </span>
                                                    @endif
                                                </div>

                                                <div class="mt-1 text-xs text-gray-600 dark:text-gray-300 leading-relaxed">
                                                    {{ $billingOption->address_line1 }}
                                                    @if($billingOption->address_line2), {{ $billingOption->address_line2 }} @endif
                                                    <br>
                                                    {{ $billingOption->city }}, {{ $billingOption->state }} - {{ $billingOption->pincode }}
                                                </div>

                                                @if(!empty($billingOption->gstin))
                                                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                                        GSTIN: {{ $billingOption->gstin }}
                                                    </div>
                                                @elseif($profileGstin !== '')
                                                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                                        Account GSTIN will be used: {{ $profileGstin }}
                                                    </div>
                                                @else
                                                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                                        No GSTIN — GST will follow the delivery state.
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>

                            @error('billing_address_id')
                                <p class="mt-2 text-[11px] text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    <div data-gst-context-notice>
                        @if($gstContextError)
                            <div class="rounded-xl border border-red-200 dark:border-red-900/60 bg-red-50 dark:bg-red-950/25 px-3 py-2 text-[11px] text-red-700 dark:text-red-300">
                                GST details need correction: {{ $gstContextError }}
                            </div>
                        @elseif(!empty($gstContext['is_bill_to_ship_to']))
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 text-[11px] text-gray-600 dark:text-gray-300">
                                Bill-To / Ship-To order: the invoice place of supply is {{ $gstContext['place_of_supply_state_name'] ?? ('state code '.$gstContext['place_of_supply_gst_state_code']) }} from the Bill-To GSTIN, while delivery is to {{ $gstContext['ship_to_state_name'] ?? ('state code '.$gstContext['ship_to_gst_state_code']) }}.
                            </div>
                        @endif
                    </div>

                    <textarea name="customer_note"
                              rows="3"
                              placeholder="Optional note for your order…"
                              class="w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]">{{ old('customer_note') }}</textarea>

                    @if($isB2BCheckoutUser)
                        <div data-checkout-payment-methods class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-3">
                            <div>
                                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Payment method</h2>
                                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                    Choose online payment or approved B2B invoice terms.
                                </p>
                            </div>

                            <label class="flex gap-3 rounded-xl border border-gray-200 dark:border-gray-800 px-3 py-2 text-[11px]">
                                <input type="radio" name="payment_method" value="razorpay" @checked($selectedPaymentMethod !== 'pay_later')>
                                <span>
                                    <span class="block font-medium text-gray-900 dark:text-gray-50">Pay now online</span>
                                    <span class="block text-gray-500 dark:text-gray-400">Pay securely using Razorpay.</span>
                                </span>
                            </label>

                            @if($payLaterEligible)
                                <label class="flex gap-3 rounded-xl border border-gray-200 dark:border-gray-800 px-3 py-2 text-[11px]">
                                    <input type="radio" name="payment_method" value="pay_later" @checked($selectedPaymentMethod === 'pay_later')>
                                    <span>
                                        <span class="block font-medium text-gray-900 dark:text-gray-50">Pay later on invoice</span>
                                        <span class="block text-gray-500 dark:text-gray-400">
                                            Due in {{ (int) ($payLaterOption['terms_days'] ?? 0) }} day(s).
                                            Available credit: ₹{{ number_format((float) ($payLaterOption['available_credit'] ?? 0), 2) }}
                                        </span>
                                    </span>
                                </label>
                            @else
                                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 text-[11px] text-gray-600 dark:text-gray-300">
                                    Pay Later is not available: {{ $payLaterOption['reason'] ?? 'not approved for this account.' }}
                                </div>
                            @endif

                            @error('payment_method')
                                <p class="text-[11px] text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @else
                        <input type="hidden" name="payment_method" value="razorpay">
                    @endif

                    @if(!$isB2BCheckoutUser)
                    <div data-bandara-credit-section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Bandara Credit</h2>
                                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                    Reserve credits now. They are posted only after payment succeeds, or released on failed/cancelled orders.
                                </p>
                            </div>
                            <div class="text-right text-[11px]">
                                <div class="font-semibold text-gray-900 dark:text-gray-50">{{ number_format($bandaraCreditAvailable) }} available</div>
                                @if($bandaraCreditReserved > 0)
                                    <div class="text-amber-600 dark:text-amber-300">{{ number_format($bandaraCreditReserved) }} reserved</div>
                                @endif
                            </div>
                        </div>

                        @if($bandaraCreditEnabled && $bandaraCreditCanRedeem && $bandaraCreditMaxPoints > 0)
                            <div class="space-y-3">
                                <div>
                                    <label for="bandara_credit_points" class="block text-[11px] font-medium text-gray-700 dark:text-gray-200">
                                        Credits to redeem
                                    </label>
                                    <input
                                        id="bandara_credit_points"
                                        type="number"
                                        name="bandara_credit_points"
                                        min="0"
                                        max="{{ $bandaraCreditMaxPoints }}"
                                        step="1"
                                        value="{{ $bandaraCreditRequested }}"
                                        inputmode="numeric"
                                        data-bandara-credit-input
                                        class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-xs"
                                    >
                                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                        Maximum for this order: {{ number_format($bandaraCreditMaxPoints) }} credits
                                        @if($bandaraCreditMaxAmount > 0)
                                            (₹{{ number_format($bandaraCreditMaxAmount, 2) }})
                                        @endif
                                        @if($bandaraCreditMinimum > 0)
                                            · Minimum redemption: {{ number_format($bandaraCreditMinimum) }} credits
                                        @endif
                                    </p>
                                    @error('bandara_credit_points')
                                        <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        type="submit"
                                        formaction="{{ route('checkout.bandara-credit.apply') }}"
                                        formmethod="POST"
                                        formnovalidate
                                        data-bandara-credit-apply
                                        class="inline-flex items-center justify-center rounded-xl bg-gray-900 dark:bg-gray-100 px-3 py-2 text-[11px] font-medium text-white dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-white"
                                    >
                                        Apply credit
                                    </button>

                                    <button
                                        type="button"
                                        data-bandara-credit-use-maximum
                                        data-bandara-credit-maximum="{{ $bandaraCreditMaxPoints }}"
                                        class="inline-flex items-center justify-center rounded-xl border border-gray-300 dark:border-gray-700 px-3 py-2 text-[11px] font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800"
                                    >
                                        Use maximum
                                    </button>

                                    @if($bandaraCreditAppliedPoints > 0 || $bandaraCreditRequested > 0)
                                        <button
                                            type="submit"
                                            formaction="{{ route('checkout.bandara-credit.remove') }}"
                                            formmethod="POST"
                                            formnovalidate
                                            name="_method"
                                            value="DELETE"
                                            data-bandara-credit-remove
                                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 dark:border-gray-700 px-3 py-2 text-[11px] font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800"
                                        >
                                            Remove credit
                                        </button>
                                    @endif
                                </div>

                                @if($bandaraCreditAppliedPoints > 0)
                                    <div class="rounded-xl border border-emerald-200 dark:border-emerald-900 bg-emerald-50 dark:bg-emerald-950/25 px-3 py-2 text-[11px] text-emerald-800 dark:text-emerald-300">
                                        {{ number_format($bandaraCreditAppliedPoints) }} credits applied
                                        @if($bandaraCreditAppliedAmount > 0)
                                            · ₹{{ number_format($bandaraCreditAppliedAmount, 2) }} will be reserved when you place the order.
                                        @endif
                                    </div>
                                @endif

                                @if($bandaraCreditMessage)
                                    <div class="rounded-xl border border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-950/25 px-3 py-2 text-[11px] text-amber-800 dark:text-amber-300">
                                        {{ $bandaraCreditMessage }}
                                    </div>
                                @endif
                            </div>
                        @elseif($bandaraCreditEnabled)
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 text-[11px] text-gray-600 dark:text-gray-300">
                                {{ $bandaraCreditMessage ?: 'You do not currently have enough eligible Bandara Credit for this order.' }}
                            </div>
                        @else
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 text-[11px] text-gray-600 dark:text-gray-300">
                                {{ $bandaraCreditEnabled ? 'You do not currently have enough eligible Bandara Credit for this order.' : 'Bandara Credit redemption is currently disabled. You can still earn credits on eligible orders.' }}
                            </div>
                        @endif
                    </div>
                    @endif

                    <div data-checkout-totals class="border-t border-gray-200 dark:border-gray-800 pt-3 space-y-2">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-gray-600 dark:text-gray-300">Subtotal <span class="text-[10px] text-gray-400">(excl GST)</span></span>
                            <span class="text-gray-900 dark:text-gray-50">₹{{ number_format($subtotal, 2) }}</span>
                        </div>

                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-gray-600 dark:text-gray-300">Discount</span>
                            <span class="text-gray-900 dark:text-gray-50">-₹{{ number_format($discount ?? 0, 2) }}</span>
                        </div>

                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-gray-600 dark:text-gray-300">Taxable <span class="text-[10px] text-gray-400">(excl GST)</span></span>
                            <span class="text-gray-900 dark:text-gray-50">₹{{ number_format($taxable, 2) }}</span>
                        </div>

                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-gray-600 dark:text-gray-300">GST treatment</span>
                            <span class="font-medium text-gray-900 dark:text-gray-50">{{ $gstContext['tax_label'] ?? (($gst['gst_type'] ?? null) === 'intra_state' ? 'CGST + SGST' : 'IGST') }}</span>
                        </div>

                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-gray-600 dark:text-gray-300">Product GST</span>
                            <span class="text-gray-900 dark:text-gray-50">₹{{ number_format($gst['tax_total'] ?? 0, 2) }}</span>
                        </div>

                        @php
                            $deliveryQuote = $deliveryQuote ?? [];
                            $deliveryFee = (float) ($deliveryQuote['delivery_fee'] ?? 0);
                            $handlingFee = (float) ($deliveryQuote['handling_fee'] ?? 0);
                            $hasHandlingRule = !empty($deliveryQuote['handling_rule_id']) || $handlingFee > 0;
                            $handlingWasWaived = (bool) ($deliveryQuote['handling_free_handling_applied'] ?? false);
                            $chargeTax = (float) ($deliveryChargeTaxTotal ?? ($deliveryQuote['tax_total'] ?? 0));
                        @endphp

                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-gray-600 dark:text-gray-300">Delivery fee <span class="text-[10px] text-gray-400">(excl GST)</span></span>
                            <span class="text-gray-900 dark:text-gray-50">₹{{ number_format($deliveryFee, 2) }}</span>
                        </div>

                        @if($hasHandlingRule)
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="text-gray-600 dark:text-gray-300">Cold-chain handling & packing <span class="text-[10px] text-gray-400">(excl GST)</span></span>
                                <span class="text-gray-900 dark:text-gray-50">
                                    @if($handlingFee > 0)
                                        ₹{{ number_format($handlingFee, 2) }}
                                    @else
                                        Free
                                    @endif
                                </span>
                            </div>
                            @if($handlingWasWaived && (float) ($deliveryQuote['handling_fee_before_waiver'] ?? 0) > 0)
                                <div class="-mt-1 text-[10px] text-emerald-600 dark:text-emerald-300">
                                    Cold-chain handling waived for this order.
                                </div>
                            @endif
                        @endif

                        @if($chargeTax > 0)
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="text-gray-600 dark:text-gray-300">Delivery / handling GST</span>
                                <span class="text-gray-900 dark:text-gray-50">₹{{ number_format($chargeTax, 2) }}</span>
                            </div>
                        @endif

                        @if(($deliveryQuote['delivery_fee_source'] ?? null) === 'distance' && !empty($deliveryQuote['delivery_distance_km']))
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 text-[11px] text-gray-600 dark:text-gray-300">
                                Delivery distance: {{ number_format((float) $deliveryQuote['delivery_distance_km'], 2) }} km from store
                                @if(!empty($deliveryQuote['delivery_duration_minutes']))
                                    · approx. {{ (int) $deliveryQuote['delivery_duration_minutes'] }} min
                                @endif
                                @if(!empty($deliveryQuote['pincode']))
                                    · {{ $deliveryQuote['pincode'] }}
                                @endif
                                @if(($deliveryQuote['delivery_fee_formula'] ?? null) === 'base_plus_per_km')
                                    <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                                        Fee: ₹{{ number_format((float) ($deliveryQuote['delivery_base_fee'] ?? 0), 2) }} base
                                        @if((float) ($deliveryQuote['delivery_included_distance_km'] ?? 0) > 0)
                                            covers first {{ number_format((float) $deliveryQuote['delivery_included_distance_km'], 2) }} km
                                        @endif
                                        + ₹{{ number_format((float) ($deliveryQuote['delivery_per_km_fee'] ?? 0), 2) }} × {{ (int) ($deliveryQuote['delivery_chargeable_km_units'] ?? 0) }} started km after base.
                                    </div>
                                @endif
                            </div>
                        @elseif(!empty($deliveryQuote['zone_name']))
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 text-[11px] text-gray-600 dark:text-gray-300">
                                Delivery zone: {{ $deliveryQuote['zone_name'] }}
                                @if(!empty($deliveryQuote['pincode']))
                                    · {{ $deliveryQuote['pincode'] }}
                                @endif
                            </div>
                        @elseif(!empty($deliveryQuote['messages']))
                            <div class="rounded-xl border border-amber-200 dark:border-amber-900/50 bg-amber-50 dark:bg-amber-950/30 px-3 py-2 text-[11px] text-amber-800 dark:text-amber-200">
                                {{ $deliveryQuote['messages'][0] }}
                            </div>
                        @endif

                        @if(! $isB2BCheckoutUser && !empty($bandaraCredit['applied_points']))
                            <div class="flex items-center justify-between text-[11px] text-emerald-700 dark:text-emerald-300">
                                <span>Bandara Credit preview</span>
                                <span>-₹{{ number_format((float) ($bandaraCredit['applied_amount'] ?? 0), 2) }}</span>
                            </div>
                        @endif

                        <div class="flex items-center justify-between text-[12px] font-semibold pt-2">
                            <span class="text-gray-900 dark:text-gray-50">Grand total</span>
                            <span class="text-gray-900 dark:text-gray-50">₹{{ number_format($grandTotal, 2) }}</span>
                        </div>
                    
                        @if(! $isB2BCheckoutUser && !empty($bandaraCredit['applied_points']))
                            <div class="flex items-center justify-between text-[12px] font-semibold text-emerald-700 dark:text-emerald-300">
                                <span>Payable after Bandara Credit</span>
                                <span>₹{{ number_format((float) ($bandaraCredit['remaining_payable'] ?? $grandTotal), 2) }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center justify-between gap-3 pt-4">
                        <a href="{{ $backUrl }}"
                        class="text-xs text-gray-500 dark:text-gray-400 hover:underline">
                            {{ $backLabel }}
                        </a>

                        @if($addresses->isEmpty())
                            <a href="{{ $addressCreateUrl ?? route('account.addresses.create', ['return_to' => request()->fullUrl()]) }}"
                            class="inline-flex items-center justify-center rounded-xl bg-gray-900 dark:bg-gray-100 px-4 py-2 text-xs font-medium text-white dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-white">
                                Add address to continue
                            </a>
                        @else
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-xl bg-gray-900 dark:bg-gray-100 px-4 py-2 text-xs font-medium text-white dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-white"
                            >
                                Place order
                            </button>
                        @endif
                    </div>
                </form>
            @else
                <div class="rounded-sm border border-yellow-300 bg-yellow-50 px-3 py-2 text-[11px] text-yellow-800">
                    Checkout place route not found. Expected route name: <code>checkout.place</code>
                </div>
            @endif
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const checkboxes = Array.from(document.querySelectorAll('[data-checkout-item-checkbox]'));
    const selectAll = document.querySelector('[data-checkout-select-all]');
    const bulkButton = document.querySelector('[data-checkout-bulk-remove-button]');

    if (!checkboxes.length || !bulkButton) {
        return;
    }

    const updateBulkState = function () {
        const selectedCount = checkboxes.filter((checkbox) => checkbox.checked).length;

        bulkButton.disabled = selectedCount === 0;
        bulkButton.textContent = selectedCount > 0
            ? 'Remove selected (' + selectedCount + ')'
            : 'Remove selected';

        if (selectAll) {
            selectAll.checked = selectedCount === checkboxes.length;
            selectAll.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;
        }
    };

    checkboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', updateBulkState);
    });

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach((checkbox) => {
                checkbox.checked = selectAll.checked;
            });
            updateBulkState();
        });
    }

    updateBulkState();
})();
</script>

<script>
(function () {
    const form = document.querySelector('[data-checkout-form]');
    if (!form || !window.URL) {
        return;
    }

    let activeRequest = null;

    const selectedPaymentValue = function () {
        const checkedRadio = form.querySelector('input[type="radio"][name="payment_method"]:checked');
        if (checkedRadio && checkedRadio.value) {
            return checkedRadio.value;
        }

        const hiddenInput = form.querySelector('input[type="hidden"][name="payment_method"]');
        return hiddenInput && hiddenInput.value ? hiddenInput.value : null;
    };

    const buildCheckoutUrl = function (addressId, billingAddressId) {
        const url = new URL(window.location.href);

        if (addressId) {
            url.searchParams.set('address_id', addressId);
        } else {
            url.searchParams.delete('address_id');
        }

        if (billingAddressId) {
            url.searchParams.set('billing_address_id', billingAddressId);
        } else {
            url.searchParams.delete('billing_address_id');
        }

        const creditInput = form.querySelector('input[name="bandara_credit_points"]');
        const creditPoints = creditInput ? parseInt(creditInput.value || '0', 10) : 0;
        if (creditInput && creditPoints > 0) {
            url.searchParams.set('bandara_credit_points', String(creditPoints));
        } else {
            url.searchParams.delete('bandara_credit_points');
        }

        const paymentMethod = selectedPaymentValue();
        if (paymentMethod) {
            url.searchParams.set('payment_method', paymentMethod);
        } else {
            url.searchParams.delete('payment_method');
        }

        return url;
    };

    const updateReturnUrl = function (url) {
        const localReturnTo = url.pathname + url.search + url.hash;
        document.querySelectorAll('input[name="return_to"]').forEach((returnInput) => {
            returnInput.value = localReturnTo;
        });
    };

    const replaceSection = function (selector, nextDocument) {
        const current = document.querySelector(selector);
        const next = nextDocument.querySelector(selector);

        if (current && next) {
            current.replaceWith(next);
        } else if (current && !next) {
            current.remove();
        }
    };

    const setRefreshing = function (isRefreshing) {
        form.toggleAttribute('aria-busy', isRefreshing);

        ['[data-checkout-totals]', '[data-bandara-credit-section]'].forEach((selector) => {
            const section = document.querySelector(selector);
            if (section) {
                section.classList.toggle('opacity-60', isRefreshing);
                section.classList.toggle('pointer-events-none', isRefreshing);
            }
        });
    };

    const selectedAddressValue = function () {
        const selectedAddress = form.querySelector('[data-checkout-address-radio]:checked');
        return selectedAddress && selectedAddress.value ? selectedAddress.value : null;
    };

    const selectedBillingAddressValue = function () {
        const selectedAddress = form.querySelector('[data-checkout-billing-address-radio]:checked');
        return selectedAddress && selectedAddress.value ? selectedAddress.value : null;
    };

    const refreshCheckout = async function (
        addressId = selectedAddressValue(),
        billingAddressId = selectedBillingAddressValue()
    ) {
        const url = buildCheckoutUrl(addressId, billingAddressId);
        updateReturnUrl(url);

        if (!window.fetch || !window.DOMParser) {
            window.location.assign(url.toString());
            return;
        }

        if (activeRequest && activeRequest.abort) {
            activeRequest.abort();
        }

        const controller = window.AbortController ? new AbortController() : null;
        activeRequest = controller;
        setRefreshing(true);

        try {
            const response = await fetch(url.toString(), {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'text/html',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: controller ? controller.signal : undefined
            });

            if (!response.ok) {
                throw new Error('Checkout refresh failed.');
            }

            const html = await response.text();
            const nextDocument = new DOMParser().parseFromString(html, 'text/html');

            replaceSection('[data-checkout-payment-methods]', nextDocument);
            replaceSection('[data-bandara-credit-section]', nextDocument);
            replaceSection('[data-gst-context-notice]', nextDocument);
            replaceSection('[data-checkout-totals]', nextDocument);

            window.history.replaceState({}, '', url.toString());
        } catch (error) {
            if (error && error.name === 'AbortError') {
                return;
            }

            window.location.assign(url.toString());
        } finally {
            if (activeRequest === controller) {
                activeRequest = null;
            }
            setRefreshing(false);
        }
    };

    document.addEventListener('click', function (event) {
        const target = event.target instanceof Element ? event.target : null;
        if (!target) {
            return;
        }

        const applyButton = target.closest('[data-bandara-credit-apply]');
        if (applyButton && form.contains(applyButton)) {
            event.preventDefault();
            refreshCheckout();
            return;
        }

        const maximumButton = target.closest('[data-bandara-credit-use-maximum]');
        if (maximumButton && form.contains(maximumButton)) {
            event.preventDefault();

            const creditInput = form.querySelector('[data-bandara-credit-input]');
            if (creditInput) {
                creditInput.value = maximumButton.getAttribute('data-bandara-credit-maximum') || '0';
            }

            refreshCheckout();
            return;
        }

        const removeButton = target.closest('[data-bandara-credit-remove]');
        if (removeButton && form.contains(removeButton)) {
            event.preventDefault();

            const creditInput = form.querySelector('[data-bandara-credit-input]');
            if (creditInput) {
                creditInput.value = '0';
            }

            refreshCheckout();
        }
    });

    document.addEventListener('keydown', function (event) {
        const target = event.target instanceof Element ? event.target : null;
        const creditInput = target ? target.closest('[data-bandara-credit-input]') : null;

        if (!creditInput || !form.contains(creditInput) || event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        refreshCheckout();
    });

    document.addEventListener('change', function (event) {
        const target = event.target instanceof Element ? event.target : null;
        const addressRadio = target ? target.closest('[data-checkout-address-radio]') : null;
        const billingAddressRadio = target ? target.closest('[data-checkout-billing-address-radio]') : null;
        const changedRadio = addressRadio || billingAddressRadio;

        if (!changedRadio || !form.contains(changedRadio) || !changedRadio.checked) {
            return;
        }

        refreshCheckout();
    });
})();

</script>
@endpush

