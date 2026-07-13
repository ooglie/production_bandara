<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `orders` MODIFY `status` ENUM('pending_payment','processing','shipped','delivered','cancelled','payment_failed','payment_expired') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'processing'");
            DB::statement("ALTER TABLE `orders` MODIFY `payment_status` ENUM('pending','paid','failed','expired','refunded') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("UPDATE `orders` SET `status` = 'cancelled' WHERE `status` IN ('payment_failed', 'payment_expired')");
            DB::statement("UPDATE `orders` SET `status` = 'processing' WHERE `status` = 'pending_payment'");
            DB::statement("UPDATE `orders` SET `payment_status` = 'failed' WHERE `payment_status` = 'expired'");

            DB::statement("ALTER TABLE `orders` MODIFY `status` ENUM('processing','shipped','delivered','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'processing'");
            DB::statement("ALTER TABLE `orders` MODIFY `payment_status` ENUM('pending','paid','failed','refunded') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending'");
        }
    }
};
