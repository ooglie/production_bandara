<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SettingsSeeder::class,
        ]);

        $this->command?->warn(
            'AdminUserSeeder is intentionally not run automatically. '.
            'Set ADMIN_EMAIL, ADMIN_PASSWORD and ADMIN_NAME, then run it explicitly.'
        );
    }
}
