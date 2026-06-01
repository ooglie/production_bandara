@php
    use Illuminate\Support\Facades\Route;

    $isB2bCustomer = auth()->check() && ((auth()->user()->customer_type ?? 'b2c') === 'b2b');

    $ordersOrLoginUrl = auth()->check() ? route('orders.index') : route('login');
    $ordersOrLoginActive = auth()->check()
        ? request()->routeIs('orders.*')
        : (request()->routeIs('login') || request()->routeIs('register') || request()->routeIs('password.*'));
    $ordersOrLoginLabel = auth()->check() ? 'Orders' : 'Login';

    $wishlistEnabled = config('features.wishlist', true);

    $homeUrl = $isB2bCustomer && Route::has('b2b.dashboard') ? route('b2b.dashboard') : route('home');
    $homeLabel = $isB2bCustomer ? 'Dashboard' : 'Home';
    $homeActive = $isB2bCustomer ? request()->routeIs('b2b.dashboard') : request()->routeIs('home');

    $catalogueOrShopUrl = $isB2bCustomer && Route::has('b2b.catalog.index') ? route('b2b.catalog.index') : route('shop.index');
    $catalogueOrShopLabel = $isB2bCustomer ? 'Catalogue' : 'Shop';
    $catalogueOrShopActive = $isB2bCustomer
        ? request()->routeIs('b2b.catalog.*')
        : (request()->routeIs('shop.*') || request()->routeIs('product.show') || request()->routeIs('collections.show'));
    $shopActive = $catalogueOrShopActive;

    $portfolioOrCartUrl = $isB2bCustomer && Route::has('b2b.portfolio') ? route('b2b.portfolio') : route('cart.index');
    $portfolioOrCartLabel = $isB2bCustomer ? 'Portfolio' : 'Cart';
    $portfolioOrCartActive = $isB2bCustomer ? request()->routeIs('b2b.portfolio') : (request()->routeIs('cart.*') || request()->routeIs('checkout.*'));
    $cartActive = $portfolioOrCartActive;

    $moreActive =
        request()->routeIs('wishlist.*') ||
        request()->routeIs('tickets.*') ||
        request()->routeIs('account.*') ||
        request()->routeIs('dashboard.customer') ||
        request()->routeIs('b2b.*') ||
        request()->routeIs('invoices.*') ||
        request()->routeIs('login') ||
        request()->routeIs('register') ||
        request()->routeIs('password.*');

    $wishlistUrl = auth()->check()
        ? ($isB2bCustomer && Route::has('b2b.wishlist.index') ? route('b2b.wishlist.index') : route('wishlist.index'))
        : route('login');
    $cartUrl = $isB2bCustomer && Route::has('b2b.cart.index') ? route('b2b.cart.index') : route('cart.index');
    $accountUrl = auth()->check()
        ? ($isB2bCustomer && Route::has('b2b.dashboard') ? route('b2b.dashboard') : route('account.dashboard'))
        : route('login');

    $itemBase = 'relative flex min-h-[56px] flex-col items-center justify-center gap-0.5 px-1 text-[10px]';
    $itemActive = 'text-gray-900 dark:text-gray-50';
    $itemInactive = 'text-gray-600 dark:text-gray-300';
@endphp

<nav
    class="fixed inset-x-0 bottom-0 z-50 border-t border-gray-200 dark:border-gray-800 bg-white/95 dark:bg-gray-900/95 backdrop-blur md:hidden shadow-[0_-4px_12px_rgba(0,0,0,0.06)]"
    style="padding-bottom: env(safe-area-inset-bottom);"
>
    <div class="max-w-7xl mx-auto px-2">
        <div class="grid grid-cols-5 items-stretch">

            <a href="{{ $homeUrl }}"
               class="{{ $itemBase }} {{ $homeActive ? $itemActive : $itemInactive }}">
                @if($homeActive)
                    <span class="absolute top-0 left-1/2 -translate-x-1/2 h-0.5 w-8 rounded-sm bg-gray-900 dark:bg-gray-100"></span>
                @endif

                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M3 11.25L12 4l9 7.25M4.5 10.5V20h15v-9.5" />
                </svg>
                <span>{{ $homeLabel }}</span>
            </a>

            @if($isB2bCustomer)
                <a href="{{ route('b2b.catalog.index') }}"
                   class="{{ $itemBase }} {{ request()->routeIs('b2b.catalog.*') ? $itemActive : $itemInactive }}">
                    @if(request()->routeIs('b2b.catalog.*'))
                        <span class="absolute top-0 left-1/2 -translate-x-1/2 h-0.5 w-8 rounded-sm bg-gray-900 dark:bg-gray-100"></span>
                    @endif
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 4h18M4 7h16l-1.5 9.5H5.5L4 7Z" />
                    </svg>
                    <span>Catalogue</span>
                </a>

                <a href="{{ $cartUrl }}"
                   class="{{ $itemBase }} {{ request()->routeIs('b2b.cart.*') || request()->routeIs('b2b.checkout.*') ? $itemActive : $itemInactive }}">
                    @if(request()->routeIs('b2b.cart.*') || request()->routeIs('b2b.checkout.*'))
                        <span class="absolute top-0 left-1/2 -translate-x-1/2 h-0.5 w-8 rounded-sm bg-gray-900 dark:bg-gray-100"></span>
                    @endif
                    <span class="relative inline-flex">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 4h2l1 5h12l1-4H6" />
                            <circle cx="9" cy="19" r="1" />
                            <circle cx="17" cy="19" r="1" />
                        </svg>
                        @isset($cartCount)
                            @if($cartCount > 0)
                                <span class="absolute -top-1.5 -right-2 inline-flex min-h-[14px] min-w-[14px] items-center justify-center rounded-sm bg-gray-900 text-white dark:bg-gray-50 dark:text-gray-900 text-[9px] px-0.5 leading-none">
                                    {{ $cartCount > 9 ? '9+' : $cartCount }}
                                </span>
                            @endif
                        @endisset
                    </span>
                    <span>Cart</span>
                </a>
            @else
                <a href="{{ route('shop.index') }}"
                   class="{{ $itemBase }} {{ $catalogueOrShopActive ? $itemActive : $itemInactive }}">
                    @if($catalogueOrShopActive)
                        <span class="absolute top-0 left-1/2 -translate-x-1/2 h-0.5 w-8 rounded-sm bg-gray-900 dark:bg-gray-100"></span>
                    @endif
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 4h18M4 7h16l-1.5 9.5H5.5L4 7Z" />
                        <circle cx="9" cy="19" r="1" />
                        <circle cx="17" cy="19" r="1" />
                    </svg>
                    <span>Shop</span>
                </a>

                <a href="{{ route('cart.index') }}"
                   class="{{ $itemBase }} {{ $cartActive ? $itemActive : $itemInactive }}">
                    @if($cartActive)
                        <span class="absolute top-0 left-1/2 -translate-x-1/2 h-0.5 w-8 rounded-sm bg-gray-900 dark:bg-gray-100"></span>
                    @endif
                    <span class="relative inline-flex">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 4h2l1 5h12l1-4H6" />
                            <circle cx="9" cy="19" r="1" />
                            <circle cx="17" cy="19" r="1" />
                        </svg>
                        @isset($cartCount)
                            @if($cartCount > 0)
                                <span class="absolute -top-1.5 -right-2 inline-flex min-h-[14px] min-w-[14px] items-center justify-center rounded-sm bg-gray-900 text-white dark:bg-gray-50 dark:text-gray-900 text-[9px] px-0.5 leading-none">
                                    {{ $cartCount > 9 ? '9+' : $cartCount }}
                                </span>
                            @endif
                        @endisset
                    </span>
                    <span>Cart</span>
                </a>
            @endif

            <a href="{{ $ordersOrLoginUrl }}"
               class="{{ $itemBase }} {{ $ordersOrLoginActive ? $itemActive : $itemInactive }}">
                @if($ordersOrLoginActive)
                    <span class="absolute top-0 left-1/2 -translate-x-1/2 h-0.5 w-8 rounded-sm bg-gray-900 dark:bg-gray-100"></span>
                @endif

                @if(auth()->check())
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.25 6.75h7.5M8.25 10.5h7.5M8.25 14.25h4.5" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6.75 3.75h10.5A2.25 2.25 0 0 1 19.5 6v12a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 18V6a2.25 2.25 0 0 1 2.25-2.25Z" />
                    </svg>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 9V5.25a3.75 3.75 0 0 0-7.5 0V9" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6.75 9h10.5A1.5 1.5 0 0 1 18.75 10.5v7.5A1.5 1.5 0 0 1 17.25 19.5H6.75A1.5 1.5 0 0 1 5.25 18v-7.5A1.5 1.5 0 0 1 6.75 9Z" />
                    </svg>
                @endif

                <span>{{ $ordersOrLoginLabel }}</span>
            </a>

            <button type="button"
                    id="mobile-more-toggle"
                    class="{{ $itemBase }} {{ $moreActive ? $itemActive : $itemInactive }}"
                    aria-expanded="false"
                    aria-controls="mobile-more-sheet">
                @if($moreActive)
                    <span class="absolute top-0 left-1/2 -translate-x-1/2 h-0.5 w-8 rounded-sm bg-gray-900 dark:bg-gray-100"></span>
                @endif

                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="5" cy="12" r="1.5" fill="currentColor" stroke="none"></circle>
                    <circle cx="12" cy="12" r="1.5" fill="currentColor" stroke="none"></circle>
                    <circle cx="19" cy="12" r="1.5" fill="currentColor" stroke="none"></circle>
                </svg>
                <span>More</span>
            </button>
        </div>
    </div>
</nav>

<div id="mobile-more-backdrop"
     class="hidden fixed inset-0 z-40 bg-black/30 md:hidden"></div>

<div id="mobile-more-sheet"
     class="hidden fixed inset-x-3 z-50 rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-xl md:hidden"
     style="bottom: calc(64px + env(safe-area-inset-bottom));">
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-800">
        <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">More</div>
        <button type="button"
                id="mobile-more-close"
                class="inline-flex h-8 w-8 items-center justify-center rounded-sm border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800"
                aria-label="Close menu">
            ✕
        </button>
    </div>

    <div class="p-4 space-y-4">
        <div class="grid grid-cols-2 gap-2">
            @if($wishlistEnabled)
                <a href="{{ $wishlistUrl }}"
                   class="inline-flex items-center justify-between rounded-sm border border-gray-200 dark:border-gray-700 px-3 py-2 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                    <span>Wishlist</span>
                    @isset($wishlistCount)
                        @if($wishlistCount > 0)
                            <span class="inline-flex min-h-[16px] min-w-[16px] items-center justify-center rounded-sm bg-gray-900 text-white dark:bg-gray-50 dark:text-gray-900 text-[9px] px-1 leading-none">
                                {{ $wishlistCount > 9 ? '9+' : $wishlistCount }}
                            </span>
                        @endif
                    @endisset
                </a>
            @endif

            <a href="{{ route('tickets.index') }}"
               class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 px-3 py-2 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                Support
            </a>

            <a href="{{ $accountUrl }}"
               class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 px-3 py-2 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                {{ auth()->check() ? 'Account' : 'Sign in' }}
            </a>

            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 px-3 py-2 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                        Logout
                    </button>
                </form>
            @endauth
        </div>

        <div class="space-y-2">
            <div class="text-[10px] uppercase tracking-wide text-gray-400 dark:text-gray-500">
                Language
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" onclick="translateLanguage('en')" class="rounded-sm border border-gray-200 dark:border-gray-700 px-3 py-2 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">🇺🇸 English</button>
                <button type="button" onclick="translateLanguage('hi')" class="rounded-sm border border-gray-200 dark:border-gray-700 px-3 py-2 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">🇮🇳 Hindi</button>
                <button type="button" onclick="translateLanguage('mr')" class="rounded-sm border border-gray-200 dark:border-gray-700 px-3 py-2 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">🇮🇳 Marathi</button>
                <button type="button" onclick="translateLanguage('th')" class="rounded-sm border border-gray-200 dark:border-gray-700 px-3 py-2 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">🇹🇭 Thai</button>
                <button type="button" onclick="translateLanguage('ko')" class="rounded-sm border border-gray-200 dark:border-gray-700 px-3 py-2 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">🇰🇷 Korean</button>
                <button type="button" onclick="translateLanguage('fr')" class="rounded-sm border border-gray-200 dark:border-gray-700 px-3 py-2 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">🇫🇷 French</button>
                <button type="button" onclick="translateLanguage('es')" class="rounded-sm border border-gray-200 dark:border-gray-700 px-3 py-2 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">🇪🇸 Spanish</button>
                <button type="button" onclick="translateLanguage('de')" class="rounded-sm border border-gray-200 dark:border-gray-700 px-3 py-2 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">🇩🇪 German</button>
            </div>
        </div>

        <div class="space-y-2">
            <div class="text-[10px] uppercase tracking-wide text-gray-400 dark:text-gray-500">
                Appearance
            </div>
            <button type="button"
                    onclick="if (typeof toggleTheme === 'function') { toggleTheme(); }"
                    class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 px-3 py-2 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                Toggle dark mode
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    const toggle = document.getElementById('mobile-more-toggle');
    const closeBtn = document.getElementById('mobile-more-close');
    const sheet = document.getElementById('mobile-more-sheet');
    const backdrop = document.getElementById('mobile-more-backdrop');

    if (!toggle || !sheet || !backdrop) return;
    if (toggle.dataset.bound === 'true') return;
    toggle.dataset.bound = 'true';

    function openSheet() {
        sheet.classList.remove('hidden');
        backdrop.classList.remove('hidden');
        toggle.setAttribute('aria-expanded', 'true');
    }

    function closeSheet() {
        sheet.classList.add('hidden');
        backdrop.classList.add('hidden');
        toggle.setAttribute('aria-expanded', 'false');
    }

    toggle.addEventListener('click', function () {
        if (sheet.classList.contains('hidden')) openSheet();
        else closeSheet();
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', closeSheet);
    }

    backdrop.addEventListener('click', closeSheet);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeSheet();
    });
})();
</script>