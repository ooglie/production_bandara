@extends('layouts.customer')

@section('title', 'Open support ticket')

@section('content')
@php
    $backUrl = \Illuminate\Support\Facades\Route::has('tickets.index') ? route('tickets.index') : url()->previous();
    $storeUrl = \Illuminate\Support\Facades\Route::has('tickets.store') ? route('tickets.store') : '#';
@endphp

<div class="max-w-5xl mx-auto px-4 py-6 space-y-4">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Open a support ticket</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Tell us what you need help with — we’ll get back to you as soon as possible.
            </p>
        </div>

        <a href="{{ $backUrl }}" class="text-[12px] text-gray-500 dark:text-gray-400 hover:underline">
            ← Back to tickets
        </a>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-[12px] text-red-800 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-200">
            <div class="font-medium mb-1">Please fix the following:</div>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $storeUrl }}" enctype="multipart/form-data" class="space-y-4">
        @csrf

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Ticket details</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Category, subject and your message.</div>
            </div>

            <div class="p-5 space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                        <select name="category_id"
                                class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                            <option value="">Select a category…</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Subject</label>
                        <input type="text" name="subject" value="{{ old('subject') }}"
                               class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                               placeholder="e.g. Order issue, invoice request, delivery question">
                        @error('subject')
                            <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Describe your issue</label>
                    <textarea name="message" rows="6"
                              class="w-full rounded-2xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                              placeholder="Include order number, product name, and what went wrong…">{{ old('message') }}</textarea>
                    @error('message')
                        <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 p-4">
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Attachments (optional)</label>
                    <input type="file" name="attachments[]" multiple class="w-full text-[12px] text-gray-600 dark:text-gray-300">
                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        Up to 5MB per file.
                    </p>
                    @error('attachments.*')
                        <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <a href="{{ $backUrl }}" class="text-[12px] text-gray-500 dark:text-gray-400 hover:underline">
                    Cancel
                </a>

                <button type="submit"
                        class="inline-flex items-center rounded-xl border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-5 py-2 text-[12px] font-semibold hover:bg-gray-800 dark:hover:bg-gray-200">
                    Submit ticket
                </button>
            </div>
        </div>
    </form>

    {{-- Optional: show recent tickets below (only if $tickets exists) --}}
    @if(isset($tickets) && is_iterable($tickets))
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Recent tickets</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Your latest requests.</div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-[12px]">
                    <thead class="bg-gray-50 dark:bg-gray-950/40 border-b border-gray-200 dark:border-gray-800">
                    <tr class="text-left text-gray-600 dark:text-gray-300">
                        <th class="px-4 py-3 font-medium">Ticket</th>
                        <th class="px-4 py-3 font-medium">Category</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Updated</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($tickets as $t)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-950/30">
                            <td class="px-4 py-3">
                                @if(\Illuminate\Support\Facades\Route::has('tickets.show'))
                                    <a href="{{ route('tickets.show', $t) }}" class="font-semibold text-gray-900 dark:text-gray-50 hover:underline">
                                        {{ $t->ticket_number ?? ('#'.$t->id) }}
                                    </a>
                                @else
                                    {{ $t->ticket_number ?? ('#'.$t->id) }}
                                @endif
                                <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ $t->subject ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                {{ $t->category?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-2.5 py-1 text-[11px]">
                                    {{ ucfirst(str_replace('_',' ', (string)($t->status ?? 'open'))) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                {{ optional($t->updated_at)->diffForHumans() }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No tickets yet.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if(is_object($tickets) && method_exists($tickets, 'links'))
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800">
                    {{ $tickets->withQueryString()->links() }}
                </div>
            @endif
        </div>
    @endif

</div>
@endsection