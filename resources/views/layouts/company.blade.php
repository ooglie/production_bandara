@php
    /** @var \App\Models\User|null $user */
    $user = auth()->user();
    $roleName = $user?->getRoleNames()->first();
    $initial = $user ? mb_strtoupper(mb_substr($user->name, 0, 1)) : '?';

    function fb_role_label($role) {
        if (!$role) return 'Guest';
        return $role;
    }

    function fb_dashboard_route($user) {
        if (!$user) return null;

        if ($user->hasRole('Customer'))   return route('account.dashboard');
        if ($user->hasRole('Admin'))      return route('admin.dashboard');
        if ($user->hasRole('Manager'))    return route('manager.dashboard');
        if ($user->hasRole('Support'))    return route('support.dashboard');
        if ($user->hasRole('Accountant') || $user->hasRole('CAAccountant')) return route('accountant.dashboard');
        if ($user->hasRole('Stores'))     return route('stores.dashboard');
        if ($user->hasRole('DeliveryAgent') && Route::has('delivery.index')) return route('delivery.index');

        // default for unknown roles
        return route('home');
    }
@endphp

@extends('layouts.base')


@section('body')
    @if(auth()->check() && session()->has('impersonator_id'))
        <div class="bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-100 text-[11px] px-4 py-2 flex items-center justify-between">
            <span>
                You are currently impersonating
                <strong>{{ auth()->user()->name }}</strong>.
            </span>
            <form method="POST" action="{{ route('impersonation.stop') }}">
                @csrf
                <button
                    type="submit"
                    class="underline">
                    Stop impersonating
                </button>
            </form>
        </div>
    @endif


    <div class="min-h-screen flex bg-gray-50 dark:bg-gray-950">
        {{-- Sidebar --}}
        @include('partials.nav.company')

        {{-- Main content --}}
        <div class="flex-1 flex flex-col">
            {{-- <header class="border-b border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 backdrop-blur">
                <div class="px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        @yield('breadcrumb')
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            onclick="toggleTheme()"
                            class="text-xs px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800"
                        >
                            Toggle theme
                        </button>


                        <div class="text-xs text-gray-600 dark:text-gray-300">
                            {{ auth()->user()->name ?? 'User' }}<br>
                            <span class="text-gray-400 text-[11px]">
                                {{ auth()->user()->getRoleNames()->first() ?? 'Role' }}
                            </span>
                        </div>
                    </div>
                </div>
            </header> --}}

            <header class="sticky top-0 z-40 border-b border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 backdrop-blur">
                <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6">
                    <div class="flex h-14 items-center justify-between gap-3">
                        

                        {{-- Center: main links (desktop) --}}
                        <nav class="hidden md:flex items-center gap-4 text-[11px] text-gray-700 dark:text-gray-200">
                            
                        </nav>

                        {{-- Right: actions --}}
                        <div class="flex items-center gap-2">
                            {{-- Wishlist (icon) --}}
                            {{--  --}}

                            {{-- Theme toggle --}}
                            <button
                                type="button"
                                onclick="toggleTheme()"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800"
                            >
                                <span class="sr-only">Toggle theme</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-700 dark:text-gray-200" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M12 3v2.25M18.364 5.636l-1.59 1.59M21 12h-2.25M18.364 18.364l-1.59-1.59M12 18.75V21M7.226 16.774l-1.59 1.59M5.25 12H3M7.226 7.226l-1.59-1.59M16 12a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" />
                                </svg>
                            </button>

                            {{-- User menu (avatar + dropdown) --}}
                            @include('partials.user-menu')

                            {{-- Mobile menu placeholder (if you add one later) --}}
                            <button class="md:hidden inline-flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 dark:border-gray-700 ml-1">
                                <span class="sr-only">Open menu</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-700 dark:text-gray-200" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M4 7h16M4 12h16M4 17h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1">
                <div class="px-4 sm:px-6 lg:px-8 py-6">
                    @yield('content')
                </div>
            </main>

            @include('partials.footer.company')
        </div>
    </div>
@endsection
