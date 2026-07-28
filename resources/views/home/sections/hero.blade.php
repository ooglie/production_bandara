@php
    /*
     | The homepage is DB-driven. Keep the existing section record authoritative,
     | while accepting the variable names used by older Bandara homepage builds.
     */
    $heroSource = $section ?? $homeSection ?? $hero ?? [];

    if (is_array($heroSource) && array_key_exists('section', $heroSource)) {
        $heroSource = $heroSource['section'];
    }

    $rawSettings = data_get($heroSource, 'settings', []);
    $heroSettings = is_array($rawSettings)
        ? $rawSettings
        : (json_decode((string) $rawSettings, true) ?: []);

    $heroEyebrow = data_get($heroSource, 'eyebrow')
        ?: data_get($heroSettings, 'overlay_eyebrow')
        ?: 'Curated by Bandara';

    $heroTitle = data_get($heroSource, 'title')
        ?: data_get($heroSettings, 'overlay_title')
        ?: 'Food worth keeping, ready when you are.';

    $heroCopy = data_get($heroSource, 'subtitle')
        ?: data_get($heroSource, 'body')
        ?: 'Premium frozen and chilled foods selected with care for home kitchens, retailers, hotels and professional tables.';

    $shopUrl = \Illuminate\Support\Facades\Route::has('shop.index')
        ? route('shop.index')
        : url('/shop');

    $primaryLabel = data_get($heroSource, 'cta_text') ?: 'Shop all products';
    $primaryUrl = data_get($heroSource, 'cta_url') ?: $shopUrl;
    $secondaryLabel = data_get($heroSource, 'secondary_cta_text') ?: 'Explore categories';
    $secondaryUrl = data_get($heroSource, 'secondary_cta_url') ?: url('/#home-categories');

    $resolveHeroImage = static function (?string $path): ?string {
        if (blank($path)) {
            return null;
        }

        $path = trim($path);

        if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://', '//', 'data:'])) {
            return $path;
        }

        $cleanPath = ltrim($path, '/');

        if (is_file(public_path($cleanPath))) {
            return asset($cleanPath);
        }

        if (\Illuminate\Support\Str::startsWith($cleanPath, 'storage/')) {
            return asset($cleanPath);
        }

        try {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($cleanPath)) {
                return \Illuminate\Support\Facades\Storage::disk('public')->url($cleanPath);
            }
        } catch (\Throwable) {
            // A missing public disk should never prevent the homepage from rendering.
        }

        return null;
    };

    $fallbackImages = data_get($heroSettings, 'fallback_images', []);
    $fallbackImages = is_array($fallbackImages) ? $fallbackImages : [];

    $desktopCandidates = array_values(array_filter([
        data_get($heroSource, 'image_url'),
        data_get($heroSource, 'image_path'),
        ...$fallbackImages,
    ]));

    $mobileCandidates = array_values(array_filter([
        data_get($heroSource, 'mobile_image_url'),
        data_get($heroSource, 'mobile_image_path'),
        data_get($heroSource, 'image_url'),
        data_get($heroSource, 'image_path'),
        ...$fallbackImages,
    ]));

    $heroImage = null;
    foreach ($desktopCandidates as $candidate) {
        $resolved = $resolveHeroImage((string) $candidate);
        if ($resolved) {
            $heroImage = $resolved;
            break;
        }
    }

    $heroMobileImage = null;
    foreach ($mobileCandidates as $candidate) {
        $resolved = $resolveHeroImage((string) $candidate);
        if ($resolved) {
            $heroMobileImage = $resolved;
            break;
        }
    }

    $heroImageAlt = data_get($heroSettings, 'image_alt')
        ?: data_get($heroSource, 'image_alt')
        ?: 'A carefully selected product from the Bandara range';

    $heroImageLabel = data_get($heroSettings, 'image_label') ?: 'Selected with care';
@endphp

<section
    class="bandara-phase1-hero relative overflow-hidden border-b border-stone-200 bg-stone-50 text-slate-800 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100"
    aria-labelledby="bandara-home-hero-title"
>
    <div aria-hidden="true" class="pointer-events-none absolute inset-y-0 left-0 hidden w-1/2 bg-white/40 dark:bg-slate-900/20 lg:block"></div>

    <div class="relative mx-auto grid max-w-7xl items-center gap-9 px-4 py-10 sm:px-6 sm:py-12 lg:grid-cols-[0.92fr_1.08fr] lg:gap-14 lg:px-8 lg:py-14 xl:gap-16">
        <div class="bandara-phase1-hero-copy max-w-2xl lg:py-6">
            <p class="inline-flex items-center gap-2 text-[0.68rem] font-normal uppercase tracking-[0.23em] text-amber-700 dark:text-amber-400">
                <span aria-hidden="true" class="h-1.5 w-1.5 rounded-full bg-current"></span>
                {{ $heroEyebrow }}
            </p>

            <h1 id="bandara-home-hero-title" class="mt-5 text-3xl font-light leading-[1.08] tracking-[-0.035em] text-slate-950 dark:text-white sm:text-4xl lg:text-[3.2rem] xl:text-[3.55rem]">
                {{ $heroTitle }}
            </h1>

            <p class="mt-5 max-w-xl text-sm font-light leading-7 text-slate-600 dark:text-slate-300 sm:text-base sm:leading-8">
                {{ $heroCopy }}
            </p>

            <div class="mt-7 flex flex-wrap items-center gap-3">
                <a data-bandara-phase1-cta-colours-restored
                    href="{{ $primaryUrl }}"
                    class="bandara-phase1-hero-button bandara-phase1-hero-button-primary inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-sm font-normal shadow-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 bandara-phase1-cta-colours-restored bg-gray-900 text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white"
                >
                    {{ $primaryLabel }}
                    <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 20 20" fill="none">
                        <path d="M4 10h12M12 6l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>

                @if (filled($secondaryLabel) && filled($secondaryUrl))
                    <a
                        href="{{ $secondaryUrl }}"
                        class="bandara-phase1-hero-button inline-flex min-h-10 items-center justify-center rounded-lg border border-stone-300 bg-white/70 px-5 py-2.5 text-sm font-normal text-slate-700 transition hover:border-slate-400 hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-600 focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:bg-slate-900 dark:focus-visible:ring-amber-400 dark:focus-visible:ring-offset-slate-950"
                    >
                        {{ $secondaryLabel }}
                    </a>
                @endif
            </div>
        </div>

        <div class="bandara-phase1-hero-visual relative">
            <div class="relative overflow-hidden rounded-xl border border-stone-200 bg-stone-100 shadow-[0_18px_45px_-30px_rgba(15,23,42,0.45)] dark:border-slate-800 dark:bg-slate-900 dark:shadow-[0_18px_45px_-30px_rgba(0,0,0,0.8)]">
                @if ($heroImage)
                    <picture>
                        @if ($heroMobileImage && $heroMobileImage !== $heroImage)
                            <source media="(max-width: 767px)" srcset="{{ $heroMobileImage }}">
                        @endif
                        <img
                            src="{{ $heroImage }}"
                            alt="{{ $heroImageAlt }}"
                            class="bandara-phase1-hero-image aspect-[16/10] w-full object-cover sm:aspect-[16/9] lg:aspect-[4/3]"
                            loading="eager"
                            fetchpriority="high"
                            decoding="async"
                        >
                    </picture>
                @else
                    <div class="flex aspect-[16/10] items-center justify-center bg-gradient-to-br from-stone-100 via-white to-stone-200 px-8 text-center dark:from-slate-900 dark:via-slate-950 dark:to-slate-900 sm:aspect-[16/9] lg:aspect-[4/3]">
                        <p class="max-w-xs text-sm font-light leading-7 text-slate-500 dark:text-slate-400">Bandara · Ancient name. Uncompromising standard.</p>
                    </div>
                @endif

                <div class="absolute bottom-3 left-3 rounded-full border border-white/30 bg-slate-950/70 px-3 py-1.5 text-[0.65rem] font-normal uppercase tracking-[0.16em] text-white backdrop-blur-sm sm:bottom-4 sm:left-4">
                    {{ $heroImageLabel }}
                </div>
            </div>
        </div>
    </div>
</section>
