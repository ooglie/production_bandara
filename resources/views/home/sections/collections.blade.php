@php
    use Illuminate\Support\Str;

    $cards = $occasionCards ?? collect();
    $occasionSlugs = ['weeknight-wins', 'party-starters', 'family-table-favourites'];

    $cardUrl = function ($card) use ($collectionUrl, $shopUrl, $occasionSlugs) {
        $linkedSlug = Str::lower(trim((string) data_get($card, 'collection.slug', '')));
        $storedUrl = Str::lower(trim((string) ($card->cta_url ?? '')));
        $context = Str::lower(implode(' ', array_filter([
            $card->title ?? null,
            $card->eyebrow ?? null,
            data_get($card, 'collection.name'),
        ])));

        $approvedSlug = match (true) {
            in_array($linkedSlug, $occasionSlugs, true) => $linkedSlug,
            Str::contains($storedUrl, 'weeknight-wins'),
            Str::contains($context, ['weeknight', 'quick meal']) => 'weeknight-wins',
            Str::contains($storedUrl, 'party-starters'),
            Str::contains($context, ['entertaining', 'party starter']) => 'party-starters',
            Str::contains($storedUrl, 'family-table-favourites'),
            Str::contains($context, ['everyday staple', 'family table', 'family favourite']) => 'family-table-favourites',
            default => null,
        };

        /* Keep the three approved collection links exact and host-relative. */
        if ($approvedSlug) {
            return '/collections/' . $approvedSlug;
        }

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

        return Str::startsWith($url, '/') ? $url : '/' . ltrim($url, '/');
    };

    /*
     * Customer-facing copy for the three occasion collections.
     * It is intentionally scoped by collection slug so custom cards and any
     * future collections continue to use their own admin-managed content.
     */
    $occasionCopy = [
        'weeknight-wins' => [
            'eyebrow' => 'Quick meals',
            'title' => 'Weeknight wins',
            'description' => 'Flexible favourites for satisfying dinners, easy plates and evenings that need less planning.',
            'cta' => 'Explore Weeknight wins',
        ],
        'party-starters' => [
            'eyebrow' => 'Party starters',
            'title' => 'Entertaining',
            'description' => 'Shareable bites, cheeses and easy sides selected for relaxed hosting and generous tables.',
            'cta' => 'Explore Entertaining',
        ],
        'family-table-favourites' => [
            'eyebrow' => 'Family favourites',
            'title' => 'Everyday staples',
            'description' => 'Versatile meats, seafood, cheese and ready-to-cook favourites for meals made again and again.',
            'cta' => 'Explore Everyday staples',
        ],
    ];

    $occasionKey = function ($card) use ($occasionCopy) {
        $slug = Str::lower(trim((string) data_get($card, 'collection.slug', '')));

        if (array_key_exists($slug, $occasionCopy)) {
            return $slug;
        }

        $url = Str::lower(trim((string) ($card->cta_url ?? '')));
        foreach (array_keys($occasionCopy) as $knownSlug) {
            if ($url !== '' && Str::contains($url, $knownSlug)) {
                return $knownSlug;
            }
        }

        $context = Str::lower(implode(' ', array_filter([
            $card->title ?? null,
            $card->eyebrow ?? null,
            data_get($card, 'collection.name'),
        ])));

        return match (true) {
            Str::contains($context, ['weeknight', 'quick meal']) => 'weeknight-wins',
            Str::contains($context, ['entertaining', 'party starter']) => 'party-starters',
            Str::contains($context, ['everyday staple', 'family table', 'family favourite']) => 'family-table-favourites',
            default => null,
        };
    };
@endphp

<section id="occasions" class="space-y-4">
    <div>
        <p class="text-[11px] uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">Shop by occasion</p>
        <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-50">Curated for the way you cook</h2>
        <p class="mt-1 max-w-2xl text-sm text-gray-600 dark:text-gray-300">
            From quick dinners to relaxed entertaining and dependable everyday favourites, these collections make choosing simpler.
        </p>
    </div>

    @if($cards->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 bg-white px-6 py-10 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
            Add active product collections or custom homepage items to show them here.
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-3">
            @foreach($cards as $card)
                @php
                    $collectionImage = $resolveMediaUrl($card->image_path ?? null);
                    $copyKey = $occasionKey($card);
                    $copy = $copyKey ? ($occasionCopy[$copyKey] ?? null) : null;
                    $cardTitle = $copy['title'] ?? ($card->title ?: 'Curated collection');
                    $cardEyebrow = $copy['eyebrow'] ?? ($card->eyebrow ?? null);
                    $cardDescription = $copy['description'] ?? ($card->description ?? null);
                    $cardCta = $copy['cta'] ?? ($card->cta_text ?: 'Explore collection');
                    $productCount = $card->products_count ?? data_get($card, 'collection.products_count');
                @endphp

                <a
                    href="{{ $cardUrl($card) }}"
                    class="group flex h-full flex-col overflow-hidden rounded-lg border border-gray-200 bg-white transition hover:shadow-sm dark:border-gray-800 dark:bg-gray-900"
                    aria-label="{{ $cardCta }}"
                >
                    <div class="aspect-[16/10] overflow-hidden bg-gray-100 dark:bg-gray-800">
                        @if($collectionImage)
                            <img
                                src="{{ $collectionImage }}"
                                alt="{{ $cardTitle }}"
                                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.02]"
                                loading="lazy"
                            >
                        @else
                            <div class="h-full w-full bg-gradient-to-br from-gray-100 via-sky-50 to-white dark:from-gray-800 dark:via-sky-950/20 dark:to-gray-900"></div>
                        @endif
                    </div>

                    <div class="flex flex-1 flex-col p-4">
                        @if($cardEyebrow)
                            <div class="text-[10px] uppercase tracking-[0.14em] text-gray-400 dark:text-gray-500">{{ $cardEyebrow }}</div>
                        @endif

                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $cardTitle }}</div>

                        @if($cardDescription)
                            <p class="mt-1 line-clamp-2 text-xs leading-5 text-gray-600 dark:text-gray-300">{{ $cardDescription }}</p>
                        @endif

                        <div class="mt-auto flex items-center justify-between gap-3 border-t border-gray-100 pt-3 text-[11px] dark:border-gray-800">
                            <span class="text-gray-500 dark:text-gray-400">
                                @if(! is_null($productCount))
                                    {{ $productCount }} {{ Str::plural('product', (int) $productCount) }}
                                @else
                                    Curated collection
                                @endif
                            </span>

                            <span class="inline-flex items-center gap-1 font-medium text-gray-700 dark:text-gray-200">
                                Explore
                                <svg class="h-3.5 w-3.5 transition-transform duration-200 group-hover:translate-x-0.5" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                    <path d="M4 10h12M11.5 5.5 16 10l-4.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</section>
