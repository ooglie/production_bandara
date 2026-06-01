@extends('layouts.guest')

@section('title', 'Forgot password')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-950 px-4 py-10 flex items-center justify-center">
    <div class="w-full max-w-4xl grid gap-4 lg:grid-cols-[1fr,0.95fr]">

        {{-- Left panel --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-gradient-to-br from-white via-slate-50 to-sky-50 dark:from-gray-900 dark:via-gray-900 dark:to-slate-900 p-6 sm:p-8 flex flex-col justify-between min-h-[260px]">
            <div class="space-y-3">
                <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white/90 dark:bg-gray-950/50 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.14em] text-gray-600 dark:text-gray-300">
                    Account recovery
                </span>

                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-50 leading-tight">
                    Forgot your password?
                </h1>

                <p class="max-w-md text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                    Enter your email and we’ll send you a secure reset link so you can regain access to your account.
                </p>
            </div>

            <div class="mt-6 rounded-sm border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-950/40 px-4 py-3">
                <div class="text-[10px] uppercase tracking-wide text-gray-400">Helpful tip</div>
                <div class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-50">
                    Use the same email address you used when registering.
                </div>
            </div>
        </div>

        {{-- Right card --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 sm:p-8 space-y-5">
            <div class="space-y-1">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                    Send reset link
                </h2>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                    We’ll email you instructions to reset your password.
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

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
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
                </div>

                <button
                    type="submit"
                    class="w-full inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2.5 text-xs font-medium hover:bg-gray-800 dark:hover:bg-gray-200"
                >
                    Send reset link
                </button>
            </form>

            <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3 text-[11px] text-gray-600 dark:text-gray-300">
                Remembered your password?
                <a href="{{ route('login') }}" class="underline font-medium">
                    Back to sign in
                </a>
            </div>
        </div>
    </div>
</div>
@endsection