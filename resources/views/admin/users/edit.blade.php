@extends('layouts.company')

@section('title', 'Edit user')

@section('content')
@php
    /** @var \App\Models\User $user */

    $backUrl = \Illuminate\Support\Facades\Route::has('admin.users.index')
        ? route('admin.users.index')
        : url()->previous();

    $hasB2BRoutes =
        \Illuminate\Support\Facades\Route::has('admin.b2b.moq.index')
        && \Illuminate\Support\Facades\Route::has('admin.b2b.prices.index');

    $oldCustomerType = old('customer_type', $user->customer_type ?? 'b2c');
    $isB2B = ($oldCustomerType === 'b2b');
@endphp

<div class="max-w-xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Edit user</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Update user details and roles.
            </p>
        </div>

        <a href="{{ $backUrl }}"
           class="rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-xs hover:bg-gray-50 dark:hover:bg-gray-800">
            Back to users
        </a>
    </div>

    {{-- Helpful B2B quick links --}}
    @if($isB2B && $hasB2BRoutes)
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <div class="font-semibold text-gray-900 dark:text-gray-50 text-[12px]">
                        B2B controls
                    </div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400">
                        Manage MOQ and customer pricing for this customer.
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.b2b.moq.index', $user) }}"
                       class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                        MOQ
                    </a>
                    <a href="{{ route('admin.b2b.prices.index', $user) }}"
                       class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Prices
                    </a>
                </div>
            </div>
        </div>
    @endif

    @include('admin.users._form', [
        'action' => route('admin.users.update', $user),
        'mode' => 'edit',
        'user' => $user,
        'roles' => $roles ?? collect(),
        'userRoleNames' => $userRoleNames ?? [],
        'backUrl' => $backUrl,
    ])

    {{-- Delete button (kept same behavior: cannot delete yourself) --}}
    @if($user->id !== auth()->id())
        <div class="pt-2 flex justify-end">
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                  onsubmit="return confirm('Delete this user?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-[11px] text-red-600 dark:text-red-400 underline">
                    Delete user
                </button>
            </form>
        </div>
    @endif
</div>
@endsection