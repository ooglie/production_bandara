<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_variant_attribute_values')) {
            return;
        }

        $valueTable = Schema::hasTable('product_attribute_values')
            ? 'product_attribute_values'
            : 'attribute_values';

        $valueColumn = $valueTable === 'product_attribute_values'
            ? 'product_attribute_value_id'
            : 'attribute_value_id';

        Schema::create('product_variant_attribute_values', function (Blueprint $table) use ($valueTable, $valueColumn) {
            $table->id();

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();

            $table->unsignedBigInteger($valueColumn);

            $table->timestamps();

            $table->unique(
                ['product_variant_id', $valueColumn],
                'pvav_variant_value_unique'
            );

            $table->foreign($valueColumn)
                ->references('id')
                ->on($valueTable)
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_attribute_values');
    }
};