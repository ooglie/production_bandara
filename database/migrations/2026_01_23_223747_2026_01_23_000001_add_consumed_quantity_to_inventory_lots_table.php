<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('inventory_lots')) return;

        Schema::table('inventory_lots', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_lots', 'consumed_quantity')) {
                $table->decimal('consumed_quantity', 10, 2)->default(0)->after('received_quantity');
            }
            if (!Schema::hasColumn('inventory_lots', 'consumed_weight_kg')) {
                $table->decimal('consumed_weight_kg', 10, 3)->default(0)->after('consumed_quantity');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('inventory_lots')) return;

        Schema::table('inventory_lots', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_lots', 'consumed_quantity')) {
                $table->dropColumn('consumed_quantity');
            }
            if (Schema::hasColumn('inventory_lots', 'consumed_weight_kg')) {
                $table->dropColumn('consumed_weight_kg');
            }
        });
    }
};
