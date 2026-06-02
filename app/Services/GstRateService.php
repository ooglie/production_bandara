<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\HsnCode;
use App\Models\Product;
use App\Models\User;

class GstRateService
{
    public function rateForCartItem(?CartItem $item, ?User $user = null): float
    {
        if (! $item) {
            return 0.0;
        }

        $product = $item->relationLoaded('product')
            ? $item->product
            : Product::query()->with('hsnCode')->find($item->product_id);

        return $this->rateForProduct($product, $user);
    }

    public function rateForProduct(?Product $product, ?User $user = null): float
    {
        if (! $product) {
            return 0.0;
        }

        $productRate = $this->normaliseRate($product->getAttribute('gst_rate'));
        $hsnRate = $this->hsnRateFor($product);

        // Prefer configured positive HSN GST when present.
        if ($hsnRate !== null && $hsnRate > 0) {
            return $hsnRate;
        }

        // Then use the product-level snapshot when positive.
        if ($productRate > 0) {
            return $productRate;
        }

        // Critical B2B rule:
        // B2B prices are normally shown/entered ex-GST. If a B2B cart/checkout
        // line reaches tax calculation with no positive product/HSN GST rate,
        // do not silently treat it as tax-free. Use the configured default GST
        // rate unless this safety fallback is explicitly disabled.
        if ($this->shouldApplyB2BDefaultFallback($product, $user)) {
            return $this->defaultRate();
        }

        // Respect explicit zero-rated HSNs for guest/B2C or for projects that
        // have disabled the B2B fallback above.
        if ($hsnRate !== null && $hsnRate <= 0) {
            return 0.0;
        }

        // Safety net for older/local rows that have no usable GST source at all.
        if ((bool) config('pricing.fallback_missing_product_gst_rate', true)) {
            return $this->defaultRate();
        }

        return 0.0;
    }

    protected function shouldApplyB2BDefaultFallback(Product $product, ?User $user): bool
    {
        if (! $user || (($user->customer_type ?? 'b2c') !== 'b2b')) {
            return false;
        }

        if (! (bool) config('pricing.b2b_force_default_gst_when_zero_rate', true)) {
            return false;
        }

        // If the B2B channel is configured as GST-inclusive for this product,
        // the line price already includes GST for display. The accounting price
        // is still ex-GST, but the admin has opted into inclusive B2B entry.
        // For the normal B2B ex-GST case, missing/zero rate must be taxable.
        return ! (bool) ($product->b2b_price_includes_gst ?? false);
    }

    protected function hsnRateFor(Product $product): ?float
    {
        if ($product->relationLoaded('hsnCode')) {
            return $product->hsnCode
                ? $this->normaliseRate($product->hsnCode->getAttribute('gst_rate'))
                : null;
        }

        $hsnId = $product->getAttribute('hsn_code_id');
        if (! $hsnId) {
            return null;
        }

        $rate = HsnCode::query()
            ->whereKey($hsnId)
            ->value('gst_rate');

        return $rate === null ? null : $this->normaliseRate($rate);
    }

    protected function normaliseRate(mixed $value): float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return 0.0;
        }

        return round(max((float) $value, 0.0), 2);
    }

    protected function defaultRate(): float
    {
        return round(max((float) config('pricing.default_gst_rate', 5), 0.0), 2);
    }
}
