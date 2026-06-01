@extends('layouts.company')

@section('title', 'Reward reports')
@section('breadcrumb', 'Admin · Rewards · Reports')

@section('content')
@php
    $summary = $summary ?? [];
    $monthlyRows = collect($monthlyRows ?? []);
    $campaignRows = collect($campaignRows ?? []);
    $tierRows = collect($tierRows ?? []);
    $eligibilityAudit = $eligibilityAudit ?? [];
    $metricLabel = fn ($key) => str($key)->headline();
@endphp

<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Reward reports</h1>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Accounting view for reward liability, campaign performance, redemptions, reversals, and B2C-only audit checks.</p>
        </div>
        @if(Route::has('admin.rewards.reports.export'))
            <a href="{{ route('admin.rewards.reports.export', request()->only(['from', 'to'])) }}" class="inline-flex items-center rounded bg-gray-900 px-3 py-2 text-xs font-medium text-white dark:bg-gray-100 dark:text-gray-900">Export CSV</a>
        @endif
    </div>

    @include('admin.rewards._nav')

    <form method="GET" action="{{ route('admin.rewards.reports') }}" class="flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-white p-4 text-xs dark:border-gray-800 dark:bg-gray-950">
        <div>
            <label class="block text-[10px] uppercase tracking-wide text-gray-500">From</label>
            <input type="date" name="from" value="{{ request('from', $from->toDateString()) }}" class="mt-1 rounded border border-gray-300 px-2 py-1 dark:border-gray-700 dark:bg-gray-950">
        </div>
        <div>
            <label class="block text-[10px] uppercase tracking-wide text-gray-500">To</label>
            <input type="date" name="to" value="{{ request('to', $to->toDateString()) }}" class="mt-1 rounded border border-gray-300 px-2 py-1 dark:border-gray-700 dark:bg-gray-950">
        </div>
        <button class="rounded bg-gray-900 px-3 py-1.5 text-white dark:bg-gray-100 dark:text-gray-900">Apply</button>
        <a href="{{ route('admin.rewards.reports') }}" class="rounded border border-gray-300 px-3 py-1.5 text-gray-600 dark:border-gray-700 dark:text-gray-300">Reset</a>
    </form>

    @can('manage rewards')
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Partial refund / correction adjustment</h2>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Use this only when an order is partially refunded/cancelled or reward accounting needs a manual order-linked correction. Choose the correction type and enter a positive point amount.
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.rewards.order-adjustments.store') }}" class="mt-3 grid gap-3 text-xs md:grid-cols-6">
                @csrf
                <div>
                    <label class="block text-[10px] uppercase tracking-wide text-gray-500">Order ID</label>
                    <input type="number" name="order_id" value="{{ old('order_id') }}" class="mt-1 w-full rounded border border-gray-300 px-2 py-1 dark:border-gray-700 dark:bg-gray-950" required>
                </div>
                <div>
                    <label class="block text-[10px] uppercase tracking-wide text-gray-500">Type</label>
                    <select name="adjustment_type" class="mt-1 w-full rounded border border-gray-300 px-2 py-1 dark:border-gray-700 dark:bg-gray-950" required>
                        <option value="earn_reversal">Reverse earned</option>
                        <option value="redeem_restore">Restore redeemed</option>
                        <option value="manual_credit">Manual credit</option>
                        <option value="manual_debit">Manual debit</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] uppercase tracking-wide text-gray-500">Points</label>
                    <input type="number" name="points" value="{{ old('points') }}" min="1" placeholder="40" class="mt-1 w-full rounded border border-gray-300 px-2 py-1 dark:border-gray-700 dark:bg-gray-950" required>
                </div>
                <div>
                    <label class="block text-[10px] uppercase tracking-wide text-gray-500">Tier points</label>
                    <input type="number" name="tier_points" value="{{ old('tier_points') }}" min="0" placeholder="40" class="mt-1 w-full rounded border border-gray-300 px-2 py-1 dark:border-gray-700 dark:bg-gray-950">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[10px] uppercase tracking-wide text-gray-500">Reason / note</label>
                    <div class="flex gap-2">
                        <input type="text" name="note" value="{{ old('note') }}" placeholder="Partial refund of item X" class="mt-1 w-full rounded border border-gray-300 px-2 py-1 dark:border-gray-700 dark:bg-gray-950" required>
                        <button class="mt-1 rounded bg-gray-900 px-3 py-1 text-white dark:bg-gray-100 dark:text-gray-900">Post</button>
                    </div>
                </div>
            </form>
        </div>
    @endcan

    @if(($eligibilityAudit['non_b2c_wallets'] ?? 0) > 0 || ($eligibilityAudit['non_b2c_transactions'] ?? 0) > 0)
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200">
            B2C eligibility audit requires attention: {{ number_format($eligibilityAudit['non_b2c_wallets'] ?? 0) }} non-B2C wallet(s) and {{ number_format($eligibilityAudit['non_b2c_transactions'] ?? 0) }} non-B2C reward transaction(s). Run <code>php artisan bandara-credit:audit-eligibility --json</code> for details.
        </div>
    @endif

    <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-5">
        @foreach($summary as $key => $value)
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
                <div class="text-[10px] uppercase tracking-wide text-gray-400">{{ $metricLabel($key) }}</div>
                <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50">{{ is_numeric($value) ? number_format((float) $value) : $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 xl:grid-cols-[1fr,0.8fr]">
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Monthly ledger movement</h2>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-xs dark:divide-gray-800">
                    <thead class="text-left text-gray-500"><tr><th class="px-3 py-2">Month</th><th class="px-3 py-2 text-right">Issued</th><th class="px-3 py-2 text-right">Redeemed</th><th class="px-3 py-2 text-right">Reserved</th><th class="px-3 py-2 text-right">Reversed</th><th class="px-3 py-2 text-right">Promo</th><th class="px-3 py-2 text-right">Tier pts</th></tr></thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($monthlyRows as $row)
                            <tr><td class="px-3 py-2 font-medium">{{ $row->period }}</td><td class="px-3 py-2 text-right">{{ number_format((int) $row->issued) }}</td><td class="px-3 py-2 text-right">{{ number_format((int) $row->redeemed) }}</td><td class="px-3 py-2 text-right">{{ number_format((int) $row->reserved) }}</td><td class="px-3 py-2 text-right">{{ number_format((int) $row->reversed) }}</td><td class="px-3 py-2 text-right">{{ number_format((int) $row->promo_bonus) }}</td><td class="px-3 py-2 text-right">{{ number_format((int) $row->tier_points) }}</td></tr>
                        @empty
                            <tr><td colspan="7" class="px-3 py-5 text-center text-gray-500">No reward activity for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Tier liability</h2>
            <div class="mt-3 space-y-2 text-xs">
                @forelse($tierRows as $row)
                    <div class="flex items-center justify-between rounded border border-gray-100 px-3 py-2 dark:border-gray-800"><div><div class="font-medium">{{ str($row->tier ?? 'unknown')->title() }}</div><div class="text-gray-500">{{ number_format((int) $row->customers_count) }} customers</div></div><div class="text-right font-semibold">{{ number_format((int) $row->balance_sum) }} pts</div></div>
                @empty
                    <div class="rounded border border-dashed border-gray-300 px-3 py-4 text-gray-500 dark:border-gray-700">No wallet data yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
        <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Campaign performance</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-xs dark:divide-gray-800">
                <thead class="text-left text-gray-500"><tr><th class="px-3 py-2">Campaign</th><th class="px-3 py-2">Status</th><th class="px-3 py-2 text-right">Bonus issued</th><th class="px-3 py-2 text-right">Tier points</th><th class="px-3 py-2 text-right">Txns</th><th class="px-3 py-2 text-right">Customers</th><th class="px-3 py-2 text-right">Orders</th><th class="px-3 py-2 text-right">Budget</th></tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($campaignRows as $row)
                        <tr><td class="px-3 py-2 font-medium">{{ $row->name ?? 'Unassigned campaign' }}</td><td class="px-3 py-2">{{ str($row->status ?? 'n/a')->headline() }}</td><td class="px-3 py-2 text-right">{{ number_format((int) $row->bonus_issued) }}</td><td class="px-3 py-2 text-right">{{ number_format((int) $row->tier_points) }}</td><td class="px-3 py-2 text-right">{{ number_format((int) $row->transactions_count) }}</td><td class="px-3 py-2 text-right">{{ number_format((int) $row->customers_count) }}</td><td class="px-3 py-2 text-right">{{ number_format((int) $row->orders_count) }}</td><td class="px-3 py-2 text-right">{{ $row->budget_points ? number_format((int) $row->used_budget_points).' / '.number_format((int) $row->budget_points) : 'No cap' }}</td></tr>
                    @empty
                        <tr><td colspan="8" class="px-3 py-5 text-center text-gray-500">No campaign data for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
