@props([
    'title' => 'Business Account',
    'heading' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · {{ config('app.name', 'Bandara') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (() => {
            const preferred = localStorage.getItem('theme');
            if (preferred === 'dark' || (!preferred && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>
<body class="min-h-full bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
    <div class="min-h-screen">
        <header class="border-b border-slate-200/80 bg-white/95 backdrop-blur dark:border-slate-800 dark:bg-slate-950/95">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <a href="{{ url('/') }}" class="min-w-0">
                    <span class="block text-xl font-light tracking-[0.18em] text-slate-950 dark:text-white">{{ strtoupper(config('app.name', 'Bandara')) }}</span>
                    <span class="block truncate text-[0.65rem] uppercase tracking-[0.22em] text-slate-500">Quality you can freeze on</span>
                </a>

                <nav class="flex items-center gap-2 text-sm">
                    @if (Route::has('shop.index'))
                        <a href="{{ route('shop.index') }}" class="hidden rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100 hover:text-slate-950 sm:inline-flex dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white">Shop</a>
                    @elseif (Route::has('shop'))
                        <a href="{{ route('shop') }}" class="hidden rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100 hover:text-slate-950 sm:inline-flex dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white">Shop</a>
                    @endif

                    <a href="{{ route('business-account.index') }}" class="rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white">For business</a>

                    @auth
                        <a href="{{ route('account.business-application.show') }}" class="rounded-lg bg-slate-950 px-3 py-2 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200">My application</a>
                    @else
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-slate-700 hover:border-slate-400 dark:border-slate-700 dark:text-slate-200">Sign in</a>
                        @endif
                    @endauth

                    <button type="button" aria-label="Toggle dark mode" class="rounded-lg border border-slate-300 p-2 text-slate-600 dark:border-slate-700 dark:text-slate-300" onclick="document.documentElement.classList.toggle('dark'); localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light')">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 15.75A9 9 0 1 1 8.25 2.25a7.5 7.5 0 0 0 13.5 13.5Z" /></svg>
                    </button>
                </nav>
            </div>
        </header>

        <main>
            @if ($heading)
                <div class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
                    <div class="mx-auto max-w-7xl px-4 py-7 sm:px-6 lg:px-8">
                        <p class="text-xs uppercase tracking-[0.2em] text-sky-600 dark:text-sky-300">Bandara for business</p>
                        <h1 class="mt-2 text-3xl font-light tracking-tight text-slate-950 dark:text-white">{{ $heading }}</h1>
                    </div>
                </div>
            @endif

            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                @if (session('success'))
                    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-200">
                        {{ session('error') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-200">
                        <p class="font-medium">Please correct the highlighted information.</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{ $slot }}
            </div>
        </main>

        <footer class="mt-12 border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
            <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                <p>© {{ now()->year }} {{ config('app.name', 'Bandara') }}. All rights reserved.</p>
                <p>Business enquiries are reviewed before wholesale access is enabled.</p>
            </div>
        </footer>
    </div>
</body>
</html>
