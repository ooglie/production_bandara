<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_product_prices', function (Blueprint $table) {
            $table->id();

            // B2B customer
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            // Optional variant-level pricing override
            $table->foreignId('product_variant_id')
                ->nullable()
                ->constrained('product_variants')
                ->nullOnDelete();

            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('INR');

            // Validity window (nullable = always valid)
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();

            $table->boolean('is_active')->default(true);

            // Track who set the rate (admin/manager)
            $table->foreignId('created_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['user_id', 'product_id', 'product_variant_id', 'is_active'], 'cpp_lookup');
            $table->index(['valid_from', 'valid_to'], 'cpp_validity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_product_prices');
    }
};
