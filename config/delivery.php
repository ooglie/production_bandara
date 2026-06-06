<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Delivery / Cold-chain Handling Defaults
    |--------------------------------------------------------------------------
    |
    | The tables added by this feature are the source of truth for actual fees.
    | These config values are safety defaults used when a rule omits a value and
    | to decide whether checkout should block unmapped pincodes.
    |
    */

    'store_pincode' => env('STORE_PINCODE', '411001'),

    // Keep false while zones are being built out; turn on when all serviceable
    // Pune pincodes are mapped and checkout should block unmapped addresses.
    'require_serviceable_pincode' => (bool) env('DELIVERY_REQUIRE_SERVICEABLE_PINCODE', false),

    'default_delivery_tax_rate' => (float) env('DELIVERY_TAX_RATE', 0),
    'default_handling_tax_rate' => (float) env('HANDLING_TAX_RATE', 0),
];
