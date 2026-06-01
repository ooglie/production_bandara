<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionController extends Controller
{
    public function index()
    {
        $roles = Role::query()
            ->withCount('permissions')
            ->orderBy('name')
            ->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function edit(Role $role)
    {
        $guard = config('permission.default_guard') ?? 'web';

        $matrix = config('fb_permissions.modules', []);
        $labels = config('fb_permissions.labels', []);
        $configuredExtraPermissions = (array) config('fb_permissions.extra_permissions', []);

        // Ensure all configured permissions exist so the UI reflects the canonical access map.
        $matrixPermissionNames = [];
        foreach ($matrix as $module => $actions) {
            foreach ($actions as $action) {
                $name = trim($action . ' ' . $module);
                $matrixPermissionNames[] = $name;

                Permission::firstOrCreate([
                    'name'       => $name,
                    'guard_name' => $guard,
                ]);
            }
        }

        foreach ($configuredExtraPermissions as $permissionName) {
            Permission::firstOrCreate([
                'name'       => trim((string) $permissionName),
                'guard_name' => $guard,
            ]);
        }

        $allPermissions = Permission::query()
            ->where('guard_name', $guard)
            ->orderBy('name')
            ->get();

        $extraPermissions = $allPermissions
            ->whereNotIn('name', $matrixPermissionNames)
            ->values();

        $rolePermissions = $role->permissions()->pluck('name')->toArray();

        return view('admin.roles.edit', [
            'role'                => $role,
            'matrix'              => $matrix,
            'labels'              => $labels,
            'matrixPermissionNames' => $matrixPermissionNames,
            'allPermissions'      => $allPermissions,
            'extraPermissions'    => $extraPermissions,
            'rolePermissions'     => $rolePermissions,
        ]);
    }

    public function update(Request $request, Role $role)
    {
        // Safety: keep Admin role always fully privileged (prevents lockouts)
        if (strcasecmp($role->name, 'Admin') === 0) {
            // Still allow saving, but we enforce all permissions.
            $guard = config('permission.default_guard') ?? 'web';
            $all = Permission::query()->where('guard_name', $guard)->pluck('name')->toArray();
            $role->syncPermissions($all);

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return redirect()
                ->route('admin.roles.edit', $role)
                ->with('status', 'Admin role always has all permissions (cannot be reduced).');
        }

        $guard = config('permission.default_guard') ?? 'web';

        $matrix = config('fb_permissions.modules', []);
        $matrixPermissionNames = [];

        foreach ($matrix as $module => $actions) {
            foreach ($actions as $action) {
                $matrixPermissionNames[] = trim($action . ' ' . $module);
            }
        }

        foreach ((array) config('fb_permissions.extra_permissions', []) as $permissionName) {
            Permission::firstOrCreate([
                'name' => trim((string) $permissionName),
                'guard_name' => $guard,
            ]);
        }

        $selected = $request->input('permissions', []);
        if (!is_array($selected)) {
            $selected = [];
        }

        // Normalize + keep only permissions that exist in DB for this guard
        $selected = array_values(array_unique(array_map('strval', $selected)));

        // Enforce: if "manage X" selected and "view X" exists in matrix, auto-add "view X"
        foreach ($matrix as $module => $actions) {
            $manageName = 'manage ' . $module;
            $viewName   = 'view ' . $module;

            if (in_array($manageName, $selected, true)) {
                if (in_array($viewName, $matrixPermissionNames, true) && !in_array($viewName, $selected, true)) {
                    $selected[] = $viewName;
                }
            }
        }

        // Only allow permissions that exist with correct guard
        $valid = Permission::query()
            ->where('guard_name', $guard)
            ->whereIn('name', $selected)
            ->pluck('name')
            ->toArray();

        $role->syncPermissions($valid);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.roles.edit', $role)
            ->with('status', 'Permissions updated.');
    }
}
