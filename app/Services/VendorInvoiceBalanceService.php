<?php

namespace App\Services;

use App\Models\VendorInvoice;

class VendorInvoiceBalanceService
{
    public function summary(VendorInvoice $invoice): array
    {
        $originalSubtotal = round((float) $invoice->subtotal, 2);
        $originalTax = round((float) $invoice->tax_amount, 2);
        $originalTotal = round((float) $invoice->total_amount, 2);

        $adjustmentSubtotal = $this->postedComponent($invoice, 'subtotal_delta', 'posted_adjustment_subtotal');
        $adjustmentTax = $this->postedComponent($invoice, 'tax_delta', 'posted_adjustment_tax');
        $adjustmentTotal = $this->postedComponent($invoice, 'total_delta', 'posted_adjustment_total');

        $adjustedSubtotal = round(max(0, $originalSubtotal + $adjustmentSubtotal), 2);
        $adjustedTax = round(max(0, $originalTax + $adjustmentTax), 2);
        $adjustedTotal = round(max(0, $originalTotal + $adjustmentTotal), 2);
        $paid = $this->paidAmount($invoice);
        $outstanding = round(max(0, $adjustedTotal - $paid), 2);
        $vendorCreditDue = round(max(0, $paid - $adjustedTotal), 2);

        return [
            'original_subtotal' => $originalSubtotal,
            'original_tax' => $originalTax,
            'original_total' => $originalTotal,
            'adjustment_subtotal' => $adjustmentSubtotal,
            'adjustment_tax' => $adjustmentTax,
            'adjustment_total' => $adjustmentTotal,
            'adjusted_subtotal' => $adjustedSubtotal,
            'adjusted_tax' => $adjustedTax,
            'adjusted_total' => $adjustedTotal,
            'paid' => $paid,
            'outstanding' => $outstanding,
            'vendor_credit_due' => $vendorCreditDue,
        ];
    }

    public function adjustedTotal(VendorInvoice $invoice): float
    {
        return $this->summary($invoice)['adjusted_total'];
    }

    public function outstanding(VendorInvoice $invoice): float
    {
        return $this->summary($invoice)['outstanding'];
    }

    public function syncStatus(VendorInvoice $invoice): VendorInvoice
    {
        if ((string) $invoice->status === 'cancelled') {
            return $invoice;
        }

        $summary = $this->summary($invoice);
        $adjustedTotal = $summary['adjusted_total'];
        $paid = $summary['paid'];

        $status = 'pending';

        if ($adjustedTotal <= 0.005 || $paid + 0.005 >= $adjustedTotal) {
            $status = 'paid';
        } elseif ($paid > 0.005) {
            $status = 'partially_paid';
        }

        if ((string) $invoice->status !== $status) {
            $invoice->status = $status;
            $invoice->save();
        }

        return $invoice;
    }

    private function postedComponent(VendorInvoice $invoice, string $column, string $aggregateAttribute): float
    {
        if (array_key_exists($aggregateAttribute, $invoice->getAttributes())) {
            return round((float) $invoice->getAttribute($aggregateAttribute), 2);
        }

        if ($invoice->relationLoaded('postedAdjustments')) {
            return round((float) $invoice->postedAdjustments->sum($column), 2);
        }

        if ($invoice->relationLoaded('adjustments')) {
            return round((float) $invoice->adjustments
                ->where('status', 'posted')
                ->sum($column), 2);
        }

        return round((float) $invoice->postedAdjustments()->sum($column), 2);
    }

    private function paidAmount(VendorInvoice $invoice): float
    {
        if (array_key_exists('paid_total', $invoice->getAttributes())) {
            return round((float) $invoice->getAttribute('paid_total'), 2);
        }

        if ($invoice->relationLoaded('payments')) {
            return round((float) $invoice->payments->sum('amount'), 2);
        }

        return round((float) $invoice->payments()->sum('amount'), 2);
    }
}
