<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('sku')->unique();
            $table->string('name')->nullable();

            $table->boolean('manage_stock')->default(true);
            $table->decimal('stock_quantity', 10, 2)->nullable();
            $table->decimal('low_stock_threshold', 10, 2)->nullable();
            $table->decimal('min_order_quantity', 10, 2)->nullable();

            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
