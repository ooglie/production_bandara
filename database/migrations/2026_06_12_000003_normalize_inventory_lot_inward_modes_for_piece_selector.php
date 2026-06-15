<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_lots') || ! Schema::hasColumn('inventory_lots', 'inward_mode')) {
            return;
        }

        // The storefront/cart slab UI already uses the legacy inventory-lot modes:
        // pieces = individually weighed saleable pieces/slabs
        // qty    = normal quantity stock
        // Vendor invoices may still store richer receipt_type values separately.
        DB::table('inventory_lots')
            ->where('inward_mode', 'pieces_weight')
            ->update(['inward_mode' => 'pieces']);

        DB::table('inventory_lots')
            ->where('inward_mode', 'quantity')
            ->update(['inward_mode' => 'qty']);

        // Existing products/lots created during the refactor may have a visible product
        // but a non-saleable piece lot. The slab selector needs the lot to be
        // saleable, so repair only active non-internal products.
        if (Schema::hasTable('products') && Schema::hasColumn('inventory_lots', 'is_saleable')) {
            $query = DB::table('inventory_lots')
                ->join('products', 'products.id', '=', 'inventory_lots.product_id')
                ->where('inventory_lots.inward_mode', 'pieces')
                ->where('inventory_lots.lot_status', 'available')
                ->where('products.is_active', true);

            if (Schema::hasColumn('products', 'inventory_role')) {
                $query->where(function ($q) {
                    $q->whereNull('products.inventory_role')
                        ->orWhere('products.inventory_role', '<>', 'internal');
                });
            }

            $query->update(['inventory_lots.is_saleable' => true]);
        }
    }

    public function down(): void
    {
        // Non-destructive: do not guess which legacy pieces/qty lots came from vendor invoices.
    }
};
