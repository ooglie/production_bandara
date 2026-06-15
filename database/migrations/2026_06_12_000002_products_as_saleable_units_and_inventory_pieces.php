<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (! Schema::hasColumn('products', 'inventory_role')) {
                    $table->string('inventory_role', 20)->default('saleable')->after('type')->index();
                }

                if (! Schema::hasColumn('products', 'pack_type')) {
                    $table->string('pack_type', 40)->default('quantity')->after('sell_unit')->index();
                }

                if (! Schema::hasColumn('products', 'pieces_per_pack')) {
                    $table->decimal('pieces_per_pack', 12, 3)->nullable()->after('product_weight');
                }
            });
        }

        if (Schema::hasTable('inventory_pieces')) {
            Schema::table('inventory_pieces', function (Blueprint $table) {
                if (! Schema::hasColumn('inventory_pieces', 'available_weight_kg')) {
                    $table->decimal('available_weight_kg', 10, 3)->nullable()->after('weight_kg');
                }

                if (! Schema::hasColumn('inventory_pieces', 'label')) {
                    $table->string('label', 120)->nullable()->after('piece_no');
                }

                if (! Schema::hasColumn('inventory_pieces', 'notes')) {
                    $table->text('notes')->nullable()->after('sold_order_item_id');
                }
            });
        }
    }

    public function down(): void
    {
        // Non-destructive: these columns are guarded in code and are safe to leave.
    }
};
