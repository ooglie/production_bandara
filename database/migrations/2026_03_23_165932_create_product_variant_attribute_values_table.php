<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
// sxuse RuntimeException;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_variant_attribute_values')) {
            return;
        }

        $valueTable = null;
        $valueForeignKey = null;

        if (Schema::hasTable('product_attribute_values')) {
            $valueTable = 'product_attribute_values';
            $valueForeignKey = 'product_attribute_value_id';
        } elseif (Schema::hasTable('attribute_values')) {
            $valueTable = 'attribute_values';
            $valueForeignKey = 'attribute_value_id';
        }

        if (! $valueTable || ! $valueForeignKey) {
            throw new RuntimeException(
                'Neither product_attribute_values nor attribute_values table exists. Create the attribute values table before this pivot table.'
            );
        }

        Schema::create('product_variant_attribute_values', function (Blueprint $table) use ($valueTable, $valueForeignKey) {
            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();

            $table->unsignedBigInteger($valueForeignKey);

            $table->foreign($valueForeignKey)
                ->references('id')
                ->on($valueTable)
                ->cascadeOnDelete();

            $table->unique(
                ['product_variant_id', $valueForeignKey],
                'pvav_variant_value_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_attribute_values');
    }
};