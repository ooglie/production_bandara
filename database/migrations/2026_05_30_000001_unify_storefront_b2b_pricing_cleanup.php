<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (! Schema::hasColumn('products', 'standard_b2b_price')) {
                    $table->decimal('standard_b2b_price', 12, 2)->nullable()->after('special_price');
                }
                if (! Schema::hasColumn('products', 'standard_b2b_min_order_quantity')) {
                    $table->decimal('standard_b2b_min_order_quantity', 12, 3)->nullable()->after('standard_b2b_price');
                }
                if (! Schema::hasColumn('products', 'special_audience')) {
                    $table->string('special_audience', 20)->default('b2c')->after('is_special')->index();
                }
            });
        }

        if (Schema::hasTable('product_variants')) {
            Schema::table('product_variants', function (Blueprint $table) {
                if (! Schema::hasColumn('product_variants', 'standard_b2b_price')) {
                    $table->decimal('standard_b2b_price', 12, 2)->nullable()->after('price');
                }
                if (! Schema::hasColumn('product_variants', 'standard_b2b_min_order_quantity')) {
                    $table->decimal('standard_b2b_min_order_quantity', 12, 3)->nullable()->after('standard_b2b_price');
                }
            });
        }

        if (Schema::hasTable('product_sell_units')) {
            Schema::table('product_sell_units', function (Blueprint $table) {
                if (! Schema::hasColumn('product_sell_units', 'standard_b2b_price')) {
                    $table->decimal('standard_b2b_price', 12, 2)->nullable()->after('weight_per_unit_kg');
                }
                if (! Schema::hasColumn('product_sell_units', 'standard_b2b_min_order_quantity')) {
                    $table->decimal('standard_b2b_min_order_quantity', 12, 3)->nullable()->after('standard_b2b_price');
                }
            });
        }

        // The temporary B2B piece/kg request allocation module is no longer part
        // of the normal unified-storefront ordering flow. Keep product access
        // requests (b2b_product_requests), but remove the obsolete order request tables.
        Schema::dropIfExists('b2b_order_item_allocations');
        Schema::dropIfExists('b2b_order_request_items');
        Schema::dropIfExists('b2b_order_requests');
    }

    public function down(): void
    {
        // Non-destructive by design. Pricing columns are safe to leave in place,
        // and obsolete request tables are intentionally not recreated.
    }
};
