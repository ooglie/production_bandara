<?php

declare(strict_types=1);

/*
 | B2B UI classes remain sourced from the approved Bandara project.
 | Hotfix v3 removes positioning/size utilities that caused content panels
 | to behave like dropdown popovers. No new palette or stylesheet is added.
 */

return array (
  'location' => 
  array (
    'country_code' => 'IN',
    'states' => 
    array (
      'table' => 'states',
      'id' => 'id',
      'name' => 'name',
      'relation_key' => 'code',
      'country_column' => 'country_code',
      'active' => 'is_active',
      'sort' => 'sort_order',
    ),
    'cities' => 
    array (
      'table' => 'cities',
      'id' => 'id',
      'name' => 'name',
      'state_key' => 'state_code',
      'country_column' => 'country_code',
      'active' => 'is_active',
      'sort' => 'sort_order',
    ),
  ),
  'entry_intent' => 
  array (
    'session_key' => 'bandara.business_account_intent',
    'ttl_seconds' => 7200,
  ),
  'view' => 
  array (
    'customer_layout' => 'layouts.customer',
    'customer_section' => 'content',
    'admin_layout' => 'layouts.company',
    'admin_section' => 'content',
  ),
  'ui' => 
  array (
    'container' => 'mx-auto w-full max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8',
    'panel' => 'mt-2 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-lg p-2',
    'panel_compact' => 'mt-2 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-lg p-2',
    'heading' => 'text-2xl sm:text-3xl font-semibold tracking-tight text-gray-900 dark:text-gray-50 leading-tight',
    'subheading' => 'text-lg font-semibold text-gray-900 dark:text-gray-50',
    'text' => 'max-w-md text-sm text-gray-600 dark:text-gray-300 leading-relaxed',
    'muted' => 'mt-1 text-xs text-gray-500 dark:text-gray-400',
    'label' => 'block text-xs font-medium text-gray-700 dark:text-gray-300',
    'field' => 'mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500',
    'checkbox' => 'rounded-sm',
    'button_primary' => 'inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2.5 text-xs font-medium hover:bg-gray-800 dark:hover:bg-gray-200',
    'button_secondary' => 'inline-flex text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800',
    'button_danger' => 'inline-flex text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800',
    'link' => 'text-[11px] text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100',
    'alert_success' => 'rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800',
    'alert_error' => 'rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 sm:p-8 space-y-5',
    'alert_info' => 'rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-xs',
    'badge' => 'inline-flex w-max rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300',
    'table' => 'min-w-full text-xs',
    'table_head' => 'text-left text-gray-500',
    'table_cell' => 'px-3 py-2 text-left font-medium',
    'nav_link' => 'inline-flex items-center gap-2',
    'admin_nav_link' => 'hover:underline',
  ),
);
