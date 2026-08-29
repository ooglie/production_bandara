@php
    /** @var \Illuminate\Database\Eloquent\Model $recipe */
    $title = \App\Support\BandaraKitchen::recipeTitle($recipe);
    $imageUrl = \App\Support\BandaraKitchen::recipeImageUrl($recipe);
    $recipeUrl = \App\Support\BandaraKitchen::recipeUrl($recipe);
    $prep = (int) ($recipe->getAttribute('prep_time_minutes') ?? 0);
    $cook = (int) ($recipe->getAttribute('cook_time_minutes') ?? 0);
    $total = $prep + $cook;
@endphp

<a href="{{ $recipeUrl }}"
   class="group flex h-full flex-col overflow-hidden rounded-xl border border-slate-200/80 bg-white transition duration-300 hover:-translate-y-1 hover:border-slate-300 hover:shadow-lg dark:border-slate-800 dark:bg-slate-950 dark:hover:border-slate-700">
    <div class="aspect-[4/3] overflow-hidden bg-slate-100 dark:bg-slate-900">
        @if ($imageUrl)
            <img src="{{ $imageUrl }}"
                 alt="{{ $title }}"
                 class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.025]"
                 loading="lazy">
        @endif
    </div>
    <div class="flex flex-1 flex-col p-4 sm:p-5">
        <h3 class="text-base font-light leading-6 text-slate-950 dark:text-white">{{ $title }}</h3>
        @if ($total > 0)
            <p class="mt-2 text-xs uppercase tracking-[0.13em] text-slate-500 dark:text-slate-500">
                {{ $total }} minutes
            </p>
        @endif
        <span class="mt-auto inline-flex pt-4 items-center gap-2 text-sm font-medium text-slate-900 dark:text-slate-100">
            View recipe
            <span aria-hidden="true" class="transition-transform duration-300 group-hover:translate-x-1">→</span>
        </span>
    </div>
</a>
