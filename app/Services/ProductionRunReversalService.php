<?php

namespace App\Services;

use App\Models\InventoryLot;
use App\Models\InventoryPack;
use App\Models\InventoryPiece;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductionRun;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ProductionRunReversalService
{
    private const FLOAT_TOLERANCE = 0.0005;

    /**
     * Return a read-only assessment for the Production Run detail screen.
     * The same checks are repeated under row locks before a reversal is committed.
     */
    public function assess(ProductionRun $run): array
    {
        $run->loadMissing([
            'inputs.inventoryLot.product',
            'inputs.inventoryLot.productVariant',
            'inputs.product',
            'inputs.productVariant',
            'outputs.inventoryLot.product',
            'outputs.inventoryLot.productVariant',
            'outputs.inventoryLot.pieces',
            'outputs.product',
            'outputs.productVariant',
            'reversedBy',
        ]);

        $blockers = collect();

        $auditColumns = ['reversed_at', 'reversed_by_id', 'reversal_reason', 'reversal_snapshot_json'];
        if (collect($auditColumns)->contains(fn (string $column) => ! Schema::hasColumn('production_runs', $column))) {
            $blockers->push('Run the pending production reversal migration before using this action.');
        }

        if ((string) $run->status !== 'completed') {
            $message = (string) $run->status === 'reversed'
                ? 'This production run has already been reversed.'
                : 'Only completed production runs can be reversed.';
            $blockers->push($message);
        }

        if ($run->inputs->isEmpty()) {
            $blockers->push('The production run has no recorded input rows.');
        }

        if ($run->outputs->isEmpty()) {
            $blockers->push('The production run has no recorded output rows.');
        }

        $sourceRows = $this->sourcePreviewRows($run, $blockers);
        $outputRows = $this->outputPreviewRows($run, $blockers);

        $this->checkDownstreamUsage($run, $blockers);
        $this->checkReservations($run, $blockers);
        $this->checkProductStockCapacity($run, $blockers);

        return [
            'can_reverse' => $blockers->isEmpty(),
            'blockers' => $blockers->unique()->values()->all(),
            'sources' => $sourceRows,
            'outputs' => $outputRows,
            'stock_deltas' => $this->productStockDeltas($run)->values()->all(),
        ];
    }

    /**
     * Reverse a completed production run as one atomic, audited transaction.
     */
    public function reverse(ProductionRun $run, User $actor, string $reason): ProductionRun
    {
        return DB::transaction(function () use ($run, $actor, $reason) {
            $lockedRun = ProductionRun::query()
                ->lockForUpdate()
                ->findOrFail($run->getKey());

            $lockedRun->load([
                'inputs.inventoryLot.product',
                'inputs.inventoryLot.productVariant',
                'inputs.product',
                'inputs.productVariant',
                'outputs.inventoryLot.product',
                'outputs.inventoryLot.productVariant',
                'outputs.inventoryLot.pieces',
                'outputs.product',
                'outputs.productVariant',
            ]);

            $this->lockAffectedRows($lockedRun);

            // Reload relationships after the row locks are acquired.
            $lockedRun->unsetRelations();
            $lockedRun->load([
                'inputs.inventoryLot.product',
                'inputs.inventoryLot.productVariant',
                'inputs.product',
                'inputs.productVariant',
                'outputs.inventoryLot.product',
                'outputs.inventoryLot.productVariant',
                'outputs.inventoryLot.pieces',
                'outputs.product',
                'outputs.productVariant',
            ]);

            $assessment = $this->assess($lockedRun);

            if (! $assessment['can_reverse']) {
                throw ValidationException::withMessages([
                    'reversal_reason' => $assessment['blockers'],
                ]);
            }

            $this->cancelOutputInventory($lockedRun, $actor);
            $this->restoreSourceInventory($lockedRun, $actor);
            $this->applyProductStockDeltas($lockedRun, $actor);

            $snapshot = [
                'version' => 1,
                'reversed_at' => now()->toIso8601String(),
                'reversed_by_id' => $actor->id,
                'sources' => $assessment['sources'],
                'outputs' => $assessment['outputs'],
                'stock_deltas' => $assessment['stock_deltas'],
            ];

            $lockedRun->status = 'reversed';
            $lockedRun->reversed_at = now();
            $lockedRun->reversed_by_id = $actor->id;
            $lockedRun->reversal_reason = trim($reason);
            $lockedRun->reversal_snapshot_json = $snapshot;
            $lockedRun->updated_by_id = $actor->id;
            $lockedRun->save();

            return $lockedRun->fresh([
                'inputs.inventoryLot.product',
                'outputs.inventoryLot.product',
                'reversedBy',
            ]);
        }, 3);
    }

    private function sourcePreviewRows(ProductionRun $run, Collection $blockers): array
    {
        $rows = [];

        foreach ($run->inputs as $input) {
            $lot = $input->inventoryLot;

            if (! $lot) {
                $blockers->push("Input row #{$input->id} no longer has its source inventory lot.");
                continue;
            }

            $consumedWeight = round((float) ($input->consumed_weight_kg ?? 0), 3);
            $consumedQuantity = round((float) ($input->consumed_quantity ?? 0), 3);
            $consumedPieces = (int) ($input->consumed_piece_count ?? 0);

            $newWeight = round((float) ($lot->available_weight_kg ?? 0) + $consumedWeight, 3);
            $newQuantity = round((float) ($lot->available_quantity ?? 0) + $consumedQuantity, 3);
            $newPieceCount = (int) ($lot->available_piece_count ?? 0) + $consumedPieces;

            $totalWeight = $lot->total_weight_kg !== null ? (float) $lot->total_weight_kg : null;
            $receivedQuantity = $lot->received_quantity !== null ? (float) $lot->received_quantity : null;
            $pieceCount = $lot->piece_count !== null ? (int) $lot->piece_count : null;

            if ($totalWeight !== null && $totalWeight > 0 && $newWeight > $totalWeight + self::FLOAT_TOLERANCE) {
                $blockers->push("Source lot {$this->lotLabel($lot)} cannot accept the restored weight because its current balance has been changed.");
            }

            if ($receivedQuantity !== null && $receivedQuantity > 0 && $newQuantity > $receivedQuantity + self::FLOAT_TOLERANCE) {
                $blockers->push("Source lot {$this->lotLabel($lot)} cannot accept the restored quantity because its current balance has been changed.");
            }

            if ($consumedPieces > 0) {
                $consumedPieceRows = InventoryPiece::query()
                    ->where('inventory_lot_id', $lot->id)
                    ->where('consumed_in_production_run_id', $run->id)
                    ->get();

                if ($consumedPieceRows->count() !== $consumedPieces) {
                    $blockers->push("Source lot {$this->lotLabel($lot)} no longer has all {$consumedPieces} pieces recorded against this run.");
                }

                if ($consumedPieceRows->contains(fn (InventoryPiece $piece) => (string) $piece->status !== 'consumed' || $piece->sold_order_item_id !== null)) {
                    $blockers->push("One or more source pieces from {$this->lotLabel($lot)} have been modified after this run.");
                }

                if ($pieceCount !== null && $newPieceCount > $pieceCount) {
                    $blockers->push("Source lot {$this->lotLabel($lot)} cannot accept the restored pieces because its current balance has been changed.");
                }
            }

            if (in_array((string) $lot->lot_status, ['cancelled', 'hold'], true)) {
                $blockers->push("Source lot {$this->lotLabel($lot)} is currently {$lot->lot_status} and must be reviewed before reversal.");
            }

            $rows[] = [
                'input_id' => $input->id,
                'lot_id' => $lot->id,
                'lot_code' => $lot->lot_code,
                'product' => $input->product?->name ?? $lot->product?->name ?? 'Unknown product',
                'variant' => $input->productVariant?->name ?? $lot->productVariant?->name,
                'restore_quantity' => $consumedQuantity,
                'restore_weight_kg' => $consumedWeight,
                'restore_piece_count' => $consumedPieces,
                'available_quantity_before' => round((float) ($lot->available_quantity ?? 0), 3),
                'available_weight_before' => round((float) ($lot->available_weight_kg ?? 0), 3),
                'available_piece_count_before' => (int) ($lot->available_piece_count ?? 0),
                'available_quantity_after' => $newQuantity,
                'available_weight_after' => $newWeight,
                'available_piece_count_after' => $newPieceCount,
            ];
        }

        return $rows;
    }

    private function outputPreviewRows(ProductionRun $run, Collection $blockers): array
    {
        $rows = [];

        foreach ($run->outputs as $output) {
            $lot = $output->inventoryLot;

            if (! $lot) {
                $blockers->push("Output row #{$output->id} no longer has its inventory lot.");
                continue;
            }

            if ((int) $lot->production_run_id !== (int) $run->id) {
                $blockers->push("Output lot {$this->lotLabel($lot)} is no longer linked to this production run.");
            }

            if ((string) $lot->lot_status !== 'available') {
                $blockers->push("Output lot {$this->lotLabel($lot)} is currently {$lot->lot_status}; only untouched available output can be reversed.");
            }

            $producedQuantity = round((float) ($output->produced_quantity ?? 0), 3);
            $producedWeight = round((float) ($output->produced_weight_kg ?? 0), 3);
            $pieceCount = $output->piece_count !== null ? (int) $output->piece_count : null;

            if (! $this->approximatelyEqual($lot->available_quantity, $producedQuantity)) {
                $blockers->push("Output lot {$this->lotLabel($lot)} quantity has changed from {$producedQuantity} to " . round((float) ($lot->available_quantity ?? 0), 3) . '.');
            }

            if (! $this->approximatelyEqual($lot->available_weight_kg, $producedWeight)) {
                $blockers->push("Output lot {$this->lotLabel($lot)} weight has changed from {$producedWeight} kg to " . round((float) ($lot->available_weight_kg ?? 0), 3) . ' kg.');
            }

            if (! $this->approximatelyEqual($lot->received_quantity, $producedQuantity)
                || ! $this->approximatelyEqual($lot->total_weight_kg, $producedWeight)) {
                $blockers->push("Output lot {$this->lotLabel($lot)} totals were adjusted after production.");
            }

            if ($pieceCount !== null) {
                if ((int) ($lot->available_piece_count ?? 0) !== $pieceCount || (int) ($lot->piece_count ?? 0) !== $pieceCount) {
                    $blockers->push("Output lot {$this->lotLabel($lot)} piece balance has changed.");
                }

                // Only piece-mode outputs create one InventoryPiece row per output piece.
                // Qty-mode output can carry an informational piece_count without child rows.
                if ((string) $lot->inward_mode === 'pieces') {
                    $pieces = $lot->pieces;
                    if ($pieces->count() !== $pieceCount) {
                        $blockers->push("Output lot {$this->lotLabel($lot)} no longer has all {$pieceCount} generated piece records.");
                    }

                    if ($pieces->contains(function (InventoryPiece $piece) {
                        return (string) $piece->status !== 'available'
                            || $piece->sold_order_item_id !== null
                            || $piece->consumed_in_production_run_id !== null;
                    })) {
                        $blockers->push("One or more pieces from output lot {$this->lotLabel($lot)} have been sold, reserved, consumed, or modified.");
                    }
                }
            }

            $rows[] = [
                'output_id' => $output->id,
                'lot_id' => $lot->id,
                'lot_code' => $lot->lot_code,
                'product' => $output->product?->name ?? $lot->product?->name ?? 'Unknown product',
                'variant' => $output->productVariant?->name ?? $lot->productVariant?->name,
                'cancel_quantity' => $producedQuantity,
                'cancel_weight_kg' => $producedWeight,
                'cancel_piece_count' => $pieceCount ?? 0,
                'output_stage' => $output->output_stage,
            ];
        }

        return $rows;
    }

    private function checkDownstreamUsage(ProductionRun $run, Collection $blockers): void
    {
        $outputLotIds = $run->outputs
            ->pluck('inventory_lot_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($outputLotIds->isEmpty()) {
            return;
        }

        $childLots = InventoryLot::query()
            ->whereIn('parent_inventory_lot_id', $outputLotIds)
            ->where(function ($query) {
                $query->whereNull('production_run_id')
                    ->orWhereHas('productionRun', fn ($runQuery) => $runQuery->where('status', '!=', 'reversed'));
            })
            ->get(['id', 'lot_code', 'parent_inventory_lot_id']);

        foreach ($childLots as $childLot) {
            $blockers->push("Output lot #{$childLot->parent_inventory_lot_id} has already produced downstream lot " . ($childLot->lot_code ?: "#{$childLot->id}") . '.');
        }

        if (Schema::hasTable('inventory_packs')) {
            $packUsage = InventoryPack::query()
                ->whereIn('source_inventory_lot_id', $outputLotIds)
                ->get(['id', 'pack_code', 'source_inventory_lot_id']);

            foreach ($packUsage as $pack) {
                $blockers->push("Output lot #{$pack->source_inventory_lot_id} has already been used in Transform Stock / repack" . ($pack->pack_code ? " ({$pack->pack_code})" : '') . '.');
            }

            if (InventoryPack::query()->where('production_run_id', $run->id)->exists()) {
                $blockers->push('This run has legacy direct pack records and cannot be reversed automatically.');
            }
        }


        if (Schema::hasTable('stock_movements')) {
            $hasRepackMovement = StockMovement::query()
                ->where('reference_type', 'inventory_repack')
                ->whereIn('reference_id', $outputLotIds)
                ->exists();

            if ($hasRepackMovement) {
                $blockers->push('One or more output lots have recorded Transform Stock / repack movements.');
            }
        }
    }

    private function checkReservations(ProductionRun $run, Collection $blockers): void
    {
        if (! Schema::hasTable('stock_reservations')) {
            return;
        }

        $outputLotIds = $run->outputs
            ->pluck('inventory_lot_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($outputLotIds->isNotEmpty()) {
            $pieceIds = InventoryPiece::query()
                ->whereIn('inventory_lot_id', $outputLotIds)
                ->pluck('id');

            if ($pieceIds->isNotEmpty() && $this->activeReservationQuery()->whereIn('inventory_piece_id', $pieceIds)->exists()) {
                $blockers->push('One or more output pieces are reserved in an active checkout.');
            }
        }

        foreach ($run->outputs->unique(fn ($output) => $output->product_id . ':' . ($output->product_variant_id ?? 'null')) as $output) {
            $query = $this->activeReservationQuery()
                ->whereNull('inventory_piece_id')
                ->where('product_id', $output->product_id);

            if ($output->product_variant_id) {
                $query->where('product_variant_id', $output->product_variant_id);
            } else {
                $query->whereNull('product_variant_id');
            }

            if ($query->exists()) {
                $name = $output->product?->name ?? "Product #{$output->product_id}";
                if ($output->productVariant?->name) {
                    $name .= ' — ' . $output->productVariant->name;
                }
                $blockers->push("{$name} currently has active reserved stock.");
            }
        }
    }

    private function checkProductStockCapacity(ProductionRun $run, Collection $blockers): void
    {
        foreach ($this->productStockDeltas($run) as $delta) {
            $product = Product::query()->find($delta['product_id']);
            if (! $product) {
                $blockers->push("Product #{$delta['product_id']} no longer exists.");
                continue;
            }

            $stockAfter = round((float) ($product->stock_quantity ?? 0) + (float) $delta['delta'], 3);
            if ($stockAfter < -self::FLOAT_TOLERANCE) {
                $blockers->push("{$product->name} does not have enough current stock to remove the untouched production output.");
            }
        }
    }

    private function productStockDeltas(ProductionRun $run): Collection
    {
        $deltas = collect();

        foreach ($run->inputs as $input) {
            $quantity = $input->consumed_quantity !== null
                ? (float) $input->consumed_quantity
                : (float) ($input->consumed_weight_kg ?? 0);

            $this->mergeStockDelta(
                $deltas,
                (int) $input->product_id,
                $input->product?->name ?? "Product #{$input->product_id}",
                round($quantity, 3),
                'source_restore'
            );
        }

        foreach ($run->outputs as $output) {
            $this->mergeStockDelta(
                $deltas,
                (int) $output->product_id,
                $output->product?->name ?? "Product #{$output->product_id}",
                -1 * round((float) ($output->produced_quantity ?? 0), 3),
                'output_cancel'
            );
        }

        return $deltas->map(function (array $row) {
            $row['delta'] = round((float) $row['delta'], 3);
            return $row;
        });
    }

    private function mergeStockDelta(Collection $deltas, int $productId, string $productName, float $delta, string $reason): void
    {
        $existing = $deltas->get($productId, [
            'product_id' => $productId,
            'product' => $productName,
            'delta' => 0.0,
            'components' => [],
        ]);

        $existing['delta'] = round((float) $existing['delta'] + $delta, 3);
        $existing['components'][] = [
            'reason' => $reason,
            'quantity' => round($delta, 3),
        ];

        $deltas->put($productId, $existing);
    }

    private function lockAffectedRows(ProductionRun $run): void
    {
        $inputLotIds = $run->inputs->pluck('inventory_lot_id')->filter()->map(fn ($id) => (int) $id);
        $outputLotIds = $run->outputs->pluck('inventory_lot_id')->filter()->map(fn ($id) => (int) $id);
        $allLotIds = $inputLotIds->merge($outputLotIds)->unique()->values();

        if ($allLotIds->isNotEmpty()) {
            InventoryLot::query()->whereIn('id', $allLotIds)->orderBy('id')->lockForUpdate()->get();
            InventoryPiece::query()->whereIn('inventory_lot_id', $allLotIds)->orderBy('id')->lockForUpdate()->get();
        }

        InventoryPiece::query()
            ->where('consumed_in_production_run_id', $run->id)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $productIds = $run->inputs->pluck('product_id')
            ->merge($run->outputs->pluck('product_id'))
            ->filter()
            ->unique()
            ->values();

        if ($productIds->isNotEmpty()) {
            Product::query()->whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get();
        }

        $variantIds = $run->inputs->pluck('product_variant_id')
            ->merge($run->outputs->pluck('product_variant_id'))
            ->filter()
            ->unique()
            ->values();

        if ($variantIds->isNotEmpty()) {
            ProductVariant::query()->whereIn('id', $variantIds)->orderBy('id')->lockForUpdate()->get();
        }

        if (Schema::hasTable('inventory_packs') && $outputLotIds->isNotEmpty()) {
            InventoryPack::query()
                ->whereIn('source_inventory_lot_id', $outputLotIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
        }

        if (Schema::hasTable('stock_reservations')) {
            StockReservation::query()
                ->whereIn('product_id', $run->outputs->pluck('product_id')->filter()->unique())
                ->where('status', 'reserved')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
        }
    }

    private function cancelOutputInventory(ProductionRun $run, User $actor): void
    {
        foreach ($run->outputs as $output) {
            $lot = InventoryLot::query()->lockForUpdate()->findOrFail($output->inventory_lot_id);

            InventoryPiece::query()
                ->where('inventory_lot_id', $lot->id)
                ->update([
                    'status' => 'cancelled',
                    'available_weight_kg' => 0,
                    'updated_at' => now(),
                ]);

            $lot->available_quantity = 0;
            $lot->available_weight_kg = 0;
            $lot->available_piece_count = 0;

            if (Schema::hasColumn('inventory_lots', 'available_pack_count')) {
                $lot->available_pack_count = 0;
            }

            $lot->lot_status = 'cancelled';
            $lot->is_saleable = false;
            $lot->can_repack = false;
            $lot->updated_by_id = $actor->id;
            $lot->notes = $this->appendAuditNote($lot->notes, "Cancelled by reversal of production run {$run->run_number}.");
            $lot->save();
        }
    }

    private function restoreSourceInventory(ProductionRun $run, User $actor): void
    {
        foreach ($run->inputs as $input) {
            $lot = InventoryLot::query()->lockForUpdate()->findOrFail($input->inventory_lot_id);

            $lot->available_weight_kg = round(
                (float) ($lot->available_weight_kg ?? 0) + (float) ($input->consumed_weight_kg ?? 0),
                3
            );

            if ($input->consumed_quantity !== null) {
                $lot->available_quantity = round(
                    (float) ($lot->available_quantity ?? 0) + (float) $input->consumed_quantity,
                    3
                );
            }

            if ($input->consumed_piece_count !== null) {
                $lot->available_piece_count = (int) ($lot->available_piece_count ?? 0) + (int) $input->consumed_piece_count;

                $pieces = InventoryPiece::query()
                    ->where('inventory_lot_id', $lot->id)
                    ->where('consumed_in_production_run_id', $run->id)
                    ->lockForUpdate()
                    ->get();

                foreach ($pieces as $piece) {
                    $piece->status = 'available';
                    $piece->consumed_in_production_run_id = null;
                    if (Schema::hasColumn('inventory_pieces', 'available_weight_kg')) {
                        $piece->available_weight_kg = $piece->weight_kg;
                    }
                    $piece->save();
                }
            }

            if ((float) ($lot->available_weight_kg ?? 0) > self::FLOAT_TOLERANCE
                || (float) ($lot->available_quantity ?? 0) > self::FLOAT_TOLERANCE
                || (int) ($lot->available_piece_count ?? 0) > 0) {
                $lot->lot_status = 'available';
            }

            $lot->updated_by_id = $actor->id;
            $lot->notes = $this->appendAuditNote($lot->notes, "Stock restored by reversal of production run {$run->run_number}.");
            $lot->save();
        }
    }

    private function applyProductStockDeltas(ProductionRun $run, User $actor): void
    {
        foreach ($this->productStockDeltas($run) as $delta) {
            $product = Product::query()->lockForUpdate()->findOrFail($delta['product_id']);
            $before = round((float) ($product->stock_quantity ?? 0), 3);
            $after = max(0, round($before + (float) $delta['delta'], 3));

            $product->stock_quantity = $after;
            $product->updated_by_id = $actor->id;
            $product->save();

            $this->recordReversalMovement(
                $run,
                $product,
                (float) $delta['delta'],
                $before,
                $after
            );
        }
    }

    private function recordReversalMovement(ProductionRun $run, Product $product, float $delta, float $before, float $after): void
    {
        if (! Schema::hasTable('stock_movements') || abs($delta) <= self::FLOAT_TOLERANCE) {
            return;
        }

        $attributes = [
            'product_id' => $product->id,
            'product_variant_id' => null,
            'vendor_id' => null,
            'quantity' => round($delta, 2),
            'movement_type' => 'adjustment',
            'reference_type' => 'production_run_reversal',
            'reference_id' => $run->id,
            'cost_price' => null,
            'notes' => "Production run {$run->run_number} reversed. Product stock {$before} → {$after}.",
            'created_at' => now(),
        ];

        StockMovement::create(array_filter(
            $attributes,
            fn ($value, string $column) => Schema::hasColumn('stock_movements', $column),
            ARRAY_FILTER_USE_BOTH
        ));
    }

    private function activeReservationQuery()
    {
        return StockReservation::query()
            ->where('status', 'reserved')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    private function approximatelyEqual(mixed $actual, mixed $expected): bool
    {
        return abs((float) ($actual ?? 0) - (float) ($expected ?? 0)) <= self::FLOAT_TOLERANCE;
    }

    private function lotLabel(InventoryLot $lot): string
    {
        return $lot->lot_code ?: "#{$lot->id}";
    }

    private function appendAuditNote(?string $current, string $note): string
    {
        $current = trim((string) $current);
        $stamp = now()->format('d M Y H:i');
        $line = "[{$stamp}] {$note}";

        return $current === '' ? $line : $current . PHP_EOL . $line;
    }
}
