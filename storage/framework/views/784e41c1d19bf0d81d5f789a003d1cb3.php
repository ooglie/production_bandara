<?php $__env->startSection('title', $vendorReturn->return_number); ?>
<?php $__env->startSection('breadcrumb', 'Admin · Vendor Invoices · Purchase return'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $draft = $vendorReturn->isDraft();
    $statusLabel = match($vendorReturn->status) { 'credit_pending' => 'Awaiting supplier credit note', 'credited' => 'Credited', default => 'Draft' };
?>
<div class="max-w-6xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div><div class="text-[11px] uppercase tracking-wide text-gray-400">Purchase return</div><h1 class="mt-1 text-lg font-semibold"><?php echo e($vendorReturn->return_number); ?></h1><div class="mt-2 text-[11px] text-gray-500"><?php echo e($invoice->vendor?->name); ?> · Invoice <?php echo e($invoice->invoice_number); ?></div></div>
        <div class="flex flex-wrap gap-2">
            <a href="<?php echo e(route('admin.vendor-invoices.show', $invoice)); ?>" class="rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-2 text-[11px]">Invoice</a>
            <?php if($draft): ?>
                <form method="POST" action="<?php echo e(route('admin.vendor-invoices.returns.destroy', [$invoice, $vendorReturn])); ?>" onsubmit="return confirm('Delete this purchase-return draft?')"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button class="rounded-lg border border-red-300 dark:border-red-800 px-3 py-2 text-[11px] text-red-700 dark:text-red-300">Delete draft</button></form>
                <form method="POST" action="<?php echo e(route('admin.vendor-invoices.returns.post', [$invoice, $vendorReturn])); ?>" onsubmit="return confirm('Post this purchase return? Stock will be reduced immediately and the action cannot be edited.')"><?php echo csrf_field(); ?><button class="rounded-lg bg-gray-900 dark:bg-gray-100 px-4 py-2 text-[11px] font-semibold text-white dark:text-gray-900">Post return</button></form>
            <?php elseif($vendorReturn->status === 'credit_pending'): ?>
                <a href="<?php echo e(route('admin.vendor-invoices.returns.credit-note', [$invoice, $vendorReturn])); ?>" class="rounded-lg bg-gray-900 dark:bg-gray-100 px-4 py-2 text-[11px] font-semibold text-white dark:text-gray-900">Record supplier credit note</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if(session('status')): ?><div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200"><?php echo e(session('status')); ?></div><?php endif; ?>
    <?php if($errors->any()): ?><div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800 dark:border-red-800 dark:bg-red-950/30 dark:text-red-200"><ul class="list-disc pl-4"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul></div><?php endif; ?>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4"><div class="text-[10px] uppercase tracking-wide text-gray-400">Status</div><div class="mt-1 font-semibold"><?php echo e($statusLabel); ?></div></div>
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4"><div class="text-[10px] uppercase tracking-wide text-gray-400">Return date</div><div class="mt-1 font-semibold"><?php echo e($vendorReturn->return_date?->format('d M Y')); ?></div><div class="text-[10px] text-gray-400"><?php echo e($vendorReturn->reference_number ?: 'No challan reference'); ?></div></div>
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4"><div class="text-[10px] uppercase tracking-wide text-gray-400">Expected supplier credit</div><div class="mt-1 text-base font-semibold">₹<?php echo e(number_format((float)$vendorReturn->expected_total, 2)); ?></div><div class="text-[10px] text-gray-400">Taxable ₹<?php echo e(number_format((float)$vendorReturn->expected_subtotal, 2)); ?> · Tax ₹<?php echo e(number_format((float)$vendorReturn->expected_tax, 2)); ?></div></div>
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4"><div class="text-[10px] uppercase tracking-wide text-gray-400">Credit note</div><div class="mt-1 font-semibold"><?php echo e($vendorReturn->supplier_credit_note_number ?: ($vendorReturn->credit_note_received ? 'Received' : 'Pending')); ?></div><div class="text-[10px] text-gray-400"><?php echo e($vendorReturn->supplier_credit_note_date?->format('d M Y') ?: '—'); ?></div></div>
    </div>

    <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5"><div class="grid gap-4 md:grid-cols-2"><div><div class="text-[10px] uppercase tracking-wide text-gray-400">Reason</div><div class="mt-1 text-[12px]"><?php echo e($vendorReturn->reason); ?></div><?php if($vendorReturn->notes): ?><div class="mt-2 whitespace-pre-line text-[11px] text-gray-500"><?php echo e($vendorReturn->notes); ?></div><?php endif; ?></div><div><div class="text-[10px] uppercase tracking-wide text-gray-400">Audit</div><div class="mt-1 text-[11px] text-gray-500">Created by <?php echo e($vendorReturn->creator?->name ?? 'System'); ?> · <?php echo e($vendorReturn->created_at?->format('d M Y H:i')); ?></div><?php if($vendorReturn->posted_at): ?><div class="text-[11px] text-gray-500">Posted by <?php echo e($vendorReturn->postedBy?->name ?? 'System'); ?> · <?php echo e($vendorReturn->posted_at->format('d M Y H:i')); ?></div><?php endif; ?> <?php if($vendorReturn->supplierCreditAdjustment): ?><div class="mt-2"><a class="underline font-medium" href="<?php echo e(route('admin.vendor-invoices.adjustments.show', [$invoice, $vendorReturn->supplierCreditAdjustment])); ?>">View linked credit <?php echo e($vendorReturn->supplierCreditAdjustment->adjustment_number); ?></a></div><?php endif; ?></div></div></section>

    <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800"><div class="text-sm font-semibold">Return lines</div><p class="mt-1 text-[10px] text-gray-400">The values below are calculated proportionately from the original supplier invoice line.</p></div>
        <div class="overflow-x-auto"><table class="min-w-full text-[11px]"><thead class="bg-gray-50 dark:bg-gray-950/40"><tr><th class="px-4 py-3 text-left">Product / lot</th><th class="px-4 py-3 text-right">Return</th><th class="px-4 py-3 text-right">Taxable</th><th class="px-4 py-3 text-right">Tax</th><th class="px-4 py-3 text-right">Total</th></tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            <?php $__currentLoopData = $vendorReturn->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr><td class="px-4 py-3"><div class="font-medium"><?php echo e($item->invoiceItem?->product?->name ?? '—'); ?></div><?php if($item->invoiceItem?->productVariant): ?><div class="text-[10px] text-gray-400"><?php echo e($item->invoiceItem->productVariant->name); ?></div><?php endif; ?><div class="text-[10px] text-gray-400"><?php echo e($item->inventoryLot?->lot_code ?? 'Lot unavailable'); ?> · <?php echo e(ucwords(str_replace('_', ' ', $item->return_mode))); ?></div></td><td class="px-4 py-3 text-right"><?php if((float)$item->weight_kg > 0): ?><?php echo e(number_format((float)$item->weight_kg, 3)); ?> kg <?php else: ?> <?php echo e(number_format((float)$item->quantity, 3)); ?> units <?php endif; ?> <?php if((int)$item->piece_count > 0): ?><div class="text-[10px] text-gray-400"><?php echo e($item->piece_count); ?> pcs</div><?php endif; ?></td><td class="px-4 py-3 text-right">₹<?php echo e(number_format((float)$item->subtotal_amount, 2)); ?></td><td class="px-4 py-3 text-right">₹<?php echo e(number_format((float)$item->tax_amount, 2)); ?></td><td class="px-4 py-3 text-right font-semibold">₹<?php echo e(number_format((float)$item->total_amount, 2)); ?></td></tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody></table></div>
    </section>

    <?php if($draft): ?>
        <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-4 text-[11px] text-amber-800 dark:border-amber-900 dark:bg-amber-950/20 dark:text-amber-300"><strong>Preview only:</strong> stock and supplier payable are unchanged until this draft is posted.</div>
    <?php elseif($vendorReturn->status === 'credit_pending'): ?>
        <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-4 text-[11px] text-amber-800 dark:border-amber-900 dark:bg-amber-950/20 dark:text-amber-300"><strong>Stock has been returned.</strong> The supplier payable remains unchanged until the supplier credit note is recorded and posted.</div>
    <?php else: ?>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-4 text-[11px] text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/20 dark:text-emerald-300">Stock and the supplier payable have both been adjusted. The original invoice remains unchanged for audit.</div>
    <?php endif; ?>

    <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5"><div class="text-[10px] uppercase tracking-wide text-gray-400">Current adjusted position</div><div class="mt-3 grid gap-3 sm:grid-cols-3"><div><div class="text-[10px] text-gray-400">Adjusted payable</div><div class="font-semibold">₹<?php echo e(number_format($balance['adjusted_total'], 2)); ?></div></div><div><div class="text-[10px] text-gray-400">Paid</div><div class="font-semibold">₹<?php echo e(number_format($balance['paid'], 2)); ?></div></div><div><div class="text-[10px] text-gray-400">Outstanding / vendor credit</div><div class="font-semibold"><?php if($balance['vendor_credit_due'] > 0): ?> Vendor credit ₹<?php echo e(number_format($balance['vendor_credit_due'], 2)); ?> <?php else: ?> ₹<?php echo e(number_format($balance['outstanding'], 2)); ?> <?php endif; ?></div></div></div></section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/vendor_invoices/return_show.blade.php ENDPATH**/ ?>