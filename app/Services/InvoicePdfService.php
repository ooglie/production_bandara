<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\OrderItem;
use App\Support\FinancialYearStoragePath;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class InvoicePdfService
{
    public function __construct(
        protected InvoiceTaxSummaryService $taxSummaryService,
    ) {
    }

    /**
     * Generate a PDF for the invoice, store it on disk, and update pdf_path.
     * If the stored file already exists and $forceRegenerate is false, reuse it.
     */
    public function generateAndStore(Invoice $invoice, bool $forceRegenerate = false): Invoice
    {
        if (
            $invoice->pdf_path
            && ! $forceRegenerate
            && Storage::disk('public')->exists($invoice->pdf_path)
        ) {
            return $invoice;
        }

        $invoice->loadMissing([
            'order.user',
            'order.addresses',
            'order.items.product.hsnCode',
            'items.orderItem.product.hsnCode',
        ]);

        $this->syncInvoiceItemsFromOrder($invoice);

        $invoice->unsetRelation('items');
        $invoice->loadMissing(['items.orderItem.product.hsnCode']);

        $order = $invoice->order;
        $customer = $order?->user;
        $billing = $order?->addresses?->firstWhere('type', 'billing');
        $shipping = $order?->addresses?->firstWhere('type', 'shipping');

        $itemWeights = $invoice->items->pluck('item_weight')->filter();
        $sellUnits = $invoice->items->pluck('sell_unit')->filter()->unique();

        $gstType = $invoice->getAttribute('gst_type') ?: $order?->gst_type;

        $cgstAmount = $invoice->getAttribute('cgst_amount');
        $sgstAmount = $invoice->getAttribute('sgst_amount');
        $igstAmount = $invoice->getAttribute('igst_amount');

        if ($cgstAmount === null && $sgstAmount === null && $igstAmount === null) {
            $cgstAmount = $order?->cgst_amount;
            $sgstAmount = $order?->sgst_amount;
            $igstAmount = $order?->igst_amount;
        }

        $taxTotal = $invoice->tax_total;
        if ($taxTotal === null) {
            $taxTotal = $order?->tax_total;
        }
        if ($taxTotal === null) {
            $taxTotal = (float) ($cgstAmount ?? 0)
                + (float) ($sgstAmount ?? 0)
                + (float) ($igstAmount ?? 0);
        }

        $taxSummary = $this->taxSummaryService->build($invoice, $gstType);
        $gstType = $taxSummary['gst_type'];

        // A GST invoice must use exactly one tax mode. Normalise older rows
        // that may have only tax_total saved so the visible totals never show
        // CGST/SGST together with IGST.
        if ($gstType === 'inter_state') {
            $cgstAmount = 0.0;
            $sgstAmount = 0.0;
            $igstAmount = (float) ($igstAmount ?? 0) > 0
                ? (float) $igstAmount
                : (float) $taxTotal;
        } else {
            $igstAmount = 0.0;

            if (((float) ($cgstAmount ?? 0) + (float) ($sgstAmount ?? 0)) <= 0 && (float) $taxTotal > 0) {
                $cgstAmount = round((float) $taxTotal / 2, 2);
                $sgstAmount = round((float) $taxTotal - (float) $cgstAmount, 2);
            }
        }

        $viewName = View::exists('customer.invoices.pdf')
            ? 'customer.invoices.pdf'
            : 'invoices.pdf';

        $pdf = Pdf::loadView($viewName, [
            'invoice' => $invoice,
            'order' => $order,
            'customer' => $customer,
            'billing' => $billing,
            'shipping' => $shipping,
            'item_weights' => $itemWeights,
            'sell_units' => $sellUnits,
            'gst_type' => $gstType,
            'cgst_amount' => (float) ($cgstAmount ?? 0),
            'sgst_amount' => (float) ($sgstAmount ?? 0),
            'igst_amount' => (float) ($igstAmount ?? 0),
            'tax_total' => (float) ($taxTotal ?? 0),
            'tax_summary' => $taxSummary,
        ])->setPaper('a4');

        $fileName = FinancialYearStoragePath::invoice(
            filename: $invoice->invoice_number . '.pdf',
            date: $invoice->invoice_date,
            customerType: $customer?->is_b2b ? 'B2B' : 'B2C',
        );

        Storage::disk('public')->put($fileName, $pdf->output());

        $invoice->update([
            'pdf_path' => $fileName,
        ]);

        return $invoice;
    }

    /**
     * Older admin-created invoices did not always receive invoice_items rows.
     * Populate only missing rows/fields from the immutable order-item snapshot.
     */
    private function syncInvoiceItemsFromOrder(Invoice $invoice): void
    {
        $order = $invoice->order;
        if (! $order) {
            return;
        }

        $orderItems = $order->items;
        if ($orderItems->isEmpty()) {
            return;
        }

        $existingItems = $invoice->items->keyBy('order_item_id');

        foreach ($orderItems as $orderItem) {
            $existing = $existingItems->get($orderItem->id);
            $data = $this->invoiceItemData($invoice, $orderItem);

            if (! $existing) {
                InvoiceItem::create($data);
                continue;
            }

            $updates = [];
            foreach (['hsn_sac_code', 'gst_rate', 'cgst_amount', 'sgst_amount', 'igst_amount'] as $field) {
                if (
                    array_key_exists($field, $data)
                    && ($existing->getAttribute($field) === null || $existing->getAttribute($field) === '')
                ) {
                    $updates[$field] = $data[$field];
                }
            }

            if ($updates !== []) {
                $existing->fill($updates)->save();
            }
        }
    }

    private function invoiceItemData(Invoice $invoice, OrderItem $orderItem): array
    {
        $data = [
            'invoice_id' => $invoice->id,
            'order_item_id' => $orderItem->id,
            'description' => $orderItem->product_name,
            'quantity' => $orderItem->quantity,
            'item_weight' => $orderItem->item_weight,
            'unit_price' => $orderItem->unit_price,
            'sell_unit' => $orderItem->sell_unit,
            'pricing_unit' => $orderItem->pricing_unit,
            'subtotal' => $orderItem->subtotal,
            'tax_amount' => $orderItem->tax_amount,
            'total' => $orderItem->total,
        ];

        if (Schema::hasColumn('invoice_items', 'hsn_sac_code')) {
            $data['hsn_sac_code'] = trim((string) (
                $orderItem->hsn_sac_code
                    ?? $orderItem->product?->hsnCode?->code
                    ?? ''
            )) ?: null;
        }

        if (Schema::hasColumn('invoice_items', 'gst_rate')) {
            $data['gst_rate'] = $this->resolveOrderItemGstRate($orderItem);
        }

        foreach (['cgst_amount', 'sgst_amount', 'igst_amount'] as $field) {
            if (Schema::hasColumn('invoice_items', $field)) {
                $data[$field] = $orderItem->getAttribute($field);
            }
        }

        return $data;
    }

    private function resolveOrderItemGstRate(OrderItem $orderItem): float
    {
        $stored = $orderItem->getAttribute('gst_rate');
        if ($stored !== null && $stored !== '' && is_numeric($stored)) {
            return round(max((float) $stored, 0), 2);
        }

        $taxAmount = round(max((float) ($orderItem->tax_amount ?? 0), 0), 2);
        $taxableValue = round(max((float) ($orderItem->total ?? 0) - $taxAmount, 0), 2);

        if ($taxableValue > 0) {
            return round(($taxAmount / $taxableValue) * 100, 2);
        }

        return round(max((float) (
            $orderItem->product?->hsnCode?->gst_rate
                ?? $orderItem->product?->gst_rate
                ?? 0
        ), 0), 2);
    }
}
