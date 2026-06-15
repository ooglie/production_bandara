<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Delivery / Cold-chain Handling Defaults
    |--------------------------------------------------------------------------
    |
    | Delivery fees are primarily table-driven. Distance-based delivery can be
    | enabled with Google Maps/Distance Matrix configuration and will fall back
    | to the pincode-zone rules when configured to do so.
    |
    */

    'store_pincode' => env('STORE_PINCODE', '411001'),

    'store_origin_address' => env('STORE_DELIVERY_ORIGIN_ADDRESS', env('STORE_ADDRESS', '')),
    'store_origin_lat' => env('STORE_DELIVERY_ORIGIN_LAT'),
    'store_origin_lng' => env('STORE_DELIVERY_ORIGIN_LNG'),

    'distance_enabled' => (bool) env('DELIVERY_DISTANCE_ENABLED', false),
    'distance_provider' => strtolower((string) env('DELIVERY_DISTANCE_PROVIDER', 'google')) === 'osrm'
        ? 'google'
        : env('DELIVERY_DISTANCE_PROVIDER', 'google'),
    'distance_required' => (bool) env('DELIVERY_DISTANCE_REQUIRED', false),
    'distance_fallback_to_zone' => (bool) env('DELIVERY_DISTANCE_FALLBACK_TO_ZONE', true),
    'distance_timeout_seconds' => (int) env('DELIVERY_DISTANCE_TIMEOUT_SECONDS', 6),

    'google_maps_api_key' => env('GOOGLE_MAPS_DISTANCE_MATRIX_API_KEY', env('GOOGLE_DISTANCE_MATRIX_API_KEY', env('GOOGLE_MAPS_API_KEY'))),
    'google_geocoding_api_key' => env('GOOGLE_GEOCODING_API_KEY', env('GOOGLE_MAPS_API_KEY')),

    // Keep false while zones are being built out; turn on when all serviceable
    // Pune pincodes are mapped and checkout should block unmapped addresses.
    'require_serviceable_pincode' => (bool) env('DELIVERY_REQUIRE_SERVICEABLE_PINCODE', false),

    'default_delivery_tax_rate' => (float) env('DELIVERY_TAX_RATE', 0),
    'default_handling_tax_rate' => (float) env('HANDLING_TAX_RATE', 0),

    // Bandara currently operates as a frozen/cold-chain storefront, so handling
    // rules should default to the frozen temperature bucket unless the caller
    // passes a more specific mode. Admin can still create an `all` rule.
    'default_handling_temperature_mode' => env('DELIVERY_HANDLING_TEMPERATURE_MODE', 'frozen'),
];
