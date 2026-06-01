<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_customer_products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            // Per-B2B-customer MOQ for this product.
            // Default is 1 as requested (independent from products.min_order_quantity).
            $table->decimal('min_order_quantity', 10, 2)->default(1);

            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(['user_id', 'product_id'], 'b2b_user_product_unique');
            $table->index(['user_id', 'is_active'], 'b2b_user_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_customer_products');
    }
};
