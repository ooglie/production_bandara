<x-layouts.admin title="Recurring expenses" heading="Recurring expenses">
    @include('admin.finance.partials.flash')
    @include('admin.finance.partials.nav')

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-950 dark:text-white">Recurring templates</h2>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">Templates generate draft expenses only. Actual amounts can be corrected and an authorised accountant must post each expense before it enters the operating summary.</p>
            </div>
            @if ($canManageSettings)
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.finance.recurring-expenses.create') }}" class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white dark:bg-slate-100 dark:text-slate-950">New template</a>
                    <form method="POST" action="{{ route('admin.finance.recurring-expenses.generate-due') }}" class="flex items-center gap-2">
                        @csrf
                        <input type="date" name="through_date" value="{{ today()->format('Y-m-d') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        <button type="submit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-700 dark:text-slate-200">Generate due drafts</button>
                    </form>
                </div>
            @endif
        </div>
    </section>

    <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                <thead class="bg-slate-50 dark:bg-slate-800/60">
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        <th class="px-4 py-3 font-medium">Template</th>
                        <th class="px-4 py-3 font-medium">Frequency</th>
                        <th class="px-4 py-3 font-medium">Next due</th>
                        <th class="px-4 py-3 text-right font-medium">Expected total</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        @if ($canManageSettings)<th class="px-4 py-3 text-right font-medium">Actions</th>@endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($templates as $template)
                        <tr>
                            <td class="min-w-72 px-4 py-3 align-top">
                                <p class="font-medium text-slate-950 dark:text-white">{{ $template->description }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $template->category?->name }}{{ $template->payee ? ' · '.$template->payee : '' }} · {{ $template->generated_expenses_count }} generated occurrence(s)</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 align-top text-slate-700 dark:text-slate-300">{{ \App\Models\RecurringExpenseTemplate::frequencies()[$template->frequency] ?? ucfirst($template->frequency) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 align-top text-slate-700 dark:text-slate-300">{{ $template->next_due_date?->format('d M Y') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right align-top font-semibold text-slate-950 dark:text-white">₹{{ number_format((float) $template->expected_total_amount, 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 align-top"><span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $template->is_active ? 'Active' : 'Inactive' }}</span></td>
                            @if ($canManageSettings)
                                <td class="whitespace-nowrap px-4 py-3 text-right align-top">
                                    <div class="flex justify-end gap-3">
                                        <a href="{{ route('admin.finance.recurring-expenses.edit', $template) }}" class="font-medium text-slate-700 hover:underline dark:text-slate-200">Edit</a>
                                        <form method="POST" action="{{ route('admin.finance.recurring-expenses.destroy', $template) }}" onsubmit="return confirm('Delete or deactivate this recurring template?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-rose-600 dark:text-rose-400">{{ $template->generated_expenses_count ? 'Deactivate' : 'Delete' }}</button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canManageSettings ? 6 : 5 }}" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">No recurring templates exist.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($templates->hasPages())
            <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">{{ $templates->links() }}</div>
        @endif
    </section>
</x-layouts.admin>
