@extends('layouts.customer')

@section('title', config('app.name') . ' - Home')

@section('content')
@php
    use Illuminate\Support\Facades\Route;

    $has = fn(string $r) => Route::has($r);
    $shopUrl = $has('shop.index') ? route('shop.index') : '#';
    $supportUrl = $has('tickets.create') ? route('tickets.create') : ($has('account.tickets.create') ? route('account.tickets.create') : null);

    $cartAddUrl = $has('cart.items.store') ? route('cart.items.store') : ($has('cart.add') ? route('cart.add') : ($has('cart.store') ? route('cart.store') : null));
    $wishlistToggleUrl = $has('wishlist.toggle') ? route('wishlist.toggle') : ($has('wishlist.store') ? route('wishlist.store') : null);
    $wishlistUrl = $has('wishlist.index') ? route('wishlist.index') : ($has('wishlist') ? route('wishlist') : null);
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
        return $has('shop.index') ? route('shop.index', ['category' => $category->id]) : '#?category=' . $category->id;
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
        return filled($collection->cta_url ?? null) ? $collection->cta_url : $shopUrl;
    };

    $resolveMediaUrl = $mediaUrlResolver ?? fn($pathOrPaths) => null;

    $recipeText = function ($recipe, $field) {
        if (method_exists($recipe, 'tr')) return $recipe->tr($field);
        $value = $recipe->{$field} ?? null;
        if (is_array($value)) return $value[app()->getLocale()] ?? $value['en'] ?? (count($value) ? reset($value) : null);
        return $value;
    };

    $recipeList = function ($recipe, $field) {
        if (method_exists($recipe, 'trList')) return $recipe->trList($field);
        $value = $recipe->{$field} ?? [];
        if (!is_array($value)) return [];
        if (isset($value[app()->getLocale()]) && is_array($value[app()->getLocale()])) return $value[app()->getLocale()];
        if (isset($value['en']) && is_array($value['en'])) return $value['en'];
        return array_values($value);
    };
@endphp

<div class="bg-gray-50 dark:bg-gray-950 min-h-screen">
    <div class="max-w-6xl mx-auto px-4 py-6 space-y-6">
        @if($homeAnnouncement)
            @include('partials.home_cards.announcement_banner', ['announcement' => $homeAnnouncement])
        @endif

        @foreach($homeSections as $section)
            <div id="home-section-{{ $section->key }}" class="scroll-mt-24">
                @switch($section->type)
                    @case('hero')
                        @include('home.sections.hero', ['section' => $section])
                        @break

                    @case('categories')
                        @include('home.sections.categories', ['section' => $section])
                        @break

                    @case('product_showcase')
                        @include('home.sections.product-showcase', ['section' => $section])
                        @break

                    @case('collections')
                        @include('home.sections.collections', ['section' => $section])
                        @break

                    @case('chef_picks')
                        @include('home.sections.chef-picks', ['section' => $section])
                        @break

                    @case('trust_cards')
                        @include('home.sections.trust', ['section' => $section])
                        @break

                    @case('support_cta')
                        @include('home.sections.support-cta', ['section' => $section])
                        @break
                @endswitch
            </div>
        @endforeach
    </div>
</div>

@if(Route::has('product.variants.options'))
    @include('home.sections.product-card-scripts')
@endif
@endsection
