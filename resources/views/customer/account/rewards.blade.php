@extends('layouts.customer')

@section('title', 'My rewards')

@section('content')
@php
    use Illuminate\Support\Str;

    $availablePoints = (int) ($availablePoints ?? 0);
    $pendingPoints = (int) ($pendingPoints ?? 0);
    $reservedPoints = (int) ($reservedPoints ?? 0);
    $lifetimePoints = (int) ($lifetimePoints ?? 0);
    $redeemedPoints = (int) ($redeemedPoints ?? 0);

    $nextRewardAt = (int) ($nextRewardAt ?? 500);
    $pointsToNextReward = max($nextRewardAt - $availablePoints, 0);

    $progressPercent = $nextRewardAt > 0
        ? min(100, (int) round(($availablePoints / $nextRewardAt) * 100))
        : 0;

    $earnRateLabel = $earnRateLabel ?? 'Earn 1 point for every ₹100 spent';
    $redeemRuleLabel = $redeemRuleLabel ?? 'Redeem once you reach 500 points';
    $expiryLabel = $expiryLabel ?? null;

    $currentTier = strtolower((string) ($currentTier ?? 'silver'));
    $currentTierLabel = (string) ($currentTierLabel ?? Str::headline($currentTier));
    $tierPoints = (int) ($tierPoints ?? $annualTierPoints ?? 0);
    $tierRewardRatePercent = (float) ($tierRewardRatePercent ?? 1);
    $nextTier = $nextTier ?? null;
    $nextTierLabel = $nextTierLabel ?? ($nextTier ? Str::headline((string) $nextTier) : null);
    $nextTierThreshold = isset($nextTierThreshold) && $nextTierThreshold !== null ? (int) $nextTierThreshold : null;
    $pointsToNextTier = (int) ($pointsToNextTier ?? 0);
    $tierProgressPercent = min(100, max(0, (float) ($tierProgressPercent ?? 0)));
    $tierValidUntil = $tierValidUntil ?? null;

    $pointsHistory = collect($pointsHistory ?? []);
    $programEnabled = (bool) ($programEnabled ?? false);
    $eligibleUser = (bool) ($eligibleUser ?? true);
    $redemptionEnabled = (bool) ($redemptionEnabled ?? $redeemEnabled ?? false);
@endphp

<div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                My rewards
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Track your points, understand how rewards work, and see recent activity.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if(Route::has('account.rewards.terms'))
                <a href="{{ route('account.rewards.terms') }}"
                   class="inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-4 py-2 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                    Reward rules
                </a>
            @endif
            <a href="{{ route('account.dashboard') }}"
               class="inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-4 py-2 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                Back to dashboard
            </a>
        </div>
    </div>


    @if(! $programEnabled)
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-[12px] text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200">
            Bandara Credit is not live yet. Your reward information will appear here once the programme is enabled.
        </div>
    @elseif(! $eligibleUser)
        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-[12px] text-gray-600 dark:border-gray-800 dark:bg-gray-950/40 dark:text-gray-300">
            Bandara Credit is currently available only for eligible retail customer accounts.
        </div>
    @endif

    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Available points</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-50">
                {{ number_format($availablePoints) }}
            </div>
            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                {{ $redemptionEnabled ? 'Ready to redeem' : 'Tracked safely for future redemption' }}
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Current tier</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-50">
                {{ $currentTierLabel }}
            </div>
            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                {{ number_format($tierPoints) }} annual tier points
                @if($tierRewardRatePercent > 0)
                    · {{ rtrim(rtrim(number_format($tierRewardRatePercent, 2), '0'), '.') }}% back
                @endif
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Pending points</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-50">
                {{ number_format($pendingPoints) }}
            </div>
            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                Confirmed after eligible order delivery
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Reserved points</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-50">
                {{ number_format($reservedPoints) }}
            </div>
            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                Held during pending checkout
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Lifetime points earned</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-50">
                {{ number_format($lifetimePoints) }}
            </div>
            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                Total points earned so far
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Lifetime redeemed</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-50">
                {{ number_format($redeemedPoints) }}
            </div>
            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                Points used on checkout
            </div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-[1.1fr,0.9fr]">
        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 space-y-4">
            <div>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                    {{ $nextTierLabel ? 'Progress to '.$nextTierLabel : 'Top tier reached' }}
                </h2>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                    @if($nextTierLabel)
                        {{ number_format($pointsToNextTier) }} annual tier points to {{ $nextTierLabel }}.
                    @else
                        You are currently at the highest Bandara Credit tier.
                    @endif
                    @if($tierValidUntil)
                        Valid until {{ \Illuminate\Support\Carbon::parse($tierValidUntil)->format('d M Y') }}.
                    @endif
                </p>
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between text-[10px] text-gray-500 dark:text-gray-400">
                    <span>Annual tier points</span>
                    <span>
                        @if($nextTierLabel)
                            {{ number_format($tierPoints) }}{{ $nextTierThreshold ? ' / '.number_format($nextTierThreshold) : '' }} pts
                        @else
                            100%
                        @endif
                    </span>
                </div>

                <div class="h-2 rounded-sm bg-gray-100 dark:bg-gray-800 overflow-hidden">
                    <div class="h-full bg-gray-900 dark:bg-gray-100" style="width: {{ $nextTierLabel ? $tierProgressPercent : 100 }}%"></div>
                </div>

                <div class="flex items-center justify-between text-[10px] text-gray-500 dark:text-gray-400">
                    <span>{{ $nextTierLabel ? number_format($pointsToNextTier).' tier points to go' : 'Highest reward rate reached' }}</span>
                    <span>{{ $nextTierLabel ? rtrim(rtrim(number_format($tierProgressPercent, 1), '0'), '.') : 100 }}%</span>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                    <div class="text-[10px] uppercase tracking-wide text-gray-400">How to earn</div>
                    <div class="mt-1 text-[12px] text-gray-700 dark:text-gray-200">
                        {{ $earnRateLabel }}
                    </div>
                </div>

                <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                    <div class="text-[10px] uppercase tracking-wide text-gray-400">How to redeem</div>
                    <div class="mt-1 text-[12px] text-gray-700 dark:text-gray-200">
                        {{ $redeemRuleLabel }}
                    </div>
                </div>
            </div>

            @if($expiryLabel)
                <div class="rounded-sm border border-amber-200 bg-amber-50 px-4 py-3 text-[11px] text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200">
                    {{ $expiryLabel }}
                </div>
            @endif
        </div>

        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 space-y-4">
            <div>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                    Recent points activity
                </h2>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                    Latest earned, redeemed, or adjusted points.
                </p>
            </div>

            @if($pointsHistory->isEmpty())
                <div class="rounded-sm border border-dashed border-gray-300 dark:border-gray-700 px-4 py-5 text-[11px] text-gray-500 dark:text-gray-400">
                    No points activity yet.
                </div>
            @else
                <div class="space-y-2">
                    @foreach($pointsHistory as $entry)
                        @php
                            $points = (int) ($entry['points'] ?? 0);
                            $isPositive = $points >= 0;
                        @endphp

                        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-[12px] font-normal text-gray-900 dark:text-gray-50">
                                        @if(!empty($entry['date']))
                                            {{-- <div class="mt-2 text-[10px] text-gray-400"> --}}
                                                {{-- <span class="text-[12px] font-normal text-gray-500 dark:text-gray-500"> --}}
                                                   {{ $entry['date'] }}
                                                {{-- </span> --}}
                                            
                                        @endif
                                        <span class="ml-4 text-[12px] font-normal text-gray-900 dark:text-gray-50">
                                        {{ $entry['title'] ?? 'Points update' }}
                                        </span>
                                        {{-- </div> --}}
                                        @if(!empty($entry['subtitle']))
                                                <span class="ml-4 text-[11px] font-thin text-gray-700 dark:text-gray-50">
                                                   {{ $entry['subtitle'] }}
                                                </span>
                                        @endif
                                        
                                    </div>
                                </div>

                                <div class="text-[12px] font-semibold {{ $isPositive ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">
                                    {{ $isPositive ? '+' : '' }}{{ number_format($points) }}
                                </div>
                            </div>

                            {{-- @if(!empty($entry['date']))
                                <div class="mt-2 text-[10px] text-gray-400">
                                    {{ $entry['date'] }}
                                </div>
                            @endif --}}
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection