<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // 'pack' = price per unit/pack, 'kg' = price per kilogram
            $table->enum('pricing_unit', ['pack', 'kg'])
                  ->default('pack')
                  ->after('base_price');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            // nullable means: inherit from product.pricing_unit unless overridden
            $table->enum('pricing_unit', ['pack', 'kg'])
                  ->nullable()
                  ->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('pricing_unit');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('pricing_unit');
        });
    }
};
