<section id="home-categories" id="categories" class="space-y-3">
    <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50"><?php echo e($section->title ?: 'Browse by category'); ?></h2>
        <?php if($section->cta_text && $section->cta_url): ?>
            <a href="<?php echo e(url($section->cta_url)); ?>" class="text-[11px] text-gray-600 dark:text-gray-300 hover:underline"><?php echo e($section->cta_text); ?></a>
        <?php endif; ?>
    </div>

    <?php if($topCategories->isEmpty()): ?>
        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 text-[12px] text-gray-500 dark:text-gray-400">
            No categories yet. Add some in admin to show them here.
        </div>
    <?php else: ?>
        <div class="grid grid-cols-4 sm:grid-cols-6 lg:grid-cols-8 gap-3">
            <?php $__currentLoopData = $topCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e($categoryUrl($cat)); ?>" class="group rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-3 hover:bg-gray-50 dark:hover:bg-gray-900/60 transition">
                    <div class="aspect-square rounded-sm bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-lg font-semibold text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-200">
                        <?php echo e(mb_substr($cat->name, 0, 1)); ?>

                    </div>
                    <div class="mt-2 text-[11px] font-medium text-gray-800 dark:text-gray-100 line-clamp-2"><?php echo e($cat->name); ?></div>
                    <?php if($section->getSetting('show_counts', true)): ?>
                        <div class="mt-1 text-[10px] text-gray-400"><?php echo e($cat->products_count); ?> products</div>
                    <?php endif; ?>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>
</section>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/home/sections/categories.blade.php ENDPATH**/ ?>