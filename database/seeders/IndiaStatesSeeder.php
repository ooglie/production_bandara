<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IndiaStatesSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // States
            ['code' => 'AP', 'name' => 'Andhra Pradesh'],
            ['code' => 'AR', 'name' => 'Arunachal Pradesh'],
            ['code' => 'AS', 'name' => 'Assam'],
            ['code' => 'BR', 'name' => 'Bihar'],
            ['code' => 'CG', 'name' => 'Chhattisgarh'],
            ['code' => 'GA', 'name' => 'Goa'],
            ['code' => 'GJ', 'name' => 'Gujarat'],
            ['code' => 'HR', 'name' => 'Haryana'],
            ['code' => 'HP', 'name' => 'Himachal Pradesh'],
            ['code' => 'JH', 'name' => 'Jharkhand'],
            ['code' => 'KA', 'name' => 'Karnataka'],
            ['code' => 'KL', 'name' => 'Kerala'],
            ['code' => 'MP', 'name' => 'Madhya Pradesh'],
            ['code' => 'MH', 'name' => 'Maharashtra'],
            ['code' => 'MN', 'name' => 'Manipur'],
            ['code' => 'ML', 'name' => 'Meghalaya'],
            ['code' => 'MZ', 'name' => 'Mizoram'],
            ['code' => 'NL', 'name' => 'Nagaland'],
            ['code' => 'OD', 'name' => 'Odisha'],
            ['code' => 'PB', 'name' => 'Punjab'],
            ['code' => 'RJ', 'name' => 'Rajasthan'],
            ['code' => 'SK', 'name' => 'Sikkim'],
            ['code' => 'TN', 'name' => 'Tamil Nadu'],
            ['code' => 'TS', 'name' => 'Telangana'],
            ['code' => 'TR', 'name' => 'Tripura'],
            ['code' => 'UP', 'name' => 'Uttar Pradesh'],
            ['code' => 'UK', 'name' => 'Uttarakhand'],
            ['code' => 'WB', 'name' => 'West Bengal'],

            // Union Territories
            ['code' => 'AN', 'name' => 'Andaman and Nicobar Islands'],
            ['code' => 'CH', 'name' => 'Chandigarh'],
            ['code' => 'DH', 'name' => 'Dadra and Nagar Haveli and Daman and Diu'],
            ['code' => 'DL', 'name' => 'Delhi'],
            ['code' => 'JK', 'name' => 'Jammu and Kashmir'],
            ['code' => 'LA', 'name' => 'Ladakh'],
            ['code' => 'LD', 'name' => 'Lakshadweep'],
            ['code' => 'PY', 'name' => 'Puducherry'],
        ];

        $now = now();

        // Upsert by (country_code, code)
        foreach ($rows as $i => $r) {
            DB::table('states')->updateOrInsert(
                ['country_code' => 'IN', 'code' => $r['code']],
                [
                    'name'       => $r['name'],
                    'is_active'  => true,
                    'sort_order' => $i + 1,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
}
