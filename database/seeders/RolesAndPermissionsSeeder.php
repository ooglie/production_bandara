<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guardName = config('fb_permissions.guard', config('permission.default_guard', 'web')) ?: 'web';

        $permissionNames = $this->canonicalPermissionNames();

        foreach ($permissionNames as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => $guardName,
            ]);
        }

        $roles = array_keys((array) config('fb_permissions.roles', []));
        foreach ($roles as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => $guardName,
            ]);
        }

        $this->mergeRoleAliases($guardName);

        $allPermissions = Permission::query()
            ->where('guard_name', $guardName)
            ->pluck('name')
            ->all();

        foreach ((array) config('fb_permissions.role_permissions', []) as $roleName => $permissions) {
            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', $guardName)
                ->first();

            if (! $role) {
                continue;
            }

            $role->syncPermissions(in_array('*', (array) $permissions, true) ? $allPermissions : $permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function canonicalPermissionNames(): array
    {
        $names = [];

        foreach ((array) config('fb_permissions.modules', []) as $module => $actions) {
            foreach ((array) $actions as $action) {
                $names[] = trim($action . ' ' . $module);
            }
        }

        foreach ((array) config('fb_permissions.extra_permissions', []) as $permissionName) {
            $names[] = trim((string) $permissionName);
        }

        return array_values(array_unique(array_filter($names)));
    }

    private function mergeRoleAliases(string $guardName): void
    {
        foreach ((array) config('fb_permissions.role_aliases', []) as $alias => $canonical) {
            if ($alias === $canonical) {
                continue;
            }

            $aliasRole = Role::query()->where('name', $alias)->where('guard_name', $guardName)->first();
            $canonicalRole = Role::query()->firstOrCreate([
                'name' => $canonical,
                'guard_name' => $guardName,
            ]);

            if (! $aliasRole || $aliasRole->id === $canonicalRole->id) {
                continue;
            }

            // Move direct user role assignments and role permissions to the canonical role.
            $modelKey = config('permission.column_names.model_morph_key', 'model_id') ?: 'model_id';
            $rolePivotKey = config('permission.column_names.role_pivot_key') ?: 'role_id';

            $assignments = \DB::table(config('permission.table_names.model_has_roles', 'model_has_roles'))
                ->where($rolePivotKey, $aliasRole->id)
                ->get();

            foreach ($assignments as $assignment) {
                $exists = \DB::table(config('permission.table_names.model_has_roles', 'model_has_roles'))
                    ->where($rolePivotKey, $canonicalRole->id)
                    ->where($modelKey, $assignment->{$modelKey})
                    ->where('model_type', $assignment->model_type)
                    ->exists();

                if (! $exists) {
                    \DB::table(config('permission.table_names.model_has_roles', 'model_has_roles'))
                        ->where($rolePivotKey, $aliasRole->id)
                        ->where($modelKey, $assignment->{$modelKey})
                        ->where('model_type', $assignment->model_type)
                        ->update([$rolePivotKey => $canonicalRole->id]);
                }
            }

            \DB::table(config('permission.table_names.model_has_roles', 'model_has_roles'))
                ->where($rolePivotKey, $aliasRole->id)
                ->delete();

            $permissionPivotKey = config('permission.column_names.permission_pivot_key') ?: 'permission_id';
            $rolePermissionRows = \DB::table(config('permission.table_names.role_has_permissions', 'role_has_permissions'))
                ->where($rolePivotKey, $aliasRole->id)
                ->get();

            foreach ($rolePermissionRows as $row) {
                $exists = \DB::table(config('permission.table_names.role_has_permissions', 'role_has_permissions'))
                    ->where($rolePivotKey, $canonicalRole->id)
                    ->where($permissionPivotKey, $row->{$permissionPivotKey})
                    ->exists();

                if (! $exists) {
                    \DB::table(config('permission.table_names.role_has_permissions', 'role_has_permissions'))
                        ->insert([
                            $rolePivotKey => $canonicalRole->id,
                            $permissionPivotKey => $row->{$permissionPivotKey},
                        ]);
                }
            }

            \DB::table(config('permission.table_names.role_has_permissions', 'role_has_permissions'))
                ->where($rolePivotKey, $aliasRole->id)
                ->delete();

            $aliasRole->delete();
        }
    }
}
