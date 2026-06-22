<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_variants') && ! Schema::hasColumn('product_variants', 'customer_visibility')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->string('customer_visibility', 20)->default('all')->after('standard_b2b_min_order_quantity');
                $table->index('customer_visibility');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_variants') && Schema::hasColumn('product_variants', 'customer_visibility')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->dropIndex(['customer_visibility']);
                $table->dropColumn('customer_visibility');
            });
        }
    }
};
