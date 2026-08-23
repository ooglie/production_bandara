<?php if($homeProductShowcase->isNotEmpty()): ?>
    <section class="space-y-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <?php if($section->eyebrow): ?>
                    <p class="text-[11px] uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400"><?php echo e($section->eyebrow); ?></p>
                <?php endif; ?>
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-50"><?php echo e($section->title ?: 'Popular frozen picks'); ?></h2>
                <?php if($section->subtitle): ?>
                    <p class="mt-1 max-w-2xl text-sm text-gray-600 dark:text-gray-300"><?php echo e($section->subtitle); ?></p>
                <?php endif; ?>
            </div>
            <?php if($section->cta_text && $section->cta_url): ?>
                <a href="<?php echo e(url($section->cta_url)); ?>" class="text-[12px] font-medium text-gray-700 dark:text-gray-200 hover:underline"><?php echo e($section->cta_text); ?></a>
            <?php endif; ?>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <?php $__currentLoopData = $homeProductShowcase; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php echo $__env->make('partials.home_cards.product_card', [
                    'product' => $product,
                    'productUrl' => $productUrl($product),
                    'cartAddUrl' => $cartAddUrl,
                    'wishlistToggleUrl' => $wishlistToggleUrl,
                    'wishlistUrl' => $wishlistUrl,
                    'loginUrl' => $loginUrl,
                    'flagEmoji' => $flagEmoji,
                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </section>
<?php endif; ?>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/home/sections/product-showcase.blade.php ENDPATH**/ ?>