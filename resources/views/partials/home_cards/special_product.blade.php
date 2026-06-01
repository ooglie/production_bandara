<section id="specials" class="space-y-3">
    @if($specialProducts->isEmpty())
        {{-- <p class="text-xs text-gray-500 dark:text-gray-400">
            No active specials. Mark products as “Special” with a special price and (optionally) a time window.
        </p> --}}
    @else
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                Special offers
            </h2>
            {{-- <a href="{{ route('admin.products.index', ['flag' => 'special']) }}"
                class="hidden sm:inline text-[11px] text-gray-500 dark:text-gray-400 underline">
                Manage specials in admin
            </a> --}}
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($specialProducts as $product)
                @include('partials.home_cards.product_card', ['product' => $product])
            @endforeach
        </div>
    @endif
</section>