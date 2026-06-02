<?php

return [
    /*
    |--------------------------------------------------------------------------
    | B2B Pricing Guardrails
    |--------------------------------------------------------------------------
    |
    | Unified storefront rule:
    | - Guests/B2C see public retail pricing and B2C specials.
    | - B2B users see account-aware B2B pricing and MOQ.
    | - If no explicit B2B price exists, active standard products may fall back
    |   to the product/variant base price so the B2B catalogue is not empty.
    |   B2C-only specials never leak into B2B fallback pricing.
    |
    */

    'b2b_allow_retail_fallback' => (bool) env('B2B_ALLOW_RETAIL_PRICE_FALLBACK', true),

    /*
    |--------------------------------------------------------------------------
    | GST Safety Fallback
    |--------------------------------------------------------------------------
    |
    | Product and HSN GST rates should be configured on every saleable product.
    | Older/local rows can still have products.gst_rate = 0 with no usable HSN
    | rate, which makes B2B ex-GST checkout look untaxed. When enabled, the
    | cart/checkout uses this default only when no product/HSN rate exists.
    |
    */
    'default_gst_rate' => (float) env('DEFAULT_GST_RATE', 5),
    'fallback_missing_product_gst_rate' => (bool) env('FALLBACK_MISSING_PRODUCT_GST_RATE', true),

    // B2B prices are normally ex-GST. If product/HSN GST is left as 0,
    // keep B2B checkout taxable with DEFAULT_GST_RATE instead of showing GST 0.
    'b2b_force_default_gst_when_zero_rate' => (bool) env('B2B_FORCE_DEFAULT_GST_WHEN_ZERO_RATE', true),
];
