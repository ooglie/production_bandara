<?php

namespace App\Services;

use App\Models\CustomerProductPrice;
use App\Models\Product;
use App\Models\ProductSellUnit;
use App\Models\ProductVariant;
use App\Models\User;

class PricingService
{
    protected static array $displayCache = [];
    protected static array $accountingCache = [];

    public function quote(?User $user, Product $product, ?ProductVariant $variant = null, ?ProductSellUnit $sellUnit = null): array
    {
        $sellUnit = $sellUnit ?: $variant?->sellUnit;
        $isB2B = $this->isB2B($user);

        $price = $this->priceForContext($user, $product, $variant, $sellUnit);
        $base = $isB2B
            ? $this->b2bCompareAtPrice($product, $variant)
            : $this->b2cDisplayBasePrice($product, $variant);

        $source = $isB2B
            ? $this->b2bPriceSource($user, $product, $variant, $sellUnit)
            : $this->b2cPriceSource($product);

        $moq = $isB2B
            ? app(B2BTermsService::class)->minOrderQty($user, $product, $sellUnit, $variant)
            : 1.0;

        $displayIncludesGst = $isB2B
            ? $this->b2bPriceIncludesGst($product)
            : $this->b2cPriceIncludesGst($product);

        return [
            'customer_type' => $isB2B ? 'b2b' : 'b2c',
            'price' => round((float) $price, 2),
            'compare_at_price' => $base > $price ? round((float) $base, 2) : null,
            'source' => $source,
            'is_special' => in_array($source, ['b2c_special', 'b2b_special', 'all_special'], true),
            'moq' => $moq,
            'can_buy' => $isB2B ? $price > 0 : true,
            'message' => $isB2B && $price <= 0 ? 'B2B price is not configured for this item.' : null,
            // Always ex-GST for cart/order/checkout accounting.
            'accounting_unit_price' => round($this->cartUnitPriceFor($user, $product, $variant, $sellUnit), 2),
            // Kept for existing views; now means current displayed quote includes GST.
            'price_includes_gst_for_display' => $displayIncludesGst,
            'display_price_includes_gst' => $displayIncludesGst,
        ];
    }

    /** Display price for cards/detail pages. */
    public function priceFor(?User $user, Product $product, ?ProductVariant $variant = null): float
    {
        return $this->priceForContext($user, $product, $variant, $variant?->sellUnit);
    }

    /** Display price for cards/detail pages when a sellable unit is selected. */
    public function priceForSellUnit(?User $user, Product $product, ProductSellUnit $sellUnit): float
    {
        return $this->priceForContext($user, $product, null, $sellUnit);
    }

    /** Tax-exclusive unit price for cart/order/accounting calculations. */
    public function cartUnitPriceFor(?User $user, Product $product, ?ProductVariant $variant = null, ?ProductSellUnit $sellUnit = null): float
    {
        $sellUnit = $sellUnit ?: $variant?->sellUnit;
        $isB2B = $this->isB2B($user);
        $today = now()->toDateString();
        $userId = $user?->id ? (int) $user->id : 0;
        $cacheKey = implode('|', [$userId, (int) $product->id, (int) ($variant?->id ?? 0), (int) ($sellUnit?->id ?? 0), $today, 'accounting']);

        if (array_key_exists($cacheKey, self::$accountingCache)) {
            return (float) self::$accountingCache[$cacheKey];
        }

        if (! $isB2B) {
            return self::$accountingCache[$cacheKey] = round($this->b2cAccountingPrice($product, $variant), 2);
        }

        $source = $this->b2bPriceCandidate($user, $product, $variant, $sellUnit, $today);
        if ($source['price'] <= 0) {
            return self::$accountingCache[$cacheKey] = 0.0;
        }

        $price = (float) $source['price'];

        if (($source['tax_mode'] ?? 'exclusive') === 'inclusive') {
            $price = $this->removeGst($product, $price);
        }

        return self::$accountingCache[$cacheKey] = round($price, 2);
    }

    public function hasB2BPrice(?User $user, Product $product, ?ProductSellUnit $sellUnit = null, ?ProductVariant $variant = null): bool
    {
        if (! $this->isB2B($user)) {
            return true;
        }

        return $this->priceForContext($user, $product, $variant, $sellUnit ?: $variant?->sellUnit) > 0;
    }

    protected function priceForContext(?User $user, Product $product, ?ProductVariant $variant = null, ?ProductSellUnit $sellUnit = null): float
    {
        $today = now()->toDateString();
        $userId = $user?->id ? (int) $user->id : 0;
        $cacheKey = implode('|', [$userId, (int) $product->id, (int) ($variant?->id ?? 0), (int) ($sellUnit?->id ?? 0), $today, 'display']);

        if (array_key_exists($cacheKey, self::$displayCache)) {
            return (float) self::$displayCache[$cacheKey];
        }

        if ($this->isB2B($user)) {
            return self::$displayCache[$cacheKey] = round($this->b2bDisplayPrice($user, $product, $variant, $sellUnit, $today), 2);
        }

        return self::$displayCache[$cacheKey] = round($this->b2cDisplayPrice($product, $variant), 2);
    }

    protected function b2bDisplayPrice(User $user, Product $product, ?ProductVariant $variant, ?ProductSellUnit $sellUnit, string $today): float
    {
        $source = $this->b2bPriceCandidate($user, $product, $variant, $sellUnit, $today);
        if ($source['price'] <= 0) {
            return 0.0;
        }

        $price = (float) $source['price'];

        // If the admin/customer price source is GST-inclusive, show it as entered.
        // Otherwise convert from stored ex-GST to the product's B2B display mode.
        if (($source['tax_mode'] ?? 'exclusive') === 'inclusive') {
            return round($price, 2);
        }

        return $this->displayForB2B($product, $price);
    }

    protected function b2bPriceCandidate(User $user, Product $product, ?ProductVariant $variant, ?ProductSellUnit $sellUnit, string $today): array
    {
        $productId = (int) $product->id;
        $variantId = $variant?->id ? (int) $variant->id : null;
        $sellUnitId = $sellUnit?->id ? (int) $sellUnit->id : null;
        if ($sellUnitId) {
            $override = $this->getCustomerOverridePrice($user->id, $productId, null, $sellUnitId, $today);
            if ($override !== null) return ['price' => $override, 'source' => 'customer_b2b_sell_unit', 'tax_mode' => 'exclusive'];
        }

        if ($variantId) {
            $override = $this->getCustomerOverridePrice($user->id, $productId, $variantId, null, $today);
            if ($override !== null) return ['price' => $override, 'source' => 'customer_b2b_variant', 'tax_mode' => 'exclusive'];
        }

        $override = $this->getCustomerOverridePrice($user->id, $productId, null, null, $today);
        if ($override !== null) return ['price' => $override, 'source' => 'customer_b2b_product', 'tax_mode' => 'exclusive'];

        $b2bSpecial = $this->activeSpecialPrice($product, ['b2b']);
        if ($b2bSpecial !== null) return ['price' => $b2bSpecial, 'source' => 'b2b_special', 'tax_mode' => 'exclusive'];

        foreach ([['model' => $sellUnit, 'source' => 'standard_b2b_sell_unit'], ['model' => $variant, 'source' => 'standard_b2b_variant'], ['model' => $product, 'source' => 'standard_b2b_product']] as $entry) {
            $standard = $this->positiveNumber($entry['model']?->standard_b2b_price ?? null);
            if ($standard !== null) {
                return ['price' => $standard, 'source' => $entry['source'], 'tax_mode' => 'exclusive'];
            }
        }

        $allSpecial = $this->activeSpecialPrice($product, ['all']);
        if ($allSpecial !== null) return ['price' => $allSpecial, 'source' => 'all_special', 'tax_mode' => 'exclusive'];

        if ((bool) config('pricing.b2b_allow_retail_fallback', true)) {
            return ['price' => $this->retailBasePrice($product, $variant), 'source' => 'base_price_fallback', 'tax_mode' => 'exclusive'];
        }

        return ['price' => 0.0, 'source' => 'not_configured', 'tax_mode' => 'exclusive'];
    }

    protected function b2cDisplayPrice(Product $product, ?ProductVariant $variant = null): float
    {
        $exGst = $this->b2cAccountingPrice($product, $variant);
        return $this->displayForB2C($product, $exGst);
    }

    protected function b2cAccountingPrice(Product $product, ?ProductVariant $variant = null): float
    {
        if ($variant) {
            $variantPrice = $this->positiveNumber($variant->special_price ?? null)
                ?? $this->positiveNumber($variant->sale_price ?? null)
                ?? $this->positiveNumber($variant->price ?? null)
                ?? $this->positiveNumber($variant->base_price ?? null);

            if ($variantPrice !== null) return round($variantPrice, 2);
        }

        return $this->activeSpecialPrice($product, ['b2c', 'all'])
            ?? $this->retailBasePrice($product, null);
    }

    protected function b2cDisplayBasePrice(Product $product, ?ProductVariant $variant = null): float
    {
        return $this->displayForB2C($product, $this->retailBasePrice($product, $variant));
    }

    protected function retailBasePrice(Product $product, ?ProductVariant $variant = null): float
    {
        if ($variant) {
            foreach (['price', 'base_price'] as $field) {
                $value = $this->positiveNumber($variant->{$field} ?? null);
                if ($value !== null) {
                    return round($value, 2);
                }
            }
        }

        foreach (['base_price', 'price'] as $field) {
            $value = $this->positiveNumber($product->{$field} ?? null);
            if ($value !== null) return round($value, 2);
        }

        return 0.0;
    }

    protected function b2bCompareAtPrice(Product $product, ?ProductVariant $variant = null): float
    {
        return $this->displayForB2B($product, $this->retailBasePrice($product, $variant));
    }

    protected function activeSpecialPrice(Product $product, array $audiences): ?float
    {
        if (! (bool) ($product->is_special ?? false)) return null;
        if (! in_array((string) ($product->special_audience ?? 'b2c'), $audiences, true)) return null;

        $price = $this->positiveNumber($product->special_price ?? null);
        if ($price === null) return null;

        $now = now();
        if ($product->special_starts_at && $product->special_starts_at->gt($now)) return null;
        if ($product->special_ends_at && $product->special_ends_at->lt($now)) return null;

        return round($price, 2);
    }

    protected function getCustomerOverridePrice(int $userId, int $productId, ?int $variantId, ?int $sellUnitId, string $today): ?float
    {
        $q = CustomerProductPrice::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('is_active', true);

        if ($sellUnitId !== null) {
            $q->where('product_sell_unit_id', $sellUnitId)->whereNull('product_variant_id');
        } elseif ($variantId !== null) {
            $q->where('product_variant_id', $variantId)->whereNull('product_sell_unit_id');
        } else {
            $q->whereNull('product_variant_id')->whereNull('product_sell_unit_id');
        }

        $q->where(function ($x) use ($today) {
            $x->whereNull('valid_from')->orWhereDate('valid_from', '<=', $today);
        })->where(function ($x) use ($today) {
            $x->whereNull('valid_to')->orWhereDate('valid_to', '>=', $today);
        });

        $row = $q->orderByDesc('valid_from')->orderByDesc('id')->first();
        return $row ? $this->positiveNumber($row->price) : null;
    }

    protected function b2bPriceSource(User $user, Product $product, ?ProductVariant $variant, ?ProductSellUnit $sellUnit): string
    {
        $today = now()->toDateString();
        return (string) ($this->b2bPriceCandidate($user, $product, $variant, $sellUnit, $today)['source'] ?? 'not_configured');
    }

    protected function b2cPriceSource(Product $product): string
    {
        return $this->activeSpecialPrice($product, ['b2c', 'all']) !== null ? 'b2c_special' : 'retail';
    }

    protected function displayForB2C(Product $product, float $exGstPrice): float
    {
        return $this->b2cPriceIncludesGst($product) ? $this->addGst($product, $exGstPrice) : round($exGstPrice, 2);
    }

    protected function displayForB2B(Product $product, float $exGstPrice): float
    {
        return $this->b2bPriceIncludesGst($product) ? $this->addGst($product, $exGstPrice) : round($exGstPrice, 2);
    }

    protected function addGst(Product $product, float $price): float
    {
        $rate = $this->gstRateFor($product);
        if ($price <= 0 || $rate <= 0) return round($price, 2);

        return round($price * (1 + ($rate / 100)), 2);
    }

    protected function removeGst(Product $product, float $price): float
    {
        $rate = $this->gstRateFor($product);
        if ($price <= 0 || $rate <= 0) return round($price, 2);

        return round($price / (1 + ($rate / 100)), 2);
    }

    protected function gstRateFor(Product $product): float
    {
        return app(GstRateService::class)->rateForProduct($product);
    }

    protected function b2cPriceIncludesGst(Product $product): bool
    {
        return (bool) ($product->b2c_price_includes_gst ?? true);
    }

    protected function b2bPriceIncludesGst(Product $product): bool
    {
        return (bool) ($product->b2b_price_includes_gst ?? false);
    }

    protected function isB2B(?User $user): bool
    {
        return $user && (($user->customer_type ?? 'b2c') === 'b2b');
    }

    protected function positiveNumber(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) return null;
        $number = (float) $value;
        return $number > 0 ? $number : null;
    }
}
