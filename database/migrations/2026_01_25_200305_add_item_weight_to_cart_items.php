<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('cart_items')) {
            return;
        }

        if (! Schema::hasColumn('cart_items', 'item_weight')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->decimal('item_weight', 10, 3)->nullable();
            });
        }
    }

    public function down(): void
    {
        // No-op for imported handoff databases.
    }
};
