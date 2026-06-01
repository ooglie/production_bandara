@php
    /** @var \App\Models\NewsletterSubscriber|null $subscriber */

    $subscriber = $subscriber ?? null;
    $isEdit = $subscriber && method_exists($subscriber, 'exists') && $subscriber->exists;

    $action = $action ?? '#';
    $backUrl = $backUrl ?? (Route::has('admin.newsletter-subscribers.index') ? route('admin.newsletter-subscribers.index') : url()->previous());

    // Defaults
    $defaultStatus = $isEdit ? ($subscriber->status ?? 'active') : 'active';

    // Normalize confirmed_at for datetime-local input
    $confirmedAtValue = old('confirmed_at');
    if ($confirmedAtValue === null && $isEdit && !empty($subscriber->confirmed_at)) {
        try {
            $confirmedAtValue = optional($subscriber->confirmed_at)->format('Y-m-d\TH:i');
        } catch (\Throwable $e) {
            $confirmedAtValue = '';
        }
    }
@endphp

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

<form method="POST" action="{{ $action }}" class="space-y-4">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-4">

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Email</label>
                <input type="email" name="email" value="{{ old('email', $subscriber->email ?? '') }}" required
                       class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]
                              focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700"
                       placeholder="customer@email.com">
                @error('email') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Name (optional)</label>
                <input type="text" name="name" value="{{ old('name', $subscriber->name ?? '') }}"
                       class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]
                              focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700"
                       placeholder="Customer name">
                @error('name') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Status</label>
                @php $st = old('status', $defaultStatus); @endphp
                <select name="status"
                        class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]
                               focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700">
                    <option value="pending" @selected($st==='pending')>Pending</option>
                    <option value="active" @selected($st==='active')>Active</option>
                    <option value="unsubscribed" @selected($st==='unsubscribed')>Unsubscribed</option>
                    <option value="bounced" @selected($st==='bounced')>Bounced</option>
                </select>
                @error('status') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Source (optional)</label>
                <input type="text" name="source" value="{{ old('source', $subscriber->source ?? 'admin') }}"
                       class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]
                              focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700"
                       placeholder="admin / import / website">
                @error('source') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Confirmed at (optional)</label>
                <input type="datetime-local" name="confirmed_at"
                       value="{{ $confirmedAtValue }}"
                       class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]
                              focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700">
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                    Leave blank to keep unconfirmed (useful for pending).
                </p>
                @error('confirmed_at') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between">
        <a href="{{ $backUrl }}"
           class="text-[12px] text-gray-600 dark:text-gray-300 hover:underline">
            Cancel
        </a>

        <button type="submit"
                class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white
                       dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-[12px] font-semibold hover:bg-gray-800 dark:hover:bg-gray-200">
            {{ $isEdit ? 'Update subscriber' : 'Save subscriber' }}
        </button>
    </div>
</form>