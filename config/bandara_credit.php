<?php

use App\Models\Order;

return [

    /*
    |--------------------------------------------------------------------------
    | Master flags
    |--------------------------------------------------------------------------
    */
    'enabled' => env('BANDARA_CREDIT_ENABLED', false),
    'shadow_mode' => env('BANDARA_CREDIT_SHADOW_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | Feature flags
    |--------------------------------------------------------------------------
    */
    'earn_enabled' => env('BANDARA_CREDIT_EARN_ENABLED', false),
    'redeem_enabled' => env('BANDARA_CREDIT_REDEEM_ENABLED', false),

    'repeat_bonus_enabled' => env('BANDARA_CREDIT_REPEAT_BONUS_ENABLED', true),
    'welcome_bonus_enabled' => env('BANDARA_CREDIT_WELCOME_BONUS_ENABLED', true),
    'birthday_bonus_enabled' => env('BANDARA_CREDIT_BIRTHDAY_ENABLED', true),
    'tiers_enabled' => env('BANDARA_CREDIT_TIERS_ENABLED', true),

    // Controls automatic order-lifecycle writes only. Manual CLI posting can be
    // used independently as long as enabled/earn_enabled are true and shadow_mode is false.
    'auto_post_enabled' => env('BANDARA_CREDIT_AUTO_POST_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Customer UI / redemption controls
    |--------------------------------------------------------------------------
    |
    | Checkout redemption uses reserve/post/release ledger accounting. Keep
    | redeem_enabled false until migrations, wallet reconciliation, and smoke
    | tests have passed in production/staging.
    |
    */
    'redemption' => [
        // Minimum credits a customer must redeem in one checkout attempt.
        'minimum_points' => (int) env('BANDARA_CREDIT_REDEEM_MINIMUM_POINTS', env('BANDARA_CREDIT_REDEEM_MIN_BALANCE', 500)),

        // Rupee value of one Bandara Credit. Keep 1 for ₹1 = 1 credit.
        'point_value' => (float) env('BANDARA_CREDIT_POINT_VALUE', 1),

        // Safety cap: maximum percentage of the payable order total that can be paid with credits.
        'max_order_percentage' => (float) env('BANDARA_CREDIT_REDEEM_MAX_ORDER_PERCENTAGE', 20),

        // Keep a small payable amount for Razorpay unless a dedicated full-credit payment flow is added.
        'minimum_payable_amount' => (float) env('BANDARA_CREDIT_REDEEM_MINIMUM_PAYABLE_AMOUNT', 1),

        // Release unpaid checkout reservations older than this many minutes.
        'reservation_ttl_minutes' => (int) env('BANDARA_CREDIT_RESERVATION_TTL_MINUTES', 180),
    ],

    'next_reward_at' => (int) env('BANDARA_CREDIT_NEXT_REWARD_AT', 500),
    'history_limit' => (int) env('BANDARA_CREDIT_HISTORY_LIMIT', 8),

    /*
    |--------------------------------------------------------------------------
    | Earning rules
    |--------------------------------------------------------------------------
    */
    'earning' => [
        'per_amount_spent' => (int) env('BANDARA_CREDIT_PER_AMOUNT_SPENT', 100),   // ₹100 spent
        'credit_amount' => (int) env('BANDARA_CREDIT_AMOUNT_PER_BLOCK', env('BANDARA_CREDIT_CREDIT_AMOUNT', 1)),        // earns ₹1 Bandara Credit
        'repeat_window_days' => (int) env('BANDARA_CREDIT_REPEAT_WINDOW_DAYS', 10),
        'welcome_credit' => (int) env('BANDARA_CREDIT_WELCOME_AMOUNT', 100),
        'welcome_min_order_value' => (int) env('BANDARA_CREDIT_WELCOME_MIN_ORDER_VALUE', 999),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tier rules
    |--------------------------------------------------------------------------
    */
    'tiers' => [
        'silver' => [
            'threshold' => (int) env('BANDARA_CREDIT_SILVER_THRESHOLD', 0),
            'birthday_credit' => (int) env('BANDARA_CREDIT_SILVER_BIRTHDAY', 100),
        ],
        'gold' => [
            'threshold' => (int) env('BANDARA_CREDIT_GOLD_THRESHOLD', 10000),
            'birthday_credit' => (int) env('BANDARA_CREDIT_GOLD_BIRTHDAY', 150),
        ],
        'platinum' => [
            'threshold' => (int) env('BANDARA_CREDIT_PLATINUM_THRESHOLD', 25000),
            'birthday_credit' => (int) env('BANDARA_CREDIT_PLATINUM_BIRTHDAY', 200),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Order model + field mapping
    |--------------------------------------------------------------------------
    |
    | Keep schema assumptions here, not inside service logic.
    |
    | IMPORTANT:
    | eligible_spend should be:
    | merchandise subtotal after coupon/discount, before GST and delivery.
    |
    */
    'order_model' => Order::class,

    'order_mapping' => [
        'user_id' => 'user_id',
        'status' => 'status',
        'placed_at' => 'created_at',
        'eligible_spend' => 'subtotal',
    ],

    /*
    |--------------------------------------------------------------------------
    | Order lifecycle statuses
    |--------------------------------------------------------------------------
    */
    'pending_statuses' => [
        'processing',
        'shipped',
    ],

    'successful_statuses' => [
        'delivered',
        'completed',
    ],

    'cancelled_statuses' => [
        'cancelled',
    ],

    'eligibility' => [
        'mode' => env('BANDARA_CREDIT_ELIGIBILITY_MODE', 'b2c'), // b2c | column | role | all
        'allowed_roles' => ['Customer'], // B2C roles only
        'column' => env('BANDARA_CREDIT_ELIGIBILITY_COLUMN', 'customer_type'),
        'b2c_value' => env('BANDARA_CREDIT_ELIGIBILITY_B2C_VALUE', 'b2c'),
    ],
];
