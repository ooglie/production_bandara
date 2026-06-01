@extends('layouts.customer')

@section('title', 'My dashboard')

@section('content')
@php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $user = auth()->user();

    $has = fn (string $r) => Route::has($r);

    $shopUrl = $has('shop.index') ? route('shop.index') : '#';
    $ordersUrl = $has('orders.index') ? route('orders.index') : '#';
    $invoicesUrl = $has('invoices.index') ? route('invoices.index') : '#';
    $wishlistUrl = $has('wishlist.index') ? route('wishlist.index') : '#';
    $addressesUrl = $has('account.addresses.index') ? route('account.addresses.index') : '#';
    $ticketsUrl = $has('tickets.index') ? route('tickets.index') : '#';
    $newsletterUrl = $has('account.newsletter') ? route('account.newsletter') : '#';
    $rewardsUrl = $has('account.rewards') ? route('account.rewards') : '#';

    $productUrl = function ($product) use ($has) {
        if (!$product) return '#';

        if ($has('products.show')) return route('products.show', $product);
        if ($has('product.show')) return route('product.show', $product->slug ?? $product);
        if ($has('shop.show')) return route('shop.show', $product->slug ?? $product);

        return '#';
    };

    $cartAddUrl =
        $has('cart.items.store') ? route('cart.items.store')
        : ($has('cart.add') ? route('cart.add')
        : ($has('cart.store') ? route('cart.store') : null));

    $canQuickAdd = function ($product) use ($cartAddUrl) {
        if (!$product || !$cartAddUrl) {
            return false;
        }

        if (($product->type ?? 'simple') !== 'simple') {
            return false;
        }

        $manageStock = (bool) ($product->manage_stock ?? false);
        $stockValue = (float) ($product->stock_quantity ?? 0);

        if ($manageStock && $stockValue <= 0) {
            return false;
        }

        return true;
    };

    $resolveMediaUrl = function ($pathOrPaths) {
        $candidates = is_array($pathOrPaths) ? $pathOrPaths : [$pathOrPaths];

        foreach ($candidates as $candidate) {
            if (!$candidate) continue;

            $candidate = trim((string) $candidate);
            if ($candidate === '') continue;

            if (preg_match('#^https?://[^/]+(/storage/.*)$#i', $candidate, $matches)) {
                return $matches[1];
            }

            if (Str::startsWith($candidate, ['http://', 'https://'])) {
                return $candidate;
            }

            if (Str::startsWith($candidate, '/storage/')) {
                return $candidate;
            }

            if (Str::startsWith($candidate, 'storage/')) {
                return '/' . ltrim($candidate, '/');
            }

            if (Str::startsWith($candidate, 'storage/app/public/')) {
                return '/storage/' . ltrim(Str::after($candidate, 'storage/app/public/'), '/');
            }

            if (Str::startsWith($candidate, 'public/')) {
                return '/storage/' . ltrim(Str::after($candidate, 'public/'), '/');
            }

            if (Str::startsWith($candidate, '/')) {
                $publicRelative = ltrim($candidate, '/');

                if (file_exists(public_path($publicRelative))) {
                    return '/' . $publicRelative;
                }

                return $candidate;
            }

            if (file_exists(public_path($candidate))) {
                return '/' . ltrim($candidate, '/');
            }

            if (Storage::disk('public')->exists($candidate)) {
                return '/storage/' . ltrim($candidate, '/');
            }
        }

        return null;
    };

    $productImageUrl = function ($product) use ($resolveMediaUrl) {
        if (!$product) return null;

        $images = $product->images ?? collect();

        return $resolveMediaUrl([
            $product->primary_image_url ?? null,
            $product->primary_image ?? null,
            $product->image_path ?? null,
            optional($images->firstWhere('is_primary', true))->file_path,
            optional($images->first())->file_path,
        ]);
    };

    $isB2b = ($user->customer_type ?? 'b2c') === 'b2b';

    $lastOrderUrl = ($lastOrder && $has('orders.show')) ? route('orders.show', $lastOrder) : '#';
    $favoriteProductImage = $favoriteProduct ? $productImageUrl($favoriteProduct) : null;

    $favoriteOrdersCount = (int) ($favoriteProductStats['orders_count'] ?? 0);
    $favoriteTotalQty = (int) ($favoriteProductStats['total_quantity'] ?? 0);

    $offersCount = $personalOffers->count();

    $statusMeta = function (?string $status) {
        $status = strtolower((string) $status);

        return match ($status) {
            'delivered' => [
                'label' => 'Delivered',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-300 dark:border-emerald-800',
            ],
            'shipped' => [
                'label' => 'Shipped',
                'class' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800',
            ],
            'processing' => [
                'label' => 'Processing',
                'class' => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-900/20 dark:text-sky-300 dark:border-sky-800',
            ],
            'cancelled' => [
                'label' => 'Cancelled',
                'class' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800',
            ],
            default => [
                'label' => Str::headline($status ?: 'Pending'),
                'class' => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
            ],
        };
    };

    $lastOrderMeta = $lastOrder ? $statusMeta($lastOrder->status) : null;
    $lastOrderItems = $lastOrder && $lastOrder->items ? $lastOrder->items : collect();

    $availablePoints = (int) ($availablePoints ?? 0);
    $redemptionEnabled = (bool) ($redemptionEnabled ?? ($redeemEnabled ?? false));
    $pendingPoints = (int) ($pendingPoints ?? 0);
    $nextRewardAt = (int) ($nextRewardAt ?? 500);
    $pointsToNextReward = max($nextRewardAt - $availablePoints, 0);
    $progressPercent = $nextRewardAt > 0
        ? min(100, (int) round(($availablePoints / $nextRewardAt) * 100))
        : 0;

    $programEnabled = (bool) ($programEnabled ?? false);
    $eligibleUser = (bool) ($eligibleUser ?? false);
    $currentTier = strtolower((string) ($currentTier ?? 'silver'));
    $currentTierLabel = (string) ($currentTierLabel ?? $currentTierName ?? $tierName ?? Str::headline($currentTier));
    $tierPoints = (int) ($tierPoints ?? $annualTierPoints ?? 0);
    $nextTier = $nextTier ?? null;
    $nextTierLabel = $nextTierLabel ?? ($nextTier ? Str::headline((string) $nextTier) : null);
    $nextTierThreshold = isset($nextTierThreshold) && $nextTierThreshold !== null ? (int) $nextTierThreshold : null;
    $pointsToNextTier = (int) ($pointsToNextTier ?? $tierPointsToNext ?? 0);
    $tierProgressPercent = min(100, max(0, (float) ($tierProgressPercent ?? 0)));
    $tierRewardRatePercent = (float) ($tierRewardRatePercent ?? 1);
    $tierValidUntil = $tierValidUntil ?? null;
    $tierBadgeClass = match ($currentTier) {
        'platinum' => 'border-slate-300 bg-slate-100 text-slate-800 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100',
        'gold' => 'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-700 dark:bg-amber-950/30 dark:text-amber-200',
        default => 'border-gray-300 bg-gray-50 text-gray-700 dark:border-gray-700 dark:bg-gray-950/40 dark:text-gray-200',
    };

    $suggestedProducts = collect();

    if ($favoriteProduct) {
        $suggestedProducts->push($favoriteProduct);
    }

    foreach ($personalOffers as $offer) {
        if ($offer->product && !$suggestedProducts->contains(fn ($p) => $p->id === $offer->product->id)) {
            $suggestedProducts->push($offer->product);
        }
    }

    $suggestedProducts = $suggestedProducts->take(3)->values();

    $quickActions = collect([
        [
            'title' => 'Orders',
            'text' => 'Track orders',
            'href' => $ordersUrl,
            'icon' => '📦',
            'show' => true,
        ],
        [
            'title' => 'Invoices',
            'text' => 'Download invoices',
            'href' => $invoicesUrl,
            'icon' => '🧾',
            'show' => true,
        ],
        [
            'title' => 'Wishlist',
            'text' => 'Saved favourites',
            'href' => $wishlistUrl,
            'icon' => '💛',
            'show' => config('features.wishlist', true),
        ],
        [
            'title' => 'Addresses',
            'text' => 'Manage addresses',
            'href' => $addressesUrl,
            'icon' => '📍',
            'show' => true,
        ],
        [
            'title' => 'Support',
            'text' => 'Get help',
            'href' => $ticketsUrl,
            'icon' => '🎫',
            'show' => true,
        ],
        [
            'title' => 'Newsletter',
            'text' => 'Preferences',
            'href' => $newsletterUrl,
            'icon' => '✉️',
            'show' => config('features.newsletter', true),
        ],
        [
            'title' => 'Rewards',
            'text' => 'View points',
            'href' => $rewardsUrl,
            'icon' => '⭐',
            'show' => !$isB2b && $has('account.rewards'),
        ],
    ])->filter(fn ($item) => $item['show'])->values();
@endphp

<div class="max-w-6xl mx-auto px-4 py-6 space-y-6">

    {{-- Greeting --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">
                Hi {{ $user->name }}, welcome back 👋
            </h1>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                A compact snapshot of your orders, favourites, account pricing, and next-best actions.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            @if(! $isB2b && $programEnabled && $eligibleUser)
                <a href="{{ $rewardsUrl }}"
                   class="inline-flex items-center gap-2 rounded-full border px-3 py-2 text-xs font-medium {{ $tierBadgeClass }}"
                   title="{{ number_format($tierPoints) }} annual tier points{{ $tierValidUntil ? ' • valid until '.$tierValidUntil : '' }}">
                    <span class="text-[10px] uppercase tracking-wide opacity-70">Tier</span>
                    <span class="font-semibold">{{ $currentTierLabel }}</span>
                    <span class="text-[10px] opacity-70">{{ rtrim(rtrim(number_format($tierRewardRatePercent, 2), '0'), '.') }}% back</span>
                </a>
            @endif

            <a href="{{ $shopUrl }}"
               class="inline-flex items-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-sm font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                Continue shopping
            </a>

            @if($lastOrder)
                <a href="{{ $lastOrderUrl }}"
                   class="inline-flex items-center rounded-sm border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-medium text-gray-800 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">
                    View last order
                </a>
            @endif
        </div>
    </div>

    @if($isB2b)
        @include('dashboard.partials.b2b_quick_order')
    @endif

    {{-- Rewards --}}
    <section class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 space-y-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                    Your rewards
                </p>
                <p class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                    Track redeemable Bandara Credit and your current tier progress.
                </p>
            </div>

            @if($has('account.rewards'))
                <a href="{{ $rewardsUrl }}"
                   class="inline-flex items-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1 text-[10px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                    View rewards
                </a>
            @endif
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-3">
                <div class="text-[10px] uppercase tracking-wide text-gray-400">Current tier</div>
                <div class="mt-1 flex items-center gap-2">
                    <span class="inline-flex rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $tierBadgeClass }}">
                        {{ $currentTierLabel }}
                    </span>
                    @if($tierRewardRatePercent > 0)
                        <span class="text-[11px] text-gray-500 dark:text-gray-400">
                            {{ rtrim(rtrim(number_format($tierRewardRatePercent, 2), '0'), '.') }}% back
                        </span>
                    @endif
                </div>
                <div class="mt-2 text-[10px] text-gray-500 dark:text-gray-400">
                    {{ number_format($tierPoints) }} annual tier points
                </div>
            </div>

            <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-3">
                <div class="text-[10px] uppercase tracking-wide text-gray-400">Available</div>
                <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">
                    {{ number_format($availablePoints) }}
                </div>
                <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                    {{ $redemptionEnabled ? 'Ready to redeem' : 'Tracked for future redemption' }}
                </div>
            </div>

            <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-3">
                <div class="text-[10px] uppercase tracking-wide text-gray-400">Pending</div>
                <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">
                    {{ number_format($pendingPoints) }}
                </div>
                <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                    Added after order completion
                </div>
            </div>

            <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-3">
                <div class="text-[10px] uppercase tracking-wide text-gray-400">
                    {{ $nextTierLabel ? 'Next tier' : 'Highest tier' }}
                </div>
                <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">
                    {{ $nextTierLabel ?: $currentTierLabel }}
                </div>
                <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                    @if($nextTierLabel)
                        {{ number_format($pointsToNextTier) }} tier points to go
                    @else
                        You are at the top tier
                    @endif
                </div>
            </div>
        </div>

        <div class="space-y-2">
            <div class="flex items-center justify-between text-[10px] text-gray-500 dark:text-gray-400">
                <span>{{ $nextTierLabel ? 'Progress to '.$nextTierLabel : 'Top tier reached' }}</span>
                <span>
                    @if($nextTierLabel)
                        {{ number_format($tierPoints) }} / {{ number_format($nextTierThreshold ?: ($tierPoints + $pointsToNextTier)) }} pts
                    @else
                        100%
                    @endif
                </span>
            </div>

            <div class="h-2 rounded-sm bg-gray-100 dark:bg-gray-800 overflow-hidden">
                <div class="h-full bg-gray-900 dark:bg-gray-100" style="width: {{ $nextTierLabel ? min(100, max(0, $tierProgressPercent)) : 100 }}%"></div>
            </div>

            <div class="text-[10px] text-gray-500 dark:text-gray-400">
                @if($nextTierLabel)
                    {{ number_format($pointsToNextTier) }} annual tier points to {{ $nextTierLabel }}.
                @else
                    You are currently at the highest Bandara Credit tier.
                @endif
                @if($tierValidUntil)
                    Valid until {{ \Illuminate\Support\Carbon::parse($tierValidUntil)->format('d M Y') }}.
                @endif
            </div>
        </div>
    </section>

    {{-- Main summary cards --}}
    <div class="grid gap-3 lg:grid-cols-4">

        {{-- Last order --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 h-full">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-gray-400">Last order</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">
                        {{ $lastOrder ? ('#' . ($lastOrder->order_number ?? $lastOrder->id)) : 'No orders yet' }}
                    </p>
                </div>

                @if($lastOrderMeta)
                    <span class="inline-flex rounded-sm border px-2.5 py-1 text-[10px] font-medium {{ $lastOrderMeta['class'] }}">
                        {{ $lastOrderMeta['label'] }}
                    </span>
                @endif
            </div>

            @if($lastOrder)
                <div class="mt-3 space-y-2">
                    <p class="text-[11px] text-gray-600 dark:text-gray-300">
                        Placed {{ optional($lastOrder->placed_at ?? $lastOrder->created_at)->format('d M Y, H:i') }}
                    </p>

                    <p class="text-[11px] font-medium text-gray-900 dark:text-gray-50">
                        ₹{{ number_format($lastOrder->grand_total ?? 0, 2) }}
                    </p>

                    @if($lastOrderItems->isNotEmpty())
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 line-clamp-2">
                            {{ $lastOrderItems->take(2)->pluck('product_name')->join(', ') }}
                            @if($lastOrderItems->count() > 2)
                                and {{ $lastOrderItems->count() - 2 }} more
                            @endif
                        </p>
                    @endif

                    <a href="{{ $lastOrderUrl }}"
                       class="inline-flex items-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] text-gray-800 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">
                        View order
                    </a>
                </div>
            @else
                <p class="mt-3 text-[11px] text-gray-500 dark:text-gray-400">
                    You haven’t placed any orders yet.
                </p>
            @endif
        </div>

        {{-- Favourite product --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 h-full flex flex-col">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-gray-400">Favourite product</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">
                        Your most ordered item
                    </p>
                </div>
                <span class="text-xl">💛</span>
            </div>

            @if($favoriteProduct)
                <div class="mt-3 flex items-start gap-3 flex-1">
                    <div class="h-16 w-16 overflow-hidden rounded-sm border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 shrink-0">
                        @if($favoriteProductImage)
                            <img
                                src="{{ $favoriteProductImage }}"
                                alt="{{ $favoriteProduct->name }}"
                                class="h-full w-full object-cover"
                            >
                        @else
                            <div class="h-full w-full flex items-center justify-center text-xl">❄️</div>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1 flex flex-col">
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-50 line-clamp-2">
                            {{ $favoriteProduct->name }}
                        </p>

                        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                            {{ $favoriteOrdersCount }} {{ Str::plural('order', $favoriteOrdersCount) }} • {{ $favoriteTotalQty }} units
                        </p>

                        <div class="mt-auto pt-3">
                            <div class="flex flex-wrap gap-2">
                                @if($canQuickAdd($favoriteProduct))
                                    <form method="POST" action="{{ $cartAddUrl }}" class="flex-1 min-w-[120px]">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $favoriteProduct->id }}">
                                        <input type="hidden" name="quantity" value="1">

                                        <button
                                            type="submit"
                                            class="inline-flex w-full items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                                            Buy again
                                        </button>
                                    </form>
                                @endif

                                <a href="{{ $productUrl($favoriteProduct) }}"
                                   class="inline-flex flex-1 min-w-[120px] items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] text-gray-800 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">
                                    View product
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <p class="mt-3 text-[11px] text-gray-500 dark:text-gray-400">
                    Once you order more, your most loved item will show here.
                </p>
            @endif
        </div>

        {{-- Personal offers --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 h-full">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-gray-400">Offers for you</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">
                        {{ $offersCount }} available
                    </p>
                </div>
                <span class="text-xl">✨</span>
            </div>

            @if($personalOffers->isNotEmpty())
                <div class="mt-3 space-y-2">
                    @foreach($personalOffers->take(2) as $offer)
                        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-950/40 px-3 py-2">
                            <p class="text-[11px] font-medium text-gray-900 dark:text-gray-50 line-clamp-1">
                                {{ optional($offer->product)->name ?? 'Special offer' }}
                            </p>
                            <p class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                                Tailored for your next order
                            </p>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mt-3 text-[11px] text-gray-500 dark:text-gray-400">
                    We’ll surface personalised offers here as your history grows.
                </p>
            @endif
        </div>

        {{-- Account snapshot --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 h-full">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-gray-400">Account</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">
                        {{ $isB2b ? 'B2B customer' : 'B2C customer' }}
                    </p>
                </div>

                <span class="inline-flex rounded-sm px-2.5 py-1 text-[10px] font-medium {{ $isB2b ? 'bg-sky-50 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' }}">
                    {{ $isB2b ? 'Business' : 'Retail' }}
                </span>
            </div>

            <div class="mt-3 space-y-2 text-[11px] text-gray-600 dark:text-gray-300">
                <a href="{{ $invoicesUrl }}" class="block hover:underline">View invoices</a>
                <a href="{{ $addressesUrl }}" class="block hover:underline">Manage addresses</a>
                <a href="{{ $ticketsUrl }}" class="block hover:underline">Support tickets</a>
                @if($has('account.newsletter'))
                    <a href="{{ $newsletterUrl }}" class="block hover:underline">Newsletter preferences</a>
                @endif
            </div>
        </div>
    </div>

    {{-- Buy again / picked for you --}}
    @if($suggestedProducts->isNotEmpty())
        <section class="space-y-3">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-gray-400">Buy again</p>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-50">
                        Quick picks for your next order
                    </h2>
                </div>

                <a href="{{ $shopUrl }}"
                   class="text-[11px] font-medium text-gray-700 dark:text-gray-200 hover:underline">
                    Browse all
                </a>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                @foreach($suggestedProducts as $product)
                    @php
                        $productThumb = $productImageUrl($product);
                    @endphp

                    <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
                        <div class="relative h-36 overflow-hidden bg-gray-100 dark:bg-gray-800">
                            @if($productThumb)
                                <img
                                    src="{{ $productThumb }}"
                                    alt="{{ $product->name }}"
                                    class="h-full w-full object-cover"
                                >
                            @else
                                <div class="h-full w-full flex items-center justify-center text-3xl">🛒</div>
                            @endif
                        </div>

                        <div class="p-4 space-y-2">
                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-50 line-clamp-2">
                                {{ $product->name }}
                            </div>

                            @if(!empty($product->short_description))
                                <div class="text-[11px] text-gray-500 dark:text-gray-400 line-clamp-2">
                                    {{ $product->short_description }}
                                </div>
                            @endif

                            <div class="flex flex-wrap gap-2 pt-2">
                                @if($canQuickAdd($product))
                                    <form method="POST" action="{{ $cartAddUrl }}" class="flex-1">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                                        <input type="hidden" name="quantity" value="1">

                                        <button type="submit"
                                                class="w-full inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-2 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                                            Add again
                                        </button>
                                    </form>
                                @endif

                                <a href="{{ $productUrl($product) }}"
                                   class="inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-2 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                                    View
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Quick actions --}}
    <section class="space-y-3">
        <div>
            <p class="text-[11px] uppercase tracking-wide text-gray-400">Quick actions</p>
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-50">
                Manage your account faster
            </h2>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($quickActions as $item)
                <a href="{{ $item['href'] }}"
                   class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 hover:border-gray-300 dark:hover:border-gray-700 transition">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                                {{ $item['title'] }}
                            </div>
                            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                {{ $item['text'] }}
                            </div>
                        </div>

                        <span class="text-lg">{{ $item['icon'] }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
</div>
@endsection