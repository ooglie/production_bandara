<?php $__env->startSection('title', 'Production / Repack'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">Production / Repack</h1>
            <p class="text-[12px] text-gray-500 dark:text-gray-400">
                Raw → Slab, Slab → Slice, and Raw → Slice Direct runs.
            </p>
        </div>

        <a href="<?php echo e(route('admin.production.create')); ?>"
           class="inline-flex items-center rounded-xl border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-[12px] font-semibold hover:bg-gray-800 dark:hover:bg-gray-200">
            New run
        </a>
    </div>

    <?php if(session('status')): ?>
        <div class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-[12px] text-emerald-800">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-[12px]">
                <thead class="bg-gray-50 dark:bg-gray-950/40">
                    <tr class="text-left text-gray-500 dark:text-gray-400">
                        <th class="px-4 py-3 font-medium">Run</th>
                        <th class="px-4 py-3 font-medium">Date</th>
                        <th class="px-4 py-3 font-medium">Type</th>
                        <th class="px-4 py-3 font-medium">Inputs</th>
                        <th class="px-4 py-3 font-medium">Outputs</th>
                        <th class="px-4 py-3 font-medium">Yield</th>
                        <th class="px-4 py-3 font-medium text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <?php $__empty_1 = true; $__currentLoopData = $runs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $run): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $runStatusClass = match ((string) $run->status) {
                                'completed' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/25 dark:text-emerald-200',
                                'reversed' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/25 dark:text-amber-200',
                                'cancelled' => 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/25 dark:text-red-200',
                                default => 'border-gray-200 bg-gray-50 text-gray-600 dark:border-gray-700 dark:bg-gray-950/30 dark:text-gray-300',
                            };
                        ?>
                        <tr>
                            <td class="px-4 py-3 text-gray-900 dark:text-gray-50 font-medium">
                                <div><?php echo e($run->run_number ?? ('Run #' . $run->id)); ?></div>
                                <span class="mt-1 inline-flex rounded-full border px-2 py-0.5 text-[10px] font-normal <?php echo e($runStatusClass); ?>">
                                    <?php echo e(ucfirst((string) $run->status)); ?>

                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                <?php echo e($run->run_date ? $run->run_date->format('d M Y') : '—'); ?>

                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                <?php echo e(str_replace('_', ' ', ucfirst($run->run_type))); ?>

                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                <?php echo e($run->inputs_count); ?>

                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                <?php echo e($run->outputs_count); ?>

                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                <?php echo e($run->yield_percent !== null ? number_format((float) $run->yield_percent, 2) . '%' : '—'); ?>

                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="<?php echo e(route('admin.production.show', $run)); ?>"
                                   class="text-[11px] text-gray-700 dark:text-gray-200 underline">
                                    View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No production runs yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php echo e($runs->links()); ?>

</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/production/index.blade.php ENDPATH**/ ?>