<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $guard = 'web';
        $permissionName = 'adjust vendor invoices';
        $now = now();

        $permissionId = DB::table('permissions')
            ->where('name', $permissionName)
            ->where('guard_name', $guard)
            ->value('id');

        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => $permissionName,
                'guard_name' => $guard,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $roleIds = DB::table('roles')
            ->where('guard_name', $guard)
            ->whereIn('name', ['Admin', 'Manager', 'Accountant'])
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }

        Cache::forget(config('permission.cache.key', 'spatie.permission.cache'));
    }

    public function down(): void
    {
        // Intentionally keep the permission and role assignments on rollback.
        // Removing access-control rows can unexpectedly revoke unrelated access.
    }
};
