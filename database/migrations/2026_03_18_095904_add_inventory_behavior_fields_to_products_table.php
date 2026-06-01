<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'lot_stage_default')) {
                $table->string('lot_stage_default', 20)
                    ->nullable()
                    ->after('type');
            }

            if (!Schema::hasColumn('products', 'inventory_is_saleable')) {
                $table->boolean('inventory_is_saleable')
                    ->default(true)
                    ->after('lot_stage_default');
            }

            if (!Schema::hasColumn('products', 'inventory_can_repack')) {
                $table->boolean('inventory_can_repack')
                    ->default(false)
                    ->after('inventory_is_saleable');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $drops = [];

            if (Schema::hasColumn('products', 'inventory_can_repack')) {
                $drops[] = 'inventory_can_repack';
            }

            if (Schema::hasColumn('products', 'inventory_is_saleable')) {
                $drops[] = 'inventory_is_saleable';
            }

            if (Schema::hasColumn('products', 'lot_stage_default')) {
                $drops[] = 'lot_stage_default';
            }

            if (!empty($drops)) {
                $table->dropColumn($drops);
            }
        });
    }
};