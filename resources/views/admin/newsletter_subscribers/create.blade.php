@extends('layouts.company')

@section('title', 'Add newsletter subscriber')

@section('content')
@php
    $has = fn(string $r) => \Illuminate\Support\Facades\Route::has($r);

    $indexUrl = $has('admin.newsletter-subscribers.index') ? route('admin.newsletter-subscribers.index') : url()->previous();
    $storeUrl = $has('admin.newsletter-subscribers.store') ? route('admin.newsletter-subscribers.store') : '#';
@endphp

<div class="max-w-3xl mx-auto px-4 py-6 space-y-4">

    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Add subscriber</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Add a subscriber manually.
            </p>
        </div>

        <a href="{{ $indexUrl }}"
           class="rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-xs hover:bg-gray-50 dark:hover:bg-gray-800">
            Back
        </a>
    </div>

    @if(session('status'))
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @include('admin.newsletter_subscribers._form', [
        'action' => $storeUrl,
        'subscriber' => null,
        'backUrl' => $indexUrl,
    ])
</div>
@endsection