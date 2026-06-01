<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('inventory_lots', 'vendor_invoice_item_id')) {
            Schema::table('inventory_lots', function (Blueprint $table) {
                $table->foreignId('vendor_invoice_item_id')
                    ->nullable()
                    ->after('vendor_invoice_id')
                    ->constrained('vendor_invoice_items')
                    ->nullOnDelete();
            });
        }

        /*
         * Backfill old rows.
         * Historical lots were created in the same loop/order as vendor_invoice_items,
         * so for each invoice we map by ascending id order.
         */
        $invoiceIds = DB::table('inventory_lots')
            ->whereNotNull('vendor_invoice_id')
            ->distinct()
            ->orderBy('vendor_invoice_id')
            ->pluck('vendor_invoice_id');

        foreach ($invoiceIds as $invoiceId) {
            $itemIds = DB::table('vendor_invoice_items')
                ->where('vendor_invoice_id', $invoiceId)
                ->orderBy('id')
                ->pluck('id')
                ->values();

            $lotIds = DB::table('inventory_lots')
                ->where('vendor_invoice_id', $invoiceId)
                ->whereNull('vendor_invoice_item_id')
                ->orderBy('id')
                ->pluck('id')
                ->values();

            $count = min($itemIds->count(), $lotIds->count());

            for ($i = 0; $i < $count; $i++) {
                DB::table('inventory_lots')
                    ->where('id', $lotIds[$i])
                    ->update([
                        'vendor_invoice_item_id' => $itemIds[$i],
                    ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('inventory_lots', 'vendor_invoice_item_id')) {
            Schema::table('inventory_lots', function (Blueprint $table) {
                $table->dropConstrainedForeignId('vendor_invoice_item_id');
            });
        }
    }
};