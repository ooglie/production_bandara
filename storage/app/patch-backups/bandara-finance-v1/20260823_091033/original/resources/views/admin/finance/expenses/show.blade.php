<x-layouts.admin title="Expense {{ $expense->expense_number }}" heading="Business expense">
    @include('admin.finance.partials.flash')
    @include('admin.finance.partials.nav')

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-2xl font-semibold text-slate-950 dark:text-white">{{ $expense->expense_number }}</h2>
                    <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ \App\Models\BusinessExpense::recordStatuses()[$expense->record_status] ?? ucfirst($expense->record_status) }}</span>
                    <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ \App\Models\BusinessExpense::paymentStatuses()[$expense->payment_status] ?? ucfirst($expense->payment_status) }}</span>
                </div>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $expense->expense_date?->format('d F Y') }} · {{ $expense->category?->name }}</p>
            </div>

            <div class="flex flex-wrap gap-2">
                @if ($canManage && $expense->isDraft())
                    <a href="{{ route('admin.finance.expenses.edit', $expense) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 dark:border-slate-700 dark:text-slate-200">Edit draft</a>
                @endif
                @if ($canPost && $expense->isDraft())
                    <form method="POST" action="{{ route('admin.finance.expenses.post', $expense) }}" onsubmit="return confirm('Post this expense? It will enter the operating summary and become non-editable.')">
                        @csrf
                        <button type="submit" class="rounded-lg bg-slate-950 px-3 py-2 text-sm font-medium text-white dark:bg-slate-100 dark:text-slate-950">Post expense</button>
                    </form>
                @endif
                @if ($canPost && $expense->isPosted())
                    <form method="POST" action="{{ route('admin.finance.expenses.void', $expense) }}" onsubmit="return confirm('Void this posted expense? The audit record will remain.')">
                        @csrf
                        <button type="submit" class="rounded-lg border border-rose-300 px-3 py-2 text-sm font-medium text-rose-700 dark:border-rose-800 dark:text-rose-300">Void</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg bg-slate-50 p-4 dark:bg-slate-800/60">
                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Taxable</p>
                <p class="mt-2 text-xl font-semibold text-slate-950 dark:text-white">₹{{ number_format((float) $expense->taxable_amount, 2) }}</p>
            </div>
            <div class="rounded-lg bg-slate-50 p-4 dark:bg-slate-800/60">
                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">GST</p>
                <p class="mt-2 text-xl font-semibold text-slate-950 dark:text-white">₹{{ number_format((float) $expense->gst_amount, 2) }}</p>
            </div>
            <div class="rounded-lg bg-slate-950 p-4 text-white dark:bg-slate-100 dark:text-slate-950">
                <p class="text-xs uppercase tracking-wide text-slate-300 dark:text-slate-600">Total</p>
                <p class="mt-2 text-xl font-semibold">₹{{ number_format((float) $expense->total_amount, 2) }}</p>
            </div>
            <div class="rounded-lg bg-slate-50 p-4 dark:bg-slate-800/60">
                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Payee</p>
                <p class="mt-2 text-sm font-medium text-slate-950 dark:text-white">{{ $expense->payee ?: '—' }}</p>
            </div>
        </div>

        <dl class="mt-6 grid gap-4 border-t border-slate-200 pt-5 text-sm dark:border-slate-800 md:grid-cols-2 xl:grid-cols-3">
            <div><dt class="text-slate-500 dark:text-slate-400">Description</dt><dd class="mt-1 font-medium text-slate-900 dark:text-white">{{ $expense->description }}</dd></div>
            <div><dt class="text-slate-500 dark:text-slate-400">Due date</dt><dd class="mt-1 font-medium text-slate-900 dark:text-white">{{ $expense->due_date?->format('d M Y') ?: '—' }}</dd></div>
            <div><dt class="text-slate-500 dark:text-slate-400">Paid date</dt><dd class="mt-1 font-medium text-slate-900 dark:text-white">{{ $expense->paid_date?->format('d M Y') ?: '—' }}</dd></div>
            <div><dt class="text-slate-500 dark:text-slate-400">Payment method</dt><dd class="mt-1 font-medium text-slate-900 dark:text-white">{{ $paymentMethods[$expense->payment_method] ?? ($expense->payment_method ?: '—') }}</dd></div>
            <div><dt class="text-slate-500 dark:text-slate-400">Payment reference</dt><dd class="mt-1 font-medium text-slate-900 dark:text-white">{{ $expense->payment_reference ?: '—' }}</dd></div>
            <div><dt class="text-slate-500 dark:text-slate-400">Created by</dt><dd class="mt-1 font-medium text-slate-900 dark:text-white">{{ $expense->createdBy?->name ?: 'System' }}</dd></div>
            @if ($expense->posted_at)
                <div><dt class="text-slate-500 dark:text-slate-400">Posted</dt><dd class="mt-1 font-medium text-slate-900 dark:text-white">{{ $expense->posted_at->format('d M Y, H:i') }} by {{ $expense->postedBy?->name ?: 'System' }}</dd></div>
            @endif
            @if ($expense->recurringTemplate)
                <div><dt class="text-slate-500 dark:text-slate-400">Recurring source</dt><dd class="mt-1 font-medium text-slate-900 dark:text-white">{{ $expense->recurringTemplate->description }}</dd></div>
            @endif
            @if ($expense->receipt_path)
                <div><dt class="text-slate-500 dark:text-slate-400">Receipt</dt><dd class="mt-1"><a href="{{ route('admin.finance.expenses.attachment', $expense) }}" class="font-medium text-slate-950 underline dark:text-white">Download {{ $expense->receipt_original_name }}</a></dd></div>
            @endif
        </dl>

        @if ($expense->notes)
            <div class="mt-5 rounded-lg bg-slate-50 p-4 text-sm leading-6 text-slate-700 dark:bg-slate-800/60 dark:text-slate-200">
                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Notes</p>
                {!! nl2br(e($expense->notes)) !!}
            </div>
        @endif
    </section>

    @if ($canPost && $expense->isPosted())
        <section class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h3 class="text-lg font-semibold text-slate-950 dark:text-white">Update payment details</h3>
            <form method="POST" action="{{ route('admin.finance.expenses.payment.update', $expense) }}" class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @csrf
                @method('PUT')
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Payment status</span>
                    <select name="payment_status" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        @foreach (\App\Models\BusinessExpense::paymentStatuses() as $value => $label)
                            <option value="{{ $value }}" @selected($expense->payment_status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Method</span>
                    <select name="payment_method" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        <option value="">Select</option>
                        @foreach ($paymentMethods as $value => $label)
                            <option value="{{ $value }}" @selected($expense->payment_method === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Reference</span>
                    <input name="payment_reference" value="{{ $expense->payment_reference }}" maxlength="255" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Paid date</span>
                    <input type="date" name="paid_date" value="{{ $expense->paid_date?->format('Y-m-d') }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
                <div class="md:col-span-2 xl:col-span-4">
                    <button type="submit" class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white dark:bg-slate-100 dark:text-slate-950">Save payment details</button>
                </div>
            </form>
        </section>
    @endif

    @if ($canManage && $expense->isDraft())
        <div class="mt-6 flex justify-end">
            <form method="POST" action="{{ route('admin.finance.expenses.destroy', $expense) }}" onsubmit="return confirm('Delete this draft expense?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm font-medium text-rose-600 hover:text-rose-700 dark:text-rose-400">Delete draft</button>
            </form>
        </div>
    @endif
</x-layouts.admin>
