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

        Schema::table('product_variants', function (Blueprint $table) {
            if (! Schema::hasColumn('product_variants', 'pack_type')) {
                $table->string('pack_type', 40)->nullable()->after('name')->index();
            }

            if (! Schema::hasColumn('product_variants', 'pieces_per_pack')) {
                $table->decimal('pieces_per_pack', 12, 3)->nullable()->after('product_weight');
            }

            if (! Schema::hasColumn('product_variants', 'mrp_price')) {
                $table->decimal('mrp_price', 12, 2)->nullable()->after('price');
            }
        });

        if (Schema::hasColumn('product_variants', 'pack_type')) {
            DB::table('product_variants')
                ->whereNull('pack_type')
                ->update(['pack_type' => DB::raw("CASE WHEN COALESCE(product_weight, 0) > 0 THEN 'fixed_weight_pack' ELSE 'quantity' END")]);
        }
    }

    public function down(): void
    {
        // Non-destructive. These columns are guarded in code and are safe to leave.
    }
};
