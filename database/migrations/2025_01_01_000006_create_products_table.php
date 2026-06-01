<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->nullable();

            $table->enum('type', ['simple', 'variable'])->default('simple');

            $table->string('short_description')->nullable();
            $table->longText('description')->nullable();

            $table->string('primary_image')->nullable();

            $table->boolean('manage_stock')->default(false);
            $table->decimal('stock_quantity', 10, 2)->nullable();
            $table->decimal('low_stock_threshold', 10, 2)->nullable();
            $table->decimal('min_order_quantity', 10, 2)->nullable();

            $table->decimal('base_price', 10, 2)->default(0);
            $table->boolean('dynamic_pricing_enabled')->default(false);

            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);

            $table->foreignId('vendor_id')
                ->nullable()
                ->constrained('vendors')
                ->nullOnDelete();

            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            $table->foreignId('created_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
