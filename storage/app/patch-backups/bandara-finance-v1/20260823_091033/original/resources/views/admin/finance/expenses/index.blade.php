<x-layouts.admin title="Business expenses" heading="Business expenses">
    @include('admin.finance.partials.flash')
    @include('admin.finance.partials.nav')

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-950 dark:text-white">Expense register</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Only posted expenses enter the operating summary. Drafts remain editable; posted records can be voided but not deleted.</p>
            </div>
            @if ($canManage)
                <a href="{{ route('admin.finance.expenses.create') }}" class="inline-flex items-center justify-center rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-950">New expense</a>
            @endif
        </div>

        <form method="GET" action="{{ route('admin.finance.expenses.index') }}" class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-6">
            <label class="block">
                <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Month</span>
                <input type="month" name="month" value="{{ $month->format('Y-m') }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
            </label>
            <label class="block xl:col-span-2">
                <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Search</span>
                <input name="search" value="{{ request('search') }}" placeholder="Reference, description, payee" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Category</span>
                <select name="category_id" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    <option value="">All</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Record status</span>
                <select name="record_status" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    <option value="">All</option>
                    @foreach (\App\Models\BusinessExpense::recordStatuses() as $value => $label)
                        <option value="{{ $value }}" @selected(request('record_status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex items-end gap-2">
                <label class="min-w-0 flex-1">
                    <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Payment</span>
                    <select name="payment_status" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        <option value="">All</option>
                        @foreach (\App\Models\BusinessExpense::paymentStatuses() as $value => $label)
                            <option value="{{ $value }}" @selected(request('payment_status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white dark:bg-slate-100 dark:text-slate-950">Filter</button>
            </div>
        </form>
    </section>

    <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                <thead class="bg-slate-50 dark:bg-slate-800/60">
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        <th class="px-4 py-3 font-medium">Date / reference</th>
                        <th class="px-4 py-3 font-medium">Expense</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Taxable</th>
                        <th class="px-4 py-3 text-right font-medium">GST</th>
                        <th class="px-4 py-3 text-right font-medium">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($expenses as $expense)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                            <td class="whitespace-nowrap px-4 py-3 align-top">
                                <a href="{{ route('admin.finance.expenses.show', $expense) }}" class="font-medium text-slate-950 hover:underline dark:text-white">{{ $expense->expense_number }}</a>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $expense->expense_date?->format('d M Y') }}</p>
                            </td>
                            <td class="min-w-64 px-4 py-3 align-top">
                                <p class="font-medium text-slate-900 dark:text-white">{{ $expense->description }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $expense->category?->name }}{{ $expense->payee ? ' · '.$expense->payee : '' }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 align-top">
                                <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ \App\Models\BusinessExpense::recordStatuses()[$expense->record_status] ?? ucfirst($expense->record_status) }}</span>
                                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ \App\Models\BusinessExpense::paymentStatuses()[$expense->payment_status] ?? ucfirst($expense->payment_status) }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right align-top text-slate-700 dark:text-slate-300">₹{{ number_format((float) $expense->taxable_amount, 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right align-top text-slate-700 dark:text-slate-300">₹{{ number_format((float) $expense->gst_amount, 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right align-top font-semibold text-slate-950 dark:text-white">₹{{ number_format((float) $expense->total_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">No expenses match the selected filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($expenses->hasPages())
            <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">{{ $expenses->links() }}</div>
        @endif
    </section>
</x-layouts.admin>
