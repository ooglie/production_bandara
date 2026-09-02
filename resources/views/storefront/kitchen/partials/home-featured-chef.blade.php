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
            // Keep the storefront available when the Chef module is unavailable
            // or no published Chef has been selected manually.
            $bandaraKitchenFeaturedChef = null;
        }
    }

    $bandaraKitchenChefPortrait = $bandaraKitchenFeaturedChef?->portraitUrl()
        ?: $bandaraKitchenFeaturedChef?->heroImageUrl();
    $bandaraKitchenSignatureDishImage = $bandaraKitchenFeaturedChef?->signatureDishImageUrl();
@endphp

@if ($bandaraKitchenFeaturedChef)
    <section
        class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 sm:py-12 lg:px-8"
        aria-labelledby="bandara-kitchen-home-chef-title"
        data-bandara-kitchen-home-chef
    >
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-normal uppercase tracking-[0.2em] text-slate-500 dark:text-slate-500">
                    Bandara Kitchen
                </p>
                <h2
                    id="bandara-kitchen-home-chef-title"
                    class="mt-2 text-2xl font-light tracking-tight text-slate-950 sm:text-3xl dark:text-white"
                >
                    Meet our featured Chef
                </h2>
            </div>

            <a
                href="{{ route('kitchen.index') }}"
                class="inline-flex items-center gap-2 text-sm font-normal text-slate-700 transition hover:text-slate-950 dark:text-slate-300 dark:hover:text-white"
            >
                Visit Bandara Kitchen <span aria-hidden="true">→</span>
            </a>
        </div>

        <div class="group overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg dark:border-slate-800 dark:bg-slate-950">
            <div class="grid lg:grid-cols-12">
                <div class="relative aspect-[4/3] overflow-hidden bg-slate-100 sm:aspect-[16/10] lg:col-span-5 lg:aspect-auto lg:min-h-[28rem] dark:bg-slate-900">
                    @if ($bandaraKitchenChefPortrait)
                        <img
                            src="{{ $bandaraKitchenChefPortrait }}"
                            alt="{{ $bandaraKitchenFeaturedChef->display_name }}"
                            class="h-full w-full object-cover object-top transition duration-500 group-hover:scale-[1.025]"
                            loading="lazy"
                        >
                    @else
                        <div class="flex h-full min-h-72 items-center justify-center text-5xl font-light tracking-[0.18em] text-slate-400 dark:text-slate-600">
                            {{ \App\Support\BandaraKitchen::initials($bandaraKitchenFeaturedChef->display_name) }}
                        </div>
                    @endif
                </div>

                <div
                    @class([
                        'flex flex-col justify-center p-6 sm:p-8 lg:p-10',
                        'lg:col-span-4' => $bandaraKitchenSignatureDishImage,
                        'lg:col-span-7' => ! $bandaraKitchenSignatureDishImage,
                    ])
                >
                    <p class="text-xs font-normal uppercase tracking-[0.18em] text-slate-500 dark:text-slate-500">
                        Featured Chef
                    </p>

                    <h3 class="mt-3 text-3xl font-light tracking-tight text-slate-950 sm:text-4xl dark:text-white">
                        {{ $bandaraKitchenFeaturedChef->display_name }}
                    </h3>

                    @if ($bandaraKitchenFeaturedChef->professional_title || $bandaraKitchenFeaturedChef->publicOrganisationLine() || $bandaraKitchenFeaturedChef->city)
                        <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400">
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
                        <p class="mt-6 max-w-2xl text-base font-light leading-7 text-slate-700 sm:text-lg dark:text-slate-300">
                            {{ $bandaraKitchenFeaturedChef->short_intro }}
                        </p>
                    @endif

                    @if (! $bandaraKitchenSignatureDishImage && $bandaraKitchenFeaturedChef->signature_dish_name)
                        <div class="mt-7 border-t border-slate-200 pt-5 dark:border-slate-800">
                            <p class="text-xs font-normal uppercase tracking-[0.16em] text-slate-500 dark:text-slate-500">
                                Signature dish
                            </p>
                            <p class="mt-2 text-base font-light leading-6 text-slate-900 dark:text-slate-100">
                                {{ $bandaraKitchenFeaturedChef->signature_dish_name }}
                            </p>
                        </div>
                    @endif

                    <div class="mt-8">
                        <a
                            href="{{ route('kitchen.chefs.show', $bandaraKitchenFeaturedChef) }}"
                            class="inline-flex min-h-11 items-center justify-center rounded-lg bg-slate-950 px-5 py-2.5 text-sm font-normal text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
                        >
                            Meet the Chef
                        </a>
                    </div>
                </div>

                @if ($bandaraKitchenSignatureDishImage)
                    <div class="flex flex-col border-t border-slate-200 lg:col-span-3 lg:border-l lg:border-t-0 dark:border-slate-800">
                        <div class="aspect-[16/10] overflow-hidden bg-slate-100 lg:aspect-auto lg:min-h-[20.5rem] lg:flex-1 dark:bg-slate-900">
                            <img
                                src="{{ $bandaraKitchenSignatureDishImage }}"
                                alt="{{ $bandaraKitchenFeaturedChef->signature_dish_name ?: 'Signature dish by '.$bandaraKitchenFeaturedChef->display_name }}"
                                class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.025]"
                                loading="lazy"
                            >
                        </div>
                        <div class="border-t border-slate-200 p-5 dark:border-slate-800">
                            <p class="text-xs font-normal uppercase tracking-[0.16em] text-slate-500 dark:text-slate-500">
                                Signature dish
                            </p>
                            @if ($bandaraKitchenFeaturedChef->signature_dish_name)
                                <p class="mt-2 text-sm font-light leading-6 text-slate-900 dark:text-slate-100">
                                    {{ $bandaraKitchenFeaturedChef->signature_dish_name }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endif
