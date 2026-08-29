@extends('layouts.customer')

@section('title', 'Bandara Kitchen')

@section('content')
    <main>
        <section class="mx-auto w-full max-w-7xl px-4 pb-8 pt-10 sm:px-6 sm:pb-12 sm:pt-14 lg:px-8">
            <nav aria-label="Breadcrumb" class="text-xs uppercase tracking-[0.14em] text-slate-500 dark:text-slate-500">
                <a href="{{ url('/') }}" class="transition hover:text-slate-900 dark:hover:text-slate-200">Home</a>
                <span aria-hidden="true" class="px-2">/</span>
                <span aria-current="page">Bandara Kitchen</span>
            </nav>

            <div class="mt-8 max-w-3xl">
                <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-500 dark:text-slate-500">Bandara Kitchen</p>
                <h1 class="mt-3 text-4xl font-light tracking-tight text-slate-950 sm:text-5xl dark:text-white">
                    Recipes, techniques and kitchen stories.
                </h1>
                <p class="mt-5 text-base font-light leading-8 text-slate-600 sm:text-lg dark:text-slate-400">
                    Meet the chefs sharing their experience, cooking philosophy and recipes with the Bandara community.
                </p>
            </div>
        </section>

        @if ($featuredChef)
            @include('storefront.kitchen.partials.featured-chef', ['bandaraKitchenFeaturedChef' => $featuredChef])
        @endif

        @if ($chefs->isNotEmpty())
            <section class="mx-auto w-full max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8" aria-labelledby="kitchen-meet-chefs-title">
                <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-500 dark:text-slate-500">Bandara Kitchen</p>
                        <h2 id="kitchen-meet-chefs-title" class="mt-2 text-2xl font-light tracking-tight text-slate-950 sm:text-3xl dark:text-white">
                            Meet the Chefs
                        </h2>
                    </div>
                    <a href="{{ route('kitchen.chefs.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-900 dark:text-slate-100">
                        View all chefs <span aria-hidden="true">→</span>
                    </a>
                </div>

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($chefs as $chef)
                        @include('storefront.kitchen.partials.chef-card', ['chef' => $chef])
                    @endforeach
                </div>
            </section>
        @elseif (! $featuredChef)
            <section class="mx-auto w-full max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
                <div class="rounded-xl border border-slate-200/80 bg-white px-6 py-12 text-center dark:border-slate-800 dark:bg-slate-950">
                    <h2 class="text-2xl font-light tracking-tight text-slate-950 dark:text-white">Stories are being prepared.</h2>
                    <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-slate-600 dark:text-slate-400">
                        Chef profiles and recipes will appear here as they are published by Bandara Kitchen.
                    </p>
                </div>
            </section>
        @endif
    </main>
@endsection
