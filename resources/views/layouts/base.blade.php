<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="google" content="notranslate">
    <meta name="robots" content="notranslate">

    <title>@yield('title', config('app.name', 'Bandara by Maytira'))</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Hide Google UI completely, keep translation engine working */
        body > .skiptranslate {
            display: none !important;
        }

        .goog-te-banner-frame,
        .goog-te-banner-frame.skiptranslate,
        .goog-te-gadget,
        .goog-te-gadget-simple,
        .goog-te-gadget-icon,
        .goog-logo-link,
        #goog-gt-tt,
        .goog-te-balloon-frame {
            display: none !important;
            visibility: hidden !important;
        }

        .goog-text-highlight {
            background: transparent !important;
            box-shadow: none !important;
        }

        body {
            top: 0 !important;
            position: static !important;
        }

        html {
            margin-top: 0 !important;
        }
    </style>

    @stack('scripts')
    
    <script>
        (function () {
            const STORAGE_KEY = 'theme';

            function getStoredTheme() {
                try {
                    const value = localStorage.getItem(STORAGE_KEY);
                    if (value === 'light' || value === 'dark' || value === 'system') {
                        return value;
                    }
                } catch (e) {}
                return 'system';
            }

            function prefersDark() {
                return window.matchMedia &&
                    window.matchMedia('(prefers-color-scheme: dark)').matches;
            }

            function shouldUseDark(theme) {
                if (theme === 'dark') return true;
                if (theme === 'light') return false;
                return prefersDark();
            }

            function applyTheme(theme) {
                const html = document.documentElement;
                const dark = shouldUseDark(theme);

                if (dark) {
                    html.classList.add('dark');
                } else {
                    html.classList.remove('dark');
                }

                html.dataset.theme = theme;
            }

            const initialTheme = getStoredTheme();
            applyTheme(initialTheme);

            window.toggleTheme = function () {
                const current = getStoredTheme();
                let next = 'dark';

                if (current === 'light') next = 'dark';
                else if (current === 'dark') next = 'light';
                else next = 'dark';

                try {
                    localStorage.setItem(STORAGE_KEY, next);
                } catch (e) {}

                applyTheme(next);
            };
        })();
    </script>

    <script>
        document.addEventListener('click', function (event) {
            const roots = document.querySelectorAll('[data-user-menu]');

            roots.forEach(function (root) {
                const toggle = root.querySelector('[data-user-menu-toggle]');
                const panel  = root.querySelector('[data-user-menu-panel]');
                if (!toggle || !panel) return;

                const clickedInsideRoot = root.contains(event.target);
                const clickedOnToggle = toggle.contains(event.target);

                if (clickedOnToggle) {
                    const isOpening = panel.classList.contains('hidden');
                    panel.classList.toggle('hidden');
                    toggle.setAttribute('aria-expanded', isOpening ? 'true' : 'false');
                } else if (!clickedInsideRoot) {
                    panel.classList.add('hidden');
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('[data-user-menu]').forEach(function (root) {
                    const toggle = root.querySelector('[data-user-menu-toggle]');
                    const panel = root.querySelector('[data-user-menu-panel]');
                    if (panel) panel.classList.add('hidden');
                    if (toggle) toggle.setAttribute('aria-expanded', 'false');
                });
            }
        });
    </script>

    <script>
        function googleTranslateElementInit() {
            new google.translate.TranslateElement(
                {
                    pageLanguage: 'en',
                    autoDisplay: false
                },
                'google_translate_element'
            );
        }

        function translateLanguage(lang) {
            const host = window.location.hostname;

            document.cookie = 'googtrans=/en/' + lang + ';path=/';
            document.cookie = 'googtrans=/en/' + lang + ';domain=' + host + ';path=/';

            window.location.reload();
        }

        function cleanupGoogleUi() {
            const selectors = [
                'body > .skiptranslate',
                '.goog-te-banner-frame',
                '.goog-te-banner-frame.skiptranslate',
                '.goog-te-balloon-frame',
                '#goog-gt-tt'
            ];

            selectors.forEach(function (selector) {
                document.querySelectorAll(selector).forEach(function (el) {
                    if (selector === 'body > .skiptranslate') {
                        el.style.display = 'none';
                    } else {
                        el.remove();
                    }
                });
            });

            document.body.style.top = '0px';
            document.documentElement.style.marginTop = '0px';
        }

        setInterval(cleanupGoogleUi, 300);
    </script>

    <script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

    @stack('head')
</head>
<body class="h-full bg-gray-50 text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100">
    {{-- Hidden Google container: required for translation engine --}}
    <div id="google_translate_element" class="hidden" aria-hidden="true"></div>

    @yield('body')

    @stack('scripts')
</body>
</html>