@extends('layouts.customer')

@section('title', 'My invoices')

@section('content')
@php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;

    $ordersUrl = Route::has('orders.index') ? route('orders.index') : '#';

    $invoiceStatusMeta = function (?string $status) {
        $status = strtolower((string) $status);

        return match ($status) {
            'paid' => [
                'label' => 'Paid',
                'class' => 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800',
            ],
            'past_due' => [
                'label' => 'Past due',
                'class' => 'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800',
            ],
            'due' => [
                'label' => 'Due',
                'class' => 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800',
            ],
            'pending' => [
                'label' => 'Pending',
                'class' => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
            ],
            default => [
                'label' => Str::headline(str_replace('_', ' ', $status ?: 'Unknown')),
                'class' => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
            ],
        };
    };

    $invoiceCount = method_exists($invoices, 'total') ? $invoices->total() : $invoices->count();
@endphp

<div class="max-w-6xl mx-auto px-4 py-6 space-y-5">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <p class="text-[11px] uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">
                Invoices
            </p>
            <h1 class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-50">
                My invoices
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                View invoice records, payment status, and order references.
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            <div class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-1.5 text-[11px]">
                <span class="text-gray-500 dark:text-gray-400">Total invoices</span>
                <span class="ml-2 font-medium text-gray-900 dark:text-gray-50">{{ $invoiceCount }}</span>
            </div>

            <a href="{{ $ordersUrl }}"
               class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                Back to orders
            </a>
        </div>
    </div>

    @if($invoices->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-6 py-10 text-center">
            <div class="text-4xl">🧾</div>
            <h2 class="mt-3 text-lg font-semibold text-gray-900 dark:text-gray-50">
                You have no invoices yet
            </h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Once invoices are generated for your orders, they’ll appear here.
            </p>
        </div>
    @else

        {{-- Mobile cards --}}
        <div class="grid gap-3 md:hidden">
            @foreach($invoices as $invoice)
                @php
                    $status = $invoiceStatusMeta($invoice->status ?? null);
                @endphp

                <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-[10px] uppercase tracking-wide text-gray-400">Invoice</div>
                            <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">
                                {{ $invoice->invoice_number }}
                            </div>
                        </div>

                        <span class="inline-flex items-center rounded-sm border px-2 py-0.5 text-[10px] font-medium {{ $status['class'] }}">
                            {{ $status['label'] }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-950/40 px-3 py-3">
                            <div class="text-[10px] uppercase tracking-wide text-gray-400">Order</div>
                            <div class="mt-1 text-gray-900 dark:text-gray-50">
                                {{ $invoice->order->order_number ?? '—' }}
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-950/40 px-3 py-3">
                            <div class="text-[10px] uppercase tracking-wide text-gray-400">Date</div>
                            <div class="mt-1 text-gray-900 dark:text-gray-50">
                                {{ optional($invoice->invoice_date ?? $invoice->created_at)->format('d M Y') ?? '—' }}
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-950/40 px-3 py-3">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">Total</div>
                        <div class="mt-1 text-base font-semibold text-gray-900 dark:text-gray-50">
                            ₹{{ number_format($invoice->grand_total ?? $invoice->total_amount ?? 0, 2) }}
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('invoices.show', $invoice) }}"
                           class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                            View invoice
                        </a>

                        @if($invoice->order && Route::has('orders.show'))
                            <a href="{{ route('orders.show', $invoice->order) }}"
                               class="inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] text-gray-800 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">
                                View order
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Desktop compact table --}}
        <div class="hidden md:block overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-950/40">
                        <tr class="text-left text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <th class="px-4 py-2.5">Invoice</th>
                            <th class="px-4 py-2.5">Order</th>
                            <th class="px-4 py-2.5">Date</th>
                            <th class="px-4 py-2.5 text-right">Total</th>
                            <th class="px-4 py-2.5 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($invoices as $invoice)
                            @php
                                $status = $invoiceStatusMeta($invoice->status ?? null);
                            @endphp

                            <tr class="align-middle hover:bg-gray-50/70 dark:hover:bg-gray-950/30 transition">
                                {{-- Invoice block --}}
                                <td class="px-4 py-2.5">
                                    <div class="relative inline-flex min-w-[190px] rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-950/40 px-3 py-2 pr-20">
                                        <div>
                                            <div class="text-[10px] uppercase tracking-wide text-gray-400">Invoice</div>
                                            <div class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-gray-50">
                                                {{ $invoice->invoice_number }}
                                            </div>
                                        </div>

                                        <span class="absolute right-2 top-2 inline-flex items-center rounded-sm border px-2 py-0.5 text-[10px] font-medium {{ $status['class'] }}">
                                            {{ $status['label'] }}
                                        </span>
                                    </div>
                                </td>

                                {{-- Order ref --}}
                                <td class="px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $invoice->order->order_number ?? '—' }}
                                </td>

                                {{-- Date --}}
                                <td class="px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                    {{ optional($invoice->invoice_date ?? $invoice->created_at)->format('d M Y') ?? '—' }}
                                </td>

                                {{-- Total --}}
                                <td class="px-4 py-2.5 text-right text-sm font-semibold text-gray-900 dark:text-gray-50 whitespace-nowrap">
                                    ₹{{ number_format($invoice->grand_total ?? $invoice->total_amount ?? 0, 2) }}
                                </td>

                                {{-- Actions --}}
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('invoices.show', $invoice) }}"
                                           class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200 whitespace-nowrap">
                                            View
                                        </a>

                                        @if($invoice->order && Route::has('orders.show'))
                                            <a href="{{ route('orders.show', $invoice->order) }}"
                                               class="inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] text-gray-800 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800 whitespace-nowrap">
                                                Order
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-800">
                {{ $invoices->links() }}
            </div>
        </div>

        {{-- Mobile pagination --}}
        <div class="md:hidden">
            {{ $invoices->links() }}
        </div>
    @endif
</div>
@endsection