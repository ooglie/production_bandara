@php
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

    $chefSpotlightImage = $resolveMediaUrl(array_values(array_filter([
        $section->image_path ?? null,
        $section->mobile_image_path ?? null,
        $chefCollection?->image_path,
    ])));

    $chefSpotlightEyebrow = $section->getSetting('spotlight_eyebrow', $chefCollection?->eyebrow ?: 'Chef spotlight');
    $chefSpotlightTitle = $section->getSetting('spotlight_title', $chefCollection?->name ?: 'Chef notes, serving ideas, and practical cooking tips.');
    $chefSpotlightDescription = $section->getSetting('spotlight_description', $section->body ?: ($chefCollection?->description ?: 'Use collections and recipes to explain how to finish, plate, or serve your frozen products in a more inspiring way.'));
    $chefSpotlightCtaText = $section->cta_text ?: ($chefCollection?->cta_text ?: 'Browse chef picks');
    $chefSpotlightCtaUrl = $section->cta_url ?: ($chefCollection ? $collectionUrl($chefCollection) : null);

    $spotlightTipOne = $section->getSetting('spotlight_tip_one', 'Great for air fryer, pan, or oven-finish cooking');
    $spotlightTipTwo = $section->getSetting('spotlight_tip_two', 'Useful for weekday meals, platters, and sharing');
@endphp

<section class="space-y-4">
    <div>
        @if($section->eyebrow)
            <p class="text-[11px] uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">{{ $section->eyebrow }}</p>
        @endif
        <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-50">{{ $section->title ?: 'Cook with more confidence' }}</h2>
        @if($section->subtitle)
            <p class="mt-1 max-w-2xl text-sm text-gray-600 dark:text-gray-300">{{ $section->subtitle }}</p>
        @endif
    </div>

    <div class="grid gap-4 md:grid-cols-2 items-stretch">
        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 h-full">
            <div class="relative h-[230px] overflow-hidden bg-gradient-to-br from-rose-50 via-fuchsia-50 to-white dark:from-rose-950/30 dark:via-fuchsia-950/20 dark:to-gray-900">
                @if($chefSpotlightImage)
                    <img
                        src="{{ $chefSpotlightImage }}"
                        alt="{{ $chefSpotlightTitle }}"
                        class="h-full w-full object-cover transition duration-300 hover:scale-105"
                    >
                @else
                    <div class="absolute inset-0 bg-gradient-to-br from-rose-100 via-fuchsia-50 to-white dark:from-rose-950/30 dark:via-fuchsia-950/20 dark:to-gray-900"></div>
                @endif
            </div>

            <div class="p-5 space-y-4">
                <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-950/40 px-3 py-1 text-[10px] font-medium uppercase tracking-wide text-gray-600 dark:text-gray-300">
                    {{ $chefSpotlightEyebrow }}
                </span>

                <div class="space-y-2">
                    <h3 class="text-2xl font-semibold text-gray-900 dark:text-gray-50">
                        {{ $chefSpotlightTitle }}
                    </h3>

                    <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                        {{ $chefSpotlightDescription }}
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                        {{ $spotlightTipOne }}
                    </div>
                    <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                        {{ $spotlightTipTwo }}
                    </div>
                </div>

                @if($chefSpotlightCtaUrl)
                    <div>
                        <a href="{{ $chefSpotlightCtaUrl }}"
                           class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-sm font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                            {{ $chefSpotlightCtaText }}
                        </a>
                    </div>
                @endif
            </div>
        </div>

        @if($featuredRecipeProduct && $featuredRecipe)
            <a href="{{ $productUrl($featuredRecipeProduct) }}"
               class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 transition hover:-translate-y-0.5 h-full flex flex-col">
                <div class="relative h-[220px] shrink-0 overflow-hidden bg-gray-100 dark:bg-gray-800">
                    @if($featuredRecipeImage)
                        <img
                            src="{{ $featuredRecipeImage }}"
                            alt="{{ $featuredRecipeTitle }}"
                            class="h-full w-full object-cover"
                        >
                    @else
                        <div class="absolute inset-0 bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-950/20 dark:to-orange-950/10"></div>
                    @endif
                </div>

                <div class="p-5 flex-1 flex flex-col">
                    <div class="space-y-3">
                        <div class="inline-flex items-center rounded-sm bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 px-2.5 py-1 text-[10px] font-medium uppercase tracking-wide">
                            Recipe inspiration
                        </div>

                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-50">
                            {{ $featuredRecipeTitle }}
                        </h3>

                        @if($featuredRecipeShort)
                            <p class="text-sm font-medium leading-relaxed text-gray-700 dark:text-gray-200">
                                {{ $featuredRecipeShort }}
                            </p>
                        @endif

                        <div class="flex flex-wrap gap-2 text-[11px]">
                            @if($featuredRecipe->prep_time_minutes)
                                <span class="rounded-sm border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950/40 px-3 py-1 text-gray-600 dark:text-gray-300">
                                    Prep {{ $featuredRecipe->prep_time_minutes }} mins
                                </span>
                            @endif

                            @if($featuredRecipe->cook_time_minutes)
                                <span class="rounded-sm border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950/40 px-3 py-1 text-gray-600 dark:text-gray-300">
                                    Cook {{ $featuredRecipe->cook_time_minutes }} mins
                                </span>
                            @endif

                            @if($featuredRecipe->servings)
                                <span class="rounded-sm border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950/40 px-3 py-1 text-gray-600 dark:text-gray-300">
                                    Serves {{ $featuredRecipe->servings }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <div class="text-[11px] font-semibold text-gray-900 dark:text-gray-50 mb-2">Ingredients</div>
                            @if(!empty($featuredRecipeIngredientsTeaser))
                                <ul class="space-y-1 text-xs text-gray-600 dark:text-gray-300">
                                    @foreach($featuredRecipeIngredientsTeaser as $ingredient)
                                        <li class="flex items-start gap-2">
                                            <span class="mt-[5px] h-1.5 w-1.5 rounded-sm bg-gray-400"></span>
                                            <span>{{ $ingredient }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="text-xs text-gray-400">Ingredients not added yet.</div>
                            @endif
                        </div>

                        <div>
                            <div class="text-[11px] font-semibold text-gray-900 dark:text-gray-50 mb-2">Method</div>
                            @if(!empty($featuredRecipeStepsTeaser))
                                <ol class="space-y-2 text-xs text-gray-600 dark:text-gray-300">
                                    @foreach($featuredRecipeStepsTeaser as $step)
                                        <li class="flex items-start gap-2">
                                            <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-sm bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 text-[10px] font-semibold">
                                                {{ $loop->iteration }}
                                            </span>
                                            <span>{{ $step }}</span>
                                        </li>
                                    @endforeach
                                </ol>
                            @else
                                <div class="text-xs text-gray-400">Cooking steps not added yet.</div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between gap-4">
                        <div>
                            <div class="text-[11px] uppercase tracking-wide text-gray-400">Featured product</div>
                            <div class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-50">
                                {{ $featuredRecipeProduct->name }}
                            </div>
                        </div>

                        <div class="inline-flex items-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-2 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                            View product
                        </div>
                    </div>
                </div>
            </a>
        @else
            <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                Add at least one product with an active recipe to show a rotating recipe card here.
            </div>
        @endif
    </div>
</section>
