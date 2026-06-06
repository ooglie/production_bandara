
{{-- Guest: show Sign in / Register buttons --}}
@guest
    <div class="flex items-center gap-2 text-xs">
        <a href="{{ route('login') }}"
           class="rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-gray-800 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">
            Sign in
        </a>
        @if (Route::has('register'))
            <a href="{{ route('register') }}"
               class="rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 hover:bg-gray-800 dark:hover:bg-gray-200">
                Register
            </a>
        @endif
    </div>
@endguest

{{-- Authenticated: avatar + dropdown --}}
@auth
<div class="relative text-xs" data-user-menu>
    <button type="button"
            data-user-menu-toggle
            class="flex items-center gap-2 rounded-full  dark:border-gray-700 bg-white/80 dark:bg-gray-900/80 px-2 py-1.5 hover:bg-gray-100 dark:hover:bg-gray-800">
        <div class="hidden sm:flex flex-col items-start leading-tight">
           
        </div>
        <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 text-[11px] font-medium">
            {{ $initial }}
        </span>
        <svg class="h-3 w-3 text-gray-500 dark:text-gray-300"
             xmlns="http://www.w3.org/2000/svg" fill="none"
             viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M6 9l6 6 6-6"/>
        </svg>
    </button>

    <div data-user-menu-panel
         class="hidden absolute right-0 mt-2 w-56 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-lg z-40">
        {{-- Header --}}
        <div class="px-3 py-2  dark:border-gray-800">
            <div class="text-[11px] font-medium text-gray-900 dark:text-gray-50 truncate">
                {{ $user->name }}
            </div>
            <div class="text-[10px] text-gray-500 dark:text-gray-400 truncate">
                {{ $user->email }}
            </div>
            @if($roleName)
                <div class="mt-1 inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-[10px] text-gray-700 dark:text-gray-200">
                    {{ $roleName }}
                </div>
            @endif
        </div>

        {{-- Links --}}
        <div class="py-1 text-[11px]">
            {{-- <a href="{{ fb_dashboard_route($user) }}"
               class="flex items-center gap-2 px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-800 dark:text-gray-100">
                <span>Dashboard</span>
            </a> --}}

            @if($user->hasRole('Customer'))
                <a href="{{ route('dashboard.customer') }}"
                   class="flex items-center gap-2 px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-800 dark:text-gray-100">
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('shop.index') }}"
                   class="flex items-center gap-2 px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-800 dark:text-gray-100">
                    <span>Shop</span>
                </a>

                <a href="{{ route('orders.index') }}"
                   class="flex items-center gap-2 px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-800 dark:text-gray-100">
                    <span>My orders</span>
                </a>
                <a href="{{ route('invoices.index') }}"
                   class="flex items-center gap-2 px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-800 dark:text-gray-100">
                    <span>Invoices</span>
                </a>
                @if(config('features.wishlist', true))
                    <a href="{{ route('wishlist.index') }}"
                       class="flex items-center gap-2 px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-800 dark:text-gray-100">
                        <span>Wishlist</span>
                    </a>
                @endif
            @endif

            @if($user->hasAnyRole(['Admin','Manager','Accountant','CAAccountant','Support', 'Stores', 'DeliveryAgent']))
                <div class="mt-1 border-t border-gray-100 dark:border-gray-800"></div>
                @if($user->hasRole('Admin'))
                    <a href="{{ route('admin.dashboard') }}"
                       class="flex items-center gap-2 px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-800 dark:text-gray-100">
                        <span>Admin panel</span>
                    </a>
                @elseif($user->hasRole('Manager'))
                    <a href="{{ route('manager.dashboard') }}"
                       class="flex items-center gap-2 px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-800 dark:text-gray-100">
                        <span>Manager dashboard</span>
                    </a>
                @elseif($user->hasRole('Accountant') || $user->hasRole('CAAccountant'))
                    <a href="{{ route('accountant.dashboard') }}"
                       class="flex items-center gap-2 px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-800 dark:text-gray-100">
                        <span>Accountant dashboard</span>
                    </a>
                @elseif($user->hasRole('Support'))
                    <a href="{{ route('support.dashboard') }}"
                       class="flex items-center gap-2 px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-800 dark:text-gray-100">
                        <span>Support panel</span>
                    </a>
                @elseif($user->hasRole('Stores'))
                    <a href="{{ route('stores.dashboard') }}"
                       class="flex items-center gap-2 px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-800 dark:text-gray-100">
                        <span>Stores dashboard</span>
                    </a>
                @elseif($user->hasRole('DeliveryAgent') && Route::has('delivery.index'))
                    <a href="{{ route('delivery.index') }}"
                       class="flex items-center gap-2 px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-800 dark:text-gray-100">
                        <span>Delivery dashboard</span>
                    </a>
                @endif
            @endif

            @if(session()->has('impersonator_id'))
                <div class="mt-1 border-t border-gray-100 dark:border-gray-800"></div>
                <form method="POST" action="{{ route('impersonation.stop') }}">
                    @csrf
                    <button type="submit"
                            class="w-full text-left flex items-center gap-2 px-3 py-1.5 hover:bg-amber-50 dark:hover:bg-amber-900/40 text-amber-800 dark:text-amber-100">
                        <span>Stop impersonating</span>
                    </button>
                </form>
            @endif
        </div>

        {{-- Logout --}}
        <div class="border-t border-gray-100 dark:border-gray-800 px-3 py-1.5">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full text-left text-[11px] text-red-600 dark:text-red-400 hover:underline">
                    Logout
                </button>
            </form>
        </div>
    </div>
</div>
@endauth
