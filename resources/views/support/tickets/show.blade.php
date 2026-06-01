@extends('layouts.company')

@section('title', 'Ticket #' . ($ticket->id ?? ''))

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-4 text-xs">

    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Ticket #{{ $ticket->id }}
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                {{ $ticket->subject ?? $ticket->title ?? 'Support ticket' }}
            </p>
        </div>

        <a href="{{ route('support.tickets.index') }}"
           class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
            Back
        </a>
    </div>

    @if(session('status'))
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-3">
        {{-- Left: ticket info --}}
        <div class="lg:col-span-1 space-y-3">
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-2">
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Customer</div>
                <div class="text-[12px] font-medium text-gray-900 dark:text-gray-50">
                    {{ $ticket->customer?->name ?? $ticket->customer_email ?? '—' }}
                </div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">
                    {{ $ticket->customer?->email ?? '—' }}
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-2">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-[11px] text-gray-500 dark:text-gray-400">Status</div>
                        <div class="text-[12px] font-medium text-gray-900 dark:text-gray-50">
                            {{ ucfirst(str_replace('_',' ', $ticket->status ?? 'open')) }}
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-[11px] text-gray-500 dark:text-gray-400">Assigned</div>
                        <div class="text-[12px] font-medium text-gray-900 dark:text-gray-50">
                            {{ $ticket->assignee?->name ?? '—' }}
                        </div>
                    </div>
                </div>

                {{-- Status update --}}
                <form method="POST" action="{{ route('support.tickets.status', $ticket) }}" class="pt-2 space-y-2">
                    @csrf
                    <label class="block text-[10px] text-gray-600 dark:text-gray-300">Change status</label>
                    <select name="status"
                            class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-[11px]">
                        @foreach(['open','awaiting_customer','awaiting_agent','resolved','closed'] as $st)
                            <option value="{{ $st }}" @selected(($ticket->status ?? 'open') === $st)>
                                {{ ucfirst(str_replace('_',' ', $st)) }}
                            </option>
                        @endforeach
                    </select>
                    <button class="w-full inline-flex items-center justify-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                        Update
                    </button>
                </form>

                {{-- Assign to me --}}
                <form method="POST" action="{{ route('support.tickets.assignToMe', $ticket) }}" class="pt-2">
                    @csrf
                    <button class="w-full inline-flex items-center justify-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] hover:bg-gray-100 dark:hover:bg-gray-800">
                        Assign to me
                    </button>
                </form>
            </div>

            {{-- Tags --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-2">
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Tags</div>

                <form method="POST" action="{{ route('support.tickets.tags', $ticket) }}" class="space-y-2">
                    @csrf
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach(($allTags ?? []) as $tag)
                            <label class="inline-flex items-center gap-2 text-[11px] text-gray-700 dark:text-gray-200">
                                <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                                       @checked($ticket->tags?->contains('id', $tag->id))>
                                <span>{{ $tag->name }}</span>
                            </label>
                        @endforeach
                    </div>

                    <button class="w-full inline-flex items-center justify-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] hover:bg-gray-100 dark:hover:bg-gray-800">
                        Save tags
                    </button>
                </form>
            </div>
        </div>

        {{-- Right: messages --}}
        <div class="lg:col-span-2 space-y-3">

            {{-- Reply --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-2">
                <div class="text-[12px] font-semibold text-gray-900 dark:text-gray-50">Reply to customer</div>

                <form method="POST" action="{{ route('support.tickets.reply', $ticket) }}" enctype="multipart/form-data" class="space-y-2">
                    @csrf
                    <textarea name="message" rows="4" required
                              class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]"
                              placeholder="Type your reply...">{{ old('message') }}</textarea>

                    <input type="file" name="attachments[]" multiple
                           class="w-full text-[11px] text-gray-600 dark:text-gray-300">

                    <button class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                        Send reply
                    </button>
                </form>
            </div>


            {{-- Internal note --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-2">
                <div class="text-[12px] font-semibold text-gray-900 dark:text-gray-50">Internal note</div>

                <form method="POST" action="{{ route('support.tickets.note', $ticket) }}" class="space-y-2">
                    @csrf
                    <textarea name="message" rows="3" required
                              class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]"
                              placeholder="Internal note (only staff can see)...">{{ old('message') }}</textarea>

                    <button class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-4 py-1.5 text-[11px] hover:bg-gray-100 dark:hover:bg-gray-800">
                        Add note
                    </button>
                </form>
            </div>
            
            {{-- Conversation --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-3">
                <div class="text-[12px] font-semibold text-gray-900 dark:text-gray-50">Conversation</div>

                <div class="space-y-3">

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
                            $name = $isCustomer ? $message->author->name : ($isStaff ? 'Support Team' : 'System');
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
    </div>
</div>
@endsection