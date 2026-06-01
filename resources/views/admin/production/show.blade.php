@extends('layouts.company')

@section('title', 'Production Run ' . ($run->run_number ?? ''))

@section('content')
@php
    $inputs = $run->inputs ?? collect();
    $outputs = $run->outputs ?? collect();

    $runTypeLabel = match ($run->run_type) {
        'raw_to_slab' => 'Raw → Slab',
        'slab_to_slice' => 'Slab → Slice',
        'raw_to_slice_direct' => 'Raw → Slice Direct',
        default => str_replace('_', ' ', ucfirst($run->run_type ?? '')),
    };

    $status = $run->status ?? 'draft';

    $statusClass = match ($status) {
        'completed' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200',
        'cancelled' => 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200',
        default => 'border-gray-200 bg-gray-50 text-gray-600 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-300',
    };

    $flowSteps = collect($run->process_flow_json ?? []);

    $fmtQty = fn ($v) => number_format((float) ($v ?? 0), 3);
    $fmtW   = fn ($v) => number_format((float) ($v ?? 0), 3) . ' kg';
    $fmtM   = fn ($v) => '₹' . number_format((float) ($v ?? 0), 2);
@endphp

<div class="max-w-7xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">
                Production Run {{ $run->run_number ?? ('#' . $run->id) }}
            </h1>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] {{ $statusClass }}">
                    {{ ucfirst($status) }}
                </span>
                <span class="inline-flex items-center rounded-full border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950/40 px-2.5 py-1 text-[11px] text-gray-700 dark:text-gray-200">
                    {{ $runTypeLabel }}
                </span>
                @if($run->run_date)
                    <span class="text-[11px] text-gray-500 dark:text-gray-400">
                        {{ $run->run_date->format('d M Y') }}
                    </span>
                @endif
            </div>
        </div>

        <a href="{{ route('admin.production.index') }}"
           class="text-[12px] px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
            Back
        </a>
    </div>

    {{-- Summary --}}
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Input weight</div>
            <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-50">
                {{ $fmtW($run->input_weight_kg) }}
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Saleable output</div>
            <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-50">
                {{ $fmtW($run->saleable_output_weight_kg) }}
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Trim</div>
            <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-50">
                {{ $fmtW($run->trim_weight_kg) }}
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Waste</div>
            <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-50">
                {{ $fmtW($run->waste_weight_kg) }}
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Yield</div>
            <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-50">
                {{ number_format((float) ($run->yield_percent ?? 0), 2) }}%
            </div>
        </div>
    </div>

    {{-- Process flow --}}
    @if($flowSteps->isNotEmpty())
        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                <div class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Process flow</div>
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Steps used in this run</div>
            </div>

            <div class="p-5">
                <div class="flex flex-wrap gap-2">
                    @foreach($flowSteps as $step)
                        @php
                            $label = ucfirst((string) data_get($step, 'step', 'step'));
                            $invOut = (bool) data_get($step, 'inventory_output', false);
                        @endphp
                        <span class="inline-flex items-center rounded-full border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950/40 px-3 py-1.5 text-[11px] text-gray-700 dark:text-gray-200">
                            {{ $label }}
                            <span class="ml-2 text-gray-400">
                                {{ $invOut ? 'stocked' : 'virtual' }}
                            </span>
                        </span>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if(!empty($run->notes))
        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Notes</div>
            <div class="mt-2 text-[12px] text-gray-700 dark:text-gray-200 whitespace-pre-line">
                {{ $run->notes }}
            </div>
        </section>
    @endif

    {{-- Inputs --}}
    <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Input</div>
            <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Consumed lot</div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-[12px]">
                <thead class="bg-gray-50 dark:bg-gray-950/40">
                    <tr class="text-left text-gray-500 dark:text-gray-400">
                        <th class="px-4 py-3 font-medium">Lot</th>
                        <th class="px-4 py-3 font-medium">Product</th>
                        <th class="px-4 py-3 font-medium">Mode</th>
                        <th class="px-4 py-3 font-medium">Consumed qty</th>
                        <th class="px-4 py-3 font-medium">Consumed wt.</th>
                        <th class="px-4 py-3 font-medium">Pieces</th>
                        <th class="px-4 py-3 font-medium text-right">Cost snapshot</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($inputs as $input)
                        @php
                            $lot = $input->inventoryLot;
                            $lotMode = $lot?->inward_mode ? ucfirst($lot->inward_mode) : 'Qty';
                        @endphp
                        <tr>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                <div class="font-medium text-gray-900 dark:text-gray-50">
                                    {{ $lot?->lot_code ?? ('LOT-' . ($input->inventory_lot_id ?? '—')) }}
                                </div>
                                @if($lot?->batch_code)
                                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                        Batch: {{ $lot->batch_code }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                {{ $input->product->name ?? '—' }}
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                {{ $lotMode }}
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                {{ $fmtQty($input->consumed_quantity) }}
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                {{ $fmtW($input->consumed_weight_kg) }}
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                @if(!empty($input->consumed_piece_count))
                                    {{ (int) $input->consumed_piece_count }}
                                @else
                                    —
                                @endif
                            </td>

                            <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-50">
                                {{ $fmtM($input->total_cost_snapshot) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No input rows found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Outputs --}}
    <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Outputs</div>
            <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Produced lots</div>
        </div>

        <div class="space-y-0">
            @forelse($outputs as $output)
                @php
                    $lot = $output->inventoryLot;
                    $mode = ($lot?->inward_mode === 'pieces') ? 'Pieces' : 'Qty';
                    $pieces = $lot?->pieces ?? collect();
                @endphp

                <div class="border-b last:border-b-0 border-gray-100 dark:border-gray-800 p-5">
                    <div class="grid gap-4 xl:grid-cols-[1.2fr,1fr]">
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                                    {{ $output->product->name ?? '—' }}
                                </div>

                                <span class="inline-flex items-center rounded-full border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950/40 px-2 py-1 text-[10px] text-gray-600 dark:text-gray-300">
                                    {{ ucfirst($output->output_stage) }}
                                </span>

                                <span class="inline-flex items-center rounded-full border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950/40 px-2 py-1 text-[10px] text-gray-600 dark:text-gray-300">
                                    Mode: {{ $mode }}
                                </span>

                                @if($lot?->lot_code)
                                    <span class="inline-flex items-center rounded-full border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950/40 px-2 py-1 text-[10px] text-gray-600 dark:text-gray-300">
                                        Lot: {{ $lot->lot_code }}
                                    </span>
                                @endif
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                                    <div class="text-[10px] uppercase tracking-wide text-gray-400">Produced qty</div>
                                    <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">
                                        {{ $fmtQty($output->produced_quantity) }}
                                    </div>
                                </div>

                                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                                    <div class="text-[10px] uppercase tracking-wide text-gray-400">Produced weight</div>
                                    <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">
                                        {{ $fmtW($output->produced_weight_kg) }}
                                    </div>
                                </div>

                                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                                    <div class="text-[10px] uppercase tracking-wide text-gray-400">Piece count</div>
                                    <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">
                                        @if(!empty($output->piece_count))
                                            {{ (int) $output->piece_count }}
                                        @else
                                            —
                                        @endif
                                    </div>
                                </div>

                                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                                    <div class="text-[10px] uppercase tracking-wide text-gray-400">Pack size</div>
                                    <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">
                                        @if(!empty($output->pack_size_kg))
                                            {{ number_format((float) $output->pack_size_kg, 3) }} kg
                                        @else
                                            —
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if(!empty($output->notes))
                                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3 text-[12px] text-gray-700 dark:text-gray-200">
                                    {{ $output->notes }}
                                </div>
                            @endif
                        </div>

                        <div class="space-y-3">
                            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950/20 p-4">
                                <div class="flex items-center justify-between text-[12px]">
                                    <span class="text-gray-500 dark:text-gray-400">Allocated cost</span>
                                    <span class="font-semibold text-gray-900 dark:text-gray-50">
                                        {{ $fmtM($output->allocated_cost) }}
                                    </span>
                                </div>

                                @if($lot)
                                    <div class="mt-3 grid gap-2 text-[11px] text-gray-600 dark:text-gray-300">
                                        <div class="flex items-center justify-between">
                                            <span>Available qty</span>
                                            <span class="font-medium text-gray-900 dark:text-gray-50">{{ $fmtQty($lot->available_quantity) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span>Available wt.</span>
                                            <span class="font-medium text-gray-900 dark:text-gray-50">{{ $fmtW($lot->available_weight_kg) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span>Saleable</span>
                                            <span class="font-medium text-gray-900 dark:text-gray-50">{{ $lot->is_saleable ? 'Yes' : 'No' }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span>Can repack</span>
                                            <span class="font-medium text-gray-900 dark:text-gray-50">{{ $lot->can_repack ? 'Yes' : 'No' }}</span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            @if($mode === 'Pieces')
                                <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 p-4">
                                    <div class="text-[11px] font-semibold text-gray-900 dark:text-gray-50 mb-3">
                                        Individual output weights
                                    </div>

                                    @if($pieces->isNotEmpty())
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($pieces as $piece)
                                                <span class="inline-flex items-center rounded-full border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-1 text-[11px] text-gray-700 dark:text-gray-200">
                                                    #{{ $piece->piece_no }} · {{ number_format((float) $piece->weight_kg, 3) }} kg
                                                </span>
                                            @endforeach
                                        </div>

                                        <div class="mt-3 text-[11px] text-gray-500 dark:text-gray-400">
                                            {{ $pieces->count() }} piece(s) stored in this lot.
                                        </div>
                                    @else
                                        <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                            No individual piece rows found for this output lot.
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    No output rows found.
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection