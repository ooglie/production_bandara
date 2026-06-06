<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Canonical Bandara Role / Permission Matrix
    |--------------------------------------------------------------------------
    |
    | Spatie's native tables remain the source of truth:
    | - roles
    | - permissions
    | - model_has_roles
    | - model_has_permissions
    | - role_has_permissions
    |
    | Permission names intentionally keep the existing app format
    | ("view products", "manage products", ...), so current @can(), can:,
    | and Spatie checks continue to work without a wide rename to dot syntax.
    |
    */

    'guard' => 'web',

    'roles' => [
        'Admin' => [
            'label' => 'Admin',
            'description' => 'Full system access. This role is always synced to every permission.',
            'locked' => true,
        ],
        'Manager' => [
            'label' => 'Manager',
            'description' => 'Operations manager with catalog, order, customer, vendor, store, support, marketing and rewards access; no user/role administration.',
        ],
        'Support' => [
            'label' => 'Support',
            'description' => 'Support team access to customer/order context and ticket handling.',
        ],
        'Accountant' => [
            'label' => 'Accountant',
            'description' => 'Finance operations access for invoices, payments, vendor payments, reports and order/customer context.',
        ],
        'CAAccountant' => [
            'label' => 'CA Accountant',
            'description' => 'CA/accounting view access for invoices, payments, reports and order/customer context.',
        ],
        'Stores' => [
            'label' => 'Stores',
            'description' => 'Stores/inventory/production access with vendor invoice support.',
        ],
        'DeliveryAgent' => [
            'label' => 'Delivery Agent',
            'description' => 'Mobile delivery role with access only to assigned deliveries.',
        ],
        'Customer' => [
            'label' => 'Customer',
            'description' => 'Frontend customer role. No back-office permissions.',
        ],
    ],

    // Legacy names found in old route/view code or old databases. These are
    // merged into the canonical roles by the repair migration/seeder.
    'role_aliases' => [
        'CA-Accountant' => 'CAAccountant',
        'CA Accountant' => 'CAAccountant',
        'Account' => 'Accountant',
        'admin' => 'Admin',
        'manager' => 'Manager',
        'support' => 'Support',
        'accountant' => 'Accountant',
        'stores' => 'Stores',
        'customer' => 'Customer',
    ],

    'modules' => [
        'products'   => ['view', 'manage'],
        'orders'     => ['view', 'manage'],
        'invoices'   => ['view', 'manage'],
        'customers'  => ['view', 'manage'],
        'vendors'    => ['view', 'manage'],
        'coupons'    => ['view', 'manage'],
        'payments'   => ['view', 'manage'],
        'stores'     => ['view', 'manage'],
        'tickets'    => ['view', 'manage'],
        'marketing'  => ['view', 'manage'],
        'content'    => ['view', 'manage'],
        'rewards'    => ['view', 'manage'],
        'users'      => ['manage'],
        'settings'   => ['manage'],
        'reports'    => ['view'],
    ],

    'labels' => [
        'products'   => 'Products / Catalog',
        'orders'     => 'Orders',
        'invoices'   => 'Invoices',
        'customers'  => 'Customers / B2B',
        'vendors'    => 'Vendors',
        'coupons'    => 'Coupons & Discounts',
        'payments'   => 'Payments',
        'stores'     => 'Stores / Inventory / Production',
        'tickets'    => 'Support Tickets',
        'marketing'  => 'Marketing / Newsletters',
        'content'    => 'Content / Announcements / Collections',
        'rewards'    => 'Bandara Credit / Rewards',
        'users'      => 'Users & Roles',
        'settings'   => 'Settings',
        'reports'    => 'Reports',
    ],

    // Existing legacy permissions that are used by older code or tables but do
    // not fit the simple action + module naming pattern above. Keep these until
    // route/controller checks are fully migrated to the canonical module names.
    'extra_permissions' => [
        'create vendor invoice',
        'manage vendor payments',
        'manage sales',
        'view assigned deliveries',
        'update assigned delivery status',
    ],

    'role_permissions' => [
        'Admin' => ['*'],

        'Manager' => [
            'view products', 'manage products',
            'view orders', 'manage orders', 'manage sales',
            'view invoices', 'manage invoices',
            'view customers', 'manage customers',
            'view vendors', 'manage vendors',
            'view coupons', 'manage coupons',
            'view payments', 'manage payments',
            'view stores', 'manage stores',
            'view tickets', 'manage tickets',
            'view marketing', 'manage marketing',
            'view content', 'manage content',
            'view rewards', 'manage rewards',
            'view reports',
            'create vendor invoice',
            'manage vendor payments',
        ],

        'Support' => [
            'view customers',
            'view orders',
            'view tickets', 'manage tickets',
        ],

        'Accountant' => [
            'view orders',
            'view customers',
            'view invoices', 'manage invoices',
            'view payments', 'manage payments',
            'view vendors',
            'view reports',
            'manage vendor payments',
        ],

        'CAAccountant' => [
            'view orders',
            'view customers',
            'view invoices',
            'view payments',
            'view reports',
        ],

        'Stores' => [
            'view products',
            'view vendors',
            'view stores', 'manage stores',
            'create vendor invoice',
        ],

        'DeliveryAgent' => [
            'view assigned deliveries',
            'update assigned delivery status',
        ],

        'Customer' => [],
    ],
];
