<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\B2BApplicationPermissionSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Throwable;

class InstallB2BApplicationPermissions extends Command
{
    protected $signature = 'bandara:b2b-applications:permissions {--remove : Remove module permissions instead of creating them}';
    protected $description = 'Create, assign or remove permissions for the B2B application review module.';

    public function handle(): int
    {
        if ($this->option('remove')) {
            return $this->removePermissions();
        }

        $this->call('db:seed', [
            '--class' => B2BApplicationPermissionSeeder::class,
            '--force' => true,
        ]);
        $this->components->info('B2B application permissions processed.');

        return self::SUCCESS;
    }

    private function removePermissions(): int
    {
        if (! class_exists(\Spatie\Permission\Models\Permission::class)
            || ! Schema::hasTable(config('permission.table_names.permissions', 'permissions'))) {
            $this->components->warn('Spatie permissions were not present; nothing to remove.');
            return self::SUCCESS;
        }

        $guard = config('auth.defaults.guard', 'web');
        foreach (array_values(array_filter((array) config('b2b_application.permissions', []), 'is_string')) as $permissionName) {
            try {
                $permission = \Spatie\Permission\Models\Permission::findByName($permissionName, $guard);
                $permission->delete();
            } catch (Throwable) {
                // Already absent.
            }
        }

        if (class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }

        $this->components->info('B2B application permissions removed.');
        return self::SUCCESS;
    }
}
