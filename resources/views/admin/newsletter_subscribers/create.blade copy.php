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
                Add a subscriber manually. You can set status + confirmation date.
            </p>
        </div>

        <div class="flex items-center gap-2 text-xs">
            <a href="{{ $indexUrl }}"
               class="rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-800">
                Back
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
            <div class="font-medium mb-1">Please fix the following:</div>
            <ul class="list-disc pl-4 space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $storeUrl }}" class="space-y-4">
        @csrf

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-4">

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]
                                  focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700"
                           placeholder="customer@email.com">
                    @error('email') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Name (optional)</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]
                                  focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700"
                           placeholder="Customer name">
                    @error('name') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Status</label>
                    <select name="status"
                            class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]
                                   focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700">
                        @php $st = old('status', 'active'); @endphp
                        <option value="pending" @selected($st==='pending')>Pending</option>
                        <option value="active" @selected($st==='active')>Active</option>
                        <option value="unsubscribed" @selected($st==='unsubscribed')>Unsubscribed</option>
                        <option value="bounced" @selected($st==='bounced')>Bounced</option>
                    </select>
                    @error('status') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Source (optional)</label>
                    <input type="text" name="source" value="{{ old('source', 'admin') }}"
                           class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]
                                  focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700"
                           placeholder="admin / import / website">
                    @error('source') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Confirmed at (optional)</label>
                    <input type="datetime-local" name="confirmed_at"
                           value="{{ old('confirmed_at') }}"
                           class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]
                                  focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700">
                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        Leave blank to keep unconfirmed (useful when status is pending).
                    </p>
                    @error('confirmed_at') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ $indexUrl }}"
               class="text-[12px] text-gray-600 dark:text-gray-300 hover:underline">
                Cancel
            </a>

            <button type="submit"
                    class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white
                           dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-[12px] font-semibold hover:bg-gray-800 dark:hover:bg-gray-200">
                Save subscriber
            </button>
        </div>
    </form>
</div>
@endsection