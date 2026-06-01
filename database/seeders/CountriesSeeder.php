<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use League\ISO3166\ISO3166;

class CountriesSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [];
        foreach ((new ISO3166())->all() as $c) {
            $rows[] = [
                'code' => strtoupper($c['alpha2']),
                'name' => $c['name'],
            ];
        }

        // No FK constraints, safe to wipe & refill
        DB::table('countries')->delete();

        // Upsert keeps it safe even if table isn't empty
        DB::table('countries')->upsert($rows, ['code'], ['name']);
    }
}
