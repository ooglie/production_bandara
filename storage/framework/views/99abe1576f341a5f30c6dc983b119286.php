<?php
    $pieceSelector = $product->piece_selector ?? ['enabled' => false];
    $hasPieceSelector = (bool) data_get($pieceSelector, 'enabled', false);
    $bands = data_get($pieceSelector, 'bands', []);

    $quote = app(\App\Services\PricingService::class)->quote(auth()->user(), $product);
    $unitEffectivePrice = (float) ($quote['price'] ?? 0);
    $unitBasePrice = (float) ($quote['compare_at_price'] ?? $product->base_price ?? 0);

    $pricingUnit = strtolower((string) (($product->sell_unit ?? 'piece') === 'kg' ? 'kg' : 'pack'));
    $weightMultiplier = 1.0;
    if (! $hasPieceSelector && $pricingUnit === 'kg' && (float) ($product->product_weight ?? 0) > 0) {
        // Fixed/catchweight product sold as one physical unit: display total price for that unit.
        $weightMultiplier = round((float) $product->product_weight, 3);
    }

    $effectivePrice = round($unitEffectivePrice * $weightMultiplier, 2);
    $basePrice = round($unitBasePrice * $weightMultiplier, 2);
    $showsWeightedTotal = ! $hasPieceSelector && $pricingUnit === 'kg' && $weightMultiplier > 1;
    $displayMrp = isset($mrp) && $mrp !== null ? round((float) $mrp * $weightMultiplier, 2) : null;
    $displayEffectiveForSavings = isset($effective) ? round((float) $effective * $weightMultiplier, 2) : $effectivePrice;
    $displayBaseForSavings = isset($base) ? round((float) $base * $weightMultiplier, 2) : $basePrice;
    $isB2BPrice = ($quote['customer_type'] ?? 'b2c') === 'b2b';
    $isSpecialPrice = !$isB2BPrice && (bool) ($quote['is_special'] ?? false) && $effectivePrice > 0 && $basePrice > $effectivePrice;
    $moq = (float) ($quote['moq'] ?? 1);
    $priceTaxLabel = ($quote['display_price_includes_gst'] ?? false) ? 'incl GST' : 'excl GST';
?>

<?php if($hasPieceSelector): ?>
    <div class="space-y-2" data-piece-range-card="<?php echo e($product->id); ?>">
        <div class="space-y-1">
            <div class="text-[11px] font-medium text-gray-900 dark:text-gray-50">
                Variable weight
            </div>
            <div class="text-[10px] text-gray-500 dark:text-gray-400">
                Choose a slab size range to continue.
            </div>
        </div>

        <?php if(!empty($bands)): ?>
            <div>
                <select
                    class="piece-range-select w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2.5 py-2 text-[11px] text-gray-700 dark:text-gray-200"
                    data-product-url="<?php echo e(route('product.show', $product)); ?>"
                >
                    <?php $__currentLoopData = $bands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $band): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option
                            value="<?php echo e($band['key']); ?>"
                            data-label="<?php echo e($band['label']); ?>"
                            data-url="<?php echo e(route('product.show', $product)); ?>?band=<?php echo e(urlencode($band['key'])); ?>#piece-selector-root"
                            data-selected-text="View <?php echo e($band['label']); ?>"
                            <?php if($loop->first): echo 'selected'; endif; ?>
                        >
                            <?php echo e($band['label']); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <a
                href="<?php echo e(route('product.show', $product)); ?>?band=<?php echo e(urlencode($bands[0]['key'])); ?>#piece-selector-root"
                class="piece-range-link inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-2 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800"
            >
                View <?php echo e($bands[0]['label']); ?>

            </a>
        <?php else: ?>
            <span class="inline-flex items-center justify-center rounded-sm border border-gray-200 dark:border-gray-700 px-3 py-2 text-[11px] text-gray-400">
                Out of stock
            </span>
        <?php endif; ?>
    </div>

    <?php if(!empty($bands)): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const root = document.querySelector('[data-piece-range-card="<?php echo e($product->id); ?>"]');
            if (!root || root.dataset.bound === 'true') return;
            root.dataset.bound = 'true';

            const select = root.querySelector('.piece-range-select');
            const link = root.querySelector('.piece-range-link');

            function updateRangeLink() {
                const opt = select.options[select.selectedIndex];
                const value = select.value;

                if (!value || !opt) {
                    link.href = select.dataset.productUrl + '#piece-selector-root';
                    link.textContent = 'View available slabs';
                    return;
                }

                link.href = opt.dataset.url || (select.dataset.productUrl + '#piece-selector-root');
                link.textContent = opt.dataset.selectedText || ('View ' + (opt.dataset.label || opt.textContent));
            }

            select.addEventListener('change', updateRangeLink);
            updateRangeLink();
        });
        </script>
    <?php endif; ?>
<?php else: ?>
    <div class="space-y-1">
        <?php if($isB2BPrice): ?>
            <?php if($effectivePrice > 0): ?>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-50">₹<?php echo e(number_format($effectivePrice, 2)); ?></span>
                    <span class="text-[10px] rounded-full bg-gray-100 px-2 py-0.5 text-gray-600 dark:bg-gray-800 dark:text-gray-300">B2B <?php echo e($priceTaxLabel); ?></span>
                </div>
                <?php if($moq > 1): ?>
                    <div class="text-[10px] text-gray-500 dark:text-gray-400">MOQ: <?php echo e(rtrim(rtrim(number_format($moq, 3), '0'), '.')); ?></div>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-[11px] font-medium text-amber-700 dark:text-amber-300">B2B price pending</div>
            <?php endif; ?>
        <?php elseif($isSpecialPrice): ?>
            <div class="flex items-center gap-2">
                <span class="text-sm font-semibold text-gray-900 dark:text-gray-50">₹<?php echo e(number_format($effectivePrice, 2)); ?></span>
                <span class="text-[11px] text-gray-400 line-through">₹<?php echo e(number_format($basePrice, 2)); ?></span>
            </div>
        <?php else: ?>
            <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">₹<?php echo e(number_format($effectivePrice, 2)); ?></div>
        <?php endif; ?>

        <?php if($showsWeightedTotal): ?>
            <div class="text-[10px] text-gray-500 dark:text-gray-400">
                ₹<?php echo e(number_format($unitEffectivePrice, 2)); ?>/kg × <?php echo e(rtrim(rtrim(number_format($weightMultiplier, 3), '0'), '.')); ?> kg
            </div>
        <?php endif; ?>
    </div>

    <?php if($displayMrp !== null && $displayMrp > 0 && $displayMrp > $displayEffectiveForSavings): ?>
        <div class="flex items-center gap-2">
            <span class="text-[11px] text-red-600 line-through">
                ₹<?php echo e(number_format($displayMrp, 2)); ?>

            </span>

            <?php
                $offPct = $displayMrp > 0 ? (($displayMrp - $displayEffectiveForSavings) / $displayMrp) * 100 : 0;
            ?>

            <?php if($offPct > 0.5): ?>
                <span class="text-[10px] font-semibold text-green-700 dark:text-green-300">
                    <?php echo e(number_format($offPct, 0)); ?>% OFF
                </span>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php if(isset($effective, $base) && $product->is_special && $displayEffectiveForSavings < $displayBaseForSavings): ?>
            <span class="text-[11px] text-gray-400 line-through">
                ₹<?php echo e(number_format($displayBaseForSavings, 2)); ?>

            </span>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/partials/_shop_price_or_range.blade.php ENDPATH**/ ?>