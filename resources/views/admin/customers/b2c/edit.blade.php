@extends('layouts.company')

@section('title', 'Edit B2C Customer')

@section('content')
@php
    /** @var \App\Models\User $user */

    $has = fn($r) => \Illuminate\Support\Facades\Route::has($r);

    $updateUrl =
        $has('admin.customers.b2c.update') ? route('admin.customers.b2c.update', $user)
        : ($has('admin.users.update') ? route('admin.users.update', $user) : null);

    $backUrl =
        $has('admin.customers.b2c.index') ? route('admin.customers.b2c.index')
        : ($has('admin.users.index') ? route('admin.users.index', ['customer_type' => 'b2c']) : url()->previous());

    $destroyUrl =
        $has('admin.customers.b2c.destroy') ? route('admin.customers.b2c.destroy', $user)
        : ($has('admin.users.destroy') ? route('admin.users.destroy', $user) : null);

    $canDelete = $destroyUrl && ($user->id !== auth()->id());
@endphp

<div class="max-w-2xl mx-auto px-4 py-6 space-y-4">

    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Edit B2C Customer</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Update customer details. Customer role stays <strong>Customer</strong>.
            </p>
        </div>

        <a href="{{ $backUrl }}"
           class="rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-xs hover:bg-gray-50 dark:hover:bg-gray-800">
            Back
        </a>
    </div>

    @if(!$updateUrl)
        <div class="rounded border border-yellow-300 bg-yellow-50 px-3 py-2 text-[11px] text-yellow-800">
            Update route not found. Expected <code>admin.customers.b2c.update</code> or <code>admin.users.update</code>.
        </div>
    @endif

    @include('admin.customers.b2c._form', [
        'action' => $updateUrl ?? '#',
        'mode' => 'edit',
        'user' => $user,
        'backUrl' => $backUrl,
    ])

    {{-- Delete (outside form; no nesting) --}}
    @if($canDelete)
        <div class="pt-1 flex justify-end">
            <form method="POST" action="{{ $destroyUrl }}" onsubmit="return confirm('Delete this customer?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-[11px] text-red-600 dark:text-red-400 underline">
                    Delete
                </button>
            </form>
        </div>
    @endif

</div>
@endsection