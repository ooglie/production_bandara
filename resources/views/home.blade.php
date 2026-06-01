@extends('layouts.customer')

@section('title', config('app.name') . ' - Home')

@section('content')
@php
    use App\Models\Product;
    use App\Models\ProductCollection;
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $has = fn(string $r) => Route::has($r);

    $shopUrl = $has('shop.index') ? route('shop.index') : '#';
    $supportUrl = $has('tickets.create') ? route('tickets.create') : null;

    $cartAddUrl =
        $has('cart.items.store') ? route('cart.items.store')
        : ($has('cart.add') ? route('cart.add')
        : ($has('cart.store') ? route('cart.store') : null));

    $wishlistToggleUrl =
        $has('wishlist.toggle') ? route('wishlist.toggle')
        : ($has('wishlist.store') ? route('wishlist.store') : null);

    $wishlistUrl =
        $has('wishlist.index') ? route('wishlist.index')
        : ($has('wishlist') ? route('wishlist') : null);

    $loginUrl = $has('login') ? route('login') : null;

    $flagEmoji = function (?string $code) {
        if (!function_exists('mb_convert_encoding')) return null;

        $code = strtoupper(trim((string) $code));
        if (!preg_match('/^[A-Z]{2}$/', $code)) return null;

        $a = 127397 + ord($code[0]);
        $b = 127397 + ord($code[1]);

        return mb_convert_encoding("&#{$a};&#{$b};", 'UTF-8', 'HTML-ENTITIES');
    };

    $categoryUrl = function ($category) use ($has) {
        if ($has('shop.index')) {
            return route('shop.index', ['category' => $category->id]);
        }

        return '#?category=' . $category->id;
    };

    $productUrl = function ($product) use ($has) {
        if ($has('products.show')) return route('products.show', $product);
        if ($has('product.show')) return route('product.show', $product);
        if ($has('shop.show')) return route('shop.show', $product);

        return '#';
    };

    $collectionUrl = function ($collection) use ($has, $shopUrl) {
        if ($has('collections.show') && filled($collection->slug ?? null)) {
            return route('collections.show', ['collection' => $collection->slug]);
        }

        if (filled($collection->cta_url ?? null)) {
            return $collection->cta_url;
        }

        return $shopUrl;
    };

    $resolveMediaUrl = function ($pathOrPaths) {
        $candidates = is_array($pathOrPaths) ? $pathOrPaths : [$pathOrPaths];

        foreach ($candidates as $candidate) {
            if (!$candidate) continue;

            $candidate = trim((string) $candidate);
            if ($candidate === '') continue;

            if (preg_match('#^https?://[^/]+(/storage/.*)$#i', $candidate, $matches)) {
                return $matches[1];
            }

            if (Str::startsWith($candidate, ['http://', 'https://'])) {
                return $candidate;
            }

            if (Str::startsWith($candidate, '/storage/')) {
                return $candidate;
            }

            if (Str::startsWith($candidate, 'storage/')) {
                return '/' . ltrim($candidate, '/');
            }

            if (Str::startsWith($candidate, 'storage/app/public/')) {
                return '/storage/' . ltrim(Str::after($candidate, 'storage/app/public/'), '/');
            }

            if (Str::startsWith($candidate, 'public/')) {
                return '/storage/' . ltrim(Str::after($candidate, 'public/'), '/');
            }

            if (Str::startsWith($candidate, '/')) {
                $publicRelative = ltrim($candidate, '/');

                if (file_exists(public_path($publicRelative))) {
                    return '/' . $publicRelative;
                }

                return $candidate;
            }

            if (file_exists(public_path($candidate))) {
                return '/' . ltrim($candidate, '/');
            }

            if (Storage::disk('public')->exists($candidate)) {
                return '/storage/' . ltrim($candidate, '/');
            }
        }

        return null;
    };

    $productPrimaryImageUrl = function ($product) use ($resolveMediaUrl) {
        $images = $product->images ?? collect();

        return $resolveMediaUrl([
            $product->primary_image_url ?? null,
            $product->primary_image ?? null,
            $product->image_path ?? null,
            optional($images->firstWhere('is_primary', true))->file_path,
            optional($images->first())->file_path,
        ]);
    };

    $recipeText = function ($recipe, $field) {
        if (method_exists($recipe, 'tr')) {
            return $recipe->tr($field);
        }

        $value = $recipe->{$field} ?? null;

        if (is_array($value)) {
            return $value[app()->getLocale()] ?? $value['en'] ?? (count($value) ? reset($value) : null);
        }

        return $value;
    };

    $recipeList = function ($recipe, $field) {
        if (method_exists($recipe, 'trList')) {
            return $recipe->trList($field);
        }

        $value = $recipe->{$field} ?? [];

        if (!is_array($value)) {
            return [];
        }

        if (isset($value[app()->getLocale()]) && is_array($value[app()->getLocale()])) {
            return $value[app()->getLocale()];
        }

        if (isset($value['en']) && is_array($value['en'])) {
            return $value['en'];
        }

        return array_values($value);
    };

    $heroMainImage = $resolveMediaUrl([
        'images/home/hero-main.png',
        'images/home/frozen-hero.png',
        'images/hero/frozen-hero.png',
    ]);

    $heroDishImage = $resolveMediaUrl([
        'images/home/cooked-platter.png',
        'images/home/plated-dish.png',
        'images/home/cooked-product.png',
    ]);

    $heroChefImage = $resolveMediaUrl([
        'images/home/chef.png',
        'images/home/chef-portrait.png',
        'images/home/chef-prep.png',
    ]);

    $heroPackImage = $resolveMediaUrl([
        'images/home/product-pack.png',
        'images/home/frozen-pack.png',
        'images/home/pack-shot.jpeg',
    ]);

    $heroVisualImage = $heroMainImage ?: $heroDishImage ?: $heroPackImage;
    $chefFallbackImage = $heroChefImage ?: $heroPackImage ?: $heroMainImage;

    $occasionCollections = $occasionCollections ?? ProductCollection::query()
        ->withCount('products')
        ->where('is_active', true)
        ->where('show_on_home', true)
        ->where('home_section', 'occasions')
        ->where(function ($q) {
            $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
        })
        ->where(function ($q) {
            $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
        })
        ->orderBy('home_order')
        ->take(3)
        ->get();

    $kindAccentMap = [
        'occasion' => 'from-sky-50 to-cyan-50 dark:from-sky-950/30 dark:to-cyan-950/20',
        'chef' => 'from-rose-50 to-fuchsia-50 dark:from-rose-950/30 dark:to-fuchsia-950/20',
        'seasonal' => 'from-amber-50 to-orange-50 dark:from-amber-950/30 dark:to-orange-950/20',
        'campaign' => 'from-violet-50 to-purple-50 dark:from-violet-950/30 dark:to-purple-950/20',
        'general' => 'from-slate-50 to-gray-50 dark:from-slate-950/30 dark:to-gray-950/20',
    ];

    $occasionCards = collect($occasionCollections)->map(function ($collection) use ($resolveMediaUrl, $collectionUrl, $kindAccentMap) {
        return [
            'eyebrow' => $collection->eyebrow ?: ucfirst($collection->kind ?? 'Collection'),
            'title' => $collection->name,
            'description' => $collection->description ?: 'Curated products selected for this collection.',
            'cta' => $collection->cta_text ?: 'Shop now',
            'href' => $collectionUrl($collection),
            'image' => $resolveMediaUrl([$collection->image_path ?? null]),
            'accent' => $kindAccentMap[$collection->kind ?? 'general'] ?? $kindAccentMap['general'],
            'meta' => ($collection->products_count ?? null) ? $collection->products_count . ' items' : null,
        ];
    });

    if ($occasionCards->isEmpty()) {
        $occasionCards = collect([
            [
                'eyebrow' => 'Quick meals',
                'title' => 'Weeknight wins',
                'description' => 'Easy freezer-to-pan options for busy evenings and repeat household orders.',
                'cta' => 'Shop now',
                'href' => '#shop-highlights',
                'image' => $resolveMediaUrl(['images/home/occasion-weeknight.png', 'images/home/cooked-skillet.png']),
                'accent' => 'from-sky-50 to-cyan-50 dark:from-sky-950/30 dark:to-cyan-950/20',
                'meta' => null,
            ],
            [
                'eyebrow' => 'Sharing moments',
                'title' => 'Party starters',
                'description' => 'Crowd-pleasing bites, seafood picks, and easy entertaining favourites.',
                'cta' => 'Shop now',
                'href' => '#shop-highlights',
                'image' => $resolveMediaUrl(['images/home/occasion-party.png', 'images/home/party-platter.png']),
                'accent' => 'from-amber-50 to-orange-50 dark:from-amber-950/30 dark:to-orange-950/20',
                'meta' => null,
            ],
            [
                'eyebrow' => 'Comfort cooking',
                'title' => 'Family table favourites',
                'description' => 'Frozen staples designed for practical, tasty everyday cooking.',
                'cta' => 'Shop now',
                'href' => '#shop-highlights',
                'image' => $resolveMediaUrl(['images/home/occasion-family.png', 'images/home/family-meal.png']),
                'accent' => 'from-rose-50 to-fuchsia-50 dark:from-rose-950/30 dark:to-fuchsia-950/20',
                'meta' => null,
            ],
        ]);
    }

    $chefCollection = $chefCollection ?? ProductCollection::query()
        ->where('is_active', true)
        ->where('show_on_home', true)
        ->where('home_section', 'chef_picks')
        ->where(function ($q) {
            $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
        })
        ->where(function ($q) {
            $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
        })
        ->orderBy('home_order')
        ->first();

    $recipeFeatureProduct = $recipeFeatureProduct ?? null;

    if (!$recipeFeatureProduct && $chefCollection) {
        $recipeFeatureProduct = Product::query()
            ->with([
                'images',
                'activeRecipes' => function ($q) {
                    $q->inRandomOrder();
                },
            ])
            ->whereHas('collections', function ($q) use ($chefCollection) {
                $q->where('product_collections.id', $chefCollection->id);
            })
            ->whereHas('activeRecipes')
            ->inRandomOrder()
            ->first();
    }

    if (!$recipeFeatureProduct) {
        $recipeFeatureProduct = Product::query()
            ->with([
                'images',
                'activeRecipes' => function ($q) {
                    $q->inRandomOrder();
                },
            ])
            ->whereHas('activeRecipes')
            ->inRandomOrder()
            ->first();
    }

    $featuredRecipeProduct = $recipeFeatureProduct?->loadMissing(['images', 'activeRecipes']);
    $featuredRecipe = $featuredRecipeProduct?->activeRecipes?->first();

    $featuredRecipeTitle = $featuredRecipe
        ? ($recipeText($featuredRecipe, 'title') ?: 'Recipe inspiration')
        : 'Recipe inspiration';

    $featuredRecipeShort = $featuredRecipe
        ? $recipeText($featuredRecipe, 'short_description')
        : null;

    $featuredRecipeDescription = $featuredRecipe
        ? $recipeText($featuredRecipe, 'description')
        : null;

    $featuredRecipeIngredients = $featuredRecipe
        ? $recipeList($featuredRecipe, 'ingredients')
        : [];

    $featuredRecipeSteps = $featuredRecipe
        ? $recipeList($featuredRecipe, 'steps')
        : [];

    $featuredRecipeImage = $featuredRecipe
        ? (
            $resolveMediaUrl([
                $featuredRecipe->image_path ?? null,
            ]) ?: (
                $featuredRecipeProduct
                    ? $productPrimaryImageUrl($featuredRecipeProduct)
                    : null
            )
        )
        : (
            $featuredRecipeProduct
                ? $productPrimaryImageUrl($featuredRecipeProduct)
                : null
        );

    $featuredRecipeIngredientsTeaser = collect($featuredRecipeIngredients)->take(4)->values()->all();
    $featuredRecipeStepsTeaser = collect($featuredRecipeSteps)->take(3)->values()->all();

    $chefSpotlightTitle = $chefCollection?->name ?: 'Chef notes, serving ideas, and practical cooking tips.';
    $chefSpotlightDescription = $chefCollection?->description ?: 'Use this space to explain how to finish, plate, or serve your frozen products in a more inspiring way.';
    $chefSpotlightEyebrow = $chefCollection?->eyebrow ?: 'Chef spotlight';
    $chefSpotlightImage = $chefCollection
        ? $resolveMediaUrl([$chefCollection->image_path ?? null])
        : null;

    $chefSpotlightCtaText = $chefCollection?->cta_text ?: 'Browse chef picks';
    $chefSpotlightCtaUrl = null;

    if ($chefCollection) {
        if (!empty($chefCollection->cta_url)) {
            $chefSpotlightCtaUrl = $chefCollection->cta_url;
        } elseif (Route::has('collections.show') && !empty($chefCollection->slug)) {
            $chefSpotlightCtaUrl = route('collections.show', ['collection' => $chefCollection->slug]);
        } else {
            $chefSpotlightCtaUrl = $shopUrl;
        }
    }

    $homeProductShowcase = Product::query()
        ->with(['images'])
        ->withCount('variants')
        ->where('is_active', true)
        ->where(function ($q) {
            $q->where('is_special', true)
              ->orWhere('is_featured', true)
              ->orWhere('is_new', true);
        })
        ->orderByDesc('is_special')
        ->orderByDesc('is_featured')
        ->orderByDesc('is_new')
        ->latest()
        ->take(8)
        ->get();

    $trustItems = [
        [
            'icon' => '🧾',
            'title' => 'GST-ready ordering',
            'text' => 'Invoice-friendly checkout for repeat buying and business-friendly orders.',
            'accent' => 'bg-sky-50 border-sky-100 dark:bg-sky-950/20 dark:border-sky-900/40',
        ],
        [
            'icon' => '❄️',
            'title' => 'Frozen-first experience',
            'text' => 'A storefront built around frozen storage, practical cooking, and easy browsing.',
            'accent' => 'bg-cyan-50 border-cyan-100 dark:bg-cyan-950/20 dark:border-cyan-900/40',
        ],
        [
            'icon' => '📦',
            'title' => 'Bulk-order friendly',
            'text' => 'Useful for households, events, and repeat larger-volume buying patterns.',
            'accent' => 'bg-amber-50 border-amber-100 dark:bg-amber-950/20 dark:border-amber-900/40',
        ],
        [
            'icon' => '👨‍🍳',
            'title' => 'Recipe-led discovery',
            'text' => 'Connect products with dishes, serving ideas, and real usage moments.',
            'accent' => 'bg-rose-50 border-rose-100 dark:bg-rose-950/20 dark:border-rose-900/40',
        ],
    ];
@endphp

<div class="bg-gray-50 dark:bg-gray-950 min-h-screen">
    <div class="max-w-6xl mx-auto px-4 py-6 space-y-6">
        @if($homeAnnouncement)
            @include('partials.home_cards.announcement_banner', [
                'announcement' => $homeAnnouncement,
            ])
        @endif

        {{-- Hero --}}
        <section class="grid gap-4 lg:grid-cols-[1.05fr,0.95fr] items-stretch">
            <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-gradient-to-br from-white via-slate-50 to-sky-50 dark:from-gray-900 dark:via-gray-900 dark:to-slate-900 p-6 sm:p-8 flex flex-col justify-between min-h-[320px]">
                <div class="space-y-5">
                    <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white/90 dark:bg-gray-950/50 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.14em] text-gray-600 dark:text-gray-300">
                        Frozen • Bandara by Maytira
                    </span>

                    <div class="space-y-3">
                        <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-gray-900 dark:text-gray-50 leading-tight">
                            Frozen favourites for everyday cooking and special gatherings.
                        </h1>

                        <p class="max-w-xl text-sm sm:text-base text-gray-600 dark:text-gray-300 leading-relaxed">
                            Shop frozen and chilled products, discover chef-led serving ideas, and order confidently for home or business.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3 text-sm">
                        <a href="{{ $shopUrl }}"
                           class="inline-flex items-center justify-center rounded-sm bg-gray-900 px-4 py-2.5 font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">
                            Shop all products
                        </a>

                        <a href="#occasions"
                           class="inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-4 py-2.5 font-medium text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-800">
                            Browse collections
                        </a>
                    </div>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-950/40 px-4 py-3">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">Better discovery</div>
                        <div class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-50">Visual product browsing</div>
                    </div>
                    <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-950/40 px-4 py-3">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">Business ready</div>
                        <div class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-50">GST-ready invoices</div>
                    </div>
                    <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-950/40 px-4 py-3">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">Cook with confidence</div>
                        <div class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-50">Chef-led inspiration</div>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 max-h-[340px]">
                @if($heroVisualImage)
                    <img
                        src="{{ $heroVisualImage }}"
                        alt="Frozen Bandara hero"
                        class="w-full object-cover min-h-[340px] max-h-[340px]"
                    >
                @else
                    <div class="absolute inset-0 bg-gradient-to-br from-sky-100 via-cyan-50 to-white dark:from-sky-950/30 dark:via-cyan-950/20 dark:to-gray-900"></div>
                @endif

                <div class="absolute inset-x-0 bottom-0 p-4">
                    <div class="rounded-sm border border-white/70 bg-white/90 backdrop-blur px-4 py-3 shadow-sm dark:border-gray-700/60 dark:bg-gray-950/70">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">From freezer to table</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">
                            Practical frozen products for everyday cooking, entertaining, and repeat ordering.
                        </div>
                    </div>
                </div>
            </div>
            {{-- <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden min-h-[320px] relative">
                @if($heroVisualImage)
                    <img
                        src="{{ $heroVisualImage }}"
                        alt="Bandara homepage visual"
                        class="h-full w-full object-cover"
                    >
                @else
                    <div class="absolute inset-0 bg-gradient-to-br from-sky-100 via-cyan-50 to-white dark:from-sky-950/30 dark:via-cyan-950/20 dark:to-gray-900"></div>
                @endif

                <div class="absolute inset-x-0 bottom-0 p-4">
                    <div class="rounded-sm border border-white/70 bg-white/88 backdrop-blur px-4 py-3 shadow-sm dark:border-gray-700/60 dark:bg-gray-950/70">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">From freezer to table</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">
                            Practical frozen products for everyday cooking, entertaining, and repeat ordering.
                        </div>
                    </div>
                </div>
            </div> --}}
        </section>

        {{-- Quick categories --}}
        <section id="categories" class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Browse by category</h2>
                <a href="{{ $shopUrl }}" class="text-[11px] text-gray-600 dark:text-gray-300 hover:underline">View all</a>
            </div>

            @if($topCategories->isEmpty())
                <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 text-[12px] text-gray-500 dark:text-gray-400">
                    No categories yet. Add some in admin to show them here.
                </div>
            @else
                <div class="grid grid-cols-4 sm:grid-cols-6 lg:grid-cols-8 gap-3">
                    @foreach($topCategories as $cat)
                        <a href="{{ $categoryUrl($cat) }}"
                           class="group rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-3 hover:bg-gray-50 dark:hover:bg-gray-900/60 transition">
                            <div class="flex flex-col items-center text-center gap-2">
                                <div class="h-12 w-12 rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 flex items-center justify-center">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                                        {{ mb_strtoupper(mb_substr($cat->name ?? 'C', 0, 1)) }}
                                    </span>
                                </div>
                                <div class="text-[11px] font-medium text-gray-900 dark:text-gray-50 leading-tight line-clamp-2">
                                    {{ $cat->name }}
                                </div>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                    {{ (int)($cat->products_count ?? 0) }} items
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Unified highlights --}}
        <section id="shop-highlights" class="space-y-4 scroll-mt-24">
            {{-- Backward-compatible anchors --}}
            <div id="featured" class="scroll-mt-24"></div>
            <div id="new" class="scroll-mt-24"></div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">Shop highlights</p>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-50">Popular picks from the Bandara range</h2>
                    <p class="mt-1 max-w-2xl text-sm text-gray-600 dark:text-gray-300">
                        A curated mix of everyday staples, entertaining favourites, and standout products from across the catalogue.
                    </p>
                </div>

                <a href="{{ $shopUrl }}"
                   class="inline-flex items-center text-sm font-medium text-gray-700 dark:text-gray-200 hover:underline">
                    View full shop
                </a>
            </div>

            @if($homeProductShowcase->isNotEmpty())
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($homeProductShowcase as $product)
                        @include('partials.home_cards.product_card', [
                            'product' => $product,
                            'cartAddUrl' => $cartAddUrl,
                            'wishlistToggleUrl' => $wishlistToggleUrl,
                            'wishlistUrl' => $wishlistUrl,
                            'loginUrl' => $loginUrl,
                            'singleCard' => false,
                            'flagEmoji' => $flagEmoji,
                        ])
                    @endforeach
                </div>
            @else
                <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                    Highlighted products will appear here once they are added in admin.
                </div>
            @endif
        </section>

        {{-- Shop by occasion --}}
        <section id="occasions" class="space-y-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">Shop by occasion</p>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-50">Find the right products for the moment</h2>
                    <p class="mt-1 max-w-2xl text-sm text-gray-600 dark:text-gray-300">
                        Explore curated collections built around quick meals, entertaining, and everyday freezer staples.
                    </p>
                </div>

                <a href="{{ $shopUrl }}"
                   class="inline-flex items-center text-sm font-medium text-gray-700 dark:text-gray-200 hover:underline">
                    View full shop
                </a>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                @foreach($occasionCards as $card)
                    <a href="{{ $card['href'] }}"
                       class="group overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 transition hover:-translate-y-0.5">
                        <div class="relative aspect-[4/3] overflow-hidden bg-gradient-to-br {{ $card['accent'] }}">
                            @if($card['image'])
                                <img
                                    src="{{ $card['image'] }}"
                                    alt="{{ $card['title'] }}"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]"
                                >
                            @else
                                <div class="absolute inset-0 bg-gradient-to-br {{ $card['accent'] }}"></div>
                            @endif
                        </div>

                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="text-[11px] uppercase tracking-wide text-gray-400">
                                    {{ $card['eyebrow'] }}
                                </div>

                                @if(!empty($card['meta']))
                                    <div class="text-[10px] text-gray-400">
                                        {{ $card['meta'] }}
                                    </div>
                                @endif
                            </div>

                            <h3 class="mt-2 text-xl font-semibold text-gray-900 dark:text-gray-50">
                                {{ $card['title'] }}
                            </h3>

                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                                {{ $card['description'] }}
                            </p>

                            <div class="mt-4 inline-flex items-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-2 text-xs font-medium text-gray-700 dark:text-gray-200 group-hover:bg-gray-50 dark:group-hover:bg-gray-800">
                                {{ $card['cta'] }}
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- Chef picks --}}
        <section id="chef-picks" class="space-y-4">
            <div>
                <p class="text-[11px] uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">Chef picks</p>
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-50">Cook with more confidence</h2>
                <p class="mt-1 max-w-2xl text-sm text-gray-600 dark:text-gray-300">
                    Discover chef-led serving ideas and one featured recipe built around products from the Bandara range.
                </p>
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
                        @elseif($heroChefImage ?? $heroPackImage)
                            <img
                                src="{{ $heroChefImage ?: $heroPackImage }}"
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
                                Great for air fryer, pan, or oven-finish cooking
                            </div>
                            <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                Useful for weekday meals, platters, and sharing
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

        {{-- Trust --}}
        <section class="space-y-4">
            <div>
                <p class="text-[11px] uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">Shop with confidence</p>
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-50">Clear information before the order</h2>
                <p class="mt-1 max-w-2xl text-sm text-gray-600 dark:text-gray-300">
                    Give customers confidence with clear pricing, practical product details, and support when they need it.
                </p>
            </div>

            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                @foreach($trustItems as $item)
                    <div class="rounded-lg border px-4 py-4 {{ $item['accent'] }}">
                        <div class="text-2xl">{{ $item['icon'] }}</div>
                        <div class="mt-3 text-sm font-semibold text-gray-900 dark:text-gray-50">
                            {{ $item['title'] }}
                        </div>
                        <p class="mt-1 text-xs leading-relaxed text-gray-600 dark:text-gray-300">
                            {{ $item['text'] }}
                        </p>
                    </div>
                @endforeach
            </div>

            <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-5 py-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                            Need help before ordering?
                        </div>
                        <p class="mt-1 text-[12px] text-gray-600 dark:text-gray-300">
                            Get help with product selection, storage guidance, business orders, or support queries.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @if($supportUrl)
                            <a href="{{ $supportUrl }}"
                               class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                                Contact support
                            </a>
                        @endif

                        <a href="#occasions"
                           class="inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-4 py-2 text-[11px] font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                            Browse collections
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

@if(Route::has('product.variants.options'))
<script>
(function () {
    function setVariant(card, variantId) {
        card.querySelectorAll('input.js-variant-input').forEach(inp => inp.value = variantId || '');
        const btn = card.querySelector('form.js-cart-form button.js-cart-btn');
        if (btn) {
            btn.disabled = !variantId || btn.hasAttribute('data-force-disabled');
            btn.classList.toggle('opacity-40', btn.disabled);
        }
    }

    async function hydrateCard(card) {
        const sel = card.querySelector('select.js-variant-select');
        if (!sel) return;

        const url = sel.dataset.url;
        const hint = card.querySelector('.js-variant-hint');

        sel.innerHTML = '<option value="">Loading…</option>';
        sel.disabled = true;

        const btn = card.querySelector('form.js-cart-form button.js-cart-btn');
        if (btn) {
            btn.disabled = true;
            btn.classList.add('opacity-40');
        }

        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('bad');
            const data = await res.json();
            const list = Array.isArray(data.variants) ? data.variants : [];

            sel.innerHTML = '<option value="">Choose option</option>';

            if (!list.length) {
                sel.innerHTML = '<option value="">No slabs available</option>';
                if (hint) hint.textContent = 'No available slabs in stock.';
                sel.disabled = true;
                setVariant(card, '');
                return;
            }

            list.forEach(v => {
                const opt = document.createElement('option');
                opt.value = v.id;
                opt.textContent = v.label || ('Slab #' + v.id);
                sel.appendChild(opt);
            });

            sel.disabled = false;
            if (hint) hint.textContent = 'Select & add to cart.';
        } catch (e) {
            sel.innerHTML = '<option value="">Unable to load slabs</option>';
            sel.disabled = true;
            if (hint) hint.textContent = 'Could not load slab list.';
            setVariant(card, '');
            return;
        }

        sel.addEventListener('change', () => setVariant(card, sel.value));
    }

    document.querySelectorAll('.js-product-card').forEach(hydrateCard);
})();
</script>
@endif
@endsection