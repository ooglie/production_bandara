<?php $__env->startSection('title', 'Vendor ' . $vendor->name); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-6xl mx-auto px-4 py-6 text-xs space-y-4">
    <div class="flex items-center justify-between gap-3 mb-2">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Vendor: <?php echo e($vendor->name); ?>

            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                <?php if($vendor->code): ?>
                    Code: <span class="font-mono"><?php echo e($vendor->code); ?></span>
                    ·
                <?php endif; ?>
                Created <?php echo e(optional($vendor->created_at)->format('d M Y')); ?>

            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="<?php echo e(route('admin.vendors.index')); ?>"
               class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                Back to vendors
            </a>
            <a href="<?php echo e(route('admin.vendors.edit', $vendor)); ?>"
               class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                Edit vendor
            </a>
            <a href="<?php echo e(route('admin.vendor-invoices.create', ['vendor_id' => $vendor->id])); ?>"
               class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                New vendor invoice
            </a>
            <a href="<?php echo e(route('admin.vendor-payments.create', ['vendor_id' => $vendor->id])); ?>"
               class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                Record payment
            </a>
        </div>
    </div>

    <?php if(session('status')): ?>
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <?php
        $invoices = $vendor->invoices ?? collect();
        $payments = $vendor->payments ?? collect();

        $totalInvoiced = (float) $invoices->sum('total_amount');
        $totalPaid     = (float) $payments->sum('amount');
        $balance       = max(0, $totalInvoiced - $totalPaid);
    ?>

    <div class="grid gap-3 lg:grid-cols-[2fr,1.3fr]">
        
        <div class="space-y-3">
            
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3 space-y-2">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        Vendor details
                    </p>
                    <span class="inline-flex items-center rounded-full border px-3 py-0.5 text-[10px]
                        <?php if($vendor->is_active ?? true): ?>
                            border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200
                        <?php else: ?>
                            border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400
                        <?php endif; ?>">
                        <?php echo e(($vendor->is_active ?? true) ? 'Active' : 'Inactive'); ?>

                    </span>
                </div>

                <div class="grid gap-3 md:grid-cols-2 text-[11px] text-gray-700 dark:text-gray-200 pt-1">
                    <div class="space-y-1">
                        <?php if($vendor->contact_name): ?>
                            <p>
                                Contact:
                                <span class="font-medium"><?php echo e($vendor->contact_name); ?></span>
                            </p>
                        <?php endif; ?>
                        <?php if($vendor->email): ?>
                            <p>
                                Email:
                                <span class="font-medium"><?php echo e($vendor->email); ?></span>
                            </p>
                        <?php endif; ?>
                        <?php if($vendor->phone): ?>
                            <p>
                                Phone:
                                <span class="font-medium"><?php echo e($vendor->phone); ?></span>
                            </p>
                        <?php endif; ?>
                        <?php if($vendor->gst_number): ?>
                            <p>
                                GSTIN:
                                <span class="font-mono"><?php echo e($vendor->gst_number); ?></span>
                            </p>
                        <?php endif; ?>
                        <?php if($vendor->fssai_number): ?>
                            <p>
                                FSSAI:
                                <span class="font-mono"><?php echo e($vendor->fssai_number); ?></span>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="space-y-1 text-[10px] text-gray-600 dark:text-gray-300">
                        <p class="font-medium text-[11px] text-gray-700 dark:text-gray-200">
                            Address
                        </p>
                        <?php if($vendor->address_line1 || $vendor->address_line2 || $vendor->city || $vendor->state || $vendor->pincode): ?>
                            <p>
                                <?php if($vendor->address_line1): ?>
                                    <?php echo e($vendor->address_line1); ?><br>
                                <?php endif; ?>
                                <?php if($vendor->address_line2): ?>
                                    <?php echo e($vendor->address_line2); ?><br>
                                <?php endif; ?>
                                <?php if($vendor->city || $vendor->state || $vendor->pincode): ?>
                                    <?php echo e($vendor->city); ?> <?php if($vendor->city && ($vendor->state || $vendor->pincode)): ?>, <?php endif; ?>
                                    <?php echo e($vendor->state); ?> <?php echo e($vendor->pincode); ?><br>
                                <?php endif; ?>
                                <?php echo e($vendor->country ?? 'India'); ?>

                            </p>
                        <?php else: ?>
                            <p class="text-gray-400">
                                No address saved yet.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-2 dark:border-gray-800">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                            Bank details
                        </p>
                        <a href="<?php echo e(route('admin.vendors.edit', $vendor)); ?>"
                           class="text-[10px] text-gray-500 hover:underline dark:text-gray-400">
                            Edit
                        </a>
                    </div>

                    <?php if($vendor->bank_name || $vendor->bank_ifsc_code || $vendor->bank_account_number): ?>
                        <div class="mt-2 grid gap-3 sm:grid-cols-3">
                            <div>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400">Bank</p>
                                <p class="mt-0.5 text-[11px] font-medium text-gray-700 dark:text-gray-200">
                                    <?php echo e($vendor->bank_name ?? '—'); ?>

                                </p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400">IFSC code</p>
                                <p class="mt-0.5 font-mono text-[11px] text-gray-700 dark:text-gray-200">
                                    <?php echo e($vendor->bank_ifsc_code ?? '—'); ?>

                                </p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400">Account number</p>
                                <p class="mt-0.5 font-mono text-[11px] text-gray-700 dark:text-gray-200">
                                    <?php echo e($vendor->maskedBankAccountNumber() ?? '—'); ?>

                                </p>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="mt-1 text-[10px] text-gray-400">
                            No bank details saved yet.
                        </p>
                    <?php endif; ?>
                </div>

                <?php if(!empty($vendor->notes)): ?>
                    <div class="pt-2 border-t border-gray-100 dark:border-gray-800 mt-2">
                        <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50 mb-0.5">
                            Internal notes
                        </p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 whitespace-pre-line">
                            <?php echo e($vendor->notes); ?>

                        </p>
                    </div>
                <?php endif; ?>
            </div>

            
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3 space-y-2">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        Recent invoices
                    </p>
                    <a href="<?php echo e(route('admin.vendor-invoices.index', ['vendor_id' => $vendor->id])); ?>"
                       class="text-[10px] text-gray-500 dark:text-gray-400 hover:underline">
                        View all
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-[11px]">
                        <thead class="bg-gray-50 dark:bg-gray-950/40">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Invoice</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Total</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <?php $__empty_1 = true; $__currentLoopData = ($invoices)->sortByDesc('invoice_date')->take(5); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $inv): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td class="px-3 py-2">
                                        <a href="<?php echo e(route('admin.vendor-invoices.show', $inv)); ?>"
                                           class="text-gray-900 dark:text-gray-50 hover:underline">
                                            <?php echo e($inv->invoice_number); ?>

                                        </a>
                                        <div class="text-[10px] text-gray-400">
                                            #<?php echo e($inv->id); ?>

                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-right text-gray-900 dark:text-gray-50">
                                        ₹<?php echo e(number_format($inv->total_amount, 2)); ?>

                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px]
                                            <?php if($inv->status === 'pending'): ?> border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200
                                            <?php elseif($inv->status === 'partially_paid'): ?> border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-800 dark:bg-sky-900/30 dark:text-sky-200
                                            <?php elseif($inv->status === 'paid'): ?> border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200
                                            <?php elseif($inv->status === 'cancelled'): ?> border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400
                                            <?php endif; ?>">
                                            <?php echo e(ucfirst(str_replace('_',' ',$inv->status))); ?>

                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">
                                        <?php echo e(optional($inv->invoice_date)->format('d M Y')); ?>

                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="4" class="px-3 py-3 text-center text-gray-500 dark:text-gray-400">
                                        No invoices for this vendor yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        
        <div class="space-y-3">
            
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3 space-y-1">
                <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                    Financial summary
                </p>

                <div class="space-y-1 text-[11px] text-gray-700 dark:text-gray-200">
                    <div class="flex items-center justify-between">
                        <span>Total invoiced</span>
                        <span>₹<?php echo e(number_format($totalInvoiced, 2)); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Total paid</span>
                        <span>₹<?php echo e(number_format($totalPaid, 2)); ?></span>
                    </div>
                    <div class="flex items-center justify-between font-semibold border-t border-dashed border-gray-200 dark:border-gray-700 pt-1 mt-1">
                        <span>Outstanding balance</span>
                        <span>₹<?php echo e(number_format($balance, 2)); ?></span>
                    </div>
                    <div class="flex items-center justify-between text-[10px] text-gray-500 dark:text-gray-400 pt-1">
                        <span>Invoices count</span>
                        <span><?php echo e($invoices->count()); ?></span>
                    </div>
                </div>
            </div>

            
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3 space-y-2">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        Recent payments
                    </p>
                    <a href="<?php echo e(route('admin.vendor-payments.index', ['vendor_id' => $vendor->id])); ?>"
                       class="text-[10px] text-gray-500 dark:text-gray-400 hover:underline">
                        View all
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-[11px]">
                        <thead class="bg-gray-50 dark:bg-gray-950/40">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Date</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Invoice</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Method</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Ref</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <?php $__empty_1 = true; $__currentLoopData = ($payments)->sortByDesc('payment_date')->take(5); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pay): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                                        <?php echo e($pay->payment_date?->format('d M Y')); ?>

                                    </td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                                        <?php if($pay->invoice): ?>
                                            <?php echo e($pay->invoice->invoice_number); ?>

                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">
                                        <?php echo e($pay->payment_method ?? '—'); ?>

                                    </td>
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">
                                        <?php echo e($pay->reference_number ?? '—'); ?>

                                    </td>
                                    <td class="px-3 py-2 text-right text-gray-900 dark:text-gray-50">
                                        ₹<?php echo e(number_format($pay->amount, 2)); ?>

                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="5" class="px-3 py-3 text-center text-gray-500 dark:text-gray-400">
                                        No payments recorded for this vendor yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3 space-y-2">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        Products supplied
                    </p>
                    <span class="text-[10px] text-gray-500 dark:text-gray-400">
                        Based on vendor invoices
                    </span>
                </div>

                <?php if(isset($suppliedProducts) && $suppliedProducts->isNotEmpty()): ?>
                    <ul class="space-y-1">
                        <?php $__currentLoopData = $suppliedProducts->take(10); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li class="flex items-center justify-between text-[11px] text-gray-700 dark:text-gray-200">
                                <div>
                                    <span class="font-medium"><?php echo e($product->name); ?></span>
                                    <?php if($product->sku ?? false): ?>
                                        <span class="text-[10px] text-gray-400">
                                            (<?php echo e($product->sku); ?>)
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <a href="<?php echo e(route('admin.products.edit', $product)); ?>"
                                   class="text-[10px] text-gray-500 dark:text-gray-400 hover:underline">
                                    View product
                                </a>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>

                    <?php if($suppliedProducts->count() > 10): ?>
                        <p class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                            + <?php echo e($suppliedProducts->count() - 10); ?> more product(s) supplied by this vendor.
                        </p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">
                        No products recorded yet for this vendor in purchase invoices.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/vendors/show.blade.php ENDPATH**/ ?>