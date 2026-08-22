<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_invoice_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_invoice_id')
                ->constrained('vendor_invoices')
                ->cascadeOnDelete();

            $table->string('adjustment_number', 60)->unique();
            $table->string('type', 40)->index();
            $table->string('direction', 10)->index(); // credit, debit, neutral
            $table->string('status', 20)->default('draft')->index(); // draft, posted

            $table->string('supplier_document_number', 120)->nullable()->index();
            $table->date('supplier_document_date')->nullable();
            $table->string('reason', 500);
            $table->text('notes')->nullable();

            $table->decimal('subtotal_delta', 14, 2)->default(0);
            $table->decimal('tax_delta', 14, 2)->default(0);
            $table->decimal('total_delta', 14, 2)->default(0);
            $table->boolean('affects_stock')->default(false);

            $table->foreignId('reverses_adjustment_id')
                ->nullable()
                ->constrained('vendor_invoice_adjustments')
                ->nullOnDelete();
            $table->json('meta')->nullable();

            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();

            $table->timestamps();

            $table->index(['vendor_invoice_id', 'status']);
            $table->index(['vendor_invoice_id', 'type']);
        });

        Schema::create('vendor_invoice_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_invoice_adjustment_id');
            $table->foreign('vendor_invoice_adjustment_id', 'via_items_adjustment_fk')
                ->references('id')
                ->on('vendor_invoice_adjustments')
                ->cascadeOnDelete();
            $table->foreignId('vendor_invoice_item_id')
                ->nullable()
                ->constrained('vendor_invoice_items')
                ->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('inventory_lot_id')->nullable()->constrained('inventory_lots')->nullOnDelete();

            $table->decimal('quantity_delta', 12, 3)->nullable();
            $table->decimal('weight_delta_kg', 12, 3)->nullable();
            $table->integer('piece_count_delta')->nullable();

            $table->decimal('original_unit_cost', 14, 2)->nullable();
            $table->decimal('revised_unit_cost', 14, 2)->nullable();
            $table->decimal('subtotal_delta', 14, 2)->default(0);
            $table->decimal('tax_delta', 14, 2)->default(0);
            $table->decimal('total_delta', 14, 2)->default(0);
            $table->boolean('affects_stock')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['vendor_invoice_adjustment_id', 'vendor_invoice_item_id'], 'via_item_adjustment_invoice_item_idx');
        });

        Schema::create('vendor_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_invoice_id')
                ->constrained('vendor_invoices')
                ->cascadeOnDelete();

            $table->string('return_number', 60)->unique();
            $table->date('return_date');
            $table->string('status', 30)->default('draft')->index(); // draft, credit_pending, credited
            $table->string('reference_number', 120)->nullable();
            $table->string('reason', 500);
            $table->text('notes')->nullable();

            $table->decimal('expected_subtotal', 14, 2)->default(0);
            $table->decimal('expected_tax', 14, 2)->default(0);
            $table->decimal('expected_total', 14, 2)->default(0);

            $table->boolean('credit_note_received')->default(false);
            $table->string('supplier_credit_note_number', 120)->nullable()->index();
            $table->date('supplier_credit_note_date')->nullable();
            $table->foreignId('supplier_credit_adjustment_id')
                ->nullable()
                ->constrained('vendor_invoice_adjustments')
                ->nullOnDelete();

            $table->json('meta')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['vendor_invoice_id', 'status']);
        });

        Schema::create('vendor_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_return_id')
                ->constrained('vendor_returns')
                ->cascadeOnDelete();
            $table->foreignId('vendor_invoice_item_id')
                ->constrained('vendor_invoice_items')
                ->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('inventory_lot_id')->nullable()->constrained('inventory_lots')->nullOnDelete();

            $table->string('return_mode', 30); // pieces, packs, weight, quantity
            $table->decimal('quantity', 12, 3)->default(0);
            $table->decimal('weight_kg', 12, 3)->default(0);
            $table->unsignedInteger('piece_count')->default(0);

            $table->decimal('subtotal_amount', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);

            $table->json('inventory_piece_ids')->nullable();
            $table->json('inventory_pack_ids')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['vendor_return_id', 'vendor_invoice_item_id'], 'vendor_return_item_invoice_item_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_return_items');
        Schema::dropIfExists('vendor_returns');
        Schema::dropIfExists('vendor_invoice_adjustment_items');
        Schema::dropIfExists('vendor_invoice_adjustments');
    }
};
