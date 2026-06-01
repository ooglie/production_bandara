@php
    $pieceSelector = $product->piece_selector ?? ['enabled' => false];
    $hasPieceSelector = (bool) data_get($pieceSelector, 'enabled', false);
    $pieceBands = data_get($pieceSelector, 'bands', []);
    $hasMultipleBands = is_array($pieceBands) && count($pieceBands) > 1;
@endphp

{{-- Wishlist --}}
@if(auth()->check())
    @if($wishlistToggleUrl)
        <form method="POST" action="{{ $wishlistToggleUrl }}" class="js-wishlist-form">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <input type="hidden" name="product_variant_id" class="js-variant-input" value="">
            <button
                type="submit"
                title="Add to wishlist"
                class="inline-flex items-center justify-center w-9 h-9 rounded-sm
                    border border-gray-200 dark:border-gray-700
                    bg-white/80 dark:bg-gray-950/70 backdrop-blur
                    text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-900
                    disabled:opacity-40 cursor-pointer"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21 8.25c0-2.485-2.099-4.5-4.687-4.5-1.935 0-3.597 1.126-4.313 2.733-.716-1.607-2.378-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
                </svg>
            </button>
        </form>
    @elseif($wishlistUrl)
        <a href="{{ $wishlistUrl }}"
            title="Wishlist"
            class="inline-flex items-center justify-center w-9 h-9 rounded-sm
                    border border-gray-200 dark:border-gray-700
                    bg-white/80 dark:bg-gray-950/70 backdrop-blur
                    text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-900
                    disabled:opacity-40"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 8.25c0-2.485-2.099-4.5-4.687-4.5-1.935 0-3.597 1.126-4.313 2.733-.716-1.607-2.378-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
            </svg>
        </a>
    @endif
@else
    @if($loginUrl)
        <a href="{{ $loginUrl }}"
            title="Login to use wishlist"
            class="inline-flex items-center justify-center w-9 h-9 rounded-sm
                    border border-gray-200 dark:border-gray-700
                    bg-white/80 dark:bg-gray-950/70 backdrop-blur
                    text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-900 cursor-pointer"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 8.25c0-2.485-2.099-4.5-4.687-4.5-1.935 0-3.597 1.126-4.313 2.733-.716-1.607-2.378-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
            </svg>
        </a>
    @endif
@endif

{{-- Add to cart / Choose size --}}
@if($hasPieceSelector)
    @if($inStock)
        @if($hasMultipleBands)
            <details class="relative">
                <summary
                    title="Choose size"
                    class="list-none inline-flex items-center justify-center w-9 h-9 rounded-sm
                           border border-gray-200 dark:border-gray-700
                           bg-white/80 dark:bg-gray-950/70 backdrop-blur
                           text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-900
                           cursor-pointer"
                    style="list-style: none;"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                         class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M4.5 7.5h15M6 12h12M8.25 16.5h7.5" />
                    </svg>
                </summary>

                <div class="absolute right-0 z-50 mt-2 w-56 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-lg p-2">
                    <div class="px-2 pb-1 text-[10px] uppercase tracking-wide text-gray-400">
                        Choose size
                    </div>

                    <div class="space-y-1">
                        @foreach($pieceBands as $band)
                            <a href="{{ route('product.show', $product) }}?band={{ urlencode($band['key']) }}#piece-selector-root"
                               class="block rounded-lg px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-800">
                                <div class="text-[12px] font-medium text-gray-900 dark:text-gray-50">
                                    {{ $band['label'] }}
                                </div>
                                <div class="mt-0.5 text-[10px] text-gray-500 dark:text-gray-400">
                                    {{ $band['count'] }} available ·
                                    ₹{{ number_format((float) $band['price_min'], 2) }}
                                    @if((float) $band['price_max'] > (float) $band['price_min'])
                                        – ₹{{ number_format((float) $band['price_max'], 2) }}
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </details>
        @else
            <a href="{{ route('product.show', $product) }}#piece-selector-root"
                title="Choose slab size"
                class="inline-flex items-center justify-center w-9 h-9 rounded-sm
                        border border-gray-200 dark:border-gray-700
                        bg-white/80 dark:bg-gray-950/70 backdrop-blur
                        text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-900 cursor-pointer"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                     class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M4.5 7.5h15M6 12h12M8.25 16.5h7.5" />
                </svg>
            </a>
        @endif
    @else
        <button
            type="button"
            title="Out of stock"
            disabled
            class="inline-flex items-center justify-center w-9 h-9 rounded-sm
                   border border-gray-200 dark:border-gray-700
                   bg-white/80 dark:bg-gray-950/70 backdrop-blur
                   text-gray-700 dark:text-gray-200 disabled:opacity-40"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                 class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M4.5 7.5h15M6 12h12M8.25 16.5h7.5" />
            </svg>
        </button>
    @endif
@elseif($cartAddUrl)
    <form method="POST" action="{{ $cartAddUrl }}" class="js-cart-form">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <input type="hidden" name="product_variant_id" class="js-variant-input" value="">
        <input type="hidden" name="quantity" value="1">

        <button
            type="submit"
            title="{{ $inStock ? ($isVariable ? 'Select a slab and add to cart' : 'Add to cart') : 'Out of stock' }}"
            @disabled(!$inStock)
            class="js-cart-btn inline-flex items-center justify-center w-9 h-9 rounded-sm
                border border-gray-200 dark:border-gray-700
                bg-white/80 dark:bg-gray-950/70 backdrop-blur
                text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-900
                disabled:opacity-40 cursor-pointer"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.25 3h1.5l1.5 12h13.5l1.5-9H6.75" />
                <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 20.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm10.5 0a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" />
            </svg>
        </button>
    </form>
@else
    <a href="{{ route('product.show', $product) }}"
        title="View product"
        class="inline-flex items-center justify-center w-9 h-9 rounded-sm
                border border-gray-200 dark:border-gray-700
                bg-white/80 dark:bg-gray-950/70 backdrop-blur
                text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-900 cursor-pointer"
    >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round"
                    d="M2.25 3h1.5l1.5 12h13.5l1.5-9H6.75" />
            <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 20.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm10.5 0a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" />
        </svg>
    </a>
@endif

{{-- View product (eye) --}}
<a href="{{ route('product.show', $product) }}"
   title="View details"
   class="inline-flex items-center justify-center w-9 h-9 rounded-sm
          border border-gray-200 dark:border-gray-700
          bg-white/80 dark:bg-gray-950/70 backdrop-blur
          text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-900"
>
    <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
        <path stroke="currentColor" stroke-width="1" d="M21 12c0 1.2-4.03 6-9 6s-9-4.8-9-6c0-1.2 4.03-6 9-6s9 4.8 9 6Z"/>
        <path stroke="currentColor" stroke-width="1" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
    </svg>
</a>