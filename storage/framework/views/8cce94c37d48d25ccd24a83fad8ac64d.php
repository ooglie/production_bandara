<?php $__env->startSection('title', 'Correct Vendor Invoice Details'); ?>
<?php $__env->startSection('breadcrumb', 'Admin · Vendor Invoices · Correct details'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-4xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-start justify-between gap-3">
        <div>
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Vendor invoice</div>
            <h1 class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">Correct <?php echo e($invoice->invoice_number); ?></h1>
            <p class="mt-1 text-[12px] text-gray-500 dark:text-gray-400">
                Only reference details can be corrected here. Product, quantity, price and tax changes must use a return, credit note or debit note.
            </p>
        </div>
        <a href="<?php echo e(route('admin.vendor-invoices.show', $invoice)); ?>"
           class="rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-2 text-[11px] hover:bg-gray-50 dark:hover:bg-gray-800">Back</a>
    </div>

    <?php if($errors->any()): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-[12px] text-red-800 dark:border-red-800 dark:bg-red-950/30 dark:text-red-200">
            <ul class="list-disc pl-4 space-y-1">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(route('admin.vendor-invoices.update-details', $invoice)); ?>" class="space-y-4">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>

        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 space-y-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block mb-1 text-[11px] font-medium text-gray-700 dark:text-gray-300">Supplier invoice number</label>
                    <input name="invoice_number" value="<?php echo e(old('invoice_number', $invoice->invoice_number)); ?>" required maxlength="100"
                           class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]">
                </div>
                <div>
                    <label class="block mb-1 text-[11px] font-medium text-gray-700 dark:text-gray-300">Tally reference</label>
                    <input name="tally_reference" value="<?php echo e(old('tally_reference', $invoice->tally_reference)); ?>" maxlength="255"
                           class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]">
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block mb-1 text-[11px] font-medium text-gray-700 dark:text-gray-300">Invoice date</label>
                    <input type="date" name="invoice_date" value="<?php echo e(old('invoice_date', $invoice->invoice_date?->format('Y-m-d'))); ?>" required
                           class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]">
                </div>
                <div>
                    <label class="block mb-1 text-[11px] font-medium text-gray-700 dark:text-gray-300">Due date</label>
                    <input type="date" name="due_date" value="<?php echo e(old('due_date', $invoice->due_date?->format('Y-m-d'))); ?>"
                           class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]">
                </div>
            </div>

            <div>
                <label class="block mb-1 text-[11px] font-medium text-gray-700 dark:text-gray-300">Invoice notes</label>
                <textarea name="notes" rows="4" maxlength="10000"
                          class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]"><?php echo e(old('notes', $invoice->notes)); ?></textarea>
            </div>
        </section>

        <section class="rounded-2xl border border-amber-200 dark:border-amber-900 bg-amber-50/70 dark:bg-amber-950/20 p-5">
            <label class="block mb-1 text-[11px] font-medium text-amber-900 dark:text-amber-200">Reason for correction</label>
            <textarea name="correction_reason" rows="3" required maxlength="500"
                      placeholder="Explain why the supplier invoice reference details are being corrected."
                      class="w-full rounded-lg border border-amber-300 dark:border-amber-800 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]"><?php echo e(old('correction_reason')); ?></textarea>
            <p class="mt-2 text-[11px] text-amber-800 dark:text-amber-300">The old and new values, reason, user and timestamp will be retained in the audit history.</p>
        </section>

        <div class="flex items-center justify-end gap-2">
            <a href="<?php echo e(route('admin.vendor-invoices.show', $invoice)); ?>" class="px-3 py-2 text-[11px] text-gray-500 hover:underline">Cancel</a>
            <button class="rounded-xl bg-gray-900 dark:bg-gray-100 px-5 py-2 text-[12px] font-semibold text-white dark:text-gray-900">
                Save correction
            </button>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/vendor_invoices/edit_details.blade.php ENDPATH**/ ?>