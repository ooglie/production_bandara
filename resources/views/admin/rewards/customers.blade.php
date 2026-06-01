@extends('layouts.company')
@section('title', 'Reward Customers')
@section('breadcrumb', 'Admin · Rewards · Customers')
@section('content')
<div class="space-y-4">
    <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">B2C Reward Customers</h1>
    @include('admin.rewards._nav')
    @if(session('status'))<div class="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-700">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="rounded border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">{{ $errors->first() }}</div>@endif
    <div class="grid gap-4 xl:grid-cols-3">
        <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950 xl:col-span-2 overflow-x-auto"><table class="min-w-full text-xs"><thead class="text-left text-gray-500"><tr><th class="p-3">Customer</th><th class="p-3">Tier</th><th class="p-3">Balance</th><th class="p-3">Type</th></tr></thead><tbody>@foreach($customers as $customer)<tr class="border-t border-gray-100 dark:border-gray-800"><td class="p-3"><div class="font-medium text-gray-900 dark:text-gray-50">{{ $customer->name }}</div><div class="text-gray-500">{{ $customer->email }} · #{{ $customer->id }}</div></td><td class="p-3">{{ str($customer->reward_tier ?? 'silver')->title() }}</td><td class="p-3">{{ number_format($customer->reward_balance ?? 0) }}</td><td class="p-3">{{ strtoupper($customer->customer_type ?? 'b2c') }}</td></tr>@endforeach</tbody></table></div>
        <form method="POST" action="{{ route('admin.rewards.adjustments.store') }}" class="rounded-lg border border-gray-200 bg-white p-4 text-xs dark:border-gray-800 dark:bg-gray-950">@csrf<h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Manual adjustment</h2><p class="mt-1 text-gray-500">Only B2C users are accepted. Use negative amount for debit.</p><div class="mt-3 space-y-2"><input name="user_id" placeholder="User ID" class="w-full rounded border px-2 py-1 dark:border-gray-700 dark:bg-gray-950"><input type="number" name="amount" placeholder="Wallet points + / -" class="w-full rounded border px-2 py-1 dark:border-gray-700 dark:bg-gray-950"><input type="number" name="tier_points" placeholder="Tier points effect (optional)" class="w-full rounded border px-2 py-1 dark:border-gray-700 dark:bg-gray-950"><textarea name="note" rows="3" placeholder="Reason" class="w-full rounded border px-2 py-1 dark:border-gray-700 dark:bg-gray-950"></textarea><button class="rounded bg-gray-900 px-3 py-1.5 text-white dark:bg-gray-100 dark:text-gray-900">Post adjustment</button></div></form>
    </div>
    {{ $customers->links() }}
</div>
@endsection
