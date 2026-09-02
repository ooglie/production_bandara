<?php

namespace App\Services;

use App\Models\InventoryLot;
use App\Models\InventoryPack;
use App\Models\InventoryPiece;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductionRunInput;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Models\User;
use App\Models\VendorInvoice;
use App\Models\VendorInvoiceAdjustment;
use App\Models\VendorInvoiceItem;
use App\Models\VendorReturn;
use App\Models\VendorReturnItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class VendorReturnService
{
    private const TOLERANCE = 0.0005;

    public function __construct(
        private readonly VendorInvoiceAdjustmentService $adjustments,
        private readonly VendorInvoiceBalanceService $balances,
    ) {
    }

    /**
     * Read-only return options for the create screen.
     *
     * @return array<int, array<string, mixed>>
     */
    public function options(VendorInvoice $invoice): array
    {
        $invoice->loadMissing([
            'items.product',
            'items.productVariant',
            'items.inventoryLot.pieces',
        ]);

        return $invoice->items
            ->sortBy('id')
            ->mapWithKeys(fn (VendorInvoiceItem $item) => [$item->id => $this->optionForItem($item)])
            ->all();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createDraft(VendorInvoice $invoice, User $actor, array $data): VendorReturn
    {
        if ((string) $invoice->status === 'cancelled') {
            throw ValidationException::withMessages(['invoice' => 'A cancelled vendor invoice cannot receive another return.']);
        }

        $options = $this->options($invoice);
        $requestedItems = is_array($data['items'] ?? null) ? $data['items'] : [];
        $lines = [];

        foreach ($options as $invoiceItemId => $option) {
            $submitted = $requestedItems[$invoiceItemId] ?? $requestedItems[(string) $invoiceItemId] ?? [];
            if (! is_array($submitted)) {
                $submitted = [];
            }

            $line = $this->draftLineFromSubmitted($option, $submitted);
            if ($line !== null) {
                $lines[] = $line;
            }
        }

        if ($lines === []) {
            throw ValidationException::withMessages([
                'items' => 'Select at least one available piece, pack, full piece, quantity or weight to return.',
            ]);
        }

        $creditNoteReceived = (bool) ($data['credit_note_received'] ?? false);
        $creditNumber = $this->nullableTrim($data['supplier_credit_note_number'] ?? null);
        $creditDate = $data['supplier_credit_note_date'] ?? null;

        if ($creditNoteReceived && ($creditNumber === null || ! $creditDate)) {
            throw ValidationException::withMessages([
                'supplier_credit_note_number' => 'Enter the supplier credit-note number and date when the credit note has already been received.',
            ]);
        }

        return DB::transaction(function () use ($invoice, $actor, $data, $lines, $creditNoteReceived, $creditNumber, $creditDate) {
            $lockedInvoice = VendorInvoice::query()->lockForUpdate()->findOrFail($invoice->id);

            $vendorReturn = VendorReturn::create([
                'vendor_invoice_id' => $lockedInvoice->id,
                'return_number' => $this->temporaryReturnNumber(),
                'return_date' => $data['return_date'],
                'status' => VendorReturn::STATUS_DRAFT,
                'reference_number' => $this->nullableTrim($data['reference_number'] ?? null),
                'reason' => trim((string) $data['reason']),
                'notes' => $this->nullableTrim($data['notes'] ?? null),
                'expected_subtotal' => round((float) collect($lines)->sum('subtotal_amount'), 2),
                'expected_tax' => round((float) collect($lines)->sum('tax_amount'), 2),
                'expected_total' => round((float) collect($lines)->sum('total_amount'), 2),
                'credit_note_received' => $creditNoteReceived,
                'supplier_credit_note_number' => $creditNumber,
                'supplier_credit_note_date' => $creditDate,
                'meta' => ['version' => 1],
                'created_by_id' => $actor->id,
            ]);

            $vendorReturn->return_number = $this->returnNumber($vendorReturn);
            $vendorReturn->save();

            foreach ($lines as $line) {
                VendorReturnItem::create(array_merge($line, [
                    'vendor_return_id' => $vendorReturn->id,
                ]));
            }

            return $vendorReturn->fresh([
                'invoice.vendor',
                'items.invoiceItem.product',
                'items.invoiceItem.productVariant',
                'items.inventoryLot',
                'creator',
            ]);
        }, 3);
    }

    public function deleteDraft(VendorReturn $vendorReturn): void
    {
        DB::transaction(function () use ($vendorReturn) {
            $locked = VendorReturn::query()->lockForUpdate()->findOrFail($vendorReturn->id);

            if (! $locked->isDraft()) {
                throw ValidationException::withMessages(['vendor_return' => 'Posted vendor returns cannot be deleted.']);
            }

            $locked->delete();
        });
    }

    public function post(VendorReturn $vendorReturn, User $actor): VendorReturn
    {
        return DB::transaction(function () use ($vendorReturn, $actor) {
            $lockedReturn = VendorReturn::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($vendorReturn->id);

            if (! $lockedReturn->isDraft()) {
                throw ValidationException::withMessages(['vendor_return' => 'This vendor return has already been posted.']);
            }

            $invoice = VendorInvoice::query()->lockForUpdate()->findOrFail($lockedReturn->vendor_invoice_id);

            if ((string) $invoice->status === 'cancelled') {
                throw ValidationException::withMessages(['vendor_return' => 'A cancelled vendor invoice cannot receive another return.']);
            }

            $this->postReturnItems($invoice, $lockedReturn, $actor);

            $lockedReturn->posted_by_id = $actor->id;
            $lockedReturn->posted_at = now();
            $lockedReturn->status = $lockedReturn->credit_note_received
                ? VendorReturn::STATUS_CREDITED
                : VendorReturn::STATUS_CREDIT_PENDING;
            $lockedReturn->save();

            if ($lockedReturn->credit_note_received) {
                $credit = $this->createCreditForPostedReturn(
                    invoice: $invoice,
                    vendorReturn: $lockedReturn,
                    actor: $actor,
                    type: VendorInvoiceAdjustment::TYPE_PURCHASE_RETURN_CREDIT,
                );

                $lockedReturn->supplier_credit_adjustment_id = $credit->id;
                $lockedReturn->save();
                $this->balances->syncStatus($invoice->fresh());
            }

            return $lockedReturn->fresh([
                'invoice.vendor',
                'items.invoiceItem.product',
                'items.invoiceItem.productVariant',
                'items.inventoryLot',
                'supplierCreditAdjustment',
                'creator',
                'postedBy',
            ]);
        }, 3);
    }

    /**
     * Read-only assessment for a complete invoice reversal.
     *
     * @return array{can_reverse: bool, blockers: array<int, string>, lines: array<int, array<string, mixed>>}
     */
    public function assessFullReversal(VendorInvoice $invoice): array
    {
        $invoice->loadMissing([
            'payments',
            'adjustments',
            'vendorReturns',
            'items.product',
            'items.productVariant',
            'items.inventoryLot.pieces',
        ]);

        $blockers = collect();

        if ((string) $invoice->status === 'cancelled') {
            $blockers->push('This vendor invoice is already cancelled or reversed.');
        }

        if ((float) $invoice->payments->sum('amount') > 0.005) {
            $blockers->push('Payments are already recorded against this invoice. Reverse or correct those payments before reversing the invoice.');
        }

        $draftAdjustments = $invoice->adjustments
            ->where('status', VendorInvoiceAdjustment::STATUS_DRAFT);

        if ($draftAdjustments->isNotEmpty()) {
            $blockers->push('Delete or post the existing adjustment draft before reversing the full invoice.');
        }

        $financialAdjustments = $invoice->adjustments
            ->where('status', VendorInvoiceAdjustment::STATUS_POSTED)
            ->reject(fn (VendorInvoiceAdjustment $adjustment) => $adjustment->type === VendorInvoiceAdjustment::TYPE_METADATA_CORRECTION);

        if ($financialAdjustments->isNotEmpty()) {
            $blockers->push('Financial adjustments already exist against this invoice. Reverse or resolve them before reversing the full invoice.');
        }

        if ($invoice->vendorReturns->where('status', VendorReturn::STATUS_DRAFT)->isNotEmpty()) {
            $blockers->push('Delete or post the existing purchase-return draft before reversing the full invoice.');
        }

        if ($invoice->vendorReturns->where('status', '!=', VendorReturn::STATUS_DRAFT)->isNotEmpty()) {
            $blockers->push('A posted purchase return already exists against this invoice.');
        }

        $lines = [];
        foreach ($invoice->items->sortBy('id') as $item) {
            $option = $this->optionForItem($item);
            $lot = $option['lot'];

            if (! $lot) {
                $blockers->push("Invoice item #{$item->id} has no linked inventory lot.");
                continue;
            }

            if (ProductionRunInput::query()->where('inventory_lot_id', $lot->id)->exists()) {
                $blockers->push("Lot {$this->lotLabel($lot)} has already been used in Transform Stock / production.");
            }

            if (InventoryLot::query()
                ->where(function ($query) use ($lot) {
                    $query->where('parent_inventory_lot_id', $lot->id)
                        ->orWhere(function ($root) use ($lot) {
                            $root->where('root_inventory_lot_id', $lot->id)
                                ->where('id', '!=', $lot->id);
                        });
                })
                ->exists()) {
                $blockers->push("Lot {$this->lotLabel($lot)} has downstream inventory lots.");
            }

            $line = $this->fullReversalLine($option, $blockers);
            if ($line !== null) {
                $lines[] = $line;
            }
        }

        return [
            'can_reverse' => $blockers->isEmpty() && $lines !== [],
            'blockers' => $blockers->unique()->values()->all(),
            'lines' => $lines,
        ];
    }

    public function reverseInvoice(VendorInvoice $invoice, User $actor, string $reason): VendorInvoice
    {
        return DB::transaction(function () use ($invoice, $actor, $reason) {
            $lockedInvoice = VendorInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $assessment = $this->assessFullReversal($lockedInvoice);

            if (! $assessment['can_reverse']) {
                throw ValidationException::withMessages(['reversal_reason' => $assessment['blockers']]);
            }

            $vendorReturn = VendorReturn::create([
                'vendor_invoice_id' => $lockedInvoice->id,
                'return_number' => $this->temporaryReturnNumber(),
                'return_date' => now()->toDateString(),
                'status' => VendorReturn::STATUS_DRAFT,
                'reason' => trim($reason),
                'notes' => 'Created automatically by full vendor-invoice reversal.',
                'expected_subtotal' => round((float) $lockedInvoice->subtotal, 2),
                'expected_tax' => round((float) $lockedInvoice->tax_amount, 2),
                'expected_total' => round((float) $lockedInvoice->total_amount, 2),
                'credit_note_received' => true,
                'supplier_credit_note_number' => 'REV-' . (string) $lockedInvoice->invoice_number,
                'supplier_credit_note_date' => now()->toDateString(),
                'meta' => ['version' => 1, 'full_invoice_reversal' => true],
                'created_by_id' => $actor->id,
            ]);

            $vendorReturn->return_number = $this->returnNumber($vendorReturn);
            $vendorReturn->save();

            foreach ($assessment['lines'] as $line) {
                VendorReturnItem::create(array_merge($line, [
                    'vendor_return_id' => $vendorReturn->id,
                ]));
            }

            $vendorReturn->load('items');
            $this->postReturnItems($lockedInvoice, $vendorReturn, $actor);

            $vendorReturn->posted_by_id = $actor->id;
            $vendorReturn->posted_at = now();
            $vendorReturn->status = VendorReturn::STATUS_CREDITED;
            $vendorReturn->save();

            $credit = $this->createCreditForPostedReturn(
                invoice: $lockedInvoice,
                vendorReturn: $vendorReturn,
                actor: $actor,
                type: VendorInvoiceAdjustment::TYPE_FULL_REVERSAL,
            );

            $vendorReturn->supplier_credit_adjustment_id = $credit->id;
            $vendorReturn->save();

            $lockedInvoice->status = 'cancelled';
            $lockedInvoice->save();

            return $lockedInvoice->fresh([
                'vendor',
                'items',
                'adjustments',
                'vendorReturns',
            ]);
        }, 3);
    }

    /**
     * @return array<string, mixed>
     */
    private function optionForItem(VendorInvoiceItem $item): array
    {
        $item->loadMissing(['product', 'productVariant', 'inventoryLot.pieces']);

        $lot = $item->inventoryLot;
        $blockers = collect();

        if (! $lot) {
            $blockers->push('No linked inventory lot was found.');
        }

        $allPieces = $lot
            ? InventoryPiece::query()->where('inventory_lot_id', $lot->id)->orderBy('piece_no')->get()
            : collect();

        $returnablePieces = $allPieces->filter(fn (InventoryPiece $piece) => $this->pieceIsReturnable($piece));

        $allPacks = $lot && Schema::hasTable('inventory_packs')
            ? InventoryPack::query()
                ->where('source_inventory_lot_id', $lot->id)
                ->whereNull('production_run_id')
                ->orderBy('pack_no')
                ->get()
            : collect();

        $returnablePacks = $allPacks->filter(fn (InventoryPack $pack) => $this->packIsReturnable($pack));

        $receiptType = $this->normalizeReceiptType((string) ($item->receipt_type ?? $lot?->inward_mode ?? 'quantity'));
        $isSingleWholePiece = $allPieces->isEmpty()
            && $receiptType === 'pieces_weight'
            && $this->approximatelyEqual((float) ($item->quantity ?? 0), 1.0);

        $mode = match (true) {
            $allPieces->isNotEmpty() => 'pieces',
            $allPacks->isNotEmpty() => 'packs',
            $isSingleWholePiece => 'whole_piece',
            $receiptType === 'pieces_weight' => 'weight',
            default => 'quantity',
        };

        $targetAvailable = $this->returnableStockTargetQuantity($item);
        $lotQuantity = round(max(0, (float) ($lot?->available_quantity ?? 0)), 3);
        $lotWeight = round(max(0, (float) ($lot?->available_weight_kg ?? 0)), 3);

        $maxQuantity = match ($mode) {
            'pieces' => (float) $returnablePieces->count(),
            'packs' => round((float) $returnablePacks->sum(fn (InventoryPack $pack) => (float) ($pack->available_pack_quantity ?? $pack->pack_quantity ?? 1)), 3),
            'quantity' => round(min($lotQuantity, $targetAvailable), 3),
            default => 0.0,
        };

        $pieceWeight = round((float) $returnablePieces->sum(fn (InventoryPiece $piece) => (float) ($piece->available_weight_kg ?? $piece->weight_kg ?? 0)), 3);
        $maxWeight = match ($mode) {
            'pieces' => round(min($pieceWeight, $targetAvailable), 3),
            'whole_piece', 'weight' => round(min($lotWeight > 0 ? $lotWeight : $lotQuantity, $targetAvailable), 3),
            default => 0.0,
        };

        if ($lot && in_array((string) $lot->lot_status, ['cancelled', 'hold', 'returned'], true)) {
            $blockers->push("Lot status is {$lot->lot_status}.");
        }

        if ($mode === 'pieces' && $returnablePieces->isEmpty()) {
            $blockers->push('No available unreserved pieces remain from this invoice item.');
        }

        if ($mode === 'packs' && $returnablePacks->isEmpty()) {
            $blockers->push('No available direct packs remain from this invoice item.');
        }

        if ($mode === 'whole_piece') {
            $originalWeight = $this->originalBase($item);
            $activeReserved = $this->activeReservedQuantityForTarget($item);

            if ($originalWeight <= self::TOLERANCE) {
                $blockers->push('The original full-piece weight is missing.');
            }

            if (! $this->approximatelyEqual($lotWeight, $originalWeight)) {
                $blockers->push('This full piece is no longer completely available. A full piece cannot be returned by partial weight.');
            }

            if ($activeReserved > self::TOLERANCE) {
                $blockers->push('This product currently has active checkout reservations. Release or complete them before returning the full piece.');
            }

            if ($lot && ProductionRunInput::query()->where('inventory_lot_id', $lot->id)->exists()) {
                $blockers->push('This full piece has already been used in a production run.');
            }

            if ($lot && InventoryLot::query()
                ->where(function ($query) use ($lot) {
                    $query->where('parent_inventory_lot_id', $lot->id)
                        ->orWhere(function ($root) use ($lot) {
                            $root->where('root_inventory_lot_id', $lot->id)
                                ->where('id', '!=', $lot->id);
                        });
                })
                ->exists()) {
                $blockers->push('This full piece has downstream or transformed inventory and cannot be returned as the original piece.');
            }
        }

        if (in_array($mode, ['whole_piece', 'weight'], true) && $maxWeight <= self::TOLERANCE) {
            $blockers->push('No returnable weight remains from this invoice item.');
        }

        if ($mode === 'quantity' && $maxQuantity <= self::TOLERANCE) {
            $blockers->push('No returnable quantity remains from this invoice item.');
        }

        return [
            'item' => $item,
            'lot' => $lot,
            'mode' => $mode,
            'receipt_type' => $receiptType,
            'is_whole_piece' => $mode === 'whole_piece',
            'pieces' => $allPieces,
            'returnable_pieces' => $returnablePieces->values(),
            'packs' => $allPacks,
            'returnable_packs' => $returnablePacks->values(),
            'max_quantity' => $maxQuantity,
            'max_weight_kg' => $maxWeight,
            'max_piece_count' => $returnablePieces->count(),
            'target_available' => $targetAvailable,
            'blockers' => $blockers->unique()->values()->all(),
        ];
    }

    /**
     * @param array<string, mixed> $option
     * @param array<string, mixed> $submitted
     * @return array<string, mixed>|null
     */
    private function draftLineFromSubmitted(array $option, array $submitted): ?array
    {
        /** @var VendorInvoiceItem $item */
        $item = $option['item'];
        /** @var InventoryLot|null $lot */
        $lot = $option['lot'];
        $mode = (string) $option['mode'];

        if (! $lot) {
            return null;
        }

        $quantity = 0.0;
        $weight = 0.0;
        $pieceCount = 0;
        $pieceIds = [];
        $packIds = [];

        if ($mode === 'pieces') {
            $pieceIds = collect($submitted['piece_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            if ($pieceIds === []) {
                return null;
            }

            $allowed = $option['returnable_pieces']->keyBy('id');
            $selected = collect($pieceIds)->map(function (int $id) use ($allowed) {
                $piece = $allowed->get($id);
                if (! $piece) {
                    throw ValidationException::withMessages(['items' => "Inventory piece #{$id} is no longer available for return."]);
                }
                return $piece;
            });

            $quantity = (float) $selected->count();
            $pieceCount = $selected->count();
            $weight = round((float) $selected->sum(fn (InventoryPiece $piece) => (float) ($piece->available_weight_kg ?? $piece->weight_kg ?? 0)), 3);

            if ($weight > (float) $option['target_available'] + self::TOLERANCE) {
                throw ValidationException::withMessages(['items' => 'Selected pieces exceed the stock currently free from checkout reservations.']);
            }
        } elseif ($mode === 'packs') {
            $packIds = collect($submitted['pack_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            if ($packIds === []) {
                return null;
            }

            $allowed = $option['returnable_packs']->keyBy('id');
            $selected = collect($packIds)->map(function (int $id) use ($allowed) {
                $pack = $allowed->get($id);
                if (! $pack) {
                    throw ValidationException::withMessages(['items' => "Inventory pack #{$id} is no longer available for return."]);
                }
                return $pack;
            });

            $quantity = round((float) $selected->sum(fn (InventoryPack $pack) => (float) ($pack->available_pack_quantity ?? $pack->pack_quantity ?? 1)), 3);
            $weight = round((float) $selected->sum(fn (InventoryPack $pack) => (float) ($pack->actual_weight_kg ?? $pack->total_weight_kg ?? 0)), 3);
            $pieceCount = (int) round((float) $selected->sum(fn (InventoryPack $pack) => (float) ($pack->available_pieces ?? $pack->total_pieces ?? 0)));

            if ($quantity > (float) $option['target_available'] + self::TOLERANCE) {
                throw ValidationException::withMessages(['items' => 'Selected packs exceed the stock currently free from checkout reservations.']);
            }
        } elseif ($mode === 'whole_piece') {
            if (! (bool) ($submitted['whole_piece'] ?? false)) {
                return null;
            }

            $originalWeight = $this->originalBase($item);
            $availableWeight = round(max(0, (float) ($lot->available_weight_kg ?? $lot->available_quantity ?? 0)), 3);

            if ($originalWeight <= self::TOLERANCE || ! $this->approximatelyEqual($availableWeight, $originalWeight)) {
                throw ValidationException::withMessages([
                    'items' => 'The full piece is no longer completely available. It cannot be returned by entering or reducing a partial weight.',
                ]);
            }

            if ($this->activeReservedQuantityForTarget($item) > self::TOLERANCE) {
                throw ValidationException::withMessages([
                    'items' => 'The product has an active stock reservation. The full piece cannot be returned until that reservation is released.',
                ]);
            }

            if (ProductionRunInput::query()->where('inventory_lot_id', $lot->id)->exists()
                || InventoryLot::query()
                    ->where(function ($query) use ($lot) {
                        $query->where('parent_inventory_lot_id', $lot->id)
                            ->orWhere(function ($root) use ($lot) {
                                $root->where('root_inventory_lot_id', $lot->id)
                                    ->where('id', '!=', $lot->id);
                            });
                    })
                    ->exists()) {
                throw ValidationException::withMessages([
                    'items' => 'The full piece has already been transformed or used in production and cannot be returned as the original piece.',
                ]);
            }

            $quantity = 1.0;
            $weight = $originalWeight;
            $pieceCount = 1;
        } elseif ($mode === 'weight') {
            $weight = round(max(0, (float) ($submitted['weight_kg'] ?? 0)), 3);
            $pieceCount = max(0, (int) ($submitted['piece_count'] ?? 0));

            if ($weight <= self::TOLERANCE) {
                return null;
            }

            if ($weight > (float) $option['max_weight_kg'] + self::TOLERANCE) {
                throw ValidationException::withMessages(['items' => 'Entered return weight exceeds the available unreserved lot weight.']);
            }

            $quantity = $weight;
        } else {
            $quantity = round(max(0, (float) ($submitted['quantity'] ?? 0)), 3);

            if ($quantity <= self::TOLERANCE) {
                return null;
            }

            if ($quantity > (float) $option['max_quantity'] + self::TOLERANCE) {
                throw ValidationException::withMessages(['items' => 'Entered return quantity exceeds the available unreserved lot quantity.']);
            }
        }

        $returnedBase = $option['receipt_type'] === 'pieces_weight' ? $weight : $quantity;
        $amounts = $this->financialAmounts($item, $returnedBase);

        return [
            'vendor_invoice_item_id' => $item->id,
            'product_id' => $item->product_id,
            'product_variant_id' => $item->product_variant_id,
            'inventory_lot_id' => $lot->id,
            'return_mode' => $mode,
            'quantity' => round($quantity, 3),
            'weight_kg' => round($weight, 3),
            'piece_count' => $pieceCount,
            'subtotal_amount' => $amounts['subtotal'],
            'tax_amount' => $amounts['tax'],
            'total_amount' => $amounts['total'],
            'inventory_piece_ids' => $pieceIds !== [] ? $pieceIds : null,
            'inventory_pack_ids' => $packIds !== [] ? $packIds : null,
            'meta' => [
                'receipt_type' => $option['receipt_type'],
                'original_base' => $amounts['original_base'],
                'returned_base' => $returnedBase,
            ],
        ];
    }

    private function postReturnItems(VendorInvoice $invoice, VendorReturn $vendorReturn, User $actor): void
    {
        $items = VendorReturnItem::query()
            ->where('vendor_return_id', $vendorReturn->id)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['vendor_return' => 'This vendor return has no items.']);
        }

        foreach ($items as $returnItem) {
            $invoiceItem = VendorInvoiceItem::query()
                ->with(['product', 'productVariant'])
                ->where('vendor_invoice_id', $invoice->id)
                ->lockForUpdate()
                ->findOrFail($returnItem->vendor_invoice_item_id);

            $lot = InventoryLot::query()->lockForUpdate()->findOrFail($returnItem->inventory_lot_id);

            if ((int) $lot->vendor_invoice_id !== (int) $invoice->id
                || (int) ($lot->vendor_invoice_item_id ?? 0) !== (int) $invoiceItem->id) {
                throw ValidationException::withMessages(['vendor_return' => 'One return line no longer matches the original invoice lot.']);
            }

            $movementQuantity = match ($returnItem->return_mode) {
                'pieces' => $this->applyPieceReturn($invoiceItem, $lot, $returnItem, $actor),
                'packs' => $this->applyPackReturn($invoiceItem, $lot, $returnItem, $actor),
                'whole_piece' => $this->applyWholePieceReturn($invoiceItem, $lot, $returnItem, $actor),
                'weight' => $this->applyWeightReturn($invoiceItem, $lot, $returnItem, $actor),
                default => $this->applyQuantityReturn($invoiceItem, $lot, $returnItem, $actor),
            };

            $this->writeReturnMovement(
                invoice: $invoice,
                vendorReturn: $vendorReturn,
                invoiceItem: $invoiceItem,
                quantity: $movementQuantity,
                notes: "Vendor return {$vendorReturn->return_number}: {$vendorReturn->reason}",
            );
        }
    }

    private function applyPieceReturn(VendorInvoiceItem $invoiceItem, InventoryLot $lot, VendorReturnItem $returnItem, User $actor): float
    {
        $pieceIds = collect($returnItem->inventory_piece_ids ?? [])->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($pieceIds->isEmpty()) {
            throw ValidationException::withMessages(['vendor_return' => 'The piece return no longer has selected inventory pieces.']);
        }

        $pieces = InventoryPiece::query()
            ->whereIn('id', $pieceIds)
            ->where('inventory_lot_id', $lot->id)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($pieces->count() !== $pieceIds->count()) {
            throw ValidationException::withMessages(['vendor_return' => 'One or more selected pieces no longer belong to the original lot.']);
        }

        foreach ($pieces as $piece) {
            if (! $this->pieceIsReturnable($piece)) {
                throw ValidationException::withMessages(['vendor_return' => "Piece {$piece->label} is sold, consumed, held, or reserved and cannot be returned."]);
            }
        }

        $weight = round((float) $pieces->sum(fn (InventoryPiece $piece) => (float) ($piece->available_weight_kg ?? $piece->weight_kg ?? 0)), 3);
        if (! $this->approximatelyEqual($weight, (float) $returnItem->weight_kg)) {
            throw ValidationException::withMessages(['vendor_return' => 'The selected piece weights changed after the return draft was created.']);
        }

        $this->decreaseStockTarget($invoiceItem, $weight);

        foreach ($pieces as $piece) {
            $piece->status = 'returned';
            if (Schema::hasColumn('inventory_pieces', 'available_weight_kg')) {
                $piece->available_weight_kg = 0;
            }
            if (Schema::hasColumn('inventory_pieces', 'notes')) {
                $piece->notes = $this->appendNote($piece->notes, "Returned to supplier on vendor return #{$returnItem->vendor_return_id}.");
            }
            $piece->save();
        }

        $this->decreaseLotBalances(
            lot: $lot,
            quantity: $this->normalizeReceiptType((string) $invoiceItem->receipt_type) === 'pieces_weight' ? $weight : (float) $pieces->count(),
            weight: $weight,
            pieceCount: $pieces->count(),
            packCount: 0,
            actor: $actor,
        );

        return $weight;
    }

    private function applyPackReturn(VendorInvoiceItem $invoiceItem, InventoryLot $lot, VendorReturnItem $returnItem, User $actor): float
    {
        $packIds = collect($returnItem->inventory_pack_ids ?? [])->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($packIds->isEmpty()) {
            throw ValidationException::withMessages(['vendor_return' => 'The pack return no longer has selected inventory packs.']);
        }

        $packs = InventoryPack::query()
            ->whereIn('id', $packIds)
            ->where('source_inventory_lot_id', $lot->id)
            ->whereNull('production_run_id')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($packs->count() !== $packIds->count()) {
            throw ValidationException::withMessages(['vendor_return' => 'One or more selected packs no longer belong to the original direct-receipt lot.']);
        }

        foreach ($packs as $pack) {
            if (! $this->packIsReturnable($pack)) {
                throw ValidationException::withMessages(['vendor_return' => "Pack {$pack->pack_code} is sold, consumed, held, or reserved and cannot be returned."]);
            }
        }

        $quantity = round((float) $packs->sum(fn (InventoryPack $pack) => (float) ($pack->available_pack_quantity ?? $pack->pack_quantity ?? 1)), 3);
        if (! $this->approximatelyEqual($quantity, (float) $returnItem->quantity)) {
            throw ValidationException::withMessages(['vendor_return' => 'The selected pack quantity changed after the return draft was created.']);
        }

        $weight = round((float) $packs->sum(fn (InventoryPack $pack) => (float) ($pack->actual_weight_kg ?? $pack->total_weight_kg ?? 0)), 3);
        $pieces = (int) round((float) $packs->sum(fn (InventoryPack $pack) => (float) ($pack->available_pieces ?? $pack->total_pieces ?? 0)));

        $this->decreaseStockTarget($invoiceItem, $quantity);

        foreach ($packs as $pack) {
            $pack->available_pack_quantity = 0;
            if (Schema::hasColumn('inventory_packs', 'available_pieces')) {
                $pack->available_pieces = 0;
            }
            $pack->status = 'returned';
            $pack->reserved_until = null;
            $pack->updated_by_id = $actor->id;
            $pack->notes = $this->appendNote($pack->notes, "Returned to supplier on vendor return #{$returnItem->vendor_return_id}.");
            $pack->save();
        }

        $this->decreaseLotBalances(
            lot: $lot,
            quantity: $quantity,
            weight: $weight,
            pieceCount: $pieces,
            packCount: $packs->count(),
            actor: $actor,
        );

        return $quantity;
    }

    private function applyWholePieceReturn(VendorInvoiceItem $invoiceItem, InventoryLot $lot, VendorReturnItem $returnItem, User $actor): float
    {
        $originalWeight = $this->originalBase($invoiceItem);
        $returnWeight = round((float) $returnItem->weight_kg, 3);
        $availableWeight = round(max(0, (float) ($lot->available_weight_kg ?? $lot->available_quantity ?? 0)), 3);

        if ($originalWeight <= self::TOLERANCE
            || ! $this->approximatelyEqual($returnWeight, $originalWeight)
            || ! $this->approximatelyEqual($availableWeight, $originalWeight)) {
            throw ValidationException::withMessages([
                'vendor_return' => 'The full piece is no longer completely available. A single full piece must be returned in full.',
            ]);
        }

        if ($this->activeReservedQuantityForTarget($invoiceItem) > self::TOLERANCE) {
            throw ValidationException::withMessages([
                'vendor_return' => 'The product currently has an active checkout reservation and cannot be returned as a full piece.',
            ]);
        }

        if (ProductionRunInput::query()->where('inventory_lot_id', $lot->id)->exists()
            || InventoryLot::query()
                ->where(function ($query) use ($lot) {
                    $query->where('parent_inventory_lot_id', $lot->id)
                        ->orWhere(function ($root) use ($lot) {
                            $root->where('root_inventory_lot_id', $lot->id)
                                ->where('id', '!=', $lot->id);
                        });
                })
                ->exists()) {
            throw ValidationException::withMessages([
                'vendor_return' => 'This full piece has been transformed or used in production and cannot be returned as the original piece.',
            ]);
        }

        $this->decreaseStockTarget($invoiceItem, $originalWeight);
        $this->decreaseLotBalances(
            lot: $lot,
            quantity: $originalWeight,
            weight: $originalWeight,
            pieceCount: 1,
            packCount: 0,
            actor: $actor,
        );

        return $originalWeight;
    }

    private function applyWeightReturn(VendorInvoiceItem $invoiceItem, InventoryLot $lot, VendorReturnItem $returnItem, User $actor): float
    {
        $weight = round((float) $returnItem->weight_kg, 3);
        $available = round(max(0, (float) ($lot->available_weight_kg ?? $lot->available_quantity ?? 0)), 3);

        if ($weight <= self::TOLERANCE || $weight > $available + self::TOLERANCE) {
            throw ValidationException::withMessages(['vendor_return' => 'The requested return weight is no longer available in the original lot.']);
        }

        $this->decreaseStockTarget($invoiceItem, $weight);
        $this->decreaseLotBalances(
            lot: $lot,
            quantity: $weight,
            weight: $weight,
            pieceCount: min((int) $returnItem->piece_count, (int) ($lot->available_piece_count ?? 0)),
            packCount: 0,
            actor: $actor,
        );

        return $weight;
    }

    private function applyQuantityReturn(VendorInvoiceItem $invoiceItem, InventoryLot $lot, VendorReturnItem $returnItem, User $actor): float
    {
        $quantity = round((float) $returnItem->quantity, 3);
        $available = round(max(0, (float) ($lot->available_quantity ?? 0)), 3);

        if ($quantity <= self::TOLERANCE || $quantity > $available + self::TOLERANCE) {
            throw ValidationException::withMessages(['vendor_return' => 'The requested return quantity is no longer available in the original lot.']);
        }

        $this->decreaseStockTarget($invoiceItem, $quantity);

        $unitWeight = (float) ($lot->unit_weight_kg ?? 0);
        if ($unitWeight <= 0 && (float) ($lot->received_quantity ?? 0) > 0 && (float) ($lot->total_weight_kg ?? 0) > 0) {
            $unitWeight = (float) $lot->total_weight_kg / (float) $lot->received_quantity;
        }
        $weight = round(max(0, $quantity * $unitWeight), 3);
        $pieceCount = (int) round(max(0, $quantity * (float) ($lot->pieces_per_pack ?? 0)));

        $this->decreaseLotBalances(
            lot: $lot,
            quantity: $quantity,
            weight: $weight,
            pieceCount: $pieceCount,
            packCount: (int) round($quantity),
            actor: $actor,
        );

        return $quantity;
    }

    private function decreaseStockTarget(VendorInvoiceItem $invoiceItem, float $quantity): void
    {
        $quantity = round(max(0, $quantity), 3);
        if ($quantity <= self::TOLERANCE) {
            return;
        }

        $target = $invoiceItem->product_variant_id
            ? ProductVariant::query()->lockForUpdate()->find($invoiceItem->product_variant_id)
            : Product::query()->lockForUpdate()->find($invoiceItem->product_id);

        if (! $target || ! Schema::hasColumn($target->getTable(), 'stock_quantity')) {
            throw ValidationException::withMessages(['vendor_return' => 'The linked product stock target is unavailable.']);
        }

        $physical = round((float) ($target->stock_quantity ?? 0), 3);
        $reserved = $this->activeReservedQuantityForTarget($invoiceItem);
        $free = round(max(0, $physical - $reserved), 3);

        if ($quantity > $free + self::TOLERANCE) {
            throw ValidationException::withMessages([
                'vendor_return' => "Only {$free} units/kg are free from checkout reservations for {$invoiceItem->product?->name}.",
            ]);
        }

        $target->stock_quantity = round(max(0, $physical - $quantity), 3);
        $target->save();

        if ($target instanceof ProductVariant) {
            $this->syncParentProductStockFromVariants((int) $target->product_id);
        }
    }

    private function decreaseLotBalances(InventoryLot $lot, float $quantity, float $weight, int $pieceCount, int $packCount, User $actor): void
    {
        $lot->available_quantity = round(max(0, (float) ($lot->available_quantity ?? 0) - max(0, $quantity)), 3);

        if ($lot->available_weight_kg !== null) {
            $lot->available_weight_kg = round(max(0, (float) $lot->available_weight_kg - max(0, $weight)), 3);
        }

        if ($lot->available_piece_count !== null) {
            $lot->available_piece_count = max(0, (int) $lot->available_piece_count - max(0, $pieceCount));
        }

        if (Schema::hasColumn('inventory_lots', 'available_pack_count') && $lot->available_pack_count !== null) {
            $lot->available_pack_count = max(0, (int) $lot->available_pack_count - max(0, $packCount));
        }

        $lot->updated_by_id = $actor->id;
        $lot->notes = $this->appendNote($lot->notes, 'Stock returned to supplier.');

        $remaining = max(
            (float) ($lot->available_quantity ?? 0),
            (float) ($lot->available_weight_kg ?? 0),
            (float) ($lot->available_piece_count ?? 0),
            (float) ($lot->available_pack_count ?? 0),
        );

        $lot->lot_status = $remaining <= self::TOLERANCE ? 'returned' : 'available';
        $lot->save();
    }

    private function writeReturnMovement(VendorInvoice $invoice, VendorReturn $vendorReturn, VendorInvoiceItem $invoiceItem, float $quantity, string $notes): void
    {
        StockMovement::create([
            'product_id' => $invoiceItem->product_id,
            'product_variant_id' => $invoiceItem->product_variant_id,
            'vendor_id' => $invoice->vendor_id,
            'quantity' => -1 * abs(round($quantity, 2)),
            'movement_type' => 'return',
            'reference_type' => 'vendor_return',
            'reference_id' => $vendorReturn->id,
            'cost_price' => $invoiceItem->unit_cost,
            'notes' => $notes,
            'created_at' => now(),
        ]);
    }

    private function createCreditForPostedReturn(VendorInvoice $invoice, VendorReturn $vendorReturn, User $actor, string $type): VendorInvoiceAdjustment
    {
        $vendorReturn->loadMissing('items.invoiceItem');

        $lines = $vendorReturn->items->map(function (VendorReturnItem $item) {
            return [
                'vendor_return_item_id' => $item->id,
                'vendor_invoice_item_id' => $item->vendor_invoice_item_id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'inventory_lot_id' => $item->inventory_lot_id,
                'quantity' => (float) $item->quantity,
                'weight_kg' => (float) $item->weight_kg,
                'piece_count' => (int) $item->piece_count,
                'original_unit_cost' => $item->invoiceItem?->unit_cost,
                'subtotal_amount' => (float) $item->subtotal_amount,
                'tax_amount' => (float) $item->tax_amount,
                'total_amount' => (float) $item->total_amount,
            ];
        })->all();

        return $this->adjustments->createPostedStockLinkedCredit(
            invoice: $invoice,
            actor: $actor,
            vendorReturn: $vendorReturn,
            type: $type,
            reason: $vendorReturn->reason,
            documentNumber: $vendorReturn->supplier_credit_note_number,
            documentDate: $vendorReturn->supplier_credit_note_date,
            lines: $lines,
            subtotal: (float) $vendorReturn->expected_subtotal,
            tax: (float) $vendorReturn->expected_tax,
        );
    }

    /**
     * @param array<string, mixed> $option
     * @return array<string, mixed>|null
     */
    private function fullReversalLine(array $option, Collection $blockers): ?array
    {
        /** @var VendorInvoiceItem $item */
        $item = $option['item'];
        /** @var InventoryLot|null $lot */
        $lot = $option['lot'];
        if (! $lot) {
            return null;
        }

        $mode = (string) $option['mode'];
        $originalBase = $this->originalBase($item);
        $submitted = [];

        if ($mode === 'pieces') {
            if ($option['pieces']->count() !== $option['returnable_pieces']->count()) {
                $blockers->push("One or more pieces from {$this->lotLabel($lot)} have been sold, consumed, held, or reserved.");
            }

            $weight = round((float) $option['returnable_pieces']->sum(fn (InventoryPiece $piece) => (float) ($piece->available_weight_kg ?? $piece->weight_kg ?? 0)), 3);
            if (! $this->approximatelyEqual($weight, $originalBase)) {
                $blockers->push("Lot {$this->lotLabel($lot)} no longer has its original received weight available.");
            }

            $submitted['piece_ids'] = $option['returnable_pieces']->pluck('id')->all();
        } elseif ($mode === 'packs') {
            if ($option['packs']->count() !== $option['returnable_packs']->count()) {
                $blockers->push("One or more packs from {$this->lotLabel($lot)} have been sold, consumed, held, or reserved.");
            }

            if (! $this->approximatelyEqual((float) $option['max_quantity'], $originalBase)) {
                $blockers->push("Lot {$this->lotLabel($lot)} no longer has its original received quantity available.");
            }

            $submitted['pack_ids'] = $option['returnable_packs']->pluck('id')->all();
        } elseif ($mode === 'whole_piece') {
            if (! $this->approximatelyEqual((float) $option['max_weight_kg'], $originalBase)) {
                $blockers->push("Lot {$this->lotLabel($lot)} no longer has the complete original piece available.");
            }
            $submitted['whole_piece'] = true;
        } elseif ($mode === 'weight') {
            if (! $this->approximatelyEqual((float) $option['max_weight_kg'], $originalBase)) {
                $blockers->push("Lot {$this->lotLabel($lot)} no longer has its original received weight available.");
            }
            $submitted['weight_kg'] = $originalBase;
            $submitted['piece_count'] = (int) ($lot->available_piece_count ?? 0);
        } else {
            if (! $this->approximatelyEqual((float) $option['max_quantity'], $originalBase)) {
                $blockers->push("Lot {$this->lotLabel($lot)} no longer has its original received quantity available.");
            }
            $submitted['quantity'] = $originalBase;
        }

        try {
            return $this->draftLineFromSubmitted($option, $submitted);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $messages) {
                foreach ((array) $messages as $message) {
                    $blockers->push((string) $message);
                }
            }
            return null;
        }
    }

    /**
     * @return array{subtotal: float, tax: float, total: float, original_base: float}
     */
    private function financialAmounts(VendorInvoiceItem $item, float $returnedBase): array
    {
        $originalBase = $this->originalBase($item);
        if ($originalBase <= self::TOLERANCE) {
            throw ValidationException::withMessages(['items' => "Invoice item #{$item->id} has no valid original quantity or weight."]);
        }

        $originalTax = round((float) $item->tax_amount, 2);
        $originalTotal = round((float) $item->total, 2);
        $originalSubtotal = round($originalTotal - $originalTax, 2);

        if ($this->approximatelyEqual($returnedBase, $originalBase)) {
            return [
                'subtotal' => $originalSubtotal,
                'tax' => $originalTax,
                'total' => $originalTotal,
                'original_base' => $originalBase,
            ];
        }

        $ratio = min(1, max(0, $returnedBase / $originalBase));
        $subtotal = round($originalSubtotal * $ratio, 2);
        $tax = round($originalTax * $ratio, 2);

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => round($subtotal + $tax, 2),
            'original_base' => $originalBase,
        ];
    }

    private function originalBase(VendorInvoiceItem $item): float
    {
        return $this->normalizeReceiptType((string) $item->receipt_type) === 'pieces_weight'
            ? round((float) ($item->total_weight_kg ?? 0), 3)
            : round((float) ($item->quantity ?? 0), 3);
    }

    private function returnableStockTargetQuantity(VendorInvoiceItem $item): float
    {
        $target = $item->product_variant_id ? $item->productVariant : $item->product;
        $physical = round(max(0, (float) ($target?->stock_quantity ?? 0)), 3);

        return round(max(0, $physical - $this->activeReservedQuantityForTarget($item)), 3);
    }

    private function activeReservedQuantityForTarget(VendorInvoiceItem $item): float
    {
        if (! Schema::hasTable('stock_reservations')) {
            return 0.0;
        }

        $query = StockReservation::query()
            ->where('status', 'reserved')
            ->where('product_id', $item->product_id)
            ->where(function ($expiry) {
                $expiry->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        if ($item->product_variant_id) {
            $query->where('product_variant_id', $item->product_variant_id);
        } else {
            $query->whereNull('product_variant_id');
        }

        return round((float) $query->sum('quantity'), 3);
    }

    private function pieceIsReturnable(InventoryPiece $piece): bool
    {
        $status = strtolower((string) ($piece->status ?? 'available'));

        if (! in_array($status, ['', 'available'], true)
            || $piece->sold_order_item_id !== null
            || $piece->consumed_in_production_run_id !== null) {
            return false;
        }

        if (! Schema::hasTable('stock_reservations')) {
            return true;
        }

        return ! StockReservation::query()
            ->where('status', 'reserved')
            ->where('inventory_piece_id', $piece->id)
            ->where(function ($expiry) {
                $expiry->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    private function packIsReturnable(InventoryPack $pack): bool
    {
        $status = strtolower((string) ($pack->status ?? 'available'));
        $available = (float) ($pack->available_pack_quantity ?? $pack->pack_quantity ?? 0);
        $reservationActive = $pack->reserved_until && $pack->reserved_until->isFuture();

        return in_array($status, ['', 'available'], true)
            && $available > self::TOLERANCE
            && ! $reservationActive
            && $pack->sold_order_id === null
            && $pack->sold_order_item_id === null;
    }

    private function syncParentProductStockFromVariants(int $productId): void
    {
        $product = Product::query()->lockForUpdate()->find($productId);
        if (! $product) {
            return;
        }

        $sum = ProductVariant::query()
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->sum('stock_quantity');

        $product->type = 'variable';
        $product->manage_stock = true;
        $product->stock_quantity = round((float) $sum, 3);
        $product->save();
    }

    private function normalizeReceiptType(string $type): string
    {
        return match ($type) {
            'bulk_weight', 'pieces', 'pieces_weight' => 'pieces_weight',
            default => 'quantity',
        };
    }

    private function returnNumber(VendorReturn $vendorReturn): string
    {
        return 'VRT-' . now()->format('ymd') . '-' . str_pad((string) $vendorReturn->id, 6, '0', STR_PAD_LEFT);
    }

    private function temporaryReturnNumber(): string
    {
        return 'TMP-' . bin2hex(random_bytes(12));
    }

    private function approximatelyEqual(float $left, float $right): bool
    {
        return abs($left - $right) <= self::TOLERANCE;
    }

    private function lotLabel(InventoryLot $lot): string
    {
        return $lot->lot_code ?: "#{$lot->id}";
    }

    private function appendNote(?string $existing, string $note): string
    {
        $existing = trim((string) $existing);

        return $existing !== '' ? $existing . "\n" . $note : $note;
    }

    private function nullableTrim(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
