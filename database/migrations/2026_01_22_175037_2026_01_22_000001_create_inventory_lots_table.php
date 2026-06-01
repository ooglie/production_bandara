<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_lots', function (Blueprint $table) {
            $table->id();

            // Link to where it came from (Vendor invoice)
            $table->foreignId('vendor_invoice_id')
                ->nullable()
                ->constrained('vendor_invoices')
                ->nullOnDelete();

            $table->foreignId('vendor_id')
                ->nullable()
                ->constrained('vendors')
                ->nullOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('product_variant_id')
                ->nullable()
                ->constrained('product_variants')
                ->nullOnDelete();

            // Batch + date metadata
            $table->string('batch_code', 80)->nullable()->index();
            $table->date('mfg_date')->nullable();
            $table->date('packed_date')->nullable();
            $table->date('expiry_date')->nullable()->index();

            // Receiving info (does NOT change your existing stock system)
            $table->date('received_date')->nullable()->index();

            // "qty" for normal items, "pieces" if you want piece weights
            $table->string('inward_mode', 20)->default('qty'); // qty | pieces

            // Quantity that was received (same meaning as your vendor invoice quantity)
            $table->decimal('received_quantity', 10, 2)->default(0);

            // Optional total weight captured (kg)
            $table->decimal('total_weight_kg', 10, 3)->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['product_id', 'product_variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_lots');
    }
};