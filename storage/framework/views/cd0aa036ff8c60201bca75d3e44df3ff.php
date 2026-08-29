<?php $__env->startSection('title', $chef->display_name.' | Bandara Kitchen'); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $organisation = $chef->publicOrganisationLine();
        $location = collect([$chef->city, $chef->country])->filter()->implode(' · ');
        $gallery = $chef->galleryImageUrls();
        $professionalLinks = collect([
            'Website' => $chef->website_url,
            'Instagram' => $chef->instagram_url,
            'LinkedIn' => $chef->linkedin_url,
        ])->filter();
        $hasSignatureDish = filled($chef->signature_dish_name)
            || filled($chef->signature_dishes)
            || filled($chef->signature_dish_image_path);
    ?>

    <main>
        <section class="mx-auto w-full max-w-7xl px-4 pb-12 pt-10 sm:px-6 sm:pb-16 sm:pt-14 lg:px-8">
            <nav aria-label="Breadcrumb" class="text-xs uppercase tracking-[0.14em] text-slate-500 dark:text-slate-500">
                <a href="<?php echo e(url('/')); ?>" class="transition hover:text-slate-900 dark:hover:text-slate-200">Home</a>
                <span aria-hidden="true" class="px-2">/</span>
                <a href="<?php echo e(route('kitchen.index')); ?>" class="transition hover:text-slate-900 dark:hover:text-slate-200">Bandara Kitchen</a>
                <span aria-hidden="true" class="px-2">/</span>
                <a href="<?php echo e(route('kitchen.chefs.index')); ?>" class="transition hover:text-slate-900 dark:hover:text-slate-200">Chefs</a>
                <span aria-hidden="true" class="px-2">/</span>
                <span aria-current="page"><?php echo e($chef->display_name); ?></span>
            </nav>

            <div class="mt-10 grid items-center gap-8 lg:grid-cols-[minmax(0,0.78fr)_minmax(0,1.22fr)] lg:gap-16">
                <div class="mx-auto aspect-[4/5] w-full max-w-lg overflow-hidden rounded-xl bg-slate-100 shadow-sm dark:bg-slate-900">
                    <?php if($chef->portraitUrl()): ?>
                        <img src="<?php echo e($chef->portraitUrl()); ?>" alt="Portrait of <?php echo e($chef->display_name); ?>" class="h-full w-full object-cover" fetchpriority="high">
                    <?php else: ?>
                        <div class="flex h-full items-center justify-center text-5xl font-light tracking-[0.18em] text-slate-400 dark:text-slate-600"><?php echo e(\App\Support\BandaraKitchen::initials($chef->display_name)); ?></div>
                    <?php endif; ?>
                </div>

                <div class="py-2 lg:pr-8">
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-500 dark:text-slate-500">Meet the Chef</p>
                    <h1 class="mt-4 text-4xl font-light tracking-tight text-slate-950 sm:text-5xl lg:text-6xl dark:text-white"><?php echo e($chef->display_name); ?></h1>
                    <p class="mt-4 text-sm leading-7 text-slate-600 dark:text-slate-400">
                        <?php echo e($chef->professional_title); ?>

                        <?php if($organisation): ?><span aria-hidden="true"> · </span><?php echo e($organisation); ?><?php endif; ?>
                        <?php if($location): ?><span aria-hidden="true"> · </span><?php echo e($location); ?><?php endif; ?>
                    </p>

                    <?php if($chef->short_intro): ?>
                        <p class="mt-8 max-w-2xl text-lg font-light leading-8 text-slate-700 sm:text-xl sm:leading-9 dark:text-slate-300"><?php echo e($chef->short_intro); ?></p>
                    <?php endif; ?>

                    <?php if($professionalLinks->isNotEmpty()): ?>
                        <div class="mt-8 flex flex-wrap gap-x-6 gap-y-3 border-t border-slate-200 pt-6 dark:border-slate-800" aria-label="Professional links">
                            <?php $__currentLoopData = $professionalLinks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $url): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <a href="<?php echo e($url); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm text-slate-700 transition hover:text-slate-950 dark:text-slate-300 dark:hover:text-white">
                                    <?php echo e($label); ?> <span aria-hidden="true">↗</span>
                                </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="mx-auto w-full max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8" aria-labelledby="chef-story-title">
            <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(20rem,0.72fr)] lg:items-start lg:gap-16">
                <div class="max-w-3xl">
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-500 dark:text-slate-500">Bandara Kitchen</p>
                    <h2 id="chef-story-title" class="mt-3 text-3xl font-light tracking-tight text-slate-950 sm:text-4xl dark:text-white">The Chef’s Story</h2>
                    <div class="mt-7 whitespace-pre-line text-base font-light leading-8 text-slate-700 sm:text-lg sm:leading-9 dark:text-slate-300"><?php echo e($chef->biography); ?></div>
                </div>

                <?php if($chef->workingImageUrl()): ?>
                    <figure class="overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-900">
                        <div class="aspect-[4/3] overflow-hidden">
                            <img src="<?php echo e($chef->workingImageUrl()); ?>" alt="<?php echo e($chef->display_name); ?> at work" class="h-full w-full object-cover" loading="lazy">
                        </div>
                        <?php if($chef->photographer_credit): ?>
                            <figcaption class="px-4 py-3 text-xs text-slate-500 dark:text-slate-500">Photography: <?php echo e($chef->photographer_credit); ?></figcaption>
                        <?php endif; ?>
                    </figure>
                <?php endif; ?>
            </div>
        </section>

        <?php if($hasSignatureDish): ?>
            <section class="mx-auto w-full max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8" aria-labelledby="signature-dish-title">
                <div class="border-y border-slate-200 py-10 sm:py-14 dark:border-slate-800">
                    <div class="grid items-center gap-8 lg:grid-cols-[minmax(0,1.12fr)_minmax(0,0.88fr)] lg:gap-14">
                        <?php if($chef->signatureDishImageUrl()): ?>
                            <div class="aspect-[4/3] overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-900">
                                <img src="<?php echo e($chef->signatureDishImageUrl()); ?>" alt="<?php echo e($chef->signature_dish_name ?: 'Signature dish by '.$chef->display_name); ?>" class="h-full w-full object-cover" loading="lazy">
                            </div>
                        <?php endif; ?>

                        <div class="<?php echo \Illuminate\Support\Arr::toCssClasses(['lg:col-span-2 max-w-3xl' => ! $chef->signatureDishImageUrl()]); ?>">
                            <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-500 dark:text-slate-500">Signature Dish</p>
                            <?php if($chef->signature_dish_name): ?>
                                <h2 id="signature-dish-title" class="mt-3 text-3xl font-light leading-tight tracking-tight text-slate-950 sm:text-4xl dark:text-white"><?php echo e($chef->signature_dish_name); ?></h2>
                            <?php else: ?>
                                <h2 id="signature-dish-title" class="sr-only">Signature dish</h2>
                            <?php endif; ?>
                            <?php if($chef->signature_dishes): ?>
                                <div class="mt-6 whitespace-pre-line text-base font-light leading-8 text-slate-700 dark:text-slate-300"><?php echo e($chef->signature_dishes); ?></div>
                            <?php endif; ?>
                            <p class="mt-6 text-xs uppercase tracking-[0.14em] text-slate-500 dark:text-slate-500">Selected by <?php echo e($chef->display_name); ?></p>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if($gallery !== []): ?>
            <section class="mx-auto w-full max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8" aria-labelledby="chef-gallery-title">
                <div class="mb-7">
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-500 dark:text-slate-500">In the Kitchen</p>
                    <h2 id="chef-gallery-title" class="mt-2 text-3xl font-light tracking-tight text-slate-950 dark:text-white">A closer look</h2>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <?php $__currentLoopData = $gallery; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $imageUrl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="aspect-[4/3] overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-900">
                            <img src="<?php echo e($imageUrl); ?>" alt="<?php echo e($chef->display_name); ?> kitchen photograph" class="h-full w-full object-cover transition duration-500 hover:scale-[1.02]" loading="lazy">
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <?php if($chef->photographer_credit && ! $chef->workingImageUrl()): ?>
                    <p class="mt-4 text-xs text-slate-500 dark:text-slate-500">Photography: <?php echo e($chef->photographer_credit); ?></p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if($relatedChefs->isNotEmpty()): ?>
            <section class="mx-auto w-full max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8" aria-labelledby="other-chefs-title">
                <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-500 dark:text-slate-500">Bandara Kitchen</p>
                        <h2 id="other-chefs-title" class="mt-2 text-3xl font-light tracking-tight text-slate-950 dark:text-white">Meet more Chefs</h2>
                    </div>
                    <a href="<?php echo e(route('kitchen.chefs.index')); ?>" class="inline-flex items-center gap-2 text-sm font-medium text-slate-900 dark:text-slate-100">All Chefs <span aria-hidden="true">→</span></a>
                </div>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <?php $__currentLoopData = $relatedChefs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $relatedChef): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php echo $__env->make('storefront.kitchen.partials.chef-card', ['chef' => $relatedChef], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </section>
        <?php else: ?>
            <div class="mx-auto w-full max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
                <a href="<?php echo e(route('kitchen.chefs.index')); ?>" class="inline-flex items-center gap-2 text-sm font-medium text-slate-900 dark:text-slate-100"><span aria-hidden="true">←</span> All Chefs</a>
            </div>
        <?php endif; ?>
    </main>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.customer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/storefront/kitchen/chefs/show.blade.php ENDPATH**/ ?>