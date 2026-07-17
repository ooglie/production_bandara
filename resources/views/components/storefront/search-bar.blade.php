@props([
    'mobile' => false,
    'tablet' => false,
    'placeholder' => null,
])

@php
    $searchValue = request()->routeIs('shop.*')
        ? trim((string) request()->query('q', ''))
        : '';

    $resolvedPlaceholder = $placeholder ?: 'Search products';

    $formClass = $mobile
        ? 'mx-auto w-full max-w-[360px]'
        : ($tablet ? 'mx-auto w-full max-w-[360px]' : 'w-full min-w-0');

    $shopCategoryFilters = request()->routeIs('shop.*')
        ? collect((array) request()->query('category', []))->filter()->values()->all()
        : [];
    $shopSort = request()->routeIs('shop.*') ? trim((string) request()->query('sort', '')) : '';

    $clearQuery = request()->routeIs('shop.*') ? request()->query() : [];
    unset($clearQuery['q'], $clearQuery['page']);
    $clearUrl = route('shop.index', $clearQuery);
@endphp

<form method="GET" action="{{ route('shop.index') }}" role="search" {{ $attributes->class([$formClass]) }}>
    @foreach($shopCategoryFilters as $categoryId)
        <input type="hidden" name="category[]" value="{{ $categoryId }}">
    @endforeach

    @if($shopSort !== '')
        <input type="hidden" name="sort" value="{{ $shopSort }}">
    @endif
    <label for="{{ $mobile ? 'mobile-storefront-search' : ($tablet ? 'tablet-storefront-search' : 'desktop-storefront-search') }}" class="sr-only">
        Search products
    </label>

    <div class="relative">
        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-gray-400 dark:text-gray-500" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="11" cy="11" r="6.5"></circle>
                <path stroke-linecap="round" d="m16 16 4 4"></path>
            </svg>
        </span>

        <input
            id="{{ $mobile ? 'mobile-storefront-search' : ($tablet ? 'tablet-storefront-search' : 'desktop-storefront-search') }}"
            type="search"
            name="q"
            value="{{ $searchValue }}"
            placeholder="{{ $resolvedPlaceholder }}"
            enterkeyhint="search"
            autocomplete="off"
            class="h-8 w-full rounded-sm border border-gray-300 bg-white pl-8 pr-14 text-[11px] text-gray-900 placeholder:text-gray-400 focus:border-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-gray-600 dark:focus:ring-gray-700"
        >

        <div class="absolute inset-y-0 right-0 flex items-center">
            @if($searchValue !== '')
                <a
                    href="{{ $clearUrl }}"
                    class="inline-flex h-full w-7 items-center justify-center text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
                    aria-label="Clear product search"
                    title="Clear search"
                >
                    <span aria-hidden="true">&times;</span>
                </a>
            @endif

            <button
                type="submit"
                class="inline-flex h-full w-7 items-center justify-center text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
                aria-label="Search products"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="11" cy="11" r="6.5"></circle>
                    <path stroke-linecap="round" d="m16 16 4 4"></path>
                </svg>
            </button>
        </div>
    </div>
</form>
