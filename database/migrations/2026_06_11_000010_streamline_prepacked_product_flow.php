<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_sell_units')) {
            Schema::table('product_sell_units', function (Blueprint $table) {
                if (! Schema::hasColumn('product_sell_units', 'sale_type')) {
                    $table->string('sale_type', 40)->default('fixed_piece_pack')->after('pricing_unit')->index();
                }
                if (! Schema::hasColumn('product_sell_units', 'base_price')) {
                    $table->decimal('base_price', 12, 2)->nullable()->after('weight_per_unit_kg');
                }
                if (! Schema::hasColumn('product_sell_units', 'mrp_price')) {
                    $table->decimal('mrp_price', 12, 2)->nullable()->after('base_price');
                }
                if (! Schema::hasColumn('product_sell_units', 'b2c_price_includes_gst')) {
                    $table->boolean('b2c_price_includes_gst')->default(true)->after('mrp_price');
                }
            });
        }

        if (Schema::hasTable('vendor_invoice_items')) {
            $this->modifyColumnIfMySql('vendor_invoice_items', 'quantity', 'DECIMAL(12,3) NOT NULL');
            $this->modifyColumnIfMySql('vendor_invoice_items', 'unit_weight_kg', 'DECIMAL(10,3) NULL');
            $this->modifyColumnIfMySql('vendor_invoice_items', 'total_weight_kg', 'DECIMAL(12,3) NULL');

            Schema::table('vendor_invoice_items', function (Blueprint $table) {
                if (! Schema::hasColumn('vendor_invoice_items', 'product_sell_unit_id')) {
                    $table->unsignedBigInteger('product_sell_unit_id')->nullable()->after('product_variant_id')->index();
                }
                if (! Schema::hasColumn('vendor_invoice_items', 'receipt_type')) {
                    $table->string('receipt_type', 40)->nullable()->after('product_sell_unit_id')->index();
                }
                if (! Schema::hasColumn('vendor_invoice_items', 'unit_cost_includes_gst')) {
                    $table->boolean('unit_cost_includes_gst')->default(false)->after('unit_cost');
                }
                if (! Schema::hasColumn('vendor_invoice_items', 'tax_manual')) {
                    $table->boolean('tax_manual')->default(false)->after('unit_cost_includes_gst');
                }
                if (! Schema::hasColumn('vendor_invoice_items', 'hsn_code_id')) {
                    $table->unsignedBigInteger('hsn_code_id')->nullable()->after('tax_manual')->index();
                }
                if (! Schema::hasColumn('vendor_invoice_items', 'gst_rate')) {
                    $table->decimal('gst_rate', 5, 2)->nullable()->after('hsn_code_id');
                }
                if (! Schema::hasColumn('vendor_invoice_items', 'mrp_incl_gst')) {
                    $table->decimal('mrp_incl_gst', 12, 2)->nullable()->after('gst_rate');
                }
            });
        }

        if (Schema::hasTable('inventory_packs')) {
            $this->modifyColumnIfMySql('inventory_packs', 'production_run_id', 'BIGINT UNSIGNED NULL');
        }

    }

    public function down(): void
    {
        // Non-destructive. These columns are safe to leave in place and are guarded in code.
    }

    private function modifyColumnIfMySql(string $table, string $column, string $definition): void
    {
        if (! Schema::hasColumn($table, $column)) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` {$definition}");
    }
};
