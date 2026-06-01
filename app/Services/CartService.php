<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CartService
{
    public function currentCart(bool $create = true): ?Cart
    {
        $sessionId     = session()->getId();
        $sessionCartId = session('cart_id');

        if ($sessionCartId) {
            $cart = Cart::query()->where('id', $sessionCartId)->first();
            if ($cart) {
                if (Auth::check()) {
                    return $cart;
                }

                if (is_null($cart->user_id)) {
                    if ((string) $cart->session_id !== (string) $sessionId) {
                        $cart->session_id = $sessionId;
                        $cart->save();
                    }
                    return $cart;
                }
            }
        }

        if (Auth::check()) {
            $userId = Auth::id();

            $userCart = Cart::query()
                ->where('user_id', $userId)
                ->orderByDesc('id')
                ->first();

            $guestCart = Cart::query()
                ->whereNull('user_id')
                ->where('session_id', $sessionId)
                ->orderByDesc('id')
                ->first();

            if (! $userCart && $guestCart) {
                $guestCart->user_id = $userId;
                $guestCart->session_id = null;
                $guestCart->save();

                session(['cart_id' => $guestCart->id]);
                return $guestCart;
            }

            if (! $userCart && $create) {
                $userCart = new Cart();
                $userCart->user_id = $userId;
                $userCart->session_id = null;
                $userCart->coupon_id = null;
                $userCart->save();
            }

            if ($userCart) {
                session(['cart_id' => $userCart->id]);
            }

            return $userCart;
        }

        $cart = Cart::query()
            ->whereNull('user_id')
            ->where('session_id', $sessionId)
            ->orderByDesc('id')
            ->first();

        if (! $cart && $create) {
            $cart = new Cart();
            $cart->user_id = null;
            $cart->session_id = $sessionId;
            $cart->coupon_id = null;
            $cart->save();
        }

        if ($cart) {
            session(['cart_id' => $cart->id]);
        }

        return $cart;
    }


    /**
     * Add item / increase quantity.
     * For variants:
     * - weight uses variant.product_weight first
     * - line total uses pricing_unit (kg vs pack)
     * - stock is validated before increasing quantity
     */
    public function addToCart(int $productId, ?int $variantId, float $qty = 1.0, float $itemWeight = 0.0): void
    {
        DB::transaction(function () use ($productId, $variantId, $qty) {
            $cart = $this->currentCart(true);
            if (! $cart) {
                throw new \RuntimeException('Cart not found.');
            }

            session(['cart_id' => $cart->id]);

            $product = Product::query()->findOrFail($productId);

            $user  = Auth::user();
            $terms = app(\App\Services\B2BTermsService::class);
            $isB2B = $user && (($user->customer_type ?? 'b2c') === 'b2b');

            $type = (string) ($product->type ?? 'simple');
            $isVariableProduct = $type !== 'simple';

            if ($isVariableProduct && ! $variantId) {
                throw new \RuntimeException('Please select a variant for this product.');
            }

            $variant = null;
            if ($variantId) {
                $variant = ProductVariant::query()
                    ->where('id', $variantId)
                    ->where('product_id', $product->id)
                    ->firstOrFail();
            }

            if ($isB2B && ! $terms->canBuy($user, $product, $variant?->sellUnit, $variant)) {
                throw new \RuntimeException('This product is not available for your account.');
            }

            $sellUnit = strtolower((string) ($product->sell_unit ?? 'piece'));
            $isKg = ($sellUnit === 'kg');

            $unitPrice = $this->resolveUnitPrice($product, $variant);

            $itemQuery = CartItem::query()
                ->where('cart_id', $cart->id)
                ->where('product_id', $product->id);

            if ($variantId) {
                $itemQuery->where('product_variant_id', $variantId);
            } else {
                $itemQuery->whereNull('product_variant_id');
            }

            $item = $itemQuery->lockForUpdate()->first();

            $qty = (float) $qty;
            if ($qty <= 0) $qty = 1.0;
            $qty = $isKg ? round(max($qty, 0.01), 2) : (float) max((int) round($qty), 1);

            $newQty = $qty;
            if ($item) {
                $newQty = (float) $item->quantity + $qty;
            }

            if ($isB2B) {
                $min = (float) $terms->minOrderQty($user, $product, $variant?->sellUnit, $variant);
                if ($min <= 0) $min = $isKg ? 0.01 : 1;

                $min = $isKg ? round(max($min, 0.01), 2) : (float) max((int) ceil($min), 1);

                if ($newQty < $min) {
                    $newQty = $min;
                }
            }

            $newQty = $isKg ? round($newQty, 2) : (float) max((int) round($newQty), 1);

            [$availableQty, $hasStockLimit] = $this->availableStockFor($product, $variant, $isKg);

            if ($hasStockLimit && $availableQty !== null && $newQty > ($availableQty + 1e-9)) {
                $label = $isKg
                    ? rtrim(rtrim(number_format($availableQty, 2, '.', ''), '0'), '.')
                    : (string) (int) $availableQty;

                throw new \RuntimeException("Only {$label} available in stock for this item.");
            }

            $lineWeight = $this->calculateLineWeight($product, $variant, $newQty);
            $lineTotal = $this->calculateLineTotal($product, $variant, $newQty, $unitPrice, $lineWeight);

            if (! $item) {
                $item = new CartItem();
                $item->cart_id = $cart->id;
                $item->product_id = $product->id;
                $item->product_variant_id = $variantId;
            }

            $item->quantity = $newQty;
            $item->unit_price = $unitPrice;
            $item->item_weight = $lineWeight;
            $item->total = $lineTotal;
            $item->save();

            $cart->touch();
        });
    }

    public function badgeCount(): int
    {
        $cart = $this->currentCart(false);
        if (! $cart) return 0;

        return (int) CartItem::query()
            ->where('cart_id', $cart->id)
            ->count();
    }

    /**
     * Sync prices and keep line weight consistent with variant/product setup.
     */
    public function syncPrices(Cart $cart): int
    {
        $items = CartItem::query()
            ->where('cart_id', $cart->id)
            ->get();

        if ($items->isEmpty()) return 0;

        $updated = 0;

        foreach ($items as $it) {
            $product = Product::query()->find($it->product_id);
            if (! $product) continue;

            $variant = null;
            if ($it->product_variant_id) {
                $variant = ProductVariant::query()->find($it->product_variant_id);
            }

            $newUnit = $this->resolveUnitPrice($product, $variant);
            $newWeight = $this->calculateLineWeight($product, $variant, (float) $it->quantity);
            $expectedTotal = $this->calculateLineTotal($product, $variant, (float) $it->quantity, $newUnit, $newWeight);

            $oldUnit = (float) ($it->unit_price ?? 0);
            $oldWeight = $it->item_weight !== null ? round((float) $it->item_weight, 3) : null;
            $targetWeight = $newWeight !== null ? round((float) $newWeight, 3) : null;
            $oldTotal = round((float) ($it->total ?? 0), 2);

            $changed = false;

            if (abs($newUnit - $oldUnit) > 0.0001) {
                $it->unit_price = $newUnit;
                $changed = true;
            }

            if ($oldWeight !== $targetWeight) {
                $it->item_weight = $newWeight;
                $changed = true;
            }

            if (abs($expectedTotal - $oldTotal) > 0.0001) {
                $it->total = $expectedTotal;
                $changed = true;
            }

            if ($changed) {
                $it->save();
                $updated++;
            }
        }

        if ($updated > 0) $cart->touch();

        return $updated;
    }

    public function subtotal(Cart $cart): float
    {
        return (float) CartItem::query()
            ->where('cart_id', $cart->id)
            ->sum('total');
    }

    public function applyCouponCode(Cart $cart, string $code, float $subtotal, ?User $user = null): array
    {
        $code = trim($code);
        if ($code === '') {
            return ['ok' => false, 'message' => 'Please enter a coupon code.', 'coupon' => null, 'discount' => 0];
        }

        $coupon = $this->findCouponByCode($code);

        if (! $coupon) {
            return ['ok' => false, 'message' => 'Invalid coupon code.', 'coupon' => null, 'discount' => 0];
        }

        [$ok, $msg] = $this->validateCoupon($coupon, $subtotal, $user);

        if (! $ok) {
            return ['ok' => false, 'message' => $msg, 'coupon' => null, 'discount' => 0];
        }

        $cart->coupon_id = $coupon->id;
        $cart->save();

        $discount = $this->calculateCouponDiscount($coupon, $subtotal);

        return ['ok' => true, 'message' => null, 'coupon' => $coupon, 'discount' => $discount];
    }

    public function removeCoupon(Cart $cart): void
    {
        if ($cart->coupon_id) {
            $cart->coupon_id = null;
            $cart->save();
        }
    }

    public function ensureCouponStillValid(Cart $cart, float $subtotal, ?User $user = null): ?string
    {
        if (! $cart->coupon_id) return null;

        $coupon = Coupon::query()->find($cart->coupon_id);

        if (! $coupon) {
            $this->removeCoupon($cart);
            return 'Applied coupon was removed (no longer available).';
        }

        [$ok, $msg] = $this->validateCoupon($coupon, $subtotal, $user);

        if (! $ok) {
            $this->removeCoupon($cart);
            return 'Applied coupon removed: ' . $msg;
        }

        return null;
    }

    public function calculateCouponDiscount(?Coupon $coupon, float $subtotal): float
    {
        if (! $coupon || $subtotal <= 0) return 0.0;

        if (! is_null($coupon->min_order_amount) && (float) $subtotal < (float) $coupon->min_order_amount) {
            return 0.0;
        }

        $discount = 0.0;

        if ($coupon->discount_type === 'flat') {
            $discount = (float) $coupon->discount_value;
        } else {
            $discount = ((float) $subtotal) * ((float) $coupon->discount_value) / 100.0;
        }

        if (! is_null($coupon->max_discount_amount)) {
            $discount = min($discount, (float) $coupon->max_discount_amount);
        }

        $discount = min($discount, (float) $subtotal);

        return round($discount, 2);
    }

    private function findCouponByCode(string $code): ?Coupon
    {
        $normalized = Str::lower(trim($code));

        return Coupon::query()
            ->whereRaw('LOWER(code) = ?', [$normalized])
            ->first();
    }

    private function validateCoupon(Coupon $coupon, float $subtotal, ?User $user = null): array
    {
        if (! $coupon->is_active) return [false, 'Coupon is not active.'];

        $now = now();

        $starts = $this->asCarbon($coupon->starts_at);
        if ($starts && $now->lt($starts)) return [false, 'Coupon is not active yet.'];

        $expires = $this->asCarbon($coupon->expires_at);
        if ($expires && $now->gt($expires)) return [false, 'Coupon has expired.'];

        if (! is_null($coupon->min_order_amount) && (float) $subtotal < (float) $coupon->min_order_amount) {
            return [false, 'Order total is too low for this coupon.'];
        }

        if (! is_null($coupon->usage_limit) && (int) $coupon->usage_count >= (int) $coupon->usage_limit) {
            return [false, 'Coupon usage limit reached.'];
        }

        $userId = $user?->id;

        if ($userId) {
            $usedByUser = (int) DB::table('coupon_redemptions')
                ->where('coupon_id', $coupon->id)
                ->where('user_id', $userId)
                ->count();

            if ($coupon->is_one_time && $usedByUser > 0) {
                return [false, 'This coupon can only be used once per customer.'];
            }

            if (! is_null($coupon->per_user_limit) && $usedByUser >= (int) $coupon->per_user_limit) {
                return [false, 'You have reached the per-user usage limit for this coupon.'];
            }
        }

        return [true, null];
    }

    private function asCarbon($value): ?Carbon
    {
        if (! $value) return null;
        return $value instanceof Carbon ? $value : Carbon::parse($value);
    }

    private function resolveUnitPrice(Product $product, ?ProductVariant $variant): float
    {
        $user = Auth::user();

        if (class_exists(\App\Services\PricingService::class)) {
            return (float) app(\App\Services\PricingService::class)
                ->cartUnitPriceFor($user, $product, $variant, $variant?->sellUnit);
        }

        if ($variant && $variant->price !== null && (float) $variant->price > 0) {
            $resolved = (float) $variant->price;
            return $this->normalizeVariantUnitPriceForCart($product, $variant, $resolved);
        }

        return round((float) ($product->base_price ?? 0), 2);
    }

    /**
     * Normalize variant cart price so it behaves like frontend product display.
     * Only adjust when:
     * - variant exists
     * - variant has its own raw price
     * - product B2C price mode is GST-inclusive
     * - resolved price is effectively the raw variant price
     *
     * This avoids disturbing simple products or custom non-variant pricing flows.
     */
    private function normalizeVariantUnitPriceForCart(Product $product, ?ProductVariant $variant, float $resolvedPrice): float
    {
        if (! $variant) {
            return round($resolvedPrice, 2);
        }

        $rawVariantPrice = $variant->price ?? null;
        if ($rawVariantPrice === null || $rawVariantPrice === '') {
            return round($resolvedPrice, 2);
        }

        $gstRate = (float) ($product->effective_gst_rate ?? $product->gst_rate ?? 0);
        if (!($product->b2c_price_includes_gst ?? true) || $gstRate <= 0) {
            return round($resolvedPrice, 2);
        }

        $rawVariantPrice = round((float) $rawVariantPrice, 2);
        $resolvedRounded = round((float) $resolvedPrice, 2);

        // Only normalize if the resolved price is effectively the raw stored variant price.
        // This protects any external/custom pricing logic that may already return an ex-GST value.
        if (abs($resolvedRounded - $rawVariantPrice) < 0.011) {
            $resolvedRounded = $resolvedRounded / (1 + ($gstRate / 100));
        }

        return round($resolvedRounded, 2);
    }

    private function resolvePricingUnit(Product $product, ?ProductVariant $variant): string
    {
        $unit = strtolower((string) ($variant?->pricing_unit ?? ($product->sell_unit === 'kg' ? 'kg' : 'pack')));

        return in_array($unit, ['kg', 'pack'], true) ? $unit : 'pack';
    }

    private function resolveUnitWeightKg(Product $product, ?ProductVariant $variant): float
    {
        $variantWeight = round((float) ($variant?->product_weight ?? 0), 3);
        if ($variantWeight > 0) {
            return $variantWeight;
        }

        return round((float) ($product->product_weight ?? 0), 3);
    }

    private function calculateLineWeight(Product $product, ?ProductVariant $variant, float $qty): ?float
    {
        $sellUnit = strtolower((string) ($product->sell_unit ?? 'piece'));

        if ($sellUnit === 'kg') {
            return round($qty, 3);
        }

        $unitWeightKg = $this->resolveUnitWeightKg($product, $variant);

        return $unitWeightKg > 0
            ? round($qty * $unitWeightKg, 3)
            : null;
    }

    private function calculateLineTotal(
        Product $product,
        ?ProductVariant $variant,
        float $qty,
        float $unitPrice,
        ?float $lineWeight = null
    ): float {
        $pricingUnit = $this->resolvePricingUnit($product, $variant);

        if ($pricingUnit === 'kg') {
            $weight = (float) ($lineWeight ?? $this->calculateLineWeight($product, $variant, $qty) ?? 0);
            return round($weight * $unitPrice, 2);
        }

        return round($qty * $unitPrice, 2);
    }


    private function availableStockFor(Product $product, ?ProductVariant $variant, bool $isKg): array
    {
        $available = null;
        $hasLimit = false;

        if ($variant && (bool) ($variant->manage_stock ?? false)) {
            $available = (float) ($variant->stock_quantity ?? 0);
            $hasLimit = true;
        } elseif ((bool) ($product->manage_stock ?? false)) {
            $available = (float) ($product->stock_quantity ?? 0);
            $hasLimit = true;
        } elseif ($variant && $variant->stock_quantity !== null && (float) $variant->stock_quantity > 0) {
            $available = (float) $variant->stock_quantity;
            $hasLimit = true;
        } elseif ($product->stock_quantity !== null && (float) $product->stock_quantity > 0) {
            $available = (float) $product->stock_quantity;
            $hasLimit = true;
        }

        if (! $hasLimit) {
            return [null, false];
        }

        if ($available < 0) {
            $available = 0;
        }

        $available = $isKg
            ? round($available, 2)
            : (float) max((int) floor($available), 0);

        return [$available, true];
    }

    public function mergeGuestCartIntoUser(User $user): void
    {
        // keep as-is if used elsewhere
    }
}