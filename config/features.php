<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Feature toggles
    |--------------------------------------------------------------------------
    |
    | Central place to enable/disable optional features for Frozen – Bandara.
    | You can later wire these to env() if you want per-environment control.
    |
    */

    // Product-level features
    'dynamic_pricing'           => true,
    'out_of_stock_notifications'=> true,

    // Frontend UI features
    'dark_mode'                 => true,
    'newsletter'                => true,
    'wishlist'                  => true,
];
