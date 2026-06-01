@extends('layouts.customer')

@section('title', 'Bandara Credit rules')

@section('content')
@php
    $minimumRedeemPoints = (int) ($minimumRedeemPoints ?? config('bandara_credit.redemption.minimum_points', 1));
    $maxOrderPercent = (float) ($maxOrderPercent ?? config('bandara_credit.redemption.max_order_percent', 20));
    $minimumPayableAmount = (float) ($minimumPayableAmount ?? config('bandara_credit.redemption.minimum_payable_amount', 1));
    $tiers = collect($tiers ?? []);
@endphp

<div class="max-w-5xl mx-auto px-4 py-6 space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Bandara Credit rules</h1>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Simple rules for earning, redeeming, tiers, promotions, refunds, and eligibility.
            </p>
        </div>
        @if(Route::has('account.rewards'))
            <a href="{{ route('account.rewards') }}" class="inline-flex rounded-sm border border-gray-300 px-4 py-2 text-xs text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                Back to rewards
            </a>
        @endif
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-4 text-xs text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
        <strong class="text-gray-900 dark:text-gray-50">Eligibility:</strong>
        Bandara Credit is available only for eligible B2C retail customers. B2B customers are excluded from earning and redemption.
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">How earning works</h2>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="text-left text-gray-500">
                        <tr><th class="py-2 pr-3">Tier</th><th class="py-2 pr-3">Annual tier points</th><th class="py-2 text-right">Reward rate</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($tiers as $tier)
                            <tr>
                                <td class="py-2 pr-3 font-medium text-gray-900 dark:text-gray-50">{{ $tier['name'] ?? str($tier['key'] ?? 'tier')->title() }}</td>
                                <td class="py-2 pr-3 text-gray-600 dark:text-gray-300">
                                    {{ number_format((int) ($tier['threshold_min'] ?? 0)) }}{{ isset($tier['threshold_max']) && $tier['threshold_max'] !== null ? '–'.number_format((int) $tier['threshold_max']) : '+' }}
                                </td>
                                <td class="py-2 text-right text-gray-900 dark:text-gray-50">{{ rtrim(rtrim(number_format((float) ($tier['reward_rate_percent'] ?? 0), 2), '0'), '.') }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-4 text-gray-500">Tier details will appear once the programme is configured.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                Tier progress is based on annual posted reward tier points, not current wallet balance. Redeeming credits does not reduce tier progress.
            </p>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">How redemption works</h2>
            <ul class="mt-3 space-y-2 text-xs text-gray-600 dark:text-gray-300">
                <li>• Available credits can be applied during eligible checkout orders.</li>
                <li>• Minimum redemption: {{ number_format($minimumRedeemPoints) }} point{{ $minimumRedeemPoints === 1 ? '' : 's' }}.</li>
                <li>• Maximum redemption per order: {{ rtrim(rtrim(number_format($maxOrderPercent, 2), '0'), '.') }}% of eligible order value.</li>
                <li>• Reserved credits are held while payment is pending and released if checkout/payment fails.</li>
                <li>• At least ₹{{ number_format($minimumPayableAmount, 2) }} must remain payable unless a separate fully credit-paid checkout flow is enabled later.</li>
            </ul>
        </section>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Promotions</h2>
            <p class="mt-2 text-xs text-gray-600 dark:text-gray-300">
                Reward specials may add extra wallet credits on selected orders, products, or categories. Promotional bonus credits do not count toward tier unless the campaign explicitly says they do.
            </p>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Refunds & cancellations</h2>
            <p class="mt-2 text-xs text-gray-600 dark:text-gray-300">
                Earned credits may be reversed when an order is cancelled, refunded, or adjusted. Redeemed credits may be restored where applicable. All changes are recorded in the ledger.
            </p>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Tier validity</h2>
            <p class="mt-2 text-xs text-gray-600 dark:text-gray-300">
                When a customer reaches Gold or Platinum, the tier can remain valid through the qualifying year and the next full calendar year, based on the configured programme settings.
            </p>
        </section>
    </div>
</div>
@endsection
