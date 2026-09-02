@extends('layouts.customer')

@section('title', 'Page not found | Bandara')

@section('content')
    <div class="pb-14 sm:pb-20">
        <section class="mx-auto w-full max-w-7xl px-4 pt-7 sm:px-6 sm:pt-9 lg:px-8" aria-labelledby="page-not-found-title">
            <article class="overflow-hidden rounded-xl bg-slate-50 dark:bg-slate-900">
                <div class="grid lg:min-h-[560px] lg:grid-cols-2">
                    <div class="flex flex-col justify-center px-6 py-12 sm:px-10 sm:py-16 lg:px-14 lg:py-16 xl:px-16">
                        <p class="text-xs font-medium uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">
                            Error 404
                        </p>

                        <h1 id="page-not-found-title" class="mt-5 text-4xl font-light leading-[1.05] tracking-tight text-slate-950 sm:text-5xl lg:text-6xl dark:text-white">
                            Page not found.
                        </h1>

                        <span class="mt-6 block h-px w-12 bg-slate-400 dark:bg-slate-600" aria-hidden="true"></span>

                        <p class="mt-5 max-w-xl text-base font-light leading-8 text-slate-700 sm:text-lg dark:text-slate-300">
                            Sorry, we couldn’t find the page you’re looking for.
                        </p>

                        <div class="mt-8 flex flex-wrap items-center gap-3">
                            <a
                                href="{{ url('/') }}"
                                class="inline-flex min-h-10 items-center justify-center rounded-md bg-slate-950 px-5 py-2.5 text-xs font-medium uppercase tracking-[0.13em] text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
                            >
                                Return home
                            </a>

                            <a
                                href="{{ url('/shop') }}"
                                class="inline-flex min-h-10 items-center gap-1.5 px-2 py-2 text-sm text-slate-700 transition hover:text-slate-950 dark:text-slate-300 dark:hover:text-white"
                            >
                                Browse products <span aria-hidden="true">→</span>
                            </a>
                        </div>
                    </div>

                    <div class="relative flex min-h-[300px] items-center justify-center overflow-hidden bg-slate-100 sm:min-h-[420px] lg:min-h-[560px] dark:bg-slate-950" aria-hidden="true">
                        <div class="absolute h-64 w-64 rounded-full border border-slate-300 sm:h-80 sm:w-80 dark:border-slate-800"></div>
                        <div class="absolute h-44 w-44 rounded-full border border-slate-300 sm:h-56 sm:w-56 dark:border-slate-800"></div>
                        <div class="relative text-7xl font-light tracking-tight text-slate-400 sm:text-8xl lg:text-9xl dark:text-slate-600">
                            404
                        </div>
                    </div>
                </div>
            </article>
        </section>
    </div>
@endsection
