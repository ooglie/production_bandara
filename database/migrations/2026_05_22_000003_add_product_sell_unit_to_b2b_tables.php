<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('b2b_customer_products') && ! Schema::hasColumn('b2b_customer_products', 'product_sell_unit_id')) {
            Schema::table('b2b_customer_products', function (Blueprint $table) {
                $table->foreignId('product_sell_unit_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('product_sell_units')
                    ->nullOnDelete();

                $table->index(['user_id', 'product_id', 'product_sell_unit_id', 'is_active'], 'b2b_customer_product_unit_active_idx');
            });
        }

        if (Schema::hasTable('customer_product_prices') && ! Schema::hasColumn('customer_product_prices', 'product_sell_unit_id')) {
            Schema::table('customer_product_prices', function (Blueprint $table) {
                $table->foreignId('product_sell_unit_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('product_sell_units')
                    ->nullOnDelete();

                $table->index(['user_id', 'product_id', 'product_sell_unit_id', 'is_active'], 'customer_prices_sell_unit_active_idx');
            });
        }

        if (Schema::hasTable('b2b_product_requests') && ! Schema::hasColumn('b2b_product_requests', 'product_sell_unit_id')) {
            Schema::table('b2b_product_requests', function (Blueprint $table) {
                $table->foreignId('product_sell_unit_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('product_sell_units')
                    ->nullOnDelete();

                $table->index(['user_id', 'product_id', 'product_sell_unit_id', 'status'], 'b2b_product_requests_sell_unit_status_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('b2b_product_requests') && Schema::hasColumn('b2b_product_requests', 'product_sell_unit_id')) {
            Schema::table('b2b_product_requests', function (Blueprint $table) {
                $table->dropIndex('b2b_product_requests_sell_unit_status_idx');
                $table->dropConstrainedForeignId('product_sell_unit_id');
            });
        }

        if (Schema::hasTable('customer_product_prices') && Schema::hasColumn('customer_product_prices', 'product_sell_unit_id')) {
            Schema::table('customer_product_prices', function (Blueprint $table) {
                $table->dropIndex('customer_prices_sell_unit_active_idx');
                $table->dropConstrainedForeignId('product_sell_unit_id');
            });
        }

        if (Schema::hasTable('b2b_customer_products') && Schema::hasColumn('b2b_customer_products', 'product_sell_unit_id')) {
            Schema::table('b2b_customer_products', function (Blueprint $table) {
                $table->dropIndex('b2b_customer_product_unit_active_idx');
                $table->dropConstrainedForeignId('product_sell_unit_id');
            });
        }
    }
};
