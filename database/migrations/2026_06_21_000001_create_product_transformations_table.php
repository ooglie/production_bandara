<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_transformations')) {
            Schema::create('product_transformations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('source_product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('target_product_id')->constrained('products')->cascadeOnDelete();
                $table->string('transformation_type', 40)->default('repack');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['source_product_id', 'target_product_id'], 'product_transformations_source_target_unique');
                $table->index('target_product_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_transformations');
    }
};
