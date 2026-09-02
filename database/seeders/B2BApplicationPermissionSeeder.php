<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Throwable;

class B2BApplicationPermissionSeeder extends Seeder
{
    public function run(): void
    {
        if (! class_exists(\Spatie\Permission\Models\Permission::class)
            || ! class_exists(\Spatie\Permission\Models\Role::class)
            || ! Schema::hasTable(config('permission.table_names.permissions', 'permissions'))
            || ! Schema::hasTable(config('permission.table_names.roles', 'roles'))) {
            $this->command?->warn('Spatie permission tables/classes were not found. Role fallback remains active.');
            return;
        }

        $guard = config('auth.defaults.guard', 'web');
        $permissions = array_values(array_filter((array) config('b2b_application.permissions', []), 'is_string'));

        foreach ($permissions as $permissionName) {
            \Spatie\Permission\Models\Permission::findOrCreate($permissionName, $guard);
        }

        foreach ((array) config('b2b_application.admin_roles', ['Admin', 'Manager']) as $roleName) {
            try {
                $role = \Spatie\Permission\Models\Role::findByName($roleName, $guard);
                $role->givePermissionTo($permissions);
            } catch (Throwable $exception) {
                $this->command?->warn("Role {$roleName} was not found: {$exception->getMessage()}");
            }
        }

        if (class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
}
