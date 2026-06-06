<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\CustomerAddress;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InventoryPiece;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Services\B2BPayLaterService;
use App\Services\BandaraCreditService;
use App\Services\CartService;
use App\Services\DeliveryChargeService;
use App\Services\InvoicePdfService;
use App\Services\OrderInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use App\Models\Product;
use App\Models\ProductVariant;

class CheckoutController extends Controller
{
    public function index(Request $request, CartService $cartService)
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if ($request->routeIs('b2b.*') && ! $this->isB2BRequest($request)) {
            abort(403, 'B2B checkout is available only to B2B customers.');
        }

        $cart = $cartService->currentCart(false);
        if (! $cart) {
            return $this->emptyCartRedirect($request);
        }

        $pricingUpdatedCount = $this->syncCartPricesPreservingWeights($cartService, $cart);

        $items = CartItem::with(['product.hsnCode', 'productVariant'])
            ->where('cart_id', $cart->id)
            ->orderBy('id')
            ->get();

        if ($items->isEmpty()) {
            $cart->delete();
            session()->forget('cart_id');
            return $this->emptyCartRedirect($request);
        }

        $subtotal = (float) $items->sum('total');

        $addresses = CustomerAddress::query()
            ->where('user_id', $user->id)
            ->orderByDesc('is_default_shipping')
            ->orderByDesc('id')
            ->get();

        $defaultAddress = $addresses->firstWhere('is_default_shipping', true) ?? $addresses->first();

        $selectedAddressId = (int) ($request->query('address_id') ?: old('address_id', $defaultAddress?->id));
        $selectedAddress = $addresses->firstWhere('id', $selectedAddressId) ?? $defaultAddress;

        $isB2B = (($user->customer_type ?? 'b2c') === 'b2b');

        if ($isB2B && $cart->coupon_id) {
            $cart->coupon_id = null;
            $cart->save();
        }

        $couponNotice = null;
        $coupon = null;
        $discount = 0.0;

        if (! $isB2B) {
            $couponNotice = $cartService->ensureCouponStillValid($cart, $subtotal, $user);
            $coupon = $cart->coupon_id ? Coupon::find($cart->coupon_id) : null;
            $discount = $cartService->calculateCouponDiscount($coupon, $subtotal);
        }

        $taxable = max($subtotal - $discount, 0);

        $gst = $this->calculateGstFromItems($items, (float) $discount, $selectedAddress?->state, $user);
        $deliveryQuote = app(DeliveryChargeService::class)->quote($user, $selectedAddress, $taxable);

        $shippingTotal = round((float) ($deliveryQuote['fee_total'] ?? 0), 2);
        $deliveryChargeTaxTotal = round((float) ($deliveryQuote['tax_total'] ?? 0), 2);
        $grandTotal = round($taxable + (float) $gst['tax_total'] + $shippingTotal + $deliveryChargeTaxTotal, 2);

        $payLaterOption = app(B2BPayLaterService::class)->checkoutOptionFor($user, $grandTotal);

        $requestedBandaraCreditPoints = $isB2B ? 0 : max(0, (int) old(
            'bandara_credit_points',
            $request->input('bandara_credit_points', 0)
        ));

        $bandaraCreditQuote = $isB2B
            ? []
            : $this->bandaraCreditQuoteForCheckout($user, $grandTotal, $requestedBandaraCreditPoints, [
                'source' => 'checkout_index',
            ]);

        $itemWeight = 0.0;
        $sellUnit = 'piece';
        foreach ($items as $it) {
            $product = $it->product ?: Product::query()->with('hsnCode')->find($it->product_id);

            $itemWeight += (float) ($it->item_weight ?? 0);
            $sellUnit = (string) ($product?->sell_unit ?? 'piece');
        }

        return view('customer.checkout.index', [
            'cart'                => $cart,
            'items'               => $items,
            'subtotal'            => $subtotal,

            'addresses'           => $addresses,
            'selectedAddress'     => $selectedAddress,
            'selectedAddressId'   => $selectedAddress?->id,
            'addressCreateUrl'    => $addresses->isEmpty() ? $this->addressCreateUrl($request, $this->checkoutIndexUrl($request)) : null,

            'coupon'              => $coupon,
            'discount'            => $discount,
            'couponNotice'        => $couponNotice,

            'taxable'             => $taxable,
            'gst'                 => $gst,
            'deliveryQuote'       => $deliveryQuote,
            'deliveryChargeTaxTotal' => $deliveryChargeTaxTotal,
            'shippingTotal'       => $shippingTotal,
            'grandTotal'          => $grandTotal,

            'pricingUpdatedCount' => $pricingUpdatedCount,
            'itemWeight'          => $itemWeight,
            'sellUnit'            => $sellUnit,
            'payLaterOption'      => $payLaterOption,
            'bandaraCreditQuote'  => $bandaraCreditQuote,
            'bandaraCreditRedemption' => $bandaraCreditQuote,
            'bandaraCredit'       => $bandaraCreditQuote,
            'grandTotalBeforeBandaraCredit' => $grandTotal,
        ]);
    }

    public function place(Request $request, CartService $cartService)
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if ($request->routeIs('b2b.*') && ! $this->isB2BRequest($request)) {
            abort(403, 'B2B checkout is available only to B2B customers.');
        }

        $addresses = CustomerAddress::query()
            ->where('user_id', $user->id)
            ->orderByDesc('is_default_shipping')
            ->orderByDesc('id')
            ->get();

        if ($addresses->isEmpty()) {
            $returnTo = $this->checkoutIndexUrl($request);

            $request->session()->put($this->addressReturnToSessionKey(), $returnTo);

            return redirect()
                ->to($this->addressCreateUrl($request, $returnTo))
                ->with('status', 'Please add a delivery address before placing your order.');
        }

        $defaultAddress = $addresses->firstWhere('is_default_shipping', true) ?? $addresses->first();

        if (! $request->filled('address_id') && $defaultAddress) {
            $request->merge(['address_id' => $defaultAddress->id]);
        }

        if (! $request->filled('payment_method')) {
            $request->merge(['payment_method' => 'razorpay']);
        }

        $data = $request->validate([
            'address_id'      => ['required', 'integer'],
            'customer_note'   => ['nullable', 'string', 'max:5000'],
            'payment_method'  => ['required', 'in:razorpay,pay_later'],
            'bandara_credit_points' => ['nullable', 'integer', 'min:0'],
        ]);

        $address = $addresses->firstWhere('id', (int) $data['address_id']);

        if (! $address) {
            return redirect()
                ->to($this->checkoutIndexUrl($request))
                ->withErrors(['address_id' => 'Please select a valid delivery address.'])
                ->withInput();
        }

        $cart = $cartService->currentCart(false);
        if (! $cart) {
            return $this->emptyCartRedirect($request);
        }

        $this->syncCartPricesPreservingWeights($cartService, $cart);

        $items = CartItem::with(['product.hsnCode', 'productVariant'])
            ->where('cart_id', $cart->id)
            ->orderBy('id')
            ->get();

        if ($items->isEmpty()) {
            $cart->delete();
            session()->forget('cart_id');
            return $this->emptyCartRedirect($request);
        }

        $stockError = $this->validateCartStockBeforeOrder($items);
        if ($stockError !== null) {
            return redirect()
                ->to($this->appendQueryParameter($this->checkoutIndexUrl($request), 'address_id', (string) $address->id))
                ->withErrors(['checkout' => $stockError])
                ->withInput();
        }

        // ✅ MOQ enforcement (B2B)
        $isB2B = (($user->customer_type ?? 'b2c') === 'b2b');

        if ($isB2B) {
            $terms = app(\App\Services\B2BTermsService::class);

            foreach ($items as $it) {
                if ($it->product && ! $terms->canBuy($user, $it->product, $it->productVariant?->sellUnit, $it->productVariant)) {
                    return $this->b2bCartBlockedRedirect()
                        ->withErrors(['cart' => ($it->product->name ?? 'This product') . ' does not have a configured B2B price. Please contact the team before buying.']);
                }
            }

            $moqAdjusted = false;

            DB::transaction(function () use ($items, $terms, $user, &$moqAdjusted) {
                foreach ($items as $it) {
                    if (! $it->product) {
                        continue;
                    }

                    $min = (float) $terms->minOrderQty($user, $it->product, $it->productVariant?->sellUnit, $it->productVariant);
                    if ($min <= 0) {
                        $min = 1.0;
                    }

                    $minInt = (int) ceil($min);
                    $qty = (int) $it->quantity;

                    if ($qty < $minInt) {
                        $it->quantity = $minInt;
                        $it->total = $this->cartLineTotal($it);
                        $it->save();
                        $moqAdjusted = true;
                    }
                }
            });

            if ($moqAdjusted) {
                return redirect()
                    ->to($this->appendQueryParameter($this->checkoutIndexUrl($request), 'address_id', (string) $address->id))
                    ->with('status', 'Some quantities were increased to meet MOQ. Please review your updated totals and place the order again.')
                    ->withInput();
            }
        }

        $subtotal = (float) $items->sum('total');

        if ($isB2B && $cart->coupon_id) {
            $cart->coupon_id = null;
            $cart->save();
        }

        if (! $isB2B) {
            $cartService->ensureCouponStillValid($cart, $subtotal, $user);
        }

        $cart->refresh();

        $coupon = (! $isB2B && $cart->coupon_id) ? Coupon::find($cart->coupon_id) : null;
        $discountTotal = $isB2B ? 0.0 : $cartService->calculateCouponDiscount($coupon, $subtotal);

        $taxable = max($subtotal - $discountTotal, 0);

        // ✅ GST per item using configured product/HSN/default rates.
        $gst = $this->calculateGstFromItems($items, (float) $discountTotal, $address->state, $user);
        $deliveryQuote = app(DeliveryChargeService::class)->quote($user, $address, $taxable);

        if (! ($deliveryQuote['serviceable'] ?? true)) {
            return redirect()
                ->to($this->appendQueryParameter($this->checkoutIndexUrl($request), 'address_id', (string) $address->id))
                ->withErrors(['address_id' => ($deliveryQuote['messages'][0] ?? 'This delivery address is not currently serviceable.')])
                ->withInput();
        }

        $shippingTotal = round((float) ($deliveryQuote['fee_total'] ?? 0), 2);
        $deliveryChargeTaxTotal = round((float) ($deliveryQuote['tax_total'] ?? 0), 2);
        $deliveryChargeGst = app(DeliveryChargeService::class)->splitChargeTaxForState($deliveryQuote, $address->state);
        $grandTotal = round($taxable + (float) $gst['tax_total'] + $shippingTotal + $deliveryChargeTaxTotal, 2);

        $paymentMethod = (string) ($data['payment_method'] ?? 'razorpay');
        $payLaterOption = app(B2BPayLaterService::class)->checkoutOptionFor($user, $grandTotal);

        if ($paymentMethod === 'pay_later' && ! ($payLaterOption['eligible'] ?? false)) {
            return redirect()
                ->to($this->appendQueryParameter($this->checkoutIndexUrl($request), 'address_id', (string) $address->id))
                ->withErrors(['payment_method' => $payLaterOption['reason'] ?? 'Pay Later is not available for this order.'])
                ->withInput();
        }

        $requestedBandaraCreditPoints = $isB2B ? 0 : max(0, (int) ($data['bandara_credit_points'] ?? 0));
        $bandaraCreditQuote = [];
        $bandaraCreditPointsToRedeem = 0;
        $bandaraCreditRedeemAmount = 0.0;
        $grandTotalBeforeBandaraCredit = $grandTotal;
        $payableGrandTotal = $grandTotal;

        if (! $isB2B && $paymentMethod === 'razorpay') {
            $bandaraCreditQuote = $this->bandaraCreditQuoteForCheckout($user, $grandTotal, $requestedBandaraCreditPoints, [
                'source' => 'checkout_place',
            ]);

            if ($requestedBandaraCreditPoints > 0) {
                $bandaraCreditPointsToRedeem = max(0, (int) ($bandaraCreditQuote['points_to_redeem'] ?? 0));
                $bandaraCreditRedeemAmount = round(max(0, (float) ($bandaraCreditQuote['redeem_amount'] ?? 0)), 2);

                if ($bandaraCreditPointsToRedeem <= 0 || $bandaraCreditRedeemAmount <= 0) {
                    return redirect()
                        ->to($this->appendQueryParameter($this->checkoutIndexUrl($request), 'address_id', (string) $address->id))
                        ->withErrors(['bandara_credit_points' => $bandaraCreditQuote['message'] ?? 'Bandara Credit cannot be applied to this order.'])
                        ->withInput();
                }

                $payableGrandTotal = round(max(0, $grandTotal - $bandaraCreditRedeemAmount), 2);

                if ($payableGrandTotal <= 0) {
                    return redirect()
                        ->to($this->appendQueryParameter($this->checkoutIndexUrl($request), 'address_id', (string) $address->id))
                        ->withErrors(['bandara_credit_points' => 'Bandara Credit cannot cover the full payable amount yet. Please reduce the credits used.'])
                        ->withInput();
                }
            }
        }

        $order = null;

        DB::transaction(function () use (
            $user,
            $cart,
            $items,
            $coupon,
            $discountTotal,
            $subtotal,
            $gst,
            $deliveryQuote,
            $deliveryChargeGst,
            $shippingTotal,
            $grandTotal,
            $grandTotalBeforeBandaraCredit,
            $payableGrandTotal,
            $bandaraCreditQuote,
            $bandaraCreditPointsToRedeem,
            $bandaraCreditRedeemAmount,
            $address,
            $data,
            $paymentMethod,
            $payLaterOption,
            &$order
        ) {
            $order = new Order();
            $order->order_number = 'ORD-' . now()->format('dmy') . '-' . Str::upper(Str::random(6));
            $order->user_id = $user->id;

            $order->status = 'processing';

            $order->subtotal = round($subtotal, 2);
            $order->discount_total = round($discountTotal, 2);
            $order->tax_total = round((float) $gst['tax_total'] + (float) ($deliveryChargeGst['tax_total'] ?? 0), 2);
            $order->shipping_total = round($shippingTotal, 2);
            $order->grand_total = round($payableGrandTotal, 2);

            if (Schema::hasColumn('orders', 'delivery_zone_id')) {
                $order->delivery_zone_id = $deliveryQuote['zone_id'] ?? null;
            }
            if (Schema::hasColumn('orders', 'delivery_pincode')) {
                $order->delivery_pincode = $deliveryQuote['pincode'] ?? $address->pincode;
            }
            foreach ([
                'delivery_fee',
                'handling_fee',
                'delivery_tax_amount',
                'handling_tax_amount',
                'delivery_tax_rate',
                'handling_tax_rate',
            ] as $deliveryColumn) {
                if (Schema::hasColumn('orders', $deliveryColumn)) {
                    $order->{$deliveryColumn} = round((float) ($deliveryQuote[$deliveryColumn] ?? 0), 2);
                }
            }

            if (Schema::hasColumn('orders', 'bandara_credit_redeemed_points')) {
                $order->bandara_credit_redeemed_points = $bandaraCreditPointsToRedeem;
            }
            if (Schema::hasColumn('orders', 'bandara_credit_redeemed_amount')) {
                $order->bandara_credit_redeemed_amount = round($bandaraCreditRedeemAmount, 2);
            }
            if (Schema::hasColumn('orders', 'bandara_credit_points_redeemed')) {
                $order->bandara_credit_points_redeemed = $bandaraCreditPointsToRedeem;
            }
            if (Schema::hasColumn('orders', 'bandara_credit_discount_total')) {
                $order->bandara_credit_discount_total = round($bandaraCreditRedeemAmount, 2);
            }
            if (Schema::hasColumn('orders', 'bandara_credit_order_total_before_redemption')) {
                $order->bandara_credit_order_total_before_redemption = round($grandTotalBeforeBandaraCredit, 2);
            }

            $order->coupon_id = $coupon?->id;

            $order->gst_type = $gst['gst_type'];
            $order->cgst_amount = $this->addNullableAmounts($gst['cgst_amount'] ?? null, $deliveryChargeGst['cgst_amount'] ?? null);
            $order->sgst_amount = $this->addNullableAmounts($gst['sgst_amount'] ?? null, $deliveryChargeGst['sgst_amount'] ?? null);
            $order->igst_amount = $this->addNullableAmounts($gst['igst_amount'] ?? null, $deliveryChargeGst['igst_amount'] ?? null);

            $order->payment_status = 'pending';

            if (Schema::hasColumn('orders', 'payment_method')) {
                $order->payment_method = $paymentMethod;
            }

            if ($paymentMethod === 'pay_later') {
                $termsDays = (int) ($payLaterOption['terms_days'] ?? 7);
                $dueAt = now()->addDays(max($termsDays, 1));

                if (Schema::hasColumn('orders', 'payment_terms_days')) {
                    $order->payment_terms_days = $termsDays;
                }

                if (Schema::hasColumn('orders', 'payment_due_at')) {
                    $order->payment_due_at = $dueAt;
                }

                if (Schema::hasColumn('orders', 'pay_later_approved_at')) {
                    $order->pay_later_approved_at = now();
                }
            }

            $order->customer_note = $data['customer_note'] ?? null;
            $order->placed_at = now();
            $order->save();

            if ($bandaraCreditPointsToRedeem > 0 && $bandaraCreditRedeemAmount > 0) {
                $reserveResult = app(BandaraCreditService::class)->reserveRedemptionForOrder(
                    $order,
                    $bandaraCreditPointsToRedeem,
                    $bandaraCreditRedeemAmount,
                    [
                        'source' => 'checkout_place',
                        'order_amount_before_credit' => round($grandTotalBeforeBandaraCredit, 2),
                        'order_amount_after_credit' => round($payableGrandTotal, 2),
                        'quote' => $bandaraCreditQuote,
                    ]
                );

                if (($reserveResult['action'] ?? null) !== 'reserved') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'bandara_credit_points' => $reserveResult['message']
                            ?? $bandaraCreditQuote['message']
                            ?? 'Bandara Credit could not be reserved for this order. Please try again.',
                    ]);
                }
            }

            foreach (['shipping', 'billing'] as $type) {
                $oa = new OrderAddress();
                $oa->order_id = $order->id;
                $oa->type = $type;

                $oa->full_name = $address->full_name;
                $oa->phone = $address->phone;

                $oa->address_line1 = $address->address_line1;
                $oa->address_line2 = $address->address_line2;
                $oa->city = $address->city;
                $oa->state = $address->state;
                $oa->state_code = $address->state_code;
                $oa->country = $address->country ?? 'India';
                $oa->pincode = $address->pincode;

                $oa->gstin = $address->gstin;
                $oa->save();
            }

            // ✅ Allocate discount + tax across items using per-item gst_rate
            $alloc = $this->allocateLineDiscountAndTaxUsingRates($items, (float) $discountTotal, $gst);

            $orderItems = [];
            foreach ($items as $it) {
                $product = $it->product;
                $getproduct = Product::query()->find($it->product_id);

                $selectedPieceSnapshot = $this->selectedPieceSnapshotForCartItem($it);
                $lineVariantId = (int) ($it->product_variant_id ?: ($selectedPieceSnapshot['variant_id'] ?? 0));
                $lineVariantId = $lineVariantId > 0 ? $lineVariantId : null;

                $variant = $lineVariantId
                    ? ProductVariant::query()
                        ->where('id', $lineVariantId)
                        ->where('product_id', $it->product_id)
                        ->first()
                    : $it->productVariant;

                $sellUnit = $getproduct?->sell_unit;

                $getpriceUnit = $variant?->pricing_unit ?: ($sellUnit === 'kg' ? 'kg' : 'pack');

                $line = $alloc[$it->id];

                $oi = new OrderItem();
                $oi->order_id = $order->id;

                $oi->product_id = $it->product_id;
                $oi->product_variant_id = $variant?->id ?: $lineVariantId;

                $oi->product_name = $product?->name ?? 'Product';
                $oi->pricing_unit = $getpriceUnit;
                $oi->sell_unit = $sellUnit;
                $oi->sku = $variant?->sku ?? $product?->sku;

                $snapshot = [];
                if ($variant) {
                    $snapshot['variant_id'] = $variant->id;
                    $snapshot['variant_sku'] = $variant->sku;
                    $snapshot['variant_name'] = $variant->name;
                }

                if (! empty($selectedPieceSnapshot)) {
                    $snapshot['selected_piece'] = $selectedPieceSnapshot;
                }

                $oi->attributes_snapshot = ! empty($snapshot) ? $snapshot : null;

                $oi->quantity = $line['quantity'];
                $oi->item_weight = $line['item_weight'];
                $oi->unit_price = $line['unit_price'];
                $oi->subtotal = $line['subtotal'];

                $oi->discount_amount = $line['discount_amount'];
                $oi->tax_amount = $line['tax_amount'];
                $oi->total = $line['total'];

                $oi->cgst_amount = $line['cgst_amount'];
                $oi->sgst_amount = $line['sgst_amount'];
                $oi->igst_amount = $line['igst_amount'];

                $oi->save();
                $orderItems[] = $oi;
            }

            if ($coupon && $discountTotal > 0) {
                CouponRedemption::create([
                    'coupon_id'       => $coupon->id,
                    'user_id'         => $user->id,
                    'order_id'        => $order->id,
                    'discount_amount' => round($discountTotal, 2),
                    'redeemed_at'     => now(),
                ]);

                DB::table('coupons')->where('id', $coupon->id)->increment('usage_count');
            }

            $invoice = new Invoice();
            $invoice->order_id = $order->id;
            $invoice->invoice_number = 'BA-' . now()->format('dmy') . '-' . Str::upper(Str::random(6));
            $invoice->status = $paymentMethod === 'pay_later' ? 'due' : 'pending';
            $invoice->invoice_date = now()->toDateString();
            $invoice->due_date = $paymentMethod === 'pay_later'
                ? now()->addDays((int) ($payLaterOption['terms_days'] ?? 7))->toDateString()
                : now()->addDays(7)->toDateString();

            $invoice->subtotal = round($order->subtotal, 2);
            $invoice->tax_total = round($order->tax_total, 2);
            $invoice->discount_total = round($order->discount_total, 2);
            if (Schema::hasColumn('invoices', 'delivery_zone_id')) {
                $invoice->delivery_zone_id = $order->delivery_zone_id ?? null;
            }
            if (Schema::hasColumn('invoices', 'delivery_pincode')) {
                $invoice->delivery_pincode = $order->delivery_pincode ?? $address->pincode;
            }
            foreach ([
                'delivery_fee',
                'handling_fee',
                'delivery_tax_amount',
                'handling_tax_amount',
                'delivery_tax_rate',
                'handling_tax_rate',
            ] as $deliveryColumn) {
                if (Schema::hasColumn('invoices', $deliveryColumn)) {
                    $invoice->{$deliveryColumn} = $order->{$deliveryColumn} ?? 0;
                }
            }
            $invoice->grand_total = round($order->grand_total, 2);
            if (Schema::hasColumn('invoices', 'bandara_credit_redeemed_points')) {
                $invoice->bandara_credit_redeemed_points = $bandaraCreditPointsToRedeem;
            }
            if (Schema::hasColumn('invoices', 'bandara_credit_redeemed_amount')) {
                $invoice->bandara_credit_redeemed_amount = round($bandaraCreditRedeemAmount, 2);
            }
            if (Schema::hasColumn('invoices', 'bandara_credit_points_redeemed')) {
                $invoice->bandara_credit_points_redeemed = $bandaraCreditPointsToRedeem;
            }
            if (Schema::hasColumn('invoices', 'bandara_credit_discount_total')) {
                $invoice->bandara_credit_discount_total = round($bandaraCreditRedeemAmount, 2);
            }
            $invoice->save();

            foreach ($orderItems as $oi) {
                InvoiceItem::create([
                    'invoice_id'    => $invoice->id,
                    'order_item_id' => $oi->id,
                    'description'   => $oi->product_name,
                    'quantity'      => $oi->quantity,
                    'item_weight'   => $oi->item_weight,
                    'unit_price'    => $oi->unit_price,
                    'sell_unit'     => $oi->sell_unit,
                    'pricing_unit'  => $oi->pricing_unit,
                    'subtotal'      => $oi->subtotal,
                    'tax_amount'    => $oi->tax_amount,
                    'total'         => $oi->total,
                ]);
            }

            if ($paymentMethod === 'pay_later') {
                // Pay Later is an accepted B2B credit order, not a failed/pending
                // Razorpay payment. Commit stock immediately inside this transaction
                // so an inventory failure rolls the order back instead of creating a
                // due invoice without stock being reserved/removed.
                app(OrderInventoryService::class)->commitPaidOrder($order);
            }

            // For Pay Now/Razorpay orders, do not deduct inventory here. Inventory is
            // committed exactly once after successful payment verification.

            DB::table('cart_items')->where('cart_id', $cart->id)->delete();
            DB::table('carts')->where('id', $cart->id)->delete();
            session()->forget('cart_id');
            session()->forget('cart_piece_meta');
        });

        if (! $order) {
            return back()->withErrors(['checkout' => 'Something went wrong. Please try again.']);
        }

        if ($paymentMethod === 'pay_later') {
            try {
                $invoice = $order->invoice()->first();
                if ($invoice) {
                    app(InvoicePdfService::class)->generateAndStore($invoice);
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to generate Pay Later invoice PDF', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $dueLabel = $order->payment_due_at
                ? $order->payment_due_at->format('d M Y')
                : 'the invoice due date';

            return redirect()
                ->route('orders.show', $order)
                ->with('status', 'Order placed on Pay Later terms. Invoice is due on ' . $dueLabel . '.');
        }

        if (Route::has('orders.pay.razorpay')) {
            return redirect()->route('orders.pay.razorpay', $order);
        }

        if (Route::has('orders.show')) {
            return redirect()->route('orders.show', $order);
        }

        return redirect()->route('home')->with('status', 'Order placed.');
    }


    public function applyBandaraCredit(Request $request, CartService $cartService)
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if (($user->customer_type ?? 'b2c') === 'b2b') {
            return redirect()->route('checkout.index')
                ->withErrors(['bandara_credit_points' => 'Bandara Credit redemption is not available for B2B checkout.']);
        }

        $data = $request->validate([
            'bandara_credit_points' => ['nullable', 'integer', 'min:0'],
            'address_id' => ['nullable', 'integer'],
        ]);

        $params = [];
        if (! empty($data['address_id'])) {
            $params['address_id'] = (int) $data['address_id'];
        }
        if ((int) ($data['bandara_credit_points'] ?? 0) > 0) {
            $params['bandara_credit_points'] = (int) $data['bandara_credit_points'];
        }

        return redirect()->route('checkout.index', $params);
    }

    public function removeBandaraCredit(Request $request)
    {
        $params = [];
        if ($request->filled('address_id')) {
            $params['address_id'] = (int) $request->input('address_id');
        }

        return redirect()->route('checkout.index', $params)
            ->with('status', 'Bandara Credit redemption removed.');
    }

    protected function bandaraCreditQuoteForCheckout($user, float $orderAmount, int $requestedPoints = 0, array $context = []): array
    {
        try {
            $service = app(BandaraCreditService::class);

            if (method_exists($service, 'redemptionQuoteForCheckout')) {
                return (array) $service->redemptionQuoteForCheckout($user, $orderAmount, $requestedPoints, $context);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to prepare Bandara Credit checkout quote', [
                'user_id' => $user?->id,
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'enabled' => false,
            'eligible_user' => false,
            'can_redeem' => false,
            'available_points' => 0,
            'reserved_points' => 0,
            'minimum_points' => 0,
            'max_redeemable_points' => 0,
            'requested_points' => max(0, $requestedPoints),
            'points_to_redeem' => 0,
            'redeem_amount' => 0,
            'order_amount_before_credit' => round(max(0, $orderAmount), 2),
            'order_amount_after_credit' => round(max(0, $orderAmount), 2),
            'message' => 'Bandara Credit is not available right now.',
        ];
    }


    protected function validateCartStockBeforeOrder($items): ?string
    {
        foreach ($items as $it) {
            $product = $it->product ?: Product::query()->with('hsnCode')->find($it->product_id);
            if (! $product) {
                return 'One of the products in your cart is no longer available.';
            }

            $selectedPieceSnapshot = $this->selectedPieceSnapshotForCartItem($it);
            $variant = $it->productVariant;

            if (! $variant && ! empty($selectedPieceSnapshot['variant_id'])) {
                $variant = ProductVariant::query()
                    ->where('id', (int) $selectedPieceSnapshot['variant_id'])
                    ->where('product_id', $it->product_id)
                    ->first();
            }

            $sellUnit = strtolower((string) ($product->sell_unit ?? 'piece'));
            $isKg = ($sellUnit === 'kg');

            $qty = (float) ($it->quantity ?? 0);

            if ($variant && (bool) ($variant->manage_stock ?? false)) {
                $available = (float) ($variant->stock_quantity ?? 0);

                if (($isKg && $qty > round($available, 2)) || (! $isKg && $qty > floor($available))) {
                    return 'Stock not available for ' . $product->name . '.';
                }

                continue;
            }

            if ((bool) ($product->manage_stock ?? false)) {
                $available = (float) ($product->stock_quantity ?? 0);

                if (($isKg && $qty > round($available, 2)) || (! $isKg && $qty > floor($available))) {
                    return 'Stock not available for ' . $product->name . '.';
                }
            }
        }

        return null;
    }

    /**
     * Stock is intentionally not deducted during order placement.
     *
     * The checkout creates an order with payment_status=pending. The actual
     * inventory commit happens after payment success in OrderInventoryService,
     * guarded by stock_movements(reference_type=order_item) so repeated payment
     * callbacks remain idempotent.
     */

    protected function selectedPieceSnapshotForCartItem(CartItem $item): array
    {
        $sessionMeta = session('cart_piece_meta', []);
        $meta = is_array($sessionMeta[$item->id] ?? null) ? $sessionMeta[$item->id] : [];

        $pieceId = 0;
        if (Schema::hasColumn('cart_items', 'selected_piece_id')) {
            $pieceId = (int) ($item->selected_piece_id ?? 0);
        }
        if ($pieceId <= 0) {
            $pieceId = (int) ($meta['piece_id'] ?? 0);
        }

        $lotId = 0;
        if (Schema::hasColumn('cart_items', 'selected_lot_id')) {
            $lotId = (int) ($item->selected_lot_id ?? 0);
        }
        if ($lotId <= 0) {
            $lotId = (int) ($meta['lot_id'] ?? 0);
        }

        if ($pieceId > 0) {
            $piece = InventoryPiece::query()
                ->with('inventoryLot')
                ->find($pieceId);

            if ($piece) {
                $lot = $piece->inventoryLot;

                return [
                    'piece_id' => (int) $piece->id,
                    'lot_id' => (int) ($lot?->id ?? $piece->inventory_lot_id),
                    'variant_id' => (int) ($lot?->product_variant_id ?? ($meta['variant_id'] ?? 0)),
                    'weight_kg' => round((float) $piece->weight_kg, 3),
                    'lot_code' => $lot?->lot_code ?? ($meta['lot_code'] ?? null),
                ];
            }
        }

        if (! empty($meta)) {
            return [
                'piece_id' => (int) ($meta['piece_id'] ?? 0),
                'lot_id' => $lotId,
                'variant_id' => (int) ($meta['variant_id'] ?? 0),
                'weight_kg' => round((float) ($meta['weight_kg'] ?? $item->item_weight ?? 0), 3),
                'lot_code' => $meta['lot_code'] ?? null,
            ];
        }

        return [];
    }

    protected function addressReturnToSessionKey(): string
    {
        return 'customer.addresses.return_to';
    }

    protected function checkoutIndexUrl(Request $request): string
    {
        if ($request->routeIs('b2b.checkout.index') || $request->routeIs('checkout.index')) {
            return $request->getRequestUri();
        }

        $returnTo = $this->sanitizeReturnUrl($request, $request->input('return_to'));
        if ($returnTo) {
            return $returnTo;
        }

        if (Route::has('checkout.index')) {
            return route('checkout.index', [], false);
        }

        return '/checkout';
    }

    protected function emptyCartRedirect(Request $request)
    {
        return redirect()->route('cart.index')->with('status', 'Your cart is empty.');
    }

    protected function b2bCartBlockedRedirect()
    {
        return redirect()->route('cart.index');
    }

    protected function isB2BRequest(Request $request): bool
    {
        return (($request->user()?->customer_type ?? 'b2c') === 'b2b');
    }

    protected function addressCreateUrl(Request $request, ?string $returnTo = null): string
    {
        $returnTo = $returnTo ?: $this->checkoutIndexUrl($request);

        if (Route::has('account.addresses.create')) {
            return route('account.addresses.create', ['return_to' => $returnTo], false);
        }

        if (Route::has('customer.addresses.create')) {
            return route('customer.addresses.create', ['return_to' => $returnTo], false);
        }

        return $returnTo;
    }

    protected function sanitizeReturnUrl(Request $request, ?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $allowedHosts = array_filter([
            parse_url($request->getSchemeAndHttpHost(), PHP_URL_HOST),
            parse_url(config('app.url'), PHP_URL_HOST),
            '127.0.0.1',
            'localhost',
        ]);

        if (! in_array($parts['host'], $allowedHosts, true)) {
            return null;
        }

        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return $path . $query . $fragment;
    }

    protected function appendQueryParameter(string $url, string $key, string $value): string
    {
        $parts = parse_url($url);

        $path = $parts['path'] ?? '/';
        $query = [];

        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        $query[$key] = $value;

        $queryString = http_build_query($query);
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return $path . ($queryString !== '' ? '?' . $queryString : '') . $fragment;
    }

    private function addNullableAmounts(mixed $left, mixed $right): ?float
    {
        $hasLeft = $left !== null;
        $hasRight = $right !== null;

        if (! $hasLeft && ! $hasRight) {
            return null;
        }

        return round((float) ($left ?? 0) + (float) ($right ?? 0), 2);
    }

    protected function calculateLineTotalForCartItem(Product $product, ?ProductVariant $variant, float $qty, float $unitPrice, ?float $lineWeight = null): float
    {
        $pricingUnit = strtolower((string) ($variant?->pricing_unit ?? ($product->sell_unit === 'kg' ? 'kg' : 'pack')));
        $pricingUnit = in_array($pricingUnit, ['kg', 'pack'], true) ? $pricingUnit : 'pack';

        if ($pricingUnit === 'kg') {
            $weight = (float) ($lineWeight ?? 0);
            if ($weight <= 0) {
                $unitWeight = (float) ($variant?->product_weight ?? $product->product_weight ?? 0);
                $weight = round($qty * $unitWeight, 3);
            }

            return round(max($weight, 0) * $unitPrice, 2);
        }

        return round(max($qty, 0) * $unitPrice, 2);
    }

    /**
     * Snapshot weighted cart rows before sync and restore their selected weight after sync.
     * This prevents slab/weight-based selections from being zeroed out on checkout load.
     */
    protected function syncCartPricesPreservingWeights(CartService $cartService, $cart): int
    {
        $beforeItems = CartItem::with(['product.hsnCode', 'productVariant'])
            ->where('cart_id', $cart->id)
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        $updatedCount = $cartService->syncPrices($cart);

        if ($beforeItems->isNotEmpty()) {
            $afterItems = CartItem::with(['product.hsnCode', 'productVariant'])
                ->whereIn('id', $beforeItems->keys()->all())
                ->get();

            foreach ($afterItems as $after) {
                $before = $beforeItems->get($after->id);

                if (! $before) {
                    continue;
                }

                $beforeWeight = round((float) ($before->item_weight ?? 0), 3);
                $beforeTotal = round((float) ($before->total ?? 0), 2);

                if ($beforeWeight <= 0) {
                    continue;
                }

                $afterWeight = round((float) ($after->item_weight ?? 0), 3);
                $afterTotal = round((float) ($after->total ?? 0), 2);

                $weightChanged = abs($afterWeight - $beforeWeight) > 0.0009;
                if ($weightChanged) {
                    $product = $after->product;
                    $variant = $after->productVariant;
                    $unitPrice = (float) ($after->unit_price ?? 0);
                    $quantity = (float) ($after->quantity ?? 0);

                    $recalculatedTotal = $product
                        ? $this->calculateLineTotalForCartItem($product, $variant, $quantity, $unitPrice, $beforeWeight)
                        : $beforeTotal;

                    DB::table('cart_items')
                        ->where('id', $after->id)
                        ->update([
                            'item_weight' => $beforeWeight,
                            'total' => $recalculatedTotal,
                        ]);
                }
            }
        }

        return $updatedCount;
    }

    /**
     * ✅ GST per cart line using products.gst_rate (percentage).
     * - Splits intra-state into CGST/SGST, else IGST
     * - Allocates coupon discount proportionally per line before computing GST
     * - Returns:
     *   gst_type, cgst_amount, sgst_amount, igst_amount, tax_total, line_tax_map
     */
    private function productGstRate(?Product $product): float
    {
        return app(\App\Services\GstRateService::class)->rateForProduct($product, request()->user());
    }

    private function calculateGstFromItems($items, float $discountTotal, ?string $state, ?\App\Models\User $user = null): array
    {
        $state = trim((string) $state);
        $isMaharashtra = $state !== '' && strcasecmp($state, 'Maharashtra') === 0;

        // Base subtotals from cart lines (already weight-aware)
        $subtotals = [];
        $sumSubtotal = 0.0;

        foreach ($items as $it) {
            $sub = (float) ($it->total ?? 0);
            $sub = round($sub, 2);
            $subtotals[$it->id] = $sub;
            $sumSubtotal += $sub;
        }

        $sumSubtotal = round($sumSubtotal, 2);
        $discountTotal = round(max($discountTotal, 0), 2);

        // Allocate discount across lines
        $allocatedDiscount = 0.0;
        $ids = array_keys($subtotals);
        $lastId = end($ids);

        $lineDiscounts = [];
        foreach ($subtotals as $id => $sub) {
            if ($sumSubtotal <= 0) {
                $lineDiscounts[$id] = 0.0;
                continue;
            }

            if ($id === $lastId) {
                $lineDiscounts[$id] = round($discountTotal - $allocatedDiscount, 2);
            } else {
                $d = round(($sub / $sumSubtotal) * $discountTotal, 2);
                $lineDiscounts[$id] = $d;
                $allocatedDiscount += $d;
            }
        }

        // Compute per-line tax using product gst_rate
        $lineTaxMap = [];
        $taxTotal = 0.0;

        foreach ($items as $it) {
            $id = $it->id;

            $product = $it->product ?: Product::query()->with('hsnCode')->find($it->product_id);

            // GST percent resolved from HSN/product/default fallback.
            $ratePercent = app(\App\Services\GstRateService::class)->rateForCartItem($it, $user);

            $sub = (float) ($subtotals[$id] ?? 0);
            $disc = (float) ($lineDiscounts[$id] ?? 0);

            $taxableLine = round(max($sub - $disc, 0), 2);
            $taxLine = round($taxableLine * ($ratePercent / 100), 2);

            $lineTaxMap[$id] = [
                'rate_percent' => $ratePercent,
                'taxable'      => $taxableLine,
                'tax'          => $taxLine,
            ];

            $taxTotal += $taxLine;
        }

        $taxTotal = round($taxTotal, 2);

        if ($isMaharashtra) {
            $cgst = round($taxTotal / 2, 2);
            $sgst = round($taxTotal - $cgst, 2);

            return [
                'gst_type'      => 'intra_state',
                'cgst_amount'   => $cgst,
                'sgst_amount'   => $sgst,
                'igst_amount'   => null,
                'tax_total'     => $taxTotal,
                'line_tax_map'  => $lineTaxMap,
                'line_discounts'=> $lineDiscounts,
                'line_subtotals'=> $subtotals,
            ];
        }

        return [
            'gst_type'      => 'inter_state',
            'cgst_amount'   => null,
            'sgst_amount'   => null,
            'igst_amount'   => $taxTotal,
            'tax_total'     => $taxTotal,
            'line_tax_map'  => $lineTaxMap,
            'line_discounts'=> $lineDiscounts,
            'line_subtotals'=> $subtotals,
        ];
    }

    /**
     * Compute cart line total using product sell_unit + product_weight.
     * Kept for MOQ logic; checkout sync preservation prevents slab rows from losing item_weight.
     */
    private function cartLineTotal(CartItem $it): float
    {
        $product = $it->product;
        $qty = (float) $it->quantity;
        $unit = (float) $it->unit_price;

        $sellUnit = (string) ($product?->sell_unit ?? 'piece');

        if ($sellUnit === 'kg') {
            $explicitWeight = round((float) ($it->item_weight ?? 0), 3);
            if ($explicitWeight > 0) {
                return round($explicitWeight * $unit, 2);
            }

            $pw = (float) ($product?->product_weight ?? 0);
            $w = round($qty * $pw, 3);
            return round($w * $unit, 2);
        }

        return round($qty * $unit, 2);
    }

    /**
     * ✅ Allocate discount + tax across cart lines so item totals match order totals.
     * Uses per-item gst_rate. Keeps existing output keys unchanged.
     */
    private function allocateLineDiscountAndTaxUsingRates($items, float $discountTotal, array $gst): array
    {
        $rows = [];

        // Pull precomputed maps from calculateGstFromItems()
        $subtotals = $gst['line_subtotals'] ?? [];
        $lineDiscounts = $gst['line_discounts'] ?? [];
        $lineTaxMap = $gst['line_tax_map'] ?? [];

        // Fallback safety
        if (empty($subtotals)) {
            $subtotals = [];
            foreach ($items as $it) {
                $subtotals[$it->id] = round((float) ($it->total ?? 0), 2);
            }
        }
        if (empty($lineDiscounts)) {
            $lineDiscounts = array_fill_keys(array_keys($subtotals), 0.0);
        }
        if (empty($lineTaxMap)) {
            $lineTaxMap = [];
            foreach ($items as $it) {
                $lineTaxMap[$it->id] = ['rate_percent' => 0.0, 'taxable' => round((float) ($subtotals[$it->id] ?? 0), 2), 'tax' => 0.0];
            }
        }

        // Ensure rounding matches order tax_total by adjusting the last line
        $taxTotal = round((float) ($gst['tax_total'] ?? 0), 2);

        $ids = array_keys($subtotals);
        $lastId = end($ids);

        $allocatedTax = 0.0;

        foreach ($items as $it) {
            $id = $it->id;

            $product = $it->product;
            $qty = (float) $it->quantity;
            $unit = (float) $it->unit_price;

            $sellUnit = (string) ($product?->sell_unit ?? 'piece');
            $pw = (float) ($product?->product_weight ?? 0);

            $explicitWeight = round((float) ($it->item_weight ?? 0), 3);
            $itemWeight = $explicitWeight > 0 ? $explicitWeight : round($qty * $pw, 3);

            $sub = round((float) ($subtotals[$id] ?? 0), 2);
            $disc = round((float) ($lineDiscounts[$id] ?? 0), 2);

            $taxableLine = round(max($sub - $disc, 0), 2);
            $taxLine = round((float) ($lineTaxMap[$id]['tax'] ?? 0), 2);

            if ($id === $lastId) {
                $taxLine = round($taxTotal - $allocatedTax, 2);
            } else {
                $allocatedTax += $taxLine;
            }

            $cgst = $sgst = $igst = null;

            if (($gst['gst_type'] ?? '') === 'intra_state') {
                $cgst = round($taxLine / 2, 2);
                $sgst = round($taxLine - $cgst, 2);
            } else {
                $igst = $taxLine;
            }

            $totalLine = round($taxableLine + $taxLine, 2);

            $rows[$id] = [
                'quantity'        => (float) ((int) $qty),
                'item_weight'     => $itemWeight,
                'unit_price'      => round($unit, 2),
                'subtotal'        => round($sub, 2),
                'discount_amount' => round($disc, 2),
                'tax_amount'      => round($taxLine, 2),
                'total'           => $totalLine,
                'cgst_amount'     => $cgst,
                'sgst_amount'     => $sgst,
                'igst_amount'     => $igst,
            ];
        }

        return $rows;
    }
}
