<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name'))</title>

    <script>
        (function () {
            try {
                const stored = localStorage.getItem('theme');
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const isDark = stored ? stored === 'dark' : prefersDark;

                document.documentElement.classList.toggle('dark', isDark);
                document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';

                window.toggleTheme = function () {
                    const nextDark = !document.documentElement.classList.contains('dark');

                    document.documentElement.classList.toggle('dark', nextDark);
                    document.documentElement.style.colorScheme = nextDark ? 'dark' : 'light';
                    localStorage.setItem('theme', nextDark ? 'dark' : 'light');
                };
            } catch (e) {
                window.toggleTheme = function () {};
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="stylesheet" href="{{ asset('css/bandara-messages.css') }}?v={{ file_exists(public_path('css/bandara-messages.css')) ? filemtime(public_path('css/bandara-messages.css')) : '1' }}">

    @stack('head')
    @stack('styles')
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100 overflow-x-hidden">


    <div class="min-h-screen flex flex-col">
        {{-- Desktop top navigation only --}}
        <div class="hidden md:block">
            @if(view()->exists('partials.nav.customer'))
                @include('partials.nav.customer')
            @elseif(view()->exists('nav.customer'))
                @include('nav.customer')
            @endif
        </div>

        {{-- Mobile top search. The existing primary mobile navigation remains at the bottom. --}}
        <header class="sticky top-0 z-40 border-b border-gray-200 bg-white/95 backdrop-blur dark:border-gray-800 dark:bg-gray-900/95 md:hidden">
            <div class="mx-auto max-w-7xl px-3 pb-2 pt-2">
                <div class="mb-2 flex h-9 items-center justify-between">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2" aria-label="Bandara home">
                        <img
                            src="{{ asset('storage/images/logo-bandara.png') }}"
                            alt="Bandara"
                            class="h-9 w-9 object-contain invert-0 dark:invert"
                        >
                        <span class="text-[12px] font-medium text-gray-700 dark:text-gray-200">Bandara</span>
                    </a>

                    <a href="{{ route('shop.index') }}" class="text-[11px] text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                        Shop all
                    </a>
                </div>

                <x-storefront.search-bar mobile />
            </div>
        </header>

        {{-- Main content --}}
        <main class="flex-1 pt-0 md:pt-24 xl:pt-14 pb-20 md:pb-0">
            @include('partials.frontend.messages')

            @hasSection('content')
                @yield('content')
            @else
                {{ $slot ?? '' }}
            @endif
        </main>

        {{-- Footer --}}
        @if(view()->exists('partials.footer.customer'))
            @include('partials.footer.customer')
        @elseif(view()->exists('partials.footer'))
            @include('partials.footer')
        @endif
    </div>

    {{-- Mobile bottom navigation only --}}
    <div class="md:hidden">
        @if(view()->exists('nav.customer-mobile'))
            @include('nav.customer-mobile')
        @elseif(view()->exists('partials.nav.customer-mobile'))
            @include('partials.nav.customer-mobile')
        @endif
    </div>

    @stack('modals')
    @stack('scripts')
    @yield('scripts')
    @include('partials.storefront-ui-refinement-v3')
</body>
</html>