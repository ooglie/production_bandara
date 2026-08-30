@extends('layouts.customer')

@section('title', ($collection->name ?? config('app.name')))

@section('content')
@php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $has = fn (string $routeName) => Route::has($routeName);

    /* Relative URLs preserve the storefront URL-safety baseline. */
    $homeUrl = $has('home') ? route('home', [], false) : '/';
    $shopUrl = $has('shop.index') ? route('shop.index', [], false) : '/shop';

    $productUrl = function ($product) use ($has) {
        return $has('product.show')
            ? route('product.show', $product->slug ?? $product, false)
            : '#';
    };

    $resolveMediaUrl = function ($pathOrPaths) {
        foreach ((array) $pathOrPaths as $candidate) {
            if (!$candidate) {
                continue;
            }

            $candidate = trim((string) $candidate);

            if ($candidate === '') {
                continue;
            }

            /* Convert stale absolute /storage URLs to host-relative URLs. */
            if (preg_match('#^https?://[^/]+(/storage/.*)$#i', $candidate, $matches)) {
                return $matches[1];
            }

            if (Str::startsWith($candidate, ['http://', 'https://'])) {
                return $candidate;
            }

            if (Str::startsWith($candidate, '/storage/')) {
                return $candidate;
            }

            if (Str::startsWith($candidate, 'storage/app/public/')) {
                return '/storage/' . ltrim(Str::after($candidate, 'storage/app/public/'), '/');
            }

            if (Str::startsWith($candidate, 'storage/')) {
                return '/' . ltrim($candidate, '/');
            }

            if (Str::startsWith($candidate, '/')) {
                $publicRelative = ltrim($candidate, '/');

                return file_exists(public_path($publicRelative))
                    ? '/' . $publicRelative
                    : $candidate;
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
        $images = collect($product->images ?? []);

        return $resolveMediaUrl([
            $product->primary_image_url ?? null,
            $product->primary_image ?? null,
            $product->image_path ?? null,
            optional($images->firstWhere('is_primary', true))->file_path,
            optional($images->first())->file_path,
        ]);
    };

    $products = $products
        ?? ($collection->relationLoaded('products')
            ? $collection->products
            : ($collection->products ?? collect()));

    if (is_array($products)) {
        $products = collect($products);
    }

    $productCount = method_exists($products, 'total')
        ? (int) $products->total()
        : (int) $products->count();

    $collectionLabel = trim((string) ($collection->eyebrow ?? ''));

    if ($collectionLabel === '') {
        $collectionLabel = !empty($collection->kind)
            ? Str::headline($collection->kind)
            : 'Curated collection';
    }

    $collectionDescription = trim((string) ($collection->description ?? ''));

    if ($collectionDescription === '') {
        $collectionDescription = 'A considered selection of Bandara products, brought together for this occasion.';
    }

    $collectionContext = Str::lower(implode(' ', array_filter([
        $collection->slug ?? null,
        $collection->name ?? null,
        $collection->eyebrow ?? null,
        $collection->kind ?? null,
    ])));

    $collectionFallback = match (true) {
        Str::contains($collectionContext, ['party', 'entertain', 'starter', 'celebrat'])
            => 'images/home/party-platter.png',
        Str::contains($collectionContext, ['family', 'everyday', 'staple', 'household'])
            => 'images/home/family-meal.png',
        default
            => 'images/home/occasion-weeknight.png',
    };

    $collectionImage = $resolveMediaUrl([
        $collection->image_path ?? null,
        $collection->image_url ?? null,
        $collection->hero_image_path ?? null,
        $collection->cover_image_path ?? null,
        $collectionFallback,
    ]);

    $collectionSlug = Str::lower(trim((string) ($collection->slug ?? '')));

    /*
     * Editorial content for the three homepage occasion collections.
     * Other collections, including Chef Picks, retain the existing layout below.
     */
    $occasionContent = [
        'weeknight-wins' => [
            'title' => 'Weeknight wins',
            'eyebrow' => 'Quick meals',
            'headline' => 'Make the evening easier.',
            'intro' => 'A flexible edit of easy starters, versatile ingredients and satisfying favourites for dinners that need less planning without feeling ordinary.',
            'guidance_title' => 'Build dinner around the time you have.',
            'guidance' => 'Start with one dependable centrepiece, add a quick extra and choose the simplest preparation for the evening. The collection is designed to help you assemble a complete plate without planning several dishes.',
            'guidance_steps' => [
                ['title' => 'Choose the centrepiece', 'copy' => 'Begin with the meat, seafood or ready-to-cook favourite you want to serve.'],
                ['title' => 'Add one easy extra', 'copy' => 'Pair it with a starter, cheese or accompaniment that needs very little preparation.'],
                ['title' => 'Cook only what you need', 'copy' => 'Steam, sear, grill or assemble in smaller portions and keep the rest ready for another meal.'],
            ],
            'products_title' => 'Easy choices for the evening',
            'products_copy' => 'Flexible favourites for quick plates, satisfying dinners and smaller meals. Open any product to see its available formats, current pricing and full information.',
            'card_copy' => 'Flexible favourites for satisfying dinners and evenings that need less planning.',
            'image' => 'images/home/occasion-weeknight.png',
            'benefits' => [
                ['title' => 'Less planning', 'copy' => 'Flexible choices for busy evenings.'],
                ['title' => 'Easy combinations', 'copy' => 'Starters, mains and accompaniments in one edit.'],
                ['title' => 'Selected with care', 'copy' => 'A focused collection rather than an endless catalogue.'],
            ],
        ],
        'party-starters' => [
            'title' => 'Entertaining',
            'eyebrow' => 'Party starters',
            'headline' => 'Bring more to the table with less last-minute work.',
            'intro' => 'Crowd-friendly bites, cheeses and easy sides that are simple to portion, quick to serve and made for passing around.',
            'guidance_title' => 'Build a generous spread in three simple layers.',
            'guidance' => 'A good sharing table needs contrast rather than a long menu. Combine warm bites, ready-to-serve choices and one familiar favourite, then replenish in small batches so everything stays at its best.',
            'guidance_steps' => [
                ['title' => 'Start with warm bites', 'copy' => 'Choose one or two crisp, baked or steamed starters for the first round.'],
                ['title' => 'Add something to share', 'copy' => 'Bring in cheese, charcuterie or another platter-friendly choice for useful variety.'],
                ['title' => 'Serve in small rounds', 'copy' => 'Put out manageable portions and replenish as guests settle in, so the table stays fresh.'],
            ],
            'products_title' => 'Build a generous sharing table',
            'products_copy' => 'Shareable bites, cheeses and easy sides selected for relaxed hosting, celebrations and generous tables.',
            'card_copy' => 'Shareable bites and easy sides for relaxed hosting and generous tables.',
            'image' => 'images/home/party-platter.png',
            'benefits' => [
                ['title' => 'Made for sharing', 'copy' => 'Crowd-friendly choices for passing around the table.'],
                ['title' => 'Easy to combine', 'copy' => 'Build useful variety without overplanning.'],
                ['title' => 'Less last-minute work', 'copy' => 'Quick-to-portion options for relaxed hosting.'],
            ],
        ],
        'family-table-favourites' => [
            'title' => 'Everyday staples',
            'eyebrow' => 'Family favourites',
            'headline' => 'Useful favourites for regular cooking.',
            'intro' => 'A dependable edit of meats, seafood, cheese and ready-to-cook essentials selected for flexible recipes, familiar meals and easy restocking.',
            'guidance_title' => 'Turn a useful selection into easier weekly meals.',
            'guidance' => 'Choose flexible ingredients for the meals you cook most, keep one quick backup for busy evenings and restock the favourites you actually use. This keeps the freezer practical rather than crowded.',
            'guidance_steps' => [
                ['title' => 'Plan two core meals', 'copy' => 'Pick versatile ingredients that can move between curries, grills, pastas or stir-fries.'],
                ['title' => 'Keep one quick backup', 'copy' => 'Reserve an easy ready-to-cook option for evenings when plans change.'],
                ['title' => 'Restock with purpose', 'copy' => 'Replace the items your household uses most before the final pack is gone.'],
            ],
            'products_title' => 'Favourites for regular cooking',
            'products_copy' => 'A practical collection of versatile ingredients and ready-to-cook favourites for meals made again and again.',
            'card_copy' => 'Versatile ingredients and dependable favourites for regular cooking.',
            'image' => 'images/home/family-meal.png',
            'benefits' => [
                ['title' => 'Versatile ingredients', 'copy' => 'Useful across curries, grills, pastas and quick meals.'],
                ['title' => 'Dependable favourites', 'copy' => 'A practical selection for regular cooking.'],
                ['title' => 'Ready when needed', 'copy' => 'Useful essentials for easy restocking.'],
            ],
        ],
    ];

    $occasion = $occasionContent[$collectionSlug] ?? null;
    $isOccasionCollection = ! is_null($occasion);

    $otherOccasions = collect($occasionContent)
        ->except($collectionSlug)
        ->map(function (array $item, string $slug) use ($resolveMediaUrl) {
            return [
                'slug' => $slug,
                'url' => '/collections/' . $slug,
                'title' => $item['title'],
                'eyebrow' => $item['eyebrow'],
                'copy' => $item['card_copy'],
                'image' => $resolveMediaUrl($item['image']),
            ];
        });

    $countryNames = [
        'AU' => 'Australia',
        'BE' => 'Belgium',
        'CA' => 'Canada',
        'DE' => 'Germany',
        'DK' => 'Denmark',
        'EE' => 'Estonia',
        'ES' => 'Spain',
        'FR' => 'France',
        'GB' => 'United Kingdom',
        'GR' => 'Greece',
        'IN' => 'India',
        'IT' => 'Italy',
        'JP' => 'Japan',
        'KR' => 'South Korea',
        'LK' => 'Sri Lanka',
        'NO' => 'Norway',
        'NZ' => 'New Zealand',
        'SG' => 'Singapore',
        'TH' => 'Thailand',
        'US' => 'United States',
    ];

    $productCountryName = function ($product) use ($countryNames) {
        $origin = trim((string) ($product->country_of_origin ?? ''));
        if ($origin === '') {
            return null;
        }

        $code = Str::upper($origin);
        if (isset($countryNames[$code])) {
            return $countryNames[$code];
        }

        return strlen($origin) > 2 ? Str::headline(Str::lower($origin)) : $code;
    };

    $isChefPicksCollection = $collectionSlug === 'chef-picks';
@endphp

{{-- Bandara collection detail — isolated editorial design v1.0.0. --}}
@once
<style>

    /* Occasion collection pages: scoped to the three homepage occasion slugs. */
    .boc-page {
        --boc-bg: #f8fafc;
        --boc-card: #fff;
        --boc-soft: #f3f4f6;
        --boc-text: #111827;
        --boc-muted: #667085;
        --boc-faint: #98a2b3;
        --boc-line: #e5e7eb;
        min-height: 100vh;
        color: var(--boc-text);
        background: var(--boc-bg);
    }

    .dark .boc-page {
        --boc-bg: #030712;
        --boc-card: #111827;
        --boc-soft: #1f2937;
        --boc-text: #f9fafb;
        --boc-muted: #cbd5e1;
        --boc-faint: #94a3b8;
        --boc-line: #273244;
    }

    .boc-page, .boc-page * { box-sizing: border-box; }
    .boc-page a { color: inherit; }

    .boc-shell {
        width: min(100%, 1360px);
        margin: 0 auto;
        padding: 28px 24px 82px;
    }

    .boc-breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 7px;
        margin: 0 0 18px;
        color: var(--boc-faint);
        font-size: 11px;
        letter-spacing: .055em;
    }

    .boc-breadcrumb a { text-decoration: none; transition: color .18s ease; }
    .boc-breadcrumb a:hover,
    .boc-breadcrumb [aria-current="page"] { color: var(--boc-text); }

    .boc-hero {
        display: grid;
        grid-template-columns: minmax(0, .94fr) minmax(420px, 1.06fr);
        min-height: 530px;
        overflow: hidden;
        border: 1px solid var(--boc-line);
        border-radius: 10px;
        background: var(--boc-card);
        box-shadow: 0 18px 48px rgba(15, 23, 42, .065);
    }

    .dark .boc-hero { box-shadow: 0 20px 52px rgba(0, 0, 0, .24); }

    .boc-hero-media {
        position: relative;
        min-height: 530px;
        overflow: hidden;
        background: var(--boc-soft);
    }

    .boc-hero-image {
        position: absolute;
        inset: 0;
        display: block;
        width: 100%;
        height: 100%;
        min-height: 0;
        object-fit: cover;
        transition: transform .7s cubic-bezier(.2, .65, .3, 1);
    }

    .boc-hero:hover .boc-hero-image { transform: scale(1.025); }

    .boc-hero-fallback {
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at 24% 22%, rgba(148, 163, 184, .30), transparent 32%),
            radial-gradient(circle at 76% 72%, rgba(148, 163, 184, .22), transparent 35%),
            linear-gradient(135deg, #e5e7eb, #f8fafc 52%, #d1d5db);
    }

    .dark .boc-hero-fallback {
        background:
            radial-gradient(circle at 24% 22%, rgba(148, 163, 184, .16), transparent 32%),
            radial-gradient(circle at 76% 72%, rgba(148, 163, 184, .10), transparent 35%),
            linear-gradient(135deg, #111827, #1f2937 52%, #0f172a);
    }

    .boc-hero-count {
        position: absolute;
        right: 20px;
        bottom: 20px;
        display: inline-flex;
        align-items: center;
        min-height: 32px;
        padding: 0 11px;
        border: 1px solid rgba(255, 255, 255, .52);
        border-radius: 4px;
        color: #fff;
        background: rgba(17, 24, 39, .54);
        backdrop-filter: blur(8px);
        font-size: 10px;
        font-weight: 600;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .boc-hero-copy {
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-width: 0;
        padding: 46px 46px 42px;
    }

    .boc-eyebrow,
    .boc-section-eyebrow,
    .boc-benefit-number,
    .boc-related-eyebrow,
    .boc-origin-label {
        color: var(--boc-faint);
        font-size: 10px;
        font-weight: 600;
        letter-spacing: .15em;
        text-transform: uppercase;
    }

    .boc-eyebrow { margin: 0 0 15px; color: var(--boc-muted); font-size: 11px; }

    .boc-title {
        max-width: 620px;
        margin: 0;
        font-size: clamp(39px, 4.1vw, 60px);
        font-weight: 600;
        line-height: 1.02;
        letter-spacing: -.045em;
        text-wrap: balance;
    }

    .boc-headline {
        max-width: 620px;
        margin: 22px 0 0;
        font-size: clamp(19px, 2vw, 24px);
        font-weight: 500;
        line-height: 1.35;
        letter-spacing: -.018em;
    }

    .boc-intro {
        max-width: 590px;
        margin: 15px 0 0;
        color: var(--boc-muted);
        font-size: 15px;
        line-height: 1.72;
    }

    .boc-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 14px 22px;
        margin-top: 36px;
    }

    .boc-primary,
    .boc-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        min-height: 44px;
        text-decoration: none;
        font-size: 12px;
        font-weight: 600;
        transition: transform .18s ease, color .18s ease, border-color .18s ease;
    }

    .boc-primary {
        padding: 0 18px;
        border: 1px solid var(--boc-text);
        border-radius: 4px;
        color: var(--boc-card) !important;
        background: var(--boc-text);
    }

    .boc-primary:hover { transform: translateY(-1px); }

    .boc-secondary {
        color: var(--boc-muted) !important;
        border-bottom: 1px solid transparent;
    }

    .boc-secondary:hover {
        color: var(--boc-text) !important;
        border-bottom-color: var(--boc-text);
    }

    .boc-icon { width: 15px; height: 15px; flex: 0 0 auto; }

    .boc-benefits {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        margin-top: 18px;
        border-top: 1px solid var(--boc-line);
        border-bottom: 1px solid var(--boc-line);
    }

    .boc-benefit {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        gap: 14px;
        min-width: 0;
        padding: 21px 24px;
    }

    .boc-benefit + .boc-benefit { border-left: 1px solid var(--boc-line); }
    .boc-benefit-number { padding-top: 2px; }
    .boc-benefit h2 { margin: 0; font-size: 13px; font-weight: 600; line-height: 1.35; }
    .boc-benefit p { margin: 5px 0 0; color: var(--boc-muted); font-size: 12px; line-height: 1.55; }

    .boc-guidance {
        margin-top: 54px;
        padding: 34px 0 0;
        border-top: 1px solid var(--boc-line);
        border-bottom: 1px solid var(--boc-line);
    }

    .boc-guidance-top {
        display: grid;
        grid-template-columns: minmax(250px, .78fr) minmax(0, 1.22fr);
        align-items: end;
        gap: 52px;
        padding-bottom: 32px;
    }

    .boc-section-eyebrow { margin: 0 0 9px; }
    .boc-guidance h2 { max-width: 600px; margin: 0; font-size: clamp(25px, 2.55vw, 36px); font-weight: 600; line-height: 1.15; letter-spacing: -.03em; }
    .boc-guidance-copy { max-width: 790px; margin: 0; color: var(--boc-muted); font-size: 15px; line-height: 1.75; }

    .boc-guidance-steps {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        border-top: 1px solid var(--boc-line);
    }

    .boc-guidance-step {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        gap: 14px;
        min-width: 0;
        padding: 27px 28px 29px 0;
    }

    .boc-guidance-step + .boc-guidance-step {
        padding-left: 28px;
        border-left: 1px solid var(--boc-line);
    }

    .boc-guidance-step-number {
        padding-top: 2px;
        color: var(--boc-faint);
        font-size: 10px;
        font-weight: 600;
        letter-spacing: .15em;
    }

    .boc-guidance-step h3 {
        margin: 0;
        font-size: 14px;
        font-weight: 600;
        line-height: 1.4;
        letter-spacing: -.01em;
    }

    .boc-guidance-step p {
        margin: 7px 0 0;
        color: var(--boc-muted);
        font-size: 12px;
        line-height: 1.62;
    }

    .boc-products { scroll-margin-top: 108px; margin-top: 70px; }

    .boc-products-header {
        display: flex;
        align-items: end;
        justify-content: space-between;
        gap: 28px;
        margin-bottom: 28px;
        padding-bottom: 22px;
        border-bottom: 1px solid var(--boc-line);
    }

    .boc-products-title {
        margin: 0;
        font-size: clamp(28px, 3vw, 40px);
        font-weight: 600;
        line-height: 1.12;
        letter-spacing: -.032em;
    }

    .boc-products-copy { max-width: 720px; margin: 9px 0 0; color: var(--boc-muted); font-size: 14px; line-height: 1.65; }

    .boc-products-count {
        flex: 0 0 auto;
        min-width: 94px;
        padding-left: 20px;
        border-left: 1px solid var(--boc-line);
        text-align: right;
    }

    .boc-products-count strong { display: block; font-size: 28px; line-height: 1; letter-spacing: -.04em; }
    .boc-products-count span { display: block; margin-top: 6px; color: var(--boc-faint); font-size: 9px; font-weight: 600; letter-spacing: .14em; text-transform: uppercase; }

    .boc-product-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 22px; }

    .boc-product-card {
        display: flex;
        min-width: 0;
        overflow: hidden;
        flex-direction: column;
        border: 1px solid var(--boc-line);
        border-radius: 8px;
        background: var(--boc-card);
        text-decoration: none;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .035);
        transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
    }

    .boc-product-card:hover {
        z-index: 2;
        transform: translateY(-7px);
        border-color: rgba(100, 116, 139, .52);
        box-shadow: 0 20px 42px rgba(15, 23, 42, .13);
    }

    .dark .boc-product-card:hover { box-shadow: 0 22px 44px rgba(0, 0, 0, .32); }

    .boc-product-media {
        position: relative;
        aspect-ratio: 1.16 / 1;
        overflow: hidden;
        background: var(--boc-soft);
    }

    .boc-product-image {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform .52s cubic-bezier(.2, .65, .3, 1);
    }

    .boc-product-card:hover .boc-product-image { transform: scale(1.045); }

    .boc-product-badges {
        position: absolute;
        z-index: 2;
        top: 12px;
        left: 12px;
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .boc-product-badge {
        display: inline-flex;
        align-items: center;
        min-height: 27px;
        padding: 0 8px;
        border: 1px solid rgba(255, 255, 255, .64);
        border-radius: 3px;
        color: #111827;
        background: rgba(255, 255, 255, .92);
        backdrop-filter: blur(7px);
        font-size: 9px;
        font-weight: 700;
        letter-spacing: .10em;
        text-transform: uppercase;
    }

    .boc-product-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        color: var(--boc-faint);
        font-size: 10px;
        font-weight: 600;
        letter-spacing: .11em;
        text-transform: uppercase;
    }

    .boc-product-body {
        display: flex;
        flex: 1;
        flex-direction: column;
        min-height: 176px;
        padding: 19px 18px 17px;
    }

    .boc-product-title,
    .boc-product-description {
        display: -webkit-box;
        overflow: hidden;
        -webkit-box-orient: vertical;
    }

    .boc-product-title {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        line-height: 1.35;
        letter-spacing: -.012em;
        -webkit-line-clamp: 2;
    }

    .boc-product-description {
        margin: 9px 0 0;
        color: var(--boc-muted);
        font-size: 12px;
        line-height: 1.55;
        -webkit-line-clamp: 2;
    }

    .boc-product-footer {
        display: flex;
        align-items: end;
        justify-content: space-between;
        gap: 14px;
        margin-top: auto;
        padding-top: 15px;
        border-top: 1px solid var(--boc-line);
    }

    .boc-origin { min-width: 0; }
    .boc-origin-label { display: block; margin-bottom: 4px; font-size: 8px; }
    .boc-origin-value { display: block; overflow: hidden; color: var(--boc-muted); font-size: 11px; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }

    .boc-view-link {
        display: inline-flex;
        align-items: center;
        flex: 0 0 auto;
        gap: 6px;
        color: var(--boc-muted);
        font-size: 10px;
        font-weight: 600;
        transition: color .18s ease;
    }

    .boc-product-card:hover .boc-view-link { color: var(--boc-text); }
    .boc-product-card:hover .boc-view-link svg { transform: translate(2px, -2px); }
    .boc-view-link svg { transition: transform .18s ease; }

    .boc-empty {
        padding: 64px 24px;
        border: 1px dashed var(--boc-line);
        border-radius: 8px;
        background: var(--boc-card);
        text-align: center;
    }

    .boc-empty h3 { margin: 0; font-size: 20px; }
    .boc-empty p { max-width: 480px; margin: 10px auto 20px; color: var(--boc-muted); font-size: 14px; line-height: 1.65; }
    .boc-pagination { margin-top: 34px; }

    .boc-related { margin-top: 76px; }

    .boc-related-header {
        display: flex;
        align-items: end;
        justify-content: space-between;
        gap: 24px;
        margin-bottom: 22px;
    }

    .boc-related-header h2 { margin: 0; font-size: clamp(25px, 2.7vw, 36px); font-weight: 600; letter-spacing: -.03em; }
    .boc-related-header p { max-width: 520px; margin: 0; color: var(--boc-muted); font-size: 13px; line-height: 1.6; }
    .boc-related-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }

    .boc-related-card {
        display: grid;
        grid-template-columns: 164px minmax(0, 1fr);
        min-width: 0;
        overflow: hidden;
        border: 1px solid var(--boc-line);
        border-radius: 8px;
        background: var(--boc-card);
        text-decoration: none;
        transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
    }

    .boc-related-card:hover {
        transform: translateY(-3px);
        border-color: rgba(100, 116, 139, .52);
        box-shadow: 0 15px 32px rgba(15, 23, 42, .09);
    }

    .dark .boc-related-card:hover { box-shadow: 0 17px 34px rgba(0, 0, 0, .27); }

    .boc-related-media { min-height: 154px; overflow: hidden; background: var(--boc-soft); }
    .boc-related-image { display: block; width: 100%; height: 100%; min-height: inherit; object-fit: cover; transition: transform .45s ease; }
    .boc-related-media .boc-product-placeholder { min-height: inherit; }
    .boc-related-card:hover .boc-related-image { transform: scale(1.035); }
    .boc-related-copy { display: flex; flex-direction: column; justify-content: center; min-width: 0; padding: 22px; }
    .boc-related-eyebrow { margin: 0 0 7px; }
    .boc-related-card h3 { margin: 0; font-size: 18px; font-weight: 600; letter-spacing: -.015em; }
    .boc-related-card p { margin: 8px 0 0; color: var(--boc-muted); font-size: 12px; line-height: 1.55; }
    .boc-related-link { display: inline-flex; align-items: center; gap: 7px; margin-top: 14px; font-size: 10px; font-weight: 600; }
    .boc-related-card:hover .boc-related-link svg { transform: translateX(2px); }
    .boc-related-link svg { transition: transform .18s ease; }

    .boc-page :is(a, button):focus-visible { outline: 2px solid var(--boc-text); outline-offset: 3px; }

    @media (max-width: 1120px) {
        .boc-hero { grid-template-columns: minmax(0, .9fr) minmax(380px, 1.1fr); min-height: 500px; }
        .boc-hero-media { min-height: 500px; }
        .boc-hero-copy { padding: 40px 36px 38px; }
        .boc-guidance-top { gap: 38px; }
        .boc-product-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }

    @media (max-width: 900px) {
        .boc-hero { grid-template-columns: 1fr; min-height: 0; }
        .boc-hero-media { min-height: 370px; height: 370px; }
        .boc-hero-image { height: 100%; }
        .boc-hero-copy { min-height: 0; padding: 38px 36px 40px; }
        .boc-guidance-top { grid-template-columns: 1fr; gap: 17px; }
        .boc-related-header { align-items: start; flex-direction: column; gap: 9px; }
    }

    @media (max-width: 760px) {
        .boc-shell { padding: 22px 16px 62px; }
        .boc-benefits { grid-template-columns: 1fr; }
        .boc-benefit + .boc-benefit { border-top: 1px solid var(--boc-line); border-left: 0; }
        .boc-guidance-steps { grid-template-columns: 1fr; }
        .boc-guidance-step { padding: 23px 0 24px; }
        .boc-guidance-step + .boc-guidance-step { padding-left: 0; border-top: 1px solid var(--boc-line); border-left: 0; }
        .boc-products { margin-top: 58px; }
        .boc-product-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .boc-related-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 560px) {
        .boc-hero-media { min-height: 285px; height: 285px; }
        .boc-hero-image { height: 100%; }
        .boc-hero-count { right: 16px; bottom: 16px; }
        .boc-hero-copy { min-height: 0; padding: 31px 24px 29px; }
        .boc-title { font-size: clamp(35px, 11vw, 47px); }
        .boc-headline { margin-top: 18px; font-size: 19px; }
        .boc-intro { font-size: 14px; }
        .boc-actions { align-items: stretch; flex-direction: column; margin-top: 29px; }
        .boc-primary, .boc-secondary { width: 100%; }
        .boc-secondary { min-height: 40px; border: 1px solid var(--boc-line); border-radius: 4px; }
        .boc-guidance { margin-top: 44px; padding-top: 25px; }
        .boc-guidance-top { padding-bottom: 24px; }
        .boc-products-header { display: block; }
        .boc-products-count { display: flex; align-items: baseline; gap: 8px; width: max-content; min-width: 0; margin-top: 18px; padding: 0; border: 0; text-align: left; }
        .boc-products-count span { margin: 0; }
        .boc-product-grid { grid-template-columns: 1fr; }
        .boc-product-body { min-height: 0; }
        .boc-related { margin-top: 60px; }
        .boc-related-card { grid-template-columns: 118px minmax(0, 1fr); }
        .boc-related-media { min-height: 166px; }
        .boc-related-copy { padding: 18px; }
    }

    @media (prefers-reduced-motion: reduce) {
        .boc-page *, .boc-page *::before, .boc-page *::after { transition-duration: .01ms !important; }
    }

    .bcd-page {
        --bcd-bg: #f8fafc;
        --bcd-card: #fff;
        --bcd-soft: #f3f4f6;
        --bcd-text: #111827;
        --bcd-muted: #667085;
        --bcd-faint: #98a2b3;
        --bcd-line: #e5e7eb;
        min-height: 100vh;
        color: var(--bcd-text);
        background: var(--bcd-bg);
    }

    .dark .bcd-page {
        --bcd-bg: #030712;
        --bcd-card: #111827;
        --bcd-soft: #1f2937;
        --bcd-text: #f9fafb;
        --bcd-muted: #cbd5e1;
        --bcd-faint: #94a3b8;
        --bcd-line: #273244;
    }

    .bcd-page, .bcd-page * { box-sizing: border-box; }
    .bcd-page a { color: inherit; }

    .bcd-shell {
        width: min(100%, 1360px);
        margin: 0 auto;
        padding: 28px 24px 80px;
    }

    .bcd-breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 7px;
        margin: 0 0 18px;
        color: var(--bcd-faint);
        font-size: 11px;
        letter-spacing: .055em;
    }

    .bcd-breadcrumb a { text-decoration: none; transition: color .18s ease; }
    .bcd-breadcrumb a:hover, .bcd-breadcrumb [aria-current="page"] { color: var(--bcd-text); }

    .bcd-hero {
        display: grid;
        grid-template-columns: minmax(0, 1.17fr) minmax(380px, .83fr);
        min-height: 530px;
        overflow: hidden;
        border: 1px solid var(--bcd-line);
        border-radius: 10px;
        background: var(--bcd-card);
        box-shadow: 0 22px 58px rgba(15, 23, 42, .07);
    }

    .dark .bcd-hero { box-shadow: 0 24px 62px rgba(0, 0, 0, .25); }

    .bcd-hero-media {
        position: relative;
        min-height: 530px;
        overflow: hidden;
        background: var(--bcd-soft);
    }

    .bcd-hero-media::after {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
        background: linear-gradient(180deg, transparent 54%, rgba(17, 24, 39, .62));
    }

    .bcd-hero-image {
        display: block;
        width: 100%;
        height: 100%;
        min-height: 530px;
        object-fit: cover;
        transition: transform .7s cubic-bezier(.2, .65, .3, 1);
    }

    .bcd-hero:hover .bcd-hero-image { transform: scale(1.025); }

    .bcd-hero-fallback {
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at 22% 24%, rgba(148, 163, 184, .34), transparent 31%),
            radial-gradient(circle at 76% 70%, rgba(148, 163, 184, .24), transparent 34%),
            linear-gradient(135deg, #e5e7eb, #f8fafc 52%, #d1d5db);
    }

    .dark .bcd-hero-fallback {
        background:
            radial-gradient(circle at 22% 24%, rgba(148, 163, 184, .17), transparent 31%),
            radial-gradient(circle at 76% 70%, rgba(148, 163, 184, .11), transparent 34%),
            linear-gradient(135deg, #111827, #1f2937 52%, #0f172a);
    }

    .bcd-image-caption {
        position: absolute;
        z-index: 2;
        right: 26px;
        bottom: 23px;
        left: 26px;
        display: flex;
        justify-content: space-between;
        gap: 16px;
        color: #fff;
        font-size: 10px;
        font-weight: 600;
        letter-spacing: .14em;
        text-transform: uppercase;
    }

    .bcd-hero-copy {
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-width: 0;
        padding: 46px 44px 40px;
    }

    .bcd-hero-copy::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 78px;
        height: 78px;
        border-bottom: 1px solid var(--bcd-line);
        border-left: 1px solid var(--bcd-line);
    }

    .bcd-edition, .bcd-eyebrow, .bcd-kicker, .bcd-meta-label, .bcd-card-kicker {
        color: var(--bcd-faint);
        font-size: 10px;
        font-weight: 600;
        letter-spacing: .15em;
        text-transform: uppercase;
    }

    .bcd-edition { display: flex; align-items: center; gap: 12px; margin-bottom: 44px; }
    .bcd-edition::before { content: ''; width: 28px; height: 1px; background: var(--bcd-line); }
    .bcd-eyebrow { margin: 0 0 14px; color: var(--bcd-muted); font-size: 11px; }

    .bcd-title {
        max-width: 650px;
        margin: 0;
        color: var(--bcd-text);
        font-size: clamp(38px, 4.25vw, 62px);
        font-weight: 600;
        line-height: 1.01;
        letter-spacing: -.045em;
        text-wrap: balance;
    }

    .bcd-description {
        max-width: 560px;
        margin: 23px 0 0;
        color: var(--bcd-muted);
        font-size: 16px;
        line-height: 1.7;
    }

    .bcd-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 14px 22px; margin-top: 40px; }

    .bcd-primary, .bcd-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        min-height: 45px;
        text-decoration: none;
        font-size: 12px;
        font-weight: 600;
        transition: transform .18s ease, color .18s ease, border-color .18s ease;
    }

    .bcd-primary {
        padding: 0 18px;
        border: 1px solid var(--bcd-text);
        border-radius: 4px;
        color: var(--bcd-card) !important;
        background: var(--bcd-text);
    }

    .bcd-primary:hover { transform: translateY(-1px); }
    .bcd-secondary { color: var(--bcd-muted) !important; border-bottom: 1px solid transparent; }
    .bcd-secondary:hover { color: var(--bcd-text) !important; border-bottom-color: var(--bcd-text); }
    .bcd-icon { width: 15px; height: 15px; flex: 0 0 auto; }

    .bcd-meta {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        margin-top: 18px;
        overflow: hidden;
        border: 1px solid var(--bcd-line);
        border-radius: 8px;
        background: var(--bcd-card);
    }

    .bcd-meta-item { min-width: 0; padding: 20px 22px; }
    .bcd-meta-item + .bcd-meta-item { border-left: 1px solid var(--bcd-line); }
    .bcd-meta-label { display: block; margin-bottom: 6px; font-size: 9px; }
    .bcd-meta-value { display: block; overflow: hidden; font-size: 13px; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }

    .bcd-products { scroll-margin-top: 108px; margin-top: 74px; }

    .bcd-products-header {
        display: flex;
        align-items: end;
        justify-content: space-between;
        gap: 28px;
        margin-bottom: 28px;
        padding-bottom: 22px;
        border-bottom: 1px solid var(--bcd-line);
    }

    .bcd-kicker { margin: 0 0 9px; }

    .bcd-section-title {
        margin: 0;
        font-size: clamp(27px, 3vw, 39px);
        font-weight: 600;
        line-height: 1.12;
        letter-spacing: -.032em;
    }

    .bcd-section-copy { max-width: 650px; margin: 9px 0 0; color: var(--bcd-muted); font-size: 14px; line-height: 1.65; }

    .bcd-count { flex: 0 0 auto; min-width: 94px; padding-left: 20px; border-left: 1px solid var(--bcd-line); text-align: right; }
    .bcd-count strong { display: block; font-size: 28px; line-height: 1; letter-spacing: -.04em; }
    .bcd-count span { display: block; margin-top: 6px; color: var(--bcd-faint); font-size: 9px; font-weight: 600; letter-spacing: .14em; text-transform: uppercase; }

    .bcd-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 22px; }

    .bcd-card {
        min-width: 0;
        overflow: hidden;
        border: 1px solid var(--bcd-line);
        border-radius: 8px;
        background: var(--bcd-card);
        text-decoration: none;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .035);
        transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
    }

    .bcd-card:hover { transform: translateY(-5px); border-color: rgba(100, 116, 139, .52); box-shadow: 0 18px 36px rgba(15, 23, 42, .11); }
    .dark .bcd-card:hover { box-shadow: 0 20px 40px rgba(0, 0, 0, .30); }

    .bcd-card-media { position: relative; aspect-ratio: 1.16 / 1; overflow: hidden; background: var(--bcd-soft); }
    .bcd-card-image { display: block; width: 100%; height: 100%; object-fit: cover; transition: transform .52s cubic-bezier(.2, .65, .3, 1); }
    .bcd-card:hover .bcd-card-image { transform: scale(1.045); }

    .bcd-card-number {
        position: absolute;
        z-index: 2;
        top: 13px;
        left: 13px;
        min-width: 34px;
        padding: 7px 8px;
        border: 1px solid rgba(255, 255, 255, .65);
        border-radius: 3px;
        color: #fff;
        background: rgba(17, 24, 39, .50);
        font-size: 9px;
        font-weight: 700;
        line-height: 1;
        letter-spacing: .11em;
        text-align: center;
    }

    .bcd-card-cta {
        position: absolute;
        z-index: 2;
        right: 13px;
        bottom: 13px;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: 31px;
        padding: 0 10px;
        border-radius: 3px;
        color: #111827;
        background: rgba(255, 255, 255, .93);
        font-size: 10px;
        font-weight: 600;
        opacity: 0;
        transform: translateY(6px);
        transition: opacity .19s ease, transform .19s ease;
    }

    .bcd-card:hover .bcd-card-cta { opacity: 1; transform: none; }

    .bcd-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        color: var(--bcd-faint);
        font-size: 10px;
        font-weight: 600;
        letter-spacing: .11em;
        text-transform: uppercase;
    }

    .bcd-card-body { display: flex; justify-content: space-between; gap: 18px; min-height: 143px; padding: 20px 18px 19px; }
    .bcd-card-copy { min-width: 0; }
    .bcd-card-kicker { margin: 0 0 7px; font-size: 9px; }

    .bcd-card-title, .bcd-card-description {
        display: -webkit-box;
        overflow: hidden;
        -webkit-box-orient: vertical;
    }

    .bcd-card-title { margin: 0; font-size: 16px; font-weight: 600; line-height: 1.35; letter-spacing: -.012em; -webkit-line-clamp: 2; }
    .bcd-card-description { margin: 9px 0 0; color: var(--bcd-muted); font-size: 12px; line-height: 1.55; -webkit-line-clamp: 2; }

    .bcd-card-arrow {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        width: 31px;
        height: 31px;
        border: 1px solid var(--bcd-line);
        border-radius: 50%;
        color: var(--bcd-muted);
        transition: transform .19s ease, color .19s ease, border-color .19s ease;
    }

    .bcd-card:hover .bcd-card-arrow { color: var(--bcd-text); border-color: var(--bcd-text); transform: translate(2px, -2px); }

    .bcd-empty { padding: 64px 24px; border: 1px dashed var(--bcd-line); border-radius: 8px; background: var(--bcd-card); text-align: center; }
    .bcd-empty h3 { margin: 0; font-size: 20px; }
    .bcd-empty p { max-width: 480px; margin: 10px auto 20px; color: var(--bcd-muted); font-size: 14px; line-height: 1.65; }
    .bcd-pagination { margin-top: 34px; }
    .bcd-page :is(a, button):focus-visible { outline: 2px solid var(--bcd-text); outline-offset: 3px; }

    @media (max-width: 1120px) {
        .bcd-hero { grid-template-columns: minmax(0, 1.05fr) minmax(350px, .95fr); }
        .bcd-hero-copy { padding: 40px 36px 36px; }
        .bcd-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }

    @media (max-width: 900px) {
        .bcd-hero { grid-template-columns: 1fr; min-height: 0; }
        .bcd-hero-media, .bcd-hero-image { min-height: 390px; }
        .bcd-hero-copy { min-height: 410px; }
        .bcd-edition { margin-bottom: 30px; }
    }

    @media (max-width: 760px) {
        .bcd-shell { padding: 22px 16px 60px; }
        .bcd-meta { grid-template-columns: 1fr; }
        .bcd-meta-item + .bcd-meta-item { border-top: 1px solid var(--bcd-line); border-left: 0; }
        .bcd-meta-value { white-space: normal; }
        .bcd-products { margin-top: 58px; }
        .bcd-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
    }

    @media (max-width: 560px) {
        .bcd-hero-media, .bcd-hero-image { min-height: 300px; }
        .bcd-image-caption { right: 18px; bottom: 17px; left: 18px; }
        .bcd-hero-copy { min-height: 0; padding: 32px 24px 30px; }
        .bcd-hero-copy::after { width: 58px; height: 58px; }
        .bcd-title { font-size: clamp(34px, 11vw, 47px); }
        .bcd-description { margin-top: 18px; font-size: 15px; }
        .bcd-actions { align-items: stretch; flex-direction: column; margin-top: 30px; }
        .bcd-primary, .bcd-secondary { width: 100%; }
        .bcd-secondary { min-height: 40px; border: 1px solid var(--bcd-line); border-radius: 4px; }
        .bcd-products-header { display: block; }
        .bcd-count { display: flex; align-items: baseline; gap: 8px; width: max-content; min-width: 0; margin-top: 18px; padding: 0; border: 0; text-align: left; }
        .bcd-count span { margin: 0; }
        .bcd-grid { grid-template-columns: 1fr; }
        .bcd-card-cta { opacity: 1; transform: none; }
        .bcd-card-body { min-height: 0; }
    }


    /* Compact presentation for the existing Chef Picks collection only. */
    .bcd-page--chef-picks .bcd-shell { padding-bottom: 64px; }

    .bcd-page--chef-picks .bcd-hero {
        grid-template-columns: minmax(17rem, .82fr) minmax(0, 1.18fr);
        min-height: 390px;
    }

    .bcd-page--chef-picks .bcd-hero-media,
    .bcd-page--chef-picks .bcd-hero-image {
        min-height: 390px;
        height: 390px;
    }

    .bcd-page--chef-picks .bcd-hero-image {
        object-position: center 28%;
    }

    .bcd-page--chef-picks .bcd-hero-copy {
        min-height: 390px;
        padding: 32px 34px 28px;
    }

    .bcd-page--chef-picks .bcd-hero-copy::after {
        width: 62px;
        height: 62px;
    }

    .bcd-page--chef-picks .bcd-edition { margin-bottom: 24px; }
    .bcd-page--chef-picks .bcd-title { font-size: clamp(34px, 3.65vw, 50px); }
    .bcd-page--chef-picks .bcd-description { margin-top: 16px; font-size: 15px; line-height: 1.62; }
    .bcd-page--chef-picks .bcd-actions { margin-top: 28px; }
    .bcd-page--chef-picks .bcd-meta { margin-top: 14px; }
    .bcd-page--chef-picks .bcd-meta-item { padding: 15px 18px; }
    .bcd-page--chef-picks .bcd-products { margin-top: 50px; }

    @media (max-width: 900px) {
        .bcd-page--chef-picks .bcd-hero { grid-template-columns: 1fr; min-height: 0; }

        .bcd-page--chef-picks .bcd-hero-media,
        .bcd-page--chef-picks .bcd-hero-image {
            min-height: 280px;
            height: 280px;
        }

        .bcd-page--chef-picks .bcd-hero-copy {
            min-height: 0;
            padding: 28px 30px 26px;
        }

        .bcd-page--chef-picks .bcd-edition { margin-bottom: 20px; }
        .bcd-page--chef-picks .bcd-title { font-size: clamp(32px, 7vw, 44px); }
    }

    @media (max-width: 560px) {
        .bcd-page--chef-picks .bcd-hero-media,
        .bcd-page--chef-picks .bcd-hero-image {
            min-height: 220px;
            height: 220px;
        }

        .bcd-page--chef-picks .bcd-hero-copy { padding: 24px 20px 22px; }
        .bcd-page--chef-picks .bcd-hero-copy::after { width: 48px; height: 48px; }
        .bcd-page--chef-picks .bcd-title { font-size: clamp(31px, 10vw, 39px); }
        .bcd-page--chef-picks .bcd-description { margin-top: 14px; font-size: 14px; }
        .bcd-page--chef-picks .bcd-actions { margin-top: 22px; }
        .bcd-page--chef-picks .bcd-products { margin-top: 42px; }
    }

    @media (prefers-reduced-motion: reduce) {
        .bcd-page *, .bcd-page *::before, .bcd-page *::after { transition-duration: .01ms !important; }
    }
</style>
@endonce

@if($isOccasionCollection)
<div class="boc-page">
    <main class="boc-shell">
        <nav class="boc-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ $homeUrl }}">Home</a>
            <span aria-hidden="true">/</span>
            <a href="{{ $homeUrl }}#occasions">Collections</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">{{ $occasion['title'] }}</span>
        </nav>

        <section class="boc-hero" aria-labelledby="occasion-collection-title">
            <div class="boc-hero-media">
                @if($collectionImage)
                    <img
                        src="{{ $collectionImage }}"
                        alt="{{ $occasion['title'] }}"
                        class="boc-hero-image"
                        loading="eager"
                        fetchpriority="high"
                    >
                @else
                    <div class="boc-hero-fallback" aria-hidden="true"></div>
                @endif

                <div class="boc-hero-count">
                    {{ $productCount }} {{ Str::plural('product', $productCount) }}
                </div>
            </div>

            <div class="boc-hero-copy">
                <div>
                    <p class="boc-eyebrow">{{ $occasion['eyebrow'] }}</p>
                    <h1 id="occasion-collection-title" class="boc-title">{{ $occasion['title'] }}</h1>
                    <p class="boc-headline">{{ $occasion['headline'] }}</p>
                    <p class="boc-intro">{{ $occasion['intro'] }}</p>
                </div>

                <div class="boc-actions">
                    <a href="#collection-products" class="boc-primary">
                        Shop this collection
                        <svg class="boc-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M10 3.5v12M5.5 11l4.5 4.5 4.5-4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>

                    <a href="{{ $shopUrl }}" class="boc-secondary">
                        Browse all products
                        <svg class="boc-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M4 10h12M11.5 5.5 16 10l-4.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </div>
            </div>
        </section>

        <section class="boc-benefits" aria-label="Why this collection is useful">
            @foreach($occasion['benefits'] as $benefit)
                <article class="boc-benefit">
                    <span class="boc-benefit-number" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <div>
                        <h2>{{ $benefit['title'] }}</h2>
                        <p>{{ $benefit['copy'] }}</p>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="boc-guidance" aria-labelledby="occasion-guidance-title">
            <div class="boc-guidance-top">
                <div>
                    <p class="boc-section-eyebrow">How to use this edit</p>
                    <h2 id="occasion-guidance-title">{{ $occasion['guidance_title'] }}</h2>
                </div>

                <p class="boc-guidance-copy">{{ $occasion['guidance'] }}</p>
            </div>

            <div class="boc-guidance-steps" aria-label="Three ways to use this collection">
                @foreach($occasion['guidance_steps'] as $step)
                    <article class="boc-guidance-step">
                        <span class="boc-guidance-step-number" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <div>
                            <h3>{{ $step['title'] }}</h3>
                            <p>{{ $step['copy'] }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section id="collection-products" class="boc-products" aria-labelledby="occasion-products-title">
            <header class="boc-products-header">
                <div>
                    <p class="boc-section-eyebrow">Shop the collection</p>
                    <h2 id="occasion-products-title" class="boc-products-title">{{ $occasion['products_title'] }}</h2>
                    <p class="boc-products-copy">{{ $occasion['products_copy'] }}</p>
                </div>

                <div class="boc-products-count" aria-label="{{ $productCount }} products in this collection">
                    <strong>{{ $productCount }}</strong>
                    <span>{{ Str::plural('product', $productCount) }}</span>
                </div>
            </header>

            @if($products->count() === 0)
                <div class="boc-empty">
                    <h3>This collection is being prepared.</h3>
                    <p>There are no visible products here at the moment. The full Bandara range is still available in the shop.</p>
                    <a href="{{ $shopUrl }}" class="boc-primary">
                        Visit the shop
                        <svg class="boc-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M4 10h12M11.5 5.5 16 10l-4.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </div>
            @else
                <div class="boc-product-grid">
                    @foreach($products as $product)
                        @php
                            $productImage = $productPrimaryImageUrl($product);
                            $productName = trim((string) ($product->name ?? 'Product'));
                            $productDescription = trim((string) ($product->short_description ?? ''));
                            $productOrigin = $productCountryName($product);
                            $isNewProduct = (bool) ($product->is_new ?? false);
                            $isSpecialProduct = (bool) ($product->is_special ?? false);
                        @endphp

                        <a href="{{ $productUrl($product) }}" class="boc-product-card" aria-label="View {{ $productName }}">
                            <div class="boc-product-media">
                                @if($productImage)
                                    <img
                                        src="{{ $productImage }}"
                                        alt="{{ $productName }}"
                                        class="boc-product-image"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="boc-product-placeholder">Image coming soon</div>
                                @endif

                                @if($isNewProduct || $isSpecialProduct)
                                    <div class="boc-product-badges" aria-label="Product labels">
                                        @if($isNewProduct)
                                            <span class="boc-product-badge">New</span>
                                        @endif
                                        @if($isSpecialProduct)
                                            <span class="boc-product-badge">Special</span>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div class="boc-product-body">
                                <div>
                                    <h3 class="boc-product-title">{{ $productName }}</h3>

                                    @if($productDescription !== '')
                                        <p class="boc-product-description">{{ $productDescription }}</p>
                                    @endif
                                </div>

                                <div class="boc-product-footer">
                                    <span class="boc-origin">
                                        @if($productOrigin)
                                            <span class="boc-origin-label">Country of origin</span>
                                            <span class="boc-origin-value">{{ $productOrigin }}</span>
                                        @else
                                            <span class="boc-origin-label">Product information</span>
                                            <span class="boc-origin-value">Formats and pricing</span>
                                        @endif
                                    </span>

                                    <span class="boc-view-link" aria-hidden="true">
                                        View
                                        <svg width="13" height="13" viewBox="0 0 20 20" fill="none">
                                            <path d="M5 15 15 5M7 5h8v8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                @if(method_exists($products, 'links'))
                    <div class="boc-pagination">{{ $products->links() }}</div>
                @endif
            @endif
        </section>

        <section class="boc-related" aria-labelledby="other-collections-title">
            <header class="boc-related-header">
                <h2 id="other-collections-title">Explore the other collections</h2>
                <p>Move from everyday cooking to quick dinners or relaxed entertaining without returning to the full catalogue.</p>
            </header>

            <div class="boc-related-grid">
                @foreach($otherOccasions as $related)
                    <a href="{{ $related['url'] }}" class="boc-related-card" aria-label="Explore {{ $related['title'] }}">
                        <div class="boc-related-media">
                            @if($related['image'])
                                <img src="{{ $related['image'] }}" alt="{{ $related['title'] }}" class="boc-related-image" loading="lazy">
                            @else
                                <div class="boc-product-placeholder">Collection image</div>
                            @endif
                        </div>

                        <div class="boc-related-copy">
                            <p class="boc-related-eyebrow">{{ $related['eyebrow'] }}</p>
                            <h3>{{ $related['title'] }}</h3>
                            <p>{{ $related['copy'] }}</p>
                            <span class="boc-related-link" aria-hidden="true">
                                Explore collection
                                <svg width="13" height="13" viewBox="0 0 20 20" fill="none">
                                    <path d="M4 10h12M11.5 5.5 16 10l-4.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    </main>
</div>
@else
<div @class(['bcd-page', 'bcd-page--chef-picks' => $isChefPicksCollection])>
    <main class="bcd-shell">
        <nav class="bcd-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ $homeUrl }}">Home</a>
            <span aria-hidden="true">/</span>
            <a href="{{ $shopUrl }}">Shop</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">{{ $collection->name ?? 'Collection' }}</span>
        </nav>

        <section class="bcd-hero" aria-labelledby="collection-title">
            <div class="bcd-hero-media">
                @if($collectionImage)
                    <img
                        src="{{ $collectionImage }}"
                        alt="{{ $collection->name ?? 'Bandara collection' }}"
                        class="bcd-hero-image"
                        loading="eager"
                        fetchpriority="high"
                    >
                @else
                    <div class="bcd-hero-fallback" aria-hidden="true"></div>
                @endif

                <div class="bcd-image-caption" aria-hidden="true">
                    <span>Bandara collection</span>
                    <span>{{ $productCount }} {{ Str::plural('product', $productCount) }}</span>
                </div>
            </div>

            <div class="bcd-hero-copy">
                <div>
                    <div class="bcd-edition">Curated edit</div>
                    <p class="bcd-eyebrow">{{ $collectionLabel }}</p>
                    <h1 id="collection-title" class="bcd-title">{{ $collection->name ?? 'Collection' }}</h1>
                    <p class="bcd-description">{{ $collectionDescription }}</p>
                </div>

                <div class="bcd-actions">
                    <a href="#collection-products" class="bcd-primary">
                        Explore the collection
                        <svg class="bcd-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M10 3.5v12M5.5 11l4.5 4.5 4.5-4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>

                    <a href="{{ $shopUrl }}" class="bcd-secondary">
                        Browse the full shop
                        <svg class="bcd-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M4 10h12M11.5 5.5 16 10l-4.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </div>
            </div>
        </section>

        <aside class="bcd-meta" aria-label="Collection summary">
            <div class="bcd-meta-item">
                <span class="bcd-meta-label">Selection</span>
                <span class="bcd-meta-value">Curated by Bandara</span>
            </div>
            <div class="bcd-meta-item">
                <span class="bcd-meta-label">Made for</span>
                <span class="bcd-meta-value">{{ $collectionLabel }}</span>
            </div>
            <div class="bcd-meta-item">
                <span class="bcd-meta-label">Collection size</span>
                <span class="bcd-meta-value">{{ $productCount }} {{ Str::plural('product', $productCount) }}</span>
            </div>
        </aside>

        <section id="collection-products" class="bcd-products" aria-labelledby="collection-products-title">
            <header class="bcd-products-header">
                <div>
                    <p class="bcd-kicker">The collection</p>
                    <h2 id="collection-products-title" class="bcd-section-title">
                        Everything in {{ $collection->name ?? 'this collection' }}
                    </h2>
                    <p class="bcd-section-copy">
                        Open any product to see its available formats, current pricing and full product information.
                    </p>
                </div>

                <div class="bcd-count" aria-label="{{ $productCount }} products in this collection">
                    <strong>{{ $productCount }}</strong>
                    <span>{{ Str::plural('product', $productCount) }}</span>
                </div>
            </header>

            @if($products->count() === 0)
                <div class="bcd-empty">
                    <h3>This collection is being prepared.</h3>
                    <p>
                        There are no visible products in this collection at the moment. The full Bandara range is still available in the shop.
                    </p>
                    <a href="{{ $shopUrl }}" class="bcd-primary">
                        Visit the shop
                        <svg class="bcd-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M4 10h12M11.5 5.5 16 10l-4.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </div>
            @else
                <div class="bcd-grid">
                    @foreach($products as $product)
                        @php
                            $productImage = $productPrimaryImageUrl($product);
                            $productName = trim((string) ($product->name ?? 'Product'));
                            $productDescription = trim((string) ($product->short_description ?? ''));
                        @endphp

                        <a href="{{ $productUrl($product) }}" class="bcd-card" aria-label="View {{ $productName }}">
                            <div class="bcd-card-media">
                                @if($productImage)
                                    <img
                                        src="{{ $productImage }}"
                                        alt="{{ $productName }}"
                                        class="bcd-card-image"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="bcd-placeholder">Image coming soon</div>
                                @endif

                                <span class="bcd-card-number" aria-hidden="true">
                                    {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                </span>

                                <span class="bcd-card-cta" aria-hidden="true">
                                    View product
                                    <svg width="12" height="12" viewBox="0 0 20 20" fill="none">
                                        <path d="M5 15 15 5M7 5h8v8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            </div>

                            <div class="bcd-card-body">
                                <div class="bcd-card-copy">
                                    <p class="bcd-card-kicker">Selected product</p>
                                    <h3 class="bcd-card-title">{{ $productName }}</h3>

                                    @if($productDescription !== '')
                                        <p class="bcd-card-description">{{ $productDescription }}</p>
                                    @endif
                                </div>

                                <span class="bcd-card-arrow" aria-hidden="true">
                                    <svg width="14" height="14" viewBox="0 0 20 20" fill="none">
                                        <path d="M5 15 15 5M7 5h8v8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>

                @if(method_exists($products, 'links'))
                    <div class="bcd-pagination">{{ $products->links() }}</div>
                @endif
            @endif
        </section>
    </main>
</div>
@endif
@endsection
