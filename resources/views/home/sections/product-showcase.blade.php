@if($homeProductShowcase->isNotEmpty())
    <section class="space-y-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                @if($section->eyebrow)
                    <p class="text-[11px] uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">{{ $section->eyebrow }}</p>
                @endif
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-50">{{ $section->title ?: 'Popular frozen picks' }}</h2>
                @if($section->subtitle)
                    <p class="mt-1 max-w-2xl text-sm text-gray-600 dark:text-gray-300">{{ $section->subtitle }}</p>
                @endif
            </div>
            @if($section->cta_text && $section->cta_url)
                <a href="{{ url($section->cta_url) }}" class="text-[12px] font-medium text-gray-700 dark:text-gray-200 hover:underline">{{ $section->cta_text }}</a>
            @endif
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($homeProductShowcase as $product)
                @include('partials.home_cards.product_card', [
                    'product' => $product,
                    'productUrl' => $productUrl($product),
                    'cartAddUrl' => $cartAddUrl,
                    'wishlistToggleUrl' => $wishlistToggleUrl,
                    'wishlistUrl' => $wishlistUrl,
                    'loginUrl' => $loginUrl,
                    'flagEmoji' => $flagEmoji,
                ])
            @endforeach
        </div>
    </section>
@endif
