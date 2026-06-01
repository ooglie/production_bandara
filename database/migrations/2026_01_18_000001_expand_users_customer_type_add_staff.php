<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'customer_type')) {
            // Nothing to do
            return;
        }

        // If any NULLs exist, normalize before altering enum/constraints.
        DB::table('users')->whereNull('customer_type')->update(['customer_type' => 'b2c']);

        // Expand enum to include 'staff'.
        // If your column is already VARCHAR, this will convert it to ENUM safely
        // as long as existing values are only b2b/b2c/staff.
        DB::statement("ALTER TABLE `users` MODIFY `customer_type` ENUM('b2c','b2b','staff') NOT NULL DEFAULT 'b2c'");

        // Optional backfill: mark staff accounts as customer_type='staff' using Spatie roles.
        // This keeps customer queries clean: customers are b2c/b2b, staff are staff.
        if (Schema::hasTable('model_has_roles') && Schema::hasTable('roles')) {
            $staffRoleNames = ['admin','manager','support','accountant'];

            $staffUserIds = DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_has_roles.model_type', 'App\\Models\\User')
                ->whereIn('roles.name', $staffRoleNames)
                ->pluck('model_has_roles.model_id')
                ->unique()
                ->values()
                ->all();

            if (!empty($staffUserIds)) {
                DB::table('users')
                    ->whereIn('id', $staffUserIds)
                    ->update(['customer_type' => 'staff']);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('users', 'customer_type')) {
            return;
        }

        // Downgrade safely: staff -> b2c (so enum shrink won't fail).
        DB::table('users')->where('customer_type', 'staff')->update(['customer_type' => 'b2c']);

        DB::statement("ALTER TABLE `users` MODIFY `customer_type` ENUM('b2c','b2b') NOT NULL DEFAULT 'b2c'");
    }
};
