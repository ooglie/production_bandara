<?php $__env->startSection('title', 'Transform Stock'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $fmt = fn($n, $d = 2) => $n === null ? '—' : rtrim(rtrim(number_format((float) $n, $d), '0'), '.');
?>

<div class="max-w-7xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">Transform Stock / Pack Stock</h1>
            <p class="mt-1 text-[12px] text-gray-500 dark:text-gray-400">
                Convert repackable source stock such as bulk belly, fillets, boxes, or loose pieces into saleable finished packs/cuts. Output stock can belong to a different finished product when you make slices/slabs from a raw source lot.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="<?php echo e(route('admin.inventory.lots.index')); ?>" class="rounded border border-gray-300 px-4 py-2 text-xs hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">Inventory lots</a>
            <a href="<?php echo e(route('admin.inventory.packs.create')); ?>" class="rounded bg-gray-900 px-4 py-2 text-xs font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">+ Transform stock</a>
        </div>
    </div>

    <?php if(session('status')): ?>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
        This screen transforms source lots into saleable product or variant stock. It updates backend inventory, output product/variant stock, and stock movements; storefront layout remains unchanged.
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950">
        <table class="min-w-full divide-y divide-gray-200 text-xs dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr class="text-left text-[11px] uppercase text-gray-500 dark:text-gray-400">
                    <th class="px-3 py-2">Pack</th>
                    <th class="px-3 py-2">Output</th>
                    <th class="px-3 py-2">Source lot</th>
                    <th class="px-3 py-2 text-right">Pack qty</th>
                    <th class="px-3 py-2 text-right">Pieces / pack</th>
                    <th class="px-3 py-2 text-right">Source consumed</th>
                    <th class="px-3 py-2 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                <?php $__empty_1 = true; $__currentLoopData = $packs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pack): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $displaySourceLot = $pack->sourceLot?->parentLot ?: $pack->sourceLot;
                    ?>
                    <tr>
                        <td class="px-3 py-2 align-top">
                            <div class="font-medium text-gray-900 dark:text-gray-50"><?php echo e($pack->pack_code ?: ('Pack #' . $pack->id)); ?></div>
                            <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                <?php echo e(optional($pack->packed_date)->format('d M Y') ?: '—'); ?>

                            </div>
                        </td>
                        <td class="px-3 py-2 align-top">
                            <div class="font-medium text-gray-900 dark:text-gray-50"><?php echo e($pack->product?->name ?? ('Product #' . $pack->product_id)); ?></div>
                            <?php if($pack->productVariant): ?>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400">Variant: <?php echo e($pack->productVariant->name ?: $pack->productVariant->sku ?: ('#' . $pack->productVariant->id)); ?></div>
                            <?php endif; ?>
                            <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                <?php echo e(str_replace('_', ' ', (string) ($pack->productVariant?->pack_type ?? $pack->product?->pack_type ?? 'finished stock'))); ?>

                                <?php if($pack->productVariant?->product_weight || $pack->product?->product_weight): ?>
                                    · <?php echo e($fmt($pack->productVariant?->product_weight ?? $pack->product->product_weight, 3)); ?> kg
                                <?php endif; ?>
                                <?php if($pack->productVariant?->pieces_per_pack || $pack->product?->pieces_per_pack): ?>
                                    · <?php echo e($fmt($pack->productVariant?->pieces_per_pack ?? $pack->product->pieces_per_pack, 0)); ?> pcs/pack
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300">
                            <div><?php echo e($displaySourceLot?->lot_code ?: ('Lot #' . ($displaySourceLot?->id ?? $pack->source_inventory_lot_id ?? '—'))); ?></div>
                            <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                <?php echo e($displaySourceLot?->product?->name ?? '—'); ?>

                                <?php if($pack->sourceLot && $displaySourceLot && (int) $pack->sourceLot->id !== (int) $displaySourceLot->id): ?>
                                    · Output lot <?php echo e($pack->sourceLot->lot_code ?: ('#' . $pack->sourceLot->id)); ?>

                                <?php endif; ?>
                                <?php if($pack->sourcePiece): ?>
                                    <div>Piece: <?php echo e($pack->sourcePiece->label ?: ('Piece ' . $pack->sourcePiece->piece_no)); ?></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-3 py-2 align-top text-right text-gray-900 dark:text-gray-50"><?php echo e($fmt($pack->available_pack_quantity ?? $pack->pack_quantity)); ?></td>
                        <td class="px-3 py-2 align-top text-right text-gray-700 dark:text-gray-300"><?php echo e($fmt($pack->pieces_per_pack, 0)); ?></td>
                        <td class="px-3 py-2 align-top text-right text-gray-700 dark:text-gray-300"><?php echo e($fmt($pack->source_quantity_consumed, 3)); ?></td>
                        <td class="px-3 py-2 align-top text-center">
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] text-gray-700 dark:bg-gray-800 dark:text-gray-200"><?php echo e(ucfirst($pack->status ?? 'available')); ?></span>
                            <?php if($pack->soldOrder): ?>
                                <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                                    Order <?php echo e($pack->soldOrder->order_number ?: ('#' . $pack->sold_order_id)); ?>

                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" class="px-3 py-8 text-center text-xs text-gray-500 dark:text-gray-400">
                            No transformed pack stock yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php echo e($packs->links()); ?>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/inventory/packs/index.blade.php ENDPATH**/ ?>