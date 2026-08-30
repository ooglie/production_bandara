@extends('layouts.customer')

@section('title', $chef->display_name.' | Bandara Kitchen')

@section('content')
    @php
        $organisation = $chef->publicOrganisationLine();
        $location = collect([$chef->city, $chef->country])
            ->filter(fn ($value) => filled($value))
            ->implode(' · ');

        $professionalMeta = collect([
            $chef->professional_title,
            $organisation,
            $location,
        ])->filter(fn ($value) => filled($value))->implode(' · ');

        $portraitUrl = $chef->portraitUrl();
        $workingImageUrl = $chef->workingImageUrl();
        $signatureDishImageUrl = $chef->signatureDishImageUrl();
        $gallery = collect($chef->galleryImageUrls())->values();
        $galleryCount = $gallery->count();

        $professionalLinks = collect([
            'Website' => $chef->website_url,
            'Instagram' => $chef->instagram_url,
            'LinkedIn' => $chef->linkedin_url,
        ])->filter(fn ($url) => filled($url));

        $hasSignatureDish = filled($chef->signature_dish_name)
            || filled($chef->signature_dishes)
            || filled($signatureDishImageUrl);
    @endphp

    <div class="pb-16 sm:pb-20">
        {{-- Breadcrumb and editorial introduction --}}
        <section class="mx-auto w-full max-w-6xl px-4 pt-8 sm:px-6 sm:pt-12 lg:px-8">
            <nav aria-label="Breadcrumb" class="text-xs uppercase tracking-[0.14em] text-slate-500 dark:text-slate-500">
                <a href="{{ url('/') }}" class="transition hover:text-slate-900 dark:hover:text-slate-200">Home</a>
                <span aria-hidden="true" class="px-2">/</span>
                <a href="{{ route('kitchen.index') }}" class="transition hover:text-slate-900 dark:hover:text-slate-200">Bandara Kitchen</a>
                <span aria-hidden="true" class="px-2">/</span>
                <a href="{{ route('kitchen.chefs.index') }}" class="transition hover:text-slate-900 dark:hover:text-slate-200">Chefs</a>
                <span aria-hidden="true" class="px-2">/</span>
                <span aria-current="page">{{ $chef->display_name }}</span>
            </nav>

            <article class="mt-7 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="grid lg:grid-cols-5">
                    <figure class="bg-slate-100 dark:bg-slate-950 lg:col-span-2">
                        <div class="aspect-[4/5] overflow-hidden">
                            @if ($portraitUrl)
                                <img
                                    src="{{ $portraitUrl }}"
                                    alt="Portrait of {{ $chef->display_name }}"
                                    class="h-full w-full object-cover object-center"
                                    fetchpriority="high"
                                >
                            @else
                                <div class="flex h-full items-center justify-center text-5xl font-light tracking-[0.18em] text-slate-400 dark:text-slate-600">
                                    {{ \App\Support\BandaraKitchen::initials($chef->display_name) }}
                                </div>
                            @endif
                        </div>
                    </figure>

                    <div class="flex flex-col justify-center px-6 py-8 sm:px-10 sm:py-12 lg:col-span-3 lg:px-14 lg:py-14">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-500 dark:text-slate-500">Meet the Chef</p>

                        <h1 class="mt-4 text-4xl font-light leading-tight tracking-tight text-slate-950 sm:text-5xl dark:text-white">
                            {{ $chef->display_name }}
                        </h1>

                        @if ($professionalMeta)
                            <p class="mt-4 text-sm leading-7 text-slate-600 dark:text-slate-400">
                                {{ $professionalMeta }}
                            </p>
                        @endif

                        @if ($chef->short_intro)
                            <p class="mt-7 max-w-2xl text-base font-light leading-8 text-slate-700 sm:text-lg sm:leading-9 dark:text-slate-300">
                                {{ $chef->short_intro }}
                            </p>
                        @endif

                        @if ($professionalLinks->isNotEmpty())
                            <div class="mt-8 flex flex-wrap gap-x-6 gap-y-3 border-t border-slate-200 pt-6 dark:border-slate-800" aria-label="Professional links">
                                @foreach ($professionalLinks as $label => $url)
                                    <a
                                        href="{{ $url }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-2 text-sm text-slate-700 transition hover:text-slate-950 dark:text-slate-300 dark:hover:text-white"
                                    >
                                        {{ $label }} <span aria-hidden="true">↗</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </article>
        </section>

        {{-- Signature dish --}}
        @if ($hasSignatureDish)
            <section class="mx-auto w-full max-w-6xl px-4 pt-10 sm:px-6 sm:pt-12 lg:px-8" aria-labelledby="signature-dish-title">
                <article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="grid lg:grid-cols-5">
                        @if ($signatureDishImageUrl)
                            <figure class="bg-slate-100 dark:bg-slate-950 lg:col-span-3">
                                <div class="aspect-[4/3] h-full overflow-hidden">
                                    <img
                                        src="{{ $signatureDishImageUrl }}"
                                        alt="{{ $chef->signature_dish_name ?: 'Signature dish by '.$chef->display_name }}"
                                        class="h-full w-full object-cover"
                                        loading="lazy"
                                    >
                                </div>
                            </figure>
                        @endif

                        <div @class([
                            'flex flex-col justify-center px-6 py-8 sm:px-10 sm:py-12 lg:px-12',
                            'lg:col-span-2' => $signatureDishImageUrl,
                            'lg:col-span-5' => ! $signatureDishImageUrl,
                        ])>
                            <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-500 dark:text-slate-500">Signature Dish</p>

                            @if ($chef->signature_dish_name)
                                <h2 id="signature-dish-title" class="mt-3 text-3xl font-light leading-tight tracking-tight text-slate-950 sm:text-4xl dark:text-white">
                                    {{ $chef->signature_dish_name }}
                                </h2>
                            @else
                                <h2 id="signature-dish-title" class="sr-only">Signature dish</h2>
                            @endif

                            @if ($chef->signature_dishes)
                                <div class="mt-6 whitespace-pre-line text-base font-light leading-8 text-slate-700 dark:text-slate-300">{{ $chef->signature_dishes }}</div>
                            @endif

                            <p class="mt-7 border-t border-slate-200 pt-5 text-xs uppercase tracking-[0.14em] text-slate-500 dark:border-slate-800 dark:text-slate-500">
                                Selected by {{ $chef->display_name }}
                            </p>
                        </div>
                    </div>
                </article>
            </section>
        @endif

        {{-- Chef story --}}
        @if ($chef->biography || $workingImageUrl)
            <section class="mx-auto w-full max-w-6xl px-4 pt-12 sm:px-6 sm:pt-16 lg:px-8" aria-labelledby="chef-story-title">
                <div class="border-t border-slate-200 pt-10 sm:pt-12 dark:border-slate-800">
                    <div class="grid gap-10 lg:grid-cols-5 lg:items-start lg:gap-14">
                        <div @class([
                            'lg:col-span-3' => $workingImageUrl,
                            'lg:col-span-5 max-w-4xl' => ! $workingImageUrl,
                        ])>
                            <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-500 dark:text-slate-500">Bandara Kitchen</p>
                            <h2 id="chef-story-title" class="mt-3 text-3xl font-light tracking-tight text-slate-950 sm:text-4xl dark:text-white">The Chef’s Story</h2>

                            @if ($chef->biography)
                                <div class="mt-7 whitespace-pre-line text-base font-light leading-8 text-slate-700 sm:text-lg sm:leading-9 dark:text-slate-300">{{ $chef->biography }}</div>
                            @endif
                        </div>

                        @if ($workingImageUrl)
                            <figure class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:col-span-2">
                                <div class="aspect-[4/3] overflow-hidden">
                                    <img
                                        src="{{ $workingImageUrl }}"
                                        alt="{{ $chef->display_name }} at work"
                                        class="h-full w-full object-cover"
                                        loading="lazy"
                                    >
                                </div>
                                @if ($chef->photographer_credit)
                                    <figcaption class="px-4 py-3 text-xs text-slate-500 dark:text-slate-500">
                                        Photography: {{ $chef->photographer_credit }}
                                    </figcaption>
                                @endif
                            </figure>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        {{-- Editorial gallery --}}
        @if ($gallery->isNotEmpty())
            <section class="mx-auto w-full max-w-6xl px-4 pt-12 sm:px-6 sm:pt-16 lg:px-8" aria-labelledby="chef-gallery-title">
                <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-500 dark:text-slate-500">In the Kitchen</p>
                        <h2 id="chef-gallery-title" class="mt-2 text-3xl font-light tracking-tight text-slate-950 dark:text-white">A closer look</h2>
                    </div>

                    @if ($chef->photographer_credit && ! $workingImageUrl)
                        <p class="text-xs text-slate-500 dark:text-slate-500">Photography: {{ $chef->photographer_credit }}</p>
                    @endif
                </div>

                @if ($galleryCount === 1)
                    <figure class="overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-900">
                        <div class="aspect-video overflow-hidden">
                            <img
                                src="{{ $gallery->first() }}"
                                alt="{{ $chef->display_name }} kitchen photograph 1"
                                class="h-full w-full object-cover transition duration-500 hover:scale-[1.02]"
                                loading="lazy"
                            >
                        </div>
                    </figure>
                @elseif ($galleryCount === 2)
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($gallery as $imageUrl)
                            <figure class="overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-900">
                                <div class="aspect-[4/3] overflow-hidden">
                                    <img
                                        src="{{ $imageUrl }}"
                                        alt="{{ $chef->display_name }} kitchen photograph {{ $loop->iteration }}"
                                        class="h-full w-full object-cover transition duration-500 hover:scale-[1.02]"
                                        loading="lazy"
                                    >
                                </div>
                            </figure>
                        @endforeach
                    </div>
                @else
                    <div class="grid gap-4 lg:grid-cols-5 lg:items-stretch">
                        <figure class="overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-900 lg:col-span-3">
                            <div class="aspect-[4/3] h-full overflow-hidden lg:aspect-auto">
                                <img
                                    src="{{ $gallery->first() }}"
                                    alt="{{ $chef->display_name }} kitchen photograph 1"
                                    class="h-full w-full object-cover transition duration-500 hover:scale-[1.02]"
                                    loading="lazy"
                                >
                            </div>
                        </figure>

                        <div class="grid gap-4 sm:grid-cols-2 lg:col-span-2">
                            @foreach ($gallery->skip(1) as $imageUrl)
                                <figure @class([
                                    'overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-900',
                                    'sm:col-span-2' => $galleryCount === 3 || $loop->first,
                                ])>
                                    <div @class([
                                        'overflow-hidden',
                                        'aspect-video' => $galleryCount === 3 || $loop->first,
                                        'aspect-square' => $galleryCount === 4 && ! $loop->first,
                                    ])>
                                        <img
                                            src="{{ $imageUrl }}"
                                            alt="{{ $chef->display_name }} kitchen photograph {{ $loop->iteration + 1 }}"
                                            class="h-full w-full object-cover transition duration-500 hover:scale-[1.02]"
                                            loading="lazy"
                                        >
                                    </div>
                                </figure>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>
        @endif

        {{-- Other published Chefs --}}
        @if ($relatedChefs->isNotEmpty())
            <section class="mx-auto w-full max-w-6xl px-4 pt-12 sm:px-6 sm:pt-16 lg:px-8" aria-labelledby="other-chefs-title">
                <div class="border-t border-slate-200 pt-10 sm:pt-12 dark:border-slate-800">
                    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-500 dark:text-slate-500">Bandara Kitchen</p>
                            <h2 id="other-chefs-title" class="mt-2 text-3xl font-light tracking-tight text-slate-950 dark:text-white">Meet more Chefs</h2>
                        </div>

                        <a href="{{ route('kitchen.chefs.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-900 dark:text-slate-100">
                            All Chefs <span aria-hidden="true">→</span>
                        </a>
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($relatedChefs as $relatedChef)
                            @include('storefront.kitchen.partials.chef-card', ['chef' => $relatedChef])
                        @endforeach
                    </div>
                </div>
            </section>
        @else
            <div class="mx-auto w-full max-w-6xl px-4 pt-12 sm:px-6 lg:px-8">
                <div class="border-t border-slate-200 pt-8 dark:border-slate-800">
                    <a href="{{ route('kitchen.chefs.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-900 dark:text-slate-100">
                        <span aria-hidden="true">←</span> All Chefs
                    </a>
                </div>
            </div>
        @endif

        {{-- Discreet editorial and food-safety note --}}
        <aside class="mx-auto w-full max-w-6xl px-4 pt-10 sm:px-6 lg:px-8" aria-label="Editorial note">
            <div class="border-t border-slate-200 pt-6 text-xs leading-6 text-slate-500 dark:border-slate-800 dark:text-slate-500">
                <span class="font-medium text-slate-700 dark:text-slate-300">Editorial note:</span>
                Bandara Kitchen Chef profiles are published with the featured Chef’s approval and are intended for editorial and culinary inspiration. Ingredient availability, preparation methods and results may vary. Please check ingredients for allergens and follow appropriate food-handling and cooking practices.
            </div>
        </aside>
    </div>
@endsection
