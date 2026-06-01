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
];
