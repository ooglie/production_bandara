<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('inventory_packs')) {
            return;
        }

        Schema::table('inventory_packs', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_packs', 'sold_order_id')) {
                $table->unsignedBigInteger('sold_order_id')->nullable()->index();
            }

            if (! Schema::hasColumn('inventory_packs', 'sold_order_item_id')) {
                $table->unsignedBigInteger('sold_order_item_id')->nullable()->index();
            }

            if (! Schema::hasColumn('inventory_packs', 'sold_at')) {
                $table->dateTime('sold_at')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        // No-op for imported/handoff databases. These columns are additive
        // sale-audit columns and should be kept once pack sales are live.
    }
};
