<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['order_items', 'invoice_items'] as $lineTable) {
            if (! Schema::hasTable($lineTable)) {
                continue;
            }

            if (! Schema::hasColumn($lineTable, 'item_weight')) {
                Schema::table($lineTable, function (Blueprint $table) {
                    $table->decimal('item_weight', 10, 3)->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        // No-op for imported handoff databases.
    }
};
