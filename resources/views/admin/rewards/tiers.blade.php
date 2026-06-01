@extends('layouts.company')
@section('title', 'Reward Tiers')
@section('breadcrumb', 'Admin · Rewards · Tiers')
@section('content')
<div class="space-y-4">
    <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Reward Tiers</h1>
    @include('admin.rewards._nav')
    @if(session('status'))<div class="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-700">{{ session('status') }}</div>@endif
    <form method="POST" action="{{ route('admin.rewards.tiers.update') }}" class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
        @csrf @method('PUT')
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs">
                <thead class="text-left text-gray-500"><tr><th class="p-2">Active</th><th class="p-2">Key</th><th class="p-2">Name</th><th class="p-2">Min</th><th class="p-2">Max</th><th class="p-2">Reward %</th><th class="p-2">Order</th></tr></thead>
                <tbody>
                    @foreach($tiers as $i => $tier)
                    <tr class="border-t border-gray-100 dark:border-gray-800">
                        <td class="p-2"><input type="checkbox" name="tiers[{{ $i }}][is_active]" value="1" @checked($tier->is_active)></td>
                        <td class="p-2"><input type="hidden" name="tiers[{{ $i }}][id]" value="{{ $tier->id }}"><input name="tiers[{{ $i }}][key]" value="{{ $tier->key }}" class="w-28 rounded border px-2 py-1 dark:border-gray-700 dark:bg-gray-950"></td>
                        <td class="p-2"><input name="tiers[{{ $i }}][name]" value="{{ $tier->name }}" class="w-32 rounded border px-2 py-1 dark:border-gray-700 dark:bg-gray-950"></td>
                        <td class="p-2"><input type="number" name="tiers[{{ $i }}][threshold_min]" value="{{ $tier->threshold_min }}" class="w-24 rounded border px-2 py-1 dark:border-gray-700 dark:bg-gray-950"></td>
                        <td class="p-2"><input type="number" name="tiers[{{ $i }}][threshold_max]" value="{{ $tier->threshold_max }}" class="w-24 rounded border px-2 py-1 dark:border-gray-700 dark:bg-gray-950" placeholder="∞"></td>
                        <td class="p-2"><input type="number" step="0.01" name="tiers[{{ $i }}][reward_rate_percent]" value="{{ $tier->reward_rate_percent }}" class="w-24 rounded border px-2 py-1 dark:border-gray-700 dark:bg-gray-950"></td>
                        <td class="p-2"><input type="number" name="tiers[{{ $i }}][sort_order]" value="{{ $tier->sort_order }}" class="w-20 rounded border px-2 py-1 dark:border-gray-700 dark:bg-gray-950"></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-xs text-gray-500">Default policy: Silver 0–999 at 1%, Gold 1000–3499 at 2%, Platinum 3500+ at 4%. Tier progress is annual tier points, not current wallet balance.</p>
        <button class="mt-4 rounded bg-gray-900 px-3 py-1.5 text-xs text-white dark:bg-gray-100 dark:text-gray-900">Save tiers</button>
    </form>
</div>
@endsection
