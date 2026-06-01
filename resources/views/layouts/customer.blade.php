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

    @stack('head')
    @stack('styles')
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100 overflow-x-hidden">
@php
    $desktopNavPartial = view()->exists('partials.nav.customer')
        ? 'partials.nav.customer'
        : (view()->exists('nav.customer') ? 'nav.customer' : null);

    $mobileNavPartial = view()->exists('nav.customer-mobile')
        ? 'nav.customer-mobile'
        : (view()->exists('partials.nav.customer-mobile') ? 'partials.nav.customer-mobile' : null);

    $footerPartial = view()->exists('partials.footer.customer')
        ? 'partials.footer.customer'
        : (view()->exists('partials.footer') ? 'partials.footer' : null);
@endphp

    <div class="min-h-screen flex flex-col">
        {{-- Desktop top navigation only --}}
        @if($desktopNavPartial)
            <div class="hidden md:block">
                @include($desktopNavPartial)
            </div>
        @endif

        {{-- Main content --}}
        <main class="flex-1 pt-0 md:pt-14 pb-20 md:pb-0">
            @hasSection('content')
                @yield('content')
            @else
                {{ $slot ?? '' }}
            @endif
        </main>

        {{-- Footer --}}
        @if($footerPartial)
            @include($footerPartial)
        @endif
    </div>

    {{-- Mobile bottom navigation only --}}
    @if($mobileNavPartial)
        <div class="md:hidden">
            @include($mobileNavPartial)
        </div>
    @endif

    @stack('modals')
    @stack('scripts')
    @yield('scripts')
</body>
</html>