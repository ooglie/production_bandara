<section id="new" class="space-y-3">
    @if($newProducts->isEmpty())
        {{-- <p class="text-xs text-gray-500 dark:text-gray-400">
            No “New” products yet. Mark products as “New” in admin.
        </p> --}}
    @else
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                New arrivals
            </h2>
            {{-- <a href="{{ route('admin.products.index', ['flag' => 'new']) }}"
                class="hidden sm:inline text-[11px] text-gray-500 dark:text-gray-400 underline">
                Manage new in admin
            </a> --}}
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($newProducts as $product)
                @include('partials.home_cards.product_card', ['product' => $product])
            @endforeach
        </div>
    @endif
</section>