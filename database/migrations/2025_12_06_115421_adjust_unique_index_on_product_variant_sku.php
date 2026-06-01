<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            // Drop existing unique index on sku if present
            // Adjust the index name to match your schema (often 'product_variants_sku_unique')
            $table->dropUnique('product_variants_sku_unique');

            // Add composite unique index
            $table->unique(['sku', 'deleted_at'], 'product_variants_sku_deleted_at_unique');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique('product_variants_sku_deleted_at_unique');
            $table->unique('sku', 'product_variants_sku_unique');
        });
    }
};

