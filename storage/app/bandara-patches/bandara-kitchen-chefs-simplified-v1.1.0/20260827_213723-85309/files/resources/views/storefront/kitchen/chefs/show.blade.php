@extends('layouts.customer')

@section('title', $chef->display_name.' | Bandara Kitchen')

@section('content')
    @php
        $organisation = $chef->publicOrganisationLine();
        $gallery = $chef->galleryImageUrls();
        $recipes = $chef->recipes;
        $professionalLinks = collect([
            'Website' => $chef->website_url,
            'Instagram' => $chef->instagram_url,
            'LinkedIn' => $chef->linkedin_url,
        ])->filter();
    @endphp

    <main>
        <section class="mx-auto w-full max-w-7xl px-4 pb-10 pt-10 sm:px-6 sm:pb-14 sm:pt-14 lg:px-8">
            <nav aria-label="Breadcrumb" class="text-xs uppercase tracking-[0.14em] text-slate-500 dark:text-slate-500">
                <a href="{{ url('/') }}" class="transition hover:text-slate-900 dark:hover:text-slate-200">Home</a>
                <span aria-hidden="true" class="px-2">/</span>
                <a href="{{ route('kitchen.index') }}" class="transition hover:text-slate-900 dark:hover:text-slate-200">Bandara Kitchen</a>
                <span aria-hidden="true" class="px-2">/</span>
                <a href="{{ route('kitchen.chefs.index') }}" class="transition hover:text-slate-900 dark:hover:text-slate-200">Chefs</a>
                <span aria-hidden="true" class="px-2">/</span>
                <span aria-current="page">{{ $chef->display_name }}</span>
            </nav>

            <div class="mt-8 grid overflow-hidden rounded-xl border border-slate-200/80 bg-white lg:grid-cols-[minmax(0,0.92fr)_minmax(0,1.08fr)] dark:border-slate-800 dark:bg-slate-950">
                <div class="aspect-[4/5] overflow-hidden bg-slate-100 sm:aspect-[5/4] lg:aspect-auto lg:min-h-[38rem] dark:bg-slate-900">
                    @if ($chef->heroImageUrl())
                        <img src="{{ $chef->heroImageUrl() }}"
                             alt="Portrait of {{ $chef->display_name }}"
                             class="h-full w-full object-cover"
                             fetchpriority="high">
                    @else
                        <div class="flex h-full items-center justify-center text-5xl font-light tracking-[0.18em] text-slate-400 dark:text-slate-600">
                            {{ \App\Support\BandaraKitchen::initials($chef->display_name) }}
                        </div>
                    @endif
                </div>

                <div class="flex flex-col justify-center p-6 sm:p-10 lg:p-14">
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-500 dark:text-slate-500">Chef Profile</p>
                    <h1 class="mt-3 text-4xl font-light tracking-tight text-slate-950 sm:text-5xl dark:text-white">{{ $chef->display_name }}</h1>
                    <p class="mt-4 text-sm leading-7 text-slate-600 dark:text-slate-400">
                        {{ $chef->professional_title }}
                        @if ($organisation)<span aria-hidden="true"> · </span>{{ $organisation }}@endif
                        <span aria-hidden="true"> · </span>{{ collect([$chef->city, $chef->country])->filter()->implode(' · ') }}
                    </p>

                    @if ($chef->short_intro)
                        <p class="mt-7 max-w-2xl text-base font-light leading-8 text-slate-700 sm:text-lg dark:text-slate-300">{{ $chef->short_intro }}</p>
                    @endif

                    @if ($chef->quote)
                        <blockquote class="mt-7 border-l border-slate-300 pl-5 text-base italic leading-8 text-slate-600 dark:border-slate-700 dark:text-slate-400">
                            “{{ $chef->quote }}”
                        </blockquote>
                    @endif

                    @if ($recipes->isNotEmpty())
                        <a href="#chef-recipes" class="mt-8 inline-flex min-h-11 w-fit items-center justify-center rounded-lg bg-slate-950 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200">
                            View Chef’s Recipes
                        </a>
                    @endif
                </div>
            </div>
        </section>

        <section class="mx-auto grid w-full max-w-7xl gap-10 px-4 py-10 sm:px-6 sm:py-14 lg:grid-cols-[minmax(0,1fr)_18rem] lg:gap-16 lg:px-8" aria-labelledby="about-chef-title">
            <div>
                <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-500 dark:text-slate-500">The Story</p>
                <h2 id="about-chef-title" class="mt-2 text-3xl font-light tracking-tight text-slate-950 dark:text-white">About the Chef</h2>
                <div class="mt-6 whitespace-pre-line text-base font-light leading-8 text-slate-700 dark:text-slate-300">{{ $chef->biography }}</div>

                @if ($chef->cooking_philosophy)
                    <div class="mt-12 border-t border-slate-200 pt-10 dark:border-slate-800">
                        <h2 class="text-2xl font-light tracking-tight text-slate-950 dark:text-white">In the Kitchen</h2>
                        <div class="mt-5 whitespace-pre-line text-base font-light leading-8 text-slate-700 dark:text-slate-300">{{ $chef->cooking_philosophy }}</div>
                    </div>
                @endif
            </div>

            <aside class="space-y-8">
                @if ($chef->specialtyList() !== [])
                    <div>
                        <h2 class="text-xs font-medium uppercase tracking-[0.18em] text-slate-500 dark:text-slate-500">Specialties</h2>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($chef->specialtyList() as $specialty)
                                <span class="rounded-md border border-slate-300 px-3 py-1.5 text-xs text-slate-700 dark:border-slate-700 dark:text-slate-300">{{ $specialty }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($chef->signature_dishes)
                    <div class="border-t border-slate-200 pt-7 dark:border-slate-800">
                        <h2 class="text-xs font-medium uppercase tracking-[0.18em] text-slate-500 dark:text-slate-500">Signature Dishes</h2>
                        <p class="mt-4 whitespace-pre-line text-sm leading-7 text-slate-600 dark:text-slate-400">{{ $chef->signature_dishes }}</p>
                    </div>
                @endif

                @if ($chef->years_experience)
                    <div class="border-t border-slate-200 pt-7 dark:border-slate-800">
                        <h2 class="text-xs font-medium uppercase tracking-[0.18em] text-slate-500 dark:text-slate-500">Experience</h2>
                        <p class="mt-3 text-sm text-slate-700 dark:text-slate-300">{{ $chef->years_experience }} years in professional kitchens</p>
                    </div>
                @endif
            </aside>
        </section>

        @if ($recipes->isNotEmpty())
            <section id="chef-recipes" class="mx-auto w-full max-w-7xl scroll-mt-32 px-4 py-12 sm:px-6 sm:py-16 lg:px-8" aria-labelledby="chef-recipes-title">
                <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-500 dark:text-slate-500">Bandara Kitchen</p>
                <h2 id="chef-recipes-title" class="mt-2 text-3xl font-light tracking-tight text-slate-950 dark:text-white">Recipes by {{ $chef->display_name }}</h2>
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($recipes as $recipe)
                        @include('storefront.kitchen.partials.recipe-card', ['recipe' => $recipe])
                    @endforeach
                </div>
            </section>
        @endif

        @if (! empty($chef->qa))
            <section class="mx-auto w-full max-w-5xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8" aria-labelledby="chef-qa-title">
                <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-500 dark:text-slate-500">Five Questions</p>
                <h2 id="chef-qa-title" class="mt-2 text-3xl font-light tracking-tight text-slate-950 dark:text-white">From the Chef</h2>
                <dl class="mt-8 divide-y divide-slate-200 border-y border-slate-200 dark:divide-slate-800 dark:border-slate-800">
                    @foreach ($chef->qa as $question => $answer)
                        <div class="grid gap-3 py-7 sm:grid-cols-[minmax(0,0.44fr)_minmax(0,0.56fr)] sm:gap-10">
                            <dt class="text-sm font-medium leading-7 text-slate-950 dark:text-white">{{ $question }}</dt>
                            <dd class="whitespace-pre-line text-sm font-light leading-7 text-slate-600 dark:text-slate-400">{{ $answer }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endif

        @if ($gallery !== [])
            <section class="mx-auto w-full max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8" aria-labelledby="chef-gallery-title">
                <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-500 dark:text-slate-500">In the Kitchen</p>
                <h2 id="chef-gallery-title" class="mt-2 text-3xl font-light tracking-tight text-slate-950 dark:text-white">Gallery</h2>
                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($gallery as $imageUrl)
                        <div class="aspect-[4/3] overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-900">
                            <img src="{{ $imageUrl }}" alt="{{ $chef->display_name }} kitchen gallery image" class="h-full w-full object-cover" loading="lazy">
                        </div>
                    @endforeach
                </div>
                @if ($chef->photographer_credit)
                    <p class="mt-4 text-xs text-slate-500 dark:text-slate-500">Photography: {{ $chef->photographer_credit }}</p>
                @endif
            </section>
        @endif

        @if ($chef->culinary_training || $chef->career_highlights || $chef->awards || $professionalLinks->isNotEmpty())
            <section class="mx-auto w-full max-w-5xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
                <div class="grid gap-10 border-y border-slate-200 py-10 sm:grid-cols-2 dark:border-slate-800">
                    @if ($chef->culinary_training || $chef->career_highlights || $chef->awards)
                        <div class="space-y-7">
                            @if ($chef->culinary_training)
                                <div>
                                    <h2 class="text-sm font-medium text-slate-950 dark:text-white">Culinary training</h2>
                                    <p class="mt-2 whitespace-pre-line text-sm font-light leading-7 text-slate-600 dark:text-slate-400">{{ $chef->culinary_training }}</p>
                                </div>
                            @endif
                            @if ($chef->career_highlights)
                                <div>
                                    <h2 class="text-sm font-medium text-slate-950 dark:text-white">Career highlights</h2>
                                    <p class="mt-2 whitespace-pre-line text-sm font-light leading-7 text-slate-600 dark:text-slate-400">{{ $chef->career_highlights }}</p>
                                </div>
                            @endif
                            @if ($chef->awards)
                                <div>
                                    <h2 class="text-sm font-medium text-slate-950 dark:text-white">Recognition</h2>
                                    <p class="mt-2 whitespace-pre-line text-sm font-light leading-7 text-slate-600 dark:text-slate-400">{{ $chef->awards }}</p>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if ($professionalLinks->isNotEmpty())
                        <div>
                            <h2 class="text-sm font-medium text-slate-950 dark:text-white">Professional links</h2>
                            <div class="mt-4 flex flex-col items-start gap-3">
                                @foreach ($professionalLinks as $label => $url)
                                    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm text-slate-700 hover:text-slate-950 dark:text-slate-300 dark:hover:text-white">
                                        {{ $label }} <span aria-hidden="true">↗</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </section>
        @endif

        @if ($relatedChefs->isNotEmpty())
            <section class="mx-auto w-full max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8" aria-labelledby="other-chefs-title">
                <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <h2 id="other-chefs-title" class="text-3xl font-light tracking-tight text-slate-950 dark:text-white">Discover another chef</h2>
                    <a href="{{ route('kitchen.chefs.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-900 dark:text-slate-100">All chefs <span aria-hidden="true">→</span></a>
                </div>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($relatedChefs as $relatedChef)
                        @include('storefront.kitchen.partials.chef-card', ['chef' => $relatedChef])
                    @endforeach
                </div>
            </section>
        @else
            <div class="mx-auto w-full max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
                <a href="{{ route('kitchen.chefs.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-900 dark:text-slate-100"><span aria-hidden="true">←</span> All chefs</a>
            </div>
        @endif
    </main>
@endsection
