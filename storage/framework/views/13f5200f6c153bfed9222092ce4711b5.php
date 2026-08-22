<?php $__env->startSection('title', 'Vendor payments'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 py-6 text-xs space-y-4">
    <div class="flex items-center justify-between gap-3 mb-2">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Vendor payments
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Track payments made to vendors.
            </p>
        </div>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('manage vendor payments')): ?>
        <a href="<?php echo e(route('admin.vendor-payments.create')); ?>"
           class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
            New vendor payment
        </a>
        <?php endif; ?>
    </div>

    <form method="GET" action="<?php echo e(route('admin.vendor-payments.index')); ?>"
          class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3 space-y-2">
        <div class="grid gap-2 md:grid-cols-3">
            <div>
                <label class="block text-[11px] text-gray-600 dark:text-gray-300 mb-1">Vendor</label>
                <select name="vendor_id"
                        class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                    <option value="">All vendors</option>
                    <?php $__currentLoopData = $vendors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($v->id); ?>" <?php if(request('vendor_id') == $v->id): echo 'selected'; endif; ?>>
                            <?php echo e($v->name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 pt-2">
            <a href="<?php echo e(route('admin.vendor-payments.index')); ?>"
               class="text-[11px] text-gray-500 dark:text-gray-400 hover:underline">
                Reset
            </a>
            <button type="submit"
                    class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                Apply filters
            </button>
        </div>
    </form>

    <?php if(session('status')): ?>
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
        <table class="w-full text-[11px]">
            <thead class="bg-gray-50 dark:bg-gray-950/40">
                <tr>
                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Date</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Vendor</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Invoice</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Method</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Ref</th>
                    <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <?php $__empty_1 = true; $__currentLoopData = $payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                            <?php echo e($payment->payment_date?->format('d M Y')); ?>

                        </td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                            <?php echo e($payment->vendor?->name ?? '—'); ?>

                        </td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                            <?php if($payment->invoice): ?>
                                <?php echo e($payment->invoice->invoice_number); ?>

                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300">
                            <?php echo e($payment->payment_method ?? '—'); ?>

                        </td>
                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300">
                            <?php echo e($payment->reference_number ?? '—'); ?>

                        </td>
                        <td class="px-3 py-2 text-right text-gray-900 dark:text-gray-50">
                            ₹<?php echo e(number_format($payment->amount, 2)); ?>

                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">
                            No vendor payments found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div>
        <?php echo e($payments->links()); ?>

    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/vendor_payments/index.blade.php ENDPATH**/ ?>