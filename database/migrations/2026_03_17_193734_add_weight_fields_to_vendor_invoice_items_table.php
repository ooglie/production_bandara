<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_invoice_items', function (Blueprint $table) {
            if (!Schema::hasColumn('vendor_invoice_items', 'unit_weight_kg')) {
                $table->decimal('unit_weight_kg', 10, 3)->nullable()->after('quantity');
            }

            if (!Schema::hasColumn('vendor_invoice_items', 'total_weight_kg')) {
                $table->decimal('total_weight_kg', 12, 3)->nullable()->after('unit_weight_kg');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendor_invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('vendor_invoice_items', 'total_weight_kg')) {
                $table->dropColumn('total_weight_kg');
            }

            if (Schema::hasColumn('vendor_invoice_items', 'unit_weight_kg')) {
                $table->dropColumn('unit_weight_kg');
            }
        });
    }
};