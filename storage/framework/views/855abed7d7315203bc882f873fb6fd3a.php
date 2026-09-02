
<?php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Facades\Route;

    $productUrl = $productUrl ?? route('product.show', $product);
    $showProductSelectors = $showProductSelectors ?? true;
    $showStockBadges = $showStockBadges ?? true;
    $showCountryOfOrigin = $showCountryOfOrigin ?? true;
    $priceView = $priceView ?? null;
    $priceViewData = $priceViewData ?? [];
    $actionsView = $actionsView ?? 'partials.home_cards.wishlist_cart_view';
    $actionsViewData = $actionsViewData ?? [];

    $cartAddUrl = $cartAddUrl ?? null;
    $wishlistToggleUrl = $wishlistToggleUrl ?? null;
    $wishlistUrl = $wishlistUrl ?? null;
    $loginUrl = $loginUrl ?? null;

    $pieceSelector = $product->piece_selector ?? ['enabled' => false];
    $hasPieceSelector = (bool) $showProductSelectors && (bool) data_get($pieceSelector, 'enabled', false);
    $pieceBands = data_get($pieceSelector, 'bands', []);

    $isVariable = (string)($product->type ?? 'simple') === 'variable';
    $inStock = $hasPieceSelector
        ? true
        : (!(bool)($product->manage_stock ?? false) || ((float)($product->stock_quantity ?? 0) > 0));

    $coCode = strtoupper(trim((string)($product->country_of_origin ?? '')));
    $flag = isset($flagEmoji) ? $flagEmoji($coCode) : null;
    $country_name = $coCode ? \Locale::getDisplayRegion('-' . $coCode, app()->getLocale()) : null;

    $cardQuote = app(\App\Services\PricingService::class)->quote(auth()->user(), $product);
    $effective = (float)($cardQuote['price'] ?? ($product->effective_price ?? 0));
    $base      = (float)($cardQuote['compare_at_price'] ?? $effective);
    $mrp       = $product->mrp_price !== null ? (float)$product->mrp_price : null;
    if ($mrp !== null && ($cardQuote['display_price_includes_gst'] ?? true)) {
        $gstRate = app(\App\Services\GstRateService::class)->rateForProduct($product, auth()->user());
        if ($gstRate > 0) {
            $mrp = round($mrp * (1 + ($gstRate / 100)), 2);
        }
    }

    $variantCount = isset($product->variants_count)
        ? (int) $product->variants_count
        : ((method_exists($product, 'variants') && $product->relationLoaded('variants')) ? $product->variants->count() : 0);

    $hasVariants = (bool) $showProductSelectors && !$hasPieceSelector && ($isVariable || $variantCount > 0);

    $variantOptionsUrl = Route::has('product.variants.options')
        ? route('product.variants.options', ['product' => $product->id])
        : null;

    $chip  = "inline-flex items-center rounded-sm px-2 py-0.5 text-[10px]";

    $actionsData = array_merge([
        'product' => $product,
        'productUrl' => $productUrl,
        'cartAddUrl' => $cartAddUrl,
        'wishlistToggleUrl' => $wishlistToggleUrl,
        'wishlistUrl' => $wishlistUrl,
        'loginUrl' => $loginUrl,
        'isVariable' => $isVariable,
        'hasVariants' => $hasVariants,
        'variantOptionsUrl' => $variantOptionsUrl,
        'inStock' => $inStock,
    ], $actionsViewData);

    $priceData = array_merge([
        'product' => $product,
        'productUrl' => $productUrl,
        'effective' => $effective,
        'base' => $base,
        'mrp' => $mrp,
    ], $priceViewData);
?>

<div data-bandara-phase1-product-card class="w-full max-w-sm justify-self-start js-product-card">
    <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-3 flex flex-col h-full">

        
        <div class="relative aspect-[4/3] rounded-sm bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-3 overflow-hidden">
            <a href="<?php echo e($productUrl); ?>" title="View details" class="block h-full w-full">
                <?php if($product->primary_image): ?>
                    <img data-bandara-phase1-product-image
                        src="<?php echo e(Storage::disk(config('media.public_disk', 'public'))->url($product->primary_image)); ?>"
                        alt="<?php echo e($product->name); ?>"
                        class="object-cover w-full h-full group-hover:scale-[1.02] transition-transform duration-300"
                        loading="lazy"
                    >
                <?php else: ?>
                    <div class="h-full w-full flex items-center justify-center">
                        <span class="text-[11px] text-gray-400 dark:text-gray-500">No image</span>
                    </div>
                <?php endif; ?>
            </a>

            
            <div class="absolute top-2 left-2 flex flex-wrap gap-1">
                <?php if($product->is_featured): ?>
                    <span class="<?php echo e($chip); ?> bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900">Featured</span>
                <?php endif; ?>
                <?php if($product->is_new): ?>
                    <span class="<?php echo e($chip); ?> bg-sky-50 text-sky-700 dark:bg-sky-900/30 dark:text-sky-200">New</span>
                <?php endif; ?>
                <?php if($product->is_special): ?>
                    <span class="<?php echo e($chip); ?> bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-200">Special</span>
                <?php endif; ?>
                <?php if($showStockBadges && !$hasVariants && !$hasPieceSelector && (bool)($product->manage_stock ?? false) && !$inStock): ?>
                    <span class="<?php echo e($chip); ?> bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-200">Out of stock</span>
                <?php endif; ?>
            </div>
        </div>

        
        <div class="flex-1 flex flex-col">
            <p class="text-[13px] font-semibold text-gray-900 dark:text-gray-50 leading-snug line-clamp-2">
                <a href="<?php echo e($productUrl); ?>" title="View details" class="hover:underline">
                    <?php echo e($product->name); ?>

                </a>
            </p>

            <?php if($product->short_description): ?>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400 line-clamp-2">
                    <?php echo e($product->short_description); ?>

                </p>
            <?php endif; ?>

            <div class="mt-3"></div>

            
            <div class="mt-auto flex items-end justify-between gap-3">
                <div class="min-w-0">
                    <?php if($priceView): ?>
                        <?php echo $__env->make($priceView, $priceData, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php elseif($hasPieceSelector && count($pieceBands)): ?>
                        <?php
                            $pieceMin = (float) collect($pieceBands)->min('price_min');
                            $pieceMax = (float) collect($pieceBands)->max('price_max');
                        ?>
                        <div class="space-y-0.5 leading-tight">
                            <div class="text-[12px] font-semibold text-gray-900 dark:text-gray-50">Choose slab</div>
                            <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                <?php echo e(count($pieceBands)); ?> size <?php echo e(count($pieceBands) === 1 ? 'range' : 'ranges'); ?>

                                <?php if($pieceMin > 0): ?>
                                    · ₹<?php echo e(number_format($pieceMin, 2)); ?>

                                    <?php if($pieceMax > $pieceMin): ?>
                                        – ₹<?php echo e(number_format($pieceMax, 2)); ?>

                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php elseif($hasVariants && $variantOptionsUrl): ?>
                        <div class="space-y-0.5 leading-tight">
                            <div class="js-variant-price-summary text-[12px] font-semibold text-gray-900 dark:text-gray-50">Choose pack</div>
                            <div class="js-variant-count-summary text-[10px] text-gray-500 dark:text-gray-400">Options loading…</div>
                        </div>
                    <?php else: ?>
                        <div class="flex flex-col items-start leading-tight">
                            <span class="text-[14px] font-semibold text-gray-900 dark:text-gray-50">
                                <?php echo $__env->make('partials._shop_price_or_range', ['product' => $product], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                
                <?php echo $__env->make($actionsView, $actionsData, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>

            <?php if($showCountryOfOrigin): ?>
                <div class="flex flex-col items-end pt-4">
                    <?php if($flag): ?>
                        <div class="inline-flex gap-1 rounded-sm bg-white/80 dark:bg-gray-950/70 backdrop-blur text-[10px] text-gray-700 dark:text-gray-200">
                            Country of origin :: <?php echo e($country_name); ?>

                            <span class="text-[14px] leading-none"><?php echo $flag; ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/partials/home_cards/product_card.blade.php ENDPATH**/ ?>