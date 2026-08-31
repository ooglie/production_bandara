<?php $__env->startSection('title', 'New Production Run'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $lotMeta = $inputLots->mapWithKeys(function ($lot) {
        $normalizedInwardMode = strtolower(trim((string) ($lot->inward_mode ?? 'qty')));
        $availablePieces = ($lot->pieces ?? collect())->values();
        $trackedPieceRecordCount = (int) ($lot->tracked_piece_record_count ?? $availablePieces->count());
        $hasAvailableTrackedPieces = $availablePieces->isNotEmpty();
        $canConsumeEntirePieceLot = ! $hasAvailableTrackedPieces
            && $trackedPieceRecordCount === 0
            && in_array($normalizedInwardMode, ['pieces', 'pieces_weight'], true)
            && (int) ($lot->available_piece_count ?? 0) > 0
            && (float) ($lot->available_weight_kg ?? 0) > 0;

        return [$lot->id => [
            'id' => (int) $lot->id,
            'product_id' => (int) $lot->product_id,
            'product_name' => (string) ($lot->product->name ?? '—'),
            'lot_code' => (string) ($lot->lot_code ?: ('LOT-' . $lot->id)),
            'lot_stage' => (string) ($lot->lot_stage ?? 'raw'),
            'inward_mode' => $normalizedInwardMode,
            'available_weight_kg' => (float) ($lot->available_weight_kg ?? 0),
            'available_quantity' => (float) ($lot->available_quantity ?? 0),
            'available_piece_count' => (int) ($lot->available_piece_count ?? 0),
            'tracked_piece_record_count' => $trackedPieceRecordCount,
            'available_tracked_piece_count' => $availablePieces->count(),
            'requires_piece_selection' => $hasAvailableTrackedPieces || $canConsumeEntirePieceLot,
            'piece_selection_mode' => $hasAvailableTrackedPieces ? 'tracked' : ($canConsumeEntirePieceLot ? 'whole_lot' : 'none'),
            'batch_code' => (string) ($lot->batch_code ?? ''),
            'expiry_date' => $lot->expiry_date ? $lot->expiry_date->format('Y-m-d') : '',
            'sell_unit' => (string) ($lot->product->sell_unit ?? 'piece'),
            'pieces' => $availablePieces->map(function ($piece) {
                return [
                    'id' => (int) $piece->id,
                    'piece_no' => (int) $piece->piece_no,
                    'label' => (string) ($piece->label ?: ('Piece ' . $piece->piece_no)),
                    'weight_kg' => (float) ($piece->available_weight_kg ?? $piece->weight_kg ?? 0),
                ];
            })->values()->all(),
        ]];
    })->all();

    $outputProductMeta = $outputProducts->mapWithKeys(function ($p) {
        return [$p->id => [
            'id' => (int) $p->id,
            'name' => (string) $p->name,
            'lot_stage_default' => (string) ($p->lot_stage_default ?? ''),
            'sell_unit' => (string) ($p->sell_unit ?? 'piece'),
            'inventory_is_saleable' => (bool) ($p->inventory_is_saleable ?? true),
            'inventory_can_repack' => (bool) ($p->inventory_can_repack ?? false),
        ]];
    })->all();

    $trimWasteMeta = $trimWasteProducts->mapWithKeys(function ($p) {
        return [$p->id => [
            'id' => (int) $p->id,
            'name' => (string) $p->name,
            'lot_stage_default' => (string) ($p->lot_stage_default ?? ''),
            'sell_unit' => (string) ($p->sell_unit ?? 'piece'),
        ]];
    })->all();

    $oldOutputs = old('outputs', []);
    $oldInputProductId = old('input_product_id');
    $oldInputLotId = old('input_lot_id');
    $oldSelectedPieceIds = collect(old('selected_piece_ids', []))->map(fn($id) => (int) $id)->values()->all();

    $trimProducts = ($trimWasteProducts ?? collect())
        ->where('lot_stage_default', 'trim')
        ->values();

    $wasteProducts = ($trimWasteProducts ?? collect())
        ->where('lot_stage_default', 'waste')
        ->values();

    $showByproductLots = $trimProducts->isNotEmpty() || $wasteProducts->isNotEmpty();
?>

<div class="max-w-6xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">New production run</h1>
            <p class="text-[12px] text-gray-500 dark:text-gray-400">
                Select a product first, then choose a lot, then choose pieces if the lot is piece-based.
            </p>
        </div>

        <a href="<?php echo e(route('admin.production.index')); ?>"
           class="text-[12px] px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
            Back
        </a>
    </div>

    <?php if($errors->any()): ?>
        <div class="rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-[12px] text-red-800">
            <div class="font-medium mb-1">Please fix the following:</div>
            <ul class="list-disc pl-5 space-y-0.5">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(route('admin.production.store')); ?>" class="space-y-4">
        <?php echo csrf_field(); ?>

        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                <div class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Step 1</div>
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Run setup</div>
            </div>

            <div class="p-5 space-y-4">
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Run date</label>
                        <input type="date" name="run_date"
                               value="<?php echo e(old('run_date', now()->format('Y-m-d'))); ?>"
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                               required>
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Run type</label>
                        <select name="run_type" id="run_type"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                                required>
                            <option value="raw_to_slab" <?php if(old('run_type') === 'raw_to_slab'): echo 'selected'; endif; ?>>Raw → Slab</option>
                            <option value="slab_to_slice" <?php if(old('run_type') === 'slab_to_slice'): echo 'selected'; endif; ?>>Slab → Slice</option>
                            <option value="raw_to_slice_direct" <?php if(old('run_type') === 'raw_to_slice_direct'): echo 'selected'; endif; ?>>Raw → Slice Direct</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Input product</label>
                        <select name="input_product_id" id="input_product_id"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                            <option value="">Select product…</option>
                        </select>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Input lot</label>
                        <select name="input_lot_id" id="input_lot_id"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                                required>
                            <option value="">Select lot…</option>
                        </select>
                        <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                            Lots shown here are filtered by product and run type.
                        </div>
                    </div>

                    <div id="input-lot-summary"
                         class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3 text-[12px] text-gray-600 dark:text-gray-300">
                        Select a run type, input product, and input lot to view available balance.
                    </div>
                </div>

                <div id="piece-picker-wrap" class="hidden rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 p-4 space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-[12px] font-medium text-gray-700 dark:text-gray-300">Select pieces from this lot</div>
                            <div class="text-[11px] text-gray-500 dark:text-gray-400">Only selected pieces will be consumed in this run.</div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" id="select-all-pieces"
                                    class="text-[11px] px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-white dark:hover:bg-gray-900">
                                Select all
                            </button>
                            <button type="button" id="clear-all-pieces"
                                    class="text-[11px] px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-white dark:hover:bg-gray-900">
                                Clear
                            </button>
                        </div>
                    </div>

                    <div id="pieces-list">
                        <?php $__currentLoopData = $inputLots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pieceLot): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $availablePieceRows = ($pieceLot->pieces ?? collect())->values();
                                $normalizedPieceLotMode = strtolower(trim((string) ($pieceLot->inward_mode ?? '')));
                                $trackedPieceRecordCount = (int) ($pieceLot->tracked_piece_record_count ?? $availablePieceRows->count());
                                $canConsumeEntirePieceLot = $availablePieceRows->isEmpty()
                                    && $trackedPieceRecordCount === 0
                                    && in_array($normalizedPieceLotMode, ['pieces', 'pieces_weight'], true)
                                    && (int) ($pieceLot->available_piece_count ?? 0) > 0
                                    && (float) ($pieceLot->available_weight_kg ?? 0) > 0;
                                $hasPieceSelection = $availablePieceRows->isNotEmpty() || $canConsumeEntirePieceLot;
                            ?>

                            <?php if($hasPieceSelection): ?>
                                <div class="hidden"
                                     data-piece-lot-group="<?php echo e($pieceLot->id); ?>"
                                     data-piece-selection-mode="<?php echo e($availablePieceRows->isNotEmpty() ? 'tracked' : 'whole_lot'); ?>">
                                    <?php if($availablePieceRows->isNotEmpty()): ?>
                                        <div class="mb-2 text-[11px] text-gray-500 dark:text-gray-400">
                                            <?php echo e($availablePieceRows->count()); ?> available piece(s) loaded from this lot.
                                        </div>
                                        <div class="grid gap-2 md:grid-cols-2">
                                            <?php $__currentLoopData = $availablePieceRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $piece): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php
                                                    $pieceInputId = 'production-piece-' . $piece->id;
                                                    $isOldPiece = in_array((int) $piece->id, $oldSelectedPieceIds, true);
                                                ?>
                                                <label for="<?php echo e($pieceInputId); ?>"
                                                       class="flex min-h-12 cursor-pointer items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-3 py-2.5 transition hover:shadow-sm focus-within:ring-2 focus-within:ring-gray-300 dark:border-gray-800 dark:bg-gray-900 dark:focus-within:ring-gray-700">
                                                    <span class="flex min-w-0 items-center gap-3">
                                                        <input id="<?php echo e($pieceInputId); ?>"
                                                               type="checkbox"
                                                               name="selected_piece_ids[]"
                                                               value="<?php echo e($piece->id); ?>"
                                                               data-piece-selection-control="1"
                                                               data-selection-kind="piece"
                                                               data-piece-checkbox="1"
                                                               data-lot-id="<?php echo e($pieceLot->id); ?>"
                                                               data-weight="<?php echo e((float) ($piece->available_weight_kg ?? $piece->weight_kg ?? 0)); ?>"
                                                               <?php if($isOldPiece): echo 'checked'; endif; ?>
                                                               <?php if((string) $oldInputLotId !== (string) $pieceLot->id): echo 'disabled'; endif; ?>
                                                               class="h-5 w-5 shrink-0 rounded border-gray-300 text-gray-900 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
                                                        <span class="truncate text-[12px] text-gray-800 dark:text-gray-200">
                                                            <?php echo e($piece->label ?: ('Piece ' . $piece->piece_no)); ?>

                                                        </span>
                                                    </span>
                                                    <span class="shrink-0 text-[12px] font-medium text-gray-700 dark:text-gray-300">
                                                        <?php echo e(number_format((float) ($piece->available_weight_kg ?? $piece->weight_kg ?? 0), 3)); ?> kg
                                                    </span>
                                                </label>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                    <?php else: ?>
                                        <?php
                                            $wholeLotInputId = 'production-whole-lot-' . $pieceLot->id;
                                            $isOldWholeLot = (string) $oldInputLotId === (string) $pieceLot->id
                                                && (bool) old('consume_entire_input_lot');
                                        ?>
                                        <div class="mb-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-[11px] text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
                                            This older piece lot has no individual piece records. It can be consumed only as the entire remaining lot.
                                        </div>
                                        <label for="<?php echo e($wholeLotInputId); ?>"
                                               class="flex min-h-12 cursor-pointer items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-3 py-2.5 transition hover:shadow-sm focus-within:ring-2 focus-within:ring-gray-300 dark:border-gray-800 dark:bg-gray-900 dark:focus-within:ring-gray-700">
                                            <span class="flex min-w-0 items-center gap-3">
                                                <input id="<?php echo e($wholeLotInputId); ?>"
                                                       type="checkbox"
                                                       name="consume_entire_input_lot"
                                                       value="1"
                                                       data-piece-selection-control="1"
                                                       data-selection-kind="whole-lot"
                                                       data-lot-id="<?php echo e($pieceLot->id); ?>"
                                                       data-weight="<?php echo e((float) ($pieceLot->available_weight_kg ?? 0)); ?>"
                                                       data-quantity="<?php echo e((float) ($pieceLot->available_quantity ?? 0)); ?>"
                                                       data-piece-count="<?php echo e((int) ($pieceLot->available_piece_count ?? 0)); ?>"
                                                       <?php if($isOldWholeLot): echo 'checked'; endif; ?>
                                                       <?php if((string) $oldInputLotId !== (string) $pieceLot->id): echo 'disabled'; endif; ?>
                                                       class="h-5 w-5 shrink-0 rounded border-gray-300 text-gray-900 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
                                                <span class="truncate text-[12px] text-gray-800 dark:text-gray-200">
                                                    Use the entire remaining lot
                                                </span>
                                            </span>
                                            <span class="shrink-0 text-right text-[12px] font-medium text-gray-700 dark:text-gray-300">
                                                <?php echo e((int) ($pieceLot->available_piece_count ?? 0)); ?> piece(s) · <?php echo e(number_format((float) ($pieceLot->available_weight_kg ?? 0), 3)); ?> kg
                                            </span>
                                        </label>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>

                    <noscript>
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-[11px] text-amber-800">
                            JavaScript is required to filter the available pieces by the selected lot.
                        </div>
                    </noscript>

                    <div id="pieces-summary"
                         class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-2 text-[12px] text-gray-700 dark:text-gray-200">
                        No pieces selected.
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-4">
                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Consumed weight (kg)</label>
                        <input type="number" step="0.001" min="0.001" name="consumed_weight_kg" id="consumed_weight_kg"
                               value="<?php echo e(old('consumed_weight_kg')); ?>"
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Consumed quantity</label>
                        <input type="number" step="0.001" min="0" name="consumed_quantity" id="consumed_quantity"
                               value="<?php echo e(old('consumed_quantity')); ?>"
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                        <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                            Auto-calculated for piece-based lots.
                        </div>
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Trim weight (kg)</label>
                        <input type="number" step="0.001" min="0" name="trim_weight_kg"
                            value="<?php echo e(old('trim_weight_kg', 0)); ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">

                        <?php if($trimProducts->isNotEmpty()): ?>
                            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                You can keep this as summary only, or choose a trim product below to create a trim lot.
                            </div>
                        <?php else: ?>
                            <div class="mt-1 text-[11px] text-amber-600 dark:text-amber-400">
                                No trim product configured yet. This will be recorded as summary only.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Waste weight (kg)</label>
                        <input type="number" step="0.001" min="0" name="waste_weight_kg"
                            value="<?php echo e(old('waste_weight_kg', 0)); ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">

                        <?php if($wasteProducts->isNotEmpty()): ?>
                            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                You can keep this as summary only, or choose a waste product below to create a waste lot.
                            </div>
                        <?php else: ?>
                            <div class="mt-1 text-[11px] text-amber-600 dark:text-amber-400">
                                No waste product configured yet. This will be recorded as summary only.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                
                                <?php if($showByproductLots): ?>
                    
                    <div class="grid gap-4 <?php echo e($trimProducts->isNotEmpty() && $wasteProducts->isNotEmpty() ? 'lg:grid-cols-2' : 'lg:grid-cols-1'); ?>">
                        <?php if($trimProducts->isNotEmpty()): ?>
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 p-4 space-y-3">
                                <div>
                                    <div class="text-[12px] font-medium text-gray-700 dark:text-gray-300">Optional trim lot</div>
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                        If left blank, trim remains summary only and no trim inventory lot is created.
                                    </div>
                                </div>

                                <div class="grid gap-3 md:grid-cols-2">
                                    <div class="md:col-span-2">
                                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Trim product</label>
                                        <select name="trim_product_id"
                                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                                            <option value="">— None —</option>
                                            <?php $__currentLoopData = $trimProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($product->id); ?>" <?php if((int) old('trim_product_id', 0) === (int) $product->id): echo 'selected'; endif; ?>>
                                                    <?php echo e($product->name); ?>

                                                </option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Trim quantity</label>
                                        <input type="number" step="0.001" min="0"
                                               name="trim_quantity_output"
                                               value="<?php echo e(old('trim_quantity_output')); ?>"
                                               class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                                    </div>

                                    <div>
                                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Trim notes</label>
                                        <input type="text"
                                               name="trim_notes"
                                               value="<?php echo e(old('trim_notes')); ?>"
                                               class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if($wasteProducts->isNotEmpty()): ?>
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 p-4 space-y-3">
                                <div>
                                    <div class="text-[12px] font-medium text-gray-700 dark:text-gray-300">Optional waste lot</div>
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                        If left blank, waste remains summary only and no waste inventory lot is created.
                                    </div>
                                </div>

                                <div class="grid gap-3 md:grid-cols-2">
                                    <div class="md:col-span-2">
                                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Waste product</label>
                                        <select name="waste_product_id"
                                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                                            <option value="">— None —</option>
                                            <?php $__currentLoopData = $wasteProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($product->id); ?>" <?php if((int) old('waste_product_id', 0) === (int) $product->id): echo 'selected'; endif; ?>>
                                                    <?php echo e($product->name); ?>

                                                </option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Waste quantity</label>
                                        <input type="number" step="0.001" min="0"
                                               name="waste_quantity_output"
                                               value="<?php echo e(old('waste_quantity_output')); ?>"
                                               class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                                    </div>

                                    <div>
                                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Waste notes</label>
                                        <input type="text"
                                               name="waste_notes"
                                               value="<?php echo e(old('waste_notes')); ?>"
                                               class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                    <textarea name="notes" rows="3"
                              class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"><?php echo e(old('notes')); ?></textarea>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Step 2</div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Output lots</div>
                </div>

                <button type="button" id="add-output-row"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-[12px] hover:bg-gray-50 dark:hover:bg-gray-800">
                    <span class="text-lg leading-none">+</span> Add output
                </button>
            </div>

            <div class="p-5 space-y-4">
                <div id="outputs-container" class="space-y-4"></div>

                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3 text-[11px] text-gray-500 dark:text-gray-400">
                    <strong>Tip:</strong> Use <strong>Individual weights</strong> when slabs are variable-sized.
                    Quantity and total weight will be auto-calculated from the entered lines.
                </div>
            </div>
        </section>

        <div class="flex items-center justify-between pt-2">
            <a href="<?php echo e(route('admin.production.index')); ?>"
               class="text-[12px] text-gray-500 dark:text-gray-400 hover:underline">
                Cancel
            </a>

            <button type="submit"
                    class="inline-flex items-center rounded-xl border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-6 py-2 text-[13px] font-semibold hover:bg-gray-800 dark:hover:bg-gray-200">
                Complete production run
            </button>
        </div>
    </form>
</div>

<template id="output-row-template">
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950/20 p-4 space-y-4" data-output-row="1">
        <div class="flex items-center justify-between">
            <div class="text-[13px] font-semibold text-gray-900 dark:text-gray-50">Output row</div>
            <button type="button"
                    class="remove-output-row text-[12px] px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                Remove
            </button>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="md:col-span-2">
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Output product</label>
                <select class="output-product w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                        name="outputs[__INDEX__][product_id]" required>
                    <option value="">Select output product…</option>
                </select>
                <div class="output-product-hint mt-1 text-[11px] text-gray-500 dark:text-gray-400"></div>
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Variant (optional)</label>
                <input type="number"
                       class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                       name="outputs[__INDEX__][product_variant_id]"
                       placeholder="Variant ID">
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Output mode</label>
                <select name="outputs[__INDEX__][output_mode]"
                        class="output-mode w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                    <option value="qty">Standard qty</option>
                    <option value="pieces">Individual weights</option>
                </select>
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Produced quantity</label>
                <input type="number" step="0.001" min="0.001"
                       name="outputs[__INDEX__][produced_quantity]"
                       class="produced-quantity w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                       required>
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Produced weight (kg)</label>
                <input type="number" step="0.001" min="0.001"
                       name="outputs[__INDEX__][produced_weight_kg]"
                       class="produced-weight w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                       required>
            </div>

            <div class="pack-size-wrap">
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Pack size (kg, optional)</label>
                <input type="number" step="0.001" min="0"
                       name="outputs[__INDEX__][pack_size_kg]"
                       class="pack-size w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
            </div>
        </div>

        <div class="piece-count-wrap hidden">
            <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Piece count</label>
            <input type="number" min="0"
                   name="outputs[__INDEX__][piece_count]"
                   class="piece-count w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                   readonly>
        </div>

        <div class="piece-weights-wrap hidden">
            <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                Individual weights (kg) — one per line
            </label>
            <textarea name="outputs[__INDEX__][piece_weights]"
                      rows="4"
                      class="piece-weights w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                      placeholder="4.250&#10;4.700&#10;5.000&#10;5.000"></textarea>
            <div class="piece-weights-summary mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                Enter one slab / piece weight per line.
            </div>
        </div>

        <div>
            <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Notes (optional)</label>
            <input type="text"
                   name="outputs[__INDEX__][notes]"
                   class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
        </div>
    </div>
</template>

<script>
(function () {
    const lotMeta = <?php echo json_encode($lotMeta, 15, 512) ?>;
    const outputProductMeta = <?php echo json_encode($outputProductMeta, 15, 512) ?>;
    const oldOutputs = <?php echo json_encode($oldOutputs, 15, 512) ?>;
    const oldInputProductId = <?php echo json_encode($oldInputProductId, 15, 512) ?>;
    const oldInputLotId = <?php echo json_encode($oldInputLotId, 15, 512) ?>;
    const oldSelectedPieceIds = <?php echo json_encode($oldSelectedPieceIds, 15, 512) ?>;

    const runTypeEl = document.getElementById('run_type');
    const inputProductEl = document.getElementById('input_product_id');
    const inputLotEl = document.getElementById('input_lot_id');
    const inputLotSummary = document.getElementById('input-lot-summary');
    const pieceWrap = document.getElementById('piece-picker-wrap');
    const pieceList = document.getElementById('pieces-list');
    const pieceGroups = Array.from(pieceList.querySelectorAll('[data-piece-lot-group]'));
    const pieceSummary = document.getElementById('pieces-summary');
    const selectAllPiecesBtn = document.getElementById('select-all-pieces');
    const clearAllPiecesBtn = document.getElementById('clear-all-pieces');
    const consumedWeightEl = document.getElementById('consumed_weight_kg');
    const consumedQuantityEl = document.getElementById('consumed_quantity');
    const outputsContainer = document.getElementById('outputs-container');
    const addOutputBtn = document.getElementById('add-output-row');
    const tpl = document.getElementById('output-row-template');

    let outputIndex = 0;

    function expectedInputStage() {
        return runTypeEl.value === 'slab_to_slice' ? 'slab' : 'raw';
    }

    function expectedOutputStage() {
        return runTypeEl.value === 'raw_to_slab' ? 'slab' : 'slice';
    }

    function groupProductsForStage(stage) {
        const seen = new Map();

        Object.values(lotMeta).forEach(function (lot) {
            if (lot.lot_stage !== stage) return;

            if (!seen.has(lot.product_id)) {
                seen.set(lot.product_id, {
                    id: lot.product_id,
                    name: lot.product_name,
                });
            }
        });

        return Array.from(seen.values()).sort((a, b) => a.name.localeCompare(b.name));
    }

    function lotsForSelection(productId, stage) {
        return Object.values(lotMeta)
            .filter(function (lot) {
                return String(lot.product_id) === String(productId) && lot.lot_stage === stage;
            })
            .sort(function (a, b) {
                return String(a.lot_code).localeCompare(String(b.lot_code));
            });
    }

    function buildProductOptions(selected = null) {
        const stage = expectedInputStage();
        const products = groupProductsForStage(stage);

        inputProductEl.innerHTML = '<option value="">Select product…</option>';

        products.forEach(function (product) {
            const opt = document.createElement('option');
            opt.value = product.id;
            opt.textContent = product.name;
            if (String(selected) === String(product.id)) {
                opt.selected = true;
            }
            inputProductEl.appendChild(opt);
        });

        if (selected && !products.some(p => String(p.id) === String(selected))) {
            inputProductEl.value = '';
        }
    }

    function formatLotLabel(lot) {
        const piecesText = lot.available_piece_count > 0
            ? (lot.available_piece_count + ' pcs · ')
            : '';

        const batchText = lot.batch_code ? (' · Batch ' + lot.batch_code) : '';
        const expiryText = lot.expiry_date ? (' · Exp ' + lot.expiry_date) : '';

        return lot.lot_code + ' · ' + piecesText + Number(lot.available_weight_kg || 0).toFixed(3) + ' kg' + batchText + expiryText;
    }

    function buildLotOptions(selected = null) {
        const stage = expectedInputStage();
        const productId = inputProductEl.value;

        inputLotEl.innerHTML = '<option value="">Select lot…</option>';

        if (!productId) return;

        const lots = lotsForSelection(productId, stage);

        lots.forEach(function (lot) {
            const opt = document.createElement('option');
            opt.value = lot.id;
            opt.textContent = formatLotLabel(lot);

            if (String(selected) === String(lot.id)) {
                opt.selected = true;
            }

            inputLotEl.appendChild(opt);
        });

        if (selected && !lots.some(l => String(l.id) === String(selected))) {
            inputLotEl.value = '';
        }
    }

    function refreshInputLotSummary() {
        const lot = lotMeta[inputLotEl.value];

        if (!lot) {
            inputLotSummary.textContent = 'Select a run type, input product, and input lot to view available balance.';
            return;
        }

        inputLotSummary.innerHTML =
            '<div class="font-medium text-gray-900 dark:text-gray-50">' + lot.product_name + '</div>' +
            '<div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">' +
                'Lot: ' + lot.lot_code +
                ' · Stage: ' + lot.lot_stage.toUpperCase() +
                ' · Mode: ' + lot.inward_mode.toUpperCase() +
                ' · Available weight: ' + Number(lot.available_weight_kg || 0).toFixed(3) + ' kg' +
                ' · Available quantity: ' + Number(lot.available_quantity || 0).toFixed(3) +
                (lot.available_piece_count ? ' · Pieces: ' + lot.available_piece_count : '') +
                (lot.batch_code ? ' · Batch: ' + lot.batch_code : '') +
                (lot.expiry_date ? ' · Expiry: ' + lot.expiry_date : '') +
            '</div>';
    }

    function setConsumedFieldsReadonly(readonly) {
        consumedWeightEl.readOnly = readonly;
        consumedQuantityEl.readOnly = readonly;

        if (readonly) {
            consumedWeightEl.classList.add('bg-gray-100', 'dark:bg-gray-900/40');
            consumedQuantityEl.classList.add('bg-gray-100', 'dark:bg-gray-900/40');
        } else {
            consumedWeightEl.classList.remove('bg-gray-100', 'dark:bg-gray-900/40');
            consumedQuantityEl.classList.remove('bg-gray-100', 'dark:bg-gray-900/40');
        }
    }

    function activePieceSelectionControls() {
        return Array.from(pieceList.querySelectorAll('input[data-piece-selection-control="1"]:not(:disabled)'));
    }

    function refreshPieceSummary() {
        const lot = lotMeta[inputLotEl.value];
        const selectedControls = activePieceSelectionControls().filter(function (control) {
            return control.checked;
        });

        if (!lot || selectedControls.length === 0) {
            pieceSummary.textContent = 'No pieces selected.';
            consumedWeightEl.value = '';
            consumedQuantityEl.value = '';
            return;
        }

        const wholeLotControl = selectedControls.find(function (control) {
            return control.dataset.selectionKind === 'whole-lot';
        });

        if (wholeLotControl) {
            const totalWeight = Number(wholeLotControl.dataset.weight || 0);
            const pieceCount = Number(wholeLotControl.dataset.pieceCount || 0);
            const availableQuantity = Number(wholeLotControl.dataset.quantity || 0);

            pieceSummary.textContent = 'Entire lot selected · '
                + pieceCount + ' piece(s) · '
                + totalWeight.toFixed(3) + ' kg';
            consumedWeightEl.value = totalWeight.toFixed(3);
            consumedQuantityEl.value = lot.lot_stage === 'raw'
                ? totalWeight.toFixed(3)
                : String(availableQuantity > 0 ? availableQuantity : pieceCount);
            return;
        }

        let totalWeight = 0;
        selectedControls.forEach(function (control) {
            totalWeight += Number(control.dataset.weight || 0);
        });

        const pieceCount = selectedControls.length;

        pieceSummary.textContent = pieceCount + ' piece(s) selected · ' + totalWeight.toFixed(3) + ' kg';
        consumedWeightEl.value = totalWeight.toFixed(3);
        consumedQuantityEl.value = lot.lot_stage === 'raw'
            ? totalWeight.toFixed(3)
            : String(pieceCount);
    }

    function renderPiecePicker() {
        const lot = lotMeta[inputLotEl.value];
        const previousActiveLotId = pieceWrap.dataset.activeLotId || '';
        let activeGroup = null;

        pieceGroups.forEach(function (group) {
            const isActive = !!lot
                && Boolean(lot.requires_piece_selection)
                && String(group.dataset.pieceLotGroup) === String(lot.id);

            if (isActive) {
                group.classList.remove('hidden');
            } else {
                group.classList.add('hidden');
            }

            group.querySelectorAll('input[data-piece-selection-control="1"]').forEach(function (control) {
                control.disabled = !isActive;
                if (!isActive) {
                    control.checked = false;
                }
            });

            if (isActive) {
                activeGroup = group;
            }
        });

        if (!lot || !Boolean(lot.requires_piece_selection) || !activeGroup) {
            pieceWrap.classList.add('hidden');
            pieceWrap.dataset.activeLotId = '';
            setConsumedFieldsReadonly(false);
            pieceSummary.textContent = 'No pieces selected.';

            if (previousActiveLotId !== '') {
                consumedWeightEl.value = '';
                consumedQuantityEl.value = '';
            }
            return;
        }

        pieceWrap.classList.remove('hidden');
        pieceWrap.dataset.activeLotId = String(lot.id);
        setConsumedFieldsReadonly(true);
        refreshPieceSummary();
    }

    function buildOutputProductOptions(selectEl, selected = null) {
        const expectedStage = expectedOutputStage();

        selectEl.innerHTML = '<option value="">Select output product…</option>';

        Object.values(outputProductMeta)
            .filter(meta => meta.lot_stage_default === expectedStage)
            .sort((a, b) => a.name.localeCompare(b.name))
            .forEach(function (meta) {
                const opt = document.createElement('option');
                opt.value = meta.id;
                opt.textContent = meta.name;

                if (String(selected) === String(meta.id)) {
                    opt.selected = true;
                }

                selectEl.appendChild(opt);
            });
    }

    function updateOutputModeUI(rowEl) {
        const modeEl = rowEl.querySelector('.output-mode');
        const qtyEl = rowEl.querySelector('.produced-quantity');
        const weightEl = rowEl.querySelector('.produced-weight');
        const pieceCountEl = rowEl.querySelector('.piece-count');
        const pieceWeightsEl = rowEl.querySelector('.piece-weights');
        const pieceSummaryEl = rowEl.querySelector('.piece-weights-summary');
        const pieceWrapEl = rowEl.querySelector('.piece-weights-wrap');
        const pieceCountWrapEl = rowEl.querySelector('.piece-count-wrap');
        const packWrapEl = rowEl.querySelector('.pack-size-wrap');

        const mode = modeEl.value || 'qty';

        if (mode === 'pieces') {
            pieceWrapEl.classList.remove('hidden');
            pieceCountWrapEl.classList.remove('hidden');
            packWrapEl.classList.add('hidden');

            qtyEl.readOnly = true;
            weightEl.readOnly = true;
            qtyEl.classList.add('bg-gray-100', 'dark:bg-gray-900/40');
            weightEl.classList.add('bg-gray-100', 'dark:bg-gray-900/40');
        } else {
            pieceWrapEl.classList.add('hidden');
            pieceCountWrapEl.classList.add('hidden');
            packWrapEl.classList.remove('hidden');

            qtyEl.readOnly = false;
            weightEl.readOnly = false;
            qtyEl.classList.remove('bg-gray-100', 'dark:bg-gray-900/40');
            weightEl.classList.remove('bg-gray-100', 'dark:bg-gray-900/40');

            pieceCountEl.value = '';
            pieceWeightsEl.value = '';
            pieceSummaryEl.textContent = 'Enter one slab / piece weight per line.';
        }
    }

    function recalcOutputPieces(rowEl) {
        const modeEl = rowEl.querySelector('.output-mode');
        if (!modeEl || modeEl.value !== 'pieces') return;

        const qtyEl = rowEl.querySelector('.produced-quantity');
        const weightEl = rowEl.querySelector('.produced-weight');
        const pieceCountEl = rowEl.querySelector('.piece-count');
        const pieceWeightsEl = rowEl.querySelector('.piece-weights');
        const pieceSummaryEl = rowEl.querySelector('.piece-weights-summary');

        const lines = String(pieceWeightsEl.value || '')
            .split(/\r\n|\n|\r/)
            .map(v => v.trim())
            .filter(Boolean);

        const weights = [];
        for (const ln of lines) {
            const n = parseFloat(ln);
            if (isFinite(n) && n > 0) {
                weights.push(n);
            }
        }

        const total = weights.reduce((a, b) => a + b, 0);
        qtyEl.value = weights.length ? String(weights.length) : '';
        weightEl.value = weights.length ? total.toFixed(3) : '';
        pieceCountEl.value = weights.length ? String(weights.length) : '';

        pieceSummaryEl.textContent = weights.length
            ? weights.length + ' piece(s) · ' + total.toFixed(3) + ' kg'
            : 'Enter one slab / piece weight per line.';
    }

    function bindOutputRow(rowEl) {
        const removeBtn = rowEl.querySelector('.remove-output-row');
        const productSel = rowEl.querySelector('.output-product');
        const hintEl = rowEl.querySelector('.output-product-hint');
        const modeEl = rowEl.querySelector('.output-mode');
        const pieceWeightsEl = rowEl.querySelector('.piece-weights');

        removeBtn?.addEventListener('click', function () {
            rowEl.remove();
            if (outputsContainer.children.length === 0) addOutputRow();
        });

        productSel?.addEventListener('change', function () {
            const meta = outputProductMeta[this.value] || null;

            if (!meta) {
                hintEl.textContent = '';
                return;
            }

            hintEl.textContent =
                'Stage: ' + (meta.lot_stage_default || '—') +
                ' · Saleable: ' + (meta.inventory_is_saleable ? 'Yes' : 'No') +
                ' · Repackable: ' + (meta.inventory_can_repack ? 'Yes' : 'No');
        });

        modeEl?.addEventListener('change', function () {
            updateOutputModeUI(rowEl);
            recalcOutputPieces(rowEl);
        });

        pieceWeightsEl?.addEventListener('input', function () {
            recalcOutputPieces(rowEl);
        });

        updateOutputModeUI(rowEl);
        recalcOutputPieces(rowEl);
    }

    function refreshSelectors() {
        const currentProduct = inputProductEl.value || oldInputProductId || '';
        const currentLot = inputLotEl.value || oldInputLotId || '';

        buildProductOptions(currentProduct);
        buildLotOptions(currentLot);
        refreshInputLotSummary();
        renderPiecePicker();
        refreshOutputSelects();
    }

    function buildLotOptions(selected = null) {
        const stage = expectedInputStage();
        const productId = inputProductEl.value;

        inputLotEl.innerHTML = '<option value="">Select lot…</option>';

        if (!productId) return;

        const lots = Object.values(lotMeta)
            .filter(function (lot) {
                return String(lot.product_id) === String(productId) && lot.lot_stage === stage;
            })
            .sort(function (a, b) {
                return String(a.lot_code).localeCompare(String(b.lot_code));
            });

        lots.forEach(function (lot) {
            const opt = document.createElement('option');
            opt.value = lot.id;
            opt.textContent = lot.lot_code + ' · ' +
                (lot.available_piece_count > 0 ? (lot.available_piece_count + ' pcs · ') : '') +
                Number(lot.available_weight_kg || 0).toFixed(3) + ' kg' +
                (lot.batch_code ? (' · Batch ' + lot.batch_code) : '') +
                (lot.expiry_date ? (' · Exp ' + lot.expiry_date) : '');

            if (String(selected) === String(lot.id)) {
                opt.selected = true;
            }

            inputLotEl.appendChild(opt);
        });

        if (selected && !lots.some(l => String(l.id) === String(selected))) {
            inputLotEl.value = '';
        }
    }

    function buildProductOptions(selected = null) {
        const stage = expectedInputStage();
        const seen = new Map();

        Object.values(lotMeta).forEach(function (lot) {
            if (lot.lot_stage !== stage) return;

            if (!seen.has(lot.product_id)) {
                seen.set(lot.product_id, {
                    id: lot.product_id,
                    name: lot.product_name,
                });
            }
        });

        const products = Array.from(seen.values()).sort((a, b) => a.name.localeCompare(b.name));

        inputProductEl.innerHTML = '<option value="">Select product…</option>';

        products.forEach(function (product) {
            const opt = document.createElement('option');
            opt.value = product.id;
            opt.textContent = product.name;

            if (String(selected) === String(product.id)) {
                opt.selected = true;
            }

            inputProductEl.appendChild(opt);
        });

        if (selected && !products.some(p => String(p.id) === String(selected))) {
            inputProductEl.value = '';
        }
    }

    function refreshOutputSelects() {
        outputsContainer.querySelectorAll('[data-output-row="1"]').forEach(function (rowEl) {
            const selectEl = rowEl.querySelector('.output-product');
            const selected = selectEl.dataset.selected || selectEl.value || '';
            buildOutputProductOptions(selectEl, selected);
            selectEl.dispatchEvent(new Event('change'));
        });
    }

    function addOutputRow(prefill = null) {
        const html = tpl.innerHTML.replaceAll('__INDEX__', String(outputIndex));
        const wrap = document.createElement('div');
        wrap.innerHTML = html.trim();
        const rowEl = wrap.firstElementChild;

        outputsContainer.appendChild(rowEl);

        const productSel = rowEl.querySelector('.output-product');
        const modeEl = rowEl.querySelector('.output-mode');

        if (prefill) {
            productSel.dataset.selected = prefill.product_id ?? '';
            modeEl.value = prefill.output_mode ?? 'qty';

            rowEl.querySelector('[name="outputs[' + outputIndex + '][product_variant_id]"]').value = prefill.product_variant_id ?? '';
            rowEl.querySelector('[name="outputs[' + outputIndex + '][produced_quantity]"]').value = prefill.produced_quantity ?? '';
            rowEl.querySelector('[name="outputs[' + outputIndex + '][produced_weight_kg]"]').value = prefill.produced_weight_kg ?? '';
            rowEl.querySelector('[name="outputs[' + outputIndex + '][piece_count]"]').value = prefill.piece_count ?? '';
            rowEl.querySelector('[name="outputs[' + outputIndex + '][pack_size_kg]"]').value = prefill.pack_size_kg ?? '';
            rowEl.querySelector('[name="outputs[' + outputIndex + '][piece_weights]"]').value = prefill.piece_weights ?? '';
            rowEl.querySelector('[name="outputs[' + outputIndex + '][notes]"]').value = prefill.notes ?? '';
        }

        bindOutputRow(rowEl);
        refreshOutputSelects();

        outputIndex++;
        return rowEl;
    }

    function handlePieceSelectionEvent(event) {
        const target = event.target;
        if (target && typeof target.matches === 'function' && target.matches('input[data-piece-selection-control="1"]')) {
            refreshPieceSummary();
        }
    }

    pieceList.addEventListener('change', handlePieceSelectionEvent);
    pieceList.addEventListener('input', handlePieceSelectionEvent);

    runTypeEl.addEventListener('change', function () {
        inputProductEl.value = '';
        inputLotEl.value = '';
        refreshSelectors();
    });

    inputProductEl.addEventListener('change', function () {
        buildLotOptions();
        refreshInputLotSummary();
        renderPiecePicker();
    });

    function handleInputLotSelection() {
        refreshInputLotSummary();
        renderPiecePicker();
    }

    inputLotEl.addEventListener('change', handleInputLotSelection);
    inputLotEl.addEventListener('input', handleInputLotSelection);

    selectAllPiecesBtn?.addEventListener('click', function () {
        activePieceSelectionControls().forEach(function (control) {
            control.checked = true;
        });
        refreshPieceSummary();
    });

    clearAllPiecesBtn?.addEventListener('click', function () {
        activePieceSelectionControls().forEach(function (control) {
            control.checked = false;
        });
        refreshPieceSummary();
    });

    addOutputBtn?.addEventListener('click', function () {
        addOutputRow();
    });

    refreshSelectors();

    window.addEventListener('pageshow', function () {
        handleInputLotSelection();
    });

    if (oldOutputs.length > 0) {
        oldOutputs.forEach(function (row) {
            addOutputRow(row);
        });
    } else {
        addOutputRow();
    }
})();
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/production/create.blade.php ENDPATH**/ ?>