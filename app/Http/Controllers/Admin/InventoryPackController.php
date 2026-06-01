<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLot;
use App\Models\InventoryPack;
use App\Models\Product;
use App\Models\ProductSellUnit;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class InventoryPackController extends Controller
{
    public function index(Request $request)
    {
        $packs = InventoryPack::query()
            ->with(['product', 'productVariant', 'sellUnit', 'sourceLot.product', 'soldOrder'])
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.inventory.packs.index', compact('packs'));
    }

    public function create(Request $request)
    {
        $lots = InventoryLot::query()
            ->with(['product.sellUnits', 'productVariant'])
            ->availableForRepack()
            ->orderBy('product_id')
            ->orderBy('expiry_date')
            ->orderBy('received_date')
            ->orderBy('id')
            ->get();

        $sellUnits = ProductSellUnit::query()
            ->with(['product:id,name,sku,type', 'variants:id,product_id,sku,name,product_sell_unit_id,stock_quantity'])
            ->where('is_active', true)
            ->whereIn('unit_type', ['piece', 'pack', 'box'])
            ->orderBy('product_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $recentPacks = InventoryPack::query()
            ->with(['product', 'productVariant', 'sellUnit', 'sourceLot', 'soldOrder'])
            ->latest('id')
            ->limit(12)
            ->get();

        $selectedLotId = $request->integer('source_inventory_lot_id') ?: $request->integer('lot_id') ?: old('source_inventory_lot_id');

        return view('admin.inventory.packs.create', compact('lots', 'sellUnits', 'recentPacks', 'selectedLotId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'source_inventory_lot_id' => ['required', 'integer', 'exists:inventory_lots,id'],
            'product_sell_unit_id' => ['required', 'integer', 'exists:product_sell_units,id'],
            'pack_count' => ['required', 'integer', 'min:1', 'max:10000'],
            'pieces_per_pack' => ['nullable', 'numeric', 'min:0.001'],
            'source_pieces_per_unit' => ['nullable', 'numeric', 'min:0.001'],
            'packed_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'batch_code' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $createdPackCount = 0;

        DB::transaction(function () use ($validated, $request, &$createdPackCount) {
            /** @var InventoryLot $sourceLot */
            $sourceLot = InventoryLot::query()
                ->with(['product'])
                ->lockForUpdate()
                ->findOrFail((int) $validated['source_inventory_lot_id']);

            /** @var ProductSellUnit $sellUnit */
            $sellUnit = ProductSellUnit::query()
                ->with(['product', 'variants'])
                ->lockForUpdate()
                ->findOrFail((int) $validated['product_sell_unit_id']);

            if ((int) $sellUnit->product_id !== (int) $sourceLot->product_id) {
                throw ValidationException::withMessages([
                    'product_sell_unit_id' => 'The sellable unit must belong to the same product as the source lot.',
                ]);
            }

            if (! in_array((string) $sellUnit->unit_type, ['piece', 'pack', 'box'], true)) {
                throw ValidationException::withMessages([
                    'product_sell_unit_id' => 'Only fixed-count piece, pack, and box units can be created from this repack screen.',
                ]);
            }

            if (! $sourceLot->can_repack || $sourceLot->lot_status !== 'available') {
                throw ValidationException::withMessages([
                    'source_inventory_lot_id' => 'Selected lot is not available for repack.',
                ]);
            }

            $packCount = (int) $validated['pack_count'];
            $piecesPerPack = $this->positiveDecimal($sellUnit->pieces_per_unit)
                ?: $this->positiveDecimal($validated['pieces_per_pack'] ?? null);

            if ($piecesPerPack <= 0) {
                throw ValidationException::withMessages([
                    'pieces_per_pack' => 'Pieces per pack is required for the selected sellable unit.',
                ]);
            }

            $sourcePiecesPerUnit = $this->positiveDecimal($validated['source_pieces_per_unit'] ?? null) ?: 1.0;
            $requiredSourcePieces = round($packCount * $piecesPerPack, 3);
            $requiredSourceQuantity = round($requiredSourcePieces / $sourcePiecesPerUnit, 3);
            $availableQuantity = round((float) ($sourceLot->available_quantity ?? 0), 3);

            if ($requiredSourceQuantity > $availableQuantity + 0.0005) {
                throw ValidationException::withMessages([
                    'pack_count' => "This repack needs {$requiredSourceQuantity} source unit(s), but the lot has only {$availableQuantity} available.",
                ]);
            }

            $product = Product::query()->lockForUpdate()->findOrFail((int) $sourceLot->product_id);
            $linkedVariants = $sellUnit->variants()->lockForUpdate()->get();

            if ($linkedVariants->count() !== 1) {
                throw ValidationException::withMessages([
                    'product_sell_unit_id' => $linkedVariants->isEmpty()
                        ? 'Link this sellable unit to exactly one product variant before creating online-orderable pack stock.'
                        : 'This sellable unit is linked to multiple variants. Link it to exactly one variant before creating pack stock.',
                ]);
            }

            /** @var ProductVariant $targetVariant */
            $targetVariant = $linkedVariants->first();

            $sourceUnitWeight = $this->sourceUnitWeight($sourceLot);
            $sourcePieceWeight = $sourceUnitWeight !== null ? round($sourceUnitWeight / $sourcePiecesPerUnit, 6) : null;
            $packWeight = $sourcePieceWeight !== null ? round($sourcePieceWeight * $piecesPerPack, 3) : null;
            $deductWeight = $sourceUnitWeight !== null ? round($sourceUnitWeight * $requiredSourceQuantity, 3) : null;

            $sourceUnitCost = $this->sourceUnitCost($sourceLot);
            $sourcePieceCost = $sourceUnitCost !== null ? round($sourceUnitCost / $sourcePiecesPerUnit, 6) : null;
            $packCost = $sourcePieceCost !== null ? round($sourcePieceCost * $piecesPerPack, 2) : null;

            $sourceLot->available_quantity = round(max($availableQuantity - $requiredSourceQuantity, 0), 3);

            if ($deductWeight !== null) {
                $sourceLot->available_weight_kg = max(round((float) ($sourceLot->available_weight_kg ?? 0) - $deductWeight, 3), 0);
            }

            if (Schema::hasColumn('inventory_lots', 'available_piece_count') && $sourceLot->available_piece_count !== null) {
                $sourceLot->available_piece_count = max((int) ($sourceLot->available_piece_count ?? 0) - (int) round($requiredSourcePieces), 0);
            }

            if (Schema::hasColumn('inventory_lots', 'consumed_quantity')) {
                $sourceLot->consumed_quantity = round((float) ($sourceLot->consumed_quantity ?? 0) + $requiredSourceQuantity, 3);
            }

            if ((float) ($sourceLot->available_quantity ?? 0) <= 0.0005) {
                $sourceLot->lot_status = 'exhausted';
                if ($deductWeight === null && Schema::hasColumn('inventory_lots', 'available_weight_kg')) {
                    $sourceLot->available_weight_kg = 0;
                }
            }

            $sourceLot->updated_by_id = $request->user()?->id;
            $sourceLot->save();

            if ($sourceLot->product_variant_id && (int) $sourceLot->product_variant_id !== (int) $targetVariant->id) {
                $sourceVariant = ProductVariant::query()
                    ->lockForUpdate()
                    ->find((int) $sourceLot->product_variant_id);

                if ($sourceVariant) {
                    $sourceVariant->stock_quantity = max(
                        round((float) ($sourceVariant->stock_quantity ?? 0) - $requiredSourceQuantity, 3),
                        0
                    );
                    $sourceVariant->save();
                }
            }

            $packedDate = $validated['packed_date'] ?? now()->toDateString();
            $expiryDate = $validated['expiry_date'] ?? $sourceLot->expiry_date;
            $batchCode = $validated['batch_code'] ?: ($sourceLot->batch_code ?: 'RP-' . now()->format('Ymd'));
            $notes = $validated['notes'] ?? null;

            $startNo = ((int) InventoryPack::query()
                ->where('source_inventory_lot_id', $sourceLot->id)
                ->where('product_sell_unit_id', $sellUnit->id)
                ->max('pack_no')) + 1;

            $sourceQuantityPerPack = round($requiredSourceQuantity / $packCount, 6);
            $sourceWeightPerPack = $deductWeight !== null ? round($deductWeight / $packCount, 3) : null;

            for ($i = 0; $i < $packCount; $i++) {
                InventoryPack::create([
                    'source_inventory_lot_id' => $sourceLot->id,
                    'source_inventory_piece_id' => null,
                    'product_id' => $product->id,
                    'product_variant_id' => $targetVariant->id,
                    'product_sell_unit_id' => $sellUnit->id,
                    'pack_no' => $startNo + $i,
                    'pack_code' => $batchCode . '-' . str_pad((string) ($startNo + $i), 3, '0', STR_PAD_LEFT),
                    'pack_quantity' => 1,
                    'available_pack_quantity' => 1,
                    'pieces_per_pack' => $piecesPerPack,
                    'total_pieces' => $piecesPerPack,
                    'available_pieces' => $piecesPerPack,
                    'source_pieces_per_unit' => $sourcePiecesPerUnit,
                    'source_quantity_consumed' => $sourceQuantityPerPack,
                    'source_weight_kg_consumed' => $sourceWeightPerPack,
                    'unit_weight_kg' => $sourcePieceWeight,
                    'actual_weight_kg' => $packWeight,
                    'total_weight_kg' => $packWeight,
                    'unit_cost' => $packCost,
                    'total_cost' => $packCost,
                    'packed_date' => $packedDate,
                    'expiry_date' => $expiryDate,
                    'batch_code' => $batchCode,
                    'status' => 'available',
                    'notes' => $notes,
                    'created_by_id' => $request->user()?->id,
                    'updated_by_id' => $request->user()?->id,
                ]);
            }

            $this->writeStockMovement(
                productId: (int) $product->id,
                variantId: $sourceLot->product_variant_id ? (int) $sourceLot->product_variant_id : null,
                sellUnitId: $sourceLot->product_sell_unit_id ? (int) $sourceLot->product_sell_unit_id : null,
                quantity: -1 * $requiredSourceQuantity,
                referenceId: (int) $sourceLot->id,
                costPrice: $sourceUnitCost,
                notes: "Repack consumed {$requiredSourceQuantity} source unit(s) / {$requiredSourcePieces} piece(s) from lot #{$sourceLot->id} into {$packCount} × {$sellUnit->name}."
            );

            $targetVariant->stock_quantity = round((float) ($targetVariant->stock_quantity ?? 0) + $packCount, 3);
            $targetVariant->manage_stock = true;
            $targetVariant->save();

            $this->syncParentProductStockFromVariants($product);

            $this->writeStockMovement(
                productId: (int) $product->id,
                variantId: (int) $targetVariant->id,
                sellUnitId: (int) $sellUnit->id,
                quantity: $packCount,
                referenceId: (int) $sourceLot->id,
                costPrice: $packCost,
                notes: "Repack created {$packCount} available {$sellUnit->name} pack(s) from lot #{$sourceLot->id}."
            );

            $createdPackCount = $packCount;
        });

        return redirect()
            ->route('admin.inventory.packs.index')
            ->with('status', "Created {$createdPackCount} pack(s) from source inventory.");
    }

    private function positiveDecimal(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return max(round((float) $value, 3), 0.0);
    }

    private function sourceUnitWeight(InventoryLot $lot): ?float
    {
        if (! empty($lot->unit_weight_kg) && (float) $lot->unit_weight_kg > 0) {
            return round((float) $lot->unit_weight_kg, 3);
        }

        $receivedQty = (float) ($lot->received_quantity ?? 0);
        $totalWeight = (float) ($lot->total_weight_kg ?? 0);

        if ($receivedQty > 0 && $totalWeight > 0) {
            return round($totalWeight / $receivedQty, 3);
        }

        return null;
    }

    private function sourceUnitCost(InventoryLot $lot): ?float
    {
        if (! empty($lot->unit_cost) && (float) $lot->unit_cost > 0) {
            return round((float) $lot->unit_cost, 2);
        }

        $receivedQty = (float) ($lot->received_quantity ?? 0);
        $totalCost = (float) ($lot->total_cost ?? 0);

        if ($receivedQty > 0 && $totalCost > 0) {
            return round($totalCost / $receivedQty, 2);
        }

        return null;
    }

    private function writeStockMovement(int $productId, ?int $variantId, ?int $sellUnitId, float $quantity, int $referenceId, ?float $costPrice, string $notes): void
    {
        StockMovement::create([
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'product_sell_unit_id' => $sellUnitId,
            'vendor_id' => null,
            'quantity' => round($quantity, 3),
            'movement_type' => 'repack',
            'reference_type' => 'inventory_repack',
            'reference_id' => $referenceId,
            'cost_price' => $costPrice,
            'notes' => $notes,
            'created_at' => now(),
        ]);
    }

    private function syncParentProductStockFromVariants(Product $product): void
    {
        $productId = (int) $product->id;
        $variantStock = round((float) ProductVariant::query()
            ->where('product_id', $productId)
            ->whereNull('deleted_at')
            ->sum('stock_quantity'), 3);

        $freshProduct = Product::query()
            ->lockForUpdate()
            ->find($productId);

        if (! $freshProduct) {
            return;
        }

        $freshProduct->stock_quantity = $variantStock;
        $freshProduct->save();
    }
}
