@extends('layouts.customer')

@section('title', 'Wishlist')

@section('content')
@php
    use Illuminate\Support\Facades\Storage;

    $b2bTerms = app(\App\Services\B2BTermsService::class);
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

    @if(session('status'))
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

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
                    $canB2BBuy = $b2bTerms->canBuy(auth()->user(), $product, $variant?->sellUnit, $variant);
                    $productUrl = route('product.show', $product);
                    $cartStoreUrl = route('cart.store');
                    $destroyUrl = route('wishlist.destroy', $item);
                @endphp
                <div class="border border-gray-200 dark:border-gray-800 rounded-sm bg-white dark:bg-gray-900 p-3 flex flex-col text-xs">
                    <div class="aspect-[4/3] rounded-sm bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-3 overflow-hidden">
                        @if($product->primary_image)
                            <img
                                src="{{ Storage::url($product->primary_image) }}"
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
                            <form method="POST" action="{{ $cartStoreUrl }}">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                @if($variant)
                                    <input type="hidden" name="product_variant_id" value="{{ $variant->id }}">
                                @endif
                                <button type="submit"
                                        class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-[11px] hover:bg-gray-800 dark:hover:bg-gray-200">
                                    {{ $isB2BWishlist ? 'Add to B2B cart' : 'Add to cart' }}
                                </button>
                            </form>
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
@endsection
