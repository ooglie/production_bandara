<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLot;
use App\Models\InventoryPack;
use App\Models\InventoryPiece;
use App\Models\Product;
use App\Models\ProductTransformation;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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
            ->with([
                'product',
                'productVariant',
                'pieces',
                'packs' => fn ($packs) => $packs
                    ->whereIn('status', ['available', 'partially_used'])
                    ->orderBy('id'),
            ])
            ->availableForRepack()
            ->orderBy('product_id')
            ->orderBy('expiry_date')
            ->orderBy('received_date')
            ->orderBy('id')
            ->get();

        $lots->each(function (InventoryLot $lot): void {
            $piecesPerUnit = $this->sourcePiecesPerUnit($lot);
            $lot->setAttribute('repack_pieces_per_unit', $piecesPerUnit);
            $lot->setAttribute('repack_available_piece_count', $this->effectiveAvailablePieces($lot, $piecesPerUnit));
            $lot->setAttribute('repack_available_pack_count', $this->effectiveAvailableFullPacks($lot));
        });

        $recentPacks = InventoryPack::query()
            ->with(['product', 'productVariant', 'sourcePiece', 'sourceLot.product', 'sourceLot.parentLot.product', 'soldOrder'])
            ->latest('id')
            ->limit(12)
            ->get();

        $selectedLotId = $request->integer('source_inventory_lot_id') ?: $request->integer('lot_id') ?: old('source_inventory_lot_id');

        return view('admin.inventory.packs.create', compact('lots', 'recentPacks', 'selectedLotId'));
    }

    /**
     * Return the exact output catalogue for one selected source lot.
     *
     * The Transform Stock page intentionally receives no global product list.
     * This endpoint is called only after a source lot is selected, preventing
     * products belonging to other transformation relationships from appearing.
     */
    public function outputOptions(Request $request)
    {
        $validated = $request->validate([
            'source_inventory_lot_id' => ['required', 'integer', 'exists:inventory_lots,id'],
        ]);

        /** @var InventoryLot $lot */
        $lot = InventoryLot::query()
            ->with(['product', 'productVariant'])
            ->findOrFail((int) $validated['source_inventory_lot_id']);

        if (! $lot->isRepackable() || (string) $lot->lot_status !== 'available') {
            return response()->json([
                'message' => 'Selected source lot is not available for repack.',
                'source_product_id' => (int) $lot->product_id,
                'source_variant_id' => $lot->product_variant_id ? (int) $lot->product_variant_id : null,
                'products' => [],
            ], 422)->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        return response()
            ->json($this->outputContextForLot($lot))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    private function outputContextForLot(InventoryLot $lot): array
    {
        $sourceProductId = (int) $lot->product_id;

        $allowedProductIds = ProductTransformation::query()
            ->where('source_product_id', $sourceProductId)
            ->pluck('target_product_id')
            ->map(fn ($id) => (int) $id)
            ->push($sourceProductId)
            ->unique()
            ->values();

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

        $productsById = Product::query()
            ->select($productColumns)
            ->whereIn('id', $allowedProductIds->all())
            ->get()
            ->keyBy('id');

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
            'inventory_can_repack',
            'is_active',
        ]);

        $variantsByProduct = ProductVariant::query()
            ->select($variantColumns)
            ->whereIn('product_id', $allowedProductIds->all())
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->groupBy('product_id');

        $products = $allowedProductIds
            ->map(function (int $productId) use ($productsById, $variantsByProduct) {
                /** @var Product|null $product */
                $product = $productsById->get($productId);
                if (! $product) {
                    return null;
                }

                $variants = collect($variantsByProduct->get($productId, collect()))
                    ->map(function (ProductVariant $variant): array {
                        $label = trim((string) ($variant->name ?? ''));
                        if ($label === '') {
                            $packType = (string) ($variant->pack_type ?? '');
                            if ($packType === 'fixed_piece_pack' && (float) ($variant->pieces_per_pack ?? 0) > 0) {
                                $label = rtrim(rtrim(number_format((float) $variant->pieces_per_pack, 3), '0'), '.') . ' pcs pack';
                            } elseif ($packType === 'fixed_weight_pack' && (float) ($variant->product_weight ?? 0) > 0) {
                                $label = rtrim(rtrim(number_format((float) $variant->product_weight, 3), '0'), '.') . ' kg pack';
                            } else {
                                $label = (string) ($variant->sku ?? ('Variant ' . $variant->id));
                            }
                        }

                        return [
                            'id' => (int) $variant->id,
                            'product_id' => (int) $variant->product_id,
                            'label' => $label,
                            'name' => (string) ($variant->name ?? ''),
                            'sku' => (string) ($variant->sku ?? ''),
                            'pack_type' => (string) ($variant->pack_type ?? 'quantity'),
                            'product_weight' => $variant->product_weight !== null ? (float) $variant->product_weight : 0,
                            'pieces_per_pack' => $variant->pieces_per_pack !== null ? (float) $variant->pieces_per_pack : 0,
                            'pricing_unit' => (string) ($variant->pricing_unit ?? 'pack'),
                            'is_active' => (bool) ($variant->is_active ?? true),
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'id' => (int) $product->id,
                    'name' => (string) $product->name,
                    'sku' => (string) ($product->sku ?? ''),
                    'inventory_role' => (string) ($product->inventory_role ?? (($product->is_active ?? false) ? 'saleable' : 'internal')),
                    'pack_type' => (string) ($product->pack_type ?? 'quantity'),
                    'sell_unit' => (string) ($product->sell_unit ?? 'piece'),
                    'product_weight' => $product->product_weight !== null ? (float) $product->product_weight : 0,
                    'pieces_per_pack' => $product->pieces_per_pack !== null ? (float) $product->pieces_per_pack : 0,
                    'is_active' => (bool) $product->is_active,
                    'variants' => $variants,
                ];
            })
            ->filter()
            ->sortBy(fn (array $product) => ($product['id'] === $sourceProductId ? '0|' : '1|') . mb_strtolower($product['name']))
            ->values()
            ->all();

        return [
            'source_lot_id' => (int) $lot->id,
            'source_product_id' => $sourceProductId,
            'source_variant_id' => $lot->product_variant_id ? (int) $lot->product_variant_id : null,
            'products' => $products,
        ];
    }

    public function store(Request $request)
    {
        $this->normalizeLegacyOutputPayload($request);

        $validated = $request->validate([
            'source_inventory_lot_id' => ['required', 'integer', 'exists:inventory_lots,id'],
            'source_inventory_piece_id' => ['nullable', 'integer', 'exists:inventory_pieces,id'],
            'source_pieces_per_unit' => ['nullable', 'numeric', 'min:0.001'],
            'outputs' => ['required', 'array', 'min:1', 'max:30'],
            'outputs.*.output_product_id' => ['required', 'integer', 'exists:products,id'],
            'outputs.*.output_product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'outputs.*.pack_count' => ['required', 'integer', 'min:1', 'max:10000'],
            'outputs.*.pieces_per_pack' => ['nullable', 'numeric', 'min:0.001'],
            'outputs.*.output_weight_kg' => ['nullable', 'numeric', 'min:0.001'],
            'packed_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'batch_code' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $createdPackCount = 0;
        $createdTargetCount = 0;

        DB::transaction(function () use ($validated, $request, &$createdPackCount, &$createdTargetCount) {
            /** @var InventoryLot $sourceLot */
            $sourceLot = InventoryLot::query()
                ->with(['product', 'productVariant'])
                ->lockForUpdate()
                ->findOrFail((int) $validated['source_inventory_lot_id']);

            if (! $sourceLot->isRepackable() || $sourceLot->lot_status !== 'available') {
                throw ValidationException::withMessages([
                    'source_inventory_lot_id' => 'Selected source lot is not available for repack.',
                ]);
            }

            $sourceProduct = Product::query()->lockForUpdate()->findOrFail((int) $sourceLot->product_id);
            $sourceVariant = $sourceLot->product_variant_id
                ? ProductVariant::query()->lockForUpdate()->find((int) $sourceLot->product_variant_id)
                : null;

            $sourceLot->setRelation('product', $sourceProduct);
            $sourceLot->setRelation('productVariant', $sourceVariant);
            $this->normalizeLegacySourceCartonBalance(
                $sourceLot,
                $sourceProduct,
                $sourceVariant,
                $validated['source_pieces_per_unit'] ?? null,
                $request->user()?->id
            );

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

            $allowedOutputProductIds = ProductTransformation::query()
                ->where('source_product_id', $sourceProduct->id)
                ->pluck('target_product_id')
                ->map(fn ($id) => (int) $id)
                ->push((int) $sourceProduct->id)
                ->unique()
                ->values()
                ->all();

            $preparedOutputs = [];
            $targetKeys = [];
            $modes = [];

            foreach (array_values($validated['outputs']) as $index => $row) {
                /** @var Product $outputProduct */
                $outputProduct = Product::query()
                    ->lockForUpdate()
                    ->findOrFail((int) $row['output_product_id']);

                if (! in_array((int) $outputProduct->id, $allowedOutputProductIds, true)) {
                    throw ValidationException::withMessages([
                        "outputs.{$index}.output_product_id" => 'The selected output product is not associated with this source product.',
                    ]);
                }

                $variantField = "outputs.{$index}.output_product_variant_id";
                $outputVariant = $this->resolveOutputVariant(
                    $outputProduct,
                    $row['output_product_variant_id'] ?? null,
                    $variantField
                );

                if ((int) $sourceProduct->id === (int) $outputProduct->id
                    && (int) ($sourceVariant?->id ?? 0) === (int) ($outputVariant?->id ?? 0)) {
                    throw ValidationException::withMessages([
                        $variantField => 'The output must be a different product or variant from the source stock.',
                    ]);
                }

                $targetKey = (int) $outputProduct->id . ':' . (int) ($outputVariant?->id ?? 0);
                if (isset($targetKeys[$targetKey])) {
                    throw ValidationException::withMessages([
                        "outputs.{$index}.output_product_id" => 'The same output product/variant is listed more than once. Increase its pack count instead.',
                    ]);
                }
                $targetKeys[$targetKey] = true;

                $packCount = (int) $row['pack_count'];
                $calculationInput = [
                    'pieces_per_pack' => $row['pieces_per_pack'] ?? null,
                    'source_pieces_per_unit' => $validated['source_pieces_per_unit'] ?? null,
                    'output_weight_kg' => $row['output_weight_kg'] ?? null,
                ];

                $consumption = $this->calculateProductConsumption(
                    $sourceLot,
                    $outputProduct,
                    $outputVariant,
                    $calculationInput,
                    $packCount
                );

                $modes[(string) $consumption['mode']] = true;
                $preparedOutputs[] = [
                    'product' => $outputProduct,
                    'variant' => $outputVariant,
                    'pack_count' => $packCount,
                    'consumption' => $consumption,
                ];
            }

            if (count($modes) > 1) {
                throw ValidationException::withMessages([
                    'outputs' => 'All output rows in one transform must use the same stock basis: pieces, weight, or quantity. Create a separate transform for a different basis.',
                ]);
            }

            $aggregateConsumption = $this->aggregateConsumptions($preparedOutputs);
            $this->assertAggregateSourceAvailability($sourceLot, $sourcePiece, $aggregateConsumption);

            $this->deductSourcePiece($sourcePiece, $aggregateConsumption, $request->user()?->id);
            $sourcePackConsumption = $this->consumeSourceInventoryPacks(
                $sourceLot,
                $sourceProduct,
                $sourceVariant,
                $aggregateConsumption,
                $request->user()?->id
            );

            $sourceLotQuantityDeduction = $sourcePackConsumption['handled']
                ? (float) $sourcePackConsumption['lot_quantity']
                : null;

            $this->deductSourceLot(
                $sourceLot,
                $aggregateConsumption,
                $request->user()?->id,
                $sourceLotQuantityDeduction
            );

            $sourceStockDeduction = $sourcePackConsumption['handled']
                ? (float) $sourcePackConsumption['stock_quantity']
                : (float) $aggregateConsumption['source_stock_quantity'];
            $this->deductSourceStock($sourceProduct, $sourceVariant, $sourceStockDeduction);

            if ($sourceStockDeduction > 0.0005) {
                $this->writeStockMovement(
                    productId: (int) $sourceProduct->id,
                    variantId: $sourceLot->product_variant_id ? (int) $sourceLot->product_variant_id : null,
                    quantity: -1 * $sourceStockDeduction,
                    referenceId: (int) $sourceLot->id,
                    costPrice: $aggregateConsumption['source_unit_cost'],
                    notes: $this->aggregateConsumptionNote($sourceLot, $preparedOutputs, $aggregateConsumption)
                );
            }

            $packedDate = $validated['packed_date'] ?? now()->toDateString();
            $expiryDate = $validated['expiry_date'] ?? ($sourceLot->expiry_date ? $sourceLot->expiry_date->format('Y-m-d') : null);
            $batchCode = trim((string) ($validated['batch_code'] ?? '')) ?: ($sourceLot->batch_code ?: 'RP-' . now()->format('Ymd'));
            $notes = $validated['notes'] ?? null;

            foreach ($preparedOutputs as $preparedOutput) {
                /** @var Product $outputProduct */
                $outputProduct = $preparedOutput['product'];
                /** @var ProductVariant|null $outputVariant */
                $outputVariant = $preparedOutput['variant'];
                $packCount = (int) $preparedOutput['pack_count'];
                $consumption = $preparedOutput['consumption'];

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

                $this->createOutputPacks(
                    outputLot: $outputLot,
                    sourcePiece: $sourcePiece,
                    outputProduct: $outputProduct,
                    outputVariant: $outputVariant,
                    consumption: $consumption,
                    packCount: $packCount,
                    packedDate: $packedDate,
                    expiryDate: $expiryDate,
                    batchCode: $batchCode,
                    notes: $notes,
                    userId: $request->user()?->id
                );

                $stockIncrease = (float) ($consumption['stock_quantity'] ?? $packCount);
                $this->increaseOutputStock($outputProduct, $outputVariant, $stockIncrease);

                $this->writeStockMovement(
                    productId: (int) $outputProduct->id,
                    variantId: $outputVariant?->id,
                    quantity: $stockIncrease,
                    referenceId: (int) $outputLot->id,
                    costPrice: $consumption['pack_cost'],
                    notes: "Repack created {$packCount} pack(s) for " . $this->outputName($outputProduct, $outputVariant) . " from lot #{$sourceLot->id}."
                );

                $createdPackCount += $packCount;
            }

            $createdTargetCount = count($preparedOutputs);
        });

        $targetLabel = $createdTargetCount === 1 ? 'output variant' : 'output variants';

        return redirect()
            ->route('admin.inventory.packs.index')
            ->with('status', "Created {$createdPackCount} pack(s) across {$createdTargetCount} {$targetLabel} from source inventory.");
    }

    private function normalizeLegacyOutputPayload(Request $request): void
    {
        $outputs = $request->input('outputs');
        if (is_array($outputs) && $outputs !== []) {
            return;
        }

        if (! $request->filled('output_product_id')) {
            return;
        }

        $request->merge([
            'outputs' => [[
                'output_product_id' => $request->input('output_product_id'),
                'output_product_variant_id' => $request->input('output_product_variant_id'),
                'pack_count' => $request->input('pack_count'),
                'pieces_per_pack' => $request->input('pieces_per_pack'),
                'output_weight_kg' => $request->input('output_weight_kg'),
            ]],
        ]);
    }

    /**
     * @param array<int, array{product: Product, variant: ?ProductVariant, pack_count: int, consumption: array<string, mixed>}> $preparedOutputs
     * @return array<string, mixed>
     */
    private function aggregateConsumptions(array $preparedOutputs): array
    {
        $first = $preparedOutputs[0]['consumption'];
        $sourceWeight = null;
        $sourcePieceCount = null;

        foreach ($preparedOutputs as $preparedOutput) {
            $consumption = $preparedOutput['consumption'];
            if ($consumption['source_weight'] !== null) {
                $sourceWeight = round((float) ($sourceWeight ?? 0) + (float) $consumption['source_weight'], 3);
            }
            if ($consumption['source_piece_count'] !== null) {
                $sourcePieceCount = round((float) ($sourcePieceCount ?? 0) + (float) $consumption['source_piece_count'], 3);
            }
        }

        return [
            'mode' => (string) $first['mode'],
            'source_quantity' => round(array_sum(array_map(
                fn (array $row): float => (float) $row['consumption']['source_quantity'],
                $preparedOutputs
            )), 6),
            'source_stock_quantity' => round(array_sum(array_map(
                fn (array $row): float => (float) $row['consumption']['source_stock_quantity'],
                $preparedOutputs
            )), 6),
            'source_weight' => $sourceWeight,
            'source_piece_count' => $sourcePieceCount,
            'source_unit_cost' => $first['source_unit_cost'] ?? null,
        ];
    }

    private function assertAggregateSourceAvailability(InventoryLot $sourceLot, ?InventoryPiece $sourcePiece, array $consumption): void
    {
        $mode = (string) ($consumption['mode'] ?? 'quantity');

        if ($mode === 'piece') {
            $requiredPieces = (float) ($consumption['source_piece_count'] ?? 0);
            $availablePieces = $this->availablePiecesForRepack($sourceLot);

            if ($availablePieces !== null && $requiredPieces > $availablePieces + 0.0005) {
                throw ValidationException::withMessages([
                    'outputs' => "The combined output rows need {$requiredPieces} source piece(s), but the lot has only {$availablePieces} available.",
                ]);
            }

            if ($availablePieces === null
                && (float) $consumption['source_quantity'] > (float) ($sourceLot->available_quantity ?? 0) + 0.0005) {
                throw ValidationException::withMessages([
                    'outputs' => 'The combined output rows need more source quantity than is available in this lot.',
                ]);
            }

            return;
        }

        if (in_array($mode, ['weight', 'variable_weight'], true)) {
            $requiredWeight = (float) ($consumption['source_weight'] ?? 0);
            $availableWeight = $sourcePiece
                ? (float) ($sourcePiece->available_weight_kg ?? $sourcePiece->weight_kg ?? 0)
                : $this->availableWeightForRepack($sourceLot);

            if ($requiredWeight > $availableWeight + 0.0005) {
                throw ValidationException::withMessages([
                    'outputs' => "The combined output rows need {$requiredWeight} kg, but only {$availableWeight} kg is available.",
                ]);
            }

            return;
        }

        $requiredQuantity = (float) ($consumption['source_quantity'] ?? 0);
        $availableQuantity = (float) ($sourceLot->available_quantity ?? 0);
        if ($requiredQuantity > $availableQuantity + 0.0005) {
            throw ValidationException::withMessages([
                'outputs' => "The combined output rows need {$requiredQuantity} source unit(s), but only {$availableQuantity} are available.",
            ]);
        }
    }

    private function createOutputPacks(InventoryLot $outputLot, ?InventoryPiece $sourcePiece, Product $outputProduct, ?ProductVariant $outputVariant, array $consumption, int $packCount, string $packedDate, ?string $expiryDate, string $batchCode, ?string $notes, ?int $userId): void
    {
        $startNo = ((int) InventoryPack::query()
            ->where('source_inventory_lot_id', $outputLot->id)
            ->where('product_id', $outputProduct->id)
            ->when($outputVariant, fn ($query) => $query->where('product_variant_id', $outputVariant->id), fn ($query) => $query->whereNull('product_variant_id'))
            ->max('pack_no')) + 1;

        $sourceQuantityPerPack = round((float) $consumption['source_quantity'] / $packCount, 6);
        $sourceWeightPerPack = $consumption['source_weight'] !== null
            ? round((float) $consumption['source_weight'] / $packCount, 3)
            : null;
        $outputQuantityPerPack = $consumption['output_quantity_per_pack'] ?? 1;
        $packCodePrefix = $batchCode . '-L' . $outputLot->id;

        for ($i = 0; $i < $packCount; $i++) {
            InventoryPack::create([
                'production_run_id' => null,
                'source_inventory_lot_id' => $outputLot->id,
                'source_inventory_piece_id' => $sourcePiece?->id,
                'product_id' => $outputProduct->id,
                'product_variant_id' => $outputVariant?->id,
                'pack_no' => $startNo + $i,
                'pack_code' => $packCodePrefix . '-' . str_pad((string) ($startNo + $i), 3, '0', STR_PAD_LEFT),
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
                'created_by_id' => $userId,
                'updated_by_id' => $userId,
            ]);
        }
    }

    /**
     * @param array<int, array{product: Product, variant: ?ProductVariant, pack_count: int, consumption: array<string, mixed>}> $preparedOutputs
     */
    private function aggregateConsumptionNote(InventoryLot $sourceLot, array $preparedOutputs, array $consumption): string
    {
        $outputs = collect($preparedOutputs)
            ->map(fn (array $row): string => $row['pack_count'] . ' × ' . $this->outputName($row['product'], $row['variant']))
            ->implode(', ');

        if ($consumption['mode'] === 'piece') {
            return "Repack consumed {$consumption['source_piece_count']} piece(s) from lot #{$sourceLot->id} into {$outputs}.";
        }

        if (in_array($consumption['mode'], ['weight', 'variable_weight'], true)) {
            return "Repack consumed {$consumption['source_weight']} kg from lot #{$sourceLot->id} into {$outputs}.";
        }

        return "Repack consumed {$consumption['source_quantity']} source unit(s) from lot #{$sourceLot->id} into {$outputs}.";
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
        $lot->lot_code = $this->generateOutputLotCode($sourceLot, $product, $variant);
        $lot->product_id = $product->id;
        $lot->product_variant_id = $variant?->id;
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

    private function generateOutputLotCode(InventoryLot $sourceLot, Product $product, ?ProductVariant $variant = null): string
    {
        $base = strtoupper((string) ($sourceLot->lot_code ?: 'LOT-' . $sourceLot->id));
        $base = preg_replace('/[^A-Z0-9\-]+/', '-', $base) ?: 'LOT';
        $base = trim(preg_replace('/\-+/', '-', $base), '-');
        $targetSku = $variant?->sku ?: $product->sku ?: 'P' . $product->id;
        $sku = strtoupper((string) $targetSku);
        $sku = preg_replace('/[^A-Z0-9\-]+/', '-', $sku) ?: 'OUT';
        $sku = trim(preg_replace('/\-+/', '-', $sku), '-');

        do {
            $code = substr($base, 0, 16)
                . '-RP-'
                . substr($sku, 0, 16)
                . '-'
                . now()->format('Hisv')
                . '-'
                . Str::upper(Str::random(4));
        } while (InventoryLot::query()->where('lot_code', $code)->exists());

        return $code;
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
        $piecesPerPack = $this->resolvedPiecesPerPack($product, $variant, $validated);
        $weight = $this->targetWeightKg($product, $variant) ?: $this->positiveDecimal($validated['output_weight_kg'] ?? null);

        // Piece configuration always wins over a stored pack weight. Dimsum packs may
        // retain a calculated/label weight for costing, but carton breakup must consume
        // 10/20/240 pieces rather than kilograms.
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

    private function resolvedPiecesPerPack(Product $product, ?ProductVariant $variant, array $validated): float
    {
        $configured = $this->targetPiecesPerPack($product, $variant);
        if ($configured > 0) {
            return $configured;
        }

        $submitted = $this->positiveDecimal($validated['pieces_per_pack'] ?? null);
        if ($submitted > 0) {
            return $submitted;
        }

        // Backward-compatible fallback for existing variants whose display name was
        // saved as "20pc pack" but pieces_per_pack was not populated.
        foreach ([
            $variant?->name,
            $variant?->sku,
            $product->name,
            $product->sku,
        ] as $label) {
            $inferred = $this->inferPiecesPerPackFromLabel($label);
            if ($inferred > 0) {
                return $inferred;
            }
        }

        return 0.0;
    }

    private function inferPiecesPerPackFromLabel(?string $label): float
    {
        $label = trim((string) $label);
        if ($label === '') {
            return 0.0;
        }

        if (preg_match('/(?<!\d)(\d+(?:\.\d+)?)\s*(?:pc|pcs|piece|pieces)\b/i', $label, $matches) !== 1) {
            return 0.0;
        }

        return $this->positiveDecimal($matches[1] ?? null);
    }

    private function calculatePiecePackConsumption(InventoryLot $sourceLot, Product $product, ?ProductVariant $variant, array $validated, int $packCount): array
    {
        $piecesPerPack = $this->resolvedPiecesPerPack($product, $variant, $validated);

        if ($piecesPerPack <= 0) {
            throw ValidationException::withMessages([
                'pieces_per_pack' => 'Pieces per pack is required for fixed-piece output products.',
            ]);
        }

        $sourcePiecesPerUnit = $this->positiveDecimal($validated['source_pieces_per_unit'] ?? null)
            ?: $this->sourcePiecesPerUnit($sourceLot);
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

    /**
     * Retire physical source cartons as soon as they are opened for a piece-pack
     * transformation. Vendor quantity inward creates one inventory_packs row per
     * carton, so this keeps the original carton from remaining saleable after any
     * of its pieces have been used.
     *
     * @return array{handled: bool, stock_quantity: float, lot_quantity: float}
     */
    private function consumeSourceInventoryPacks(InventoryLot $sourceLot, Product $sourceProduct, ?ProductVariant $sourceVariant, array $consumption, ?int $userId): array
    {
        if (($consumption['mode'] ?? null) !== 'piece'
            || ! Schema::hasTable('inventory_packs')
            || ! Schema::hasColumn('inventory_packs', 'available_pieces')) {
            return ['handled' => false, 'stock_quantity' => 0.0, 'lot_quantity' => 0.0];
        }

        $sourcePacks = InventoryPack::query()
            ->where('source_inventory_lot_id', $sourceLot->id)
            ->where('product_id', $sourceProduct->id)
            ->when(
                $sourceVariant,
                fn ($query) => $query->where('product_variant_id', $sourceVariant->id),
                fn ($query) => $query->whereNull('product_variant_id')
            )
            ->orderByRaw("CASE WHEN status = 'partially_used' THEN 0 WHEN status IS NULL OR status = 'available' THEN 1 ELSE 2 END")
            ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expiry_date')
            ->orderBy('packed_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $requiredPieces = round((float) ($consumption['source_piece_count'] ?? 0), 3);

        // Older/manual carton lots may not have one physical inventory_packs row
        // per box. Still deduct whole boxes (never fractional cartons) while the
        // aggregate available_piece_count retains any loose remainder.
        if ($sourcePacks->isEmpty()) {
            $piecesPerUnit = $this->sourcePiecesPerUnit($sourceLot);
            if ($piecesPerUnit <= 1 || $requiredPieces <= 0) {
                return ['handled' => false, 'stock_quantity' => 0.0, 'lot_quantity' => 0.0];
            }

            $availablePieces = $this->availablePiecesForRepack($sourceLot) ?? 0.0;
            $fullPacks = $sourceLot->available_pack_count !== null
                ? max((int) $sourceLot->available_pack_count, 0)
                : max((int) floor((float) ($sourceLot->available_quantity ?? 0) + 0.0005), 0);
            $piecesInUnopenedPacks = min($availablePieces, round($fullPacks * $piecesPerUnit, 3));
            $alreadyLoosePieces = max($availablePieces - $piecesInUnopenedPacks, 0);
            $piecesNeedingNewPacks = max($requiredPieces - $alreadyLoosePieces, 0);
            $openedPacks = $piecesNeedingNewPacks > 0
                ? (int) ceil(($piecesNeedingNewPacks - 0.0005) / $piecesPerUnit)
                : 0;

            if ($openedPacks > $fullPacks) {
                throw ValidationException::withMessages([
                    'outputs' => "The source lot needs {$openedPacks} unopened box(es), but only {$fullPacks} are available.",
                ]);
            }

            if (Schema::hasColumn('inventory_lots', 'available_pack_count')) {
                $sourceLot->available_pack_count = max($fullPacks - $openedPacks, 0);
            }

            return [
                'handled' => true,
                'stock_quantity' => (float) $openedPacks,
                'lot_quantity' => (float) $openedPacks,
            ];
        }

        if ($requiredPieces <= 0) {
            return ['handled' => true, 'stock_quantity' => 0.0, 'lot_quantity' => 0.0];
        }

        $availablePieces = round((float) $sourcePacks->sum(function (InventoryPack $pack): float {
            return $this->availablePiecesInSourcePack($pack);
        }), 3);

        if ($requiredPieces > $availablePieces + 0.0005) {
            throw ValidationException::withMessages([
                'pack_count' => "The source carton records contain only {$availablePieces} available piece(s), but this transform needs {$requiredPieces}.",
            ]);
        }

        $remaining = $requiredPieces;
        $openedCartons = 0.0;

        foreach ($sourcePacks as $pack) {
            if ($remaining <= 0.0005) {
                break;
            }

            $status = strtolower((string) ($pack->status ?? 'available'));
            if (! in_array($status, ['available', 'partially_used', ''], true)) {
                continue;
            }

            $packPieces = $this->availablePiecesInSourcePack($pack);
            if ($packPieces <= 0.0005) {
                continue;
            }

            $saleablePackQuantity = round((float) ($pack->available_pack_quantity ?? $pack->pack_quantity ?? 0), 3);
            $wasUnopened = in_array($status, ['available', ''], true) && $saleablePackQuantity > 0.0005;
            $take = min($packPieces, $remaining);
            $piecesLeft = round(max($packPieces - $take, 0), 3);

            $pack->available_pieces = $piecesLeft;

            if ($wasUnopened) {
                // One row represents one physical carton in the current inward
                // flow. Once opened, it is no longer a saleable full carton.
                $pack->available_pack_quantity = 0;
                $openedCartons += 1.0;
            }

            $pack->status = $piecesLeft > 0.0005 ? 'partially_used' : 'transformed';
            $pack->updated_by_id = $userId;
            $pack->notes = trim((string) ($pack->notes ?? '')
                . "\nUsed " . rtrim(rtrim(number_format($take, 3, '.', ''), '0'), '.')
                . ' piece(s) in Transform Stock on ' . now()->format('Y-m-d H:i') . '.');
            $pack->save();

            $remaining = round($remaining - $take, 3);
        }

        if ($remaining > 0.0005) {
            throw ValidationException::withMessages([
                'pack_count' => "Unable to allocate {$remaining} source piece(s) from the selected carton records.",
            ]);
        }

        if (Schema::hasColumn('inventory_lots', 'available_pack_count')) {
            $sourceLot->available_pack_count = $sourcePacks->filter(function (InventoryPack $pack): bool {
                $status = strtolower((string) ($pack->status ?? 'available'));

                return in_array($status, ['available', ''], true)
                    && (float) ($pack->available_pack_quantity ?? 0) > 0.0005;
            })->count();
        }

        return ['handled' => true, 'stock_quantity' => round($openedCartons, 3), 'lot_quantity' => round($openedCartons, 3)];
    }

    private function availablePiecesInSourcePack(InventoryPack $pack): float
    {
        $status = strtolower((string) ($pack->status ?? 'available'));
        if (! in_array($status, ['available', 'partially_used', ''], true)) {
            return 0.0;
        }

        if ($pack->available_pieces !== null) {
            return round(max((float) $pack->available_pieces, 0), 3);
        }

        if ($pack->total_pieces !== null) {
            return round(max((float) $pack->total_pieces, 0), 3);
        }

        $piecesPerPack = max((float) ($pack->pieces_per_pack ?? 0), 0);
        $packQuantity = max((float) ($pack->available_pack_quantity ?? $pack->pack_quantity ?? 0), 0);

        return round($piecesPerPack * $packQuantity, 3);
    }

    private function deductSourceLot(InventoryLot $sourceLot, array $consumption, ?int $userId, ?float $quantityDeduction = null): void
    {
        $currentQty = round((float) ($sourceLot->available_quantity ?? 0), 3);
        $currentWeight = $this->availableWeightForRepack($sourceLot);
        $deductQuantity = $quantityDeduction ?? (float) $consumption['source_quantity'];

        // For carton-backed piece stock, available_quantity represents unopened
        // cartons. Opening one carton removes one full carton from sale, while
        // any unused loose pieces remain in available_piece_count.
        $sourceLot->available_quantity = round(max($currentQty - $deductQuantity, 0), 3);

        if ($consumption['source_weight'] !== null && Schema::hasColumn('inventory_lots', 'available_weight_kg')) {
            $sourceLot->available_weight_kg = round(max($currentWeight - (float) $consumption['source_weight'], 0), 3);
        }

        if ($consumption['source_piece_count'] !== null
            && Schema::hasColumn('inventory_lots', 'available_piece_count')
            && $sourceLot->available_piece_count !== null) {
            $sourceLot->available_piece_count = max(
                (int) ($sourceLot->available_piece_count ?? 0) - (int) round((float) $consumption['source_piece_count']),
                0
            );
        }

        if (Schema::hasColumn('inventory_lots', 'consumed_quantity')) {
            $sourceLot->consumed_quantity = round(
                (float) ($sourceLot->consumed_quantity ?? 0) + (float) $consumption['source_quantity'],
                3
            );
        }

        $remainingQty = round((float) ($sourceLot->available_quantity ?? 0), 3);
        $remainingWeight = round((float) ($sourceLot->available_weight_kg ?? 0), 3);
        $remainingPieces = (int) ($sourceLot->available_piece_count ?? 0);
        $mode = (string) ($consumption['mode'] ?? 'quantity');

        $isExhausted = match ($mode) {
            'piece' => $sourceLot->available_piece_count !== null
                ? $remainingPieces <= 0
                : $remainingQty <= 0.0005,
            'weight', 'variable_weight' => $remainingWeight <= 0.0005,
            default => $remainingQty <= 0.0005,
        };

        if ($isExhausted) {
            $sourceLot->lot_status = 'exhausted';
            if ($mode === 'piece') {
                $sourceLot->available_quantity = 0;
                if (Schema::hasColumn('inventory_lots', 'available_weight_kg')) {
                    $sourceLot->available_weight_kg = 0;
                }
                if (Schema::hasColumn('inventory_lots', 'available_pack_count')) {
                    $sourceLot->available_pack_count = 0;
                }
            }
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

    private function sourcePiecesPerUnit(InventoryLot $lot): float
    {
        $lotConfigured = (float) ($lot->pieces_per_pack ?? 0);
        $variantConfigured = $lot->relationLoaded('productVariant')
            ? (float) ($lot->productVariant?->pieces_per_pack ?? 0)
            : 0.0;
        $productConfigured = $lot->relationLoaded('product')
            ? (float) ($lot->product?->pieces_per_pack ?? 0)
            : 0.0;

        // A legacy value of 1 often means "one box" rather than "one piece".
        // Prefer any explicit multi-piece configuration before accepting it.
        foreach ([$lotConfigured, $variantConfigured, $productConfigured] as $configured) {
            if ($configured > 1) {
                return round($configured, 3);
            }
        }

        // Legacy variants were sometimes named "Box of 240", "Pack 240", or
        // had a SKU ending in -240 without saving pieces_per_pack. Infer only
        // from explicit carton/pack wording or a trailing SKU number.
        foreach ([
            $lot->relationLoaded('productVariant') ? $lot->productVariant?->name : null,
            $lot->relationLoaded('productVariant') ? $lot->productVariant?->sku : null,
            $lot->relationLoaded('product') ? $lot->product?->name : null,
            $lot->relationLoaded('product') ? $lot->product?->sku : null,
        ] as $label) {
            $inferred = $this->inferSourcePiecesPerUnitFromLabel($label);
            if ($inferred > 1) {
                return $inferred;
            }
        }

        $availableQuantity = (float) ($lot->available_quantity ?? 0);
        $availablePieces = (float) ($lot->available_piece_count ?? 0);
        if ($availableQuantity > 0 && $availablePieces > $availableQuantity) {
            return round($availablePieces / $availableQuantity, 3);
        }

        foreach ([$lotConfigured, $variantConfigured, $productConfigured] as $configured) {
            if ($configured > 0) {
                return round($configured, 3);
            }
        }

        return 1.0;
    }

    private function inferSourcePiecesPerUnitFromLabel(?string $label): float
    {
        $label = trim((string) $label);
        if ($label === '') {
            return 0.0;
        }

        $pieceCount = $this->inferPiecesPerPackFromLabel($label);
        if ($pieceCount > 0) {
            return $pieceCount;
        }

        if (preg_match('/\b(?:box|carton|pack)\s*(?:of\s*)?(\d+(?:\.\d+)?)\b/i', $label, $matches) === 1) {
            return $this->positiveDecimal($matches[1] ?? null);
        }

        if (preg_match('/\b(\d+(?:\.\d+)?)\s*(?:count|ct)\b/i', $label, $matches) === 1) {
            return $this->positiveDecimal($matches[1] ?? null);
        }

        if (preg_match('/[-_](\d+(?:\.\d+)?)$/', $label, $matches) === 1) {
            return $this->positiveDecimal($matches[1] ?? null);
        }

        return 0.0;
    }

    private function effectiveAvailableFullPacks(InventoryLot $lot): int
    {
        if ($lot->relationLoaded('packs') && $lot->packs->isNotEmpty()) {
            return $lot->packs->filter(function (InventoryPack $pack): bool {
                $status = strtolower((string) ($pack->status ?? 'available'));

                return in_array($status, ['available', ''], true)
                    && (float) ($pack->available_pack_quantity ?? $pack->pack_quantity ?? 0) > 0.0005;
            })->count();
        }

        if ($lot->available_pack_count !== null) {
            return max((int) $lot->available_pack_count, 0);
        }

        return max((int) round((float) ($lot->available_quantity ?? 0)), 0);
    }

    private function effectiveAvailablePieces(InventoryLot $lot, ?float $piecesPerUnit = null): float
    {
        $piecesPerUnit = $piecesPerUnit && $piecesPerUnit > 0
            ? round($piecesPerUnit, 3)
            : $this->sourcePiecesPerUnit($lot);

        if ($lot->relationLoaded('packs') && $lot->packs->isNotEmpty()) {
            $packPieces = round((float) $lot->packs->sum(function (InventoryPack $pack) use ($piecesPerUnit): float {
                $status = strtolower((string) ($pack->status ?? 'available'));
                if (! in_array($status, ['available', 'partially_used', ''], true)) {
                    return 0.0;
                }

                $available = $pack->available_pieces !== null ? (float) $pack->available_pieces : null;
                $fullQuantity = max((float) ($pack->available_pack_quantity ?? $pack->pack_quantity ?? 0), 0);

                // Repair only for display here. The transactional method below
                // persists the same correction before stock is transformed.
                if (in_array($status, ['available', ''], true)
                    && $piecesPerUnit > 1
                    && $fullQuantity > 0.0005
                    && ($available === null || $available <= $fullQuantity + 0.0005)) {
                    return round($piecesPerUnit * $fullQuantity, 3);
                }

                if ($available !== null) {
                    return round(max($available, 0), 3);
                }

                if ($pack->total_pieces !== null) {
                    return round(max((float) $pack->total_pieces, 0), 3);
                }

                return round($piecesPerUnit * $fullQuantity, 3);
            }), 3);

            if ($packPieces > 0) {
                return $packPieces;
            }
        }

        $availablePieces = (float) ($lot->available_piece_count ?? 0);
        $fullPacks = $this->effectiveAvailableFullPacks($lot);

        if ($piecesPerUnit > 1
            && $fullPacks > 0
            && ($availablePieces <= 0 || $availablePieces <= $fullPacks + 0.0005)) {
            return round($fullPacks * $piecesPerUnit, 3);
        }

        if ($availablePieces > 0) {
            return round($availablePieces, 3);
        }

        return $fullPacks > 0 && $piecesPerUnit > 1
            ? round($fullPacks * $piecesPerUnit, 3)
            : 0.0;
    }

    private function normalizeLegacySourceCartonBalance(InventoryLot $lot, Product $product, ?ProductVariant $variant, mixed $submittedPiecesPerUnit, ?int $userId): void
    {
        $piecesPerUnit = $this->positiveDecimal($submittedPiecesPerUnit)
            ?: $this->sourcePiecesPerUnit($lot);

        if ($piecesPerUnit <= 1) {
            return;
        }

        $packType = $this->targetPackType($product, $variant);
        if ($packType !== 'fixed_piece_pack' && $this->inferSourcePiecesPerUnitFromLabel($variant?->name ?: $variant?->sku) <= 1) {
            return;
        }

        $sourcePacks = InventoryPack::query()
            ->where('source_inventory_lot_id', $lot->id)
            ->where('product_id', $product->id)
            ->when(
                $variant,
                fn ($query) => $query->where('product_variant_id', $variant->id),
                fn ($query) => $query->whereNull('product_variant_id')
            )
            ->whereIn('status', ['available', 'partially_used'])
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $packRowsChanged = false;
        foreach ($sourcePacks as $pack) {
            $status = strtolower((string) ($pack->status ?? 'available'));
            $fullQuantity = max((float) ($pack->available_pack_quantity ?? $pack->pack_quantity ?? 0), 0);
            $currentAvailable = $pack->available_pieces !== null ? (float) $pack->available_pieces : null;

            if (($pack->pieces_per_pack === null || (float) $pack->pieces_per_pack <= 1) && $piecesPerUnit > 1) {
                $pack->pieces_per_pack = $piecesPerUnit;
                $packRowsChanged = true;
            }

            // Only unopened rows are repaired automatically. A partially-used
            // row may genuinely have one piece left and must never be inflated.
            if (in_array($status, ['available', ''], true)
                && $fullQuantity > 0.0005
                && ($currentAvailable === null || $currentAvailable <= $fullQuantity + 0.0005)) {
                $expectedPieces = round($piecesPerUnit * $fullQuantity, 3);
                $pack->total_pieces = max((float) ($pack->total_pieces ?? 0), $expectedPieces);
                $pack->available_pieces = $expectedPieces;
                $packRowsChanged = true;
            }

            if ($packRowsChanged && $pack->isDirty()) {
                $pack->updated_by_id = $userId;
                $pack->notes = trim((string) ($pack->notes ?? '')
                    . "\nLegacy carton piece balance normalised to {$piecesPerUnit} piece(s) per source unit on "
                    . now()->format('Y-m-d H:i') . '.');
                $pack->save();
            }
        }

        $derivedAvailablePieces = $sourcePacks->isNotEmpty()
            ? round((float) $sourcePacks->sum(fn (InventoryPack $pack): float => $this->availablePiecesInSourcePack($pack)), 3)
            : 0.0;

        $fullPacks = $sourcePacks->isNotEmpty()
            ? $sourcePacks->filter(function (InventoryPack $pack): bool {
                $status = strtolower((string) ($pack->status ?? 'available'));

                return in_array($status, ['available', ''], true)
                    && (float) ($pack->available_pack_quantity ?? $pack->pack_quantity ?? 0) > 0.0005;
            })->count()
            : max((int) ($lot->available_pack_count ?? round((float) ($lot->available_quantity ?? 0))), 0);

        if ($derivedAvailablePieces <= 0 && $fullPacks > 0) {
            $currentPieces = (float) ($lot->available_piece_count ?? 0);
            if ($currentPieces <= 0 || $currentPieces <= $fullPacks + 0.0005) {
                $derivedAvailablePieces = round($fullPacks * $piecesPerUnit, 3);
            }
        }

        $lotChanged = false;
        if ($lot->pieces_per_pack === null || (float) $lot->pieces_per_pack <= 1) {
            $lot->pieces_per_pack = $piecesPerUnit;
            $lotChanged = true;
        }

        if ($derivedAvailablePieces > 0
            && ((float) ($lot->available_piece_count ?? 0) <= $fullPacks + 0.0005
                || abs((float) ($lot->available_piece_count ?? 0) - $derivedAvailablePieces) > 0.0005)) {
            $lot->available_piece_count = (int) round($derivedAvailablePieces);
            $lotChanged = true;
        }

        $receivedUnits = max((float) ($lot->received_quantity ?? 0), (float) ($lot->pack_count ?? 0));
        $expectedOriginalPieces = $receivedUnits > 0 ? round($receivedUnits * $piecesPerUnit, 3) : 0;
        if ($expectedOriginalPieces > (float) ($lot->piece_count ?? 0)) {
            $lot->piece_count = (int) round($expectedOriginalPieces);
            $lotChanged = true;
        }

        if ($lot->available_pack_count === null || (int) $lot->available_pack_count !== $fullPacks) {
            $lot->available_pack_count = $fullPacks;
            $lotChanged = true;
        }

        if ($lotChanged) {
            $lot->updated_by_id = $userId;
            $lot->notes = trim((string) ($lot->notes ?? '')
                . "\nLegacy carton piece balance normalised to {$piecesPerUnit} piece(s) per source unit on "
                . now()->format('Y-m-d H:i') . '.');
            $lot->save();
        }
    }

    private function availablePiecesForRepack(InventoryLot $lot): ?float
    {
        $available = $this->effectiveAvailablePieces($lot, $this->sourcePiecesPerUnit($lot));

        return $available > 0 ? round($available, 3) : null;
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

    private function writeStockMovement(int $productId, ?int $variantId, float $quantity, int $referenceId, ?float $costPrice, string $notes): void
    {
        $attrs = [
            'product_id' => $productId,
            'product_variant_id' => $variantId,
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


    private function resolveOutputVariant(Product $product, mixed $variantId, string $field = 'output_product_variant_id'): ?ProductVariant
    {
        if ($variantId === null || $variantId === '') {
            return null;
        }

        $variant = ProductVariant::query()
            ->lockForUpdate()
            ->findOrFail((int) $variantId);

        if ((int) $variant->product_id !== (int) $product->id) {
            throw ValidationException::withMessages([
                $field => 'Selected output variant does not belong to the selected output product.',
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
