<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            if (! Schema::hasColumn('product_variants', 'product_sell_unit_id')) {
                $table->foreignId('product_sell_unit_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('product_sell_units')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            if (Schema::hasColumn('product_variants', 'product_sell_unit_id')) {
                $table->dropConstrainedForeignId('product_sell_unit_id');
            }
        });
    }
};
