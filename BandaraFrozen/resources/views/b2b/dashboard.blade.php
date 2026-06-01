@extends('layouts.customer')

@section('title', 'B2B Dashboard')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 space-y-5 text-xs">
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-gray-400">Business account</p>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">Welcome, {{ $user->name }}</h1>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                    Use your B2B dashboard to review approved portfolio products and request access from Explore Catalogue.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('b2b.portfolio') }}" class="rounded-full bg-gray-900 px-4 py-2 text-[11px] font-medium text-white dark:bg-gray-100 dark:text-gray-900">My portfolio</a>
                <a href="{{ route('b2b.cart.index') }}" class="rounded-full border border-gray-300 px-4 py-2 text-[11px] font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Cart</a>
                <a href="{{ route('b2b.catalog.index') }}" class="rounded-full border border-gray-300 px-4 py-2 text-[11px] font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Explore catalogue</a>
            </div>
        </div>
    </div>


    @if(session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-[11px] text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    @php
        $payLaterTerms = $payLaterSummary['terms'] ?? null;
        $payLaterOutstanding = (float) ($payLaterSummary['outstanding_amount'] ?? 0);
        $payLaterAvailable = (float) ($payLaterSummary['available_credit'] ?? 0);
    @endphp

    <div class="grid gap-3 sm:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Portfolio products</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-50">{{ number_format($portfolioCount) }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Pending requests</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-50">{{ number_format($pendingRequestCount) }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Pending weight invoices</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-50">{{ number_format($pendingOrderRequestCount ?? 0) }}</div>
            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Orders where the team is confirming actual supplied weight.</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Pay Later</div>
            @if($payLaterTerms && $payLaterTerms->pay_later_enabled && $payLaterTerms->credit_status === 'active')
                <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format($payLaterAvailable, 2) }} available</div>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                    Limit ₹{{ number_format((float) $payLaterTerms->credit_limit, 2) }} · due in {{ (int) $payLaterTerms->payment_terms_days }} day(s).
                </p>
                @if($payLaterOutstanding > 0)
                    <p class="mt-1 text-[11px] text-amber-600 dark:text-amber-300">Outstanding ₹{{ number_format($payLaterOutstanding, 2) }}</p>
                @endif
            @elseif($payLaterTerms && $payLaterTerms->pay_later_enabled)
                <div class="mt-2 text-sm font-semibold text-amber-700 dark:text-amber-300">{{ ucwords(str_replace('_', ' ', $payLaterTerms->credit_status ?? 'on_hold')) }}</div>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Pay Later is configured but not currently active.</p>
            @else
                <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-50">Pay Now today</div>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Pay Later is not enabled for this account yet.</p>
            @endif
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <div class="mb-3 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Recent portfolio products</h2>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">These products are assigned to your business account.</p>
            </div>
            <a href="{{ route('b2b.portfolio') }}" class="text-[11px] font-medium text-gray-700 underline dark:text-gray-200">View all</a>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($recentPortfolioProducts as $product)
                <a href="{{ route('b2b.catalog.show', ['product' => $product->slug]) }}" class="rounded-xl border border-gray-200 p-3 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800/60">
                    <div class="font-medium text-gray-900 dark:text-gray-50">{{ $product->name }}</div>
                                        <div class="mt-2 inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">In portfolio</div>
                </a>
            @empty
                <div class="col-span-full rounded-xl border border-dashed border-gray-300 p-6 text-center text-[11px] text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    No products are assigned yet. Explore the catalogue and request access.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
