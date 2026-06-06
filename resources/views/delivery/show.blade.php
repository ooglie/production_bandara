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
    $isDelivered = ($order->delivery_status ?? '') === 'delivered' || ($order->status ?? '') === 'delivered';
    $mapsUrl = $addressText !== '' ? 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($addressText) . '&travelmode=driving' : null;
    $failureReasons = ['Customer unavailable', 'Address issue', 'Customer refused', 'Payment issue', 'Vehicle/rider issue', 'Other'];
    $icons = [
        'map' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l-6 3V6l6-3 6 3 6-3v15l-6 3-6-3z"/><path d="M9 3v15M15 6v15"/></svg>',
        'phone' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.8 19.8 0 0 1 3.11 5.18 2 2 0 0 1 5.1 3h3a2 2 0 0 1 2 1.72c.12.9.33 1.77.62 2.6a2 2 0 0 1-.45 2.11L9 10.7a16 16 0 0 0 4.3 4.3l1.27-1.27a2 2 0 0 1 2.11-.45c.83.29 1.7.5 2.6.62A2 2 0 0 1 22 16.92z"/></svg>',
        'truck' => '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h11v10H3z"/><path d="M14 10h4l3 3v4h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>',
        'check' => '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>',
        'failed' => '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/></svg>',
    ];
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
                    @if(! $isDelivered)
                        <a href="tel:{{ $phone }}" title="Call customer" aria-label="Call customer" class="mt-1 flex items-center gap-2 text-sm font-semibold text-sky-700 dark:text-sky-300">
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-sky-200 bg-gray-50 dark:border-sky-900/60 dark:bg-sky-950/30">{!! $icons['phone'] !!}</span>
                            <span>{{ $phone }}</span>
                        </a>
                    @else
                        <div class="mt-1 flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-950">{!! $icons['phone'] !!}</span>
                            <span>{{ $phone }}</span>
                        </div>
                    @endif
                @endif
            </div>
            @if($addressText !== '')
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-gray-400">Address</p>
                    <div class="mt-1 flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200">
                        @if($mapsUrl && ! $isDelivered)
                            <a href="{{ $mapsUrl }}" target="_blank" rel="noopener" title="Start navigation" aria-label="Start navigation" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-950">
                                {!! $icons['map'] !!}
                            </a>
                            <a href="{{ $mapsUrl }}" target="_blank" rel="noopener" class="leading-relaxed">{{ $addressText }}</a>
                        @else
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-950">{!! $icons['map'] !!}</span>
                            <span class="leading-relaxed">{{ $addressText }}</span>
                        @endif
                    </div>
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
                    <div class="flex justify-end"><button type="submit" title="Out for delivery" aria-label="Out for delivery" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-sky-300 bg-sky-50 text-sm font-semibold text-sky-800 dark:border-sky-800 dark:bg-sky-950/40 dark:text-sky-200">
                        {!! $icons['truck'] !!}
                        <span class="sr-only">Out for delivery</span>
                    </button></div>
                </form>

                <form method="POST" action="{{ route('delivery.orders.delivered', $order) }}" class="space-y-2" onsubmit="return confirm('Mark this order as delivered?');">
                    @csrf
                    <textarea name="delivery_note" rows="2" placeholder="Optional delivery note / received by" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-sm"></textarea>
                    <div class="flex justify-end"><button type="submit" title="Delivered" aria-label="Delivered" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-emerald-300 bg-emerald-50 text-sm font-semibold text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
                        {!! $icons['check'] !!}
                        <span class="sr-only">Delivered</span>
                    </button></div>
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
                    <div class="flex justify-end"><button type="submit" title="Could not deliver" aria-label="Could not deliver" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-rose-300 bg-rose-50 text-sm font-semibold text-rose-800 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-200">
                        {!! $icons['failed'] !!}
                        <span class="sr-only">Could not deliver</span>
                    </button></div>
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
