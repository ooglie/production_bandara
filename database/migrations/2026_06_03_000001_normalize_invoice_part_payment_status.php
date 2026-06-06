<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoices') || ! Schema::hasColumn('invoices', 'status')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            // Some earlier migrations used "partial" while the application uses
            // "part_payment". Allow both temporarily, convert rows, then remove
            // the obsolete value from the enum.
            DB::statement("ALTER TABLE `invoices` MODIFY `status` ENUM('pending','due','partial','part_payment','past_due','paid') NOT NULL DEFAULT 'pending'");
            DB::table('invoices')->where('status', 'partial')->update(['status' => 'part_payment']);
            DB::statement("ALTER TABLE `invoices` MODIFY `status` ENUM('pending','due','part_payment','past_due','paid') NOT NULL DEFAULT 'pending'");

            return;
        }

        // SQLite/testing fallback: enum values are not enforced as MySQL enums.
        DB::table('invoices')->where('status', 'partial')->update(['status' => 'part_payment']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('invoices') || ! Schema::hasColumn('invoices', 'status')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `invoices` MODIFY `status` ENUM('pending','due','partial','part_payment','past_due','paid') NOT NULL DEFAULT 'pending'");
            DB::table('invoices')->where('status', 'part_payment')->update(['status' => 'partial']);
            DB::statement("ALTER TABLE `invoices` MODIFY `status` ENUM('pending','due','partial','past_due','paid') NOT NULL DEFAULT 'pending'");

            return;
        }

        DB::table('invoices')->where('status', 'part_payment')->update(['status' => 'partial']);
    }
};
