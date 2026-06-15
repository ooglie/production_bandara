<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_variants') || ! Schema::hasColumn('product_variants', 'product_sell_unit_id')) {
            return;
        }

        $linkedVariantQuery = DB::table('product_variants as pv')
            ->whereNotNull('pv.product_sell_unit_id');

        if (Schema::hasColumn('product_variants', 'deleted_at')) {
            $linkedVariantQuery->whereNull('pv.deleted_at');
        }

        if (Schema::hasTable('product_variant_attribute_values')) {
            $linkedVariantQuery
                ->leftJoin('product_variant_attribute_values as pvav', 'pvav.product_variant_id', '=', 'pv.id')
                ->whereNull('pvav.product_variant_id');
        }

        $variantRows = $linkedVariantQuery
            ->select('pv.id', 'pv.product_id')
            ->get();

        if ($variantRows->isEmpty()) {
            return;
        }

        $variantIds = $variantRows->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $productIds = $variantRows->pluck('product_id')->map(fn ($id) => (int) $id)->unique()->values()->all();

        $variantUpdate = [];
        if (Schema::hasColumn('product_variants', 'is_active')) {
            $variantUpdate['is_active'] = false;
        }
        if (Schema::hasColumn('product_variants', 'deleted_at')) {
            $variantUpdate['deleted_at'] = now();
        }
        if (Schema::hasColumn('product_variants', 'updated_at')) {
            $variantUpdate['updated_at'] = now();
        }

        if ($variantUpdate !== []) {
            DB::table('product_variants')
                ->whereIn('id', $variantIds)
                ->update($variantUpdate);
        }

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'type') && $productIds !== []) {
            $activeVariantProductIds = DB::table('product_variants')
                ->whereIn('product_id', $productIds)
                ->when(Schema::hasColumn('product_variants', 'deleted_at'), fn ($query) => $query->whereNull('deleted_at'))
                ->pluck('product_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $productsWithoutVisibleVariants = array_values(array_diff($productIds, $activeVariantProductIds));

            if ($productsWithoutVisibleVariants !== []) {
                DB::table('products')
                    ->whereIn('id', $productsWithoutVisibleVariants)
                    ->update(['type' => 'simple']);
            }
        }
    }

    public function down(): void
    {
        // No-op. This migration only hides auto-generated compatibility variants
        // that were created by the previous refactor attempt.
    }
};
