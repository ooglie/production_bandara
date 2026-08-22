<?php
    $items = [
        ['route' => 'content.about', 'label' => 'About Us'],
        ['route' => 'content.terms', 'label' => 'Terms & Conditions'],
        ['route' => 'content.privacy', 'label' => 'Privacy Policy'],
        ['route' => 'content.help', 'label' => 'Help & FAQs'],
    ];
?>

<nav aria-label="Bandara information pages" class="border-b border-slate-200/70 bg-transparent dark:border-slate-800/80">
    <div class="max-w-6xl mx-auto px-4 py-6 space-y-6">
        <div class="mx-auto flex max-w-7xl items-center gap-5 overflow-x-auto px-4 sm:gap-7 sm:px-6 lg:px-8">
            <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php ($active = request()->routeIs($item['route'])); ?>

                <a
                    href="<?php echo e(route($item['route'])); ?>"
                    class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                        'relative shrink-0 py-3.5 text-xs font-normal tracking-[0.015em] transition-colors sm:text-[13px]',
                        'text-slate-950 after:absolute after:inset-x-0 after:bottom-0 after:h-px after:bg-slate-950 dark:text-white dark:after:bg-white' => $active,
                        'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100' => ! $active,
                    ]); ?>"
                    <?php if($active): ?> aria-current="page" <?php endif; ?>
                >
                    <?php echo e($item['label']); ?>

                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>

</nav>

<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/pages/partials/content-nav.blade.php ENDPATH**/ ?>