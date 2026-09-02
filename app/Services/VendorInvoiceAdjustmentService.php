<?php

namespace App\Services;

use App\Models\User;
use App\Models\VendorInvoice;
use App\Models\VendorInvoiceAdjustment;
use App\Models\VendorInvoiceAdjustmentItem;
use App\Models\VendorReturn;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VendorInvoiceAdjustmentService
{
    public function __construct(
        private readonly VendorInvoiceBalanceService $balances,
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $lines
     */
    public function createFinancialDraft(
        VendorInvoice $invoice,
        User $actor,
        string $direction,
        array $header,
        array $lines,
        ?VendorReturn $linkedReturn = null,
    ): VendorInvoiceAdjustment {
        if (! in_array($direction, [VendorInvoiceAdjustment::DIRECTION_CREDIT, VendorInvoiceAdjustment::DIRECTION_DEBIT], true)) {
            throw ValidationException::withMessages(['direction' => 'Choose a credit note or debit note.']);
        }

        if ((string) $invoice->status === 'cancelled') {
            throw ValidationException::withMessages(['invoice' => 'A cancelled vendor invoice cannot be adjusted.']);
        }

        $sign = $direction === VendorInvoiceAdjustment::DIRECTION_CREDIT ? -1 : 1;
        $subtotalMagnitude = round((float) collect($lines)->sum(fn (array $line) => abs((float) ($line['subtotal_amount'] ?? 0))), 2);
        $taxMagnitude = round((float) collect($lines)->sum(fn (array $line) => abs((float) ($line['tax_amount'] ?? 0))), 2);

        if ($subtotalMagnitude + $taxMagnitude <= 0.005) {
            throw ValidationException::withMessages([
                'lines' => 'Enter at least one taxable-value or tax adjustment.',
            ]);
        }

        $documentNumber = $this->nullableTrim($header['supplier_document_number'] ?? null);
        $this->assertSupplierDocumentAvailable($invoice, $documentNumber);

        return DB::transaction(function () use ($invoice, $actor, $direction, $header, $lines, $linkedReturn, $sign, $subtotalMagnitude, $taxMagnitude, $documentNumber) {
            $lockedInvoice = VendorInvoice::query()->lockForUpdate()->findOrFail($invoice->id);

            $adjustment = VendorInvoiceAdjustment::create([
                'vendor_invoice_id' => $lockedInvoice->id,
                'adjustment_number' => $this->temporaryAdjustmentNumber(),
                'type' => $direction === VendorInvoiceAdjustment::DIRECTION_CREDIT
                    ? VendorInvoiceAdjustment::TYPE_CREDIT_NOTE
                    : VendorInvoiceAdjustment::TYPE_DEBIT_NOTE,
                'direction' => $direction,
                'status' => VendorInvoiceAdjustment::STATUS_DRAFT,
                'supplier_document_number' => $documentNumber,
                'supplier_document_date' => $header['supplier_document_date'] ?? null,
                'reason' => trim((string) ($header['reason'] ?? '')),
                'notes' => $this->nullableTrim($header['notes'] ?? null),
                'subtotal_delta' => round($sign * $subtotalMagnitude, 2),
                'tax_delta' => round($sign * $taxMagnitude, 2),
                'total_delta' => round($sign * ($subtotalMagnitude + $taxMagnitude), 2),
                'affects_stock' => false,
                'meta' => array_filter([
                    'linked_vendor_return_id' => $linkedReturn?->id,
                    'created_from' => $linkedReturn ? 'pending_vendor_return' : 'manual_adjustment',
                ], fn ($value) => $value !== null),
                'created_by_id' => $actor->id,
            ]);

            $adjustment->adjustment_number = $this->adjustmentNumber($adjustment);
            $adjustment->save();

            foreach ($lines as $line) {
                $subtotal = round(abs((float) ($line['subtotal_amount'] ?? 0)), 2);
                $tax = round(abs((float) ($line['tax_amount'] ?? 0)), 2);

                if ($subtotal + $tax <= 0.005) {
                    continue;
                }

                VendorInvoiceAdjustmentItem::create([
                    'vendor_invoice_adjustment_id' => $adjustment->id,
                    'vendor_invoice_item_id' => $line['vendor_invoice_item_id'] ?? null,
                    'product_id' => $line['product_id'] ?? null,
                    'product_variant_id' => $line['product_variant_id'] ?? null,
                    'inventory_lot_id' => $line['inventory_lot_id'] ?? null,
                    'quantity_delta' => isset($line['quantity']) ? round($sign * abs((float) $line['quantity']), 3) : null,
                    'weight_delta_kg' => isset($line['weight_kg']) ? round($sign * abs((float) $line['weight_kg']), 3) : null,
                    'piece_count_delta' => isset($line['piece_count']) ? $sign * abs((int) $line['piece_count']) : null,
                    'original_unit_cost' => $line['original_unit_cost'] ?? null,
                    'revised_unit_cost' => $line['revised_unit_cost'] ?? null,
                    'subtotal_delta' => round($sign * $subtotal, 2),
                    'tax_delta' => round($sign * $tax, 2),
                    'total_delta' => round($sign * ($subtotal + $tax), 2),
                    'affects_stock' => false,
                    'meta' => $line['meta'] ?? null,
                ]);
            }

            return $adjustment->fresh(['items.invoiceItem.product', 'items.invoiceItem.productVariant', 'creator']);
        }, 3);
    }

    public function post(VendorInvoiceAdjustment $adjustment, User $actor): VendorInvoiceAdjustment
    {
        return DB::transaction(function () use ($adjustment, $actor) {
            $locked = VendorInvoiceAdjustment::query()
                ->lockForUpdate()
                ->findOrFail($adjustment->id);

            if (! $locked->isDraft()) {
                throw ValidationException::withMessages(['adjustment' => 'This adjustment has already been posted.']);
            }

            $invoice = VendorInvoice::query()->lockForUpdate()->findOrFail($locked->vendor_invoice_id);

            if ((string) $invoice->status === 'cancelled') {
                throw ValidationException::withMessages(['adjustment' => 'A cancelled invoice cannot be adjusted.']);
            }

            $this->assertSupplierDocumentAvailable(
                invoice: $invoice,
                documentNumber: $locked->supplier_document_number,
                ignoreAdjustmentId: $locked->id,
            );

            $current = $this->balances->summary($invoice);
            $newAdjustedTotal = round($current['adjusted_total'] + (float) $locked->total_delta, 2);

            if ($newAdjustedTotal < -0.005) {
                throw ValidationException::withMessages([
                    'adjustment' => 'This credit would reduce the adjusted invoice total below zero.',
                ]);
            }

            $linkedReturnId = (int) data_get($locked->meta, 'linked_vendor_return_id', 0);
            $linkedReturn = null;

            if ($linkedReturnId > 0) {
                $linkedReturn = VendorReturn::query()->lockForUpdate()->find($linkedReturnId);

                if (! $linkedReturn || (int) $linkedReturn->vendor_invoice_id !== (int) $invoice->id) {
                    throw ValidationException::withMessages(['adjustment' => 'The linked vendor return is no longer available.']);
                }

                if ($linkedReturn->status !== VendorReturn::STATUS_CREDIT_PENDING || $linkedReturn->supplier_credit_adjustment_id) {
                    throw ValidationException::withMessages(['adjustment' => 'The linked vendor return is no longer awaiting a credit note.']);
                }
            }

            $locked->status = VendorInvoiceAdjustment::STATUS_POSTED;
            $locked->posted_by_id = $actor->id;
            $locked->posted_at = now();
            $locked->save();

            if ($linkedReturn) {
                $linkedReturn->supplier_credit_adjustment_id = $locked->id;
                $linkedReturn->supplier_credit_note_number = $locked->supplier_document_number;
                $linkedReturn->supplier_credit_note_date = $locked->supplier_document_date;
                $linkedReturn->credit_note_received = true;
                $linkedReturn->status = VendorReturn::STATUS_CREDITED;
                $linkedReturn->save();
            }

            $this->balances->syncStatus($invoice->fresh());

            return $locked->fresh([
                'items.invoiceItem.product',
                'items.invoiceItem.productVariant',
                'creator',
                'postedBy',
                'vendorReturn',
            ]);
        }, 3);
    }

    public function deleteDraft(VendorInvoiceAdjustment $adjustment): void
    {
        DB::transaction(function () use ($adjustment) {
            $locked = VendorInvoiceAdjustment::query()->lockForUpdate()->findOrFail($adjustment->id);

            if (! $locked->isDraft()) {
                throw ValidationException::withMessages(['adjustment' => 'Posted adjustments cannot be deleted.']);
            }

            $locked->delete();
        });
    }

    public function reversePostedAdjustment(VendorInvoiceAdjustment $adjustment, User $actor, string $reason): VendorInvoiceAdjustment
    {
        return DB::transaction(function () use ($adjustment, $actor, $reason) {
            $locked = VendorInvoiceAdjustment::query()
                ->with('reversal')
                ->lockForUpdate()
                ->findOrFail($adjustment->id);

            if (! $locked->isPosted()) {
                throw ValidationException::withMessages(['adjustment' => 'Only posted adjustments can be reversed.']);
            }

            if ($locked->affects_stock || in_array($locked->type, [
                VendorInvoiceAdjustment::TYPE_FULL_REVERSAL,
                VendorInvoiceAdjustment::TYPE_PURCHASE_RETURN_CREDIT,
                VendorInvoiceAdjustment::TYPE_METADATA_CORRECTION,
                VendorInvoiceAdjustment::TYPE_ADJUSTMENT_REVERSAL,
            ], true)) {
                throw ValidationException::withMessages([
                    'adjustment' => 'This adjustment cannot be reversed from this action because it is linked to stock or audit history.',
                ]);
            }

            if ($locked->reversal()->exists()) {
                throw ValidationException::withMessages(['adjustment' => 'This adjustment has already been reversed.']);
            }

            $invoice = VendorInvoice::query()->lockForUpdate()->findOrFail($locked->vendor_invoice_id);

            $reversal = VendorInvoiceAdjustment::create([
                'vendor_invoice_id' => $invoice->id,
                'adjustment_number' => $this->temporaryAdjustmentNumber(),
                'type' => VendorInvoiceAdjustment::TYPE_ADJUSTMENT_REVERSAL,
                'direction' => $locked->direction === VendorInvoiceAdjustment::DIRECTION_CREDIT
                    ? VendorInvoiceAdjustment::DIRECTION_DEBIT
                    : VendorInvoiceAdjustment::DIRECTION_CREDIT,
                'status' => VendorInvoiceAdjustment::STATUS_POSTED,
                'reason' => trim($reason),
                'notes' => "Reverses {$locked->adjustment_number}.",
                'subtotal_delta' => round(-1 * (float) $locked->subtotal_delta, 2),
                'tax_delta' => round(-1 * (float) $locked->tax_delta, 2),
                'total_delta' => round(-1 * (float) $locked->total_delta, 2),
                'affects_stock' => false,
                'reverses_adjustment_id' => $locked->id,
                'meta' => ['reversed_adjustment_number' => $locked->adjustment_number],
                'created_by_id' => $actor->id,
                'posted_by_id' => $actor->id,
                'posted_at' => now(),
            ]);

            $reversal->adjustment_number = $this->adjustmentNumber($reversal);
            $reversal->save();

            foreach ($locked->items as $item) {
                VendorInvoiceAdjustmentItem::create([
                    'vendor_invoice_adjustment_id' => $reversal->id,
                    'vendor_invoice_item_id' => $item->vendor_invoice_item_id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'inventory_lot_id' => $item->inventory_lot_id,
                    'quantity_delta' => $item->quantity_delta !== null ? round(-1 * (float) $item->quantity_delta, 3) : null,
                    'weight_delta_kg' => $item->weight_delta_kg !== null ? round(-1 * (float) $item->weight_delta_kg, 3) : null,
                    'piece_count_delta' => $item->piece_count_delta !== null ? -1 * (int) $item->piece_count_delta : null,
                    'original_unit_cost' => $item->original_unit_cost,
                    'revised_unit_cost' => $item->revised_unit_cost,
                    'subtotal_delta' => round(-1 * (float) $item->subtotal_delta, 2),
                    'tax_delta' => round(-1 * (float) $item->tax_delta, 2),
                    'total_delta' => round(-1 * (float) $item->total_delta, 2),
                    'affects_stock' => false,
                    'meta' => ['reverses_adjustment_item_id' => $item->id],
                ]);
            }

            $this->balances->syncStatus($invoice->fresh());

            return $reversal->fresh(['items', 'creator', 'postedBy', 'reversesAdjustment']);
        }, 3);
    }

    public function recordMetadataCorrection(VendorInvoice $invoice, User $actor, array $before, array $after, string $reason): VendorInvoiceAdjustment
    {
        return DB::transaction(function () use ($invoice, $actor, $before, $after, $reason) {
            $adjustment = VendorInvoiceAdjustment::create([
                'vendor_invoice_id' => $invoice->id,
                'adjustment_number' => $this->temporaryAdjustmentNumber(),
                'type' => VendorInvoiceAdjustment::TYPE_METADATA_CORRECTION,
                'direction' => VendorInvoiceAdjustment::DIRECTION_NEUTRAL,
                'status' => VendorInvoiceAdjustment::STATUS_POSTED,
                'reason' => trim($reason),
                'subtotal_delta' => 0,
                'tax_delta' => 0,
                'total_delta' => 0,
                'affects_stock' => false,
                'meta' => [
                    'before' => $before,
                    'after' => $after,
                ],
                'created_by_id' => $actor->id,
                'posted_by_id' => $actor->id,
                'posted_at' => now(),
            ]);

            $adjustment->adjustment_number = $this->adjustmentNumber($adjustment);
            $adjustment->save();

            return $adjustment;
        });
    }

    /**
     * @param array<int, array<string, mixed>> $lines
     */
    public function createPostedStockLinkedCredit(
        VendorInvoice $invoice,
        User $actor,
        VendorReturn $vendorReturn,
        string $type,
        string $reason,
        ?string $documentNumber,
        mixed $documentDate,
        array $lines,
        float $subtotal,
        float $tax,
    ): VendorInvoiceAdjustment {
        $normalizedDocumentNumber = $this->nullableTrim($documentNumber);
        $this->assertSupplierDocumentAvailable($invoice, $normalizedDocumentNumber);

        $adjustment = VendorInvoiceAdjustment::create([
            'vendor_invoice_id' => $invoice->id,
            'adjustment_number' => $this->temporaryAdjustmentNumber(),
            'type' => $type,
            'direction' => VendorInvoiceAdjustment::DIRECTION_CREDIT,
            'status' => VendorInvoiceAdjustment::STATUS_POSTED,
            'supplier_document_number' => $normalizedDocumentNumber,
            'supplier_document_date' => $documentDate,
            'reason' => trim($reason),
            'notes' => "Linked to vendor return {$vendorReturn->return_number}.",
            'subtotal_delta' => round(-1 * abs($subtotal), 2),
            'tax_delta' => round(-1 * abs($tax), 2),
            'total_delta' => round(-1 * abs($subtotal + $tax), 2),
            'affects_stock' => true,
            'meta' => ['linked_vendor_return_id' => $vendorReturn->id],
            'created_by_id' => $actor->id,
            'posted_by_id' => $actor->id,
            'posted_at' => now(),
        ]);

        $adjustment->adjustment_number = $this->adjustmentNumber($adjustment);
        $adjustment->save();

        foreach ($lines as $line) {
            VendorInvoiceAdjustmentItem::create([
                'vendor_invoice_adjustment_id' => $adjustment->id,
                'vendor_invoice_item_id' => $line['vendor_invoice_item_id'] ?? null,
                'product_id' => $line['product_id'] ?? null,
                'product_variant_id' => $line['product_variant_id'] ?? null,
                'inventory_lot_id' => $line['inventory_lot_id'] ?? null,
                'quantity_delta' => isset($line['quantity']) ? round(-1 * abs((float) $line['quantity']), 3) : null,
                'weight_delta_kg' => isset($line['weight_kg']) ? round(-1 * abs((float) $line['weight_kg']), 3) : null,
                'piece_count_delta' => isset($line['piece_count']) ? -1 * abs((int) $line['piece_count']) : null,
                'original_unit_cost' => $line['original_unit_cost'] ?? null,
                'subtotal_delta' => round(-1 * abs((float) ($line['subtotal_amount'] ?? 0)), 2),
                'tax_delta' => round(-1 * abs((float) ($line['tax_amount'] ?? 0)), 2),
                'total_delta' => round(-1 * abs((float) ($line['total_amount'] ?? 0)), 2),
                'affects_stock' => true,
                'meta' => ['vendor_return_item_id' => $line['vendor_return_item_id'] ?? null],
            ]);
        }

        return $adjustment;
    }

    public function adjustmentNumber(VendorInvoiceAdjustment $adjustment): string
    {
        return 'VIA-' . now()->format('ymd') . '-' . str_pad((string) $adjustment->id, 6, '0', STR_PAD_LEFT);
    }

    private function assertSupplierDocumentAvailable(
        VendorInvoice $invoice,
        ?string $documentNumber,
        ?int $ignoreAdjustmentId = null,
    ): void {
        if ($documentNumber === null) {
            return;
        }

        $exists = VendorInvoiceAdjustment::query()
            ->where('supplier_document_number', $documentNumber)
            ->when($ignoreAdjustmentId !== null, fn ($query) => $query->where('id', '!=', $ignoreAdjustmentId))
            ->whereHas('invoice', fn ($query) => $query->where('vendor_id', $invoice->vendor_id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'supplier_document_number' => 'This supplier document number is already recorded for the same vendor.',
            ]);
        }
    }

    private function temporaryAdjustmentNumber(): string
    {
        return 'TMP-' . bin2hex(random_bytes(12));
    }

    private function nullableTrim(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
