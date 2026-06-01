<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use App\Support\FinancialYearStoragePath;

class InvoicePdfService
{
    /**
     * Generate a PDF for the invoice, store it on disk, update pdf_path.
     * If already exists and $forceRegenerate = false, reuse existing file.
     */
    public function generateAndStore(Invoice $invoice, bool $forceRegenerate = false): Invoice
    {
        if (
            $invoice->pdf_path &&
            ! $forceRegenerate &&
            Storage::disk('public')->exists($invoice->pdf_path)
        ) {
            return $invoice;
        }

        $invoice->loadMissing(['order.user', 'order.addresses', 'items']);

        $order    = $invoice->order;
        $customer = $order?->user;
        $billing  = $order?->addresses?->firstWhere('type', 'billing');
        $shipping = $order?->addresses?->firstWhere('type', 'shipping');

        $item_weights = $invoice->items->pluck('item_weight')->filter();
        $sell_units   = $invoice->items->pluck('sell_unit')->filter()->unique();

        // ✅ GST amounts: prefer Invoice columns if present, otherwise fall back to Order columns
        $gstType = $order?->gst_type;

        $cgstAmount = $invoice->cgst_amount;
        $sgstAmount = $invoice->sgst_amount;
        $igstAmount = $invoice->igst_amount;

        if ($cgstAmount === null && $sgstAmount === null && $igstAmount === null) {
            $cgstAmount = $order?->cgst_amount;
            $sgstAmount = $order?->sgst_amount;
            $igstAmount = $order?->igst_amount;
        }

        // Total GST: prefer invoice->tax_total, else order->tax_total, else sum splits
        $taxTotal = $invoice->tax_total;
        if ($taxTotal === null) {
            $taxTotal = $order?->tax_total;
        }
        if ($taxTotal === null) {
            $taxTotal = (float)($cgstAmount ?? 0) + (float)($sgstAmount ?? 0) + (float)($igstAmount ?? 0);
        }

        // View name safety: use your provided blade path if present
        $viewName = View::exists('customer.invoices.pdf') ? 'customer.invoices.pdf' : 'invoices.pdf';

        $pdf = Pdf::loadView($viewName, [
            'invoice'       => $invoice,
            'order'         => $order,
            'customer'      => $customer,
            'billing'       => $billing,
            'shipping'      => $shipping,
            'item_weights'  => $item_weights,
            'sell_units'    => $sell_units,

            // ✅ Provide explicit GST values for the PDF
            'gst_type'      => $gstType,
            'cgst_amount'   => (float)($cgstAmount ?? 0),
            'sgst_amount'   => (float)($sgstAmount ?? 0),
            'igst_amount'   => (float)($igstAmount ?? 0),
            'tax_total'     => (float)($taxTotal ?? 0),
        ])->setPaper('a4');

        // $fileName = 'invoices/' . $invoice->invoice_number . '.pdf';

        $fileName = FinancialYearStoragePath::invoice(
                        filename: $invoice->invoice_number . '.pdf',
                        date: $invoice->invoice_date,
                        customerType: $customer->is_b2b ? 'B2B' : 'B2C'
                    );

        // Storage::disk('public')->put($fileName, $pdf->output());
        Storage::disk('public')->put($fileName, $pdf->output());

        // $invoice->pdf_path = $fileName;
        $invoice->update([
                'pdf_path' => $fileName,
            ]);
        $invoice->save();

        return $invoice;
    }
}