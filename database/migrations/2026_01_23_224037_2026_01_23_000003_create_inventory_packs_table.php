<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_packs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('production_run_id')->constrained('production_runs')->cascadeOnDelete();

            $table->foreignId('source_inventory_lot_id')->nullable()->constrained('inventory_lots')->nullOnDelete();
            $table->foreignId('source_inventory_piece_id')->nullable()->constrained('inventory_pieces')->nullOnDelete();

            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            $table->decimal('sold_weight_kg', 10, 3)->nullable();
            $table->decimal('actual_weight_kg', 10, 3)->nullable();

            $table->date('packed_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('batch_code', 80)->nullable();

            $table->string('status', 30)->default('available'); // available/reserved/sold/wasted

            // Future-proof for cart reserve TTL (not used now)
            $table->dateTime('reserved_until')->nullable();

            $table->timestamps();

            $table->index(['product_id', 'product_variant_id']);
            $table->index(['status']);
            $table->index(['expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_packs');
    }
};