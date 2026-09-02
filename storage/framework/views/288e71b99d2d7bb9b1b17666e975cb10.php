<?php
    $featuredRecipeProduct = $recipeFeatureProduct;
    $featuredRecipe = $featuredRecipeProduct?->activeRecipes?->first();
    $featuredRecipeTitle = $featuredRecipe ? $recipeText($featuredRecipe, 'title') : null;
    $featuredRecipeShort = $featuredRecipe ? ($recipeText($featuredRecipe, 'short_description') ?: $recipeText($featuredRecipe, 'description')) : null;
    $featuredRecipeIngredientsTeaser = $featuredRecipe ? array_slice($recipeList($featuredRecipe, 'ingredients'), 0, 4) : [];
    $featuredRecipeStepsTeaser = $featuredRecipe ? array_slice($recipeList($featuredRecipe, 'steps'), 0, 3) : [];

    $featuredProductPrimaryImage = $featuredRecipeProduct?->primary_image;
    $featuredProductGalleryImage = $featuredRecipeProduct?->images?->firstWhere('is_primary', true)?->file_path
        ?: $featuredRecipeProduct?->images?->first()?->file_path;

    $featuredRecipeImage = $resolveMediaUrl(array_values(array_filter([
        $featuredRecipe?->image_path,
        $featuredProductPrimaryImage,
        $featuredProductGalleryImage,
    ])));

    /*
     * Chef content comes only from the existing chefs table.
     * The Recipe card below remains independent and keeps its existing query.
     */
    $featuredHomepageChef = \App\Models\Chef::query()
        ->homepageFeatured()
        ->orderByDesc('updated_at')
        ->first();

    $chefSpotlightImage = $featuredHomepageChef
        ? ($featuredHomepageChef->portraitUrl() ?: $featuredHomepageChef->heroImageUrl())
        : null;

    $chefProfessionalLine = $featuredHomepageChef
        ? collect([
            $featuredHomepageChef->professional_title,
            $featuredHomepageChef->publicOrganisationLine(),
            $featuredHomepageChef->city,
        ])->filter(fn ($value) => filled($value))->implode(' · ')
        : null;

    $chefBrief = $featuredHomepageChef
        ? trim(strip_tags((string) (
            $featuredHomepageChef->short_intro
            ?: $featuredHomepageChef->biography
            ?: $featuredHomepageChef->quote
            ?: ''
        )))
        : null;

    $otherHomepageChefs = $featuredHomepageChef
        ? \App\Models\Chef::query()
            ->published()
            ->where($featuredHomepageChef->getKeyName(), '!=', $featuredHomepageChef->getKey())
            ->orderBy('sort_order')
            ->orderBy('display_name')
            ->limit(5)
            ->get()
        : collect();
?>

<style>
    .bandara-home-recipe-card .bandara-home-recipe-feature-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .bandara-home-recipe-card .bandara-home-recipe-feature-image {
        aspect-ratio: 16 / 9;
    }

    @media (min-width: 768px) {
        .bandara-home-chef-card .bandara-home-chef-feature-grid {
            grid-template-columns: minmax(0, 42%) minmax(0, 1fr);
            column-gap: 1.75rem;
        }

        .bandara-home-chef-card .bandara-home-chef-feature-image,
        .bandara-home-chef-card .bandara-home-chef-feature-copy {
            grid-column: auto;
        }

        .bandara-home-chef-card .bandara-home-chef-feature-name {
            margin-top: 0.75rem;
        }

        .bandara-home-chef-card .bandara-home-chef-feature-meta {
            margin-top: 0.875rem;
        }

        .bandara-home-chef-card .bandara-home-chef-signature {
            margin-top: 1.25rem;
            padding: 0.875rem 1rem;
        }

        /* Mirror the Chef card: recipe copy on the left, food image on the right. */
        .bandara-home-recipe-card .bandara-home-recipe-feature-grid {
            grid-template-columns: minmax(0, 1fr) minmax(0, 42%);
            column-gap: 1.75rem;
        }

        .bandara-home-recipe-card .bandara-home-recipe-feature-copy {
            grid-column: 1;
            grid-row: 1;
        }

        .bandara-home-recipe-card .bandara-home-recipe-feature-image {
            grid-column: 2;
            grid-row: 1;
            aspect-ratio: 4 / 5;
        }
    }
</style>

<section class="space-y-4">
    <div>
        <?php if($section->eyebrow): ?>
            <p class="text-[11px] uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400"><?php echo e($section->eyebrow); ?></p>
        <?php endif; ?>
        <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-50"><?php echo e($section->title ?: 'Cook with more confidence'); ?></h2>
        <?php if($section->subtitle): ?>
            <p class="mt-1 max-w-2xl text-sm text-gray-600 dark:text-gray-300"><?php echo e($section->subtitle); ?></p>
        <?php endif; ?>
    </div>

    <div class="bandara-home-shared-hover-shell grid gap-4 md:grid-cols-2 items-stretch">
        <?php if($featuredHomepageChef): ?>
            <div class="bandara-home-independent-card bandara-home-chef-card overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 flex flex-col" style="align-self: start;">
                <div class="bandara-home-chef-feature-grid grid grid-cols-3 gap-4 p-5 pb-4 items-start">
                    <a
                        href="<?php echo e(route('kitchen.chefs.show', $featuredHomepageChef)); ?>"
                        class="bandara-home-chef-feature-image col-span-1 block overflow-hidden rounded-md bg-gray-100 dark:bg-gray-800 aspect-[4/5]"
                        aria-label="Meet <?php echo e($featuredHomepageChef->display_name); ?>"
                    >
                        <?php if($chefSpotlightImage): ?>
                            <img
                                src="<?php echo e($chefSpotlightImage); ?>"
                                alt="<?php echo e($featuredHomepageChef->display_name); ?>"
                                class="bandara-home-independent-media h-full w-full object-cover object-top transition duration-300"
                            >
                        <?php else: ?>
                            <span class="flex h-full w-full items-center justify-center text-2xl font-semibold text-gray-400 dark:text-gray-500">
                                <?php echo e(\Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($featuredHomepageChef->display_name, 0, 1))); ?>

                            </span>
                        <?php endif; ?>
                    </a>

                    <div class="bandara-home-chef-feature-copy col-span-2 min-w-0">
                        <p class="text-[10px] font-medium uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">
                            Featured Chef
                        </p>

                        <a
                            href="<?php echo e(route('kitchen.chefs.show', $featuredHomepageChef)); ?>"
                            class="bandara-home-chef-feature-name mt-2 block text-xl sm:text-2xl font-semibold leading-tight text-gray-900 dark:text-gray-50 hover:underline underline-offset-4"
                        >
                            <?php echo e($featuredHomepageChef->display_name); ?>

                        </a>

                        <?php if(filled($chefProfessionalLine)): ?>
                            <p class="bandara-home-chef-feature-meta mt-2 text-xs sm:text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                                <?php echo e($chefProfessionalLine); ?>

                            </p>
                        <?php endif; ?>

                        <?php if(filled($featuredHomepageChef->signature_dish_name)): ?>
                            <div class="bandara-home-chef-signature mt-3 rounded-sm border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950/40 px-3 py-2">
                                <span class="block text-[9px] font-medium uppercase tracking-[0.14em] text-gray-400 dark:text-gray-500">
                                    Signature dish
                                </span>
                                <span class="mt-0.5 block text-xs sm:text-sm font-medium text-gray-800 dark:text-gray-100">
                                    <?php echo e($featuredHomepageChef->signature_dish_name); ?>

                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if(filled($chefBrief)): ?>
                    <p class="px-5 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                        <?php echo e(\Illuminate\Support\Str::limit($chefBrief, 210)); ?>

                    </p>
                <?php endif; ?>

                <div class="px-5 pt-4">
                    <a
                        href="<?php echo e(route('kitchen.chefs.show', $featuredHomepageChef)); ?>"
                        class="inline-flex items-center justify-center gap-2 rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-sm font-medium hover:bg-gray-800 dark:hover:bg-gray-200"
                    >
                        Meet the Chef
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-5-5 5 5-5 5" />
                        </svg>
                    </a>
                </div>

                <?php if($otherHomepageChefs->isNotEmpty()): ?>
                    <div class="mt-5 border-t border-gray-100 dark:border-gray-800 px-5 py-4">
                        <div class="space-y-3">
                            <a
                                href="<?php echo e(route('kitchen.chefs.index')); ?>"
                                class="inline-flex items-center gap-2 text-[10px] font-medium uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400 hover:underline underline-offset-4"
                            >
                                Meet Other Chefs
                                <span aria-hidden="true">→</span>
                            </a>

                            <div class="grid grid-cols-5 gap-2">
                                <?php $__currentLoopData = $otherHomepageChefs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $otherChef): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $otherChefImage = $otherChef->portraitUrl() ?: $otherChef->heroImageUrl();
                                    ?>
                                    <a
                                        href="<?php echo e(route('kitchen.chefs.show', $otherChef)); ?>"
                                        class="aspect-square overflow-hidden rounded-sm bg-gray-100 dark:bg-gray-800"
                                        aria-label="Meet <?php echo e($otherChef->display_name); ?>"
                                        title="<?php echo e($otherChef->display_name); ?>"
                                    >
                                        <?php if($otherChefImage): ?>
                                            <img
                                                src="<?php echo e($otherChefImage); ?>"
                                                alt="<?php echo e($otherChef->display_name); ?>"
                                                class="h-full w-full object-cover"
                                                loading="lazy"
                                            >
                                        <?php else: ?>
                                            <span class="flex h-full w-full items-center justify-center text-sm font-semibold text-gray-400 dark:text-gray-500">
                                                <?php echo e(\Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($otherChef->display_name, 0, 1))); ?>

                                            </span>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mt-auto h-5"></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if($featuredRecipeProduct && $featuredRecipe): ?>
            <a
                href="<?php echo e($productUrl($featuredRecipeProduct)); ?>"
                class="bandara-home-independent-card bandara-home-recipe-card overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 transition hover:-translate-y-0.5 h-full flex flex-col"
                aria-label="View <?php echo e($featuredRecipeTitle); ?>"
            >
                
                <div class="bandara-home-recipe-feature-grid grid gap-4 p-5 pb-4 items-start">
                    <div class="bandara-home-recipe-feature-image relative overflow-hidden rounded-md bg-gray-100 dark:bg-gray-800">
                        <?php if($featuredRecipeImage): ?>
                            <img
                                src="<?php echo e($featuredRecipeImage); ?>"
                                alt="<?php echo e($featuredRecipeTitle); ?>"
                                class="bandara-home-independent-media h-full w-full object-cover transition duration-300"
                                loading="lazy"
                            >
                        <?php else: ?>
                            <div class="flex h-full w-full items-center justify-center px-4 text-center">
                                <span class="text-[10px] font-medium uppercase tracking-[0.16em] text-gray-400 dark:text-gray-500">
                                    Recipe image
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="bandara-home-recipe-feature-copy min-w-0">
                        <p class="text-[10px] font-medium uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">
                            Recipe inspiration
                        </p>

                        <h3 class="mt-2 break-words text-xl sm:text-2xl font-semibold leading-tight text-gray-900 dark:text-gray-50">
                            <?php echo e($featuredRecipeTitle); ?>

                        </h3>

                        <?php if($featuredRecipeShort): ?>
                            <p class="mt-3 text-xs sm:text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                                <?php echo e(\Illuminate\Support\Str::limit($featuredRecipeShort, 165)); ?>

                            </p>
                        <?php endif; ?>

                        <?php if($featuredRecipe->prep_time_minutes || $featuredRecipe->cook_time_minutes || $featuredRecipe->servings): ?>
                            <div class="mt-4 flex flex-wrap gap-2 text-[10px]">
                                <?php if($featuredRecipe->prep_time_minutes): ?>
                                    <span class="rounded-sm border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950/40 px-2.5 py-1 text-gray-600 dark:text-gray-300">
                                        Prep <?php echo e($featuredRecipe->prep_time_minutes); ?> mins
                                    </span>
                                <?php endif; ?>

                                <?php if($featuredRecipe->cook_time_minutes): ?>
                                    <span class="rounded-sm border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950/40 px-2.5 py-1 text-gray-600 dark:text-gray-300">
                                        Cook <?php echo e($featuredRecipe->cook_time_minutes); ?> mins
                                    </span>
                                <?php endif; ?>

                                <?php if($featuredRecipe->servings): ?>
                                    <span class="rounded-sm border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950/40 px-2.5 py-1 text-gray-600 dark:text-gray-300">
                                        Serves <?php echo e($featuredRecipe->servings); ?>

                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex flex-1 flex-col px-5 pb-5">
                    <div class="grid gap-5 border-t border-gray-100 pt-4 sm:grid-cols-2 dark:border-gray-800">
                        <div class="min-w-0">
                            <div class="mb-3 text-[10px] font-medium uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">
                                Ingredients
                            </div>

                            <?php if(!empty($featuredRecipeIngredientsTeaser)): ?>
                                <ul class="space-y-2 text-xs leading-relaxed text-gray-600 dark:text-gray-300">
                                    <?php $__currentLoopData = $featuredRecipeIngredientsTeaser; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ingredient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li class="flex items-start gap-2">
                                            <span class="mt-[6px] h-1.5 w-1.5 shrink-0 rounded-sm bg-gray-400 dark:bg-gray-500"></span>
                                            <span><?php echo e(\Illuminate\Support\Str::limit($ingredient, 58)); ?></span>
                                        </li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>
                            <?php else: ?>
                                <div class="text-xs text-gray-400 dark:text-gray-500">Ingredients not added yet.</div>
                            <?php endif; ?>
                        </div>

                        <div class="min-w-0">
                            <div class="mb-3 text-[10px] font-medium uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">
                                Method
                            </div>

                            <?php if(!empty($featuredRecipeStepsTeaser)): ?>
                                <ol class="space-y-2 text-xs leading-relaxed text-gray-600 dark:text-gray-300">
                                    <?php $__currentLoopData = $featuredRecipeStepsTeaser; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li class="flex items-start gap-2">
                                            <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-sm bg-gray-900 text-[10px] font-semibold text-white dark:bg-gray-100 dark:text-gray-900">
                                                <?php echo e($loop->iteration); ?>

                                            </span>
                                            <span><?php echo e(\Illuminate\Support\Str::limit($step, 82)); ?></span>
                                        </li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ol>
                            <?php else: ?>
                                <div class="text-xs text-gray-400 dark:text-gray-500">Cooking steps not added yet.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-auto pt-5">
                        <div class="flex items-end justify-between gap-4 border-t border-gray-100 pt-4 dark:border-gray-800">
                            <div class="min-w-0">
                                <div class="text-[10px] font-medium uppercase tracking-[0.14em] text-gray-400 dark:text-gray-500">
                                    Featured product
                                </div>
                                <div class="mt-1 text-sm font-medium leading-snug text-gray-900 dark:text-gray-50">
                                    <?php echo e($featuredRecipeProduct->name); ?>

                                </div>
                            </div>

                            <span class="inline-flex shrink-0 items-center justify-center gap-2 rounded-sm border border-gray-900 bg-gray-900 px-3.5 py-2 text-xs font-medium text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900">
                                View product
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-5-5 5 5-5 5" />
                                </svg>
                            </span>
                        </div>
                    </div>
                </div>
            </a>
        <?php else: ?>
            <div class="bandara-home-independent-card bandara-home-recipe-card rounded-lg border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                Add at least one product with an active recipe to show a rotating recipe card here.
            </div>
        <?php endif; ?>
    </div>
</section>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/home/sections/chef-picks.blade.php ENDPATH**/ ?>