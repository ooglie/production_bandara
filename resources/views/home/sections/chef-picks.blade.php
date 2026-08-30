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

    <div class="bandara-home-shared-hover-shell grid gap-4 md:grid-cols-2 items-stretch">
        @if($featuredHomepageChef)
            <div class="bandara-home-independent-card bandara-home-chef-card overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 h-full flex flex-col">
                <div class="grid grid-cols-3 gap-4 p-5 pb-4 items-start">
                    <a
                        href="{{ route('kitchen.chefs.show', $featuredHomepageChef) }}"
                        class="col-span-1 block overflow-hidden rounded-md bg-gray-100 dark:bg-gray-800 aspect-[4/5]"
                        aria-label="Meet {{ $featuredHomepageChef->display_name }}"
                    >
                        @if($chefSpotlightImage)
                            <img
                                src="{{ $chefSpotlightImage }}"
                                alt="{{ $featuredHomepageChef->display_name }}"
                                class="bandara-home-independent-media h-full w-full object-cover object-top transition duration-300"
                            >
                        @else
                            <span class="flex h-full w-full items-center justify-center text-2xl font-semibold text-gray-400 dark:text-gray-500">
                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($featuredHomepageChef->display_name, 0, 1)) }}
                            </span>
                        @endif
                    </a>

                    <div class="col-span-2 min-w-0">
                        <p class="text-[10px] font-medium uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">
                            Featured Chef
                        </p>

                        <a
                            href="{{ route('kitchen.chefs.show', $featuredHomepageChef) }}"
                            class="mt-2 block text-xl sm:text-2xl font-semibold leading-tight text-gray-900 dark:text-gray-50 hover:underline underline-offset-4"
                        >
                            {{ $featuredHomepageChef->display_name }}
                        </a>

                        @if(filled($chefProfessionalLine))
                            <p class="mt-2 text-xs sm:text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                                {{ $chefProfessionalLine }}
                            </p>
                        @endif

                        @if(filled($featuredHomepageChef->signature_dish_name))
                            <div class="mt-3 rounded-sm border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950/40 px-3 py-2">
                                <span class="block text-[9px] font-medium uppercase tracking-[0.14em] text-gray-400 dark:text-gray-500">
                                    Signature dish
                                </span>
                                <span class="mt-0.5 block text-xs sm:text-sm font-medium text-gray-800 dark:text-gray-100">
                                    {{ $featuredHomepageChef->signature_dish_name }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                @if(filled($chefBrief))
                    <p class="px-5 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                        {{ \Illuminate\Support\Str::limit($chefBrief, 210) }}
                    </p>
                @endif

                <div class="px-5 pt-4">
                    <a
                        href="{{ route('kitchen.chefs.show', $featuredHomepageChef) }}"
                        class="inline-flex items-center justify-center gap-2 rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-sm font-medium hover:bg-gray-800 dark:hover:bg-gray-200"
                    >
                        Meet the Chef
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-5-5 5 5-5 5" />
                        </svg>
                    </a>
                </div>

                @if($otherHomepageChefs->isNotEmpty())
                    <div class="mt-auto border-t border-gray-100 dark:border-gray-800 px-5 py-4">
                        <div class="space-y-3">
                            <a
                                href="{{ route('kitchen.chefs.index') }}"
                                class="inline-flex items-center gap-2 text-[10px] font-medium uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400 hover:underline underline-offset-4"
                            >
                                Meet Other Chefs
                                <span aria-hidden="true">→</span>
                            </a>

                            <div class="grid grid-cols-5 gap-2">
                            @foreach($otherHomepageChefs as $otherChef)
                                @php
                                    $otherChefImage = $otherChef->portraitUrl() ?: $otherChef->heroImageUrl();
                                @endphp
                                <a
                                    href="{{ route('kitchen.chefs.show', $otherChef) }}"
                                    class="aspect-square overflow-hidden rounded-sm bg-gray-100 dark:bg-gray-800"
                                    aria-label="Meet {{ $otherChef->display_name }}"
                                    title="{{ $otherChef->display_name }}"
                                >
                                    @if($otherChefImage)
                                        <img
                                            src="{{ $otherChefImage }}"
                                            alt="{{ $otherChef->display_name }}"
                                            class="h-full w-full object-cover"
                                            loading="lazy"
                                        >
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-sm font-semibold text-gray-400 dark:text-gray-500">
                                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($otherChef->display_name, 0, 1)) }}
                                        </span>
                                    @endif
                                </a>
                            @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mt-auto h-5"></div>
                @endif
            </div>
        @endif

        @if($featuredRecipeProduct && $featuredRecipe)
            <a href="{{ $productUrl($featuredRecipeProduct) }}"
               class="bandara-home-independent-card bandara-home-recipe-card overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 transition hover:-translate-y-0.5 h-full flex flex-col">
                <div class="relative h-[220px] shrink-0 overflow-hidden bg-gray-100 dark:bg-gray-800">
                    @if($featuredRecipeImage)
                        <img
                            src="{{ $featuredRecipeImage }}"
                            alt="{{ $featuredRecipeTitle }}"
                            class="bandara-home-independent-media h-full w-full object-cover"
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
            <div class="bandara-home-independent-card bandara-home-recipe-card rounded-lg border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                Add at least one product with an active recipe to show a rotating recipe card here.
            </div>
        @endif
    </div>
</section>
