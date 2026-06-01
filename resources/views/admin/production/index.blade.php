@extends('layouts.company')

@section('title', 'Production / Repack')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">Production / Repack</h1>
            <p class="text-[12px] text-gray-500 dark:text-gray-400">
                Raw → Slab, Slab → Slice, and Raw → Slice Direct runs.
            </p>
        </div>

        <a href="{{ route('admin.production.create') }}"
           class="inline-flex items-center rounded-xl border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-[12px] font-semibold hover:bg-gray-800 dark:hover:bg-gray-200">
            New run
        </a>
    </div>

    @if(session('status'))
        <div class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-[12px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-[12px]">
                <thead class="bg-gray-50 dark:bg-gray-950/40">
                    <tr class="text-left text-gray-500 dark:text-gray-400">
                        <th class="px-4 py-3 font-medium">Run</th>
                        <th class="px-4 py-3 font-medium">Date</th>
                        <th class="px-4 py-3 font-medium">Type</th>
                        <th class="px-4 py-3 font-medium">Inputs</th>
                        <th class="px-4 py-3 font-medium">Outputs</th>
                        <th class="px-4 py-3 font-medium">Yield</th>
                        <th class="px-4 py-3 font-medium text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($runs as $run)
                        <tr>
                            <td class="px-4 py-3 text-gray-900 dark:text-gray-50 font-medium">
                                {{ $run->run_number ?? ('Run #' . $run->id) }}
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                {{ $run->run_date ? $run->run_date->format('d M Y') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                {{ str_replace('_', ' ', ucfirst($run->run_type)) }}
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                {{ $run->inputs_count }}
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                {{ $run->outputs_count }}
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                {{ $run->yield_percent !== null ? number_format((float) $run->yield_percent, 2) . '%' : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.production.show', $run) }}"
                                   class="text-[11px] text-gray-700 dark:text-gray-200 underline">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No production runs yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $runs->links() }}
</div>
@endsection