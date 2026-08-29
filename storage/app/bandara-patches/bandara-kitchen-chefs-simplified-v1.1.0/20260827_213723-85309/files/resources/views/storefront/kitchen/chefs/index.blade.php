@extends('layouts.customer')

@section('title', 'Chefs | Bandara Kitchen')

@section('content')
    <main>
        <section class="mx-auto w-full max-w-7xl px-4 pb-8 pt-10 sm:px-6 sm:pb-12 sm:pt-14 lg:px-8">
            <nav aria-label="Breadcrumb" class="text-xs uppercase tracking-[0.14em] text-slate-500 dark:text-slate-500">
                <a href="{{ url('/') }}" class="transition hover:text-slate-900 dark:hover:text-slate-200">Home</a>
                <span aria-hidden="true" class="px-2">/</span>
                <a href="{{ route('kitchen.index') }}" class="transition hover:text-slate-900 dark:hover:text-slate-200">Bandara Kitchen</a>
                <span aria-hidden="true" class="px-2">/</span>
                <span aria-current="page">Chefs</span>
            </nav>

            <div class="mt-8 max-w-3xl">
                <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-500 dark:text-slate-500">Bandara Kitchen</p>
                <h1 class="mt-3 text-4xl font-light tracking-tight text-slate-950 sm:text-5xl dark:text-white">Meet the Chefs</h1>
                <p class="mt-5 text-base font-light leading-8 text-slate-600 sm:text-lg dark:text-slate-400">
                    Professional chefs sharing recipes, techniques and stories from their kitchens.
                </p>
            </div>
        </section>

        <section class="mx-auto w-full max-w-7xl px-4 pb-16 sm:px-6 sm:pb-20 lg:px-8" aria-label="Published chefs">
            @if ($chefs->isNotEmpty())
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($chefs as $chef)
                        @include('storefront.kitchen.partials.chef-card', ['chef' => $chef])
                    @endforeach
                </div>

                @if ($chefs->hasPages())
                    <div class="mt-10">
                        {{ $chefs->links() }}
                    </div>
                @endif
            @else
                <div class="rounded-xl border border-slate-200/80 bg-white px-6 py-12 text-center dark:border-slate-800 dark:bg-slate-950">
                    <h2 class="text-2xl font-light tracking-tight text-slate-950 dark:text-white">No chef profiles are published yet.</h2>
                    <p class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-400">Please return to Bandara Kitchen for newly published stories.</p>
                    <a href="{{ route('kitchen.index') }}" class="mt-6 inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-900 dark:border-slate-700 dark:text-slate-100">
                        Return to Bandara Kitchen
                    </a>
                </div>
            @endif
        </section>
    </main>
@endsection
