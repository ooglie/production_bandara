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
        $gallery = collect($chef->galleryImageUrls())->filter()->values();

        $professionalLinks = collect([
            'Website' => $chef->website_url,
            'Instagram' => $chef->instagram_url,
            'LinkedIn' => $chef->linkedin_url,
        ])->filter(fn ($url) => filled($url));

        $hasSignatureDish = filled($chef->signature_dish_name)
            || filled($chef->signature_dishes)
            || filled($signatureDishImageUrl);

        $moments = collect([$workingImageUrl])
            ->merge($gallery)
            ->when($signatureDishImageUrl, fn ($images) => $images->push($signatureDishImageUrl))
            ->filter()
            ->unique()
            ->values();


        $profileFacts = collect([
            'Professional Role' => $chef->professional_title,
            'Kitchen / Brand' => $organisation,
            'Based in' => $location,
            'Signature Dish' => $chef->signature_dish_name,
        ])->filter(fn ($value) => filled($value));
    ?>

    <div class="pb-14 sm:pb-20">
        
        <section class="mx-auto w-full max-w-7xl px-4 pt-7 sm:px-6 sm:pt-9 lg:px-8">
            <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <a href="<?php echo e(url('/')); ?>" class="transition hover:text-slate-950 dark:hover:text-white">Home</a>
                <span aria-hidden="true">›</span>
                <a href="<?php echo e(route('kitchen.index')); ?>" class="transition hover:text-slate-950 dark:hover:text-white">Bandara Kitchen</a>
                <span aria-hidden="true">›</span>
                <a href="<?php echo e(route('kitchen.chefs.index')); ?>" class="transition hover:text-slate-950 dark:hover:text-white">Chefs</a>
                <span aria-hidden="true">›</span>
                <span aria-current="page" class="text-slate-700 dark:text-slate-300"><?php echo e($chef->display_name); ?></span>
            </nav>
        </section>

        
        <section class="mx-auto mt-5 w-full max-w-7xl px-4 sm:px-6 lg:px-8" aria-labelledby="chef-name">
            <article class="overflow-hidden rounded-xl bg-slate-50 dark:bg-slate-900">
                <div class="grid lg:min-h-[560px] lg:grid-cols-2">
                    <figure class="relative min-h-[380px] overflow-hidden bg-slate-100 sm:min-h-[500px] lg:min-h-[560px] dark:bg-slate-950">
                        <?php if($portraitUrl): ?>
                            <img
                                src="<?php echo e($portraitUrl); ?>"
                                alt="Portrait of <?php echo e($chef->display_name); ?>"
                                class="absolute inset-0 h-full w-full object-cover object-top"
                                fetchpriority="high"
                            >
                        <?php else: ?>
                            <div class="absolute inset-0 flex items-center justify-center text-6xl font-light tracking-[0.16em] text-slate-400 dark:text-slate-600">
                                <?php echo e(\App\Support\BandaraKitchen::initials($chef->display_name)); ?>

                            </div>
                        <?php endif; ?>
                    </figure>

                    <div class="flex flex-col justify-center px-6 py-10 sm:px-10 sm:py-14 lg:px-14 lg:py-16 xl:px-16">
                        <p class="text-xs font-medium uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">
                            Featured Chef
                        </p>

                        <h1 id="chef-name" class="mt-5 text-4xl font-light leading-[1.05] tracking-tight text-slate-950 sm:text-5xl lg:text-6xl dark:text-white">
                            <?php echo e($chef->display_name); ?>

                        </h1>

                        <span class="mt-6 block h-px w-12 bg-slate-400 dark:bg-slate-600" aria-hidden="true"></span>

                        <?php if($professionalMeta): ?>
                            <p class="mt-5 text-sm font-medium leading-7 text-slate-800 dark:text-slate-200">
                                <?php echo e($professionalMeta); ?>

                            </p>
                        <?php endif; ?>

                        <?php if($chef->short_intro): ?>
                            <p class="mt-5 max-w-xl text-base font-light leading-8 text-slate-700 sm:text-lg dark:text-slate-300">
                                <?php echo e($chef->short_intro); ?>

                            </p>
                        <?php endif; ?>

                        <div class="mt-8 flex flex-wrap items-center gap-3">
                            <?php if($chef->biography): ?>
                                <a
                                    href="#chef-story"
                                    class="inline-flex min-h-10 items-center justify-center rounded-md bg-slate-950 px-5 py-2.5 text-xs font-medium uppercase tracking-[0.13em] text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
                                >
                                    Meet the Chef
                                </a>
                            <?php endif; ?>

                            <?php $__currentLoopData = $professionalLinks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $url): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <a
                                    href="<?php echo e($url); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex min-h-10 items-center gap-1.5 px-2 py-2 text-sm text-slate-700 transition hover:text-slate-950 dark:text-slate-300 dark:hover:text-white"
                                >
                                    <?php echo e($label); ?> <span aria-hidden="true">↗</span>
                                </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        
        <?php if($hasSignatureDish): ?>
            <section class="mx-auto mt-7 w-full max-w-7xl px-4 sm:mt-9 sm:px-6 lg:px-8" aria-labelledby="signature-dish-title">
                <article class="overflow-hidden rounded-xl bg-slate-50 dark:bg-slate-900">
                    <div class="grid lg:grid-cols-2">
                        <?php if($signatureDishImageUrl): ?>
                            <figure class="relative min-h-[300px] overflow-hidden bg-slate-100 sm:min-h-[420px] lg:min-h-[480px] dark:bg-slate-950">
                                <img
                                    src="<?php echo e($signatureDishImageUrl); ?>"
                                    alt="<?php echo e($chef->signature_dish_name ?: 'Signature dish by '.$chef->display_name); ?>"
                                    class="absolute inset-0 h-full w-full object-cover"
                                    loading="lazy"
                                >

                                <div class="absolute left-5 top-5 flex h-24 w-24 items-center justify-center rounded-full border border-slate-300 bg-white/90 px-4 text-center text-[10px] font-medium uppercase leading-4 tracking-[0.14em] text-slate-700 backdrop-blur dark:border-slate-700 dark:bg-slate-950/90 dark:text-slate-200">
                                    Chef’s<br>Signature<br>Dish
                                </div>
                            </figure>
                        <?php endif; ?>

                        <div class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                            'flex flex-col justify-center px-6 py-10 sm:px-10 sm:py-14 lg:px-14',
                            'lg:min-h-[480px]' => $signatureDishImageUrl,
                        ]); ?>">
                            <p class="text-xs font-medium uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">
                                Chef’s Signature Dish
                            </p>

                            <?php if($chef->signature_dish_name): ?>
                                <h2 id="signature-dish-title" class="mt-4 text-3xl font-light leading-tight tracking-tight text-slate-950 sm:text-4xl lg:text-5xl dark:text-white">
                                    <?php echo e($chef->signature_dish_name); ?>

                                </h2>
                            <?php else: ?>
                                <h2 id="signature-dish-title" class="sr-only">Chef’s signature dish</h2>
                            <?php endif; ?>

                            <?php if($chef->signature_dishes): ?>
                                <div class="mt-6 whitespace-pre-line text-base font-light leading-8 text-slate-700 dark:text-slate-300"><?php echo e($chef->signature_dishes); ?></div>
                            <?php endif; ?>

                            <p class="mt-8 text-xs uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">
                                Selected by <?php echo e($chef->display_name); ?>

                            </p>
                        </div>
                    </div>
                </article>
            </section>
        <?php endif; ?>

        
        <?php if($chef->biography || $moments->isNotEmpty()): ?>
            <section id="chef-story" class="mx-auto w-full max-w-7xl scroll-mt-24 px-4 py-12 sm:px-6 sm:py-16 lg:px-8" aria-labelledby="chef-story-title">
                <div class="grid gap-10 border-b border-slate-200 pb-12 lg:grid-cols-12 lg:items-start lg:gap-12 sm:pb-16 dark:border-slate-800">
                    <div class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                        'lg:col-span-7' => $moments->isNotEmpty(),
                        'lg:col-span-12 max-w-4xl' => $moments->isEmpty(),
                    ]); ?>">
                        <p class="text-xs font-medium uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">
                            The Culinary Journey
                        </p>
                        <h2 id="chef-story-title" class="mt-3 text-3xl font-light tracking-tight text-slate-950 sm:text-4xl dark:text-white">
                            The Chef’s Story
                        </h2>

                        <?php if($chef->biography): ?>
                            <div class="mt-6 whitespace-pre-line text-base font-light leading-8 text-slate-700 sm:text-lg sm:leading-9 dark:text-slate-300"><?php echo e($chef->biography); ?></div>
                        <?php endif; ?>
                    </div>

                    <?php if($moments->isNotEmpty()): ?>
                        <div class="lg:col-span-5" aria-labelledby="chef-gallery-title">
                            <p class="text-xs font-medium uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">
                                In the Kitchen
                            </p>
                            <h2 id="chef-gallery-title" class="mt-2 text-2xl font-light tracking-tight text-slate-950 sm:text-3xl dark:text-white">
                                Moments &amp; Creations
                            </h2>

                            <div class="mt-5 grid grid-cols-2 gap-3">
                                <?php $__currentLoopData = $moments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $imageUrl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <figure class="overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-900">
                                        <div class="aspect-[4/3] overflow-hidden">
                                            <img
                                                src="<?php echo e($imageUrl); ?>"
                                                alt="<?php echo e($chef->display_name); ?> kitchen moment <?php echo e($loop->iteration); ?>"
                                                class="h-full w-full object-cover transition duration-500 hover:scale-[1.02]"
                                                loading="lazy"
                                            >
                                        </div>
                                    </figure>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>

                            <?php if($chef->photographer_credit): ?>
                                <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">
                                    Photography: <?php echo e($chef->photographer_credit); ?>

                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        
        <?php if($profileFacts->isNotEmpty()): ?>
            <section class="mx-auto w-full max-w-7xl px-4 pb-8 sm:px-6 sm:pb-10 lg:px-8" aria-label="Chef profile highlights">
                <div class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                    'grid overflow-hidden rounded-xl bg-slate-50 dark:bg-slate-900',
                    'sm:grid-cols-2' => $profileFacts->count() > 1,
                    'lg:grid-cols-4' => $profileFacts->count() > 2,
                ]); ?>">
                    <?php $__currentLoopData = $profileFacts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="border-b border-slate-200 px-6 py-6 text-center last:border-b-0 sm:border-b sm:border-r sm:last:border-r-0 lg:border-b-0 dark:border-slate-800">
                            <p class="text-[10px] font-medium uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400"><?php echo e($label); ?></p>
                            <p class="mt-2 text-sm leading-6 text-slate-800 dark:text-slate-200"><?php echo e($value); ?></p>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </section>
        <?php endif; ?>

        
        <aside class="border-y border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900" aria-label="Editorial note">
            <div class="mx-auto flex w-full max-w-7xl gap-4 px-4 py-6 text-xs leading-6 text-slate-600 sm:px-6 lg:px-8 dark:text-slate-400">
                <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-slate-300 text-sm dark:border-slate-700" aria-hidden="true">✦</span>
                <p>
                    <span class="font-medium text-slate-800 dark:text-slate-200">Editorial note:</span>
                    Bandara Kitchen Chef profiles are published with the featured Chef’s approval and are intended for editorial and culinary inspiration. Ingredient availability, preparation methods and results may vary. Please check ingredients for allergens and follow appropriate food-handling and cooking practices.
                </p>
            </div>
        </aside>
    </div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.customer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/storefront/kitchen/chefs/show.blade.php ENDPATH**/ ?>