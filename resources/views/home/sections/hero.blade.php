@php
    $fallbackImages = (array)($section->getSetting('fallback_images', []) ?? []);
    $heroImage = $resolveMediaUrl(array_values(array_filter(array_merge([$section->image_path, $section->mobile_image_path], $fallbackImages))));
    $overlayEyebrow = $section->getSetting('overlay_eyebrow', 'From freezer to table');
    $overlayTitle = $section->getSetting('overlay_title', 'Practical frozen products for everyday cooking, entertaining, and repeat ordering.');
@endphp

<section class="grid gap-4 lg:grid-cols-[1.05fr,0.95fr] items-stretch">
    <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-gradient-to-br from-white via-slate-50 to-sky-50 dark:from-gray-900 dark:via-gray-900 dark:to-slate-900 p-6 sm:p-8 flex flex-col justify-between min-h-[320px]">
        <div class="space-y-5">
            @if($section->eyebrow)
                <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white/90 dark:bg-gray-950/50 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.14em] text-gray-600 dark:text-gray-300">
                    {{ $section->eyebrow }}
                </span>
            @endif

            <div class="space-y-3">
                <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-gray-900 dark:text-gray-50 leading-tight">
                    {{ $section->title }}
                </h1>

                @if($section->subtitle)
                    <p class="max-w-xl text-sm sm:text-base text-gray-600 dark:text-gray-300 leading-relaxed">
                        {{ $section->subtitle }}
                    </p>
                @endif
            </div>

            <div class="flex flex-wrap gap-3 text-sm">
                @if($section->cta_text && $section->cta_url)
                    <a href="{{ url($section->cta_url) }}" class="inline-flex items-center justify-center rounded-sm bg-gray-900 px-4 py-2.5 font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">
                        {{ $section->cta_text }}
                    </a>
                @endif

                @if($section->secondary_cta_text && $section->secondary_cta_url)
                    <a href="{{ $section->secondary_cta_url }}" class="inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-4 py-2.5 font-medium text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-800">
                        {{ $section->secondary_cta_text }}
                    </a>
                @endif
            </div>
        </div>

        @if($section->activeItems->count())
            <div class="mt-6 grid gap-3 sm:grid-cols-3">
                @foreach($section->activeItems as $item)
                    <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-950/40 px-4 py-3">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">{{ $item->getSetting('label', $item->eyebrow ?: $item->title) }}</div>
                        <div class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-50">{{ $item->description ?: $item->title }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 max-h-[340px]">
        @if($heroImage)
            <img src="{{ $heroImage }}" alt="{{ $section->title ?: 'Bandara home hero' }}" class="w-full object-cover min-h-[340px] max-h-[340px]">
        @else
            <div class="absolute inset-0 bg-gradient-to-br from-sky-100 via-cyan-50 to-white dark:from-sky-950/30 dark:via-cyan-950/20 dark:to-gray-900"></div>
        @endif

        <div class="absolute inset-x-0 bottom-0 p-4">
            <div class="rounded-sm border border-white/70 bg-white/90 backdrop-blur px-4 py-3 shadow-sm dark:border-gray-700/60 dark:bg-gray-950/70">
                <div class="text-[10px] uppercase tracking-wide text-gray-400">{{ $overlayEyebrow }}</div>
                <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $overlayTitle }}</div>
            </div>
        </div>
    </div>
</section>
