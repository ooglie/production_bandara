@extends('layouts.base')

@section('title', 'My deliveries')

@section('body')
<div class="min-h-screen bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-50">
    <header class="sticky top-0 z-30 border-b border-gray-200 dark:border-gray-800 bg-white/95 dark:bg-gray-900/95 backdrop-blur">
        <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-gray-400">Bandara delivery</p>
                <h1 class="text-base font-semibold">My deliveries</h1>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] text-gray-700 dark:text-gray-200">
                    Logout
                </button>
            </form>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-4 space-y-4">
        @if(session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-800 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-200">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
                <p class="text-[11px] text-gray-500 dark:text-gray-400">Active</p>
                <p class="mt-1 text-2xl font-semibold">{{ number_format($stats['active'] ?? 0) }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
                <p class="text-[11px] text-gray-500 dark:text-gray-400">Delivered today</p>
                <p class="mt-1 text-2xl font-semibold">{{ number_format($stats['delivered_today'] ?? 0) }}</p>
            </div>
        </div>

        <div class="flex gap-2 overflow-x-auto pb-1 text-xs">
            <a href="{{ route('delivery.index') }}"
               class="whitespace-nowrap rounded-full border px-3 py-1.5 {{ $filter === 'active' ? 'border-gray-900 bg-gray-900 text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900' : 'border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300' }}">
                Active
            </a>
            <a href="{{ route('delivery.index', ['status' => 'failed']) }}"
               class="whitespace-nowrap rounded-full border px-3 py-1.5 {{ $filter === 'failed' ? 'border-gray-900 bg-gray-900 text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900' : 'border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300' }}">
                Could not deliver
            </a>
            <a href="{{ route('delivery.index', ['status' => 'delivered']) }}"
               class="whitespace-nowrap rounded-full border px-3 py-1.5 {{ $filter === 'delivered' ? 'border-gray-900 bg-gray-900 text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900' : 'border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300' }}">
                Delivered
            </a>
        </div>

        <div class="space-y-3">
            @forelse($orders as $order)
                @php
                    $shipping = $order->shippingAddress;
                    $phone = $shipping?->phone ?: $order->user?->phone;
                    $addressText = trim(collect([
                        $shipping?->address_line1,
                        $shipping?->address_line2,
                        trim(($shipping?->city ?? '') . ' ' . ($shipping?->pincode ?? '')),
                        $shipping?->state,
                    ])->filter()->implode(', '));
                    $mapsUrl = $addressText !== '' ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode($addressText) : null;
                @endphp
                <article class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-3 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold">Order {{ $order->order_number }}</p>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                {{ $order->user?->name ?? 'Customer' }} · ₹{{ number_format((float) $order->grand_total, 2) }}
                            </p>
                        </div>
                        <span class="rounded-full border px-2.5 py-1 text-[10px]
                            @if(($order->delivery_status ?? '') === 'out_for_delivery') border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900/60 dark:bg-sky-950/40 dark:text-sky-200
                            @elseif(($order->delivery_status ?? '') === 'delivered') border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-200
                            @elseif(($order->delivery_status ?? '') === 'failed') border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-200
                            @else border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200
                            @endif">
                            {{ str_replace('_', ' ', ucfirst($order->delivery_status ?? 'assigned')) }}
                        </span>
                    </div>

                    @if($addressText !== '')
                        <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed">{{ $addressText }}</p>
                    @endif

                    @if($order->delivery_note)
                        <p class="rounded-xl bg-gray-50 dark:bg-gray-950/70 border border-gray-100 dark:border-gray-800 px-3 py-2 text-[11px] text-gray-600 dark:text-gray-300">
                            {{ $order->delivery_note }}
                        </p>
                    @endif

                    <div class="grid grid-cols-2 gap-2 text-xs">
                        @if($phone)
                            <a href="tel:{{ $phone }}" class="rounded-xl border border-gray-300 dark:border-gray-700 px-3 py-2 text-center font-medium">
                                Call customer
                            </a>
                        @endif
                        @if($mapsUrl)
                            <a href="{{ $mapsUrl }}" target="_blank" rel="noopener" class="rounded-xl border border-gray-300 dark:border-gray-700 px-3 py-2 text-center font-medium">
                                Open map
                            </a>
                        @endif
                    </div>

                    <div class="grid gap-2 sm:grid-cols-3">
                        <a href="{{ route('delivery.orders.show', $order) }}" class="rounded-xl border border-gray-300 dark:border-gray-700 px-3 py-2 text-center text-xs font-medium">
                            View details
                        </a>
                        @if(($order->delivery_status ?? '') !== 'out_for_delivery' && ($order->delivery_status ?? '') !== 'delivered')
                            <form method="POST" action="{{ route('delivery.orders.out-for-delivery', $order) }}">
                                @csrf
                                <button type="submit" class="w-full rounded-xl border border-sky-300 bg-sky-50 px-3 py-2 text-xs font-semibold text-sky-800 dark:border-sky-800 dark:bg-sky-950/40 dark:text-sky-200">
                                    Out for delivery
                                </button>
                            </form>
                        @endif
                        @if(($order->delivery_status ?? '') !== 'delivered')
                            <form method="POST" action="{{ route('delivery.orders.delivered', $order) }}" onsubmit="return confirm('Mark this order as delivered?');">
                                @csrf
                                <button type="submit" class="w-full rounded-xl border border-emerald-300 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
                                    Delivered
                                </button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    No deliveries found for this view.
                </div>
            @endforelse
        </div>

        @if(method_exists($orders, 'links'))
            {{ $orders->links() }}
        @endif
    </main>
</div>
@endsection
