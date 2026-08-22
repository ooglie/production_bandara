<?php $__env->startSection('title', 'Production Run ' . ($run->run_number ?? '')); ?>

<?php $__env->startSection('content'); ?>
<?php
    $inputs = $run->inputs ?? collect();
    $outputs = $run->outputs ?? collect();

    $runTypeLabel = match ($run->run_type) {
        'raw_to_slab' => 'Raw → Slab',
        'slab_to_slice' => 'Slab → Slice',
        'raw_to_slice_direct' => 'Raw → Slice Direct',
        default => str_replace('_', ' ', ucfirst($run->run_type ?? '')),
    };

    $status = $run->status ?? 'draft';

    $statusClass = match ($status) {
        'completed' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200',
        'reversed' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200',
        'cancelled' => 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200',
        default => 'border-gray-200 bg-gray-50 text-gray-600 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-300',
    };

    $flowSteps = collect($run->process_flow_json ?? []);

    $fmtQty = fn ($v) => number_format((float) ($v ?? 0), 3);
    $fmtW   = fn ($v) => number_format((float) ($v ?? 0), 3) . ' kg';
    $fmtM   = fn ($v) => '₹' . number_format((float) ($v ?? 0), 2);
    $canManageReversal = auth()->user()?->hasAnyRole(['Admin', 'Manager']) ?? false;
    $reversal = $reversalAssessment ?? ['can_reverse' => false, 'blockers' => [], 'sources' => [], 'outputs' => []];
?>

<div class="max-w-7xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">
                Production Run <?php echo e($run->run_number ?? ('#' . $run->id)); ?>

            </h1>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] <?php echo e($statusClass); ?>">
                    <?php echo e(ucfirst($status)); ?>

                </span>
                <span class="inline-flex items-center rounded-full border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950/40 px-2.5 py-1 text-[11px] text-gray-700 dark:text-gray-200">
                    <?php echo e($runTypeLabel); ?>

                </span>
                <?php if($run->run_date): ?>
                    <span class="text-[11px] text-gray-500 dark:text-gray-400">
                        <?php echo e($run->run_date->format('d M Y')); ?>

                    </span>
                <?php endif; ?>
            </div>
        </div>

        <a href="<?php echo e(route('admin.production.index')); ?>"
           class="text-[12px] px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
            Back
        </a>
    </div>

    <?php if(session('status')): ?>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-[12px] text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/25 dark:text-emerald-200">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <?php if($errors->any()): ?>
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-[12px] text-red-800 dark:border-red-800 dark:bg-red-900/25 dark:text-red-200">
            <div class="font-medium">The production run was not reversed.</div>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Input weight</div>
            <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-50">
                <?php echo e($fmtW($run->input_weight_kg)); ?>

            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Saleable output</div>
            <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-50">
                <?php echo e($fmtW($run->saleable_output_weight_kg)); ?>

            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Trim</div>
            <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-50">
                <?php echo e($fmtW($run->trim_weight_kg)); ?>

            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Waste</div>
            <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-50">
                <?php echo e($fmtW($run->waste_weight_kg)); ?>

            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Yield</div>
            <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-50">
                <?php echo e(number_format((float) ($run->yield_percent ?? 0), 2)); ?>%
            </div>
        </div>
    </div>

    
    <?php if($flowSteps->isNotEmpty()): ?>
        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                <div class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Process flow</div>
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Steps used in this run</div>
            </div>

            <div class="p-5">
                <div class="flex flex-wrap gap-2">
                    <?php $__currentLoopData = $flowSteps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $label = ucfirst((string) data_get($step, 'step', 'step'));
                            $invOut = (bool) data_get($step, 'inventory_output', false);
                        ?>
                        <span class="inline-flex items-center rounded-full border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950/40 px-3 py-1.5 text-[11px] text-gray-700 dark:text-gray-200">
                            <?php echo e($label); ?>

                            <span class="ml-2 text-gray-400">
                                <?php echo e($invOut ? 'stocked' : 'virtual'); ?>

                            </span>
                        </span>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if(!empty($run->notes)): ?>
        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Notes</div>
            <div class="mt-2 text-[12px] text-gray-700 dark:text-gray-200 whitespace-pre-line">
                <?php echo e($run->notes); ?>

            </div>
        </section>
    <?php endif; ?>

    
    <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Input</div>
            <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Consumed lot</div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-[12px]">
                <thead class="bg-gray-50 dark:bg-gray-950/40">
                    <tr class="text-left text-gray-500 dark:text-gray-400">
                        <th class="px-4 py-3 font-medium">Lot</th>
                        <th class="px-4 py-3 font-medium">Product</th>
                        <th class="px-4 py-3 font-medium">Mode</th>
                        <th class="px-4 py-3 font-medium">Consumed qty</th>
                        <th class="px-4 py-3 font-medium">Consumed wt.</th>
                        <th class="px-4 py-3 font-medium">Pieces</th>
                        <th class="px-4 py-3 font-medium text-right">Cost snapshot</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <?php $__empty_1 = true; $__currentLoopData = $inputs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $input): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $lot = $input->inventoryLot;
                            $lotMode = $lot?->inward_mode ? ucfirst($lot->inward_mode) : 'Qty';
                        ?>
                        <tr>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                <div class="font-medium text-gray-900 dark:text-gray-50">
                                    <?php echo e($lot?->lot_code ?? ('LOT-' . ($input->inventory_lot_id ?? '—'))); ?>

                                </div>
                                <?php if($lot?->batch_code): ?>
                                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                        Batch: <?php echo e($lot->batch_code); ?>

                                    </div>
                                <?php endif; ?>
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                <?php echo e($input->product->name ?? '—'); ?>

                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                <?php echo e($lotMode); ?>

                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                <?php echo e($fmtQty($input->consumed_quantity)); ?>

                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                <?php echo e($fmtW($input->consumed_weight_kg)); ?>

                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                <?php if(!empty($input->consumed_piece_count)): ?>
                                    <?php echo e((int) $input->consumed_piece_count); ?>

                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>

                            <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-50">
                                <?php echo e($fmtM($input->total_cost_snapshot)); ?>

                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No input rows found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    
    <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Outputs</div>
            <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Produced lots</div>
        </div>

        <div class="space-y-0">
            <?php $__empty_1 = true; $__currentLoopData = $outputs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $output): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $lot = $output->inventoryLot;
                    $mode = ($lot?->inward_mode === 'pieces') ? 'Pieces' : 'Qty';
                    $pieces = $lot?->pieces ?? collect();
                ?>

                <div class="border-b last:border-b-0 border-gray-100 dark:border-gray-800 p-5">
                    <div class="grid gap-4 xl:grid-cols-[1.2fr,1fr]">
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                                    <?php echo e($output->product->name ?? '—'); ?>

                                </div>

                                <span class="inline-flex items-center rounded-full border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950/40 px-2 py-1 text-[10px] text-gray-600 dark:text-gray-300">
                                    <?php echo e(ucfirst($output->output_stage)); ?>

                                </span>

                                <span class="inline-flex items-center rounded-full border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950/40 px-2 py-1 text-[10px] text-gray-600 dark:text-gray-300">
                                    Mode: <?php echo e($mode); ?>

                                </span>

                                <?php if($lot?->lot_code): ?>
                                    <span class="inline-flex items-center rounded-full border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950/40 px-2 py-1 text-[10px] text-gray-600 dark:text-gray-300">
                                        Lot: <?php echo e($lot->lot_code); ?>

                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                                    <div class="text-[10px] uppercase tracking-wide text-gray-400">Produced qty</div>
                                    <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">
                                        <?php echo e($fmtQty($output->produced_quantity)); ?>

                                    </div>
                                </div>

                                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                                    <div class="text-[10px] uppercase tracking-wide text-gray-400">Produced weight</div>
                                    <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">
                                        <?php echo e($fmtW($output->produced_weight_kg)); ?>

                                    </div>
                                </div>

                                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                                    <div class="text-[10px] uppercase tracking-wide text-gray-400">Piece count</div>
                                    <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">
                                        <?php if(!empty($output->piece_count)): ?>
                                            <?php echo e((int) $output->piece_count); ?>

                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                                    <div class="text-[10px] uppercase tracking-wide text-gray-400">Pack size</div>
                                    <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">
                                        <?php if(!empty($output->pack_size_kg)): ?>
                                            <?php echo e(number_format((float) $output->pack_size_kg, 3)); ?> kg
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <?php if(!empty($output->notes)): ?>
                                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3 text-[12px] text-gray-700 dark:text-gray-200">
                                    <?php echo e($output->notes); ?>

                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="space-y-3">
                            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950/20 p-4">
                                <div class="flex items-center justify-between text-[12px]">
                                    <span class="text-gray-500 dark:text-gray-400">Allocated cost</span>
                                    <span class="font-semibold text-gray-900 dark:text-gray-50">
                                        <?php echo e($fmtM($output->allocated_cost)); ?>

                                    </span>
                                </div>

                                <?php if($lot): ?>
                                    <div class="mt-3 grid gap-2 text-[11px] text-gray-600 dark:text-gray-300">
                                        <div class="flex items-center justify-between">
                                            <span>Available qty</span>
                                            <span class="font-medium text-gray-900 dark:text-gray-50"><?php echo e($fmtQty($lot->available_quantity)); ?></span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span>Available wt.</span>
                                            <span class="font-medium text-gray-900 dark:text-gray-50"><?php echo e($fmtW($lot->available_weight_kg)); ?></span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span>Saleable</span>
                                            <span class="font-medium text-gray-900 dark:text-gray-50"><?php echo e($lot->is_saleable ? 'Yes' : 'No'); ?></span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span>Can repack</span>
                                            <span class="font-medium text-gray-900 dark:text-gray-50"><?php echo e($lot->can_repack ? 'Yes' : 'No'); ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if($mode === 'Pieces'): ?>
                                <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 p-4">
                                    <div class="text-[11px] font-semibold text-gray-900 dark:text-gray-50 mb-3">
                                        Individual output weights
                                    </div>

                                    <?php if($pieces->isNotEmpty()): ?>
                                        <div class="flex flex-wrap gap-2">
                                            <?php $__currentLoopData = $pieces; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $piece): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <span class="inline-flex items-center rounded-full border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-1 text-[11px] text-gray-700 dark:text-gray-200">
                                                    #<?php echo e($piece->piece_no); ?> · <?php echo e(number_format((float) $piece->weight_kg, 3)); ?> kg
                                                </span>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>

                                        <div class="mt-3 text-[11px] text-gray-500 dark:text-gray-400">
                                            <?php echo e($pieces->count()); ?> piece(s) stored in this lot.
                                        </div>
                                    <?php else: ?>
                                        <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                            No individual piece rows found for this output lot.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    No output rows found.
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <?php if($status === 'reversed'): ?>
        <section class="rounded-2xl border border-amber-200 bg-amber-50/70 p-5 dark:border-amber-800 dark:bg-amber-900/20">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-amber-700 dark:text-amber-300">Reversal audit</div>
                    <h2 class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">This production run was reversed</h2>
                    <p class="mt-2 max-w-3xl text-[12px] leading-5 text-gray-700 dark:text-gray-200">
                        The original run remains in the audit history. Its source stock was restored and its output lots were cancelled.
                    </p>
                </div>

                <div class="text-[11px] text-gray-600 dark:text-gray-300 md:text-right">
                    <div><?php echo e($run->reversed_at?->format('d M Y, H:i') ?? '—'); ?></div>
                    <div class="mt-1">By <?php echo e($run->reversedBy?->name ?? 'Unknown user'); ?></div>
                </div>
            </div>

            <div class="mt-4 rounded-xl border border-amber-200 bg-white/70 px-4 py-3 text-[12px] text-gray-700 dark:border-amber-800 dark:bg-gray-950/30 dark:text-gray-200">
                <div class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Reason</div>
                <div class="mt-2 whitespace-pre-line"><?php echo e($run->reversal_reason ?: 'No reason recorded.'); ?></div>
            </div>
        </section>
    <?php elseif($canManageReversal): ?>
        <section class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <div class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Correction control</div>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Reverse production run</h2>
                <p class="mt-1 max-w-4xl text-[12px] leading-5 text-gray-500 dark:text-gray-400">
                    A reversal restores the consumed source stock and cancels all output stock from this run. It does not delete the production record.
                </p>
            </div>

            <?php if($reversal['can_reverse']): ?>
                <div class="grid gap-4 p-5 lg:grid-cols-2">
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-4 dark:border-emerald-800 dark:bg-emerald-900/20">
                        <div class="text-[11px] font-semibold text-emerald-800 dark:text-emerald-200">Source stock to restore</div>
                        <div class="mt-3 space-y-3">
                            <?php $__currentLoopData = $reversal['sources']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $source): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="rounded-lg border border-emerald-100 bg-white/80 px-3 py-2.5 dark:border-emerald-900 dark:bg-gray-950/30">
                                    <div class="font-medium text-gray-900 dark:text-gray-50">
                                        <?php echo e($source['product']); ?>

                                        <?php if(!empty($source['variant'])): ?>
                                            <span class="font-normal text-gray-500 dark:text-gray-400">— <?php echo e($source['variant']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                        Lot <?php echo e($source['lot_code'] ?: ('#' . $source['lot_id'])); ?>

                                    </div>
                                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-gray-700 dark:text-gray-200">
                                        <?php if((float) $source['restore_quantity'] > 0): ?>
                                            <span>+<?php echo e(number_format((float) $source['restore_quantity'], 3)); ?> qty</span>
                                        <?php endif; ?>
                                        <?php if((float) $source['restore_weight_kg'] > 0): ?>
                                            <span>+<?php echo e(number_format((float) $source['restore_weight_kg'], 3)); ?> kg</span>
                                        <?php endif; ?>
                                        <?php if((int) $source['restore_piece_count'] > 0): ?>
                                            <span>+<?php echo e((int) $source['restore_piece_count']); ?> piece(s)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>

                    <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-4 dark:border-amber-800 dark:bg-amber-900/20">
                        <div class="text-[11px] font-semibold text-amber-800 dark:text-amber-200">Output stock to cancel</div>
                        <div class="mt-3 space-y-3">
                            <?php $__currentLoopData = $reversal['outputs']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $output): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="rounded-lg border border-amber-100 bg-white/80 px-3 py-2.5 dark:border-amber-900 dark:bg-gray-950/30">
                                    <div class="font-medium text-gray-900 dark:text-gray-50">
                                        <?php echo e($output['product']); ?>

                                        <?php if(!empty($output['variant'])): ?>
                                            <span class="font-normal text-gray-500 dark:text-gray-400">— <?php echo e($output['variant']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                        Lot <?php echo e($output['lot_code'] ?: ('#' . $output['lot_id'])); ?>

                                    </div>
                                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-gray-700 dark:text-gray-200">
                                        <span><?php echo e(number_format((float) $output['cancel_quantity'], 3)); ?> qty</span>
                                        <span><?php echo e(number_format((float) $output['cancel_weight_kg'], 3)); ?> kg</span>
                                        <?php if((int) $output['cancel_piece_count'] > 0): ?>
                                            <span><?php echo e((int) $output['cancel_piece_count']); ?> piece(s)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                </div>

                <form method="POST"
                      action="<?php echo e(route('admin.production.reverse', $run)); ?>"
                      class="border-t border-gray-200 p-5 dark:border-gray-800"
                      onsubmit="return confirm('Reverse this production run? Source stock will be restored and all untouched output stock will be cancelled.');">
                    <?php echo csrf_field(); ?>

                    <label for="reversal_reason" class="block text-[12px] font-medium text-gray-800 dark:text-gray-100">
                        Reason for reversal <span class="text-red-500">*</span>
                    </label>
                    <textarea id="reversal_reason"
                              name="reversal_reason"
                              rows="3"
                              required
                              maxlength="1000"
                              class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-[12px] text-gray-900 outline-none focus:border-gray-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-50"
                              placeholder="Describe the mistake and why this run must be reversed."><?php echo e(old('reversal_reason')); ?></textarea>

                    <label class="mt-4 flex items-start gap-2 text-[11px] text-gray-600 dark:text-gray-300">
                        <input type="checkbox"
                               name="confirm_reverse"
                               value="1"
                               required
                               class="mt-0.5 rounded border-gray-300 dark:border-gray-700">
                        <span>I confirm that this is a full reversal. The output stock shown above has not been sold, reserved, repacked, or used in another production run.</span>
                    </label>

                    <div class="mt-4 flex justify-end">
                        <button type="submit"
                                class="inline-flex items-center rounded-xl border border-red-300 bg-red-50 px-4 py-2 text-[12px] font-semibold text-red-700 hover:bg-red-100 dark:border-red-800 dark:bg-red-900/25 dark:text-red-200 dark:hover:bg-red-900/40">
                            Reverse production run
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="p-5">
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-4 dark:border-red-800 dark:bg-red-900/20">
                        <div class="text-[12px] font-semibold text-red-800 dark:text-red-200">This run cannot currently be reversed</div>
                        <ul class="mt-3 list-disc space-y-1.5 pl-5 text-[11px] leading-5 text-red-700 dark:text-red-200">
                            <?php $__empty_1 = true; $__currentLoopData = $reversal['blockers']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $blocker): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <li><?php echo e($blocker); ?></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <li>The run is not eligible for reversal.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/production/show.blade.php ENDPATH**/ ?>