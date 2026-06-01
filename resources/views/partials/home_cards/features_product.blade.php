<section id="featured" class="space-y-3">
    @if($featuredProducts->isEmpty())
        {{-- <p class="text-xs text-gray-500 dark:text-gray-400">
            No featured products yet. Mark products as “Featured” in admin.
        </p> --}}
    @else
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                Featured products
            </h2>
            {{-- <a href="{{ route('admin.products.index', ['flag' => 'featured']) }}"
                class="hidden sm:inline text-[11px] text-gray-500 dark:text-gray-400 underline">
                Manage featured in admin
            </a> --}}
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($featuredProducts as $product)
                @include('partials.home_cards.product_card', ['product' => $product])
            @endforeach
        </div>
    @endif
</section>