<?php

namespace App\Services;

use App\Models\VendorInvoice;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Schema;

class VendorInvoiceMonthSummaryService
{
    /**
     * Summarise supplier invoices whose invoice date falls in the month that
     * contains the supplied moment. Posted supplier credits/debits are applied
     * without rewriting the original invoice total.
     *
     * @return array{
     *     count:int,
     *     original_total:float,
     *     posted_adjustment_total:float,
     *     adjusted_total:float,
     *     from:string,
     *     to:string
     * }
     */
    public function forMonth(CarbonInterface $moment): array
    {
        $from = $moment->copy()->startOfMonth();
        $to = $moment->copy()->endOfDay();

        $hasAdjustments = Schema::hasTable('vendor_invoice_adjustments');

        $query = VendorInvoice::query()
            ->whereDate('invoice_date', '>=', $from->toDateString())
            ->whereDate('invoice_date', '<=', $to->toDateString())
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('id');

        if ($hasAdjustments) {
            $query->withSum(
                'postedAdjustments as posted_adjustment_total',
                'total_delta'
            );
        }

        $invoices = $query->get(['id', 'total_amount']);

        $originalTotal = 0.0;
        $adjustmentTotal = 0.0;
        $adjustedTotal = 0.0;

        foreach ($invoices as $invoice) {
            $original = round((float) $invoice->total_amount, 2);
            $adjustment = $hasAdjustments
                ? round((float) ($invoice->getAttribute('posted_adjustment_total') ?? 0), 2)
                : 0.0;

            $originalTotal += $original;
            $adjustmentTotal += $adjustment;
            $adjustedTotal += max(0, $original + $adjustment);
        }

        return [
            'count' => $invoices->count(),
            'original_total' => round($originalTotal, 2),
            'posted_adjustment_total' => round($adjustmentTotal, 2),
            'adjusted_total' => round($adjustedTotal, 2),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ];
    }
}
