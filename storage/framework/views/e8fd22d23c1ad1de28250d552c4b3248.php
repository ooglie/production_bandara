<?php $__env->startSection('title', 'Record Purchase Return'); ?>
<?php $__env->startSection('breadcrumb', 'Admin · Vendor Invoices · Purchase return'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-start justify-between gap-3">
        <div>
            <div class="text-[11px] uppercase tracking-wide text-gray-400"><?php echo e($invoice->vendor?->name); ?> · <?php echo e($invoice->invoice_number); ?></div>
            <h1 class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">Record purchase return</h1>
            <p class="mt-1 text-[12px] text-gray-500 dark:text-gray-400">Select only stock that is still available in the original inward lot. Sold, transformed, held or reserved stock is not returnable.</p>
        </div>
        <a href="<?php echo e(route('admin.vendor-invoices.show', $invoice)); ?>" class="rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-2 text-[11px]">Back</a>
    </div>

    <?php if($errors->any()): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-[12px] text-red-800 dark:border-red-800 dark:bg-red-950/30 dark:text-red-200"><ul class="list-disc pl-4 space-y-1"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul></div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(route('admin.vendor-invoices.returns.store', $invoice)); ?>" class="space-y-4">
        <?php echo csrf_field(); ?>
        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 space-y-4">
            <div class="grid gap-4 md:grid-cols-3">
                <div><label class="block mb-1 text-[11px] font-medium">Return date</label><input type="date" name="return_date" value="<?php echo e(old('return_date', now()->format('Y-m-d'))); ?>" required class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2"></div>
                <div><label class="block mb-1 text-[11px] font-medium">Return / challan reference</label><input name="reference_number" maxlength="120" value="<?php echo e(old('reference_number')); ?>" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2"></div>
                <div><label class="block mb-1 text-[11px] font-medium">Reason</label><input name="reason" maxlength="500" required value="<?php echo e(old('reason')); ?>" placeholder="Damaged goods, quality issue, shortage…" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2"></div>
            </div>
            <div><label class="block mb-1 text-[11px] font-medium">Notes</label><textarea name="notes" rows="2" maxlength="10000" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2"><?php echo e(old('notes')); ?></textarea></div>
        </section>

        <section class="space-y-3">
            <?php $__currentLoopData = $options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invoiceItemId => $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $item = $option['item'];
                    $lot = $option['lot'];
                    $mode = $option['mode'];
                    $blocked = !empty($option['blockers']);
                    $variant = $item->productVariant?->name;
                ?>
                <article class="rounded-2xl border <?php echo e($blocked ? 'border-gray-200 dark:border-gray-800 opacity-75' : 'border-gray-200 dark:border-gray-800'); ?> bg-white dark:bg-gray-900 overflow-hidden">
                    <div class="flex flex-col gap-2 border-b border-gray-200 dark:border-gray-800 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-50"><?php echo e($item->product?->name ?? 'Product'); ?> <?php if($variant): ?><span class="font-normal text-gray-500">· <?php echo e($variant); ?></span><?php endif; ?></div>
                            <div class="mt-1 text-[10px] text-gray-400">Invoice line #<?php echo e($item->id); ?> · <?php echo e($lot?->lot_code ?? 'No linked lot'); ?> · <?php echo e(ucfirst($mode)); ?> return</div>
                        </div>
                        <div class="text-right text-[11px] text-gray-500">
                            <?php if($mode === 'whole_piece'): ?>
                                Whole piece <?php echo e(number_format((float)$option['max_weight_kg'], 3)); ?> kg
                            <?php elseif($mode === 'weight' || $mode === 'pieces'): ?>
                                Maximum <?php echo e(number_format((float)$option['max_weight_kg'], 3)); ?> kg
                            <?php else: ?>
                                Maximum <?php echo e(number_format((float)$option['max_quantity'], 3)); ?> units
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if($blocked): ?>
                        <div class="px-5 py-4"><div class="text-[11px] font-medium text-gray-600 dark:text-gray-300">This line is not currently returnable:</div><ul class="mt-2 list-disc pl-5 text-[11px] text-gray-500 dark:text-gray-400"><?php $__currentLoopData = $option['blockers']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $blocker): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($blocker); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul></div>
                    <?php elseif($mode === 'pieces'): ?>
                        <div class="grid gap-2 p-5 sm:grid-cols-2 lg:grid-cols-3">
                            <?php $__currentLoopData = $option['returnable_pieces']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $piece): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <label class="flex items-start gap-3 rounded-xl border border-gray-200 dark:border-gray-700 p-3 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <input type="checkbox" name="items[<?php echo e($invoiceItemId); ?>][piece_ids][]" value="<?php echo e($piece->id); ?>" <?php if(in_array($piece->id, (array)old('items.'.$invoiceItemId.'.piece_ids', []))): echo 'checked'; endif; ?> class="mt-0.5 rounded border-gray-300">
                                    <span><span class="block font-medium"><?php echo e($piece->label ?: ('Piece '.$piece->piece_no)); ?></span><span class="text-[10px] text-gray-400"><?php echo e(number_format((float)($piece->available_weight_kg ?? $piece->weight_kg), 3)); ?> kg</span></span>
                                </label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php elseif($mode === 'packs'): ?>
                        <div class="grid gap-2 p-5 sm:grid-cols-2 lg:grid-cols-3">
                            <?php $__currentLoopData = $option['returnable_packs']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pack): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <label class="flex items-start gap-3 rounded-xl border border-gray-200 dark:border-gray-700 p-3 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <input type="checkbox" name="items[<?php echo e($invoiceItemId); ?>][pack_ids][]" value="<?php echo e($pack->id); ?>" <?php if(in_array($pack->id, (array)old('items.'.$invoiceItemId.'.pack_ids', []))): echo 'checked'; endif; ?> class="mt-0.5 rounded border-gray-300">
                                    <span><span class="block font-medium"><?php echo e($pack->pack_code ?: ('Pack #'.$pack->id)); ?></span><span class="text-[10px] text-gray-400"><?php echo e(number_format((float)($pack->available_pack_quantity ?? 1), 3)); ?> pack · <?php echo e(number_format((float)($pack->actual_weight_kg ?? $pack->total_weight_kg ?? 0), 3)); ?> kg</span></span>
                                </label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php elseif($mode === 'whole_piece'): ?>
                        <div class="p-5">
                            <input type="hidden" name="items[<?php echo e($invoiceItemId); ?>][whole_piece]" value="0">
                            <label class="flex min-h-12 cursor-pointer items-start gap-3 rounded-xl border border-gray-200 p-4 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800/50">
                                <input type="checkbox"
                                       name="items[<?php echo e($invoiceItemId); ?>][whole_piece]"
                                       value="1"
                                       <?php if(old('items.'.$invoiceItemId.'.whole_piece')): echo 'checked'; endif; ?>
                                       class="mt-0.5 h-5 w-5 rounded border-gray-300">
                                <span>
                                    <span class="block font-medium text-gray-900 dark:text-gray-50">Return the entire piece</span>
                                    <span class="mt-1 block text-[10px] text-gray-400"><?php echo e(number_format((float)$option['max_weight_kg'], 3)); ?> kg · partial-weight return is not permitted for this item.</span>
                                </span>
                            </label>
                        </div>
                    <?php elseif($mode === 'weight'): ?>
                        <div class="p-5 max-w-sm"><label class="block mb-1 text-[11px] font-medium">Weight to return (kg)</label><input type="number" name="items[<?php echo e($invoiceItemId); ?>][weight_kg]" min="0" max="<?php echo e($option['max_weight_kg']); ?>" step="0.001" value="<?php echo e(old('items.'.$invoiceItemId.'.weight_kg')); ?>" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2"><input type="hidden" name="items[<?php echo e($invoiceItemId); ?>][piece_count]" value="0"></div>
                    <?php else: ?>
                        <div class="p-5 max-w-sm"><label class="block mb-1 text-[11px] font-medium">Quantity to return</label><input type="number" name="items[<?php echo e($invoiceItemId); ?>][quantity]" min="0" max="<?php echo e($option['max_quantity']); ?>" step="0.001" value="<?php echo e(old('items.'.$invoiceItemId.'.quantity')); ?>" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2"></div>
                    <?php endif; ?>
                </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </section>

        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5" x-data="{ received: <?php echo e(old('credit_note_received') ? 'true' : 'false'); ?> }">
            <label class="flex items-start gap-3"><input type="hidden" name="credit_note_received" value="0"><input type="checkbox" name="credit_note_received" value="1" x-model="received" class="mt-0.5 rounded border-gray-300"><span><span class="block text-[12px] font-medium">Supplier credit note already received</span><span class="text-[10px] text-gray-400">When selected, posting the physical return will also reduce the supplier payable.</span></span></label>
            <div class="mt-4 grid gap-4 md:grid-cols-2" x-show="received" x-cloak>
                <div><label class="block mb-1 text-[11px] font-medium">Supplier credit-note number</label><input name="supplier_credit_note_number" maxlength="120" value="<?php echo e(old('supplier_credit_note_number')); ?>" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2"></div>
                <div><label class="block mb-1 text-[11px] font-medium">Credit-note date</label><input type="date" name="supplier_credit_note_date" value="<?php echo e(old('supplier_credit_note_date')); ?>" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2"></div>
            </div>
        </section>

        <div class="flex items-center justify-between gap-3 rounded-xl border border-amber-200 dark:border-amber-900 bg-amber-50/60 dark:bg-amber-950/20 p-4">
            <div class="text-[11px] text-amber-800 dark:text-amber-300"><strong>Nothing is changed yet.</strong> This creates a draft. Stock is reduced only after you review and post it.</div>
            <button class="shrink-0 rounded-xl bg-gray-900 dark:bg-gray-100 px-5 py-2.5 text-[12px] font-semibold text-white dark:text-gray-900">Create return draft</button>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/vendor_invoices/return_form.blade.php ENDPATH**/ ?>