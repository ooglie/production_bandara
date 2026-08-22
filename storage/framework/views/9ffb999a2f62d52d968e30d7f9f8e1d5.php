<section class="space-y-4">
    <div>
        <?php if($section->eyebrow): ?><p class="text-[11px] uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400"><?php echo e($section->eyebrow); ?></p><?php endif; ?>
        <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-50"><?php echo e($section->title); ?></h2>
        <?php if($section->subtitle): ?><p class="mt-1 max-w-2xl text-sm text-gray-600 dark:text-gray-300"><?php echo e($section->subtitle); ?></p><?php endif; ?>
    </div>

    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <?php $__currentLoopData = $section->activeItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="rounded-lg border px-4 py-4 <?php echo e($item->getSetting('accent', 'bg-gray-50 border-gray-100 dark:bg-gray-900 dark:border-gray-800')); ?>">
                <?php if($item->icon): ?><div class="text-2xl"><?php echo e($item->icon); ?></div><?php endif; ?>
                <div class="mt-3 text-sm font-semibold text-gray-900 dark:text-gray-50"><?php echo e($item->title); ?></div>
                <?php if($item->description): ?><p class="mt-1 text-xs leading-relaxed text-gray-600 dark:text-gray-300"><?php echo e($item->description); ?></p><?php endif; ?>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</section>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/home/sections/trust.blade.php ENDPATH**/ ?>