@extends('layouts.customer')

@section('title', 'Newsletter preferences')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-6 space-y-4">
    <div>
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
            Newsletter preferences
        </h1>
        <p class="text-[11px] text-gray-500 dark:text-gray-400">
            Stay updated with new products, special offers, and seasonal collections.
        </p>
    </div>

    @if(session('status'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-[12px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 space-y-4">
        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Subscribed email</div>
            <div class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-50">
                {{ $user->email }}
            </div>
        </div>

        <div class="text-[12px] text-gray-600 dark:text-gray-300 leading-relaxed">
            Clicking subscribe will use your account email for newsletter updates. If your email is already subscribed,
            this simply keeps things aligned and lets you confirm your interest again if needed.
        </div>

        <form method="POST" action="{{ route('newsletter.subscribe') }}" class="space-y-3">
            @csrf
            <input type="hidden" name="email" value="{{ $user->email }}">

            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-[12px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200"
            >
                Subscribe with this email
            </button>
        </form>

        <div class="text-[11px] text-gray-500 dark:text-gray-400">
            If you want a true subscribe/unsubscribe status page later, I’d wire this directly to your subscriber table and show current status here.
        </div>
    </div>
</div>
@endsection