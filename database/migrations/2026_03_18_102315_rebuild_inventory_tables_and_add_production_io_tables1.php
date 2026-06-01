<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Because these tables are empty and the old schema has cross-FKs,
         * disable FK checks, drop all inventory/production tables, then recreate.
         */
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('production_run_outputs');
        Schema::dropIfExists('production_run_inputs');
        Schema::dropIfExists('production_runs');
        Schema::dropIfExists('inventory_pieces');
        Schema::dropIfExists('inventory_lots');

        Schema::enableForeignKeyConstraints();

        /*
         * production_runs
         * Header for raw->slab, slab->slice, raw->slice_direct runs.
         */
        Schema::create('production_runs', function (Blueprint $table) {
            $table->id();

            $table->string('run_number', 60)->nullable()->unique();
            $table->date('run_date')->index();

            $table->string('run_type', 40)->index(); // raw_to_slab, slab_to_slice, raw_to_slice_direct
            $table->string('status', 20)->default('draft')->index(); // draft, completed, cancelled

            $table->json('process_flow_json')->nullable(); // records virtual slab step etc
            $table->text('notes')->nullable();

            $table->decimal('input_weight_kg', 12, 3)->nullable();
            $table->decimal('saleable_output_weight_kg', 12, 3)->nullable();
            $table->decimal('trim_weight_kg', 12, 3)->nullable();
            $table->decimal('waste_weight_kg', 12, 3)->nullable();
            $table->decimal('yield_percent', 8, 2)->nullable();

            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });

        /*
         * inventory_lots
         * Real stock lots only.
         * raw/slab/slice are physical stages.
         * saleability/repackability are controlled separately.
         */
        Schema::create('inventory_lots', function (Blueprint $table) {
            $table->id();

            $table->string('lot_code', 80)->nullable()->unique();

            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();

            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendor_invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendor_invoice_item_id')->nullable()->constrained('vendor_invoice_items')->nullOnDelete();

            $table->foreignId('production_run_id')->nullable()->constrained('production_runs')->nullOnDelete();

            // tracing
            $table->unsignedBigInteger('parent_inventory_lot_id')->nullable()->index();
            $table->unsignedBigInteger('root_inventory_lot_id')->nullable()->index();

            // stage / behavior
            $table->string('lot_stage', 20)->default('raw')->index(); // raw, slab, slice, trim, waste
            $table->string('inward_mode', 20)->nullable(); // qty / pieces
            $table->boolean('is_saleable')->default(true);
            $table->boolean('can_repack')->default(false);
            $table->string('lot_status', 20)->default('available')->index(); // available, hold, exhausted, cancelled

            // batch / dates
            $table->string('batch_code', 80)->nullable()->index();
            $table->date('mfg_date')->nullable();
            $table->date('packed_date')->nullable();
            $table->date('expiry_date')->nullable()->index();
            $table->date('received_date')->nullable();

            // balances
            $table->decimal('received_quantity', 12, 3)->default(0);
            $table->decimal('available_quantity', 12, 3)->default(0);

            $table->decimal('unit_weight_kg', 10, 3)->nullable();
            $table->decimal('total_weight_kg', 12, 3)->nullable();
            $table->decimal('available_weight_kg', 12, 3)->nullable();

            $table->unsignedInteger('piece_count')->nullable();
            $table->unsignedInteger('available_piece_count')->nullable();

            $table->decimal('pack_size_kg', 10, 3)->nullable();

            // costing snapshots
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->decimal('cost_per_kg', 12, 2)->nullable();
            $table->decimal('total_cost', 12, 2)->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['product_id', 'lot_stage']);
            $table->index(['is_saleable', 'can_repack']);
        });

        /*
         * inventory_pieces
         * Per-piece traceability for pieces mode.
         */
        Schema::create('inventory_pieces', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inventory_lot_id')->constrained('inventory_lots')->cascadeOnDelete();

            $table->unsignedInteger('piece_no');
            $table->decimal('weight_kg', 10, 3);

            $table->string('status', 20)->default('available')->index(); // available, consumed, sold, hold

            $table->foreignId('consumed_in_production_run_id')->nullable()->constrained('production_runs')->nullOnDelete();

            // keep generic for now
            $table->unsignedBigInteger('sold_order_item_id')->nullable()->index();

            $table->timestamps();

            $table->unique(['inventory_lot_id', 'piece_no']);
        });

        /*
         * production_run_inputs
         * What lots were consumed.
         */
        Schema::create('production_run_inputs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('production_run_id')->constrained('production_runs')->cascadeOnDelete();

            $table->foreignId('inventory_lot_id')->constrained('inventory_lots')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();

            $table->decimal('consumed_quantity', 12, 3)->default(0);
            $table->decimal('consumed_weight_kg', 12, 3)->default(0);
            $table->unsignedInteger('consumed_piece_count')->nullable();

            $table->decimal('unit_cost_snapshot', 12, 2)->nullable();
            $table->decimal('total_cost_snapshot', 12, 2)->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
        });

        /*
         * production_run_outputs
         * What lots/products were produced.
         */
        Schema::create('production_run_outputs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('production_run_id')->constrained('production_runs')->cascadeOnDelete();

            $table->foreignId('inventory_lot_id')->nullable()->constrained('inventory_lots')->nullOnDelete();

            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();

            $table->string('output_stage', 20)->index(); // slab, slice, trim, waste

            $table->decimal('produced_quantity', 12, 3)->default(0);
            $table->decimal('produced_weight_kg', 12, 3)->default(0);

            $table->unsignedInteger('piece_count')->nullable();
            $table->decimal('unit_weight_kg', 10, 3)->nullable();
            $table->decimal('pack_size_kg', 10, 3)->nullable();

            $table->boolean('is_saleable')->default(true);
            $table->boolean('can_repack')->default(false);
            $table->boolean('inventory_output')->default(true);

            $table->decimal('allocated_cost', 12, 2)->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('production_run_outputs');
        Schema::dropIfExists('production_run_inputs');
        Schema::dropIfExists('inventory_pieces');
        Schema::dropIfExists('inventory_lots');
        Schema::dropIfExists('production_runs');

        Schema::enableForeignKeyConstraints();
    }
};