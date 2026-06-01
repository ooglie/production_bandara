<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_collection_product', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_collection_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_featured')->default(false);

            $table->timestamps();

            $table->unique(
                ['product_collection_id', 'product_id'],
                'pcp_collection_product_unique' // custom short name
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_collection_product');
    }
};