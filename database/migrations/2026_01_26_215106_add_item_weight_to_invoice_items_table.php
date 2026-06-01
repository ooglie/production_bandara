<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('invoice_items')) {
            return;
        }

        Schema::table('invoice_items', function (Blueprint $table) {
            if (! Schema::hasColumn('invoice_items', 'sell_unit')) {
                $table->string('sell_unit', 20)->nullable();
            }
            if (! Schema::hasColumn('invoice_items', 'item_weight')) {
                $table->decimal('item_weight', 10, 3)->nullable();
            }
            if (! Schema::hasColumn('invoice_items', 'pricing_unit')) {
                $table->string('pricing_unit', 20)->default('pack');
            }
        });
    }

    public function down(): void
    {
        // No-op for imported handoff databases.
    }
};
