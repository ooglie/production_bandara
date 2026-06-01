<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('order_unit', ['unit', 'kg'])
                ->default('unit')
                ->after('type');

            $table->enum('price_unit', ['unit', 'kg'])
                ->default('unit')
                ->after('order_unit');

            // This is just for display when order_unit is "unit"
            // Examples: "pack", "piece", "box"
            $table->string('unit_label')
                ->nullable()
                ->after('price_unit');

            // For weight ordering UI (optional, but useful)
            // ex: 0.25kg increments
            $table->decimal('order_step', 10, 3)
                ->default(1)
                ->after('min_order_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['order_unit', 'price_unit', 'unit_label', 'order_step']);
        });
    }
};
