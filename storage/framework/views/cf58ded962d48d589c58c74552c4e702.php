<?php $__env->startSection('title', 'Cart'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $fmtQty = function ($qty) {
        $n = (float) $qty;
        return rtrim(rtrim(number_format($n, 2), '0'), '.');
    };

    $fmtW = function ($kg) {
        if ($kg === null) return '—';
        $n = (float) $kg;
        return rtrim(rtrim(number_format($n, 3), '0'), '.') . ' kg';
    };

    $unitLabel = function (?string $u) {
        $u = strtolower((string)$u);
        return $u === 'kg' ? 'kg' : 'pc';
    };

    $isB2BCart = auth()->check() && ((auth()->user()->customer_type ?? 'b2c') === 'b2b');
    $cartRoute = function (string $name, $parameter = null) use ($isB2BCart) {
        $routeName = $isB2BCart ? 'b2b.' . $name : $name;
        return $parameter === null ? route($routeName) : route($routeName, $parameter);
    };

    $groupedRows = collect();

    if (!empty($items) && $items->count() > 0) {
        $groupedRows = $items->groupBy(function ($it) {
            if (!empty($it->is_piece_selected) && !empty($it->selected_piece_meta['weight_kg'])) {
                return 'piece:' . $it->product_id . '|' . ($it->product_variant_id ?? 0) . '|' . number_format((float) $it->selected_piece_meta['weight_kg'], 3, '.', '');
            }

            return 'item:' . $it->id;
        })->values();
    }
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-xs space-y-4">

    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Your Cart</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Review items before checkout.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <?php if(\Illuminate\Support\Facades\Route::has('shop.index')): ?>
                <a href="<?php echo e(route('shop.index')); ?>"
                   class="text-[11px] px-3 py-1 rounded-sm border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                    Continue shopping
                </a>
            <?php else: ?>
                <a href="<?php echo e(route('home')); ?>"
                   class="text-[11px] px-3 py-1 rounded-sm border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                    Home
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if(!empty($pricingUpdatedCount) && $pricingUpdatedCount > 0): ?>
        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-2 text-[11px] text-gray-700 dark:text-gray-200">
            Your cart was updated for <?php echo e($pricingUpdatedCount); ?> item(s) based on current availability and pricing.
        </div>
    <?php endif; ?>

    <?php if(!empty($couponNotice)): ?>
        <div class="rounded-sm border border-yellow-300 bg-yellow-50 px-3 py-2 text-[11px] text-yellow-800">
            <?php echo e($couponNotice); ?>

        </div>
    <?php endif; ?>

    <?php if($errors->any()): ?>
        <div class="rounded-sm border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
            <ul class="list-disc pl-4 space-y-0.5">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    
    <?php if(!$cart || $items->count() === 0): ?>
        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6">
            <p class="text-gray-700 dark:text-gray-200">Your cart is empty.</p>
            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                Add products to your cart and they will appear here.
            </p>
        </div>
    <?php else: ?>
        <div class="grid gap-4 lg:grid-cols-3">

            
            <div class="lg:col-span-2 rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
                <form id="cart-bulk-remove-form"
                      method="POST"
                      action="<?php echo e($cartRoute('cart.bulk-destroy')); ?>"
                      data-cart-bulk-remove-form
                      data-bandara-confirm="Remove selected item(s) from cart?"
                      data-bandara-confirm-title="Remove selected items?"
                      data-bandara-confirm-text="Remove selected"
                      data-bandara-confirm-variant="danger">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <input type="hidden" name="return_to" value="<?php echo e(request()->getRequestUri()); ?>">
                </form>

                <div class="px-3 py-2 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between gap-3">
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-gray-50">Items</div>
                        <div class="text-[11px] text-gray-500 dark:text-gray-400">Select multiple rows to remove them together.</div>
                    </div>
                    <button type="submit"
                            form="cart-bulk-remove-form"
                            data-cart-bulk-remove-button="cart-bulk-remove-form"
                            disabled
                            class="inline-flex items-center rounded-sm border border-red-300 dark:border-red-700 px-3 py-1 text-[10px] text-red-700 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-950/30 disabled:opacity-40 disabled:cursor-not-allowed">
                        Remove selected
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-[11px]">
                        <thead class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800">
                        <tr class="text-left text-gray-600 dark:text-gray-300">
                            <th class="px-3 py-2 font-medium">
                                <input type="checkbox"
                                       data-cart-bulk-select-all="cart-bulk-remove-form"
                                       class="rounded border-gray-300 dark:border-gray-700">
                            </th>
                            <th class="px-3 py-2 font-medium">#</th>
                            <th class="px-3 py-2 font-medium">Item</th>
                            <th class="px-3 py-2 font-medium">Qty</th>
                            <th class="px-3 py-2 font-medium">Weight</th>
                            <th class="px-3 py-2 font-medium">Unit</th>
                            <th class="px-3 py-2 font-medium text-right">Total</th>
                            <th class="px-3 py-2 font-medium text-right">Remove</th>
                        </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <?php $__currentLoopData = $groupedRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rowGroup): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                /** @var \App\Models\CartItem $representative */
                                $representative = $rowGroup->first();
                                $product = $representative->product;
                                $variant = $representative->productVariant;

                                $name = $product?->name ?? 'Product';
                                $variantLabel = null;
                                if ($variant) {
                                    $variantName = trim((string) ($variant->name ?? ''));
                                    $packType = (string) ($variant->pack_type ?? '');
                                    if ($variantName !== '') {
                                        $variantLabel = $variantName;
                                    } elseif ($packType === 'fixed_piece_pack' && (float) ($variant->pieces_per_pack ?? 0) > 0) {
                                        $variantLabel = rtrim(rtrim(number_format((float) $variant->pieces_per_pack, 3), '0'), '.') . ' pcs pack';
                                    } elseif ($packType === 'fixed_weight_pack' && (float) ($variant->product_weight ?? 0) > 0) {
                                        $variantLabel = rtrim(rtrim(number_format((float) $variant->product_weight, 3), '0'), '.') . ' kg pack';
                                    } else {
                                        $variantLabel = $variant->sku ?? ('Variant #' . $variant->id);
                                    }
                                }
                                $gstRate = app(\App\Services\GstRateService::class)->rateForProduct($product, auth()->user());

                                $sellUnit = strtolower((string)($product?->sell_unit ?? 'piece'));
                                $isKg = $sellUnit === 'kg';

                                $isPieceGroup = (bool) ($representative->is_piece_selected ?? false);
                                $pieceMeta = $representative->selected_piece_meta ?? null;

                                $groupQty = $rowGroup->count();

                                $unitPrice = (float) ($representative->unit_price ?? 0);
                                $lineTotal = (float) $rowGroup->sum(fn ($it) => (float) ($it->total ?? 0));

                                $packWeight = (float)($variant?->product_weight ?? $product?->product_weight ?? 0);
                                $pricingUnitForRow = strtolower((string) ($variant?->pricing_unit ?? ($product?->pricing_unit ?? ($isKg ? 'kg' : 'pack'))));
                                $pricingUnitForRow = in_array($pricingUnitForRow, ['kg', 'pack'], true) ? $pricingUnitForRow : 'pack';
                                $fixedWeightKgUnit = !$isPieceGroup && $pricingUnitForRow === 'kg' && $packWeight > 0;

                                if ($isPieceGroup) {
                                    $weightPerPiece = (float) ($pieceMeta['weight_kg'] ?? 0);
                                    $weightLabel = $pieceMeta['weight_label'] ?? $fmtW($weightPerPiece);
                                    $itemWeight = $weightPerPiece * $groupQty;
                                    $qtyDisplay = $groupQty;
                                    $perSlabPrice = (float) ($representative->total ?? 0);
                                } else {
                                    $qtyRaw = (float) ($representative->quantity ?? 1);

                                    if ($fixedWeightKgUnit) {
                                        $qtyInt = (int) max(round($qtyRaw), 1);
                                        $qtyDisplay = $qtyInt;
                                        $itemWeight = (float) ($representative->item_weight ?? ($qtyInt * $packWeight));
                                    } elseif ($isKg) {
                                        $qtyDisplay = $fmtQty($qtyRaw);
                                        $itemWeight = (float) ($representative->item_weight ?? $qtyRaw);
                                    } else {
                                        $qtyInt = (int) max(round($qtyRaw), 1);
                                        $qtyDisplay = $qtyInt;
                                        $itemWeight = (float) ($representative->item_weight ?? ($qtyInt * $packWeight));
                                    }

                                    $perSlabPrice = null;
                                    $weightLabel = null;
                                }

                                static $counter = 0;
                                $counter++;

                                $manageStock = false;
                                $available = null;

                                if (!$isPieceGroup) {
                                    if ($variant) {
                                        if ($variant->stock_quantity !== null || (bool)($variant->manage_stock ?? false)) {
                                            $manageStock = true;
                                            $available = (float)($variant->stock_quantity ?? 0);
                                        }
                                    } elseif ($product && (bool)($product->manage_stock ?? false)) {
                                        $manageStock = true;
                                        $available = (float)($product->stock_quantity ?? 0);
                                    } elseif ($product && $product->stock_quantity !== null && (float)$product->stock_quantity > 0) {
                                        $manageStock = true;
                                        $available = (float) $product->stock_quantity;
                                    }
                                }

                                $maxQty = null;
                                if ($manageStock) {
                                    $available = max((float)$available, 0);
                                    $maxQty = ($isKg && ! $fixedWeightKgUnit) ? round($available, 2) : (float) max((int) floor($available), 0);
                                }

                                $qtyForMath = $isPieceGroup
                                    ? (float) $groupQty
                                    : (($isKg && ! $fixedWeightKgUnit) ? (float) $representative->quantity : (float) max((int) round((float) $representative->quantity), 1));

                                $step = ($isKg && ! $fixedWeightKgUnit) ? 0.01 : 1;
                                $minQty = ($isKg && ! $fixedWeightKgUnit) ? 0.01 : 1;

                                $decDisabled = (!$isPieceGroup && ($qtyForMath <= ($minQty + 1e-9)));
                                $decQty = ($isKg && ! $fixedWeightKgUnit)
                                    ? round(max($qtyForMath - $step, $minQty), 2)
                                    : (float) max((int) round($qtyForMath - $step), 1);
                                $incQty = ($isKg && ! $fixedWeightKgUnit)
                                    ? round($qtyForMath + $step, 2)
                                    : (float) ((int) round($qtyForMath + $step));

                                $atMax = (!$isPieceGroup && $maxQty !== null && $maxQty > 0 && $qtyForMath >= ($maxQty - 1e-9));

                                $displayUnitPrice = $unitPrice;
                                $displayLineTotal = $lineTotal;
                                $displayPriceNote = null;

                                if ($product) {
                                    $quote = app(\App\Services\PricingService::class)->quote(auth()->user(), $product, $variant);
                                    $displayUnitPrice = (float) ($quote['price'] ?? $unitPrice);
                                    $displayPriceNote = ($quote['display_price_includes_gst'] ?? false) ? 'incl GST' : 'excl GST';

                                    if ($isPieceGroup) {
                                        $displayTaxMultiplier = ($quote['display_price_includes_gst'] ?? false)
                                            ? (1 + max($gstRate, 0) / 100)
                                            : 1;
                                        $perSlabPrice = round((float) ($representative->total ?? 0) * $displayTaxMultiplier, 2);
                                        $displayLineTotal = round($lineTotal * $displayTaxMultiplier, 2);
                                    } else {
                                        $displayLineTotal = $pricingUnitForRow === 'kg'
                                            ? round(max((float) $itemWeight, 0) * $displayUnitPrice, 2)
                                            : round($qtyForMath * $displayUnitPrice, 2);
                                    }
                                }
                            ?>

                            <tr class="text-gray-700 dark:text-gray-200">
                                <td class="px-3 py-2 whitespace-nowrap align-top">
                                    <input type="checkbox"
                                           form="cart-bulk-remove-form"
                                           name="cart_item_keys[]"
                                           value="<?php echo e($rowGroup->pluck('id')->implode(',')); ?>"
                                           data-cart-bulk-checkbox="cart-bulk-remove-form"
                                           class="rounded border-gray-300 dark:border-gray-700">
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap align-top"><?php echo e($counter); ?></td>

                                <td class="px-3 py-2">
                                    <div class="font-medium text-gray-900 dark:text-gray-50"><?php echo e($name); ?></div>

                                    <?php if(!$isB2BCart && $variantLabel): ?>
                                        <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                                            <?php echo e($variantLabel); ?>

                                        </div>
                                    <?php endif; ?>

                                    <?php if(!$isB2BCart && $isPieceGroup && !empty($pieceMeta)): ?>
                                        <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                                            Selected slab size: <?php echo e($weightLabel); ?>

                                        </div>
                                    <?php elseif(!$isB2BCart && !empty($product?->product_weight)): ?>
                                        <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                                            Pack wt: <?php echo e($fmtW($product->product_weight)); ?>

                                        </div>
                                    <?php endif; ?>

                                    <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                                        GST <?php echo e($gstRate); ?>%
                                    </div>
                                </td>

                                <td class="px-3 py-2">
                                    <?php if($isPieceGroup): ?>
                                        <div class="flex items-center gap-1">
                                            
                                            <form method="POST" action="<?php echo e($cartRoute('cart.update', $representative->id)); ?>">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('PATCH'); ?>
                                                <input type="hidden" name="quantity" value="<?php echo e($groupQty); ?>">
                                                <input type="hidden" name="piece_group_action" value="dec">
                                                <button type="submit"
                                                        title="Remove one slab"
                                                        class="h-5 w-5 inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                                    –
                                                </button>
                                            </form>

                                            <span class="inline-flex items-center justify-center min-w-[28px] px-2 py-1 rounded-sm border border-gray-300 dark:border-gray-700 text-[11px]">
                                                <?php echo e($groupQty); ?>

                                            </span>

                                            
                                            <form method="POST" action="<?php echo e($cartRoute('cart.update', $representative->id)); ?>">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('PATCH'); ?>
                                                <input type="hidden" name="quantity" value="<?php echo e($groupQty); ?>">
                                                <input type="hidden" name="piece_group_action" value="inc">
                                                <button type="submit"
                                                        title="Add one more slab of same size"
                                                        class="h-5 w-5 inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                                    +
                                                </button>
                                            </form>
                                        </div>

                                        <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                                            Exact-size grouped selection
                                        </div>
                                    <?php else: ?>
                                        <div class="flex items-center gap-1">
                                            
                                            <form method="POST" action="<?php echo e($cartRoute('cart.update', $representative->id)); ?>">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('PATCH'); ?>
                                                <input type="hidden" name="quantity" value="<?php echo e($decQty); ?>">
                                                <button type="submit"
                                                        <?php if($decDisabled): echo 'disabled'; endif; ?>
                                                        title="<?php echo e($decDisabled ? 'Minimum reached' : 'Decrease quantity'); ?>"
                                                        class="h-5 w-5 inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-transparent">
                                                    –
                                                </button>
                                            </form>

                                            
                                            <form method="POST"
                                                  action="<?php echo e($cartRoute('cart.update', $representative->id)); ?>"
                                                  class="flex items-center gap-1 js-qty-update"
                                                  data-sell-unit="<?php echo e($sellUnit); ?>"
                                                  data-min="<?php echo e($minQty); ?>"
                                                  data-step="<?php echo e($step); ?>"
                                                  <?php if(!$isB2BCart && $maxQty !== null && $maxQty > 0): ?> data-max="<?php echo e($maxQty); ?>" <?php endif; ?>>
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('PATCH'); ?>

                                                <input type="number"
                                                       name="quantity"
                                                       step="<?php echo e($step); ?>"
                                                       min="<?php echo e($minQty); ?>"
                                                       <?php if(!$isB2BCart && $maxQty !== null && $maxQty > 0): ?> max="<?php echo e($maxQty); ?>" <?php endif; ?>
                                                       value="<?php echo e($qtyDisplay); ?>"
                                                       class="w-20 rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px]">
                                                <button type="submit"
                                                        class="px-2 py-1 rounded-sm border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 text-[10px]">
                                                    Update
                                                </button>
                                            </form>

                                            
                                            <form method="POST"
                                                  action="<?php echo e($cartRoute('cart.update', $representative->id)); ?>"
                                                  class="js-inc-form"
                                                  data-current="<?php echo e($qtyForMath); ?>"
                                                  data-step="<?php echo e($step); ?>"
                                                  <?php if(!$isB2BCart && $maxQty !== null && $maxQty > 0): ?> data-max="<?php echo e($maxQty); ?>" <?php endif; ?>>
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('PATCH'); ?>
                                                <input type="hidden" name="quantity" value="<?php echo e($incQty); ?>">
                                                <button type="submit"
                                                        title="<?php echo e($atMax ? 'Limited stock of this product available' : 'Increase quantity'); ?>"
                                                        class="h-5 w-5 inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 <?php echo e($atMax ? 'opacity-40' : ''); ?>">
                                                    +
                                                </button>
                                            </form>
                                        </div>

                                        <?php if(!$isB2BCart && $maxQty !== null && $maxQty > 0): ?>
                                            <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                                                Max: <?php echo e($isKg ? $fmtQty($maxQty) : (int)$maxQty); ?> <?php echo e($unitLabel($sellUnit)); ?>

                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>

                                <td class="px-3 py-2 whitespace-nowrap">
                                    <?php if($isPieceGroup): ?>
                                        <div><?php echo e($weightLabel); ?> each</div>
                                        <?php if($groupQty > 1): ?>
                                            <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                                                <?php echo e($fmtW($itemWeight)); ?> total
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php echo e($fmtW($itemWeight)); ?>

                                    <?php endif; ?>
                                </td>

                                <td class="px-3 py-2 whitespace-nowrap">
                                    <?php if($isPieceGroup): ?>
                                        ₹<?php echo e(number_format($perSlabPrice, 2)); ?> / slab
                                        <?php if($displayPriceNote): ?>
                                            <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400"><?php echo e($displayPriceNote); ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        ₹<?php echo e(number_format($displayUnitPrice, 2)); ?>

                                        <?php if($displayPriceNote): ?>
                                            <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400"><?php echo e($displayPriceNote); ?></div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>

                                <td class="px-3 py-2 whitespace-nowrap text-right">
                                    ₹<?php echo e(number_format($displayLineTotal, 2)); ?>

                                </td>

                                <td class="px-3 py-2 whitespace-nowrap text-right">
                                    <?php if($isPieceGroup): ?>
                                        <form method="POST"
                                              action="<?php echo e($cartRoute('cart.update', $representative->id)); ?>"
                                              data-bandara-confirm="Remove all slabs of this size from cart?"
                                              data-bandara-confirm-title="Remove slab group?"
                                              data-bandara-confirm-text="Remove all"
                                              data-bandara-confirm-variant="danger">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('PATCH'); ?>
                                            <input type="hidden" name="quantity" value="<?php echo e($groupQty); ?>">
                                            <input type="hidden" name="piece_group_action" value="remove_all">
                                            <button type="submit"
                                                    class="inline-flex items-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1 text-[10px] hover:bg-gray-100 dark:hover:bg-gray-800">
                                                Remove all
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST"
                                              action="<?php echo e($cartRoute('cart.destroy', $representative->id)); ?>"
                                              data-bandara-confirm="Remove this item from cart?"
                                              data-bandara-confirm-title="Remove item?"
                                              data-bandara-confirm-text="Remove"
                                              data-bandara-confirm-variant="danger">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit"
                                                    class="inline-flex items-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1 text-[10px] hover:bg-gray-100 dark:hover:bg-gray-800">
                                                Remove
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>

                    </table>
                </div>
            </div>

            
            <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-3">
                <h2 class="font-semibold text-gray-900 dark:text-gray-50">Summary</h2>

                <div class="flex items-center justify-between text-[11px]">
                    <span class="text-gray-600 dark:text-gray-300">Subtotal <span class="text-[10px] text-gray-400">(excl GST)</span></span>
                    <span class="text-gray-900 dark:text-gray-50">₹<?php echo e(number_format($subtotal, 2)); ?></span>
                </div>

                
                <?php if(!$isB2BCart): ?>
                    <div class="rounded-sm border border-gray-200 dark:border-gray-800 p-3 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] text-gray-600 dark:text-gray-300">Coupon</span>
                            <?php if(!empty($coupon)): ?>
                                <span class="text-[10px] rounded-sm border border-gray-300 dark:border-gray-700 px-2 py-0.5">
                                    <?php echo e($coupon->code); ?>

                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if(!empty($coupon)): ?>
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="text-gray-500 dark:text-gray-400">Discount</span>
                                <span class="text-gray-900 dark:text-gray-50">-₹<?php echo e(number_format($discount ?? 0, 2)); ?></span>
                            </div>

                            <form method="POST" action="<?php echo e(route('cart.coupon.remove')); ?>">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit"
                                        class="w-full inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-4 py-2 text-[11px] hover:bg-gray-100 dark:hover:bg-gray-800">
                                    Remove coupon
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" action="<?php echo e(route('cart.coupon.apply')); ?>" class="space-y-2">
                                <?php echo csrf_field(); ?>
                                <input type="text" name="coupon_code" value="<?php echo e(old('coupon_code')); ?>"
                                       placeholder="Enter coupon code"
                                       class="w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]">
                                <button type="submit"
                                        class="w-full inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-4 py-2 text-[11px] hover:bg-gray-100 dark:hover:bg-gray-800">
                                    Apply coupon
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="flex items-center justify-between text-[11px]">
                    <span class="text-gray-600 dark:text-gray-300">Total after discount <span class="text-[10px] text-gray-400">(excl GST)</span></span>
                    <span class="text-gray-900 dark:text-gray-50">₹<?php echo e(number_format($totalAfterDiscount ?? $subtotal, 2)); ?></span>
                </div>

                <?php
                    $cartGstTotal = (float) ($cartGst['tax_total'] ?? 0);
                    $cartTotalInclGst = (float) ($cartGrandTotal ?? (($totalAfterDiscount ?? $subtotal) + $cartGstTotal));
                ?>

                <div class="flex items-center justify-between text-[11px]">
                    <span class="text-gray-600 dark:text-gray-300">Estimated product GST</span>
                    <span class="text-gray-900 dark:text-gray-50">₹<?php echo e(number_format($cartGstTotal, 2)); ?></span>
                </div>

                <?php
                    $cartDeliveryQuote = $deliveryQuote ?? [];
                    $cartDeliveryFee = (float) ($cartDeliveryQuote['delivery_fee'] ?? 0);
                    $cartHandlingFee = (float) ($cartDeliveryQuote['handling_fee'] ?? 0);
                    $cartHasHandlingRule = !empty($cartDeliveryQuote['handling_rule_id']) || $cartHandlingFee > 0;
                    $cartHandlingWasWaived = (bool) ($cartDeliveryQuote['handling_free_handling_applied'] ?? false);
                    $cartChargeTax = (float) ($cartDeliveryQuote['tax_total'] ?? 0);
                ?>

                <?php if($cartDeliveryFee > 0 || $cartHasHandlingRule || $cartChargeTax > 0 || !empty($cartDeliveryQuote['messages'])): ?>
                    <div class="border-t border-gray-100 dark:border-gray-800 pt-2 space-y-2">
                        <?php if(($cartDeliveryQuote['delivery_fee_source'] ?? null) === 'distance' && !empty($cartDeliveryQuote['delivery_distance_km'])): ?>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-[10px] text-gray-600 dark:border-gray-800 dark:bg-gray-950/40 dark:text-gray-300">
                                Estimated delivery distance: <?php echo e(number_format((float) $cartDeliveryQuote['delivery_distance_km'], 2)); ?> km from store
                                <?php if(!empty($cartDeliveryQuote['delivery_duration_minutes'])): ?>
                                    · approx. <?php echo e((int) $cartDeliveryQuote['delivery_duration_minutes']); ?> min
                                <?php endif; ?>
                                <?php if(($cartDeliveryQuote['delivery_fee_formula'] ?? null) === 'base_plus_per_km'): ?>
                                    <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                                        Fee: ₹<?php echo e(number_format((float) ($cartDeliveryQuote['delivery_base_fee'] ?? 0), 2)); ?> base
                                        <?php if((float) ($cartDeliveryQuote['delivery_included_distance_km'] ?? 0) > 0): ?>
                                            covers first <?php echo e(number_format((float) $cartDeliveryQuote['delivery_included_distance_km'], 2)); ?> km
                                        <?php endif; ?>
                                        + ₹<?php echo e(number_format((float) ($cartDeliveryQuote['delivery_per_km_fee'] ?? 0), 2)); ?> × <?php echo e((int) ($cartDeliveryQuote['delivery_chargeable_km_units'] ?? 0)); ?> started km after base.
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-gray-600 dark:text-gray-300">Estimated delivery fee</span>
                            <span class="text-gray-900 dark:text-gray-50">₹<?php echo e(number_format($cartDeliveryFee, 2)); ?></span>
                        </div>
                        <?php if($cartHasHandlingRule): ?>
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="text-gray-600 dark:text-gray-300">Cold-chain handling & packing</span>
                                <span class="text-gray-900 dark:text-gray-50">
                                    <?php if($cartHandlingFee > 0): ?>
                                        ₹<?php echo e(number_format($cartHandlingFee, 2)); ?>

                                    <?php else: ?>
                                        Free
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php if($cartHandlingWasWaived && (float) ($cartDeliveryQuote['handling_fee_before_waiver'] ?? 0) > 0): ?>
                                <div class="-mt-1 text-[10px] text-emerald-600 dark:text-emerald-300">
                                    Cold-chain handling waived for this order.
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if($cartChargeTax > 0): ?>
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="text-gray-600 dark:text-gray-300">Delivery / handling GST</span>
                                <span class="text-gray-900 dark:text-gray-50">₹<?php echo e(number_format($cartChargeTax, 2)); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if(!empty($cartDeliveryQuote['messages'])): ?>
                            <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-[10px] text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200">
                                <?php echo e($cartDeliveryQuote['messages'][0]); ?>

                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="border-t border-gray-200 dark:border-gray-800 pt-3 flex items-center justify-between text-xs font-semibold">
                    <span class="text-gray-900 dark:text-gray-50">Estimated total <span class="text-[10px] font-normal text-gray-400">(incl GST & delivery)</span></span>
                    <span class="text-gray-900 dark:text-gray-50">₹<?php echo e(number_format($cartTotalInclGst, 2)); ?></span>
                </div>

                <div class="text-[10px] text-gray-500 dark:text-gray-400">
                    Item rows show your customer-facing price mode. GST and delivery/handling fees are finalized at checkout after address selection.
                </div>

                <?php
                    $checkoutHref = $isB2BCart && \Illuminate\Support\Facades\Route::has('b2b.checkout.index')
                        ? route('b2b.checkout.index', [], false)
                        : route('checkout.index', [], false);
                ?>

                <?php if(auth()->guard()->check()): ?>
                    <?php if(auth()->user()->hasVerifiedEmail()): ?>
                        <a href="<?php echo e($checkoutHref); ?>"
                           class="w-full inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                            Proceed to checkout
                        </a>
                    <?php else: ?>
                        <a href="<?php echo e(route('verification.notice')); ?>"
                           class="w-full inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-4 py-2 text-[11px] hover:bg-gray-100 dark:hover:bg-gray-800">
                            Verify email to checkout
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?php echo e(route('login', ['redirect' => $checkoutHref])); ?>"
                       class="w-full inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                        Sign in to checkout
                    </a>
                <?php endif; ?>
            </div>

        </div>

        
        <script>
        (function () {
            const bindBulkRemove = function (formId) {
                const checkboxes = Array.from(document.querySelectorAll('[data-cart-bulk-checkbox="' + formId + '"]'));
                const selectAll = document.querySelector('[data-cart-bulk-select-all="' + formId + '"]');
                const button = document.querySelector('[data-cart-bulk-remove-button="' + formId + '"]');

                if (!checkboxes.length || !button) {
                    return;
                }

                const updateState = function () {
                    const selectedCount = checkboxes.filter((checkbox) => checkbox.checked).length;

                    button.disabled = selectedCount === 0;
                    button.textContent = selectedCount > 0
                        ? 'Remove selected (' + selectedCount + ')'
                        : 'Remove selected';

                    if (selectAll) {
                        selectAll.checked = selectedCount === checkboxes.length;
                        selectAll.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;
                    }
                };

                checkboxes.forEach((checkbox) => {
                    checkbox.addEventListener('change', updateState);
                });

                if (selectAll) {
                    selectAll.addEventListener('change', function () {
                        checkboxes.forEach((checkbox) => {
                            checkbox.checked = selectAll.checked;
                        });
                        updateState();
                    });
                }

                updateState();
            };

            bindBulkRemove('cart-bulk-remove-form');
        })();
        </script>

        <script>
        (function () {
            const LIMITED_MSG = "Limited stock of this product available";

            document.querySelectorAll('form.js-inc-form').forEach((form) => {
                form.addEventListener('submit', (e) => {
                    const max = parseFloat(form.dataset.max || '');
                    const current = parseFloat(form.dataset.current || '');
                    const step = parseFloat(form.dataset.step || '1');

                    if (Number.isFinite(max) && max > 0 && Number.isFinite(current) && Number.isFinite(step)) {
                        if ((current + step) > (max + 1e-9)) {
                            e.preventDefault();
                            if (window.BandaraToast) { BandaraToast.warning(LIMITED_MSG, 'Limited stock'); }
                        }
                    }
                });
            });

            document.querySelectorAll('form.js-qty-update').forEach((form) => {
                form.addEventListener('submit', () => {
                    const sellUnit = (form.dataset.sellUnit || 'piece').toLowerCase();
                    const min = parseFloat(form.dataset.min || '1');
                    const max = parseFloat(form.dataset.max || '');
                    const step = parseFloat(form.dataset.step || (sellUnit === 'kg' ? '0.01' : '1'));

                    const input = form.querySelector('input[name="quantity"]');
                    if (!input) return;

                    let v = parseFloat(input.value || '');
                    if (!Number.isFinite(v)) return;

                    if (sellUnit !== 'kg') {
                        v = Math.round(v);
                        if (v < 1) v = 1;
                    } else {
                        v = Math.round(v * 100) / 100;
                        if (v < min) v = min;
                        if (Number.isFinite(step) && step > 0) {
                            v = Math.round(v / step) * step;
                            v = Math.round(v * 100) / 100;
                        }
                    }

                    if (Number.isFinite(max) && max >= 0 && v > max) {
                        if (window.BandaraToast) { BandaraToast.warning(LIMITED_MSG, 'Limited stock'); }
                        v = max;
                    }

                    input.value = String(v);
                });
            });
        })();
        </script>

    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.customer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/customer/cart/index.blade.php ENDPATH**/ ?>