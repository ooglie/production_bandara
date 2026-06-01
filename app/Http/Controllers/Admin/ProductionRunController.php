<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLot;
use App\Models\InventoryPiece;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductionRun;
use App\Models\ProductionRunInput;
use App\Models\ProductionRunOutput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductionRunController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductionRun::query()
            ->withCount(['inputs', 'outputs'])
            ->latest('run_date')
            ->latest('id');

        if ($request->filled('run_type')) {
            $query->where('run_type', $request->run_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $runs = $query->paginate(20)->withQueryString();

        return view('admin.production.index', compact('runs'));
    }

    public function create()
    {
        $inputLots = InventoryLot::query()
            ->with([
                'product',
                'productVariant',
                'vendor',
                'pieces' => function ($q) {
                    $q->where('status', 'available')->orderBy('piece_no');
                },
            ])
            ->availableForRepack()
            ->orderBy('product_id')
            ->orderBy('expiry_date')
            ->orderBy('received_date')
            ->orderBy('id')
            ->get();

        $outputProducts = Product::query()
            ->where('is_active', true)
            ->whereNotNull('lot_stage_default')
            ->whereIn('lot_stage_default', ['slab', 'slice'])
            ->orderBy('name')
            ->get();

        $trimWasteProducts = Product::query()
            ->where('is_active', true)
            ->whereNotNull('lot_stage_default')
            ->whereIn('lot_stage_default', ['trim', 'waste'])
            ->orderBy('name')
            ->get();

        return view('admin.production.create', compact('inputLots', 'outputProducts', 'trimWasteProducts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'run_date' => ['required', 'date'],
            'run_type' => ['required', 'in:raw_to_slab,slab_to_slice,raw_to_slice_direct'],

            'input_product_id' => ['nullable', 'exists:products,id'],
            'input_lot_id' => ['required', 'exists:inventory_lots,id'],

            'selected_piece_ids' => ['nullable', 'array'],
            'selected_piece_ids.*' => ['integer', 'exists:inventory_pieces,id'],

            'consumed_weight_kg' => ['nullable', 'numeric', 'min:0.001'],
            'consumed_quantity' => ['nullable', 'numeric', 'min:0'],

            'trim_weight_kg' => ['nullable', 'numeric', 'min:0'],
            'waste_weight_kg' => ['nullable', 'numeric', 'min:0'],

            'trim_product_id' => ['nullable', 'exists:products,id'],
            'trim_quantity_output' => ['nullable', 'numeric', 'min:0.001'],
            'trim_notes' => ['nullable', 'string'],

            'waste_product_id' => ['nullable', 'exists:products,id'],
            'waste_quantity_output' => ['nullable', 'numeric', 'min:0.001'],
            'waste_notes' => ['nullable', 'string'],

            'notes' => ['nullable', 'string'],

            'outputs' => ['required', 'array', 'min:1'],
            'outputs.*.product_id' => ['required', 'exists:products,id'],
            'outputs.*.product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'outputs.*.output_mode' => ['required', 'in:qty,pieces'],
            'outputs.*.produced_quantity' => ['nullable', 'numeric', 'min:0.001'],
            'outputs.*.produced_weight_kg' => ['nullable', 'numeric', 'min:0.001'],
            'outputs.*.piece_count' => ['nullable', 'integer', 'min:0'],
            'outputs.*.pack_size_kg' => ['nullable', 'numeric', 'min:0'],
            'outputs.*.piece_weights' => ['nullable', 'string'],
            'outputs.*.notes' => ['nullable', 'string'],
        ]);

        $run = null;

        DB::transaction(function () use ($validated, $request, &$run) {
            /** @var InventoryLot $inputLot */
            $inputLot = InventoryLot::query()
                ->with(['product'])
                ->lockForUpdate()
                ->findOrFail($validated['input_lot_id']);

            $runType = $validated['run_type'];

            $trimWeight = round((float) ($validated['trim_weight_kg'] ?? 0), 3);
            $wasteWeight = round((float) ($validated['waste_weight_kg'] ?? 0), 3);

            if (!$inputLot->can_repack) {
                throw ValidationException::withMessages([
                    'input_lot_id' => 'Selected lot cannot be used for production / repack.',
                ]);
            }

            if ($inputLot->lot_status !== 'available') {
                throw ValidationException::withMessages([
                    'input_lot_id' => 'Selected lot is not available.',
                ]);
            }

            if (!empty($validated['input_product_id']) && (int) $validated['input_product_id'] !== (int) $inputLot->product_id) {
                throw ValidationException::withMessages([
                    'input_product_id' => 'Selected input product does not match the chosen lot.',
                ]);
            }

            if ($runType === 'raw_to_slab' && $inputLot->lot_stage !== 'raw') {
                throw ValidationException::withMessages([
                    'input_lot_id' => 'Raw → Slab requires a raw input lot.',
                ]);
            }

            if ($runType === 'raw_to_slice_direct' && $inputLot->lot_stage !== 'raw') {
                throw ValidationException::withMessages([
                    'input_lot_id' => 'Raw → Slice Direct requires a raw input lot.',
                ]);
            }

            if ($runType === 'slab_to_slice' && $inputLot->lot_stage !== 'slab') {
                throw ValidationException::withMessages([
                    'input_lot_id' => 'Slab → Slice requires a slab input lot.',
                ]);
            }

            $piecesMode = ($inputLot->inward_mode === 'pieces');

            $consumedWeight = 0.0;
            $consumedQty = null;
            $consumedPieceCount = null;
            $selectedPieces = collect();

            if ($piecesMode) {
                $selectedPieceIds = collect($validated['selected_piece_ids'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->values();

                if ($selectedPieceIds->isEmpty()) {
                    throw ValidationException::withMessages([
                        'selected_piece_ids' => 'Please select one or more pieces from the chosen lot.',
                    ]);
                }

                $selectedPieces = InventoryPiece::query()
                    ->whereIn('id', $selectedPieceIds)
                    ->where('inventory_lot_id', $inputLot->id)
                    ->lockForUpdate()
                    ->get();

                if ($selectedPieces->count() !== $selectedPieceIds->count()) {
                    throw ValidationException::withMessages([
                        'selected_piece_ids' => 'One or more selected pieces do not belong to the chosen lot.',
                    ]);
                }

                if ($selectedPieces->contains(fn ($piece) => $piece->status !== 'available')) {
                    throw ValidationException::withMessages([
                        'selected_piece_ids' => 'One or more selected pieces are no longer available.',
                    ]);
                }

                $consumedWeight = round((float) $selectedPieces->sum(fn ($piece) => (float) $piece->weight_kg), 3);
                $consumedPieceCount = (int) $selectedPieces->count();

                // raw lots remain weight-driven; slab piece lots are count-driven
                $consumedQty = $inputLot->lot_stage === 'raw'
                    ? $consumedWeight
                    : (float) $consumedPieceCount;
            } else {
                $consumedWeight = round((float) ($validated['consumed_weight_kg'] ?? 0), 3);
                $consumedQty = isset($validated['consumed_quantity']) && $validated['consumed_quantity'] !== null && $validated['consumed_quantity'] !== ''
                    ? round((float) $validated['consumed_quantity'], 3)
                    : null;

                if ($consumedWeight <= 0) {
                    throw ValidationException::withMessages([
                        'consumed_weight_kg' => 'Consumed weight is required.',
                    ]);
                }

                $inputSellUnit = (string) ($inputLot->product->sell_unit ?? 'piece');
                if ($inputSellUnit !== 'kg' && ($consumedQty === null || $consumedQty <= 0)) {
                    throw ValidationException::withMessages([
                        'consumed_quantity' => 'Consumed quantity is required for non-kg input products.',
                    ]);
                }

                if ($inputSellUnit === 'kg' && ($consumedQty === null || $consumedQty <= 0)) {
                    $consumedQty = $consumedWeight;
                }
            }

            $availableWeight = (float) ($inputLot->available_weight_kg ?? 0);
            if ($consumedWeight > $availableWeight + 0.0005) {
                throw ValidationException::withMessages([
                    $piecesMode ? 'selected_piece_ids' : 'consumed_weight_kg' => 'Consumed weight exceeds available lot weight.',
                ]);
            }

            $expectedOutputStage = $runType === 'raw_to_slab' ? 'slab' : 'slice';

            $normalizedOutputs = [];
            $saleableOutputWeight = 0.0;

            foreach ($validated['outputs'] as $i => $row) {
                $product = Product::findOrFail($row['product_id']);

                if (($product->lot_stage_default ?? null) !== $expectedOutputStage) {
                    throw ValidationException::withMessages([
                        "outputs.$i.product_id" => "Selected product does not match expected output stage: {$expectedOutputStage}.",
                    ]);
                }

                $outputMode = $row['output_mode'] ?? 'qty';

                $producedQty = null;
                $producedWeight = null;
                $pieceCount = null;
                $packSizeKg = isset($row['pack_size_kg']) && $row['pack_size_kg'] !== ''
                    ? round((float) $row['pack_size_kg'], 3)
                    : null;
                $pieceWeights = [];

                if ($outputMode === 'pieces') {
                    $lines = preg_split("/\r\n|\n|\r/", trim((string) ($row['piece_weights'] ?? '')));
                    $weights = [];

                    foreach ($lines as $ln) {
                        $ln = trim((string) $ln);
                        if ($ln === '') continue;
                        if (!is_numeric($ln)) {
                            throw ValidationException::withMessages([
                                "outputs.$i.piece_weights" => 'Piece weights must contain one numeric weight per line.',
                            ]);
                        }
                        $weights[] = round((float) $ln, 3);
                    }

                    if (count($weights) === 0) {
                        throw ValidationException::withMessages([
                            "outputs.$i.piece_weights" => 'Please enter one or more individual weights.',
                        ]);
                    }

                    $pieceWeights = $weights;
                    $pieceCount = count($weights);
                    $producedQty = (float) $pieceCount;
                    $producedWeight = round(array_sum($weights), 3);
                    $packSizeKg = null;
                } else {
                    $producedQty = isset($row['produced_quantity']) && $row['produced_quantity'] !== null && $row['produced_quantity'] !== ''
                        ? round((float) $row['produced_quantity'], 3)
                        : 0;

                    $producedWeight = isset($row['produced_weight_kg']) && $row['produced_weight_kg'] !== null && $row['produced_weight_kg'] !== ''
                        ? round((float) $row['produced_weight_kg'], 3)
                        : 0;

                    if ($producedQty <= 0) {
                        throw ValidationException::withMessages([
                            "outputs.$i.produced_quantity" => 'Produced quantity is required.',
                        ]);
                    }

                    if ($producedWeight <= 0) {
                        throw ValidationException::withMessages([
                            "outputs.$i.produced_weight_kg" => 'Produced weight is required.',
                        ]);
                    }

                    $pieceCount = !empty($row['piece_count']) ? (int) $row['piece_count'] : null;
                }

                $saleableOutputWeight += $producedWeight;

                $normalizedOutputs[] = [
                    'product' => $product,
                    'product_variant_id' => !empty($row['product_variant_id']) ? (int) $row['product_variant_id'] : null,
                    'output_stage' => $expectedOutputStage,
                    'output_mode' => $outputMode,
                    'produced_quantity' => $producedQty,
                    'produced_weight_kg' => $producedWeight,
                    'piece_count' => $pieceCount,
                    'pack_size_kg' => $packSizeKg,
                    'piece_weights' => $pieceWeights,
                    'notes' => $row['notes'] ?? null,
                ];
            }

            /*
             * Optional trim / waste inventory outputs
             * If no product selected, these remain header totals only.
             */
            $trimInventoryOutput = null;
            if (!empty($validated['trim_product_id'])) {
                $trimProduct = Product::findOrFail($validated['trim_product_id']);

                if (($trimProduct->lot_stage_default ?? null) !== 'trim') {
                    throw ValidationException::withMessages([
                        'trim_product_id' => 'Selected trim product must have lot stage default = trim.',
                    ]);
                }

                if ($trimWeight <= 0) {
                    throw ValidationException::withMessages([
                        'trim_weight_kg' => 'Trim weight must be greater than zero if you want to create a trim lot.',
                    ]);
                }

                $trimQty = isset($validated['trim_quantity_output']) && $validated['trim_quantity_output'] !== null && $validated['trim_quantity_output'] !== ''
                    ? round((float) $validated['trim_quantity_output'], 3)
                    : null;

                if (($trimProduct->sell_unit ?? 'piece') === 'kg') {
                    $trimQty = $trimQty && $trimQty > 0 ? $trimQty : $trimWeight;
                } elseif ($trimQty === null || $trimQty <= 0) {
                    throw ValidationException::withMessages([
                        'trim_quantity_output' => 'Trim quantity is required for non-kg trim products.',
                    ]);
                }

                $trimInventoryOutput = [
                    'product' => $trimProduct,
                    'product_variant_id' => null,
                    'output_stage' => 'trim',
                    'output_mode' => 'qty',
                    'produced_quantity' => $trimQty,
                    'produced_weight_kg' => $trimWeight,
                    'piece_count' => null,
                    'pack_size_kg' => null,
                    'piece_weights' => [],
                    'notes' => $validated['trim_notes'] ?? null,
                ];
            }

            $wasteInventoryOutput = null;
            if (!empty($validated['waste_product_id'])) {
                $wasteProduct = Product::findOrFail($validated['waste_product_id']);

                if (($wasteProduct->lot_stage_default ?? null) !== 'waste') {
                    throw ValidationException::withMessages([
                        'waste_product_id' => 'Selected waste product must have lot stage default = waste.',
                    ]);
                }

                if ($wasteWeight <= 0) {
                    throw ValidationException::withMessages([
                        'waste_weight_kg' => 'Waste weight must be greater than zero if you want to create a waste lot.',
                    ]);
                }

                $wasteQty = isset($validated['waste_quantity_output']) && $validated['waste_quantity_output'] !== null && $validated['waste_quantity_output'] !== ''
                    ? round((float) $validated['waste_quantity_output'], 3)
                    : null;

                if (($wasteProduct->sell_unit ?? 'piece') === 'kg') {
                    $wasteQty = $wasteQty && $wasteQty > 0 ? $wasteQty : $wasteWeight;
                } elseif ($wasteQty === null || $wasteQty <= 0) {
                    throw ValidationException::withMessages([
                        'waste_quantity_output' => 'Waste quantity is required for non-kg waste products.',
                    ]);
                }

                $wasteInventoryOutput = [
                    'product' => $wasteProduct,
                    'product_variant_id' => null,
                    'output_stage' => 'waste',
                    'output_mode' => 'qty',
                    'produced_quantity' => $wasteQty,
                    'produced_weight_kg' => $wasteWeight,
                    'piece_count' => null,
                    'pack_size_kg' => null,
                    'piece_weights' => [],
                    'notes' => $validated['waste_notes'] ?? null,
                ];
            }

            $declaredTotal = $saleableOutputWeight + $trimWeight + $wasteWeight;
            if ($declaredTotal > $consumedWeight + 0.050) {
                throw ValidationException::withMessages([
                    'outputs' => 'Output weight + trim + waste cannot exceed consumed input weight.',
                ]);
            }

            $trackedInventoryWeight = $saleableOutputWeight
                + ($trimInventoryOutput ? $trimWeight : 0)
                + ($wasteInventoryOutput ? $wasteWeight : 0);

            $processFlow = match ($runType) {
                'raw_to_slab' => [
                    ['step' => 'slab', 'inventory_output' => true],
                ],
                'slab_to_slice' => [
                    ['step' => 'slice', 'inventory_output' => true],
                ],
                'raw_to_slice_direct' => [
                    ['step' => 'slab', 'inventory_output' => false],
                    ['step' => 'slice', 'inventory_output' => true],
                ],
            };

            $run = ProductionRun::create([
                'run_number' => $this->generateRunNumber(),
                'run_date' => $validated['run_date'],
                'run_type' => $runType,
                'status' => 'completed',
                'process_flow_json' => $processFlow,
                'notes' => $validated['notes'] ?? null,
                'input_weight_kg' => $consumedWeight,
                'saleable_output_weight_kg' => round($saleableOutputWeight, 3),
                'trim_weight_kg' => $trimWeight,
                'waste_weight_kg' => $wasteWeight,
                'yield_percent' => $consumedWeight > 0
                    ? round(($saleableOutputWeight / $consumedWeight) * 100, 2)
                    : 0,
                'created_by_id' => $request->user()?->id,
            ]);

            $inputCostPerKg = null;
            if (!empty($inputLot->cost_per_kg)) {
                $inputCostPerKg = (float) $inputLot->cost_per_kg;
            } elseif (!empty($inputLot->total_cost) && !empty($inputLot->total_weight_kg)) {
                $inputCostPerKg = round((float) $inputLot->total_cost / (float) $inputLot->total_weight_kg, 2);
            }

            $inputTotalCostSnapshot = $inputCostPerKg !== null
                ? round($consumedWeight * $inputCostPerKg, 2)
                : null;

            ProductionRunInput::create([
                'production_run_id' => $run->id,
                'inventory_lot_id' => $inputLot->id,
                'product_id' => $inputLot->product_id,
                'product_variant_id' => $inputLot->product_variant_id,
                'consumed_quantity' => $consumedQty,
                'consumed_weight_kg' => $consumedWeight,
                'consumed_piece_count' => $consumedPieceCount,
                'unit_cost_snapshot' => $inputCostPerKg,
                'total_cost_snapshot' => $inputTotalCostSnapshot,
                'notes' => null,
            ]);

            // Reduce source lot balances
            $inputLot->available_weight_kg = max(round((float) ($inputLot->available_weight_kg ?? 0) - $consumedWeight, 3), 0);

            if ($consumedQty !== null && $inputLot->available_quantity !== null) {
                $inputLot->available_quantity = max(round((float) $inputLot->available_quantity - $consumedQty, 3), 0);
            }

            if ($piecesMode && $consumedPieceCount !== null) {
                $inputLot->available_piece_count = max((int) ($inputLot->available_piece_count ?? 0) - $consumedPieceCount, 0);

                foreach ($selectedPieces as $piece) {
                    $piece->status = 'consumed';
                    $piece->consumed_in_production_run_id = $run->id;
                    $piece->save();
                }
            }

            if ((float) $inputLot->available_weight_kg <= 0.0005) {
                $inputLot->lot_status = 'exhausted';
            }

            $inputLot->updated_by_id = $request->user()?->id;
            $inputLot->save();

            // keep current sales flow working
            $inputProduct = $inputLot->product;
            if ($inputProduct) {
                $stockReduce = $consumedQty ?? $consumedWeight;
                $inputProduct->stock_quantity = max(round((float) ($inputProduct->stock_quantity ?? 0) - $stockReduce, 3), 0);
                $inputProduct->save();
            }

            $rootLotId = $inputLot->root_inventory_lot_id ?: $inputLot->id;

            foreach ($normalizedOutputs as $i => $row) {
                $allocationBase = $trackedInventoryWeight > 0 ? $trackedInventoryWeight : $saleableOutputWeight;
                $allocatedCost = null;

                if ($inputTotalCostSnapshot !== null && $allocationBase > 0) {
                    $allocatedCost = round(($row['produced_weight_kg'] / $allocationBase) * $inputTotalCostSnapshot, 2);
                }

                $this->createOutputLotAndRow(
                    run: $run,
                    inputLot: $inputLot,
                    rootLotId: $rootLotId,
                    product: $row['product'],
                    variantId: $row['product_variant_id'],
                    outputStage: $row['output_stage'],
                    outputMode: $row['output_mode'],
                    producedQty: $row['produced_quantity'],
                    producedWeight: $row['produced_weight_kg'],
                    pieceCount: $row['piece_count'],
                    packSizeKg: $row['pack_size_kg'],
                    pieceWeights: $row['piece_weights'],
                    allocatedCost: $allocatedCost,
                    notes: $row['notes'],
                    userId: $request->user()?->id
                );
            }

            if ($trimInventoryOutput) {
                $allocationBase = $trackedInventoryWeight > 0 ? $trackedInventoryWeight : $saleableOutputWeight;
                $allocatedCost = null;

                if ($inputTotalCostSnapshot !== null && $allocationBase > 0) {
                    $allocatedCost = round(($trimInventoryOutput['produced_weight_kg'] / $allocationBase) * $inputTotalCostSnapshot, 2);
                }

                $this->createOutputLotAndRow(
                    run: $run,
                    inputLot: $inputLot,
                    rootLotId: $rootLotId,
                    product: $trimInventoryOutput['product'],
                    variantId: null,
                    outputStage: 'trim',
                    outputMode: 'qty',
                    producedQty: $trimInventoryOutput['produced_quantity'],
                    producedWeight: $trimInventoryOutput['produced_weight_kg'],
                    pieceCount: null,
                    packSizeKg: null,
                    pieceWeights: [],
                    allocatedCost: $allocatedCost,
                    notes: $trimInventoryOutput['notes'],
                    userId: $request->user()?->id
                );
            }

            if ($wasteInventoryOutput) {
                $allocationBase = $trackedInventoryWeight > 0 ? $trackedInventoryWeight : $saleableOutputWeight;
                $allocatedCost = null;

                if ($inputTotalCostSnapshot !== null && $allocationBase > 0) {
                    $allocatedCost = round(($wasteInventoryOutput['produced_weight_kg'] / $allocationBase) * $inputTotalCostSnapshot, 2);
                }

                $this->createOutputLotAndRow(
                    run: $run,
                    inputLot: $inputLot,
                    rootLotId: $rootLotId,
                    product: $wasteInventoryOutput['product'],
                    variantId: null,
                    outputStage: 'waste',
                    outputMode: 'qty',
                    producedQty: $wasteInventoryOutput['produced_quantity'],
                    producedWeight: $wasteInventoryOutput['produced_weight_kg'],
                    pieceCount: null,
                    packSizeKg: null,
                    pieceWeights: [],
                    allocatedCost: $allocatedCost,
                    notes: $wasteInventoryOutput['notes'],
                    userId: $request->user()?->id
                );
            }
        });

        return redirect()
            ->route('admin.production.show', $run)
            ->with('status', 'Production run completed.');
    }

    public function show(ProductionRun $run)
    {
        $run->load([
            'inputs.inventoryLot.product',
            'inputs.inventoryLot.productVariant',
            'inputs.product',
            'outputs.inventoryLot.pieces',
            'outputs.inventoryLot.product',
            'outputs.inventoryLot.productVariant',
            'outputs.product',
            'outputs.productVariant',
            'outputLots.product',
            'outputLots.productVariant',
            'outputLots.pieces',
        ]);

        return view('admin.production.show', compact('run'));
    }

    protected function createOutputLotAndRow(
        ProductionRun $run,
        InventoryLot $inputLot,
        int $rootLotId,
        Product $product,
        ?int $variantId,
        string $outputStage,
        string $outputMode,
        float $producedQty,
        float $producedWeight,
        ?int $pieceCount,
        ?float $packSizeKg,
        array $pieceWeights,
        ?float $allocatedCost,
        ?string $notes,
        $userId
    ): void {
        if ($variantId) {
            ProductVariant::where('id', $variantId)
                ->where('product_id', $product->id)
                ->firstOrFail();
        }

        $inventoryLot = new InventoryLot();
        $inventoryLot->lot_code = 'PR-' . $run->id . '-OUT-' . str_pad((string) ((int) $run->outputs()->count() + 1), 3, '0', STR_PAD_LEFT);

        $inventoryLot->product_id = $product->id;
        $inventoryLot->product_variant_id = $variantId;

        $inventoryLot->vendor_id = $inputLot->vendor_id;
        $inventoryLot->vendor_invoice_id = null;
        $inventoryLot->vendor_invoice_item_id = null;

        $inventoryLot->production_run_id = $run->id;
        $inventoryLot->parent_inventory_lot_id = $inputLot->id;
        $inventoryLot->root_inventory_lot_id = $rootLotId;

        $inventoryLot->lot_stage = $outputStage;
        $inventoryLot->inward_mode = $outputMode === 'pieces' ? 'pieces' : 'qty';
        $inventoryLot->is_saleable = (bool) ($product->inventory_is_saleable ?? true);
        $inventoryLot->can_repack = (bool) ($product->inventory_can_repack ?? false);
        $inventoryLot->lot_status = 'available';

        $inventoryLot->batch_code = $inputLot->batch_code;
        $inventoryLot->mfg_date = $inputLot->mfg_date;
        $inventoryLot->packed_date = $run->run_date;
        $inventoryLot->expiry_date = $inputLot->expiry_date;
        $inventoryLot->received_date = $run->run_date;

        $inventoryLot->received_quantity = $producedQty;
        $inventoryLot->available_quantity = $producedQty;

        $inventoryLot->unit_weight_kg = $outputMode === 'pieces'
            ? null
            : ($packSizeKg ?: ($producedQty > 0 ? round($producedWeight / $producedQty, 3) : null));

        $inventoryLot->total_weight_kg = $producedWeight;
        $inventoryLot->available_weight_kg = $producedWeight;

        $inventoryLot->piece_count = $pieceCount;
        $inventoryLot->available_piece_count = $pieceCount;
        $inventoryLot->pack_size_kg = $packSizeKg;

        $inventoryLot->unit_cost = $producedQty > 0 && $allocatedCost !== null
            ? round($allocatedCost / $producedQty, 2)
            : null;
        $inventoryLot->cost_per_kg = $producedWeight > 0 && $allocatedCost !== null
            ? round($allocatedCost / $producedWeight, 2)
            : null;
        $inventoryLot->total_cost = $allocatedCost;

        $inventoryLot->notes = $notes;
        $inventoryLot->created_by_id = $userId;
        $inventoryLot->save();

        if ($outputMode === 'pieces' && !empty($pieceWeights)) {
            foreach ($pieceWeights as $idx => $w) {
                InventoryPiece::create([
                    'inventory_lot_id' => $inventoryLot->id,
                    'piece_no' => $idx + 1,
                    'weight_kg' => $w,
                    'status' => 'available',
                    'consumed_in_production_run_id' => null,
                    'sold_order_item_id' => null,
                ]);
            }
        }

        ProductionRunOutput::create([
            'production_run_id' => $run->id,
            'inventory_lot_id' => $inventoryLot->id,
            'product_id' => $product->id,
            'product_variant_id' => $variantId,
            'output_stage' => $outputStage,
            'produced_quantity' => $producedQty,
            'produced_weight_kg' => $producedWeight,
            'piece_count' => $pieceCount,
            'unit_weight_kg' => $inventoryLot->unit_weight_kg,
            'pack_size_kg' => $packSizeKg,
            'is_saleable' => $inventoryLot->is_saleable,
            'can_repack' => $inventoryLot->can_repack,
            'inventory_output' => true,
            'allocated_cost' => $allocatedCost,
            'notes' => $notes,
        ]);

        $product->stock_quantity = round((float) ($product->stock_quantity ?? 0) + $producedQty, 3);
        $product->save();
    }

    protected function generateRunNumber(): string
    {
        return 'PR-' . now()->format('Ymd-His') . '-' . random_int(100, 999);
    }
}