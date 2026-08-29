@php
    $bandaraKitchenFeaturedChef = $bandaraKitchenFeaturedChef ?? null;

    if (! $bandaraKitchenFeaturedChef) {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('chefs')) {
                $bandaraKitchenFeaturedChef = \App\Models\Chef::query()
                    ->homepageFeatured()
                    ->orderByDesc('updated_at')
                    ->first();
            }
        } catch (\Throwable) {
            $bandaraKitchenFeaturedChef = null;
        }
    }
@endphp

@if ($bandaraKitchenFeaturedChef)
    <section class="mx-auto w-full max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8" aria-labelledby="bandara-kitchen-featured-chef-title" data-bandara-kitchen-featured-chef>
        <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-500 dark:text-slate-500">From Bandara Kitchen</p>
                <h2 id="bandara-kitchen-featured-chef-title" class="mt-2 text-2xl font-light tracking-tight text-slate-950 sm:text-3xl dark:text-white">Meet the Chef</h2>
            </div>
            <a href="{{ route('kitchen.chefs.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-900 dark:text-slate-100">View all Chefs <span aria-hidden="true">→</span></a>
        </div>

        <div class="grid overflow-hidden rounded-xl border border-slate-200/80 bg-white lg:grid-cols-[minmax(0,0.92fr)_minmax(0,1.08fr)] dark:border-slate-800 dark:bg-slate-950">
            <div class="aspect-[4/5] overflow-hidden bg-slate-100 sm:aspect-[5/4] lg:aspect-auto lg:min-h-[34rem] dark:bg-slate-900">
                @if ($bandaraKitchenFeaturedChef->heroImageUrl())
                    <img src="{{ $bandaraKitchenFeaturedChef->heroImageUrl() }}" alt="{{ $bandaraKitchenFeaturedChef->display_name }}" class="h-full w-full object-cover transition duration-500 hover:scale-[1.02]" loading="lazy">
                @else
                    <div class="flex h-full items-center justify-center text-5xl font-light tracking-[0.18em] text-slate-400 dark:text-slate-600">{{ \App\Support\BandaraKitchen::initials($bandaraKitchenFeaturedChef->display_name) }}</div>
                @endif
            </div>

            <div class="flex flex-col justify-center p-6 sm:p-9 lg:p-12">
                <p class="text-xs font-medium uppercase tracking-[0.18em] text-slate-500 dark:text-slate-500">Featured Chef</p>
                <h3 class="mt-3 text-3xl font-light tracking-tight text-slate-950 sm:text-4xl dark:text-white">{{ $bandaraKitchenFeaturedChef->display_name }}</h3>
                <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400">
                    {{ $bandaraKitchenFeaturedChef->professional_title }}
                    @if ($bandaraKitchenFeaturedChef->publicOrganisationLine())<span aria-hidden="true"> · </span>{{ $bandaraKitchenFeaturedChef->publicOrganisationLine() }}@endif
                    @if ($bandaraKitchenFeaturedChef->city)<span aria-hidden="true"> · </span>{{ $bandaraKitchenFeaturedChef->city }}@endif
                </p>

                @if ($bandaraKitchenFeaturedChef->short_intro)
                    <p class="mt-6 max-w-2xl text-base font-light leading-7 text-slate-700 sm:text-lg dark:text-slate-300">{{ $bandaraKitchenFeaturedChef->short_intro }}</p>
                @endif

                @if ($bandaraKitchenFeaturedChef->signature_dish_name)
                    <div class="mt-7 border-t border-slate-200 pt-5 dark:border-slate-800">
                        <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500 dark:text-slate-500">Signature dish</p>
                        <p class="mt-2 text-base font-light text-slate-900 dark:text-slate-100">{{ $bandaraKitchenFeaturedChef->signature_dish_name }}</p>
                    </div>
                @endif

                <div class="mt-8">
                    <a href="{{ route('kitchen.chefs.show', $bandaraKitchenFeaturedChef) }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-slate-950 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200">Meet the Chef</a>
                </div>
            </div>
        </div>
    </section>
@endif
