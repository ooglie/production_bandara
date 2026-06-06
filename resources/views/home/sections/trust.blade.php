<section class="space-y-4">
    <div>
        @if($section->eyebrow)<p class="text-[11px] uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">{{ $section->eyebrow }}</p>@endif
        <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-50">{{ $section->title }}</h2>
        @if($section->subtitle)<p class="mt-1 max-w-2xl text-sm text-gray-600 dark:text-gray-300">{{ $section->subtitle }}</p>@endif
    </div>

    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        @foreach($section->activeItems as $item)
            <div class="rounded-lg border px-4 py-4 {{ $item->getSetting('accent', 'bg-gray-50 border-gray-100 dark:bg-gray-900 dark:border-gray-800') }}">
                @if($item->icon)<div class="text-2xl">{{ $item->icon }}</div>@endif
                <div class="mt-3 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $item->title }}</div>
                @if($item->description)<p class="mt-1 text-xs leading-relaxed text-gray-600 dark:text-gray-300">{{ $item->description }}</p>@endif
            </div>
        @endforeach
    </div>
</section>
