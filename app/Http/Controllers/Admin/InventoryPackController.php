<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLot;
use App\Models\InventoryPack;
use App\Models\InventoryPiece;
use App\Models\Product;
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
            ->with(['product', 'productVariant', 'sourcePiece', 'sourceLot.product', 'sourceLot.parentLot.product', 'soldOrder'])
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.inventory.packs.index', compact('packs'));
    }

    public function create(Request $request)
    {
        $lots = InventoryLot::query()
            ->with(['product', 'productVariant', 'pieces'])
            ->availableForRepack()
            ->orderBy('product_id')
            ->orderBy('expiry_date')
            ->orderBy('received_date')
            ->orderBy('id')
            ->get();

        $productColumns = $this->existingColumns('products', [
            'id',
            'name',
            'sku',
            'type',
            'inventory_role',
            'pack_type',
            'sell_unit',
            'product_weight',
            'pieces_per_pack',
            'stock_quantity',
            'manage_stock',
            'inventory_is_saleable',
            'inventory_can_repack',
            'is_active',
        ]);

        $outputProducts = Product::query()
            ->select($productColumns)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $variantColumns = $this->existingColumns('product_variants', [
            'id',
            'product_id',
            'sku',
            'name',
            'pack_type',
            'pieces_per_pack',
            'product_weight',
            'pricing_unit',
            'stock_quantity',
            'is_active',
        ]);

        $outputVariants = ProductVariant::query()
            ->select($variantColumns)
            ->whereIn('product_id', $outputProducts->pluck('id')->all())
            ->orderBy('product_id')
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->groupBy('product_id');

        $recentPacks = InventoryPack::query()
            ->with(['product', 'productVariant', 'sourcePiece', 'sourceLot.product', 'sourceLot.parentLot.product', 'soldOrder'])
            ->latest('id')
            ->limit(12)
            ->get();

        $selectedLotId = $request->integer('source_inventory_lot_id') ?: $request->integer('lot_id') ?: old('source_inventory_lot_id');

        return view('admin.inventory.packs.create', compact('lots', 'outputProducts', 'outputVariants', 'recentPacks', 'selectedLotId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'source_inventory_lot_id' => ['required', 'integer', 'exists:inventory_lots,id'],
            'output_product_id' => ['required', 'integer', 'exists:products,id'],
            'output_product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'source_inventory_piece_id' => ['nullable', 'integer', 'exists:inventory_pieces,id'],
            'pack_count' => ['required', 'integer', 'min:1', 'max:10000'],
            'pieces_per_pack' => ['nullable', 'numeric', 'min:0.001'],
            'source_pieces_per_unit' => ['nullable', 'numeric', 'min:0.001'],
            'output_weight_kg' => ['nullable', 'numeric', 'min:0.001'],
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

            /** @var Product $outputProduct */
            $outputProduct = Product::query()
                ->lockForUpdate()
                ->findOrFail((int) $validated['output_product_id']);

            $outputVariant = $this->resolveOutputVariant($outputProduct, $validated['output_product_variant_id'] ?? null);

            if (! $sourceLot->can_repack || $sourceLot->lot_status !== 'available') {
                throw ValidationException::withMessages([
                    'source_inventory_lot_id' => 'Selected source lot is not available for repack.',
                ]);
            }

            $sourceProduct = Product::query()->lockForUpdate()->findOrFail((int) $sourceLot->product_id);
            $sourceVariant = $sourceLot->product_variant_id
                ? ProductVariant::query()->lockForUpdate()->find((int) $sourceLot->product_variant_id)
                : null;
            $sourcePiece = null;
            if (! empty($validated['source_inventory_piece_id'])) {
                $sourcePiece = InventoryPiece::query()
                    ->lockForUpdate()
                    ->findOrFail((int) $validated['source_inventory_piece_id']);

                if ((int) $sourcePiece->inventory_lot_id !== (int) $sourceLot->id) {
                    throw ValidationException::withMessages([
                        'source_inventory_piece_id' => 'Selected piece does not belong to the selected source lot.',
                    ]);
                }
            }

            $packCount = (int) $validated['pack_count'];
            $consumption = $this->calculateProductConsumption($sourceLot, $outputProduct, $outputVariant, $validated, $packCount);

            $this->deductSourcePiece($sourcePiece, $consumption, $request->user()?->id);
            $this->deductSourceLot($sourceLot, $consumption, $request->user()?->id);
            $this->deductSourceStock($sourceProduct, $sourceVariant, (float) $consumption['source_stock_quantity']);

            $packedDate = $validated['packed_date'] ?? now()->toDateString();
            $expiryDate = $validated['expiry_date'] ?? ($sourceLot->expiry_date ? $sourceLot->expiry_date->format('Y-m-d') : null);
            $batchCode = trim((string) ($validated['batch_code'] ?? '')) ?: ($sourceLot->batch_code ?: 'RP-' . now()->format('Ymd'));
            $notes = $validated['notes'] ?? null;

            $outputLot = $this->createOutputLot(
                sourceLot: $sourceLot,
                product: $outputProduct,
                variant: $outputVariant,
                consumption: $consumption,
                packCount: $packCount,
                packedDate: $packedDate,
                expiryDate: $expiryDate,
                batchCode: $batchCode,
                notes: $notes,
                userId: $request->user()?->id
            );

            $startNo = ((int) InventoryPack::query()
                ->where('source_inventory_lot_id', $outputLot->id)
                ->where('product_id', $outputProduct->id)
                ->when($outputVariant, fn ($query) => $query->where('product_variant_id', $outputVariant->id), fn ($query) => $query->whereNull('product_variant_id'))
                ->max('pack_no')) + 1;

            $sourceQuantityPerPack = round($consumption['source_quantity'] / $packCount, 6);
            $sourceWeightPerPack = $consumption['source_weight'] !== null ? round($consumption['source_weight'] / $packCount, 3) : null;
            $outputQuantityPerPack = $consumption['output_quantity_per_pack'] ?? 1;

            for ($i = 0; $i < $packCount; $i++) {
                InventoryPack::create([
                    'production_run_id' => null,
                    'source_inventory_lot_id' => $outputLot->id,
                    'source_inventory_piece_id' => $sourcePiece?->id,
                    'product_id' => $outputProduct->id,
                    'product_variant_id' => $outputVariant?->id,
                    'product_sell_unit_id' => null,
                    'pack_no' => $startNo + $i,
                    'pack_code' => $batchCode . '-' . str_pad((string) ($startNo + $i), 3, '0', STR_PAD_LEFT),
                    'pack_quantity' => $outputQuantityPerPack,
                    'available_pack_quantity' => $outputQuantityPerPack,
                    'pieces_per_pack' => $consumption['pieces_per_pack'],
                    'total_pieces' => $consumption['pieces_per_pack'],
                    'available_pieces' => $consumption['pieces_per_pack'],
                    'source_pieces_per_unit' => $consumption['source_pieces_per_unit'],
                    'source_quantity_consumed' => $sourceQuantityPerPack,
                    'source_weight_kg_consumed' => $sourceWeightPerPack,
                    'unit_weight_kg' => $consumption['pack_weight'],
                    'actual_weight_kg' => $consumption['pack_weight'],
                    'total_weight_kg' => $consumption['pack_weight'],
                    'unit_cost' => $consumption['pack_cost'],
                    'total_cost' => $consumption['pack_cost'],
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
                productId: (int) $sourceProduct->id,
                variantId: $sourceLot->product_variant_id ? (int) $sourceLot->product_variant_id : null,
                sellUnitId: $sourceLot->product_sell_unit_id ? (int) $sourceLot->product_sell_unit_id : null,
                quantity: -1 * (float) $consumption['source_stock_quantity'],
                referenceId: (int) $sourceLot->id,
                costPrice: $consumption['source_unit_cost'],
                notes: $this->consumptionNote($sourceLot, $outputProduct, $outputVariant, $packCount, $consumption)
            );

            $stockIncrease = (float) ($consumption['stock_quantity'] ?? $packCount);
            $this->increaseOutputStock($outputProduct, $outputVariant, $stockIncrease);

            $this->writeStockMovement(
                productId: (int) $outputProduct->id,
                variantId: $outputVariant?->id,
                sellUnitId: null,
                quantity: $stockIncrease,
                referenceId: (int) $outputLot->id,
                costPrice: $consumption['pack_cost'],
                notes: "Repack created {$packCount} pack(s) for " . $this->outputName($outputProduct, $outputVariant) . " from lot #{$sourceLot->id}."
            );

            $createdPackCount = $packCount;
        });

        return redirect()
            ->route('admin.inventory.packs.index')
            ->with('status', "Created {$createdPackCount} pack(s) from source inventory.");
    }

    private function createOutputLot(InventoryLot $sourceLot, Product $product, ?ProductVariant $variant, array $consumption, int $packCount, string $packedDate, ?string $expiryDate, string $batchCode, ?string $notes, ?int $userId): InventoryLot
    {
        $stockQuantity = round((float) ($consumption['stock_quantity'] ?? $packCount), 3);
        $packWeight = $consumption['pack_weight'] !== null ? round((float) $consumption['pack_weight'], 3) : null;
        $totalWeight = $consumption['source_weight'] !== null ? round((float) $consumption['source_weight'], 3) : ($packWeight !== null ? round($packWeight * $packCount, 3) : null);
        $piecesPerPack = $consumption['pieces_per_pack'] !== null ? round((float) $consumption['pieces_per_pack'], 3) : null;
        $totalPieces = $piecesPerPack !== null ? (int) round($piecesPerPack * $packCount) : null;
        $packCost = $consumption['pack_cost'] !== null ? round((float) $consumption['pack_cost'], 2) : null;
        $costPerKg = ($totalWeight && $totalWeight > 0 && $packCost !== null) ? round(($packCost * $packCount) / $totalWeight, 2) : null;

        $lot = new InventoryLot();
        $lot->lot_code = $this->generateOutputLotCode($sourceLot, $product);
        $lot->product_id = $product->id;
        $lot->product_variant_id = $variant?->id;
        $lot->product_sell_unit_id = null;
        $lot->parent_inventory_lot_id = $sourceLot->id;
        $lot->root_inventory_lot_id = $sourceLot->root_inventory_lot_id ?: $sourceLot->id;
        $lot->lot_stage = 'pack';
        $lot->inward_mode = 'repack';
        $lot->is_saleable = (bool) ($product->is_active ?? false) && (string) ($product->inventory_role ?? 'saleable') !== 'internal';
        $lot->can_repack = false;
        $lot->lot_status = 'available';
        $lot->batch_code = $batchCode;
        $lot->mfg_date = $sourceLot->mfg_date;
        $lot->packed_date = $packedDate;
        $lot->expiry_date = $expiryDate;
        $lot->received_date = now()->toDateString();
        $lot->received_quantity = $stockQuantity;
        $lot->available_quantity = $stockQuantity;
        $lot->unit_weight_kg = $packWeight;
        $lot->total_weight_kg = $totalWeight;
        $lot->available_weight_kg = $totalWeight;
        $lot->piece_count = $totalPieces;
        $lot->available_piece_count = $totalPieces;
        $lot->pack_count = $packCount;
        $lot->available_pack_count = $packCount;
        $lot->pieces_per_pack = $piecesPerPack;
        $lot->pack_size_kg = $packWeight;
        $lot->unit_cost = $packCost;
        $lot->cost_per_kg = $costPerKg;
        $lot->total_cost = $packCost !== null ? round($packCost * $packCount, 2) : null;
        $lot->notes = trim('Repacked from source lot #' . $sourceLot->id . '. ' . (string) $notes) ?: null;
        $lot->created_by_id = $userId;
        $lot->updated_by_id = $userId;
        $lot->save();

        return $lot;
    }

    private function generateOutputLotCode(InventoryLot $sourceLot, Product $product): string
    {
        $base = strtoupper((string) ($sourceLot->lot_code ?: 'LOT-' . $sourceLot->id));
        $base = preg_replace('/[^A-Z0-9\-]+/', '-', $base) ?: 'LOT';
        $base = trim(preg_replace('/\-+/', '-', $base), '-');
        $sku = strtoupper((string) ($product->sku ?: 'P' . $product->id));
        $sku = preg_replace('/[^A-Z0-9\-]+/', '-', $sku) ?: 'OUT';
        $sku = trim(preg_replace('/\-+/', '-', $sku), '-');

        return substr($base, 0, 16) . '-RP-' . substr($sku, 0, 16) . '-' . now()->format('His');
    }

    private function calculateProductConsumption(InventoryLot $sourceLot, Product $outputProduct, ?ProductVariant $outputVariant, array $validated, int $packCount): array
    {
        $mode = $this->outputMode($outputProduct, $outputVariant, $validated);

        return match ($mode) {
            'piece' => $this->calculatePiecePackConsumption($sourceLot, $outputProduct, $outputVariant, $validated, $packCount),
            'variable_weight' => $this->calculateVariableWeightPackConsumption($sourceLot, $outputProduct, $outputVariant, $validated, $packCount),
            'weight' => $this->calculateWeightPackConsumption($sourceLot, $outputProduct, $outputVariant, $validated, $packCount),
            default => $this->calculateQuantityPackConsumption($sourceLot, $outputProduct, $outputVariant, $packCount),
        };
    }

    private function outputMode(Product $product, ?ProductVariant $variant, array $validated): string
    {
        $packType = $this->targetPackType($product, $variant);
        $sellUnit = (string) ($product->sell_unit ?? 'piece');
        $piecesPerPack = $this->targetPiecesPerPack($product, $variant) ?: $this->positiveDecimal($validated['pieces_per_pack'] ?? null);
        $weight = $this->targetWeightKg($product, $variant) ?: $this->positiveDecimal($validated['output_weight_kg'] ?? null);

        if ($packType === 'fixed_piece_pack' || $piecesPerPack > 0) {
            return 'piece';
        }

        if (! $variant && ($sellUnit === 'kg' || $packType === 'variable_weight')) {
            return 'variable_weight';
        }

        if ($packType === 'fixed_weight_pack' || $weight > 0) {
            return 'weight';
        }

        return 'quantity';
    }

    private function calculatePiecePackConsumption(InventoryLot $sourceLot, Product $product, ?ProductVariant $variant, array $validated, int $packCount): array
    {
        $piecesPerPack = $this->targetPiecesPerPack($product, $variant)
            ?: $this->positiveDecimal($validated['pieces_per_pack'] ?? null);

        if ($piecesPerPack <= 0) {
            throw ValidationException::withMessages([
                'pieces_per_pack' => 'Pieces per pack is required for fixed-piece output products.',
            ]);
        }

        $sourcePiecesPerUnit = $this->positiveDecimal($validated['source_pieces_per_unit'] ?? null) ?: 1.0;
        $requiredSourcePieces = round($packCount * $piecesPerPack, 3);
        $requiredSourceQuantity = round($requiredSourcePieces / $sourcePiecesPerUnit, 3);
        $availablePieces = $this->availablePiecesForRepack($sourceLot);
        $availableQuantity = round((float) ($sourceLot->available_quantity ?? 0), 3);

        if ($availablePieces !== null && $availablePieces > 0) {
            if ($requiredSourcePieces > $availablePieces + 0.0005) {
                throw ValidationException::withMessages([
                    'pack_count' => "This repack needs {$requiredSourcePieces} piece(s), but the lot has only {$availablePieces} piece(s) available.",
                ]);
            }
        } elseif ($requiredSourceQuantity > $availableQuantity + 0.0005) {
            throw ValidationException::withMessages([
                'pack_count' => "This repack needs {$requiredSourceQuantity} source unit(s), but the lot has only {$availableQuantity} available.",
            ]);
        }

        $sourceUnitWeight = $this->sourceUnitWeight($sourceLot);
        $sourcePieceWeight = $sourceUnitWeight !== null ? round($sourceUnitWeight / $sourcePiecesPerUnit, 6) : null;
        $packWeight = $this->targetWeightKg($product, $variant) ?: ($sourcePieceWeight !== null ? round($sourcePieceWeight * $piecesPerPack, 3) : null);
        $sourceWeight = $sourceUnitWeight !== null ? round($sourceUnitWeight * $requiredSourceQuantity, 3) : null;

        $sourceUnitCost = $this->sourceUnitCost($sourceLot);
        $sourcePieceCost = $sourceUnitCost !== null ? round($sourceUnitCost / $sourcePiecesPerUnit, 6) : null;
        $packCost = $sourcePieceCost !== null ? round($sourcePieceCost * $piecesPerPack, 2) : null;

        return [
            'mode' => 'piece',
            'source_quantity' => $requiredSourceQuantity,
            'source_stock_quantity' => $requiredSourceQuantity,
            'source_weight' => $sourceWeight,
            'source_piece_count' => $requiredSourcePieces,
            'source_unit_cost' => $sourceUnitCost,
            'pack_weight' => $packWeight,
            'pack_cost' => $packCost,
            'pieces_per_pack' => $piecesPerPack,
            'source_pieces_per_unit' => $sourcePiecesPerUnit,
            'stock_quantity' => $packCount,
        ];
    }

    private function calculateWeightPackConsumption(InventoryLot $sourceLot, Product $product, ?ProductVariant $variant, array $validated, int $packCount): array
    {
        $packWeight = $this->targetWeightKg($product, $variant)
            ?: $this->positiveDecimal($validated['output_weight_kg'] ?? null);

        if ($packWeight <= 0) {
            throw ValidationException::withMessages([
                'output_weight_kg' => 'Fixed weight output products need product/pack weight in kg.',
            ]);
        }

        $requiredSourceWeight = round($packCount * $packWeight, 3);
        $this->assertWeightAvailable($sourceLot, $requiredSourceWeight);

        $requiredSourceQuantity = $this->sourceQuantityForWeight($sourceLot, $requiredSourceWeight);
        $sourceUnitCost = $this->sourceUnitCost($sourceLot);
        $costPerKg = $this->sourceCostPerKg($sourceLot, $sourceUnitCost, $this->sourceUnitWeight($sourceLot) ?: 1.0);
        $packCost = $costPerKg !== null ? round($costPerKg * $packWeight, 2) : null;

        return [
            'mode' => 'weight',
            'source_quantity' => $requiredSourceQuantity,
            'source_stock_quantity' => $requiredSourceQuantity,
            'source_weight' => $requiredSourceWeight,
            'source_piece_count' => null,
            'source_unit_cost' => $sourceUnitCost,
            'pack_weight' => $packWeight,
            'pack_cost' => $packCost,
            'pieces_per_pack' => null,
            'source_pieces_per_unit' => null,
            'stock_quantity' => $packCount,
        ];
    }

    private function calculateVariableWeightPackConsumption(InventoryLot $sourceLot, Product $product, ?ProductVariant $variant, array $validated, int $packCount): array
    {
        $totalOutputWeight = $this->positiveDecimal($validated['output_weight_kg'] ?? null);
        $targetWeight = $this->positiveDecimal($this->targetWeightKg($product, $variant));

        if ($totalOutputWeight <= 0 && $targetWeight > 0) {
            $totalOutputWeight = round($packCount * $targetWeight, 3);
        }

        if ($totalOutputWeight <= 0) {
            throw ValidationException::withMessages([
                'output_weight_kg' => 'Enter total output weight for variable-weight / by-kg output products.',
            ]);
        }

        $this->assertWeightAvailable($sourceLot, $totalOutputWeight);

        $requiredSourceQuantity = $this->sourceQuantityForWeight($sourceLot, $totalOutputWeight);
        $sourceUnitCost = $this->sourceUnitCost($sourceLot);
        $costPerKg = $this->sourceCostPerKg($sourceLot, $sourceUnitCost, $this->sourceUnitWeight($sourceLot) ?: 1.0);
        $packWeight = round($totalOutputWeight / $packCount, 3);
        $packCost = $costPerKg !== null ? round($costPerKg * $packWeight, 2) : null;

        return [
            'mode' => 'variable_weight',
            'source_quantity' => $requiredSourceQuantity,
            'source_stock_quantity' => $requiredSourceQuantity,
            'source_weight' => $totalOutputWeight,
            'source_piece_count' => null,
            'source_unit_cost' => $sourceUnitCost,
            'pack_weight' => $packWeight,
            'pack_cost' => $packCost,
            'pieces_per_pack' => null,
            'source_pieces_per_unit' => null,
            'output_quantity_per_pack' => $packWeight,
            'stock_quantity' => $totalOutputWeight,
        ];
    }

    private function calculateQuantityPackConsumption(InventoryLot $sourceLot, Product $product, ?ProductVariant $variant, int $packCount): array
    {
        $availableQuantity = round((float) ($sourceLot->available_quantity ?? 0), 3);
        if ($packCount > $availableQuantity + 0.0005) {
            throw ValidationException::withMessages([
                'pack_count' => "This repack needs {$packCount} source unit(s), but the lot has only {$availableQuantity} available.",
            ]);
        }

        $sourceUnitWeight = $this->sourceUnitWeight($sourceLot);
        $sourceUnitCost = $this->sourceUnitCost($sourceLot);
        $packWeight = $this->targetWeightKg($product, $variant) ?: $sourceUnitWeight;
        $packCost = $sourceUnitCost;

        return [
            'mode' => 'quantity',
            'source_quantity' => $packCount,
            'source_stock_quantity' => $packCount,
            'source_weight' => $sourceUnitWeight !== null ? round($sourceUnitWeight * $packCount, 3) : null,
            'source_piece_count' => null,
            'source_unit_cost' => $sourceUnitCost,
            'pack_weight' => $packWeight,
            'pack_cost' => $packCost,
            'pieces_per_pack' => $this->targetPiecesPerPack($product, $variant),
            'source_pieces_per_unit' => null,
            'stock_quantity' => $packCount,
        ];
    }

    private function assertWeightAvailable(InventoryLot $sourceLot, float $requiredSourceWeight): void
    {
        $availableWeight = $this->availableWeightForRepack($sourceLot);

        if ($requiredSourceWeight > $availableWeight + 0.0005) {
            throw ValidationException::withMessages([
                'pack_count' => "This repack needs {$requiredSourceWeight} kg, but the lot has only {$availableWeight} kg available.",
            ]);
        }
    }

    private function sourceQuantityForWeight(InventoryLot $sourceLot, float $requiredSourceWeight): float
    {
        $inwardMode = (string) ($sourceLot->inward_mode ?? '');
        if (in_array($inwardMode, ['pieces_weight', 'bulk_weight'], true)) {
            return round($requiredSourceWeight, 3);
        }

        $sourceUnitWeight = $this->sourceUnitWeight($sourceLot) ?: 1.0;

        return round($requiredSourceWeight / $sourceUnitWeight, 3);
    }

    private function deductSourcePiece(?InventoryPiece $piece, array $consumption, ?int $userId): void
    {
        if (! $piece) {
            return;
        }

        $requiredWeight = $consumption['source_weight'] ?? null;
        if ($requiredWeight === null || (float) $requiredWeight <= 0) {
            throw ValidationException::withMessages([
                'source_inventory_piece_id' => 'Specific piece selection is only supported for weight-based repack/cutting.',
            ]);
        }

        $availableWeight = Schema::hasColumn('inventory_pieces', 'available_weight_kg')
            ? (float) ($piece->available_weight_kg ?? $piece->weight_kg ?? 0)
            : (float) ($piece->weight_kg ?? 0);

        if ((float) $requiredWeight > $availableWeight + 0.0005) {
            throw ValidationException::withMessages([
                'source_inventory_piece_id' => 'Selected piece has only ' . number_format($availableWeight, 3) . ' kg available.',
            ]);
        }

        $remaining = round(max($availableWeight - (float) $requiredWeight, 0), 3);
        if (Schema::hasColumn('inventory_pieces', 'available_weight_kg')) {
            $piece->available_weight_kg = $remaining;
        }

        $piece->status = $remaining <= 0.0005 ? 'consumed' : 'partially_used';
        if (Schema::hasColumn('inventory_pieces', 'notes')) {
            $piece->notes = trim((string) ($piece->notes ?? '') . "\nConsumed " . number_format((float) $requiredWeight, 3) . ' kg in repack on ' . now()->format('Y-m-d H:i'));
        }
        $piece->save();
    }

    private function deductSourceLot(InventoryLot $sourceLot, array $consumption, ?int $userId): void
    {
        $currentQty = round((float) ($sourceLot->available_quantity ?? 0), 3);
        $currentWeight = $this->availableWeightForRepack($sourceLot);

        $sourceLot->available_quantity = round(max($currentQty - (float) $consumption['source_quantity'], 0), 3);

        if ($consumption['source_weight'] !== null && Schema::hasColumn('inventory_lots', 'available_weight_kg')) {
            $sourceLot->available_weight_kg = round(max($currentWeight - (float) $consumption['source_weight'], 0), 3);
        }

        if ($consumption['source_piece_count'] !== null && Schema::hasColumn('inventory_lots', 'available_piece_count') && $sourceLot->available_piece_count !== null) {
            $sourceLot->available_piece_count = max((int) ($sourceLot->available_piece_count ?? 0) - (int) round((float) $consumption['source_piece_count']), 0);
        }

        if (Schema::hasColumn('inventory_lots', 'consumed_quantity')) {
            $sourceLot->consumed_quantity = round((float) ($sourceLot->consumed_quantity ?? 0) + (float) $consumption['source_quantity'], 3);
        }

        $remainingQty = round((float) ($sourceLot->available_quantity ?? 0), 3);
        $remainingWeight = round((float) ($sourceLot->available_weight_kg ?? 0), 3);
        $remainingPieces = (int) ($sourceLot->available_piece_count ?? 0);

        if ($remainingQty <= 0.0005 && $remainingWeight <= 0.0005 && $remainingPieces <= 0) {
            $sourceLot->lot_status = 'exhausted';
        }

        $sourceLot->updated_by_id = $userId;
        $sourceLot->save();
    }

    private function deductSourceStock(Product $sourceProduct, ?ProductVariant $sourceVariant, float $quantity): void
    {
        if ($sourceVariant) {
            $sourceVariant->stock_quantity = max(round((float) ($sourceVariant->stock_quantity ?? 0) - $quantity, 3), 0);
            $sourceVariant->manage_stock = true;
            $sourceVariant->save();
            $this->syncProductStockFromVariants($sourceProduct);
            return;
        }

        $sourceProduct->stock_quantity = max(round((float) ($sourceProduct->stock_quantity ?? 0) - $quantity, 3), 0);
        $sourceProduct->manage_stock = true;
        $sourceProduct->save();
    }

    private function availablePiecesForRepack(InventoryLot $lot): ?float
    {
        if ($lot->available_piece_count !== null && (float) $lot->available_piece_count > 0) {
            return round((float) $lot->available_piece_count, 3);
        }

        return null;
    }

    private function availableWeightForRepack(InventoryLot $lot): float
    {
        if ($lot->available_weight_kg !== null && (float) $lot->available_weight_kg > 0) {
            return round((float) $lot->available_weight_kg, 3);
        }

        $sourceUnitWeight = $this->sourceUnitWeight($lot) ?: 1.0;

        return round((float) ($lot->available_quantity ?? 0) * $sourceUnitWeight, 3);
    }

    private function positiveDecimal(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return max(round((float) $value, 3), 0.0);
    }

    private function positiveOrNull(mixed $value): ?float
    {
        $value = $this->positiveDecimal($value);

        return $value > 0 ? $value : null;
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

    private function sourceCostPerKg(InventoryLot $lot, ?float $sourceUnitCost, float $sourceUnitWeight): ?float
    {
        if (! empty($lot->cost_per_kg) && (float) $lot->cost_per_kg > 0) {
            return round((float) $lot->cost_per_kg, 2);
        }

        $totalWeight = (float) ($lot->total_weight_kg ?? 0);
        $totalCost = (float) ($lot->total_cost ?? 0);

        if ($totalWeight > 0 && $totalCost > 0) {
            return round($totalCost / $totalWeight, 2);
        }

        if ($sourceUnitCost !== null && $sourceUnitWeight > 0) {
            return round($sourceUnitCost / $sourceUnitWeight, 2);
        }

        return null;
    }

    private function writeStockMovement(int $productId, ?int $variantId, ?int $sellUnitId, float $quantity, int $referenceId, ?float $costPrice, string $notes): void
    {
        $attrs = [
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'product_sell_unit_id' => $sellUnitId,
            'vendor_id' => null,
            'quantity' => round($quantity, 3),
            'movement_type' => 'adjustment',
            'reference_type' => 'inventory_repack',
            'reference_id' => $referenceId,
            'cost_price' => $costPrice,
            'notes' => $notes,
            'created_at' => now(),
        ];

        StockMovement::create(array_filter(
            $attrs,
            fn ($value, string $column) => Schema::hasColumn('stock_movements', $column),
            ARRAY_FILTER_USE_BOTH
        ));
    }

    private function consumptionNote(InventoryLot $sourceLot, Product $outputProduct, ?ProductVariant $outputVariant, int $packCount, array $consumption): string
    {
        $name = $this->outputName($outputProduct, $outputVariant);

        if (in_array($consumption['mode'], ['weight', 'variable_weight'], true)) {
            return "Repack consumed {$consumption['source_weight']} kg from lot #{$sourceLot->id} into {$packCount} × {$name}.";
        }

        if ($consumption['mode'] === 'piece') {
            return "Repack consumed {$consumption['source_quantity']} source unit(s) / {$consumption['source_piece_count']} piece(s) from lot #{$sourceLot->id} into {$packCount} × {$name}.";
        }

        return "Repack consumed {$consumption['source_quantity']} source unit(s) from lot #{$sourceLot->id} into {$packCount} × {$name}.";
    }


    private function resolveOutputVariant(Product $product, mixed $variantId): ?ProductVariant
    {
        if ($variantId === null || $variantId === '') {
            return null;
        }

        $variant = ProductVariant::query()
            ->lockForUpdate()
            ->findOrFail((int) $variantId);

        if ((int) $variant->product_id !== (int) $product->id) {
            throw ValidationException::withMessages([
                'output_product_variant_id' => 'Selected output variant does not belong to the selected output product.',
            ]);
        }

        return $variant;
    }

    private function targetPackType(Product $product, ?ProductVariant $variant): string
    {
        if ($variant && Schema::hasColumn('product_variants', 'pack_type') && ! empty($variant->pack_type)) {
            return (string) $variant->pack_type;
        }

        if ($variant) {
            return (float) ($variant->product_weight ?? 0) > 0 ? 'fixed_weight_pack' : 'quantity';
        }

        return (string) ($product->pack_type ?? 'quantity');
    }

    private function targetWeightKg(Product $product, ?ProductVariant $variant): ?float
    {
        if ($variant) {
            return $this->positiveOrNull($variant->product_weight ?? null);
        }

        return $this->positiveOrNull($product->product_weight ?? null);
    }

    private function targetPiecesPerPack(Product $product, ?ProductVariant $variant): ?float
    {
        if ($variant && Schema::hasColumn('product_variants', 'pieces_per_pack')) {
            return $this->positiveOrNull($variant->pieces_per_pack ?? null);
        }

        return $this->positiveOrNull($product->pieces_per_pack ?? null);
    }

    private function increaseOutputStock(Product $product, ?ProductVariant $variant, float $quantity): void
    {
        if ($variant) {
            $variant->stock_quantity = round((float) ($variant->stock_quantity ?? 0) + $quantity, 3);
            $variant->manage_stock = true;
            $variant->save();

            $this->syncProductStockFromVariants($product);
            return;
        }

        $product->stock_quantity = round((float) ($product->stock_quantity ?? 0) + $quantity, 3);
        $product->manage_stock = true;
        $product->save();
    }

    private function syncProductStockFromVariants(Product $product): void
    {
        $sum = ProductVariant::query()
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->sum('stock_quantity');

        $product->type = 'variable';
        $product->manage_stock = true;
        $product->stock_quantity = round((float) $sum, 3);
        $product->save();
    }

    private function outputName(Product $product, ?ProductVariant $variant): string
    {
        if (! $variant) {
            return (string) $product->name;
        }

        $label = trim((string) ($variant->name ?? '')) ?: trim((string) ($variant->sku ?? ''));

        return trim($product->name . ($label !== '' ? ' - ' . $label : ''));
    }

    private function existingColumns(string $table, array $columns): array
    {
        if (! Schema::hasTable($table)) {
            return $columns;
        }

        $existing = array_values(array_filter(
            $columns,
            fn (string $column): bool => Schema::hasColumn($table, $column)
        ));

        return $existing !== [] ? $existing : $columns;
    }
}
