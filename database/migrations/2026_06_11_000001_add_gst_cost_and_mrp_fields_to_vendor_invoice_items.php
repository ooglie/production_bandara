<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_invoice_items', function (Blueprint $table) {
            if (!Schema::hasColumn('vendor_invoice_items', 'hsn_code_id')) {
                $table->foreignId('hsn_code_id')
                    ->nullable()
                    ->after('product_variant_id')
                    ->constrained('hsn_codes')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('vendor_invoice_items', 'gst_rate')) {
                $table->decimal('gst_rate', 5, 2)
                    ->nullable()
                    ->after('hsn_code_id');
            }

            if (!Schema::hasColumn('vendor_invoice_items', 'entered_unit_cost')) {
                $table->decimal('entered_unit_cost', 12, 2)
                    ->nullable()
                    ->after('unit_cost');
            }

            if (!Schema::hasColumn('vendor_invoice_items', 'unit_cost_includes_gst')) {
                $table->boolean('unit_cost_includes_gst')
                    ->default(false)
                    ->after('entered_unit_cost');
            }

            if (!Schema::hasColumn('vendor_invoice_items', 'tax_amount_is_manual')) {
                $table->boolean('tax_amount_is_manual')
                    ->default(false)
                    ->after('tax_amount');
            }

            if (!Schema::hasColumn('vendor_invoice_items', 'mrp_price_incl_gst')) {
                $table->decimal('mrp_price_incl_gst', 12, 2)
                    ->nullable()
                    ->after('tax_amount_is_manual');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('vendor_invoice_items', 'hsn_code_id')) {
            try {
                Schema::table('vendor_invoice_items', function (Blueprint $table) {
                    $table->dropForeign(['hsn_code_id']);
                });
            } catch (\Throwable $e) {
                // Column may exist without a foreign key on some repaired databases.
            }
        }

        Schema::table('vendor_invoice_items', function (Blueprint $table) {
            foreach ([
                'mrp_price_incl_gst',
                'tax_amount_is_manual',
                'unit_cost_includes_gst',
                'entered_unit_cost',
                'gst_rate',
                'hsn_code_id',
            ] as $column) {
                if (Schema::hasColumn('vendor_invoice_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
