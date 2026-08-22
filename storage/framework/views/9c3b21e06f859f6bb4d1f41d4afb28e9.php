<section id="featured" class="space-y-3">
    <?php if($featuredProducts->isEmpty()): ?>
        
    <?php else: ?>
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                Featured products
            </h2>
            
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <?php $__currentLoopData = $featuredProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php echo $__env->make('partials.home_cards.product_card', ['product' => $product], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>
</section><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/partials/home_cards/features_product.blade.php ENDPATH**/ ?>