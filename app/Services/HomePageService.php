<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\HomeSectionItem;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\Recipe;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HomePageService
{
    public function build(): array
    {
        $sections = HomeSection::query()
            ->visible()
            ->with(['activeItems.linked'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->keyBy('key');

        $sections = $this->withFrontendDefaults($sections);

        $chefSection = $sections->get('chef_picks');

        return [
            'homeSections' => $sections,
            'homeAnnouncement' => Announcement::activeForHome()->first(),
            'topCategories' => $this->topCategories($sections->get('categories')),
            'homeProductShowcase' => $this->productShowcase($sections->get('shop_highlights')),
            'occasionCards' => $this->occasionCards($sections->get('occasions')),
            'occasionCollections' => $this->occasionCollections($sections->get('occasions')), // Backwards-compatible for older partials.
            'chefCollection' => $this->chefCollection($chefSection),
            'recipeProducts' => $this->recipeProducts($chefSection),
            'recipeFeatureProduct' => $this->recipeFeatureProduct($chefSection),
            'mediaUrlResolver' => fn ($pathOrPaths) => $this->mediaUrl($pathOrPaths),
        ];
    }


    protected function withFrontendDefaults(Collection $sections): Collection
    {
        $defaults = $this->sectionDefaults();

        foreach ($sections as $key => $section) {
            $sectionDefaults = $defaults[$key] ?? null;

            if (! $sectionDefaults) {
                continue;
            }

            foreach (['eyebrow', 'title', 'subtitle', 'body', 'cta_text', 'cta_url', 'secondary_cta_text', 'secondary_cta_url', 'image_path', 'mobile_image_path', 'layout'] as $field) {
                if (! filled($section->{$field}) && array_key_exists($field, $sectionDefaults)) {
                    $section->setAttribute($field, $sectionDefaults[$field]);
                }
            }

            if (array_key_exists('settings', $sectionDefaults)) {
                $section->setAttribute('settings', array_replace_recursive($sectionDefaults['settings'], $section->settings ?? []));
            }

            if (($section->relationLoaded('activeItems') ? $section->activeItems : collect())->isEmpty() && ! empty($sectionDefaults['items'])) {
                $items = collect($sectionDefaults['items'])->map(function (array $item, int $index) use ($section) {
                    $item['home_section_id'] = $section->id;
                    $item['is_active'] = true;
                    $item['sort_order'] = $item['sort_order'] ?? (($index + 1) * 10);

                    return new HomeSectionItem($item);
                });

                $section->setRelation('activeItems', $items);
            }
        }

        return $sections;
    }

    protected function sectionDefaults(): array
    {
        return [
            'hero' => [
                'eyebrow' => 'Frozen • Bandara by Maytira',
                'title' => 'Frozen favourites for everyday cooking and special gatherings.',
                'subtitle' => 'Shop frozen and chilled products, discover chef-led serving ideas, and order confidently for home or business.',
                'cta_text' => 'Shop all products',
                'cta_url' => '/shop',
                'secondary_cta_text' => 'Browse collections',
                'secondary_cta_url' => '#occasions',
                'settings' => [
                    'overlay_eyebrow' => 'From freezer to table',
                    'overlay_title' => 'Practical frozen products for everyday cooking, entertaining, and repeat ordering.',
                    'fallback_images' => ['images/home/hero-main.png', 'images/home/frozen-hero.png', 'images/hero/frozen-hero.png', 'images/home/product-pack.png'],
                ],
                'items' => [
                    ['item_type' => 'stat', 'title' => 'Better discovery', 'description' => 'Visual product browsing', 'settings' => ['label' => 'Better discovery'], 'sort_order' => 10],
                    ['item_type' => 'stat', 'title' => 'Business ready', 'description' => 'GST-ready invoices', 'settings' => ['label' => 'Business ready'], 'sort_order' => 20],
                    ['item_type' => 'stat', 'title' => 'Cook with confidence', 'description' => 'Chef-led inspiration', 'settings' => ['label' => 'Cook with confidence'], 'sort_order' => 30],
                ],
            ],
            'categories' => [
                'title' => 'Browse by category',
                'cta_text' => 'View all',
                'cta_url' => '/shop',
                'settings' => ['limit' => 8, 'show_counts' => true],
            ],
            'shop_highlights' => [
                'eyebrow' => 'Shop highlights',
                'title' => 'Popular frozen picks',
                'subtitle' => 'Featured, new, and special products selected from the active catalogue.',
                'cta_text' => 'Shop all products',
                'cta_url' => '/shop',
                'settings' => ['limit' => 8, 'mode' => 'featured_new_special'],
            ],
            'occasions' => [
                'eyebrow' => 'Shop by occasion',
                'title' => 'Curated collections for every plan',
                'subtitle' => 'Use collections to group products around occasions, meal plans, buying moments, or business requirements.',
                'settings' => ['home_section' => 'occasions', 'limit' => 6],
            ],
            'chef_picks' => [
                'eyebrow' => 'Chef picks',
                'title' => 'Serving ideas and recipe-led discovery',
                'subtitle' => 'Bring products to life with chef notes, recipe highlights, and practical cooking guidance.',
                'settings' => ['collection_home_section' => 'chef_picks', 'recipe_limit' => 3],
            ],
            'trust' => [
                'eyebrow' => 'Shop with confidence',
                'title' => 'Clear information before the order',
                'subtitle' => 'Give customers confidence with clear pricing, practical product details, and support when they need it.',
                'items' => [
                    ['item_type' => 'trust', 'title' => 'GST-ready ordering', 'description' => 'Invoice-friendly checkout for repeat buying and business-friendly orders.', 'icon' => '🧾', 'settings' => ['accent' => 'bg-sky-50 border-sky-100 dark:bg-sky-950/20 dark:border-sky-900/40'], 'sort_order' => 10],
                    ['item_type' => 'trust', 'title' => 'Frozen-first experience', 'description' => 'A storefront built around frozen storage, practical cooking, and easy browsing.', 'icon' => '❄️', 'settings' => ['accent' => 'bg-cyan-50 border-cyan-100 dark:bg-cyan-950/20 dark:border-cyan-900/40'], 'sort_order' => 20],
                    ['item_type' => 'trust', 'title' => 'Bulk-order friendly', 'description' => 'Useful for households, events, and repeat larger-volume buying patterns.', 'icon' => '📦', 'settings' => ['accent' => 'bg-amber-50 border-amber-100 dark:bg-amber-950/20 dark:border-amber-900/40'], 'sort_order' => 30],
                    ['item_type' => 'trust', 'title' => 'Recipe-led discovery', 'description' => 'Connect products with dishes, serving ideas, and real usage moments.', 'icon' => '👨‍🍳', 'settings' => ['accent' => 'bg-rose-50 border-rose-100 dark:bg-rose-950/20 dark:border-rose-900/40'], 'sort_order' => 40],
                ],
            ],
            'support_cta' => [
                'title' => 'Need help before ordering?',
                'subtitle' => 'Get help with product selection, storage guidance, business orders, or support queries.',
                'cta_text' => 'Contact support',
                'cta_url' => '/tickets/create',
                'secondary_cta_text' => 'Browse collections',
                'secondary_cta_url' => '#occasions',
            ],
        ];
    }

    public function mediaUrl(string|array|null $pathOrPaths): ?string
    {
        $candidates = is_array($pathOrPaths) ? $pathOrPaths : [$pathOrPaths];

        foreach ($candidates as $candidate) {
            if (! filled($candidate)) {
                continue;
            }

            $candidate = trim((string) $candidate);

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
    }

    protected function topCategories(?HomeSection $section): Collection
    {
        $limit = (int) ($section?->getSetting('limit', 8) ?? 8);
        $manualCategories = $this->linkedCategories($section, $limit);

        if ($manualCategories->isNotEmpty()) {
            return $manualCategories;
        }

        return Category::query()
            ->where('is_active', true)
            ->withCount('products')
            ->orderByDesc('products_count')
            ->orderBy('name')
            ->limit(max($limit, 1))
            ->get();
    }

    protected function productShowcase(?HomeSection $section): Collection
    {
        $limit = (int) ($section?->getSetting('limit', 8) ?? 8);
        $mode = (string) ($section?->getSetting('mode', 'featured_new_special') ?? 'featured_new_special');

        $manualProducts = $this->linkedProducts($section, $limit);
        if ($mode === 'manual_items' || $manualProducts->isNotEmpty()) {
            return $manualProducts;
        }

        if ($mode === 'collection' && filled($section?->getSetting('collection_id'))) {
            return $this->productsFromCollection((int) $section->getSetting('collection_id'), $limit);
        }

        if ($mode === 'category' && filled($section?->getSetting('category_id'))) {
            return $this->productsFromCategory((int) $section->getSetting('category_id'), $limit);
        }

        $query = Product::query()
            ->with(['images'])
            ->withCount('variants')
            ->where('is_active', true);

        if ($mode === 'featured_only') {
            $query->where('is_featured', true)->orderByDesc('is_featured');
        } elseif ($mode === 'new_only') {
            $query->where('is_new', true)->orderByDesc('is_new');
        } elseif ($mode === 'special_only') {
            $query->where('is_special', true)->orderByDesc('is_special');
        } elseif ($mode === 'latest') {
            $query->latest();
        } else {
            $query->where(function ($q) {
                $q->where('is_special', true)
                    ->orWhere('is_featured', true)
                    ->orWhere('is_new', true);
            })
                ->orderByDesc('is_special')
                ->orderByDesc('is_featured')
                ->orderByDesc('is_new')
                ->latest();
        }

        return $query->take(max($limit, 1))->get();
    }


    protected function occasionCards(?HomeSection $section): Collection
    {
        $manualItems = ($section?->activeItems ?? collect())
            ->filter(function (HomeSectionItem $item) {
                return $item->linked_type === ProductCollection::class
                    || filled($item->title)
                    || filled($item->description)
                    || filled($item->image_path)
                    || filled($item->cta_text)
                    || filled($item->cta_url);
            })
            ->values();

        $autoCards = $this->occasionCollections($section)
            ->map(fn (ProductCollection $collection) => $this->occasionCardFromCollection($collection))
            ->values();

        if ($manualItems->isEmpty()) {
            return $autoCards;
        }

        $manualCards = $manualItems->map(fn (HomeSectionItem $item) => $this->occasionCardFromItem($item));
        $manualCollectionIds = $manualCards
            ->map(fn ($card) => $card->collection?->id)
            ->filter()
            ->values();

        $cards = $manualCards->concat(
            $autoCards->reject(fn ($card) => $card->collection && $manualCollectionIds->contains($card->collection->id))
        )->values();

        $manualLimit = (int) ($section?->getSetting('manual_limit', 0) ?? 0);
        if ($manualLimit > 0) {
            return $cards->take($manualLimit)->values();
        }

        $limit = (int) ($section?->getSetting('limit', 6) ?? 6);

        return $cards->take(max($limit, $manualCards->count()))->values();
    }

    protected function occasionCardFromItem(HomeSectionItem $item): object
    {
        $collection = $item->linked_type === ProductCollection::class ? $item->linked : null;

        if ($collection instanceof ProductCollection && ! array_key_exists('products_count', $collection->getAttributes())) {
            $collection->loadCount('products');
        }

        return (object) [
            'eyebrow' => $item->eyebrow ?: ($collection?->eyebrow),
            'title' => $item->title ?: ($collection?->name),
            'description' => $item->description ?: ($collection?->description),
            'image_path' => $item->image_path ?: ($collection?->image_path),
            'cta_text' => $item->cta_text ?: ($collection?->cta_text),
            'cta_url' => $item->cta_url ?: ($collection?->cta_url),
            'products_count' => $collection?->products_count,
            'collection' => $collection,
        ];
    }

    protected function occasionCardFromCollection(ProductCollection $collection): object
    {
        return (object) [
            'eyebrow' => $collection->eyebrow,
            'title' => $collection->name,
            'description' => $collection->description,
            'image_path' => $collection->image_path,
            'cta_text' => $collection->cta_text,
            'cta_url' => $collection->cta_url,
            'products_count' => $collection->products_count,
            'collection' => $collection,
        ];
    }

    protected function occasionCollections(?HomeSection $section): Collection
    {
        $limit = (int) ($section?->getSetting('limit', 3) ?? 3);
        $manualCollections = $this->linkedCollections($section, $limit);

        if ($manualCollections->isNotEmpty()) {
            return $manualCollections;
        }

        $homeSection = (string) ($section?->getSetting('home_section', 'occasions') ?? 'occasions');

        return ProductCollection::query()
            ->withCount('products')
            ->homeSection($homeSection)
            ->take(max($limit, 1))
            ->get();
    }

    protected function chefCollection(?HomeSection $section): ?ProductCollection
    {
        $manualCollection = $this->linkedCollections($section, 1)->first();
        if ($manualCollection) {
            return $manualCollection;
        }

        $homeSection = (string) ($section?->getSetting('collection_home_section', 'chef_picks') ?? 'chef_picks');

        return ProductCollection::query()
            ->withCount('products')
            ->homeSection($homeSection)
            ->first();
    }

    protected function recipeProducts(?HomeSection $section): Collection
    {
        $limit = (int) ($section?->getSetting('recipe_limit', 3) ?? 3);
        $manualProducts = $this->linkedProducts($section, $limit, requireRecipes: true);

        if ($manualProducts->isNotEmpty()) {
            return $manualProducts;
        }

        return Product::query()
            ->with(['images', 'activeRecipes'])
            ->where('is_active', true)
            ->whereHas('activeRecipes')
            ->orderByDesc('is_featured')
            ->latest()
            ->take(max($limit, 1))
            ->get();
    }

    protected function recipeFeatureProduct(?HomeSection $section): ?Product
    {
        $manualRecipe = $this->linkedRecipes($section, 1)->first();
        if ($manualRecipe) {
            $product = Product::query()
                ->with([
                    'images',
                    'activeRecipes' => fn ($query) => $query->where('recipes.id', $manualRecipe->id),
                ])
                ->where('is_active', true)
                ->whereHas('activeRecipes', fn ($query) => $query->where('recipes.id', $manualRecipe->id))
                ->first();

            if ($product) {
                return $product;
            }
        }

        $manualProduct = $this->linkedProducts($section, 1, requireRecipes: true)->first();
        if ($manualProduct) {
            return $manualProduct;
        }

        return Product::query()
            ->with([
                'images',
                'activeRecipes' => function ($q) {
                    $q->inRandomOrder();
                },
            ])
            ->where('is_active', true)
            ->whereHas('activeRecipes')
            ->inRandomOrder()
            ->first();
    }

    protected function linkedProducts(?HomeSection $section, int $limit, bool $requireRecipes = false): Collection
    {
        $ids = $this->linkedIds($section, Product::class);

        if ($ids->isEmpty()) {
            return collect();
        }

        $products = Product::query()
            ->with(['images', 'activeRecipes'])
            ->withCount('variants')
            ->where('is_active', true)
            ->when($requireRecipes, fn ($query) => $query->whereHas('activeRecipes'))
            ->whereIn('id', $ids->keys())
            ->get()
            ->sortBy(fn (Product $product) => $ids->get($product->id))
            ->values();

        return $products->take(max($limit, 1));
    }

    protected function linkedCategories(?HomeSection $section, int $limit): Collection
    {
        $ids = $this->linkedIds($section, Category::class);

        if ($ids->isEmpty()) {
            return collect();
        }

        return Category::query()
            ->where('is_active', true)
            ->withCount('products')
            ->whereIn('id', $ids->keys())
            ->get()
            ->sortBy(fn (Category $category) => $ids->get($category->id))
            ->values()
            ->take(max($limit, 1));
    }

    protected function linkedCollections(?HomeSection $section, int $limit): Collection
    {
        $ids = $this->linkedIds($section, ProductCollection::class);

        if ($ids->isEmpty()) {
            return collect();
        }

        return ProductCollection::query()
            ->active()
            ->withCount('products')
            ->whereIn('id', $ids->keys())
            ->get()
            ->sortBy(fn (ProductCollection $collection) => $ids->get($collection->id))
            ->values()
            ->take(max($limit, 1));
    }

    protected function linkedRecipes(?HomeSection $section, int $limit): Collection
    {
        $ids = $this->linkedIds($section, Recipe::class);

        if ($ids->isEmpty()) {
            return collect();
        }

        return Recipe::query()
            ->active()
            ->whereIn('id', $ids->keys())
            ->get()
            ->sortBy(fn (Recipe $recipe) => $ids->get($recipe->id))
            ->values()
            ->take(max($limit, 1));
    }

    protected function linkedIds(?HomeSection $section, string $class): Collection
    {
        if (! $section) {
            return collect();
        }

        return $section->activeItems
            ->where('linked_type', $class)
            ->whereNotNull('linked_id')
            ->values()
            ->mapWithKeys(fn ($item, $index) => [(int) $item->linked_id => (int) $item->sort_order ?: $index + 1]);
    }

    protected function productsFromCollection(int $collectionId, int $limit): Collection
    {
        $collection = ProductCollection::query()
            ->active()
            ->with(['products' => function ($query) use ($limit) {
                $query->with(['images'])
                    ->withCount('variants')
                    ->where('products.is_active', true)
                    ->limit(max($limit, 1));
            }])
            ->find($collectionId);

        return $collection?->products ?? collect();
    }

    protected function productsFromCategory(int $categoryId, int $limit): Collection
    {
        return Product::query()
            ->with(['images'])
            ->withCount('variants')
            ->where('is_active', true)
            ->whereHas('categories', fn ($query) => $query->where('categories.id', $categoryId))
            ->latest()
            ->take(max($limit, 1))
            ->get();
    }
}
