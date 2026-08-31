<?php $__env->startSection('title', 'B2B Product Requests'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 py-6 text-xs space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">B2B Product Requests</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">Approve customer catalog requests and optionally set MOQ/pricing.</p>
        </div>
        <form method="GET" action="<?php echo e(route('admin.b2b.product-requests.index')); ?>" class="flex items-center gap-2">
            <select name="status" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">
                <?php $__currentLoopData = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled', 'all' => 'All']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($value); ?>" <?php if($status === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <button class="rounded-lg bg-gray-900 px-4 py-2 text-xs font-medium text-white dark:bg-gray-100 dark:text-gray-900">Filter</button>
        </form>
    </div>

    <?php if(session('status')): ?>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-[11px] text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200"><?php echo e(session('status')); ?></div>
    <?php endif; ?>

    <?php if($errors->any()): ?>
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-[11px] text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200"><?php echo e($errors->first()); ?></div>
    <?php endif; ?>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <table class="min-w-full text-[11px]">
            <thead class="bg-gray-50 dark:bg-gray-950/40">
                <tr class="text-left text-gray-500 dark:text-gray-400">
                    <th class="px-3 py-2 font-medium">Customer</th>
                    <th class="px-3 py-2 font-medium">Product</th>
                    <th class="px-3 py-2 font-medium">Request</th>
                    <th class="px-3 py-2 font-medium">Status</th>
                    <th class="px-3 py-2 font-medium text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <?php $__empty_1 = true; $__currentLoopData = $requests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="align-top">
                        <td class="px-3 py-3">
                            <div class="font-medium text-gray-900 dark:text-gray-50"><?php echo e($row->user?->name ?? ('User #' . $row->user_id)); ?></div>
                            <div class="text-gray-500 dark:text-gray-400"><?php echo e($row->user?->email); ?></div>
                        </td>
                        <td class="px-3 py-3">
                            <div class="font-medium text-gray-900 dark:text-gray-50"><?php echo e($row->product?->name ?? ('Product #' . $row->product_id)); ?></div>
                            <div class="text-gray-500 dark:text-gray-400">SKU: <?php echo e($row->product?->sku ?: '—'); ?></div>
                            <?php if($row->productVariant): ?>
                                <div class="mt-1 text-gray-500 dark:text-gray-400">Variant: <?php echo e($row->productVariant->name ?: $row->productVariant->sku); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-3 max-w-xs">
                            <div>Qty: <?php echo e($row->requested_quantity ? rtrim(rtrim(number_format((float) $row->requested_quantity, 2), '0'), '.') : '—'); ?></div>
                            <?php if($row->message): ?>
                                <div class="mt-1 text-gray-500 dark:text-gray-400"><?php echo e($row->message); ?></div>
                            <?php endif; ?>
                            <?php if($row->admin_note): ?>
                                <div class="mt-1 rounded bg-gray-50 p-2 text-gray-500 dark:bg-gray-950 dark:text-gray-400">Admin: <?php echo e($row->admin_note); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-3">
                            <?php
                                $badge = match($row->status) {
                                    'approved' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200',
                                    'rejected' => 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200',
                                    'cancelled' => 'border-gray-200 bg-gray-50 text-gray-600 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300',
                                    default => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200',
                                };
                            ?>
                            <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] <?php echo e($badge); ?>"><?php echo e(ucfirst($row->status)); ?></span>
                            <div class="mt-1 text-gray-500 dark:text-gray-400"><?php echo e($row->created_at?->format('d M Y')); ?></div>
                        </td>
                        <td class="px-3 py-3 text-right min-w-[280px]">
                            <?php if($row->status === 'pending'): ?>
                                <form method="POST" action="<?php echo e(route('admin.b2b.product-requests.approve', $row)); ?>" class="mb-2 rounded-lg border border-gray-200 p-2 text-left dark:border-gray-800">
                                    <?php echo csrf_field(); ?>
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        <input type="number" name="min_order_quantity" step="0.01" min="0.01" placeholder="MOQ" value="1" class="rounded border border-gray-300 px-2 py-1 text-[11px] dark:border-gray-700 dark:bg-gray-950">
                                        <input type="number" name="price" step="0.01" min="0" placeholder="B2B price optional" class="rounded border border-gray-300 px-2 py-1 text-[11px] dark:border-gray-700 dark:bg-gray-950">
                                    </div>
                                    <?php if($row->product_variant_id): ?>
                                        <select name="price_scope" class="mt-2 w-full rounded border border-gray-300 px-2 py-1 text-[11px] dark:border-gray-700 dark:bg-gray-950">
                                            <option value="product">Apply price at product level</option>
                                            <option value="variant" selected>Apply price to requested variant</option>
                                        </select>
                                    <?php else: ?>
                                        <input type="hidden" name="price_scope" value="product">
                                    <?php endif; ?>
                                    <input type="text" name="admin_note" placeholder="Admin note optional" class="mt-2 w-full rounded border border-gray-300 px-2 py-1 text-[11px] dark:border-gray-700 dark:bg-gray-950">
                                    <button class="mt-2 rounded-full bg-gray-900 px-3 py-1 text-[11px] font-medium text-white dark:bg-gray-100 dark:text-gray-900">Approve</button>
                                </form>
                                <form method="POST" action="<?php echo e(route('admin.b2b.product-requests.reject', $row)); ?>" class="text-left">
                                    <?php echo csrf_field(); ?>
                                    <div class="flex gap-2">
                                        <input type="text" name="admin_note" placeholder="Rejection note optional" class="min-w-0 flex-1 rounded border border-gray-300 px-2 py-1 text-[11px] dark:border-gray-700 dark:bg-gray-950">
                                        <button class="rounded-full border border-red-300 px-3 py-1 text-[11px] text-red-700 hover:bg-red-50 dark:border-red-900 dark:text-red-200 dark:hover:bg-red-950/40">Reject</button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <span class="text-gray-500 dark:text-gray-400">Resolved <?php echo e($row->resolved_at?->format('d M Y') ?: '—'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="5" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">No requests found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php echo e($requests->links()); ?>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/b2b/product-requests/index.blade.php ENDPATH**/ ?>