@extends('layouts.customer')

@section('title', 'Ticket #' . ($ticket->ticket_number ?? $ticket->id))

@section('content')
@php
    $backUrl = \Illuminate\Support\Facades\Route::has('tickets.index') ? route('tickets.index') : url()->previous();

    $status = (string)($ticket->status ?? 'open');

    $badgeClass = function ($st) {
        return match ((string)$st) {
            'open' => 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-800 dark:bg-sky-900/30 dark:text-sky-200',
            'awaiting_customer' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200',
            'awaiting_agent' => 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-200',
            'resolved' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200',
            'closed' => 'border-gray-200 bg-gray-50 text-gray-600 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-300',
            default => 'border-gray-200 bg-gray-50 text-gray-600 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-300',
        };
    };

    $statusLabel = ucfirst(str_replace('_', ' ', $status));
@endphp

<div class="max-w-5xl mx-auto px-4 py-6 space-y-4">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <div class="text-[11px] text-gray-500 dark:text-gray-400">
                Ticket {{ $ticket->ticket_number ?? ('#' . $ticket->id) }}
            </div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                {{ $ticket->subject ?? 'Support ticket' }}
            </h1>
            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                Category: {{ $ticket->category?->name ?? '—' }}
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] {{ $badgeClass($status) }}">
                {{ $statusLabel }}
            </span>

            @if($status !== 'closed')
                <form method="POST" action="{{ route('tickets.close', $ticket) }}"
                    onsubmit="return confirm('Close this ticket? You can reopen it later if needed.');">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center rounded-xl border border-gray-300 dark:border-gray-700 px-3 py-2 text-[12px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Close ticket
                    </button>
                </form>
            @else
                @if(\Illuminate\Support\Facades\Route::has('tickets.reopen'))
                    <form method="POST" action="{{ route('tickets.reopen', $ticket) }}"
                        onsubmit="return confirm('Reopen this ticket?');">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center rounded-xl border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-2 text-[12px] font-semibold hover:bg-gray-800 dark:hover:bg-gray-200">
                            Reopen ticket
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>

    <div class="flex items-center justify-between">
        <a href="{{ $backUrl }}" class="text-[12px] text-gray-500 dark:text-gray-400 hover:underline">
            ← Back to tickets
        </a>
    </div>

    @if(session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-[12px] text-emerald-800 dark:border-emerald-900/40 dark:bg-emerald-900/20 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    {{-- Reply (if not closed) --}}
    @if($status !== 'closed' && \Illuminate\Support\Facades\Route::has('tickets.reply'))
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Reply</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Send a message to the support team.</div>
            </div>

            <form method="POST" action="{{ route('tickets.reply', $ticket) }}" enctype="multipart/form-data" class="p-5 space-y-3">
                @csrf

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Message</label>
                    <textarea name="message" rows="5"
                              class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">{{ old('message') }}</textarea>
                    @error('message')
                        <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Attachments (optional)</label>
                    <input type="file" name="attachments[]" multiple class="w-full text-[12px] text-gray-600 dark:text-gray-300">
                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Up to 5MB per file.</p>
                    @error('attachments.*')
                        <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-1 flex items-center justify-end gap-2">
                    <a href="{{ $backUrl }}"
                       class="inline-flex items-center rounded-xl border border-gray-300 dark:border-gray-700 px-4 py-2 text-[12px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center rounded-xl border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-5 py-2 text-[12px] font-semibold hover:bg-gray-800 dark:hover:bg-gray-200">
                        Send reply
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Conversation --}}
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Conversation</div>
            <div class="text-[11px] text-gray-500 dark:text-gray-400">Replies between you and the support team.</div>
        </div>

        <div class="p-5 space-y-4">
            @forelse($ticket->messages as $message)
                @if($message->is_internal)
                    @continue
                @endif

                @php
                    $isCustomer = $message->author && (int)$message->author->id === (int)$ticket->user_id;
                    $isStaff = $message->author && !$isCustomer;

                    $bubble =
                        $isCustomer
                            ? 'border-gray-200 bg-gray-50 text-gray-900 dark:border-gray-800 dark:bg-gray-950/40 dark:text-gray-100'
                            : 'border-sky-200 bg-sky-50 text-gray-900 dark:border-sky-900/50 dark:bg-sky-900/20 dark:text-gray-100';

                    $side = $isCustomer ? 'items-end text-right' : 'items-start text-left';
                    $name = $isCustomer ? 'You' : ($isStaff ? 'Support Team' : 'System');
                    $time = $message->created_at ? $message->created_at->format('d M Y, H:i') : '';
                    $body = $message->message ?? $message->body ?? '';
                @endphp

                <div class="flex flex-col {{ $side }} gap-1">
                    <div class="flex items-center gap-2 text-[11px] text-gray-500 dark:text-gray-400">
                        <span class="font-medium text-gray-700 dark:text-gray-200">{{ $name }}</span>
                        <span>•</span>
                        <span>{{ $time }}</span>
                    </div>

                    <div class="max-w-3xl rounded-2xl border px-4 py-3 text-[13px] whitespace-pre-line {{ $bubble }}">
                        {{ $body }}
                    </div>

                    @if($message->attachments && $message->attachments->count())
                        <div class="flex flex-wrap gap-2 mt-1">
                            @foreach($message->attachments as $att)
                                @php
                                    $filePath = $att->file_path ?? $att->path ?? null;
                                    $fileName = $att->original_name ?? basename((string)$filePath);
                                @endphp
                                @if($filePath)
                                    <a href="{{ asset('storage/' . $filePath) }}" target="_blank"
                                       class="inline-flex items-center rounded-full border border-gray-200 dark:border-gray-700 px-3 py-1 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                                        {{ $fileName }}
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>

            @empty
                <div class="text-center text-[12px] text-gray-500 dark:text-gray-400 py-10">
                    No messages yet.
                </div>
            @endforelse
        </div>
    </div>

    

</div>
@endsection