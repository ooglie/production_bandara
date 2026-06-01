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

        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                if (! Schema::hasColumn('order_items', 'item_weight')) {
                    $table->decimal('item_weight', 10, 3)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // No-op for imported handoff databases.
    }
};
