@extends('layouts.customer')

@section('title', 'Wishlist')

@section('content')
@php
    use Illuminate\Support\Facades\Storage;

    $b2bTerms = app(\App\Services\B2BTermsService::class);
    $isB2BWishlist = (bool) ($isB2BWishlist ?? false);
@endphp

<div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Wishlist
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Save products you want to order later.
            </p>
        </div>
    </div>

    @if($items->isEmpty())
        <div class="rounded border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 text-xs text-gray-500 dark:text-gray-400">
            Your wishlist is empty.
        </div>
    @else
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($items as $item)
                @php
                    $product = $item->product;
                    if (!$product) continue;

                    $variant = $item->variant;
                    $canB2BBuy = $b2bTerms->canBuy(auth()->user(), $product, $variant);
                    $productUrl = route('product.show', $product);
                    $cartStoreUrl = route('cart.store');
                    $destroyUrl = route('wishlist.destroy', $item);

                    /*
                     * Product cards may already carry their prepared piece selector.
                     * The pack_type fallback is essential on the wishlist because this
                     * page previously submitted variable-weight products without a slab.
                     */
                    $pieceSelector = $product->piece_selector ?? ['enabled' => false];
                    $pieceBands = collect(data_get($pieceSelector, 'bands', []))->values();
                    $requiresPieceSelection = ! $variant && (
                        (bool) data_get($pieceSelector, 'enabled', false)
                        || (string) ($product->pack_type ?? '') === 'variable_weight'
                    );
                    $requiresVariantSelection = ! $variant
                        && ! $requiresPieceSelection
                        && (string) ($product->type ?? '') === 'variable';
                @endphp
                <div class="border border-gray-200 dark:border-gray-800 rounded-sm bg-white dark:bg-gray-900 p-3 flex flex-col text-xs">
                    <div class="aspect-[4/3] rounded-sm bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-3 overflow-hidden">
                        @if($product->primary_image)
                            <img
                                src="{{ Storage::disk(config('media.public_disk', 'public'))->url($product->primary_image) }}"
                                alt="{{ $product->name }}"
                                class="object-cover w-full h-full"
                            >
                        @else
                            <span class="text-[11px] text-gray-400 dark:text-gray-500">
                                No image
                            </span>
                        @endif
                    </div>

                    <div class="flex-1 space-y-1">
                        <a href="{{ $productUrl }}"
                           class="text-xs font-medium text-gray-900 dark:text-gray-50 line-clamp-2 hover:underline">
                            {{ $product->name }}
                        </a>

                        @if($variant)
                            @php
                                $parts = [];
                                foreach ($variant->attributeValues ?? [] as $value) {
                                    $parts[] = $value->attribute->name . ': ' . $value->value;
                                }
                                $variantName = trim((string) ($variant->name ?? ''));
                                $packType = (string) ($variant->pack_type ?? '');
                                if ($variantName !== '') {
                                    $variantLabel = $variantName;
                                } elseif ($packType === 'fixed_piece_pack' && (float) ($variant->pieces_per_pack ?? 0) > 0) {
                                    $variantLabel = rtrim(rtrim(number_format((float) $variant->pieces_per_pack, 3), '0'), '.') . ' pcs pack';
                                } elseif ($packType === 'fixed_weight_pack' && (float) ($variant->product_weight ?? 0) > 0) {
                                    $variantLabel = rtrim(rtrim(number_format((float) $variant->product_weight, 3), '0'), '.') . ' kg pack';
                                } else {
                                    $variantLabel = implode(' · ', $parts) ?: ('Variant #'.$variant->id);
                                }
                            @endphp
                            <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                {{ $variantLabel }}
                            </div>
                        @endif
                    </div>

                    <div class="mt-2 flex items-center justify-between gap-2">
                        @if(! $isB2BWishlist || $canB2BBuy)
                            @if($requiresPieceSelection)
                                @if($pieceBands->isNotEmpty())
                                    <details class="relative js-card-option-menu js-wishlist-slab-menu">
                                        <summary
                                            title="Choose slab"
                                            class="list-none inline-flex items-center justify-center gap-1.5 rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-[11px] hover:bg-gray-800 dark:hover:bg-gray-200 cursor-pointer"
                                            style="list-style: none;"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                 class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5"
                                                 aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                      d="M5.25 6.75h.008v.008H5.25V6.75Zm0 4.5h.008v.008H5.25v-.008Zm0 4.5h.008v.008H5.25v-.008ZM8.25 6.75h7.5M8.25 11.25h5.25M8.25 15.75h4.5M17.25 13.5v6M14.25 16.5h6" />
                                            </svg>
                                            <span>Choose slab</span>
                                        </summary>

                                        <div class="absolute left-0 z-50 mt-2 w-72 max-h-96 overflow-y-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-lg p-2">
                                            <div class="px-2 pb-1 text-[10px] uppercase tracking-wide text-gray-400">
                                                Choose slab / piece
                                            </div>

                                            <div class="space-y-2">
                                                @foreach($pieceBands as $band)
                                                    @php
                                                        $choices = collect($band['choices'] ?? [])->values();
                                                        $bandKey = (string) ($band['key'] ?? '');
                                                        $bandCount = (int) ($band['count'] ?? $choices->count());
                                                    @endphp

                                                    @if($choices->isNotEmpty())
                                                        <div class="rounded-lg border border-gray-100 dark:border-gray-800 p-1">
                                                            <div class="px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400">
                                                                {{ $band['label'] ?? 'Available slabs' }} · {{ $bandCount }} available
                                                            </div>

                                                            <div class="space-y-1">
                                                                @foreach($choices as $choice)
                                                                    <form method="POST" action="{{ $cartStoreUrl }}" class="block">
                                                                        @csrf
                                                                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                                        <input type="hidden" name="piece_weight_kg" value="{{ number_format((float) ($choice['weight_kg'] ?? 0), 3, '.', '') }}">
                                                                        <input type="hidden" name="quantity" value="1">

                                                                        <button type="submit"
                                                                                class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-800">
                                                                            <span class="min-w-0">
                                                                                <span class="block text-[12px] font-medium text-gray-900 dark:text-gray-50">
                                                                                    {{ $choice['weight_label'] ?? (rtrim(rtrim(number_format((float) ($choice['weight_kg'] ?? 0), 3), '0'), '.') . ' kg') }}
                                                                                    @if((int) ($choice['count'] ?? 1) > 1)
                                                                                        <span class="text-[10px] text-gray-400">× {{ (int) $choice['count'] }}</span>
                                                                                    @endif
                                                                                </span>
                                                                                <span class="mt-0.5 block text-[10px] text-gray-500 dark:text-gray-400">
                                                                                    Add this slab
                                                                                </span>
                                                                            </span>
                                                                            @if(isset($choice['price']))
                                                                                <span class="shrink-0 text-[12px] font-semibold text-gray-900 dark:text-gray-50">
                                                                                    ₹{{ number_format((float) $choice['price'], 2) }}
                                                                                </span>
                                                                            @endif
                                                                        </button>
                                                                    </form>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @else
                                                        <a href="{{ $productUrl }}{{ $bandKey !== '' ? '?band='.urlencode($bandKey) : '' }}#piece-selector-root"
                                                           class="block rounded-lg px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-800">
                                                            <div class="text-[12px] font-medium text-gray-900 dark:text-gray-50">
                                                                {{ $band['label'] ?? 'Choose slab' }}
                                                            </div>
                                                            <div class="mt-0.5 text-[10px] text-gray-500 dark:text-gray-400">
                                                                {{ $bandCount }} available
                                                                @if(isset($band['price_min']))
                                                                    · ₹{{ number_format((float) $band['price_min'], 2) }}
                                                                    @if(isset($band['price_max']) && (float) $band['price_max'] > (float) $band['price_min'])
                                                                        – ₹{{ number_format((float) $band['price_max'], 2) }}
                                                                    @endif
                                                                @endif
                                                            </div>
                                                        </a>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    </details>
                                @else
                                    <a href="{{ $productUrl }}#piece-selector-root"
                                       title="Choose slab"
                                       class="inline-flex items-center justify-center gap-1.5 rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-[11px] hover:bg-gray-800 dark:hover:bg-gray-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                             class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5"
                                             aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M5.25 6.75h.008v.008H5.25V6.75Zm0 4.5h.008v.008H5.25v-.008Zm0 4.5h.008v.008H5.25v-.008ZM8.25 6.75h7.5M8.25 11.25h5.25M8.25 15.75h4.5M17.25 13.5v6M14.25 16.5h6" />
                                        </svg>
                                        <span>Choose slab</span>
                                    </a>
                                @endif
                            @elseif($requiresVariantSelection)
                                <a href="{{ $productUrl }}"
                                   title="Choose pack"
                                   class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-[11px] hover:bg-gray-800 dark:hover:bg-gray-200">
                                    Choose pack
                                </a>
                            @else
                                <form method="POST" action="{{ $cartStoreUrl }}">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    @if($variant)
                                        <input type="hidden" name="product_variant_id" value="{{ $variant->id }}">
                                    @endif
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit"
                                            class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-[11px] hover:bg-gray-800 dark:hover:bg-gray-200">
                                        {{ $isB2BWishlist ? 'Add to B2B cart' : 'Add to cart' }}
                                    </button>
                                </form>
                            @endif
                        @else
                            <a href="{{ $productUrl }}" class="inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] hover:bg-gray-100 dark:hover:bg-gray-800">
                                Request access
                            </a>
                        @endif

                        <form method="POST" action="{{ $destroyUrl }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="text-[11px] text-red-600 hover:text-red-700">
                                Remove
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@once
    @push('scripts')
        <script>
        (function () {
            if (window.__bandaraProductCardMenuCloserBound) {
                return;
            }

            window.__bandaraProductCardMenuCloserBound = true;

            function closeOpenProductCardMenus(exceptMenu) {
                document.querySelectorAll('details.js-card-option-menu[open]').forEach(function (menu) {
                    if (menu !== exceptMenu) {
                        menu.removeAttribute('open');
                    }
                });
            }

            document.addEventListener('click', function (event) {
                var target = event.target;
                if (!target) {
                    return;
                }

                document.querySelectorAll('details.js-card-option-menu[open]').forEach(function (menu) {
                    if (!menu.contains(target)) {
                        menu.removeAttribute('open');
                    }
                });
            });

            document.addEventListener('toggle', function (event) {
                var menu = event.target;
                if (!menu || menu.tagName !== 'DETAILS' || !menu.classList || !menu.classList.contains('js-card-option-menu') || !menu.open) {
                    return;
                }

                closeOpenProductCardMenus(menu);
            }, true);

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeOpenProductCardMenus(null);
                }
            });
        })();
        </script>
    @endpush
@endonce
@endsection
