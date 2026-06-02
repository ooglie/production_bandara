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

        DB::statement(<<<'SQL'
            UPDATE products p
            INNER JOIN hsn_codes h ON h.id = p.hsn_code_id
            SET p.gst_rate = h.gst_rate
            WHERE p.hsn_code_id IS NOT NULL
              AND h.gst_rate > 0
              AND (p.gst_rate IS NULL OR p.gst_rate <= 0)
        SQL);
    }

    public function down(): void
    {
        // Data backfill only; do not clear product GST rates on rollback.
    }
};
