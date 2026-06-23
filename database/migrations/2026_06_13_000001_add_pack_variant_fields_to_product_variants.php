<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_variants')) {
            return;
        }

        $this->ensureProductWeightColumn();

        Schema::table('product_variants', function (Blueprint $table) {
            if (! Schema::hasColumn('product_variants', 'pack_type')) {
                $column = $table->string('pack_type', 40)->nullable()->index();

                if (Schema::hasColumn('product_variants', 'name')) {
                    $column->after('name');
                }
            }

            if (! Schema::hasColumn('product_variants', 'pieces_per_pack')) {
                $column = $table->decimal('pieces_per_pack', 12, 3)->nullable();

                if (Schema::hasColumn('product_variants', 'product_weight')) {
                    $column->after('product_weight');
                }
            }

            if (! Schema::hasColumn('product_variants', 'mrp_price')) {
                $column = $table->decimal('mrp_price', 12, 2)->nullable();

                if (Schema::hasColumn('product_variants', 'price')) {
                    $column->after('price');
                }
            }
        });

        if (Schema::hasColumn('product_variants', 'product_weight')) {
            $this->backfillProductWeight();
        }

        if (Schema::hasColumn('product_variants', 'pack_type')) {
            DB::table('product_variants')
                ->whereNull('pack_type')
                ->update([
                    'pack_type' => DB::raw("CASE WHEN COALESCE(product_weight, 0) > 0 THEN 'fixed_weight_pack' ELSE 'quantity' END"),
                ]);
        }

        $this->makeProductWeightNotNullForMysql();
    }

    public function down(): void
    {
        // Non-destructive. These columns are guarded in code and are safe to leave.
    }

    private function ensureProductWeightColumn(): void
    {
        if (Schema::hasColumn('product_variants', 'product_weight')) {
            return;
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $column = $table->decimal('product_weight', 10, 3)->nullable();

            if (Schema::hasColumn('product_variants', 'min_order_quantity')) {
                $column->after('min_order_quantity');
            }
        });
    }

    private function backfillProductWeight(): void
    {
        if (Schema::hasTable('product_sell_units')
            && Schema::hasColumn('product_variants', 'product_sell_unit_id')
            && Schema::hasColumn('product_sell_units', 'weight_per_unit_kg')) {
            DB::table('product_variants as pv')
                ->join('product_sell_units as psu', 'psu.id', '=', 'pv.product_sell_unit_id')
                ->whereNull('pv.product_weight')
                ->whereNotNull('psu.weight_per_unit_kg')
                ->update(['pv.product_weight' => DB::raw('psu.weight_per_unit_kg')]);
        }

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'product_weight')) {
            DB::table('product_variants as pv')
                ->join('products as p', 'p.id', '=', 'pv.product_id')
                ->whereNull('pv.product_weight')
                ->whereNotNull('p.product_weight')
                ->update(['pv.product_weight' => DB::raw('p.product_weight')]);
        }

        DB::table('product_variants')
            ->whereNull('product_weight')
            ->update(['product_weight' => 0]);
    }

    private function makeProductWeightNotNullForMysql(): void
    {
        if (! Schema::hasColumn('product_variants', 'product_weight')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement('ALTER TABLE `product_variants` MODIFY `product_weight` DECIMAL(10,3) NOT NULL');
    }
};
