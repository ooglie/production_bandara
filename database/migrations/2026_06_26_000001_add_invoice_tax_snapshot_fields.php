<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addOrderItemColumns();
        $this->addInvoiceItemColumns();
        $this->addOrderColumns();
        $this->addInvoiceColumns();

        $this->backfillOrderItemSnapshots();
        $this->backfillInvoiceItemSnapshots();
        $this->backfillServiceSacCodes();
        $this->backfillInvoiceTaxMode();
    }

    public function down(): void
    {
        $this->dropExistingColumns('invoice_items', [
            'hsn_sac_code',
            'gst_rate',
            'cgst_amount',
            'sgst_amount',
            'igst_amount',
        ]);

        $this->dropExistingColumns('order_items', [
            'hsn_sac_code',
            'gst_rate',
        ]);

        $this->dropExistingColumns('invoices', [
            'gst_type',
            'cgst_amount',
            'sgst_amount',
            'igst_amount',
            'delivery_sac_code',
            'handling_sac_code',
        ]);

        $this->dropExistingColumns('orders', [
            'delivery_sac_code',
            'handling_sac_code',
        ]);
    }

    private function addOrderItemColumns(): void
    {
        if (! Schema::hasTable('order_items')) {
            return;
        }

        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'hsn_sac_code')) {
                $table->string('hsn_sac_code', 32)->nullable()->after('sku');
            }

            if (! Schema::hasColumn('order_items', 'gst_rate')) {
                $table->decimal('gst_rate', 5, 2)->nullable()->after('hsn_sac_code');
            }
        });
    }

    private function addInvoiceItemColumns(): void
    {
        if (! Schema::hasTable('invoice_items')) {
            return;
        }

        Schema::table('invoice_items', function (Blueprint $table) {
            if (! Schema::hasColumn('invoice_items', 'hsn_sac_code')) {
                $table->string('hsn_sac_code', 32)->nullable()->after('description');
            }

            if (! Schema::hasColumn('invoice_items', 'gst_rate')) {
                $table->decimal('gst_rate', 5, 2)->nullable()->after('hsn_sac_code');
            }

            if (! Schema::hasColumn('invoice_items', 'cgst_amount')) {
                $table->decimal('cgst_amount', 10, 2)->nullable()->after('tax_amount');
            }

            if (! Schema::hasColumn('invoice_items', 'sgst_amount')) {
                $table->decimal('sgst_amount', 10, 2)->nullable()->after('cgst_amount');
            }

            if (! Schema::hasColumn('invoice_items', 'igst_amount')) {
                $table->decimal('igst_amount', 10, 2)->nullable()->after('sgst_amount');
            }
        });
    }

    private function addOrderColumns(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'delivery_sac_code')) {
                $table->string('delivery_sac_code', 32)->nullable()->after('handling_tax_rate');
            }

            if (! Schema::hasColumn('orders', 'handling_sac_code')) {
                $table->string('handling_sac_code', 32)->nullable()->after('delivery_sac_code');
            }
        });
    }

    private function addInvoiceColumns(): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'gst_type')) {
                $table->enum('gst_type', ['intra_state', 'inter_state'])->nullable()->after('discount_total');
            }

            if (! Schema::hasColumn('invoices', 'cgst_amount')) {
                $table->decimal('cgst_amount', 10, 2)->nullable()->after('gst_type');
            }

            if (! Schema::hasColumn('invoices', 'sgst_amount')) {
                $table->decimal('sgst_amount', 10, 2)->nullable()->after('cgst_amount');
            }

            if (! Schema::hasColumn('invoices', 'igst_amount')) {
                $table->decimal('igst_amount', 10, 2)->nullable()->after('sgst_amount');
            }

            if (! Schema::hasColumn('invoices', 'delivery_sac_code')) {
                $table->string('delivery_sac_code', 32)->nullable()->after('handling_tax_rate');
            }

            if (! Schema::hasColumn('invoices', 'handling_sac_code')) {
                $table->string('handling_sac_code', 32)->nullable()->after('delivery_sac_code');
            }
        });
    }

    private function backfillOrderItemSnapshots(): void
    {
        if (
            ! Schema::hasTable('order_items')
            || ! Schema::hasColumn('order_items', 'hsn_sac_code')
            || ! Schema::hasColumn('order_items', 'gst_rate')
        ) {
            return;
        }

        DB::table('order_items')
            ->select(['id', 'product_id', 'tax_amount', 'total'])
            ->orderBy('id')
            ->chunkById(250, function ($items) {
                $productIds = $items->pluck('product_id')->filter()->unique()->values();

                $products = DB::table('products')
                    ->leftJoin('hsn_codes', 'hsn_codes.id', '=', 'products.hsn_code_id')
                    ->whereIn('products.id', $productIds)
                    ->select([
                        'products.id',
                        'products.gst_rate as product_gst_rate',
                        'hsn_codes.code as hsn_sac_code',
                        'hsn_codes.gst_rate as hsn_gst_rate',
                    ])
                    ->get()
                    ->keyBy('id');

                foreach ($items as $item) {
                    $product = $products->get($item->product_id);
                    $taxAmount = round((float) ($item->tax_amount ?? 0), 2);
                    $taxableValue = round(max((float) ($item->total ?? 0) - $taxAmount, 0), 2);

                    $derivedRate = $taxableValue > 0
                        ? round(($taxAmount / $taxableValue) * 100, 2)
                        : null;

                    $fallbackRate = $product?->hsn_gst_rate ?? $product?->product_gst_rate;
                    $gstRate = $derivedRate ?? ($fallbackRate !== null ? round((float) $fallbackRate, 2) : null);

                    DB::table('order_items')
                        ->where('id', $item->id)
                        ->update([
                            'hsn_sac_code' => $product?->hsn_sac_code ?: null,
                            'gst_rate' => $gstRate,
                        ]);
                }
            }, 'id');
    }

    private function backfillInvoiceItemSnapshots(): void
    {
        if (
            ! Schema::hasTable('invoice_items')
            || ! Schema::hasTable('order_items')
            || ! Schema::hasColumn('invoice_items', 'hsn_sac_code')
        ) {
            return;
        }

        DB::table('invoice_items')
            ->select(['id', 'order_item_id'])
            ->orderBy('id')
            ->chunkById(250, function ($items) {
                $orderItemIds = $items->pluck('order_item_id')->filter()->unique()->values();

                $orderItems = DB::table('order_items')
                    ->whereIn('id', $orderItemIds)
                    ->select([
                        'id',
                        'hsn_sac_code',
                        'gst_rate',
                        'cgst_amount',
                        'sgst_amount',
                        'igst_amount',
                    ])
                    ->get()
                    ->keyBy('id');

                foreach ($items as $item) {
                    $orderItem = $orderItems->get($item->order_item_id);
                    if (! $orderItem) {
                        continue;
                    }

                    DB::table('invoice_items')
                        ->where('id', $item->id)
                        ->update([
                            'hsn_sac_code' => $orderItem->hsn_sac_code,
                            'gst_rate' => $orderItem->gst_rate,
                            'cgst_amount' => $orderItem->cgst_amount,
                            'sgst_amount' => $orderItem->sgst_amount,
                            'igst_amount' => $orderItem->igst_amount,
                        ]);
                }
            }, 'id');
    }

    private function backfillServiceSacCodes(): void
    {
        $deliverySacCode = trim((string) config('delivery.delivery_sac_code', ''));
        $handlingSacCode = trim((string) config('delivery.handling_sac_code', ''));

        foreach (['orders', 'invoices'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if ($deliverySacCode !== '' && Schema::hasColumn($table, 'delivery_sac_code')) {
                DB::table($table)
                    ->whereNull('delivery_sac_code')
                    ->update(['delivery_sac_code' => $deliverySacCode]);
            }

            if ($handlingSacCode !== '' && Schema::hasColumn($table, 'handling_sac_code')) {
                DB::table($table)
                    ->whereNull('handling_sac_code')
                    ->update(['handling_sac_code' => $handlingSacCode]);
            }
        }
    }

    private function backfillInvoiceTaxMode(): void
    {
        if (
            ! Schema::hasTable('invoices')
            || ! Schema::hasTable('orders')
            || ! Schema::hasColumn('invoices', 'gst_type')
        ) {
            return;
        }

        DB::table('invoices')
            ->select(['id', 'order_id', 'gst_type', 'cgst_amount', 'sgst_amount', 'igst_amount'])
            ->orderBy('id')
            ->chunkById(250, function ($invoices) {
                $orderIds = $invoices->pluck('order_id')->filter()->unique()->values();

                $orders = DB::table('orders')
                    ->whereIn('id', $orderIds)
                    ->select(['id', 'gst_type', 'cgst_amount', 'sgst_amount', 'igst_amount'])
                    ->get()
                    ->keyBy('id');

                foreach ($invoices as $invoice) {
                    $order = $orders->get($invoice->order_id);
                    if (! $order) {
                        continue;
                    }

                    $updates = [];
                    if ($invoice->gst_type === null) {
                        $updates['gst_type'] = $order->gst_type;
                    }
                    if ($invoice->cgst_amount === null) {
                        $updates['cgst_amount'] = $order->cgst_amount;
                    }
                    if ($invoice->sgst_amount === null) {
                        $updates['sgst_amount'] = $order->sgst_amount;
                    }
                    if ($invoice->igst_amount === null) {
                        $updates['igst_amount'] = $order->igst_amount;
                    }

                    if ($updates !== []) {
                        DB::table('invoices')->where('id', $invoice->id)->update($updates);
                    }
                }
            }, 'id');
    }

    private function dropExistingColumns(string $table, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $existing = array_values(array_filter(
            $columns,
            fn (string $column): bool => Schema::hasColumn($table, $column)
        ));

        if ($existing === []) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($existing) {
            $blueprint->dropColumn($existing);
        });
    }
};
