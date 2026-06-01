<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (! Schema::hasColumn('products', 'sell_unit')) {
                    $table->string('sell_unit', 20)->default('piece');
                }
                if (! Schema::hasColumn('products', 'product_weight')) {
                    $table->decimal('product_weight', 10, 3)->nullable();
                }
            });
        }

        foreach (['cart_items', 'order_items', 'invoice_items'] as $lineTable) {
            if (! Schema::hasTable($lineTable)) {
                continue;
            }

            Schema::table($lineTable, function (Blueprint $table) use ($lineTable) {
                if (! Schema::hasColumn($lineTable, 'item_weight')) {
                    $table->decimal('item_weight', 10, 3)->nullable();
                }
                if (in_array($lineTable, ['order_items', 'invoice_items'], true)
                    && ! Schema::hasColumn($lineTable, 'pricing_unit')) {
                    $table->string('pricing_unit', 20)->default('pack');
                }
            });
        }
    }

    public function down(): void
    {
        // No-op for imported handoff databases.
    }
};
