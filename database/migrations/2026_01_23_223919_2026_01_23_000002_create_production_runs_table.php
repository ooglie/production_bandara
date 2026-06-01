<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('production_runs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inventory_lot_id')->nullable()->constrained('inventory_lots')->nullOnDelete();
            $table->foreignId('inventory_piece_id')->nullable()->constrained('inventory_pieces')->nullOnDelete();

            $table->foreignId('source_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('source_product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            $table->string('process_type')->default('production'); // blocks/slices/repack/etc
            $table->decimal('consumed_quantity', 10, 2)->default(0);     // qty or kg depending on lot
            $table->decimal('consumed_weight_kg', 10, 3)->nullable();    // real kg, if known

            $table->decimal('output_total_weight_kg', 10, 3)->default(0);
            $table->decimal('waste_weight_kg', 10, 3)->default(0);

            $table->date('packed_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('batch_code', 80)->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['inventory_lot_id']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_runs');
    }
};
