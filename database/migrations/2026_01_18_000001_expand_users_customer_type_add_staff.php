<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'customer_type')) {
            return;
        }

        DB::table('users')->whereNull('customer_type')->update(['customer_type' => 'b2c']);

        // MySQL/MariaDB require an explicit enum expansion. Fresh SQLite test
        // databases already receive the complete enum in the earlier migration,
        // and SQLite does not support MySQL's MODIFY syntax.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE `users` MODIFY `customer_type` ENUM('b2c','b2b','staff') NOT NULL DEFAULT 'b2c'");
        }

        if (Schema::hasTable('model_has_roles') && Schema::hasTable('roles')) {
            $staffRoleNames = [
                'admin', 'Admin',
                'manager', 'Manager',
                'support', 'Support',
                'accountant', 'Accountant',
                'caaccountant', 'CAAccountant',
                'stores', 'Stores',
                'deliveryboy', 'DeliveryBoy',
            ];

            $staffUserIds = DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_has_roles.model_type', 'App\\Models\\User')
                ->whereIn('roles.name', $staffRoleNames)
                ->pluck('model_has_roles.model_id')
                ->unique()
                ->values()
                ->all();

            if ($staffUserIds !== []) {
                DB::table('users')
                    ->whereIn('id', $staffUserIds)
                    ->update(['customer_type' => 'staff']);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'customer_type')) {
            return;
        }

        DB::table('users')->where('customer_type', 'staff')->update(['customer_type' => 'b2c']);

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE `users` MODIFY `customer_type` ENUM('b2c','b2b') NOT NULL DEFAULT 'b2c'");
        }
    }
};
