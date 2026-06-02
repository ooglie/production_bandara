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
