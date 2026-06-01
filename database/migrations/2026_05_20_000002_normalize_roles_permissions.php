<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $guard = 'web';
        $now = now();

        $modules = [
            'products' => ['view', 'manage'],
            'orders' => ['view', 'manage'],
            'invoices' => ['view', 'manage'],
            'customers' => ['view', 'manage'],
            'vendors' => ['view', 'manage'],
            'coupons' => ['view', 'manage'],
            'payments' => ['view', 'manage'],
            'stores' => ['view', 'manage'],
            'tickets' => ['view', 'manage'],
            'marketing' => ['view', 'manage'],
            'content' => ['view', 'manage'],
            'rewards' => ['view', 'manage'],
            'users' => ['manage'],
            'settings' => ['manage'],
            'reports' => ['view'],
        ];

        $extraPermissions = [
            'create vendor invoice',
            'manage vendor payments',
            'manage sales',
        ];

        $permissionNames = [];
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                $permissionNames[] = trim($action.' '.$module);
            }
        }
        $permissionNames = array_values(array_unique(array_merge($permissionNames, $extraPermissions)));

        foreach ($permissionNames as $permissionName) {
            $this->ensurePermission($permissionName, $guard, $now);
        }

        $roles = ['Admin', 'Manager', 'Support', 'Accountant', 'CAAccountant', 'Stores', 'Customer'];
        foreach ($roles as $roleName) {
            $this->ensureRole($roleName, $guard, $now);
        }

        $this->mergeRoleAliases([
            'CA-Accountant' => 'CAAccountant',
            'CA Accountant' => 'CAAccountant',
            'Account' => 'Accountant',
            'admin' => 'Admin',
            'manager' => 'Manager',
            'support' => 'Support',
            'accountant' => 'Accountant',
            'stores' => 'Stores',
            'customer' => 'Customer',
        ], $guard);

        $rolePermissions = [
            'Manager' => [
                'view products', 'manage products',
                'view orders', 'manage orders', 'manage sales',
                'view invoices', 'manage invoices',
                'view customers', 'manage customers',
                'view vendors', 'manage vendors',
                'view coupons', 'manage coupons',
                'view payments', 'manage payments',
                'view stores', 'manage stores',
                'view tickets', 'manage tickets',
                'view marketing', 'manage marketing',
                'view content', 'manage content',
                'view rewards', 'manage rewards',
                'view reports',
                'create vendor invoice',
                'manage vendor payments',
            ],
            'Support' => [
                'view customers',
                'view orders',
                'view tickets', 'manage tickets',
            ],
            'Accountant' => [
                'view orders',
                'view customers',
                'view invoices', 'manage invoices',
                'view payments', 'manage payments',
                'view vendors',
                'view reports',
                'manage vendor payments',
            ],
            'CAAccountant' => [
                'view orders',
                'view customers',
                'view invoices',
                'view payments',
                'view reports',
            ],
            'Stores' => [
                'view products',
                'view vendors',
                'view stores', 'manage stores',
                'create vendor invoice',
            ],
            'Customer' => [],
        ];

        // Admin always receives every known permission, including legacy or future permissions already present.
        $rolePermissions['Admin'] = DB::table('permissions')
            ->where('guard_name', $guard)
            ->pluck('name')
            ->all();

        foreach ($rolePermissions as $roleName => $permissions) {
            $roleId = DB::table('roles')
                ->where('name', $roleName)
                ->where('guard_name', $guard)
                ->value('id');

            if (! $roleId) {
                continue;
            }

            DB::table('role_has_permissions')->where('role_id', $roleId)->delete();

            foreach ($permissions as $permissionName) {
                $permissionId = DB::table('permissions')
                    ->where('name', $permissionName)
                    ->where('guard_name', $guard)
                    ->value('id');

                if (! $permissionId) {
                    continue;
                }

                DB::table('role_has_permissions')->insertOrIgnore([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }

        Cache::forget(config('permission.cache.key', 'spatie.permission.cache'));
    }

    public function down(): void
    {
        // Intentionally no-op. This migration normalizes access-control data and
        // should not remove roles/permissions on rollback.
    }

    private function ensurePermission(string $name, string $guard, $now): void
    {
        $exists = DB::table('permissions')
            ->where('name', $name)
            ->where('guard_name', $guard)
            ->exists();

        if ($exists) {
            DB::table('permissions')
                ->where('name', $name)
                ->where('guard_name', $guard)
                ->update(['updated_at' => $now]);

            return;
        }

        DB::table('permissions')->insert([
            'name' => $name,
            'guard_name' => $guard,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensureRole(string $name, string $guard, $now): void
    {
        $exists = DB::table('roles')
            ->where('name', $name)
            ->where('guard_name', $guard)
            ->exists();

        if ($exists) {
            DB::table('roles')
                ->where('name', $name)
                ->where('guard_name', $guard)
                ->update(['updated_at' => $now]);

            return;
        }

        DB::table('roles')->insert([
            'name' => $name,
            'guard_name' => $guard,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function mergeRoleAliases(array $aliases, string $guard): void
    {
        foreach ($aliases as $alias => $canonical) {
            if ($alias === $canonical) {
                continue;
            }

            $aliasRole = DB::table('roles')->where('name', $alias)->where('guard_name', $guard)->first();
            $canonicalRole = DB::table('roles')->where('name', $canonical)->where('guard_name', $guard)->first();

            if (! $aliasRole || ! $canonicalRole || $aliasRole->id === $canonicalRole->id) {
                continue;
            }

            if (Schema::hasTable('model_has_roles')) {
                $assignments = DB::table('model_has_roles')->where('role_id', $aliasRole->id)->get();

                foreach ($assignments as $assignment) {
                    $exists = DB::table('model_has_roles')
                        ->where('role_id', $canonicalRole->id)
                        ->where('model_id', $assignment->model_id)
                        ->where('model_type', $assignment->model_type)
                        ->exists();

                    if (! $exists) {
                        DB::table('model_has_roles')
                            ->where('role_id', $aliasRole->id)
                            ->where('model_id', $assignment->model_id)
                            ->where('model_type', $assignment->model_type)
                            ->update(['role_id' => $canonicalRole->id]);
                    }
                }

                DB::table('model_has_roles')->where('role_id', $aliasRole->id)->delete();
            }

            $permissionRows = DB::table('role_has_permissions')->where('role_id', $aliasRole->id)->get();
            foreach ($permissionRows as $row) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'role_id' => $canonicalRole->id,
                    'permission_id' => $row->permission_id,
                ]);
            }

            DB::table('role_has_permissions')->where('role_id', $aliasRole->id)->delete();
            DB::table('roles')->where('id', $aliasRole->id)->delete();
        }
    }
};
