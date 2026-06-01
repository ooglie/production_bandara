@extends('layouts.customer')

@section('title', 'Create account')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8 sm:py-10">
    <div class="grid gap-4 lg:grid-cols-[1.05fr,0.95fr] items-stretch">

        {{-- Left intro panel --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-gradient-to-br from-white via-blue-50 to-gray-50 dark:from-gray-900 dark:via-gray-900 dark:to-slate-900 p-6 sm:p-8 flex flex-col justify-between min-h-[320px]">
            <div class="space-y-4">
                <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white/90 dark:bg-gray-950/50 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.14em] text-gray-600 dark:text-gray-300">
                    Frozen • Bandara by Maytira
                </span>

                <div class="space-y-2">
                    <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-gray-900 dark:text-gray-50 leading-tight">
                        Create your account
                    </h1>

                    <p class="max-w-md text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                        Register once to place orders, save favourites, access invoices, and get support more easily.
                    </p>
                </div>
            </div>

            <div class="mt-6 grid gap-3 sm:grid-cols-3">
                <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-950/40 px-4 py-3">
                    <div class="text-[10px] uppercase tracking-wide text-gray-400">Faster checkout</div>
                    <div class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-50">Save your details</div>
                </div>
                <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-950/40 px-4 py-3">
                    <div class="text-[10px] uppercase tracking-wide text-gray-400">Easy access</div>
                    <div class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-50">Orders & invoices</div>
                </div>
                <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-950/40 px-4 py-3">
                    <div class="text-[10px] uppercase tracking-wide text-gray-400">Support ready</div>
                    <div class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-50">Track help requests</div>
                </div>
            </div>
        </div>

        {{-- Right form card --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 sm:p-8 space-y-5">
            {{-- <div class="space-y-1">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                    Create a new account
                </h2>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                    Enter your details to start ordering.
                </p>
            </div> --}}

            @if($errors->any())
                <div class="rounded-sm border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
                    <ul class="list-disc pl-4 space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Full name
                    </label>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        autofocus
                        class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                        placeholder="Your full name"
                    >
                    @error('name')
                        <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Email
                    </label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                        placeholder="you@example.com"
                    >
                    @error('email')
                        <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Mobile number
                    </label>
                    <input
                        type="text"
                        name="phone"
                        value="{{ old('phone') }}"
                        required
                        class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                        placeholder="Your mobile number"
                    >
                    @error('phone')
                        <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Password
                    </label>
                    <input
                        type="password"
                        name="password"
                        required
                        class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                        placeholder="Create a password"
                    >
                    @error('password')
                        <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Confirm password
                    </label>
                    <input
                        type="password"
                        name="password_confirmation"
                        required
                        class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                        placeholder="Re-enter password"
                    >
                </div>

                <button
                    type="submit"
                    class="w-full inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2.5 text-xs font-medium hover:bg-gray-800 dark:hover:bg-gray-200"
                >
                    Create account
                </button>
            </form>

            <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3 text-[11px] text-gray-600 dark:text-gray-300">
                Already have an account?
                <a href="{{ route('login') }}" class="underline font-medium">
                    Sign in
                </a>
            </div>
        </div>
    </div>
</div>
@endsection