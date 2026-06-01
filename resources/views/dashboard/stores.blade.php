@extends('layouts.company')

@section('title', 'Stores Dashboard')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 space-y-4">

    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Stores Dashboard</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Vendor invoices, inventory lots, production runs, and packs.
            </p>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Vendor invoices --}}
        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">Vendor invoices (pending)</div>
            <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50">{{ $stats['vendor_invoices_pending'] }}</div>

            @if($vendorInvoicesIndex)
                <a href="{{ $vendorInvoicesIndex }}"
                   class="mt-3 inline-flex text-[11px] underline text-gray-700 dark:text-gray-200">
                    Manage vendor invoices
                </a>
            @else
                <div class="mt-3 text-[11px] text-gray-400">Route not configured</div>
            @endif
        </div>

        {{-- Lots --}}
        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">Inventory lots (total)</div>
            <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50">{{ $stats['lots_total'] }}</div>

            @if($inventoryLotsIndex)
                <a href="{{ $inventoryLotsIndex }}"
                   class="mt-3 inline-flex text-[11px] underline text-gray-700 dark:text-gray-200">
                    View lots
                </a>
            @else
                <div class="mt-3 text-[11px] text-gray-400">Route not configured</div>
            @endif
        </div>

        {{-- Production runs --}}
        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">Production runs (total)</div>
            <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50">{{ $stats['production_runs_total'] }}</div>

            @if($productionRunsIndex)
                <a href="{{ $productionRunsIndex }}"
                   class="mt-3 inline-flex text-[11px] underline text-gray-700 dark:text-gray-200">
                    Manage production/packs
                </a>
            @else
                <div class="mt-3 text-[11px] text-gray-400">Route not configured</div>
            @endif
        </div>

        {{-- Packs --}}
        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">Packs (available)</div>
            <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50">{{ $stats['packs_available'] }}</div>

            @if($inventoryPacksIndex)
                <a href="{{ $inventoryPacksIndex }}"
                   class="mt-3 inline-flex text-[11px] underline text-gray-700 dark:text-gray-200">
                    Manage packs
                </a>
            @else
                <div class="mt-3 text-[11px] text-gray-400">Route not configured</div>
            @endif
        </div>
    </div>

</div>
@endsection
