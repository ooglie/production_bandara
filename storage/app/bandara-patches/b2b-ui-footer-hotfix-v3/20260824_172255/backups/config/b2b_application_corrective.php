<?php

declare(strict_types=1);

return [
    /*
     | The live Bandara schema stores the selected state by states.id, while
     | cities are related through states.code -> cities.state_code.
     */
    'location' => [
        'country_code' => 'IN',
        'states' => [
            'table' => 'states',
            'id' => 'id',
            'name' => 'name',
            'relation_key' => 'code',
            'country_column' => 'country_code',
            'active' => 'is_active',
            'sort' => 'sort_order',
        ],
        'cities' => [
            'table' => 'cities',
            'id' => 'id',
            'name' => 'name',
            'state_key' => 'state_code',
            'country_column' => 'country_code',
            'active' => 'is_active',
            'sort' => 'sort_order',
        ],
    ],

    'entry_intent' => [
        'session_key' => 'bandara.business_account_intent',
        'ttl_seconds' => 7200,
    ],

    /* Rendered by the installer from the current approved Bandara layouts. */
    'view' => [
        'customer_layout' => 'layouts.customer',
        'customer_section' => 'content',
        'admin_layout' => 'layouts.company',
        'admin_section' => 'content',
    ],

    /*
     | Class strings are copied from the current project at install time.
     | The B2B module therefore reuses the approved UI rather than defining a
     | new palette, component system, header, footer or dark-mode treatment.
     */
    'ui' => [
        'container' => 'max-w-5xl mx-auto px-4 py-6 space-y-6',
        'panel' => 'absolute right-0 z-50 mt-2 w-72 max-h-96 overflow-y-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-lg p-2',
        'panel_compact' => 'absolute right-0 z-50 mt-2 w-72 max-h-96 overflow-y-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-lg p-2',
        'heading' => 'text-2xl sm:text-3xl font-semibold tracking-tight text-gray-900 dark:text-gray-50 leading-tight',
        'subheading' => 'text-lg font-semibold text-gray-900 dark:text-gray-50',
        'text' => 'max-w-md text-sm text-gray-600 dark:text-gray-300 leading-relaxed',
        'muted' => 'mt-1 text-xs text-gray-500 dark:text-gray-400',
        'label' => 'block text-xs font-medium text-gray-700 dark:text-gray-300',
        'field' => 'mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500',
        'checkbox' => 'rounded-sm',
        'button_primary' => 'w-full inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2.5 text-xs font-medium hover:bg-gray-800 dark:hover:bg-gray-200',
        'button_secondary' => 'text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800',
        'button_danger' => 'text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800',
        'link' => 'text-[11px] text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100',
        'alert_success' => 'inline-flex items-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800',
        'alert_error' => 'rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 sm:p-8 space-y-5',
        'alert_info' => 'rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-xs',
        'badge' => 'inline-flex w-max rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300',
        'table' => 'min-w-full text-xs',
        'table_head' => 'text-left text-gray-500',
        'table_cell' => 'px-3 py-2 text-left font-medium',
        'nav_link' => 'inline-flex items-center gap-2',
        'admin_nav_link' => 'hover:underline',
    ],
];
