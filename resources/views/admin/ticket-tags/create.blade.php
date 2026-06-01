@extends('layouts.company')

@section('title', 'New Ticket Tag')

@section('content')
@php
    $backUrl = \Illuminate\Support\Facades\Route::has('admin.ticket-tags.index')
        ? route('admin.ticket-tags.index')
        : url()->previous();
@endphp

<div class="max-w-3xl mx-auto px-4 py-6 space-y-4">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">New Ticket Tag</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Create a tag used to label and filter support tickets.
            </p>
        </div>

        <a href="{{ $backUrl }}"
           class="rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-xs hover:bg-gray-50 dark:hover:bg-gray-800">
            Back
        </a>
    </div>

    @include('admin.ticket-tags._form', [
        'action' => route('admin.ticket-tags.store'),
        'mode' => 'create',
        'tag' => null,
        'backUrl' => $backUrl,
    ])
</div>
@endsection