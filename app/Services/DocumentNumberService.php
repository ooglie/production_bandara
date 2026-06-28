<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    private const ORDER_INVOICE_SEQUENCE_KEY = 'order_invoice';
    private const START_SEQUENCE = 101;

    /**
     * Generate a matching customer-facing order/invoice number pair.
     *
     * Example for 24 June 2026:
     * BA-ORD-240626-101
     * BA-INV-240626-101
     */
    public function nextOrderInvoicePair(?CarbonInterface $date = null): array
    {
        $date = $date ? $date->copy() : now();

        return DB::transaction(function () use ($date) {
            do {
                $sequence = $this->nextMonthlySequence(self::ORDER_INVOICE_SEQUENCE_KEY, $date);
                $datePart = $date->format('dmy');

                $pair = [
                    'sequence' => $sequence,
                    'order_number' => sprintf('BA-ORD-%s-%03d', $datePart, $sequence),
                    'invoice_number' => sprintf('BA-INV-%s-%03d', $datePart, $sequence),
                ];
            } while ($this->orderOrInvoiceNumberAlreadyExists($pair['order_number'], $pair['invoice_number']));

            return $pair;
        });
    }

    private function nextMonthlySequence(string $sequenceKey, CarbonInterface $date): int
    {
        $period = $date->format('Ym');
        $timestamp = now();

        DB::table('document_number_sequences')->insertOrIgnore([
            'sequence_key' => $sequenceKey,
            'period' => $period,
            'last_number' => self::START_SEQUENCE - 1,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $row = DB::table('document_number_sequences')
            ->where('sequence_key', $sequenceKey)
            ->where('period', $period)
            ->lockForUpdate()
            ->first();

        $next = max(((int) ($row->last_number ?? 0)) + 1, self::START_SEQUENCE);

        DB::table('document_number_sequences')
            ->where('id', $row->id)
            ->update([
                'last_number' => $next,
                'updated_at' => $timestamp,
            ]);

        return $next;
    }

    private function orderOrInvoiceNumberAlreadyExists(string $orderNumber, string $invoiceNumber): bool
    {
        return Order::query()->where('order_number', $orderNumber)->exists()
            || Invoice::query()->where('invoice_number', $invoiceNumber)->exists();
    }
}
