@extends('layouts.company')
@section('title', 'Reward Ledger')
@section('breadcrumb', 'Admin · Rewards · Ledger')
@section('content')
<div class="space-y-4">
    <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Reward Ledger</h1>
    @include('admin.rewards._nav')
    <form method="GET" class="flex flex-wrap gap-2 text-xs"><input name="user_id" value="{{ request('user_id') }}" placeholder="User ID" class="rounded border px-2 py-1 dark:border-gray-700 dark:bg-gray-950"><input name="type" value="{{ request('type') }}" placeholder="Type" class="rounded border px-2 py-1 dark:border-gray-700 dark:bg-gray-950"><input name="status" value="{{ request('status') }}" placeholder="Status" class="rounded border px-2 py-1 dark:border-gray-700 dark:bg-gray-950"><button class="rounded bg-gray-900 px-3 py-1 text-white dark:bg-gray-100 dark:text-gray-900">Filter</button></form>
    <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950 overflow-x-auto">
        <table class="min-w-full text-xs"><thead class="text-left text-gray-500"><tr><th class="p-3">Date</th><th class="p-3">Customer</th><th class="p-3">Type</th><th class="p-3">Status</th><th class="p-3">Wallet</th><th class="p-3">Tier pts</th><th class="p-3">Campaign</th><th class="p-3">Note</th></tr></thead><tbody>@forelse($transactions as $tx)<tr class="border-t border-gray-100 dark:border-gray-800"><td class="p-3">{{ optional($tx->created_at)->format('d M Y H:i') }}</td><td class="p-3">{{ $tx->user?->name ?? ('User #'.$tx->user_id) }}</td><td class="p-3">{{ $tx->type }}</td><td class="p-3">{{ $tx->status }}</td><td class="p-3 font-mono">{{ $tx->amount > 0 ? '+' : '' }}{{ number_format($tx->amount) }}</td><td class="p-3 font-mono">{{ $tx->tier_points > 0 ? '+' : '' }}{{ number_format($tx->tier_points) }}</td><td class="p-3">{{ $tx->campaign?->name ?? '—' }}</td><td class="p-3 text-gray-500">{{ Str::limit($tx->note, 80) }}</td></tr>@empty<tr><td colspan="8" class="p-6 text-center text-gray-500">No ledger entries.</td></tr>@endforelse</tbody></table>
    </div>
    {{ $transactions->links() }}
</div>
@endsection
