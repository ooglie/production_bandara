<?php
    $pieceSelector = $product->piece_selector ?? ['enabled' => false];
    $hasPieceSelector = (bool) data_get($pieceSelector, 'enabled', false);
    $pieceBands = data_get($pieceSelector, 'bands', []);
    $hasMultipleBands = is_array($pieceBands) && count($pieceBands) > 1;
    $hasVariants = (bool) ($hasVariants ?? false);
    $variantOptionsUrl = $variantOptionsUrl ?? null;
?>


<?php if(auth()->check()): ?>
    <?php if($wishlistToggleUrl): ?>
        <form method="POST" action="<?php echo e($wishlistToggleUrl); ?>" class="js-wishlist-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="product_id" value="<?php echo e($product->id); ?>">
            <input type="hidden" name="product_variant_id" class="js-variant-input" value="">
            <button
                type="submit"
                title="Add to wishlist"
                class="inline-flex items-center justify-center w-9 h-9 rounded-sm
                    border border-gray-200 dark:border-gray-700
                    bg-white/80 dark:bg-gray-950/70 backdrop-blur
                    text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-900
                    disabled:opacity-40 cursor-pointer"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21 8.25c0-2.485-2.099-4.5-4.687-4.5-1.935 0-3.597 1.126-4.313 2.733-.716-1.607-2.378-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
                </svg>
            </button>
        </form>
    <?php elseif($wishlistUrl): ?>
        <a href="<?php echo e($wishlistUrl); ?>"
            title="Wishlist"
            class="inline-flex items-center justify-center w-9 h-9 rounded-sm
                    border border-gray-200 dark:border-gray-700
                    bg-white/80 dark:bg-gray-950/70 backdrop-blur
                    text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-900
                    disabled:opacity-40"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 8.25c0-2.485-2.099-4.5-4.687-4.5-1.935 0-3.597 1.126-4.313 2.733-.716-1.607-2.378-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
            </svg>
        </a>
    <?php endif; ?>
<?php else: ?>
    <?php if($loginUrl): ?>
        <a href="<?php echo e($loginUrl); ?>"
            title="Login to use wishlist"
            class="inline-flex items-center justify-center w-9 h-9 rounded-sm
                    border border-gray-200 dark:border-gray-700
                    bg-white/80 dark:bg-gray-950/70 backdrop-blur
                    text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-900 cursor-pointer"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 8.25c0-2.485-2.099-4.5-4.687-4.5-1.935 0-3.597 1.126-4.313 2.733-.716-1.607-2.378-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
            </svg>
        </a>
    <?php endif; ?>
<?php endif; ?>


<?php if($hasPieceSelector): ?>
    <?php if($inStock): ?>
        <?php if(!empty($pieceBands)): ?>
            <details class="relative js-card-option-menu js-slab-band-menu">
                <summary
                    title="Choose slab"
                    class="list-none inline-flex items-center justify-center w-9 h-9 rounded-sm
                           border border-gray-200 dark:border-gray-700
                           bg-white/80 dark:bg-gray-950/70 backdrop-blur
                           text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-900
                           cursor-pointer"
                    style="list-style: none;"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                         class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M4.5 7.5h15M6 12h12M8.25 16.5h7.5" />
                    </svg>
                </summary>

                <div class="absolute right-0 z-50 mt-2 w-72 max-h-96 overflow-y-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-lg p-2">
                    <div class="px-2 pb-1 text-[10px] uppercase tracking-wide text-gray-400">
                        Choose slab / piece
                    </div>

                    <div class="space-y-2">
                        <?php $__currentLoopData = $pieceBands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $band): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $choices = collect($band['choices'] ?? []);
                            ?>

                            <?php if($choices->isNotEmpty()): ?>
                                <div class="rounded-lg border border-gray-100 dark:border-gray-800 p-1">
                                    <div class="px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400">
                                        <?php echo e($band['label']); ?> · <?php echo e($band['count']); ?> available
                                    </div>

                                    <div class="space-y-1">
                                        <?php $__currentLoopData = $choices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $choice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php if($cartAddUrl): ?>
                                                <form method="POST" action="<?php echo e($cartAddUrl); ?>" class="block">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="product_id" value="<?php echo e($product->id); ?>">
                                                    <input type="hidden" name="piece_weight_kg" value="<?php echo e(number_format((float) $choice['weight_kg'], 3, '.', '')); ?>">
                                                    <input type="hidden" name="quantity" value="1">

                                                    <button type="submit"
                                                            class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-800">
                                                        <span class="min-w-0">
                                                            <span class="block text-[12px] font-medium text-gray-900 dark:text-gray-50">
                                                                <?php echo e($choice['weight_label']); ?>

                                                                <?php if((int) ($choice['count'] ?? 1) > 1): ?>
                                                                    <span class="text-[10px] text-gray-400">× <?php echo e((int) $choice['count']); ?></span>
                                                                <?php endif; ?>
                                                            </span>
                                                            <span class="mt-0.5 block text-[10px] text-gray-500 dark:text-gray-400">
                                                                Add this slab
                                                            </span>
                                                        </span>
                                                        <span class="shrink-0 text-[12px] font-semibold text-gray-900 dark:text-gray-50">
                                                            ₹<?php echo e(number_format((float) $choice['price'], 2)); ?>

                                                        </span>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <a href="<?php echo e(route('product.show', $product)); ?>?band=<?php echo e(urlencode($band['key'])); ?>&piece_weight_kg=<?php echo e(urlencode(number_format((float) $choice['weight_kg'], 3, '.', ''))); ?>#piece-selector-root"
                                                   class="flex items-center justify-between gap-3 rounded-lg px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-800">
                                                    <span class="min-w-0">
                                                        <span class="block text-[12px] font-medium text-gray-900 dark:text-gray-50">
                                                            <?php echo e($choice['weight_label']); ?>

                                                            <?php if((int) ($choice['count'] ?? 1) > 1): ?>
                                                                <span class="text-[10px] text-gray-400">× <?php echo e((int) $choice['count']); ?></span>
                                                            <?php endif; ?>
                                                        </span>
                                                        <span class="mt-0.5 block text-[10px] text-gray-500 dark:text-gray-400">
                                                            Select exact size
                                                        </span>
                                                    </span>
                                                    <span class="shrink-0 text-[12px] font-semibold text-gray-900 dark:text-gray-50">
                                                        ₹<?php echo e(number_format((float) $choice['price'], 2)); ?>

                                                    </span>
                                                </a>
                                            <?php endif; ?>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <a href="<?php echo e(route('product.show', $product)); ?>?band=<?php echo e(urlencode($band['key'])); ?>#piece-selector-root"
                                   class="block rounded-lg px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-800">
                                    <div class="text-[12px] font-medium text-gray-900 dark:text-gray-50">
                                        <?php echo e($band['label']); ?>

                                    </div>
                                    <div class="mt-0.5 text-[10px] text-gray-500 dark:text-gray-400">
                                        <?php echo e($band['count']); ?> available ·
                                        ₹<?php echo e(number_format((float) $band['price_min'], 2)); ?>

                                        <?php if((float) $band['price_max'] > (float) $band['price_min']): ?>
                                            – ₹<?php echo e(number_format((float) $band['price_max'], 2)); ?>

                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </details>
        <?php else: ?>
            <a href="<?php echo e(route('product.show', $product)); ?>#piece-selector-root"
                title="Choose slab"
                class="inline-flex items-center justify-center w-9 h-9 rounded-sm
                        border border-gray-200 dark:border-gray-700
                        bg-white/80 dark:bg-gray-950/70 backdrop-blur
                        text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-900 cursor-pointer"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                     class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M4.5 7.5h15M6 12h12M8.25 16.5h7.5" />
                </svg>
            </a>
        <?php endif; ?>
    <?php else: ?>
        <button
            type="button"
            title="Out of stock"
            disabled
            class="inline-flex items-center justify-center w-9 h-9 rounded-sm
                   border border-gray-200 dark:border-gray-700
                   bg-white/80 dark:bg-gray-950/70 backdrop-blur
                   text-gray-700 dark:text-gray-200 disabled:opacity-40"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                 class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M4.5 7.5h15M6 12h12M8.25 16.5h7.5" />
            </svg>
        </button>
    <?php endif; ?>
<?php elseif($hasVariants && $variantOptionsUrl && $cartAddUrl): ?>
    <details
        class="relative js-card-option-menu js-variant-option-menu"
        data-url="<?php echo e($variantOptionsUrl); ?>"
        data-cart-url="<?php echo e($cartAddUrl); ?>"
        data-product-id="<?php echo e($product->id); ?>"
        data-csrf="<?php echo e(csrf_token()); ?>"
    >
        <summary
            title="Choose pack"
            class="list-none inline-flex items-center justify-center w-9 h-9 rounded-sm
                   border border-gray-200 dark:border-gray-700
                   bg-white/80 dark:bg-gray-950/70 backdrop-blur
                   text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-900
                   cursor-pointer"
            style="list-style: none;"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.25 3h1.5l1.5 12h13.5l1.5-9H6.75" />
                <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 20.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm10.5 0a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" />
            </svg>
        </summary>

        <div class="absolute right-0 z-50 mt-2 w-64 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-lg p-2">
            <div class="px-2 pb-1 text-[10px] uppercase tracking-wide text-gray-400">
                Choose pack
            </div>
            <div class="js-variant-options-menu space-y-1">
                <div class="rounded-lg px-3 py-2 text-[11px] text-gray-500 dark:text-gray-400">
                    Loading options…
                </div>
            </div>
        </div>
    </details>
<?php elseif($cartAddUrl): ?>
    <form method="POST" action="<?php echo e($cartAddUrl); ?>" class="js-cart-form">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="product_id" value="<?php echo e($product->id); ?>">
        <input type="hidden" name="product_variant_id" class="js-variant-input" value="">
        <input type="hidden" name="quantity" value="1">

        <button
            type="submit"
            title="<?php echo e($inStock ? 'Add to cart' : 'Out of stock'); ?>"
            <?php if(!$inStock): echo 'disabled'; endif; ?>
            class="js-cart-btn inline-flex items-center justify-center w-9 h-9 rounded-sm
                border border-gray-200 dark:border-gray-700
                bg-white/80 dark:bg-gray-950/70 backdrop-blur
                text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-900
                disabled:opacity-40 cursor-pointer"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.25 3h1.5l1.5 12h13.5l1.5-9H6.75" />
                <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 20.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm10.5 0a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" />
            </svg>
        </button>
    </form>
<?php else: ?>
    <a href="<?php echo e(route('product.show', $product)); ?>"
        title="View product"
        class="inline-flex items-center justify-center w-9 h-9 rounded-sm
                border border-gray-200 dark:border-gray-700
                bg-white/80 dark:bg-gray-950/70 backdrop-blur
                text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-900 cursor-pointer"
    >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round"
                    d="M2.25 3h1.5l1.5 12h13.5l1.5-9H6.75" />
            <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 20.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm10.5 0a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" />
        </svg>
    </a>
<?php endif; ?>


<a href="<?php echo e(route('product.show', $product)); ?>"
   title="View details"
   class="inline-flex items-center justify-center w-9 h-9 rounded-sm
          border border-gray-200 dark:border-gray-700
          bg-white/80 dark:bg-gray-950/70 backdrop-blur
          text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-900"
>
    <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
        <path stroke="currentColor" stroke-width="1" d="M21 12c0 1.2-4.03 6-9 6s-9-4.8-9-6c0-1.2 4.03-6 9-6s9 4.8 9 6Z"/>
        <path stroke="currentColor" stroke-width="1" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
    </svg>
</a>

<?php if (! $__env->hasRenderedOnce('efeab393-3f12-40e5-83ca-e966a2e57d6b')): $__env->markAsRenderedOnce('efeab393-3f12-40e5-83ca-e966a2e57d6b'); ?>
    <?php $__env->startPush('scripts'); ?>
        <script>
        (function () {
            if (window.__bandaraProductCardMenuCloserBound) {
                return;
            }

            window.__bandaraProductCardMenuCloserBound = true;

            function closeOpenProductCardMenus(exceptMenu) {
                document.querySelectorAll('details.js-card-option-menu[open]').forEach(function (menu) {
                    if (menu !== exceptMenu) {
                        menu.removeAttribute('open');
                    }
                });
            }

            document.addEventListener('click', function (event) {
                var target = event.target;
                if (!target) {
                    return;
                }

                document.querySelectorAll('details.js-card-option-menu[open]').forEach(function (menu) {
                    if (!menu.contains(target)) {
                        menu.removeAttribute('open');
                    }
                });
            });

            document.addEventListener('toggle', function (event) {
                var menu = event.target;
                if (!menu || menu.tagName !== 'DETAILS' || !menu.classList || !menu.classList.contains('js-card-option-menu') || !menu.open) {
                    return;
                }

                closeOpenProductCardMenus(menu);
            }, true);

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeOpenProductCardMenus(null);
                }
            });
        })();
        </script>
    <?php $__env->stopPush(); ?>
<?php endif; ?>

<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/partials/home_cards/wishlist_cart_view.blade.php ENDPATH**/ ?>