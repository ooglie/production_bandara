<?php $__env->startSection('title', $direction === 'credit' ? 'Supplier Credit Note' : 'Supplier Debit Note'); ?>
<?php $__env->startSection('breadcrumb', 'Admin · Vendor Invoices · Adjustment'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $isCredit = $direction === 'credit';
    $title = $isCredit ? 'Record supplier credit note' : 'Record supplier debit note';
    $items = $invoice->items ?? collect();
    $linked = isset($linkedReturn) && $linkedReturn;
?>
<div class="max-w-6xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-start justify-between gap-3">
        <div>
            <div class="text-[11px] uppercase tracking-wide text-gray-400"><?php echo e($invoice->vendor?->name); ?> · <?php echo e($invoice->invoice_number); ?></div>
            <h1 class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50"><?php echo e($title); ?></h1>
            <p class="mt-1 text-[12px] text-gray-500 dark:text-gray-400">
                <?php echo e($isCredit ? 'Reduces' : 'Increases'); ?> the supplier payable without silently rewriting the original invoice.
            </p>
        </div>
        <a href="<?php echo e(route('admin.vendor-invoices.show', $invoice)); ?>"
           class="rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-2 text-[11px] hover:bg-gray-50 dark:hover:bg-gray-800">Back</a>
    </div>

    <?php if($errors->any()): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-[12px] text-red-800 dark:border-red-800 dark:bg-red-950/30 dark:text-red-200">
            <ul class="list-disc pl-4 space-y-1"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
        </div>
    <?php endif; ?>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Original total</div>
            <div class="mt-1 text-base font-semibold">₹<?php echo e(number_format($balance['original_total'], 2)); ?></div>
        </div>
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Posted adjustments</div>
            <div class="mt-1 text-base font-semibold">₹<?php echo e(number_format($balance['adjustment_total'], 2)); ?></div>
        </div>
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Adjusted payable</div>
            <div class="mt-1 text-base font-semibold">₹<?php echo e(number_format($balance['adjusted_total'], 2)); ?></div>
        </div>
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Outstanding</div>
            <div class="mt-1 text-base font-semibold">₹<?php echo e(number_format($balance['outstanding'], 2)); ?></div>
        </div>
    </div>

    <form method="POST" action="<?php echo e(route('admin.vendor-invoices.adjustments.store', [$invoice, $direction])); ?>" class="space-y-4" id="adjustment-form">
        <?php echo csrf_field(); ?>
        <?php if($linked): ?>
            <input type="hidden" name="linked_vendor_return_id" value="<?php echo e($linkedReturn->id); ?>">
        <?php endif; ?>

        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 space-y-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block mb-1 text-[11px] font-medium text-gray-700 dark:text-gray-300">Supplier document number</label>
                    <input name="supplier_document_number" required maxlength="120"
                           value="<?php echo e(old('supplier_document_number', $linked ? $linkedReturn->supplier_credit_note_number : '')); ?>"
                           class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]">
                </div>
                <div>
                    <label class="block mb-1 text-[11px] font-medium text-gray-700 dark:text-gray-300">Supplier document date</label>
                    <input type="date" name="supplier_document_date" required
                           value="<?php echo e(old('supplier_document_date', $linked ? optional($linkedReturn->supplier_credit_note_date)->format('Y-m-d') : now()->format('Y-m-d'))); ?>"
                           class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]">
                </div>
            </div>
            <div>
                <label class="block mb-1 text-[11px] font-medium text-gray-700 dark:text-gray-300">Reason</label>
                <input name="reason" required maxlength="500"
                       value="<?php echo e(old('reason', $linked ? $linkedReturn->reason : '')); ?>"
                       placeholder="Price correction, quantity shortage, post-invoice discount, omitted freight…"
                       class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]">
            </div>
            <div>
                <label class="block mb-1 text-[11px] font-medium text-gray-700 dark:text-gray-300">Notes</label>
                <textarea name="notes" rows="3" maxlength="10000"
                          class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]"><?php echo e(old('notes')); ?></textarea>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                <div class="text-[11px] uppercase tracking-wide text-gray-400">Adjustment allocation</div>
                <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">
                    <?php echo e($linked ? 'Amounts fixed from posted purchase return ' . $linkedReturn->return_number : 'Enter positive values; the system applies the credit/debit sign automatically'); ?>

                </div>
            </div>

            <?php if($linked): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-[12px]">
                        <thead class="bg-gray-50 dark:bg-gray-950/40"><tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Product</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Return</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Taxable value</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Tax</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Total</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <?php $__currentLoopData = $linkedReturn->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $returnItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium"><?php echo e($returnItem->invoiceItem?->product?->name ?? '—'); ?></div>
                                        <?php if($returnItem->invoiceItem?->productVariant): ?><div class="text-[10px] text-gray-400"><?php echo e($returnItem->invoiceItem->productVariant->name); ?></div><?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <?php if((float)$returnItem->weight_kg > 0): ?><?php echo e(number_format((float)$returnItem->weight_kg, 3)); ?> kg
                                        <?php else: ?><?php echo e(number_format((float)$returnItem->quantity, 3)); ?> units@endif
                                    </td>
                                    <td class="px-4 py-3 text-right">₹<?php echo e(number_format((float)$returnItem->subtotal_amount, 2)); ?></td>
                                    <td class="px-4 py-3 text-right">₹<?php echo e(number_format((float)$returnItem->tax_amount, 2)); ?></td>
                                    <td class="px-4 py-3 text-right font-semibold">₹<?php echo e(number_format((float)$returnItem->total_amount, 2)); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-950/40"><tr>
                            <td colspan="4" class="px-4 py-3 text-right font-medium">Supplier credit expected</td>
                            <td class="px-4 py-3 text-right font-semibold">₹<?php echo e(number_format((float)$linkedReturn->expected_total, 2)); ?></td>
                        </tr></tfoot>
                    </table>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-[12px]">
                        <thead class="bg-gray-50 dark:bg-gray-950/40"><tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Original invoice item</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Original subtotal</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500"><?php echo e($isCredit ? 'Credit' : 'Debit'); ?> taxable value</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500"><?php echo e($isCredit ? 'Credit' : 'Debit'); ?> tax</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Revised unit cost</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php $originalSubtotal = max(0, (float)$item->total - (float)$item->tax_amount); ?>
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium"><?php echo e($item->product?->name ?? '—'); ?></div>
                                        <?php if($item->productVariant): ?><div class="text-[10px] text-gray-400"><?php echo e($item->productVariant->name); ?></div><?php endif; ?>
                                        <div class="text-[10px] text-gray-400">Qty <?php echo e(number_format((float)$item->quantity, 3)); ?> · Unit cost ₹<?php echo e(number_format((float)$item->unit_cost, 2)); ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-right">₹<?php echo e(number_format($originalSubtotal, 2)); ?></td>
                                    <td class="px-4 py-3 text-right">
                                        <input type="number" min="0" step="0.01" name="lines[<?php echo e($item->id); ?>][subtotal_amount]"
                                               value="<?php echo e(old('lines.'.$item->id.'.subtotal_amount')); ?>"
                                               class="adjustment-value w-32 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-right">
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <input type="number" min="0" step="0.01" name="lines[<?php echo e($item->id); ?>][tax_amount]"
                                               value="<?php echo e(old('lines.'.$item->id.'.tax_amount')); ?>"
                                               class="adjustment-value w-28 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-right">
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <input type="number" min="0" step="0.01" name="lines[<?php echo e($item->id); ?>][revised_unit_cost]"
                                               value="<?php echo e(old('lines.'.$item->id.'.revised_unit_cost')); ?>"
                                               class="w-28 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-right">
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <tr class="bg-gray-50/60 dark:bg-gray-950/20">
                                <td class="px-4 py-3"><div class="font-medium">General / unallocated adjustment</div><div class="text-[10px] text-gray-400">Freight, rounding or another amount not tied to one product line.</div></td>
                                <td></td>
                                <td class="px-4 py-3 text-right"><input type="number" min="0" step="0.01" name="general_subtotal_amount" value="<?php echo e(old('general_subtotal_amount')); ?>" class="adjustment-value w-32 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-right"></td>
                                <td class="px-4 py-3 text-right"><input type="number" min="0" step="0.01" name="general_tax_amount" value="<?php echo e(old('general_tax_amount')); ?>" class="adjustment-value w-28 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-right"></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <div class="flex flex-col gap-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="text-[10px] uppercase tracking-wide text-gray-400">Draft amount</div>
                <div class="text-lg font-semibold" id="adjustment-total"><?php echo e($linked ? '₹'.number_format((float)$linkedReturn->expected_total, 2) : '₹0.00'); ?></div>
                <div class="text-[10px] text-gray-400">The original invoice values remain unchanged.</div>
            </div>
            <button class="rounded-xl bg-gray-900 dark:bg-gray-100 px-5 py-2.5 text-[12px] font-semibold text-white dark:text-gray-900">Create draft for review</button>
        </div>
    </form>
</div>

<?php if(!$linked): ?>
<script>
(() => {
    const fields = Array.from(document.querySelectorAll('.adjustment-value'));
    const output = document.getElementById('adjustment-total');
    const update = () => {
        const total = fields.reduce((sum, field) => sum + (Number.parseFloat(field.value) || 0), 0);
        output.textContent = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(total);
    };
    fields.forEach(field => field.addEventListener('input', update));
    update();
})();
</script>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/vendor_invoices/adjustment_form.blade.php ENDPATH**/ ?>