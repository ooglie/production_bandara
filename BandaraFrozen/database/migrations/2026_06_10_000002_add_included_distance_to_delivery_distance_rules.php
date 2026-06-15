<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('delivery_distance_rules')) {
            return;
        }

        Schema::table('delivery_distance_rules', function (Blueprint $table) {
            if (! Schema::hasColumn('delivery_distance_rules', 'included_distance_km')) {
                $table->decimal('included_distance_km', 8, 2)
                    ->default(0)
                    ->after('delivery_fee');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('delivery_distance_rules') || ! Schema::hasColumn('delivery_distance_rules', 'included_distance_km')) {
            return;
        }

        Schema::table('delivery_distance_rules', function (Blueprint $table) {
            $table->dropColumn('included_distance_km');
        });
    }
};
