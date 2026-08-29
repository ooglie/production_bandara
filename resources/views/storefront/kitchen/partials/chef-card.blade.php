@php
    /** @var \App\Models\Chef $chef */
    $portraitUrl = $chef->portraitUrl();
    $organisation = $chef->publicOrganisationLine();
@endphp

<a href="{{ route('kitchen.chefs.show', $chef) }}" class="group flex h-full flex-col overflow-hidden rounded-xl border border-slate-200/80 bg-white transition duration-300 hover:-translate-y-1 hover:border-slate-300 hover:shadow-lg dark:border-slate-800 dark:bg-slate-950 dark:hover:border-slate-700">
    <div class="aspect-[4/5] overflow-hidden bg-slate-100 dark:bg-slate-900">
        @if ($portraitUrl)
            <img src="{{ $portraitUrl }}" alt="Portrait of {{ $chef->display_name }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.025]" loading="lazy">
        @else
            <div class="flex h-full items-center justify-center text-3xl font-light tracking-[0.18em] text-slate-400 dark:text-slate-600">{{ \App\Support\BandaraKitchen::initials($chef->display_name) }}</div>
        @endif
    </div>

    <div class="flex flex-1 flex-col p-4 sm:p-5">
        <h3 class="text-lg font-light tracking-tight text-slate-950 dark:text-white">{{ $chef->display_name }}</h3>
        <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-400">
            {{ $chef->professional_title }}
            @if ($organisation)<span aria-hidden="true"> · </span>{{ $organisation }}@endif
        </p>
        @if ($chef->short_intro)
            <p class="mt-4 line-clamp-3 text-sm font-light leading-6 text-slate-600 dark:text-slate-400">{{ $chef->short_intro }}</p>
        @endif
        <span class="mt-auto inline-flex items-center gap-2 pt-5 text-sm font-medium text-slate-900 dark:text-slate-100">Meet the Chef <span aria-hidden="true" class="transition-transform duration-300 group-hover:translate-x-1">→</span></span>
    </div>
</a>
