<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasTable('hsn_codes')) {
            return;
        }

        foreach (['hsn_code_id', 'gst_rate'] as $column) {
            if (! Schema::hasColumn('products', $column)) {
                return;
            }
        }

        if (! Schema::hasColumn('hsn_codes', 'gst_rate')) {
            return;
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(<<<'SQL'
                UPDATE products p
                INNER JOIN hsn_codes h ON h.id = p.hsn_code_id
                SET p.gst_rate = h.gst_rate
                WHERE p.hsn_code_id IS NOT NULL
                  AND h.gst_rate > 0
                  AND (p.gst_rate IS NULL OR p.gst_rate <= 0)
            SQL);

            return;
        }

        // SQLite and other test databases do not support MySQL's
        // UPDATE ... INNER JOIN syntax. Read the matching HSN rates and update
        // products by primary key instead. The data result is identical.
        DB::table('products')
            ->select(['id', 'hsn_code_id'])
            ->whereNotNull('hsn_code_id')
            ->where(function ($query) {
                $query->whereNull('gst_rate')->orWhere('gst_rate', '<=', 0);
            })
            ->orderBy('id')
            ->chunkById(250, function ($products): void {
                $hsnRates = DB::table('hsn_codes')
                    ->whereIn('id', $products->pluck('hsn_code_id')->filter()->unique()->values())
                    ->where('gst_rate', '>', 0)
                    ->pluck('gst_rate', 'id');

                foreach ($products as $product) {
                    $rate = $hsnRates->get($product->hsn_code_id);

                    if ($rate === null) {
                        continue;
                    }

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update(['gst_rate' => $rate]);
                }
            }, 'id');
    }

    public function down(): void
    {
        // Data backfill only; do not clear product GST rates on rollback.
    }
};
