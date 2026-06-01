@extends('layouts.customer')

@section('title', ($collection->name ?? config('app.name')))

@section('content')
@php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $has = fn(string $r) => Route::has($r);

    $shopUrl = $has('shop.index') ? route('shop.index') : '#';

    $productUrl = function ($product) use ($has) {
        if ($has('products.show')) return route('products.show', $product);
        if ($has('product.show')) return route('product.show', $product);
        if ($has('shop.show')) return route('shop.show', $product);

        return '#';
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

    $products = $products
        ?? ($collection->relationLoaded('products')
            ? $collection->products
            : ($collection->products ?? collect()));

    $collectionImage = $resolveMediaUrl([
        $collection->image_path ?? null,
        'images/home/occasion-weeknight.png',
        'images/home/party-platter.png',
        'images/home/family-meal.png',
    ]);
@endphp

<div class="bg-gray-50 dark:bg-gray-950 min-h-screen">
    <div class="max-w-6xl mx-auto px-4 py-6 space-y-6">

        {{-- Breadcrumb --}}
        <nav class="text-[11px] text-gray-500 dark:text-gray-400">
            <a href="{{ route('home') }}" class="hover:underline">Home</a>
            <span class="mx-1">/</span>
            <a href="{{ $shopUrl }}" class="hover:underline">Shop</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700 dark:text-gray-200">{{ $collection->name ?? 'Collection' }}</span>
        </nav>

        {{-- Hero --}}
        <section class="grid gap-4 lg:grid-cols-[1.05fr,0.95fr] items-stretch">
            <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-gradient-to-br from-white via-slate-50 to-sky-50 dark:from-gray-900 dark:via-gray-900 dark:to-slate-900 p-6 sm:p-8 flex flex-col justify-between min-h-[280px]">
                <div class="space-y-4">
                    @if(!empty($collection->eyebrow))
                        <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white/90 dark:bg-gray-950/50 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.14em] text-gray-600 dark:text-gray-300">
                            {{ $collection->eyebrow }}
                        </span>
                    @endif

                    <div class="space-y-3">
                        <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-gray-900 dark:text-gray-50 leading-tight">
                            {{ $collection->name ?? 'Collection' }}
                        </h1>

                        @if(!empty($collection->description))
                            <p class="max-w-xl text-sm sm:text-base text-gray-600 dark:text-gray-300 leading-relaxed">
                                {{ $collection->description }}
                            </p>
                        @else
                            <p class="max-w-xl text-sm sm:text-base text-gray-600 dark:text-gray-300 leading-relaxed">
                                Browse products selected for this collection.
                            </p>
                        @endif
                    </div>

                    <div class="flex flex-wrap gap-3 text-sm">
                        <a href="{{ $shopUrl }}"
                           class="inline-flex items-center justify-center rounded-sm bg-gray-900 px-4 py-2.5 font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">
                            Shop all products
                        </a>

                        <a href="#collection-products"
                           class="inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-4 py-2.5 font-medium text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-800">
                            View products
                        </a>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap gap-2 text-[11px]">
                    @if(!empty($collection->kind))
                        <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white/80 dark:bg-gray-950/40 px-3 py-1 text-gray-600 dark:text-gray-300">
                            {{ ucfirst($collection->kind) }}
                        </span>
                    @endif

                    <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white/80 dark:bg-gray-950/40 px-3 py-1 text-gray-600 dark:text-gray-300">
                        {{ $products->count() }} items
                    </span>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden min-h-[280px] max-h-[500px] relative">
                @if($collectionImage)
                    <img
                        src="{{ $collectionImage }}"
                        alt="{{ $collection->name ?? 'Collection' }}"
                        class="h-full w-full object-cover"
                    >
                @else
                    <div class="absolute inset-0 bg-gradient-to-br from-sky-100 via-cyan-50 to-white dark:from-sky-950/30 dark:via-cyan-950/20 dark:to-gray-900"></div>
                @endif
            </div>
        </section>

        {{-- Products --}}
        <section id="collection-products" class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-50">Products in this collection</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        Browse products selected for {{ $collection->name ?? 'this collection' }}.
                    </p>
                </div>

                <a href="{{ $shopUrl }}"
                   class="inline-flex items-center text-sm font-medium text-gray-700 dark:text-gray-200 hover:underline">
                    View full shop
                </a>
            </div>

            @if($products->isEmpty())
                <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                    No products have been attached to this collection yet.
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach($products as $product)
                        @php
                            $productImage = $productPrimaryImageUrl($product);
                        @endphp

                        <a href="{{ $productUrl($product) }}"
                           class="group rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden hover:-translate-y-0.5 transition">
                            <div class="aspect-[4/3] bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                @if($productImage)
                                    <img
                                        src="{{ $productImage }}"
                                        alt="{{ $product->name }}"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                                    >
                                @else
                                    <div class="h-full w-full flex items-center justify-center text-[11px] text-gray-400 dark:text-gray-500">
                                        No image
                                    </div>
                                @endif
                            </div>

                            <div class="p-4 space-y-2">
                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50 line-clamp-2">
                                    {{ $product->name }}
                                </div>

                                @if(!empty($product->short_description))
                                    <p class="text-[12px] text-gray-600 dark:text-gray-300 line-clamp-2">
                                        {{ $product->short_description }}
                                    </p>
                                @endif

                                <div class="pt-1 inline-flex items-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] font-medium text-gray-700 dark:text-gray-200 group-hover:bg-gray-50 dark:group-hover:bg-gray-800">
                                    View product
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</div>
@endsection