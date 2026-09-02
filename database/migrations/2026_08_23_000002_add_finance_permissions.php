<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = [
        'view finance summary',
        'view business expenses',
        'manage business expenses',
        'post business expenses',
        'manage expense settings',
        'view salary aggregate',
        'view salary records',
        'manage salary records',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $all = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', self::PERMISSIONS)
            ->get();

        foreach (['Admin', 'Accountant'] as $roleName) {
            $role = Role::query()
                ->where('guard_name', 'web')
                ->where('name', $roleName)
                ->first();

            $role?->givePermissionTo($all);
        }

        $manager = Role::query()
            ->where('guard_name', 'web')
            ->where('name', 'Manager')
            ->first();

        $manager?->givePermissionTo([
            'view finance summary',
            'view business expenses',
            'manage business expenses',
            'view salary aggregate',
        ]);

        // CAAccountant is intentionally not granted finance permissions here.
        // Its read-only access is enabled only when an administrator explicitly
        // assigns one or more of the new view permissions.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', self::PERMISSIONS)
            ->get();

        foreach ($permissions as $permission) {
            $permission->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
