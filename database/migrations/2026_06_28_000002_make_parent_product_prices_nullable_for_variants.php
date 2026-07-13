<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'mrp_price')) {
                $table->decimal('mrp_price', 12, 2)->nullable()->after('base_price');
            }
        });

        $this->modifyProductPriceColumn('base_price', 'DECIMAL(12,2) NULL DEFAULT NULL');
        $this->modifyProductPriceColumn('mrp_price', 'DECIMAL(12,2) NULL DEFAULT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        if (Schema::hasColumn('products', 'base_price')) {
            DB::table('products')->whereNull('base_price')->update(['base_price' => 0]);
            $this->modifyProductPriceColumn('base_price', 'DECIMAL(12,2) NOT NULL DEFAULT 0.00');
        }

        if (Schema::hasColumn('products', 'mrp_price')) {
            DB::table('products')->whereNull('mrp_price')->update(['mrp_price' => 0]);
            $this->modifyProductPriceColumn('mrp_price', 'DECIMAL(12,2) NOT NULL DEFAULT 0.00');
        }
    }

    private function modifyProductPriceColumn(string $column, string $definition): void
    {
        if (! Schema::hasColumn('products', $column)) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("ALTER TABLE `products` MODIFY `{$column}` {$definition}");
    }
};
