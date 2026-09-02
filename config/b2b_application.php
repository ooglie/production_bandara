<?php

declare(strict_types=1);

return [
    'customer_type' => [
        'b2c' => 'b2c',
        'b2b' => 'b2b'
    ],
    'admin_roles' => [
        'Admin',
        'Manager'
    ],
    'permissions' => [
        'view' => 'admin.b2b-applications.view',
        'review' => 'admin.b2b-applications.review',
        'approve' => 'admin.b2b-applications.approve'
    ],
    'location' => [
        'states' => [
            'table' => 'states',
            'id' => 'id',
            'name' => 'name',
            'active' => 'is_active'
        ],
        'cities' => [
            'table' => 'cities',
            'id' => 'id',
            'name' => 'name',
            'state_id' => 'state_id',
            'active' => 'is_active'
        ]
    ],
    'business_types' => [
        'restaurant' => 'Restaurant',
        'hotel' => 'Hotel',
        'cafe' => 'Café',
        'caterer' => 'Caterer',
        'cloud_kitchen' => 'Cloud kitchen',
        'retail_store' => 'Retail store',
        'supermarket' => 'Supermarket',
        'distributor' => 'Distributor',
        'food_manufacturer' => 'Food manufacturer',
        'corporate_institution' => 'Corporate or institution',
        'other' => 'Other'
    ],
    'product_categories' => [
        'meat' => 'Meat',
        'seafood' => 'Seafood',
        'cheese_dairy' => 'Cheese and dairy',
        'bakery' => 'Bakery',
        'frozen_snacks' => 'Frozen snacks',
        'ready_to_cook' => 'Ready-to-cook products',
        'imported_speciality' => 'Imported speciality products',
        'other' => 'Other'
    ],
    'monthly_purchase_ranges' => [
        'below_25000' => 'Below ₹25,000',
        '25000_50000' => '₹25,000–₹50,000',
        '50000_100000' => '₹50,000–₹1,00,000',
        '100000_250000' => '₹1,00,000–₹2,50,000',
        'above_250000' => 'Above ₹2,50,000',
        'not_sure' => 'Not sure yet'
    ],
    'purchase_frequencies' => [
        'daily' => 'Daily',
        'several_weekly' => 'Several times a week',
        'weekly' => 'Weekly',
        'fortnightly' => 'Fortnightly',
        'monthly' => 'Monthly',
        'as_required' => 'As required'
    ],
    'notifications' => [
        'database' => true,
        'mail' => true
    ],
    'commercial_integration' => [
        'user_columns' => [
            'customer_type' => 'customer_type',
            'pay_later_enabled' => null,
            'credit_limit' => null,
            'payment_terms_days' => null,
            'minimum_order_value' => null,
            'price_group_id' => null,
            'account_manager_id' => null
        ],
        'existing_terms' => [
            'table' => 'b2b_customer_terms',
            'user_key' => 'user_id',
            'columns' => [
                'pay_later_enabled' => 'pay_later_enabled',
                'credit_limit' => 'credit_limit',
                'payment_terms_days' => 'payment_terms_days',
                'minimum_order_value' => null,
                'price_group_id' => null,
                'account_manager_id' => null,
                'updated_at' => 'updated_at',
                'created_at' => 'created_at'
            ],
            'update_existing_only' => true
        ]
    ]
];
