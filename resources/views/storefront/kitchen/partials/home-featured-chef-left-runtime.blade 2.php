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

    $bandaraKitchenChefPortrait = $bandaraKitchenFeaturedChef?->portraitUrl()
        ?: $bandaraKitchenFeaturedChef?->heroImageUrl();

    $bandaraKitchenChefInitials = $bandaraKitchenFeaturedChef
        ? \Illuminate\Support\Str::of($bandaraKitchenFeaturedChef->display_name)
            ->trim()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(static fn (string $part): string => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
            ->implode('')
        : '';

    $bandaraKitchenChefUrl = $bandaraKitchenFeaturedChef
        ? route('kitchen.chefs.show', $bandaraKitchenFeaturedChef)
        : null;
@endphp

@if ($bandaraKitchenFeaturedChef && $bandaraKitchenChefUrl)
    <template
        data-bandara-kitchen-featured-chef-template
        data-chef-url="{{ $bandaraKitchenChefUrl }}"
        data-chef-name="{{ $bandaraKitchenFeaturedChef->display_name }}"
    >
        <div class="relative z-10 flex h-full min-h-[12rem] items-center gap-4 bg-white p-4 sm:gap-5 sm:p-5 dark:bg-slate-950">
            <div
                class="flex-none overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-900"
                style="width: 30%; flex-basis: 30%; min-width: 5.25rem; max-width: 8.5rem;"
                aria-hidden="true"
            >
                <div class="aspect-[4/5] overflow-hidden">
                    @if ($bandaraKitchenChefPortrait)
                        <img
                            src="{{ $bandaraKitchenChefPortrait }}"
                            alt=""
                            class="h-full w-full object-cover object-top transition duration-500 group-hover:scale-[1.035]"
                            loading="lazy"
                        >
                    @else
                        <div class="flex h-full min-h-[10.5rem] items-center justify-center text-xl font-light tracking-[0.12em] text-slate-400 dark:text-slate-600">
                            {{ $bandaraKitchenChefInitials }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="min-w-0 flex-1 py-0.5">
                <p class="text-[0.68rem] font-normal uppercase tracking-[0.18em] text-slate-500 dark:text-slate-500">
                    Featured Chef
                </p>

                <h3 class="mt-2 text-xl font-light tracking-tight text-slate-950 sm:text-2xl dark:text-white">
                    {{ $bandaraKitchenFeaturedChef->display_name }}
                </h3>

                @if ($bandaraKitchenFeaturedChef->professional_title || $bandaraKitchenFeaturedChef->publicOrganisationLine() || $bandaraKitchenFeaturedChef->city)
                    <p class="mt-1.5 text-xs leading-5 text-slate-500 dark:text-slate-400">
                        @if ($bandaraKitchenFeaturedChef->professional_title)
                            {{ $bandaraKitchenFeaturedChef->professional_title }}
                        @endif
                        @if ($bandaraKitchenFeaturedChef->publicOrganisationLine())
                            @if ($bandaraKitchenFeaturedChef->professional_title)<span aria-hidden="true"> · </span>@endif
                            {{ $bandaraKitchenFeaturedChef->publicOrganisationLine() }}
                        @endif
                        @if ($bandaraKitchenFeaturedChef->city)
                            @if ($bandaraKitchenFeaturedChef->professional_title || $bandaraKitchenFeaturedChef->publicOrganisationLine())<span aria-hidden="true"> · </span>@endif
                            {{ $bandaraKitchenFeaturedChef->city }}
                        @endif
                    </p>
                @endif

                @if ($bandaraKitchenFeaturedChef->short_intro)
                    <p class="mt-3 max-h-[4.5rem] overflow-hidden text-sm font-light leading-6 text-slate-700 dark:text-slate-300">
                        {{ \Illuminate\Support\Str::limit($bandaraKitchenFeaturedChef->short_intro, 165) }}
                    </p>
                @endif

                @if ($bandaraKitchenFeaturedChef->signature_dish_name)
                    <p class="mt-3 text-xs leading-5 text-slate-500 dark:text-slate-400">
                        <span class="uppercase tracking-[0.12em]">Signature dish</span>
                        <span aria-hidden="true"> · </span>
                        <span class="text-slate-700 dark:text-slate-300">{{ $bandaraKitchenFeaturedChef->signature_dish_name }}</span>
                    </p>
                @endif

                <span class="mt-4 inline-flex items-center gap-2 text-sm font-normal text-slate-800 transition group-hover:text-slate-950 dark:text-slate-200 dark:group-hover:text-white">
                    Meet the Chef <span aria-hidden="true">→</span>
                </span>
            </div>
        </div>
    </template>

    <script
        src="{{ asset('bandara-kitchen/home-chef-left-v1.2.3.js') }}"
        data-bandara-kitchen-home-chef-runtime
        defer
    ></script>
@endif
