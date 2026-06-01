@extends('layouts.company')

@section('title', 'Add user')

@section('content')
@php
    $backUrl = \Illuminate\Support\Facades\Route::has('admin.users.index')
        ? route('admin.users.index')
        : url()->previous();
@endphp

<div class="max-w-xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Add user</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Create a new customer or staff member, and assign roles.
            </p>
        </div>

        <a href="{{ $backUrl }}"
           class="rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-xs hover:bg-gray-50 dark:hover:bg-gray-800">
            Back to users
        </a>
    </div>

    @include('admin.users._form', [
        'action' => route('admin.users.store'),
        'mode' => 'create',
        'user' => null,
        'roles' => $roles ?? collect(),
        'customerType' => $customerType ?? null,
        'backUrl' => $backUrl,
    ])
</div>
@endsection