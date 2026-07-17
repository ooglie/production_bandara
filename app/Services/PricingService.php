<?php

namespace App\Services;

use App\Models\CustomerProductPrice;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class PricingService
{
    protected static array $displayCache = [];
    protected static array $accountingCache = [];

    public function quote(?User $user, Product $product, ?ProductVariant $variant = null): array
    {
        $isB2B = $this->isB2B($user);

        $price = $this->priceForContext($user, $product, $variant);
        $base = $isB2B
            ? $this->b2bCompareAtPrice($product, $variant)
            : $this->b2cDisplayBasePrice($product, $variant);

        $source = $isB2B
            ? $this->b2bPriceSource($user, $product, $variant)
            : $this->b2cPriceSource($product);

        $moq = $isB2B
            ? app(B2BTermsService::class)->minOrderQty($user, $product, $variant)
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
            'accounting_unit_price' => round($this->cartUnitPriceFor($user, $product, $variant), 2),
            // Kept for existing views; now means current displayed quote includes GST.
            'price_includes_gst_for_display' => $displayIncludesGst,
            'display_price_includes_gst' => $displayIncludesGst,
        ];
    }

    /** Display price for cards/detail pages. */
    public function priceFor(?User $user, Product $product, ?ProductVariant $variant = null): float
    {
        return $this->priceForContext($user, $product, $variant);
    }

    /** Tax-exclusive unit price for cart/order/accounting calculations. */
    public function cartUnitPriceFor(?User $user, Product $product, ?ProductVariant $variant = null): float
    {
        $isB2B = $this->isB2B($user);
        $today = now()->toDateString();
        $userId = $user?->id ? (int) $user->id : 0;
        $cacheKey = implode('|', [$userId, (int) $product->id, (int) ($variant?->id ?? 0), $today, 'accounting']);

        if (array_key_exists($cacheKey, self::$accountingCache)) {
            return (float) self::$accountingCache[$cacheKey];
        }

        if (! $isB2B) {
            return self::$accountingCache[$cacheKey] = round($this->b2cAccountingPrice($product, $variant), 2);
        }

        $source = $this->b2bPriceCandidate($user, $product, $variant, $today);
        if ($source['price'] <= 0) {
            return self::$accountingCache[$cacheKey] = 0.0;
        }

        $price = (float) $source['price'];

        if (($source['tax_mode'] ?? 'exclusive') === 'inclusive') {
            $price = $this->removeGst($product, $price);
        }

        return self::$accountingCache[$cacheKey] = round($price, 2);
    }

    public function hasB2BPrice(?User $user, Product $product, ?ProductVariant $variant = null): bool
    {
        if (! $this->isB2B($user)) {
            return true;
        }

        $candidate = $this->b2bPriceCandidate($user, $product, $variant, now()->toDateString());

        return (float) ($candidate['price'] ?? 0) > 0;
    }

    /**
     * Apply the B2B catalogue rule at query level so pagination and search
     * counts remain correct. A product is visible only when the signed-in
     * B2B account has an explicit product/variant price, an active B2B/all
     * special, or an active customer-specific price override.
     */
    public function applyProductAvailabilityFilter(Builder $query, ?User $user): Builder
    {
        if (! $this->isB2B($user)) {
            return $query;
        }

        $today = now()->toDateString();
        $productTable = $query->getModel()->getTable();

        return $query->where(function (Builder $available) use ($user, $today, $productTable) {
            $available
                ->where("{$productTable}.standard_b2b_price", '>', 0)
                ->orWhere(function (Builder $special) use ($today, $productTable) {
                    $special
                        ->where("{$productTable}.is_special", true)
                        ->where("{$productTable}.special_price", '>', 0)
                        ->whereIn("{$productTable}.special_audience", ['b2b', 'all'])
                        ->where(function (Builder $starts) use ($today, $productTable) {
                            $starts->whereNull("{$productTable}.special_starts_at")
                                ->orWhereDate("{$productTable}.special_starts_at", '<=', $today);
                        })
                        ->where(function (Builder $ends) use ($today, $productTable) {
                            $ends->whereNull("{$productTable}.special_ends_at")
                                ->orWhereDate("{$productTable}.special_ends_at", '>=', $today);
                        });
                })
                ->orWhereHas('variants', function (Builder $variants) {
                    $variants
                        ->where('standard_b2b_price', '>', 0)
                        ->where(function (Builder $active) {
                            $active->whereNull('is_active')->orWhere('is_active', true);
                        })
                        ->where(function (Builder $visibility) {
                            $visibility->whereNull('customer_visibility')
                                ->orWhereIn('customer_visibility', ['all', 'b2b']);
                        });
                })
                ->orWhereExists(function ($prices) use ($user, $today, $productTable) {
                    $prices->selectRaw('1')
                        ->from('customer_product_prices as cpp')
                        ->whereColumn('cpp.product_id', "{$productTable}.id")
                        ->where('cpp.user_id', $user->id)
                        ->where('cpp.is_active', true)
                        ->where('cpp.price', '>', 0)
                        ->where(function ($starts) use ($today) {
                            $starts->whereNull('cpp.valid_from')
                                ->orWhereDate('cpp.valid_from', '<=', $today);
                        })
                        ->where(function ($ends) use ($today) {
                            $ends->whereNull('cpp.valid_to')
                                ->orWhereDate('cpp.valid_to', '>=', $today);
                        })
                        ->where(function ($target) {
                            $target->whereNull('cpp.product_variant_id')
                                ->orWhereExists(function ($variants) {
                                    $variants->selectRaw('1')
                                        ->from('product_variants as pv')
                                        ->whereColumn('pv.id', 'cpp.product_variant_id')
                                        ->whereNull('pv.deleted_at')
                                        ->where(function ($active) {
                                            $active->whereNull('pv.is_active')->orWhere('pv.is_active', true);
                                        })
                                        ->where(function ($visibility) {
                                            $visibility->whereNull('pv.customer_visibility')
                                                ->orWhereIn('pv.customer_visibility', ['all', 'b2b']);
                                        });
                                });
                        });
                });
        });
    }

    public function productIsAvailableToUser(?User $user, Product $product): bool
    {
        if (! $this->isB2B($user)) {
            return true;
        }

        if ($this->hasB2BPrice($user, $product)) {
            return true;
        }

        $product->loadMissing('variants');

        return $product->variants
            ->contains(fn (ProductVariant $variant) => $this->variantIsAvailableToUser($user, $product, $variant));
    }

    public function variantIsAvailableToUser(?User $user, Product $product, ProductVariant $variant): bool
    {
        if (! $this->isB2B($user)) {
            return true;
        }

        if ($variant->is_active !== null && ! (bool) $variant->is_active) {
            return false;
        }

        if (method_exists($variant, 'isVisibleToCustomerType') && ! $variant->isVisibleToCustomerType('b2b')) {
            return false;
        }

        return $this->hasB2BPrice($user, $product, $variant);
    }

    protected function priceForContext(?User $user, Product $product, ?ProductVariant $variant = null): float
    {
        $today = now()->toDateString();
        $userId = $user?->id ? (int) $user->id : 0;
        $cacheKey = implode('|', [$userId, (int) $product->id, (int) ($variant?->id ?? 0), $today, 'display']);

        if (array_key_exists($cacheKey, self::$displayCache)) {
            return (float) self::$displayCache[$cacheKey];
        }

        if ($this->isB2B($user)) {
            return self::$displayCache[$cacheKey] = round($this->b2bDisplayPrice($user, $product, $variant, $today), 2);
        }

        return self::$displayCache[$cacheKey] = round($this->b2cDisplayPrice($product, $variant), 2);
    }

    protected function b2bDisplayPrice(User $user, Product $product, ?ProductVariant $variant, string $today): float
    {
        $source = $this->b2bPriceCandidate($user, $product, $variant, $today);
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

    protected function b2bPriceCandidate(User $user, Product $product, ?ProductVariant $variant, string $today): array
    {
        $productId = (int) $product->id;
        $variantId = $variant?->id ? (int) $variant->id : null;

        if ($variantId) {
            $override = $this->getCustomerOverridePrice($user->id, $productId, $variantId, $today);
            if ($override !== null) return ['price' => $override, 'source' => 'customer_b2b_variant', 'tax_mode' => 'exclusive'];
        }

        $override = $this->getCustomerOverridePrice($user->id, $productId, null, $today);
        if ($override !== null) return ['price' => $override, 'source' => 'customer_b2b_product', 'tax_mode' => 'exclusive'];

        $b2bSpecial = $this->activeSpecialPrice($product, ['b2b']);
        if ($b2bSpecial !== null) return ['price' => $b2bSpecial, 'source' => 'b2b_special', 'tax_mode' => 'exclusive'];

        foreach ([['model' => $variant, 'source' => 'standard_b2b_variant'], ['model' => $product, 'source' => 'standard_b2b_product']] as $entry) {
            $standard = $this->positiveNumber($entry['model']?->standard_b2b_price ?? null);
            if ($standard !== null) {
                return ['price' => $standard, 'source' => $entry['source'], 'tax_mode' => 'exclusive'];
            }
        }

        $allSpecial = $this->activeSpecialPrice($product, ['all']);
        if ($allSpecial !== null) return ['price' => $allSpecial, 'source' => 'all_special', 'tax_mode' => 'exclusive'];

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

    protected function getCustomerOverridePrice(int $userId, int $productId, ?int $variantId, string $today): ?float
    {
        $q = CustomerProductPrice::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('is_active', true);

        if ($variantId !== null) {
            $q->where('product_variant_id', $variantId);
        } else {
            $q->whereNull('product_variant_id');
        }

        $q->where(function ($x) use ($today) {
            $x->whereNull('valid_from')->orWhereDate('valid_from', '<=', $today);
        })->where(function ($x) use ($today) {
            $x->whereNull('valid_to')->orWhereDate('valid_to', '>=', $today);
        });

        $row = $q->orderByDesc('valid_from')->orderByDesc('id')->first();
        return $row ? $this->positiveNumber($row->price) : null;
    }

    protected function b2bPriceSource(User $user, Product $product, ?ProductVariant $variant): string
    {
        $today = now()->toDateString();
        return (string) ($this->b2bPriceCandidate($user, $product, $variant, $today)['source'] ?? 'not_configured');
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
