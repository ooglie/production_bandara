@extends('layouts.customer')

@section('title', 'Help & FAQs')

@push('head')
    <meta name="description" content="Answers about Bandara orders, delivery, returns, payments, B2B supply, food handling, Bandara Credit and privacy.">
@endpush

@section('content')
    @include('pages.partials.content-nav')

    @php
        $categories = config('bandara_content.faq_categories', []);
    @endphp
    <div class="max-w-6xl mx-auto px-4 py-6 space-y-6">
        <div class="min-h-screen bg-stone-50 text-slate-800 dark:bg-slate-950 dark:text-slate-200" data-faq-root>
            <header class="border-b border-stone-200 bg-white dark:border-slate-800 dark:bg-slate-950">
                <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-20 lg:px-8">
                    <p class="text-xs font-normal uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Customer care</p>
                    <div class="mt-4 grid gap-8 lg:grid-cols-[1fr_0.85fr] lg:items-end">
                        <div>
                            <h1 class="text-4xl font-light tracking-[-0.03em] text-slate-950 dark:text-white sm:text-5xl">Help & FAQs</h1>
                            <p class="mt-5 max-w-2xl text-sm font-light leading-7 text-slate-600 dark:text-slate-300 sm:text-base">Questions about an order, delivery, product, payment or Bandara Credit? Search below or browse a category.</p>
                        </div>
                        <div>
                            <label for="bandara-faq-search" class="sr-only">Search frequently asked questions</label>
                            <div class="relative">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                                <input id="bandara-faq-search" type="search" autocomplete="off" placeholder="Search delivery, refund, B2B, storage…" class="h-12 w-full rounded-lg border border-slate-300 bg-white pl-11 pr-11 text-sm font-light text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-600 focus:ring-2 focus:ring-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-slate-400 dark:focus:ring-slate-800" data-faq-search>
                                <button type="button" class="absolute right-3 top-1/2 hidden -translate-y-1/2 rounded p-1 text-slate-400 hover:text-slate-900 dark:hover:text-white" aria-label="Clear FAQ search" data-faq-clear>
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-4 w-4"><path d="m6 6 12 12M18 6 6 18"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
                <nav aria-label="FAQ categories" class="flex gap-2 overflow-x-auto pb-3 lg:flex-wrap">
                    @foreach ($categories as $category)
                        <a href="#{{ $category['slug'] }}" class="shrink-0 rounded-full border border-slate-300 bg-white px-4 py-2 text-xs text-slate-600 transition hover:border-slate-500 hover:text-slate-950 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-500 dark:hover:text-white">
                            {{ $category['label'] }}
                        </a>
                    @endforeach
                </nav>

                <p class="mt-8 hidden rounded-lg border border-slate-200 bg-white p-5 text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300" data-faq-empty>No answer matched that search. Try a shorter phrase or contact Bandara below.</p>
            </div>

            <div class="mt-10 space-y-14">
                @foreach ($categories as $category)
                    <section id="{{ $category['slug'] }}" class="scroll-mt-28" data-faq-section>
                        <div class="grid gap-7 lg:grid-cols-[0.32fr_0.68fr] lg:gap-12">
                            <div>
                                <p class="text-xs font-normal uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</p>
                                <h2 class="mt-2 text-2xl font-light tracking-tight text-slate-950 dark:text-white">{{ $category['label'] }}</h2>
                                <p class="mt-3 max-w-sm text-sm font-light leading-6 text-slate-500 dark:text-slate-400">{{ $category['description'] }}</p>
                            </div>
                            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900/70">
                                @foreach ($category['items'] as $item)
                                    <details class="group border-b border-slate-200 last:border-b-0 dark:border-slate-800" data-faq-item data-search="{{ \Illuminate\Support\Str::lower(strip_tags($category['label'].' '.$item['question'].' '.$item['answer'])) }}">
                                        <summary class="flex cursor-pointer list-none items-start justify-between gap-6 px-5 py-5 text-sm font-normal leading-6 text-slate-900 marker:hidden hover:bg-stone-50 dark:text-slate-100 dark:hover:bg-slate-900 sm:px-6">
                                            <span>{{ $item['question'] }}</span>
                                            <span class="mt-1 shrink-0 text-slate-400 transition group-open:rotate-45" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-4 w-4"><path d="M12 5v14M5 12h14"></path></svg>
                                            </span>
                                        </summary>
                                        <div class="px-5 pb-6 text-sm font-light leading-7 text-slate-600 dark:text-slate-300 sm:px-6 [&_a]:underline [&_a]:underline-offset-4 [&_li]:ml-5 [&_li]:list-disc [&_p+p]:mt-4 [&_ul]:mt-3">
                                            {!! $item['answer'] !!}
                                        </div>
                                    </details>
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endforeach
            </div>

            <div class="mt-16 grid gap-5 lg:grid-cols-[1fr_0.52fr]">
                @include('pages.partials.contact-card')
                <aside class="rounded-xl border border-slate-200 bg-slate-900 p-6 text-white dark:border-slate-700 dark:bg-slate-100 dark:text-slate-950">
                    <p class="text-xs uppercase tracking-[0.18em] text-slate-300 dark:text-slate-500">Complete wording</p>
                    <h2 class="mt-3 text-xl font-light tracking-tight">Policies behind the answers</h2>
                    <p class="mt-3 text-sm font-light leading-6 text-slate-300 dark:text-slate-600">The FAQ is a plain-language guide. The complete controlling terms and privacy wording remain available as uninterrupted documents.</p>
                    <div class="mt-6 flex flex-col gap-3 text-sm">
                        <a href="{{ route('content.terms') }}" class="inline-flex items-center justify-between rounded-lg border border-white/20 px-4 py-3 hover:bg-white/10 dark:border-slate-300 dark:hover:bg-slate-200">Terms & Policies <span aria-hidden="true">→</span></a>
                        <a href="{{ route('content.privacy') }}" class="inline-flex items-center justify-between rounded-lg border border-white/20 px-4 py-3 hover:bg-white/10 dark:border-slate-300 dark:hover:bg-slate-200">Privacy Policy <span aria-hidden="true">→</span></a>
                    </div>
                </aside>
            </div>
        </div>
    </div>
    
    
    <script>
        (() => {
            const root = document.querySelector('[data-faq-root]');
            if (!root) return;

            const input = root.querySelector('[data-faq-search]');
            const clear = root.querySelector('[data-faq-clear]');
            const empty = root.querySelector('[data-faq-empty]');
            const sections = [...root.querySelectorAll('[data-faq-section]')];
            const normalise = (value) => value.toLocaleLowerCase().trim().replace(/\s+/g, ' ');

            const apply = () => {
                const query = normalise(input.value);
                let totalVisible = 0;

                sections.forEach((section) => {
                    let sectionVisible = 0;
                    section.querySelectorAll('[data-faq-item]').forEach((item) => {
                        const matches = !query || normalise(item.dataset.search || '').includes(query);
                        item.hidden = !matches;
                        if (matches) sectionVisible += 1;
                    });
                    section.hidden = sectionVisible === 0;
                    totalVisible += sectionVisible;
                });

                clear.classList.toggle('hidden', query.length === 0);
                empty.classList.toggle('hidden', totalVisible > 0);
            };

            input.addEventListener('input', apply);
            clear.addEventListener('click', () => {
                input.value = '';
                apply();
                input.focus();
            });
        })();
    </script>
@endsection
