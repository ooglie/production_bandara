<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('inventory_pieces')) return;

        Schema::table('inventory_pieces', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_pieces', 'production_run_id')) {
                $table->foreignId('production_run_id')->nullable()->after('inventory_lot_id')
                    ->constrained('production_runs')->nullOnDelete();
            }
            if (!Schema::hasColumn('inventory_pieces', 'consumed_at')) {
                $table->dateTime('consumed_at')->nullable()->after('production_run_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('inventory_pieces')) return;

        Schema::table('inventory_pieces', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_pieces', 'production_run_id')) {
                $table->dropConstrainedForeignId('production_run_id');
            }
            if (Schema::hasColumn('inventory_pieces', 'consumed_at')) {
                $table->dropColumn('consumed_at');
            }
        });
    }
};
