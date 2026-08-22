<?php $__env->startSection('title', 'Lot Pieces'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $has = fn($r) => \Illuminate\Support\Facades\Route::has($r);
?>

<div class="max-w-6xl mx-auto px-4 py-5 text-xs space-y-4">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Lot #<?php echo e($lot->id); ?> Pieces
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Piece-based inward. Pieces are consumed during production runs.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <?php if($has('admin.inventory.lots.index')): ?>
                <a href="<?php echo e(route('admin.inventory.lots.index')); ?>"
                   class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                    Back to lots
                </a>
            <?php endif; ?>
            <?php if($has('admin.production.create')): ?>
                <a href="<?php echo e(route('admin.production.create', ['lot_id' => $lot->id])); ?>"
                   class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                    Produce
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
        <table class="min-w-full text-[11px]">
            <thead class="bg-gray-50 dark:bg-gray-950/40">
            <tr>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Piece</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Weight (kg)</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Consumed</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            <?php $__currentLoopData = $pieces; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                    <td class="px-3 py-2 text-gray-900 dark:text-gray-50">
                        #<?php echo e($p->piece_no); ?>

                    </td>
                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                        <?php echo e(number_format((float)$p->weight_kg, 3)); ?>

                    </td>
                    <td class="px-3 py-2">
                        <span class="text-[10px] px-2 py-0.5 rounded-full border border-gray-300 text-gray-600 dark:text-gray-300">
                            <?php echo e($p->status ?? '—'); ?>

                        </span>
                    </td>
                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                        <?php echo e($p->consumed_at ?? '—'); ?>

                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        <?php echo e($pieces->links()); ?>

    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/inventory/lots/pieces.blade.php ENDPATH**/ ?>