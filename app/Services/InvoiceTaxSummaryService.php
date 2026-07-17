<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class InvoiceTaxSummaryService
{
    public function build(Invoice $invoice, ?string $gstType = null): array
    {
        $gstType = $this->resolveGstType($invoice, $gstType);

        $groups = [];
        $expectedTax = $this->expectedTaxAmounts($invoice, $gstType);
        $deriveMissingLineTax = $expectedTax['total_tax'] > 0;
        $serviceChargeFallbacks = $this->serviceChargeFallbacks();

        foreach ($this->invoiceItems($invoice) as $item) {
            $this->addInvoiceItem($groups, $item, $gstType, $deriveMissingLineTax);
        }

        $this->addServiceCharge(
            $groups,
            code: $invoice->delivery_sac_code ?: ($serviceChargeFallbacks['delivery']['code'] ?? null),
            gstRate: $invoice->delivery_tax_rate,
            taxableValue: $invoice->delivery_fee,
            taxAmount: $invoice->delivery_tax_amount,
            gstType: $gstType,
        );

        $this->addServiceCharge(
            $groups,
            code: $invoice->handling_sac_code ?: ($serviceChargeFallbacks['handling']['code'] ?? null),
            gstRate: $invoice->handling_tax_rate,
            taxableValue: $invoice->handling_fee,
            taxAmount: $invoice->handling_tax_amount,
            gstType: $gstType,
        );

        $rows = array_values($groups);

        usort($rows, function (array $left, array $right): int {
            $byCode = strnatcasecmp($left['hsn_sac_code'], $right['hsn_sac_code']);

            return $byCode !== 0
                ? $byCode
                : ($left['gst_rate'] <=> $right['gst_rate']);
        });

        $totals = [
            'taxable_value' => 0.0,
            'cgst_amount' => 0.0,
            'sgst_amount' => 0.0,
            'igst_amount' => 0.0,
            'total_tax' => 0.0,
        ];

        foreach ($rows as &$row) {
            foreach (array_keys($totals) as $field) {
                $row[$field] = round((float) $row[$field], 2);
                $totals[$field] += $row[$field];
            }
        }
        unset($row);

        foreach ($totals as $field => $value) {
            $totals[$field] = round($value, 2);
        }

        $this->reconcileRounding($rows, $totals, $expectedTax, $gstType);

        return [
            'gst_type' => $gstType,
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    private function addInvoiceItem(
        array &$groups,
        InvoiceItem $item,
        string $gstType,
        bool $deriveMissingLineTax,
    ): void {
        $orderItem = $this->relatedModel($item, 'orderItem');

        $invoiceItemTax = round(max((float) ($item->tax_amount ?? 0), 0), 2);
        $orderItemTax = round(max((float) ($orderItem?->tax_amount ?? 0), 0), 2);
        $taxAmount = $invoiceItemTax > 0 ? $invoiceItemTax : $orderItemTax;

        $subtotal = round(max((float) ($item->subtotal ?? 0), 0), 2);
        $lineTotal = round(max((float) ($item->total ?? 0), 0), 2);
        $taxableValue = $taxAmount > 0
            ? round(max($lineTotal - $taxAmount, 0), 2)
            : ($subtotal > 0 ? $subtotal : $lineTotal);

        if ($taxableValue <= 0 && $subtotal > 0) {
            $taxableValue = $subtotal;
        }

        $gstRate = $this->resolveItemGstRate($item, $taxableValue, $taxAmount, $orderItem);

        // Older admin-created invoice items stored only the order-level GST.
        // Rebuild a missing line tax from the snapshotted rate so their HSN
        // summaries remain useful after regeneration.
        if ($deriveMissingLineTax && $taxAmount <= 0 && $taxableValue > 0 && $gstRate > 0) {
            $taxAmount = round($taxableValue * ($gstRate / 100), 2);
        }
        $hsnSacCode = $this->resolveItemHsnSacCode($item, $orderItem);

        [$cgstAmount, $sgstAmount, $igstAmount] = $this->resolveTaxSplit(
            gstType: $gstType,
            taxAmount: $taxAmount,
            cgstAmount: $item->cgst_amount,
            sgstAmount: $item->sgst_amount,
            igstAmount: $item->igst_amount,
        );

        $this->addGroup(
            $groups,
            code: $hsnSacCode,
            gstRate: $gstRate,
            taxableValue: $taxableValue,
            cgstAmount: $cgstAmount,
            sgstAmount: $sgstAmount,
            igstAmount: $igstAmount,
        );
    }

    private function addServiceCharge(
        array &$groups,
        mixed $code,
        mixed $gstRate,
        mixed $taxableValue,
        mixed $taxAmount,
        string $gstType,
    ): void {
        $taxableValue = round(max((float) $taxableValue, 0), 2);

        if ($taxableValue <= 0) {
            return;
        }

        $taxAmount = round(max((float) $taxAmount, 0), 2);
        $gstRate = $gstRate === null || $gstRate === ''
            ? ($taxableValue > 0 ? round(($taxAmount / $taxableValue) * 100, 2) : 0.0)
            : round(max((float) $gstRate, 0), 2);

        [$cgstAmount, $sgstAmount, $igstAmount] = $this->resolveTaxSplit(
            gstType: $gstType,
            taxAmount: $taxAmount,
        );

        $this->addGroup(
            $groups,
            code: $this->normaliseCode($code),
            gstRate: $gstRate,
            taxableValue: $taxableValue,
            cgstAmount: $cgstAmount,
            sgstAmount: $sgstAmount,
            igstAmount: $igstAmount,
        );
    }

    private function addGroup(
        array &$groups,
        string $code,
        float $gstRate,
        float $taxableValue,
        float $cgstAmount,
        float $sgstAmount,
        float $igstAmount,
    ): void {
        $key = $code . '|' . number_format($gstRate, 2, '.', '');

        if (! isset($groups[$key])) {
            $groups[$key] = [
                'hsn_sac_code' => $code,
                'gst_rate' => round($gstRate, 2),
                'cgst_rate' => round($gstRate / 2, 2),
                'sgst_rate' => round($gstRate - ($gstRate / 2), 2),
                'igst_rate' => round($gstRate, 2),
                'taxable_value' => 0.0,
                'cgst_amount' => 0.0,
                'sgst_amount' => 0.0,
                'igst_amount' => 0.0,
                'total_tax' => 0.0,
            ];
        }

        $groups[$key]['taxable_value'] += $taxableValue;
        $groups[$key]['cgst_amount'] += $cgstAmount;
        $groups[$key]['sgst_amount'] += $sgstAmount;
        $groups[$key]['igst_amount'] += $igstAmount;
        $groups[$key]['total_tax'] += $cgstAmount + $sgstAmount + $igstAmount;
    }

    private function resolveItemHsnSacCode(InvoiceItem $item, ?Model $orderItem = null): string
    {
        $product = $orderItem ? $this->relatedModel($orderItem, 'product') : null;
        $hsnCode = $product ? $this->relatedModel($product, 'hsnCode') : null;

        return $this->normaliseCode(
            $item->hsn_sac_code
                ?? $orderItem?->getAttribute('hsn_sac_code')
                ?? $hsnCode?->getAttribute('code')
        );
    }

    private function resolveItemGstRate(InvoiceItem $item, float $taxableValue, float $taxAmount, ?Model $orderItem = null): float
    {
        $product = $orderItem ? $this->relatedModel($orderItem, 'product') : null;
        $hsnCode = $product ? $this->relatedModel($product, 'hsnCode') : null;

        $candidates = [
            $item->getAttribute('gst_rate'),
            $orderItem?->getAttribute('gst_rate'),
            $hsnCode?->getAttribute('gst_rate'),
            $product?->getAttribute('gst_rate'),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== null && $candidate !== '' && is_numeric($candidate)) {
                return round(max((float) $candidate, 0), 2);
            }
        }

        return $taxableValue > 0
            ? round(($taxAmount / $taxableValue) * 100, 2)
            : 0.0;
    }

    private function resolveTaxSplit(
        string $gstType,
        float $taxAmount,
        mixed $cgstAmount = null,
        mixed $sgstAmount = null,
        mixed $igstAmount = null,
    ): array {
        if ($gstType === 'inter_state') {
            $storedIgst = round(max((float) ($igstAmount ?? 0), 0), 2);
            $igst = $storedIgst > 0 || $taxAmount <= 0 ? $storedIgst : $taxAmount;

            return [0.0, 0.0, $igst];
        }

        $storedCgst = round(max((float) ($cgstAmount ?? 0), 0), 2);
        $storedSgst = round(max((float) ($sgstAmount ?? 0), 0), 2);

        if (($storedCgst + $storedSgst) > 0 || $taxAmount <= 0) {
            return [$storedCgst, $storedSgst, 0.0];
        }

        $cgst = round($taxAmount / 2, 2);

        return [$cgst, round($taxAmount - $cgst, 2), 0.0];
    }

    private function expectedTaxAmounts(Invoice $invoice, string $gstType): array
    {
        $order = $this->relatedModel($invoice, 'order');
        $cgst = round(max((float) ($invoice->getAttribute('cgst_amount') ?? $order?->getAttribute('cgst_amount') ?? 0), 0), 2);
        $sgst = round(max((float) ($invoice->getAttribute('sgst_amount') ?? $order?->getAttribute('sgst_amount') ?? 0), 0), 2);
        $igst = round(max((float) ($invoice->getAttribute('igst_amount') ?? $order?->getAttribute('igst_amount') ?? 0), 0), 2);
        $taxTotal = round(max((float) ($invoice->getAttribute('tax_total') ?? $order?->getAttribute('tax_total') ?? 0), 0), 2);

        if ($gstType === 'inter_state') {
            if ($igst <= 0 && $taxTotal > 0) {
                $igst = $taxTotal;
            }

            return [
                'cgst_amount' => 0.0,
                'sgst_amount' => 0.0,
                'igst_amount' => $igst,
                'total_tax' => $taxTotal > 0 ? $taxTotal : $igst,
            ];
        }

        if (($cgst + $sgst) <= 0 && $taxTotal > 0) {
            $cgst = round($taxTotal / 2, 2);
            $sgst = round($taxTotal - $cgst, 2);
        }

        return [
            'cgst_amount' => $cgst,
            'sgst_amount' => $sgst,
            'igst_amount' => 0.0,
            'total_tax' => $taxTotal > 0 ? $taxTotal : round($cgst + $sgst, 2),
        ];
    }

    /**
     * Make only tiny rounding corrections. A larger mismatch is left visible
     * rather than silently moving tax between HSN/SAC groups.
     */
    private function reconcileRounding(array &$rows, array &$totals, array $expected, string $gstType): void
    {
        if ($rows === []) {
            return;
        }

        $lastIndex = array_key_last($rows);
        $fields = $gstType === 'inter_state'
            ? ['igst_amount']
            : ['cgst_amount', 'sgst_amount'];

        foreach ($fields as $field) {
            $difference = round((float) ($expected[$field] ?? 0) - (float) ($totals[$field] ?? 0), 2);

            if (abs($difference) < 0.005 || abs($difference) > 0.05) {
                continue;
            }

            $rows[$lastIndex][$field] = round(max((float) $rows[$lastIndex][$field] + $difference, 0), 2);
            $rows[$lastIndex]['total_tax'] = round(
                (float) $rows[$lastIndex]['cgst_amount']
                    + (float) $rows[$lastIndex]['sgst_amount']
                    + (float) $rows[$lastIndex]['igst_amount'],
                2
            );
            $totals[$field] = round((float) $totals[$field] + $difference, 2);
        }

        $totals['total_tax'] = round(
            (float) $totals['cgst_amount']
                + (float) $totals['sgst_amount']
                + (float) $totals['igst_amount'],
            2
        );
    }

    /**
     * Keep this service usable as a pure unit-tested calculator. When a model is
     * created in a PHPUnit unit test there is no Laravel database resolver, so
     * touching an unloaded Eloquent relation would fail before any calculation
     * happens. Use already-loaded relations first and only lazy-load persisted
     * models when the application/database resolver is available.
     */
    private function relatedModel(Model $model, string $relation): ?Model
    {
        if ($model->relationLoaded($relation)) {
            $related = $model->getRelation($relation);

            return $related instanceof Model ? $related : null;
        }

        if (! $model->exists || Model::getConnectionResolver() === null) {
            return null;
        }

        try {
            $related = $model->{$relation};

            return $related instanceof Model ? $related : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function invoiceItems(Invoice $invoice): iterable
    {
        if ($invoice->relationLoaded('items')) {
            return $invoice->getRelation('items') ?? [];
        }

        if (! $invoice->exists || Model::getConnectionResolver() === null) {
            return [];
        }

        try {
            return $invoice->items;
        } catch (Throwable) {
            return [];
        }
    }

    private function resolveGstType(Invoice $invoice, ?string $gstType): string
    {
        $order = $this->relatedModel($invoice, 'order');

        $candidate = strtolower(trim((string) (
            $gstType
                ?? $invoice->getAttribute('gst_type')
                ?? $order?->getAttribute('gst_type')
        )));

        if (in_array($candidate, ['inter_state', 'inter-state', 'interstate'], true)) {
            return 'inter_state';
        }

        if (in_array($candidate, ['intra_state', 'intra-state', 'intrastate'], true)) {
            return 'intra_state';
        }

        // Older invoices may not have a saved GST mode. Infer it from the
        // historical split amounts rather than ever displaying both modes.
        $igstAmount = (float) (
            $invoice->getAttribute('igst_amount')
                ?? $order?->getAttribute('igst_amount')
                ?? 0
        );
        $cgstAmount = (float) (
            $invoice->getAttribute('cgst_amount')
                ?? $order?->getAttribute('cgst_amount')
                ?? 0
        );
        $sgstAmount = (float) (
            $invoice->getAttribute('sgst_amount')
                ?? $order?->getAttribute('sgst_amount')
                ?? 0
        );

        return $igstAmount > 0 && $cgstAmount <= 0 && $sgstAmount <= 0
            ? 'inter_state'
            : 'intra_state';
    }


    private function serviceChargeFallbacks(): array
    {
        if (! function_exists('app')) {
            return [];
        }

        try {
            return app(DeliveryTaxSettingsService::class)->current();
        } catch (Throwable) {
            return [];
        }
    }

    private function normaliseCode(mixed $code): string
    {
        $code = trim((string) $code);

        return $code !== '' ? $code : '-';
    }
}
