<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'gst_rate')) {
            return;
        }

        $defaultRate = round(max((float) config('pricing.default_gst_rate', 5), 0.0), 2);
        if ($defaultRate <= 0) {
            return;
        }

        if (Schema::hasTable('hsn_codes') && Schema::hasColumn('products', 'hsn_code_id') && Schema::hasColumn('hsn_codes', 'gst_rate')) {
            $driver = DB::getDriverName();

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement(
                    'UPDATE products p
                     LEFT JOIN hsn_codes h ON h.id = p.hsn_code_id
                     SET p.gst_rate = ?
                     WHERE (p.gst_rate IS NULL OR p.gst_rate <= 0)
                       AND (p.hsn_code_id IS NULL OR h.id IS NULL OR h.gst_rate IS NULL)',
                    [$defaultRate]
                );

                return;
            }

            // Portable fallback for SQLite and other non-MySQL databases.
            // Preserve the original rule: use the default only when there is no
            // HSN row, no HSN selection, or the selected HSN has a null GST rate.
            DB::table('products')
                ->select(['id', 'hsn_code_id'])
                ->where(function ($query) {
                    $query->whereNull('gst_rate')->orWhere('gst_rate', '<=', 0);
                })
                ->orderBy('id')
                ->chunkById(250, function ($products) use ($defaultRate): void {
                    $hsnRows = DB::table('hsn_codes')
                        ->whereIn('id', $products->pluck('hsn_code_id')->filter()->unique()->values())
                        ->select(['id', 'gst_rate'])
                        ->get()
                        ->keyBy('id');

                    foreach ($products as $product) {
                        $hsn = $product->hsn_code_id !== null
                            ? $hsnRows->get($product->hsn_code_id)
                            : null;

                        if ($product->hsn_code_id !== null && $hsn !== null && $hsn->gst_rate !== null) {
                            continue;
                        }

                        DB::table('products')
                            ->where('id', $product->id)
                            ->update(['gst_rate' => $defaultRate]);
                    }
                }, 'id');

            return;
        }

        DB::table('products')
            ->where(function ($query) {
                $query->whereNull('gst_rate')->orWhere('gst_rate', '<=', 0);
            })
            ->update(['gst_rate' => $defaultRate]);
    }

    public function down(): void
    {
        // Data backfill only. No destructive rollback.
    }
};
