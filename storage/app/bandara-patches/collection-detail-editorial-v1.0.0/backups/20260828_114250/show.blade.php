@extends('layouts.customer')

@section('title', ($collection->name ?? config('app.name')))

@section('content')
@php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $has = fn (string $routeName) => Route::has($routeName);

    /* Relative fallbacks preserve the storefront URL-safety baseline. */
    $homeUrl = $has('home') ? route('home') : '/';
    $shopUrl = $has('shop.index') ? route('shop.index') : '#';

    $productUrl = function ($product) use ($has) {
        return $has('product.show')
            ? route('product.show', $product->slug ?? $product)
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
@endphp

{{-- Bandara collection detail — isolated editorial design v1.0.0. --}}
@once
<style>
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

    @media (prefers-reduced-motion: reduce) {
        .bcd-page *, .bcd-page *::before, .bcd-page *::after { transition-duration: .01ms !important; }
    }
</style>
@endonce

<div class="bcd-page">
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
@endsection
