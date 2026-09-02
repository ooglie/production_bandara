<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication guards
    |--------------------------------------------------------------------------
    |
    | The existing "web" guard remains the customer guard. The "staff" guard
    | uses the same users provider but stores its identity under a different
    | guard key and, through the middleware below, a different session cookie.
    |
    */

    'staff_guard' => 'staff',

    'customer_guard' => 'web',

    'guard' => [
        'driver' => 'session',
        'provider' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Staff session
    |--------------------------------------------------------------------------
    |
    | The cookie name is intentionally different from SESSION_COOKIE. The
    | default path is "/" because a few existing staff actions (for example
    | shared ticket attachment downloads and legacy impersonation endpoints)
    | can live outside /admin. Route-aware middleware still selects the
    | customer session for ordinary storefront requests.
    |
    */

    'session' => [
        'cookie' => env('STAFF_SESSION_COOKIE', 'bandara_staff_session'),
        'path' => env('STAFF_SESSION_PATH', '/'),
        'domain' => env('STAFF_SESSION_DOMAIN', env('SESSION_DOMAIN')),
        'secure' => env('STAFF_SESSION_SECURE_COOKIE', env('SESSION_SECURE_COOKIE')),
        'http_only' => true,
        'same_site' => env('STAFF_SESSION_SAME_SITE', env('SESSION_SAME_SITE', 'lax')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Account classification
    |--------------------------------------------------------------------------
    |
    | Role matching is case-insensitive and ignores spaces, dots and hyphens.
    | Existing Spatie role/permission guard_name values are deliberately not
    | migrated, avoiding a destructive permissions-table change.
    |
    */

    'staff_roles' => array_values(array_unique(array_merge(
        [
            'Admin',
            'Super Admin',
            'SuperAdmin',
            'Manager',
            'Accountant',
            'CAAccountant',
            'CA Accountant',
            'Support',
            'Stores',
            'DeliveryAgent',
            'Delivery Agent',
            'DeliveryBoy',
            'Delivery Boy',
            'Staff',
        ],
        array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('STAFF_AUTH_ROLES', ''))
        )))
    ))),

    'customer_roles' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env(
            'CUSTOMER_AUTH_ROLES',
            'B2C Customer,B2B Customer,B2C,B2B,Customer'
        ))
    ))),

    /*
     * Existing accounts without a recognised staff role continue to use the
     * customer login. This preserves current B2C/B2B registrations that may
     * not yet have an explicit role row.
     */
    'allow_non_staff_customer_login' => true,

    'login_columns' => ['email', 'username', 'phone', 'mobile', 'whatsapp_number'],

    /*
    |--------------------------------------------------------------------------
    | Route classification
    |--------------------------------------------------------------------------
    */

    'staff_path_prefixes' => [
        'admin',
        'support',
        'manager',
        'accountant',
        'stores',
        'delivery',
    ],

    'staff_route_name_prefixes' => [
        'admin.',
        'support.',
        'manager.',
        'accountant.',
        'stores.',
        'delivery.',
    ],

    'public_staff_route_names' => [
        'admin.login',
        'admin.login.store',
    ],

    /*
     * Shared routes are selected as staff only when the separate staff cookie
     * is present. This preserves existing ticket attachment URLs.
     */
    'shared_staff_route_tokens' => [
        'ticket attachment',
        'ticket-attachment',
        'ticket_attachment',
        'ticket.attachments',
        'tickets.attachments',
    ],

    /*
    |--------------------------------------------------------------------------
    | Redirects
    |--------------------------------------------------------------------------
    */

    'role_dashboard_routes' => [
        [
            'roles' => ['Admin', 'Super Admin', 'SuperAdmin'],
            'routes' => ['admin.dashboard', 'admin.index'],
        ],
        [
            'roles' => ['Manager'],
            'routes' => ['manager.dashboard', 'admin.dashboard'],
        ],
        [
            'roles' => ['Support'],
            'routes' => ['support.dashboard'],
        ],
        [
            'roles' => ['Accountant', 'CAAccountant', 'CA Accountant'],
            'routes' => ['accountant.dashboard', 'admin.dashboard'],
        ],
        [
            'roles' => ['Stores'],
            'routes' => ['stores.dashboard', 'admin.stores.dashboard'],
        ],
        [
            'roles' => ['DeliveryAgent', 'Delivery Agent', 'DeliveryBoy', 'Delivery Boy'],
            'routes' => ['delivery.index'],
        ],
        [
            'roles' => ['Staff'],
            'routes' => ['admin.dashboard', 'admin.index'],
        ],
    ],

    'dashboard_routes' => [
        'admin.dashboard',
        'admin.index',
    ],

    'dashboard_path' => '/admin',

    /*
    |--------------------------------------------------------------------------
    | Impersonation bridge
    |--------------------------------------------------------------------------
    |
    | Legacy impersonation routes are retained, but their matched controller
    | action is safely bridged from the staff session to the customer session.
    |
    */

    'impersonation' => [
        'enabled' => true,
        'ttl_seconds' => 120,
        'allowed_staff_roles' => ['Admin', 'Super Admin', 'SuperAdmin', 'Manager'],
        'after_start_path' => '/account',
        'after_leave_path' => '/admin',
    ],

];
