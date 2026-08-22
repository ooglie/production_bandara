<?php $__env->startSection('title', $adjustment->adjustment_number); ?>
<?php $__env->startSection('breadcrumb', 'Admin · Vendor Invoices · Adjustment'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $isDraft = $adjustment->isDraft();
    $isCredit = (float)$adjustment->total_delta < 0;
    $canReverse = $adjustment->isPosted()
        && !$adjustment->affects_stock
        && !$adjustment->reversal
        && !in_array($adjustment->type, ['metadata_correction', 'adjustment_reversal'], true);
?>
<div class="max-w-6xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="text-[11px] uppercase tracking-wide text-gray-400"><?php echo e($adjustment->typeLabel()); ?></div>
            <h1 class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50"><?php echo e($adjustment->adjustment_number); ?></h1>
            <div class="mt-2 flex flex-wrap gap-2">
                <span class="rounded-full border px-2.5 py-1 text-[10px] <?php echo e($isDraft ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200' : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200'); ?>"><?php echo e(ucfirst($adjustment->status)); ?></span>
                <?php if($adjustment->affects_stock): ?><span class="rounded-full border border-gray-200 dark:border-gray-700 px-2.5 py-1 text-[10px]">Stock linked</span><?php endif; ?>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="<?php echo e(route('admin.vendor-invoices.show', $invoice)); ?>" class="rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-2 text-[11px]">Invoice</a>
            <?php if($isDraft): ?>
                <form method="POST" action="<?php echo e(route('admin.vendor-invoices.adjustments.destroy', [$invoice, $adjustment])); ?>" onsubmit="return confirm('Delete this draft adjustment?')"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button class="rounded-lg border border-red-300 dark:border-red-800 px-3 py-2 text-[11px] text-red-700 dark:text-red-300">Delete draft</button></form>
                <form method="POST" action="<?php echo e(route('admin.vendor-invoices.adjustments.post', [$invoice, $adjustment])); ?>" onsubmit="return confirm('Post this adjustment? Posted values affect the supplier payable.')"><?php echo csrf_field(); ?><button class="rounded-lg bg-gray-900 dark:bg-gray-100 px-4 py-2 text-[11px] font-semibold text-white dark:text-gray-900">Post adjustment</button></form>
            <?php endif; ?>
        </div>
    </div>

    <?php if(session('status')): ?><div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200"><?php echo e(session('status')); ?></div><?php endif; ?>
    <?php if($errors->any()): ?><div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800 dark:border-red-800 dark:bg-red-950/30 dark:text-red-200"><ul class="list-disc pl-4"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul></div><?php endif; ?>

    <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4"><div class="text-[10px] uppercase tracking-wide text-gray-400">Supplier document</div><div class="mt-1 font-semibold"><?php echo e($adjustment->supplier_document_number ?: 'Internal audit entry'); ?></div><div class="text-[10px] text-gray-400"><?php echo e($adjustment->supplier_document_date?->format('d M Y') ?: '—'); ?></div></div>
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4"><div class="text-[10px] uppercase tracking-wide text-gray-400">Taxable-value delta</div><div class="mt-1 text-base font-semibold <?php echo e($isCredit ? 'text-emerald-700 dark:text-emerald-300' : ''); ?>">₹<?php echo e(number_format((float)$adjustment->subtotal_delta, 2)); ?></div></div>
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4"><div class="text-[10px] uppercase tracking-wide text-gray-400">Tax delta</div><div class="mt-1 text-base font-semibold">₹<?php echo e(number_format((float)$adjustment->tax_delta, 2)); ?></div></div>
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4"><div class="text-[10px] uppercase tracking-wide text-gray-400">Total delta</div><div class="mt-1 text-base font-semibold">₹<?php echo e(number_format((float)$adjustment->total_delta, 2)); ?></div></div>
    </div>

    <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5">
        <div class="grid gap-4 md:grid-cols-2">
            <div><div class="text-[10px] uppercase tracking-wide text-gray-400">Reason</div><div class="mt-1 text-[12px] text-gray-800 dark:text-gray-200"><?php echo e($adjustment->reason); ?></div></div>
            <div><div class="text-[10px] uppercase tracking-wide text-gray-400">Audit</div><div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Created by <?php echo e($adjustment->creator?->name ?? 'System'); ?> · <?php echo e($adjustment->created_at?->format('d M Y H:i')); ?></div><?php if($adjustment->posted_at): ?><div class="text-[11px] text-gray-500 dark:text-gray-400">Posted by <?php echo e($adjustment->postedBy?->name ?? 'System'); ?> · <?php echo e($adjustment->posted_at->format('d M Y H:i')); ?></div><?php endif; ?></div>
        </div>
        <?php if($adjustment->notes): ?><div class="mt-4 border-t border-gray-100 dark:border-gray-800 pt-4 whitespace-pre-line text-[12px] text-gray-600 dark:text-gray-300"><?php echo e($adjustment->notes); ?></div><?php endif; ?>
        <?php if($adjustment->reversesAdjustment): ?><div class="mt-4 text-[11px]">Reverses <a class="font-medium underline" href="<?php echo e(route('admin.vendor-invoices.adjustments.show', [$invoice, $adjustment->reversesAdjustment])); ?>"><?php echo e($adjustment->reversesAdjustment->adjustment_number); ?></a></div><?php endif; ?>
        <?php if($adjustment->reversal): ?><div class="mt-4 text-[11px]">Reversed by <a class="font-medium underline" href="<?php echo e(route('admin.vendor-invoices.adjustments.show', [$invoice, $adjustment->reversal])); ?>"><?php echo e($adjustment->reversal->adjustment_number); ?></a></div><?php endif; ?>
    </section>

    <?php if($adjustment->type === 'metadata_correction'): ?>
        <section class="grid gap-4 md:grid-cols-2">
            <?php $__currentLoopData = ['before' => 'Before', 'after' => 'After']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5">
                    <div class="text-[11px] uppercase tracking-wide text-gray-400"><?php echo e($label); ?></div>
                    <dl class="mt-3 space-y-2 text-[11px]">
                        <?php $__currentLoopData = (array)data_get($adjustment->meta, $key, []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="grid grid-cols-3 gap-2"><dt class="text-gray-400"><?php echo e(str_replace('_', ' ', ucfirst($field))); ?></dt><dd class="col-span-2 break-words text-gray-800 dark:text-gray-200"><?php echo e($value ?: '—'); ?></dd></div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </dl>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </section>
    <?php elseif($adjustment->items->isNotEmpty()): ?>
        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800"><div class="text-sm font-semibold">Adjustment lines</div></div>
            <div class="overflow-x-auto"><table class="min-w-full text-[11px]"><thead class="bg-gray-50 dark:bg-gray-950/40"><tr><th class="px-4 py-3 text-left">Product / allocation</th><th class="px-4 py-3 text-right">Taxable value</th><th class="px-4 py-3 text-right">Tax</th><th class="px-4 py-3 text-right">Total</th></tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <?php $__currentLoopData = $adjustment->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><tr><td class="px-4 py-3"><div class="font-medium"><?php echo e($item->invoiceItem?->product?->name ?? (data_get($item->meta, 'general_adjustment') ? 'General adjustment' : 'Unallocated')); ?></div><?php if($item->invoiceItem?->productVariant): ?><div class="text-[10px] text-gray-400"><?php echo e($item->invoiceItem->productVariant->name); ?></div><?php endif; ?></td><td class="px-4 py-3 text-right">₹<?php echo e(number_format((float)$item->subtotal_delta, 2)); ?></td><td class="px-4 py-3 text-right">₹<?php echo e(number_format((float)$item->tax_delta, 2)); ?></td><td class="px-4 py-3 text-right font-semibold">₹<?php echo e(number_format((float)$item->total_delta, 2)); ?></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody></table></div>
        </section>
    <?php endif; ?>

    <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5">
        <div class="text-[11px] uppercase tracking-wide text-gray-400">Current invoice balance</div>
        <div class="mt-3 grid gap-3 sm:grid-cols-3"><div><div class="text-[10px] text-gray-400">Adjusted payable</div><div class="font-semibold">₹<?php echo e(number_format($balance['adjusted_total'], 2)); ?></div></div><div><div class="text-[10px] text-gray-400">Paid</div><div class="font-semibold">₹<?php echo e(number_format($balance['paid'], 2)); ?></div></div><div><div class="text-[10px] text-gray-400">Outstanding / vendor credit</div><div class="font-semibold"><?php if($balance['vendor_credit_due'] > 0): ?> Vendor credit ₹<?php echo e(number_format($balance['vendor_credit_due'], 2)); ?> <?php else: ?> ₹<?php echo e(number_format($balance['outstanding'], 2)); ?> <?php endif; ?></div></div></div>
    </section>

    <?php if($canReverse): ?>
        <form method="POST" action="<?php echo e(route('admin.vendor-invoices.adjustments.reverse', [$invoice, $adjustment])); ?>" class="rounded-2xl border border-red-200 dark:border-red-900 bg-red-50/60 dark:bg-red-950/20 p-5" onsubmit="return confirm('Create an opposite adjustment to reverse this posted entry?')">
            <?php echo csrf_field(); ?>
            <div class="text-sm font-semibold text-red-900 dark:text-red-200">Reverse this financial adjustment</div>
            <p class="mt-1 text-[11px] text-red-700 dark:text-red-300">The original adjustment remains in the audit trail. A new opposite entry will be posted.</p>
            <div class="mt-3 flex flex-col gap-2 sm:flex-row"><input name="reversal_reason" required maxlength="500" placeholder="Reason for reversal" class="flex-1 rounded-lg border border-red-300 dark:border-red-800 bg-white dark:bg-gray-950 px-3 py-2 text-[11px]"><button class="rounded-lg border border-red-600 px-4 py-2 text-[11px] font-semibold text-red-700 dark:text-red-300">Reverse adjustment</button></div>
        </form>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/vendor_invoices/adjustment_show.blade.php ENDPATH**/ ?>