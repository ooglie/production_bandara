<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Store info
            [
                'key'   => 'store.name',
                'value' => 'Frozen - Bandara by Maytira',
                'type'  => 'string',
                'group' => 'store',
            ],
            [
                'key'   => 'store.logo_path',
                'value' => null,
                'type'  => 'string',
                'group' => 'store',
            ],

            // Tax configuration (GST India)
            [
                'key'   => 'tax.home_state',
                'value' => 'Maharashtra',
                'type'  => 'string',
                'group' => 'tax',
            ],
            [
                'key'   => 'tax.cgst_rate',
                'value' => '2.5',
                'type'  => 'float',
                'group' => 'tax',
            ],
            [
                'key'   => 'tax.sgst_rate',
                'value' => '2.5',
                'type'  => 'float',
                'group' => 'tax',
            ],
            [
                'key'   => 'tax.igst_rate',
                'value' => '5',
                'type'  => 'float',
                'group' => 'tax',
            ],

            // Razorpay placeholders
            [
                'key'   => 'payment.razorpay_key_id',
                'value' => '',
                'type'  => 'string',
                'group' => 'payment',
            ],
            [
                'key'   => 'payment.razorpay_key_secret',
                'value' => '',
                'type'  => 'string',
                'group' => 'payment',
            ],

            // Tally
            [
                'key'   => 'tally.api_key',
                'value' => '',
                'type'  => 'string',
                'group' => 'tally',
            ],

            // Feature toggles
            [
                'key'   => 'features.dynamic_pricing',
                'value' => '1',
                'type'  => 'bool',
                'group' => 'features',
            ],
            [
                'key'   => 'features.out_of_stock_notifications',
                'value' => '1',
                'type'  => 'bool',
                'group' => 'features',
            ],
            [
                'key'   => 'features.dark_mode',
                'value' => '1',
                'type'  => 'bool',
                'group' => 'features',
            ],
            [
                'key'   => 'features.newsletter',
                'value' => '1',
                'type'  => 'bool',
                'group' => 'features',
            ],
            [
                'key'   => 'features.wishlist',
                'value' => '1',
                'type'  => 'bool',
                'group' => 'features',
            ],
        ];

        // upsert so you can safely re-run the seeder
        DB::table('settings')->upsert(
            $settings,
            ['key'], // unique by key
            ['value', 'type', 'group']
        );
    }
}
