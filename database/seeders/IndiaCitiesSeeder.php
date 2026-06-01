<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IndiaCitiesSeeder extends Seeder
{
    public function run(): void
    {
        $seed = [
            'MH' => ['Mumbai', 'Pune', 'Nagpur', 'Nashik', 'Thane'],
            'DL' => ['New Delhi', 'Delhi'],
            'KA' => ['Bengaluru', 'Mysuru', 'Mangaluru'],
            'TN' => ['Chennai', 'Coimbatore', 'Madurai'],
            'TS' => ['Hyderabad', 'Warangal'],
            'GJ' => ['Ahmedabad', 'Surat', 'Vadodara', 'Rajkot'],
            'WB' => ['Kolkata', 'Siliguri'],
            'UP' => ['Lucknow', 'Kanpur', 'Noida', 'Varanasi'],
            'RJ' => ['Jaipur', 'Jodhpur', 'Udaipur'],
            'KL' => ['Kochi', 'Thiruvananthapuram', 'Kozhikode'],
            'GA' => ['Panaji', 'Margao'],
            'PB' => ['Ludhiana', 'Amritsar', 'Jalandhar'],
            'HR' => ['Gurugram', 'Faridabad'],
            'MP' => ['Bhopal', 'Indore'],
            'OD' => ['Bhubaneswar', 'Cuttack'],
        ];

        $now = now();

        foreach ($seed as $stateCode => $cities) {
            foreach ($cities as $i => $name) {
                DB::table('cities')->updateOrInsert(
                    ['country_code' => 'IN', 'state_code' => $stateCode, 'name' => $name],
                    [
                        'is_active'  => true,
                        'sort_order' => $i + 1,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }
    }
}
