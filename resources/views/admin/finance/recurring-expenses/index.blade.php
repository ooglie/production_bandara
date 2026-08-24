@extends('layouts.company')

@section('title', 'Recurring expenses')
@section('breadcrumb', 'Admin · Finance · Recurring expenses')

@section('content')
<div class="space-y-4">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Recurring expenses</h1>
            <p class="mt-1 max-w-3xl text-xs leading-5 text-gray-500 dark:text-gray-400">Templates create draft expenses only. The actual amount can be corrected and an authorised accountant must post each draft.</p>
        </div>
        @if ($canManageSettings)
            <div class="flex flex-wrap items-end gap-2 text-xs">
                <a href="{{ route('admin.finance.recurring-expenses.create') }}"
                   class="inline-flex items-center rounded border border-gray-900 bg-gray-900 px-3 py-1.5 text-[11px] font-medium text-white hover:bg-gray-800 dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">
                    + New template
                </a>
                <form method="POST" action="{{ route('admin.finance.recurring-expenses.generate-due') }}" class="flex items-end gap-2">
                    @csrf
                    <div>
                        <label for="through-date" class="block text-[10px] text-gray-500">Generate through</label>
                        <input id="through-date" type="date" name="through_date" value="{{ today()->format('Y-m-d') }}"
                               class="mt-1 rounded border border-gray-300 bg-white px-2 py-1.5 text-xs dark:border-gray-700 dark:bg-gray-950">
                    </div>
                    <button type="submit" class="rounded border border-gray-300 px-3 py-1.5 text-[11px] font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900">Generate due drafts</button>
                </form>
            </div>
        @endif
    </div>

    @include('admin.finance.partials.nav')
    @include('admin.finance.partials.flash')

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr class="text-[11px] uppercase text-gray-500 dark:text-gray-400">
                        <th class="px-3 py-2 text-left font-medium">Template</th>
                        <th class="px-3 py-2 text-left font-medium">Frequency</th>
                        <th class="px-3 py-2 text-left font-medium">Next due</th>
                        <th class="px-3 py-2 text-right font-medium">Expected total</th>
                        <th class="px-3 py-2 text-left font-medium">Status</th>
                        @if ($canManageSettings)<th class="px-3 py-2 text-right font-medium">Actions</th>@endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($templates as $template)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                            <td class="min-w-72 px-3 py-2 align-top">
                                <div class="font-medium text-gray-900 dark:text-gray-50">{{ $template->description }}</div>
                                <div class="mt-0.5 text-[10px] text-gray-500">{{ $template->category?->name }}{{ $template->payee ? ' · '.$template->payee : '' }} · {{ $template->generated_expenses_count }} generated</div>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 align-top text-gray-700 dark:text-gray-300">{{ \App\Models\RecurringExpenseTemplate::frequencies()[$template->frequency] ?? ucfirst($template->frequency) }}</td>
                            <td class="whitespace-nowrap px-3 py-2 align-top text-gray-700 dark:text-gray-300">{{ $template->next_due_date?->format('d M Y') }}</td>
                            <td class="whitespace-nowrap px-3 py-2 text-right align-top font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format((float) $template->expected_total_amount, 2) }}</td>
                            <td class="whitespace-nowrap px-3 py-2 align-top">
                                @if ($template->is_active)
                                    <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300">Active</span>
                                @else
                                    <span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 text-[10px] text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">Inactive</span>
                                @endif
                            </td>
                            @if ($canManageSettings)
                                <td class="whitespace-nowrap px-3 py-2 text-right align-top">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('admin.finance.recurring-expenses.edit', $template) }}" class="text-[11px] text-gray-700 hover:underline dark:text-gray-300">Edit</a>
                                        <form method="POST" action="{{ route('admin.finance.recurring-expenses.destroy', $template) }}" onsubmit="return confirm('Delete or deactivate this recurring template?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-[11px] text-red-600 hover:underline dark:text-red-400">{{ $template->generated_expenses_count ? 'Deactivate' : 'Delete' }}</button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canManageSettings ? 6 : 5 }}" class="px-3 py-8 text-center text-xs text-gray-500 dark:text-gray-400">No recurring templates exist.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($templates->hasPages())
            <div class="border-t border-gray-100 px-3 py-2 dark:border-gray-800">{{ $templates->links() }}</div>
        @endif
    </div>
</div>
@endsection
