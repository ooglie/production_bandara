<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inventory_lots', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_lots', 'product_sell_unit_id')) {
                $table->unsignedBigInteger('product_sell_unit_id')->nullable()->after('product_variant_id')->index();
            }
            if (! Schema::hasColumn('inventory_lots', 'pack_count')) {
                $table->unsignedInteger('pack_count')->nullable()->after('available_piece_count');
            }
            if (! Schema::hasColumn('inventory_lots', 'available_pack_count')) {
                $table->unsignedInteger('available_pack_count')->nullable()->after('pack_count');
            }
            if (! Schema::hasColumn('inventory_lots', 'pieces_per_pack')) {
                $table->decimal('pieces_per_pack', 12, 3)->nullable()->after('available_pack_count');
            }
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_movements', 'product_sell_unit_id')) {
                $table->unsignedBigInteger('product_sell_unit_id')->nullable()->after('product_variant_id')->index();
            }
        });
    }

    public function down(): void
    {
        // Non-destructive. These fields are safe to leave in place.
    }
};
