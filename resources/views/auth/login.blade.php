@extends('layouts.customer')

@section('title', 'Sign in')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8 sm:py-10">
    <div class="grid gap-4 lg:grid-cols-[1.05fr,0.95fr] items-stretch">

        {{-- Left intro panel --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-gradient-to-br from-white via-slate-50 to-sky-50 dark:from-gray-900 dark:via-gray-900 dark:to-slate-900 p-6 sm:p-8 flex flex-col justify-between min-h-[280px]">
            <div class="space-y-4">
                <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white/90 dark:bg-gray-950/50 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.14em] text-gray-600 dark:text-gray-300">
                    Frozen • Bandara by Maytira
                </span>

                <div class="space-y-2">
                    <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-gray-900 dark:text-gray-50 leading-tight">
                        Welcome back
                    </h1>

                    <p class="max-w-md text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                        Sign in to review orders, track invoices, manage support requests, and continue shopping quickly.
                    </p>
                </div>
            </div>

            <div class="mt-6 grid gap-3 sm:grid-cols-3">
                <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-950/40 px-4 py-3">
                    <div class="text-[10px] uppercase tracking-wide text-gray-400">Orders</div>
                    <div class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-50">Track status easily</div>
                </div>
                <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-950/40 px-4 py-3">
                    <div class="text-[10px] uppercase tracking-wide text-gray-400">Invoices</div>
                    <div class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-50">Download anytime</div>
                </div>
                <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-950/40 px-4 py-3">
                    <div class="text-[10px] uppercase tracking-wide text-gray-400">Support</div>
                    <div class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-50">Get help quickly</div>
                </div>
            </div>
        </div>

        {{-- Right form card --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 sm:p-8 space-y-5">
            <div class="space-y-1">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                    Sign in to your account
                </h2>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                    Use your registered email and password.
                </p>
            </div>

            @if(session('status'))
                <div class="rounded-sm border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-sm border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
                    <ul class="list-disc pl-4 space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Email
                    </label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                        placeholder="you@example.com"
                    >
                    @error('email')
                        <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                            Password
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}"
                               class="text-[11px] text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                                Forgot password?
                            </a>
                        @endif
                    </div>

                    <input
                        type="password"
                        name="password"
                        required
                        class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                        placeholder="Enter your password"
                    >
                    @error('password')
                        <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between text-[11px]">
                    <label class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-300">
                        <input type="checkbox" name="remember" class="rounded-sm">
                        <span>Remember me</span>
                    </label>
                </div>

                <button
                    type="submit"
                    class="w-full inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2.5 text-xs font-medium hover:bg-gray-800 dark:hover:bg-gray-200"
                >
                    Sign in
                </button>
            </form>

            @if (Route::has('register'))
                <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3 text-[11px] text-gray-600 dark:text-gray-300">
                    New to Frozen – Bandara?
                    <a href="{{ route('register') }}" class="underline font-medium">
                        Create an account
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection