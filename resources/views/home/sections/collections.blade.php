@php
    use Illuminate\Support\Str;

    $cards = $occasionCards ?? collect();

    $cardUrl = function ($card) use ($collectionUrl, $shopUrl) {
        if (! empty($card->collection)) {
            return $collectionUrl($card->collection);
        }

        $url = trim((string) ($card->cta_url ?? ''));
        if ($url === '') {
            return $shopUrl;
        }

        if (Str::startsWith($url, ['http://', 'https://', '#'])) {
            return $url;
        }

        return Str::startsWith($url, '/') ? url($url) : url('/' . $url);
    };
@endphp

<section id="occasions" class="space-y-4">
    <div>
        @if($section->eyebrow)
            <p class="text-[11px] uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">{{ $section->eyebrow }}</p>
        @endif
        <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-50">{{ $section->title ?: 'Curated collections' }}</h2>
        @if($section->subtitle)
            <p class="mt-1 max-w-2xl text-sm text-gray-600 dark:text-gray-300">{{ $section->subtitle }}</p>
        @endif
    </div>

    @if($cards->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
            Add active product collections or custom homepage items to show them here.
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-3">
            @foreach($cards as $card)
                @php($collectionImage = $resolveMediaUrl($card->image_path ?? null))
                <a href="{{ $cardUrl($card) }}" class="group overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 hover:shadow-sm transition">
                    <div class="aspect-[16/10] bg-gray-100 dark:bg-gray-800 overflow-hidden">
                        @if($collectionImage)
                            <img src="{{ $collectionImage }}" alt="{{ $card->title ?: 'Homepage collection' }}" class="h-full w-full object-cover group-hover:scale-[1.02] transition-transform duration-300">
                        @else
                            <div class="h-full w-full bg-gradient-to-br from-gray-100 via-sky-50 to-white dark:from-gray-800 dark:via-sky-950/20 dark:to-gray-900"></div>
                        @endif
                    </div>
                    <div class="p-4">
                        @if($card->eyebrow)
                            <div class="text-[10px] uppercase tracking-wide text-gray-400">{{ $card->eyebrow }}</div>
                        @endif
                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $card->title }}</div>
                        @if($card->description)
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300 line-clamp-2">{{ $card->description }}</p>
                        @endif
                        @if(! is_null($card->products_count))
                            <div class="mt-3 text-[11px] text-gray-500 dark:text-gray-400">{{ $card->products_count }} products</div>
                        @elseif($card->cta_text)
                            <div class="mt-3 text-[11px] font-medium text-gray-600 dark:text-gray-300">{{ $card->cta_text }}</div>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</section>
