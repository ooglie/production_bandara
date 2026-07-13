@extends('layouts.company')

@section('title', 'Reports')
@section('breadcrumb', 'Admin · Reports')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-5 text-xs space-y-5">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Reports</h1>
            <p class="text-[12px] text-gray-500 dark:text-gray-400">
                Read-only sales and inventory views for operational review. Reports do not update stock, orders or invoices.
            </p>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        @foreach($reportCards as $card)
            <a href="{{ $card['route'] }}"
               class="group rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-gray-300 hover:shadow-md dark:border-gray-800 dark:bg-gray-950 dark:hover:border-gray-700">
                <div class="mb-4 inline-flex rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">
                    {{ $card['accent'] }}
                </div>
                <h2 class="text-base font-semibold text-gray-900 group-hover:text-gray-700 dark:text-gray-50 dark:group-hover:text-gray-200">
                    {{ $card['title'] }}
                </h2>
                <p class="mt-2 min-h-12 text-[12px] leading-5 text-gray-500 dark:text-gray-400">
                    {{ $card['description'] }}
                </p>
                <div class="mt-5 inline-flex items-center text-[12px] font-semibold text-gray-900 dark:text-gray-50">
                    Open report
                    <span class="ml-1 transition group-hover:translate-x-0.5">→</span>
                </div>
            </a>
        @endforeach
    </div>

    <div class="rounded-2xl border border-amber-200 bg-amber-50/80 p-4 text-[12px] text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-100">
        <div class="font-semibold">Recommended first production checks</div>
        <div class="mt-1 leading-5">
            Use Sales Summary and Product Sales after every test order batch. Use Inventory Stock after vendor inward and Transform Stock to confirm product, variant, lot, piece and pack quantities.
        </div>
    </div>
</div>
@endsection
