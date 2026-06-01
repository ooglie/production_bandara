@extends('layouts.customer')

@section('title', 'Ticket #' . $ticket->id)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-6 text-xs space-y-4">
    <div class="flex items-center justify-between gap-3 mb-1">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Ticket #{{ $ticket->ticket_number }} – {{ $ticket->subject }}
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Category: {{ $ticket->category?->name ?? '—' }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px]
                @if($ticket->status === 'open') border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-800 dark:bg-sky-900/30 dark:text-sky-200
                @elseif($ticket->status === 'awaiting_customer') border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200
                @elseif($ticket->status === 'resolved') border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200
                @elseif($ticket->status === 'closed') border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400
                @endif">
                {{ str_replace('_',' ', $ticket->status) }}
            </span>

            @if($ticket->status !== 'closed')
                <form method="POST" action="{{ route('tickets.close', $ticket) }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1 text-[10px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Close ticket
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if(session('status'))
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    {{-- Conversation --}}
    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3 space-y-3">
        @foreach($ticket->messages as $message)
            @if($message->is_internal) 
                {{-- internal notes are not shown to customer --}}
                @continue
            @endif
            @php
                $isCustomer = $message->author && $message->author->id === $ticket->user_id;
                $isStaff = $message->author && !$isCustomer;
            @endphp
            <div class="flex flex-col gap-1">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-medium text-gray-900 dark:text-gray-50">
                        @if($isCustomer)
                            You
                        @elseif($isStaff)
                            Support Team
                        @else
                            System
                        @endif
                    </span>
                    <span class="text-[10px] text-gray-500 dark:text-gray-400">
                        {{ $message->created_at->format('d M Y, H:i') }}
                    </span>
                </div>
                <div class="rounded-lg border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 text-[11px] text-gray-800 dark:text-gray-100 whitespace-pre-line">
                    {{ $message->message }}
                </div>

                @if($message->attachments->count())
                    <div class="flex flex-wrap gap-2 mt-1">
                        @foreach($message->attachments as $att)
                            <a href="{{ asset('storage/' . $att->file_path) }}" target="_blank"
                               class="inline-flex items-center rounded-full border border-gray-200 dark:border-gray-700 px-2 py-0.5 text-[10px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                                {{ $att->original_name }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Reply form (if not closed) --}}
    @if($ticket->status !== 'closed')
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3 space-y-2">
            <h2 class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                Reply to support
            </h2>

            <form method="POST" action="{{ route('tickets.reply', $ticket) }}" enctype="multipart/form-data" class="space-y-2">
                @csrf

                <textarea name="message" rows="4"
                          class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">{{ old('message') }}</textarea>
                @error('message')
                    <p class="text-[10px] text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

                <div>
                    <label class="block text-[11px] text-gray-600 dark:text-gray-300 mb-1">
                        Attachments (optional)
                    </label>
                    <input type="file" name="attachments[]" multiple
                           class="w-full text-[11px] text-gray-600 dark:text-gray-300">
                    <p class="mt-1 text-[10px] text-gray-400">
                        Up to 5MB per file.
                    </p>
                    @error('attachments.*')
                        <p class="text-[10px] text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-1 flex items-center justify-between">
                    <a href="{{ route('tickets.index') }}"
                       class="text-[11px] text-gray-500 dark:text-gray-400 hover:underline">
                        Back to tickets
                    </a>
                    <button type="submit"
                            class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                        Send reply
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
@endsection
