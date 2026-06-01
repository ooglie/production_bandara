<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Get credentials from .env (with sensible defaults for dev)
        $email = env('ADMIN_EMAIL', 'parag@bandara.in');
        $password = env('ADMIN_PASSWORD', 'Champagne2873!');

        // Make sure the Admin role exists (RolesAndPermissionsSeeder should have created it)
        $adminRole = Role::where('name', 'Admin')->first();

        if (! $adminRole) {
            $this->command->warn('Admin role not found. Run RolesAndPermissionsSeeder first.');
            return;
        }

        // Create or fetch the admin user
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'     => 'Parag Parulekar',
                'password' => Hash::make($password),
                'is_active'=> true,
            ]
        );

        // Attach Admin role
        if (! $user->hasRole('Admin')) {
            $user->assignRole('Admin');
        }

        $this->command->info("Admin user: {$email}");
    }
}
