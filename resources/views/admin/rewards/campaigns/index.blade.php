@extends('layouts.company')
@section('title', 'Reward Campaigns')
@section('breadcrumb', 'Admin · Rewards · Campaigns')
@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Reward Campaigns</h1>
        <a href="{{ route('admin.rewards.campaigns.create') }}" class="rounded bg-gray-900 px-3 py-1.5 text-xs text-white dark:bg-gray-100 dark:text-gray-900">+ New campaign</a>
    </div>
    @include('admin.rewards._nav')
    @if(session('status'))<div class="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-700">{{ session('status') }}</div>@endif
    <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950">
        <table class="min-w-full text-xs">
            <thead class="text-left text-gray-500"><tr><th class="p-3">Name</th><th class="p-3">Status</th><th class="p-3">Type</th><th class="p-3">Rule</th><th class="p-3">Scope</th><th class="p-3">Dates</th><th class="p-3"></th></tr></thead>
            <tbody>
                @forelse($campaigns as $campaign)
                    <tr class="border-t border-gray-100 dark:border-gray-800">
                        <td class="p-3"><div class="font-medium text-gray-900 dark:text-gray-50">{{ $campaign->name }}</div><div class="text-gray-500">{{ $campaign->slug }}</div></td>
                        <td class="p-3">{{ str($campaign->status)->headline() }}</td>
                        <td class="p-3">{{ str($campaign->type)->headline() }}</td>
                        <td class="p-3">{{ number_format((float) $campaign->multiplier, 2) }}x @if($campaign->fixed_bonus_points) + {{ number_format($campaign->fixed_bonus_points) }} fixed @endif <div class="text-gray-500">{{ $campaign->counts_toward_tier ? 'Counts toward tier' : 'Wallet bonus only' }}</div></td>
                        <td class="p-3">{{ $campaign->products_count }} products · {{ $campaign->categories_count }} categories</td>
                        <td class="p-3">{{ optional($campaign->starts_at)->format('d M Y') ?? 'Any' }} → {{ optional($campaign->ends_at)->format('d M Y') ?? 'Open' }}</td>
                        <td class="p-3 text-right"><a href="{{ route('admin.rewards.campaigns.edit', $campaign) }}" class="text-indigo-600 hover:underline">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-6 text-center text-gray-500">No reward campaigns yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $campaigns->links() }}
</div>
@endsection
