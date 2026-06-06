@extends('layouts.base')

@section('title', 'Delivery ' . $order->order_number)

@section('body')
@php
    $shipping = $order->shippingAddress;
    $phone = $shipping?->phone ?: $order->user?->phone;
    $addressText = trim(collect([
        $shipping?->address_line1,
        $shipping?->address_line2,
        trim(($shipping?->city ?? '') . ' ' . ($shipping?->pincode ?? '')),
        $shipping?->state,
        $shipping?->country,
    ])->filter()->implode(', '));
    $mapsUrl = $addressText !== '' ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode($addressText) : null;
    $failureReasons = ['Customer unavailable', 'Address issue', 'Customer refused', 'Payment issue', 'Vehicle/rider issue', 'Other'];
@endphp
<div class="min-h-screen bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-50">
    <header class="sticky top-0 z-30 border-b border-gray-200 dark:border-gray-800 bg-white/95 dark:bg-gray-900/95 backdrop-blur">
        <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
            <div>
                <a href="{{ route('delivery.index') }}" class="text-[11px] text-gray-500 dark:text-gray-400">← My deliveries</a>
                <h1 class="text-base font-semibold">Order {{ $order->order_number }}</h1>
            </div>
            <span class="rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1 text-[11px]">
                {{ str_replace('_', ' ', ucfirst($order->delivery_status ?? 'assigned')) }}
            </span>
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

        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-3">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-gray-400">Customer</p>
                <p class="text-sm font-semibold">{{ $shipping?->full_name ?: $order->user?->name }}</p>
                @if($phone)
                    <a href="tel:{{ $phone }}" class="mt-1 inline-block text-sm font-semibold text-sky-700 dark:text-sky-300">{{ $phone }}</a>
                @endif
            </div>
            @if($addressText !== '')
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-gray-400">Address</p>
                    <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-200">{{ $addressText }}</p>
                    @if($mapsUrl)
                        <a href="{{ $mapsUrl }}" target="_blank" rel="noopener" class="mt-2 inline-flex rounded-xl border border-gray-300 dark:border-gray-700 px-3 py-2 text-xs font-semibold">
                            Open in Google Maps
                        </a>
                    @endif
                </div>
            @endif
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="rounded-xl bg-gray-50 dark:bg-gray-950/70 border border-gray-100 dark:border-gray-800 px-3 py-2">
                    <p class="text-gray-500 dark:text-gray-400">Payment</p>
                    <p class="font-semibold">{{ str_replace('_', ' ', ucfirst($order->payment_method ?? 'razorpay')) }} · {{ ucfirst($order->payment_status ?? 'pending') }}</p>
                </div>
                <div class="rounded-xl bg-gray-50 dark:bg-gray-950/70 border border-gray-100 dark:border-gray-800 px-3 py-2">
                    <p class="text-gray-500 dark:text-gray-400">Amount</p>
                    <p class="font-semibold">₹{{ number_format((float) $order->grand_total, 2) }}</p>
                </div>
            </div>
            @if($order->delivery_note)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200">
                    {{ $order->delivery_note }}
                </div>
            @endif
        </section>

        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-3">
            <h2 class="text-sm font-semibold">Items</h2>
            <div class="space-y-2">
                @foreach($order->items as $item)
                    <div class="flex items-start justify-between gap-3 border-b border-gray-100 dark:border-gray-800 pb-2 last:border-b-0 last:pb-0">
                        <div>
                            <p class="text-xs font-semibold">{{ $item->product_name }}</p>
                            @if($item->sku)
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">SKU: {{ $item->sku }}</p>
                            @endif
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-300">Qty {{ number_format((float) $item->quantity, 2) }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        @if(($order->delivery_status ?? '') !== 'delivered' && ($order->status ?? '') !== 'cancelled')
            <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-3">
                <h2 class="text-sm font-semibold">Update delivery</h2>
                <form method="POST" action="{{ route('delivery.orders.out-for-delivery', $order) }}" class="space-y-2">
                    @csrf
                    <textarea name="delivery_note" rows="2" placeholder="Optional note" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-sm"></textarea>
                    <button type="submit" class="w-full rounded-xl border border-sky-300 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-800 dark:border-sky-800 dark:bg-sky-950/40 dark:text-sky-200">
                        Mark out for delivery
                    </button>
                </form>

                <form method="POST" action="{{ route('delivery.orders.delivered', $order) }}" class="space-y-2" onsubmit="return confirm('Mark this order as delivered?');">
                    @csrf
                    <textarea name="delivery_note" rows="2" placeholder="Optional delivery note / received by" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-sm"></textarea>
                    <button type="submit" class="w-full rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
                        Delivered
                    </button>
                </form>

                <form method="POST" action="{{ route('delivery.orders.failed', $order) }}" class="space-y-2">
                    @csrf
                    <select name="delivery_failure_reason" required class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-sm">
                        <option value="">Could not deliver reason</option>
                        @foreach($failureReasons as $reason)
                            <option value="{{ $reason }}">{{ $reason }}</option>
                        @endforeach
                    </select>
                    <textarea name="delivery_note" rows="2" placeholder="Optional note" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-sm"></textarea>
                    <button type="submit" class="w-full rounded-xl border border-rose-300 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-200">
                        Could not deliver
                    </button>
                </form>
            </section>
        @endif

        @if(($order->deliveryEvents ?? collect())->count())
            <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-2">
                <h2 class="text-sm font-semibold">Delivery history</h2>
                @foreach($order->deliveryEvents->take(12) as $event)
                    <div class="rounded-xl border border-gray-100 dark:border-gray-800 px-3 py-2 text-xs">
                        <p class="font-semibold">{{ str_replace('_', ' ', ucfirst($event->event_type)) }}</p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            {{ optional($event->created_at)->format('d M Y, H:i') }}
                            @if($event->user) · {{ $event->user->name }} @endif
                        </p>
                        @if($event->note)
                            <p class="mt-1 text-gray-600 dark:text-gray-300">{{ $event->note }}</p>
                        @endif
                    </div>
                @endforeach
            </section>
        @endif
    </main>
</div>
@endsection
