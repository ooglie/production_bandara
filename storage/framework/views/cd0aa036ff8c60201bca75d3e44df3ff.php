<?php $__env->startSection('title', $chef->display_name.' | Bandara Kitchen'); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $organisation = $chef->publicOrganisationLine();
        $location = collect([$chef->city, $chef->country])
            ->filter(fn ($value) => filled($value))
            ->implode(' · ');

        $professionalMeta = collect([
            $chef->professional_title,
            $organisation,
            $location,
        ])->filter(fn ($value) => filled($value))->implode(' · ');

        $portraitUrl = $chef->portraitUrl();
        $workingImageUrl = $chef->workingImageUrl();
        $signatureDishImageUrl = $chef->signatureDishImageUrl();
        $gallery = collect($chef->galleryImageUrls())->values();
        $galleryCount = $gallery->count();

        $professionalLinks = collect([
            'Website' => $chef->website_url,
            'Instagram' => $chef->instagram_url,
            'LinkedIn' => $chef->linkedin_url,
        ])->filter(fn ($url) => filled($url));

        $hasSignatureDish = filled($chef->signature_dish_name)
            || filled($chef->signature_dishes)
            || filled($signatureDishImageUrl);
    ?>

    <div class="pb-16 sm:pb-20">
        
        <section class="mx-auto w-full max-w-6xl px-4 pt-8 sm:px-6 sm:pt-12 lg:px-8">
            <nav aria-label="Breadcrumb" class="text-xs uppercase tracking-[0.14em] text-slate-500 dark:text-slate-500">
                <a href="<?php echo e(url('/')); ?>" class="transition hover:text-slate-900 dark:hover:text-slate-200">Home</a>
                <span aria-hidden="true" class="px-2">/</span>
                <a href="<?php echo e(route('kitchen.index')); ?>" class="transition hover:text-slate-900 dark:hover:text-slate-200">Bandara Kitchen</a>
                <span aria-hidden="true" class="px-2">/</span>
                <a href="<?php echo e(route('kitchen.chefs.index')); ?>" class="transition hover:text-slate-900 dark:hover:text-slate-200">Chefs</a>
                <span aria-hidden="true" class="px-2">/</span>
                <span aria-current="page"><?php echo e($chef->display_name); ?></span>
            </nav>

            <article class="mt-7 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="grid lg:grid-cols-5">
                    <figure class="bg-slate-100 dark:bg-slate-950 lg:col-span-2">
                        <div class="aspect-[4/5] overflow-hidden">
                            <?php if($portraitUrl): ?>
                                <img
                                    src="<?php echo e($portraitUrl); ?>"
                                    alt="Portrait of <?php echo e($chef->display_name); ?>"
                                    class="h-full w-full object-cover object-center"
                                    fetchpriority="high"
                                >
                            <?php else: ?>
                                <div class="flex h-full items-center justify-center text-5xl font-light tracking-[0.18em] text-slate-400 dark:text-slate-600">
                                    <?php echo e(\App\Support\BandaraKitchen::initials($chef->display_name)); ?>

                                </div>
                            <?php endif; ?>
                        </div>
                    </figure>

                    <div class="flex flex-col justify-center px-6 py-8 sm:px-10 sm:py-12 lg:col-span-3 lg:px-14 lg:py-14">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-500 dark:text-slate-500">Meet the Chef</p>

                        <h1 class="mt-4 text-4xl font-light leading-tight tracking-tight text-slate-950 sm:text-5xl dark:text-white">
                            <?php echo e($chef->display_name); ?>

                        </h1>

                        <?php if($professionalMeta): ?>
                            <p class="mt-4 text-sm leading-7 text-slate-600 dark:text-slate-400">
                                <?php echo e($professionalMeta); ?>

                            </p>
                        <?php endif; ?>

                        <?php if($chef->short_intro): ?>
                            <p class="mt-7 max-w-2xl text-base font-light leading-8 text-slate-700 sm:text-lg sm:leading-9 dark:text-slate-300">
                                <?php echo e($chef->short_intro); ?>

                            </p>
                        <?php endif; ?>

                        <?php if($professionalLinks->isNotEmpty()): ?>
                            <div class="mt-8 flex flex-wrap gap-x-6 gap-y-3 border-t border-slate-200 pt-6 dark:border-slate-800" aria-label="Professional links">
                                <?php $__currentLoopData = $professionalLinks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $url): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <a
                                        href="<?php echo e($url); ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-2 text-sm text-slate-700 transition hover:text-slate-950 dark:text-slate-300 dark:hover:text-white"
                                    >
                                        <?php echo e($label); ?> <span aria-hidden="true">↗</span>
                                    </a>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        </section>

        
        <?php if($hasSignatureDish): ?>
            <section class="mx-auto w-full max-w-6xl px-4 pt-10 sm:px-6 sm:pt-12 lg:px-8" aria-labelledby="signature-dish-title">
                <article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="grid lg:grid-cols-5">
                        <?php if($signatureDishImageUrl): ?>
                            <figure class="bg-slate-100 dark:bg-slate-950 lg:col-span-3">
                                <div class="aspect-[4/3] h-full overflow-hidden">
                                    <img
                                        src="<?php echo e($signatureDishImageUrl); ?>"
                                        alt="<?php echo e($chef->signature_dish_name ?: 'Signature dish by '.$chef->display_name); ?>"
                                        class="h-full w-full object-cover"
                                        loading="lazy"
                                    >
                                </div>
                            </figure>
                        <?php endif; ?>

                        <div class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                            'flex flex-col justify-center px-6 py-8 sm:px-10 sm:py-12 lg:px-12',
                            'lg:col-span-2' => $signatureDishImageUrl,
                            'lg:col-span-5' => ! $signatureDishImageUrl,
                        ]); ?>">
                            <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-500 dark:text-slate-500">Signature Dish</p>

                            <?php if($chef->signature_dish_name): ?>
                                <h2 id="signature-dish-title" class="mt-3 text-3xl font-light leading-tight tracking-tight text-slate-950 sm:text-4xl dark:text-white">
                                    <?php echo e($chef->signature_dish_name); ?>

                                </h2>
                            <?php else: ?>
                                <h2 id="signature-dish-title" class="sr-only">Signature dish</h2>
                            <?php endif; ?>

                            <?php if($chef->signature_dishes): ?>
                                <div class="mt-6 whitespace-pre-line text-base font-light leading-8 text-slate-700 dark:text-slate-300"><?php echo e($chef->signature_dishes); ?></div>
                            <?php endif; ?>

                            <p class="mt-7 border-t border-slate-200 pt-5 text-xs uppercase tracking-[0.14em] text-slate-500 dark:border-slate-800 dark:text-slate-500">
                                Selected by <?php echo e($chef->display_name); ?>

                            </p>
                        </div>
                    </div>
                </article>
            </section>
        <?php endif; ?>

        
        <?php if($chef->biography || $workingImageUrl): ?>
            <section class="mx-auto w-full max-w-6xl px-4 pt-12 sm:px-6 sm:pt-16 lg:px-8" aria-labelledby="chef-story-title">
                <div class="border-t border-slate-200 pt-10 sm:pt-12 dark:border-slate-800">
                    <div class="grid gap-10 lg:grid-cols-5 lg:items-start lg:gap-14">
                        <div class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                            'lg:col-span-3' => $workingImageUrl,
                            'lg:col-span-5 max-w-4xl' => ! $workingImageUrl,
                        ]); ?>">
                            <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-500 dark:text-slate-500">Bandara Kitchen</p>
                            <h2 id="chef-story-title" class="mt-3 text-3xl font-light tracking-tight text-slate-950 sm:text-4xl dark:text-white">The Chef’s Story</h2>

                            <?php if($chef->biography): ?>
                                <div class="mt-7 whitespace-pre-line text-base font-light leading-8 text-slate-700 sm:text-lg sm:leading-9 dark:text-slate-300"><?php echo e($chef->biography); ?></div>
                            <?php endif; ?>
                        </div>

                        <?php if($workingImageUrl): ?>
                            <figure class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:col-span-2">
                                <div class="aspect-[4/3] overflow-hidden">
                                    <img
                                        src="<?php echo e($workingImageUrl); ?>"
                                        alt="<?php echo e($chef->display_name); ?> at work"
                                        class="h-full w-full object-cover"
                                        loading="lazy"
                                    >
                                </div>
                                <?php if($chef->photographer_credit): ?>
                                    <figcaption class="px-4 py-3 text-xs text-slate-500 dark:text-slate-500">
                                        Photography: <?php echo e($chef->photographer_credit); ?>

                                    </figcaption>
                                <?php endif; ?>
                            </figure>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        
        <?php if($gallery->isNotEmpty()): ?>
            <section class="mx-auto w-full max-w-6xl px-4 pt-12 sm:px-6 sm:pt-16 lg:px-8" aria-labelledby="chef-gallery-title">
                <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-500 dark:text-slate-500">In the Kitchen</p>
                        <h2 id="chef-gallery-title" class="mt-2 text-3xl font-light tracking-tight text-slate-950 dark:text-white">A closer look</h2>
                    </div>

                    <?php if($chef->photographer_credit && ! $workingImageUrl): ?>
                        <p class="text-xs text-slate-500 dark:text-slate-500">Photography: <?php echo e($chef->photographer_credit); ?></p>
                    <?php endif; ?>
                </div>

                <?php if($galleryCount === 1): ?>
                    <figure class="overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-900">
                        <div class="aspect-video overflow-hidden">
                            <img
                                src="<?php echo e($gallery->first()); ?>"
                                alt="<?php echo e($chef->display_name); ?> kitchen photograph 1"
                                class="h-full w-full object-cover transition duration-500 hover:scale-[1.02]"
                                loading="lazy"
                            >
                        </div>
                    </figure>
                <?php elseif($galleryCount === 2): ?>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <?php $__currentLoopData = $gallery; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $imageUrl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <figure class="overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-900">
                                <div class="aspect-[4/3] overflow-hidden">
                                    <img
                                        src="<?php echo e($imageUrl); ?>"
                                        alt="<?php echo e($chef->display_name); ?> kitchen photograph <?php echo e($loop->iteration); ?>"
                                        class="h-full w-full object-cover transition duration-500 hover:scale-[1.02]"
                                        loading="lazy"
                                    >
                                </div>
                            </figure>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php else: ?>
                    <div class="grid gap-4 lg:grid-cols-5 lg:items-stretch">
                        <figure class="overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-900 lg:col-span-3">
                            <div class="aspect-[4/3] h-full overflow-hidden lg:aspect-auto">
                                <img
                                    src="<?php echo e($gallery->first()); ?>"
                                    alt="<?php echo e($chef->display_name); ?> kitchen photograph 1"
                                    class="h-full w-full object-cover transition duration-500 hover:scale-[1.02]"
                                    loading="lazy"
                                >
                            </div>
                        </figure>

                        <div class="grid gap-4 sm:grid-cols-2 lg:col-span-2">
                            <?php $__currentLoopData = $gallery->skip(1); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $imageUrl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <figure class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                                    'overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-900',
                                    'sm:col-span-2' => $galleryCount === 3 || $loop->first,
                                ]); ?>">
                                    <div class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                                        'overflow-hidden',
                                        'aspect-video' => $galleryCount === 3 || $loop->first,
                                        'aspect-square' => $galleryCount === 4 && ! $loop->first,
                                    ]); ?>">
                                        <img
                                            src="<?php echo e($imageUrl); ?>"
                                            alt="<?php echo e($chef->display_name); ?> kitchen photograph <?php echo e($loop->iteration + 1); ?>"
                                            class="h-full w-full object-cover transition duration-500 hover:scale-[1.02]"
                                            loading="lazy"
                                        >
                                    </div>
                                </figure>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        
        <?php if($relatedChefs->isNotEmpty()): ?>
            <section class="mx-auto w-full max-w-6xl px-4 pt-12 sm:px-6 sm:pt-16 lg:px-8" aria-labelledby="other-chefs-title">
                <div class="border-t border-slate-200 pt-10 sm:pt-12 dark:border-slate-800">
                    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-500 dark:text-slate-500">Bandara Kitchen</p>
                            <h2 id="other-chefs-title" class="mt-2 text-3xl font-light tracking-tight text-slate-950 dark:text-white">Meet more Chefs</h2>
                        </div>

                        <a href="<?php echo e(route('kitchen.chefs.index')); ?>" class="inline-flex items-center gap-2 text-sm font-medium text-slate-900 dark:text-slate-100">
                            All Chefs <span aria-hidden="true">→</span>
                        </a>
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <?php $__currentLoopData = $relatedChefs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $relatedChef): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php echo $__env->make('storefront.kitchen.partials.chef-card', ['chef' => $relatedChef], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <div class="mx-auto w-full max-w-6xl px-4 pt-12 sm:px-6 lg:px-8">
                <div class="border-t border-slate-200 pt-8 dark:border-slate-800">
                    <a href="<?php echo e(route('kitchen.chefs.index')); ?>" class="inline-flex items-center gap-2 text-sm font-medium text-slate-900 dark:text-slate-100">
                        <span aria-hidden="true">←</span> All Chefs
                    </a>
                </div>
            </div>
        <?php endif; ?>

        
        <aside class="mx-auto w-full max-w-6xl px-4 pt-10 sm:px-6 lg:px-8" aria-label="Editorial note">
            <div class="border-t border-slate-200 pt-6 text-xs leading-6 text-slate-500 dark:border-slate-800 dark:text-slate-500">
                <span class="font-medium text-slate-700 dark:text-slate-300">Editorial note:</span>
                Bandara Kitchen Chef profiles are published with the featured Chef’s approval and are intended for editorial and culinary inspiration. Ingredient availability, preparation methods and results may vary. Please check ingredients for allergens and follow appropriate food-handling and cooking practices.
            </div>
        </aside>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.customer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/storefront/kitchen/chefs/show.blade.php ENDPATH**/ ?>