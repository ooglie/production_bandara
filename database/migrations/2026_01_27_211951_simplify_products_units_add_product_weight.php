<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'product_weight')) {
                // kg (e.g. 1.850 = 1.85kg)
                $table->decimal('product_weight', 10, 3)->nullable()->after('sell_unit');
            }
        });

        // Safe backfill for products priced per kg (prevents totals becoming 0)
        DB::statement("UPDATE products SET product_weight = 1.000 WHERE product_weight IS NULL AND sell_unit = 'kg'");

        Schema::table('products', function (Blueprint $table) {
            // Drop the redundant unit fields (keep only sell_unit)
            $cols = ['sell_by', 'order_unit', 'price_unit', 'unit_label', 'order_step', 'pricing_unit'];

            foreach ($cols as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'product_weight')) {
                $table->dropColumn('product_weight');
            }

            // Optional: restore removed fields if you ever roll back
            if (!Schema::hasColumn('products', 'sell_by')) {
                $table->enum('sell_by', ['quantity', 'weight'])->default('quantity')->after('min_order_quantity');
            }
            if (!Schema::hasColumn('products', 'order_unit')) {
                $table->enum('order_unit', ['pack', 'piece', 'kg'])->default('piece')->after('sell_by');
            }
            if (!Schema::hasColumn('products', 'price_unit')) {
                $table->enum('price_unit', ['pack', 'piece', 'kg'])->default('pack')->after('order_unit');
            }
            if (!Schema::hasColumn('products', 'unit_label')) {
                $table->string('unit_label')->nullable()->after('price_unit');
            }
            if (!Schema::hasColumn('products', 'order_step')) {
                $table->decimal('order_step', 10, 2)->default(1.00)->after('sell_unit');
            }
            if (!Schema::hasColumn('products', 'pricing_unit')) {
                $table->enum('pricing_unit', ['pack', 'piece', 'kg'])->default('pack')->after('order_step');
            }
        });
    }
};
