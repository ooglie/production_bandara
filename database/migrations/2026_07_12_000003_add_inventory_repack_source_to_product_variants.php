<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_variants') || Schema::hasColumn('product_variants', 'inventory_can_repack')) {
            return;
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $column = $table->boolean('inventory_can_repack')->default(false);

            if (Schema::hasColumn('product_variants', 'customer_visibility')) {
                $column->after('customer_visibility');
            }

            $column->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_variants') || ! Schema::hasColumn('product_variants', 'inventory_can_repack')) {
            return;
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('inventory_can_repack');
        });
    }
};
