<section class="rounded-lg border border-gray-200 bg-white px-5 py-5 dark:border-gray-800 dark:bg-gray-900">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $section->title ?: 'Need help before ordering?' }}</div>
            @if($section->subtitle)<p class="mt-1 text-[12px] text-gray-600 dark:text-gray-300">{{ $section->subtitle }}</p>@endif
        </div>
        <div class="flex flex-wrap gap-2">
            @if($section->cta_text && ($section->cta_url || $supportUrl))
                <a href="{{ $section->cta_url ? url($section->cta_url) : $supportUrl }}" class="inline-flex items-center justify-center rounded-sm border border-gray-900 bg-gray-900 px-4 py-2 text-[11px] font-medium text-white hover:bg-gray-800 dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">{{ $section->cta_text }}</a>
            @endif
            @if($section->secondary_cta_text && $section->secondary_cta_url)
                <a href="{{ $section->secondary_cta_url }}" class="inline-flex items-center justify-center rounded-sm border border-gray-300 px-4 py-2 text-[11px] font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">{{ $section->secondary_cta_text }}</a>
            @endif
        </div>
    </div>
</section>
