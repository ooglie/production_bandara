@extends('layouts.company')

@section('title', 'Order ' . $order->order_number)

@section('content')
@php
    $adminText = function ($value, string $fallback = '—') use (&$adminText): string {
        if ($value instanceof \Illuminate\Support\Collection) {
            $value = $value->all();
        }

        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }

        if (is_null($value)) {
            return $fallback;
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            foreach (['en', 'value', 'name', 'label', 'title'] as $preferredKey) {
                if (array_key_exists($preferredKey, $value) && ! is_array($value[$preferredKey])) {
                    return $adminText($value[$preferredKey], $fallback);
                }
            }

            $parts = [];
            foreach ($value as $item) {
                $part = $adminText($item, '');
                if ($part !== '') {
                    $parts[] = $part;
                }
            }

            $parts = array_values(array_unique($parts));
            return count($parts) ? implode(', ', $parts) : $fallback;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed !== '' && in_array($trimmed[0], ['{', '['], true)) {
                $decoded = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $adminText($decoded, $fallback);
                }
            }

            return $trimmed !== '' ? $trimmed : $fallback;
        }

        if (is_scalar($value) || $value instanceof \Stringable) {
            $text = trim((string) $value);
            return $text !== '' ? $text : $fallback;
        }

        return $fallback;
    };

    $adminNumber = function ($value, float $fallback = 0.0) use ($adminText): float {
        if (is_array($value) || $value instanceof \Illuminate\Support\Collection) {
            $value = $adminText($value, '0');
        }

        return is_numeric($value) ? (float) $value : $fallback;
    };

    $adminMoney = fn ($value): string => number_format($adminNumber($value), 2);
    $adminInt = fn ($value): string => number_format((int) round($adminNumber($value)));
@endphp
<div class="max-w-7xl mx-auto px-4 py-6 text-xs space-y-4">
    <div class="flex items-center justify-between gap-3 mb-2">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Order {{ $adminText($order->order_number) }}
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Placed by {{ $adminText($order->user?->name, 'Unknown customer') }}
                @if($order->user?->email)
                    · {{ $adminText($order->user->email) }}
                @endif
            </p>
        </div>

        <a href="{{ route('admin.orders.index') }}"
           class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
            Back to orders
        </a>
    </div>

    @if(session('status'))
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            {{ $adminText(session('status')) }}
        </div>
    @endif

    {{-- Status + payment summary --}}
    <div class="grid gap-3 lg:grid-cols-[2fr,1.4fr]">
        <div class="space-y-3">
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3 space-y-2">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                            Order status
                        </p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400">
                            Placed on {{ optional($order->placed_at ?? $order->created_at)->format('d M Y, H:i') }}
                        </p>
                    </div>
                    <span class="inline-flex items-center rounded-full border px-3 py-0.5 text-[10px]
                        @if($order->status === 'processing') border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-800 dark:bg-sky-900/30 dark:text-sky-200
                        @elseif($order->status === 'shipped') border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200
                        @elseif($order->status === 'delivered') border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200
                        @elseif($order->status === 'cancelled') border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400
                        @endif">
                        {{ ucfirst($adminText($order->status, '')) }}
                    </span>
                </div>

                <div class="grid gap-2 sm:grid-cols-2 text-[10px] text-gray-600 dark:text-gray-300 pt-1">
                    <div>
                        <p>Placed: <span class="font-medium">
                            {{ optional($order->placed_at ?? $order->created_at)->format('d M Y, H:i') }}
                        </span></p>
                        @if($order->shipped_at)
                            <p>Shipped: <span class="font-medium">
                                {{ $order->shipped_at->format('d M Y, H:i') }}
                            </span></p>
                        @endif
                        @if($order->delivered_at)
                            <p>Delivered: <span class="font-medium">
                                {{ $order->delivered_at->format('d M Y, H:i') }}
                            </span></p>
                        @endif
                        @if($order->cancelled_at)
                            <p>Cancelled: <span class="font-medium">
                                {{ $order->cancelled_at->format('d M Y, H:i') }}
                            </span></p>
                        @endif
                    </div>
                    <div>
                        <p>Payment method:
                            <span class="font-semibold">
                                {{ str_replace('_', ' ', ucfirst($adminText($order->payment_method ?? 'razorpay', 'razorpay'))) }}
                            </span>
                        </p>
                        <p>Payment status:
                            <span class="font-semibold">
                                {{ ucfirst($adminText($order->payment_status, '')) }}
                            </span>
                        </p>
                        @if(($order->payment_method ?? null) === 'pay_later' && !empty($order->payment_due_at))
                            <p class="mt-1">Due date:
                                <span class="font-medium">{{ $order->payment_due_at->format('d M Y') }}</span>
                            </p>
                        @endif
                        @if($order->razorpay_order_id)
                            <p class="mt-1">
                                Razorpay order:
                                <span class="font-mono">{{ $adminText($order->razorpay_order_id) }}</span>
                            </p>
                        @endif
                        @if($order->razorpay_payment_id)
                            <p>
                                Razorpay payment:
                                <span class="font-mono">{{ $adminText($order->razorpay_payment_id) }}</span>
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Status update form --}}
                <div class="pt-2 border-t border-gray-100 dark:border-gray-800 mt-2">
                    <form method="POST" action="{{ route('admin.orders.updateStatus', $order) }}"
                          class="flex flex-wrap items-center gap-2">
                        @csrf
                        @method('PATCH')
                        <label class="text-[11px] text-gray-600 dark:text-gray-300">
                            Update status:
                        </label>
                        <select name="status"
                                class="rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                            @foreach($availableStatuses as $status)
                                <option value="{{ $status }}" @selected($order->status === $status)>
                                    {{ ucfirst($status) }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit"
                                class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                            Save
                        </button>
                    </form>
                </div>
            </div>

            {{-- Delivery assignment --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3 space-y-3">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">Delivery assignment</p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400">
                            Assign this order to a delivery agent. Delivery agents see only orders assigned to them.
                        </p>
                    </div>
                    <span class="inline-flex items-center rounded-full border px-3 py-0.5 text-[10px]
                        @if(($order->delivery_status ?? 'pending') === 'delivered') border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200
                        @elseif(($order->delivery_status ?? 'pending') === 'out_for_delivery') border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-800 dark:bg-sky-900/30 dark:text-sky-200
                        @elseif(($order->delivery_status ?? 'pending') === 'failed') border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-800 dark:bg-rose-900/30 dark:text-rose-200
                        @elseif(($order->delivery_status ?? 'pending') === 'assigned') border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200
                        @else border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400
                        @endif">
                        {{ str_replace('_', ' ', ucfirst($adminText($order->delivery_status ?? 'pending'))) }}
                    </span>
                </div>

                @if($order->deliveryAgent)
                    <div class="grid gap-2 sm:grid-cols-2 text-[10px] text-gray-600 dark:text-gray-300">
                        <p>Agent: <span class="font-semibold">{{ $adminText($order->deliveryAgent->name) }}</span></p>
                        @if($order->deliveryAgent->phone)
                            <p>Phone: <a href="tel:{{ $adminText($order->deliveryAgent->phone) }}" class="font-semibold text-sky-700 dark:text-sky-300">{{ $adminText($order->deliveryAgent->phone) }}</a></p>
                        @endif
                        @if($order->out_for_delivery_at)
                            <p>Out for delivery: <span class="font-medium">{{ $order->out_for_delivery_at->format('d M Y, H:i') }}</span></p>
                        @endif
                        @if($order->delivered_at)
                            <p>Delivered: <span class="font-medium">{{ $order->delivered_at->format('d M Y, H:i') }}</span></p>
                        @endif
                        @if($order->deliveredBy)
                            <p>Delivered by: <span class="font-medium">{{ $adminText($order->deliveredBy->name) }}</span></p>
                        @endif
                        @if($order->delivery_failed_at)
                            <p>Failed at: <span class="font-medium">{{ $order->delivery_failed_at->format('d M Y, H:i') }}</span></p>
                        @endif
                    </div>
                @else
                    <p class="text-[10px] text-gray-500 dark:text-gray-400">No delivery agent assigned yet.</p>
                @endif

                @if($order->delivery_failure_reason)
                    <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-[10px] text-rose-800 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-200">
                        Could not deliver: {{ $adminText($order->delivery_failure_reason) }}
                    </div>
                @endif

                @if($order->delivery_note)
                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-[10px] text-gray-700 dark:border-gray-800 dark:bg-gray-950/60 dark:text-gray-300">
                        {{ $adminText($order->delivery_note) }}
                    </div>
                @endif

                @if(Route::has('admin.orders.delivery.assign') && auth()->user()?->hasAnyRole(['Admin', 'Manager', 'Stores']) && ! in_array($order->status, ['delivered', 'cancelled'], true))
                    <form method="POST" action="{{ route('admin.orders.delivery.assign', $order) }}" class="grid gap-2 sm:grid-cols-[1fr,1.2fr,auto]">
                        @csrf
                        @method('PATCH')
                        <select name="delivery_agent_id"
                                class="rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                            <option value="">Unassigned</option>
                            @foreach(($deliveryAgents ?? collect()) as $agent)
                                <option value="{{ $agent->id }}" @selected((int) $order->delivery_agent_id === (int) $agent->id)>
                                    {{ $agent->name }} @if($agent->phone) · {{ $agent->phone }} @endif
                                </option>
                            @endforeach
                        </select>
                        <input type="text" name="delivery_note" value="{{ old('delivery_note', $order->delivery_note) }}"
                               placeholder="Optional delivery note for agent"
                               class="rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                        <button type="submit"
                                class="inline-flex items-center justify-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                            Save assignment
                        </button>
                    </form>
                @endif

                @if(($order->deliveryEvents ?? collect())->count())
                    <details class="text-[10px] text-gray-600 dark:text-gray-300">
                        <summary class="cursor-pointer font-semibold text-gray-700 dark:text-gray-200">Delivery history</summary>
                        <div class="mt-2 space-y-1">
                            @foreach($order->deliveryEvents->take(10) as $event)
                                <div class="rounded-lg border border-gray-100 dark:border-gray-800 px-2 py-1.5">
                                    <span class="font-semibold">{{ str_replace('_', ' ', ucfirst($adminText($event->event_type))) }}</span>
                                    @if($event->old_status || $event->new_status)
                                        · {{ $adminText($event->old_status, '—') }} → {{ $adminText($event->new_status, '—') }}
                                    @endif
                                    · {{ optional($event->created_at)->format('d M Y, H:i') }}
                                    @if($event->user)
                                        · by {{ $adminText($event->user->name) }}
                                    @endif
                                    @if($event->note)
                                        <div class="mt-1 text-gray-500 dark:text-gray-400">{{ $adminText($event->note) }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endif
            </div>

            {{-- Addresses --}}
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-2 space-y-1">
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        Shipping address
                    </p>
                    @if($shippingAddress)
                        <p class="text-[11px] text-gray-700 dark:text-gray-200">
                            {{ $adminText($shippingAddress->full_name) }}
                        </p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400">
                            {{ $adminText($shippingAddress->address_line1) }}<br>
                            @if($shippingAddress->address_line2)
                                {{ $adminText($shippingAddress->address_line2) }}<br>
                            @endif
                            {{ $adminText($shippingAddress->city) }}, {{ $adminText($shippingAddress->state) }} {{ $adminText($shippingAddress->pincode) }}<br>
                            {{ $adminText($shippingAddress->country) }}
                        </p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">
                            Phone: {{ $adminText($shippingAddress->phone) }}
                            @if($shippingAddress->gstin)
                                <br>GSTIN: {{ $adminText($shippingAddress->gstin) }}
                            @endif
                        </p>
                    @else
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            No shipping address recorded.
                        </p>
                    @endif
                </div>

                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-2 space-y-1">
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        Billing address
                    </p>
                    @if($billingAddress)
                        <p class="text-[11px] text-gray-700 dark:text-gray-200">
                            {{ $adminText($billingAddress->full_name) }}
                        </p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400">
                            {{ $adminText($billingAddress->address_line1) }}<br>
                            @if($billingAddress->address_line2)
                                {{ $adminText($billingAddress->address_line2) }}<br>
                            @endif
                            {{ $adminText($billingAddress->city) }}, {{ $adminText($billingAddress->state) }} {{ $adminText($billingAddress->pincode) }}<br>
                            {{ $adminText($billingAddress->country) }}
                        </p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">
                            Phone: {{ $adminText($billingAddress->phone) }}
                            @if($billingAddress->gstin)
                                <br>GSTIN: {{ $adminText($billingAddress->gstin) }}
                            @endif
                        </p>
                    @else
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            No billing address recorded.
                        </p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Items table --}}
    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3 space-y-2">
        <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
            Order items
        </p>

        <div class="overflow-x-auto">
            <table class="min-w-full text-[11px]">
                <thead class="bg-gray-50 dark:bg-gray-950/40">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Item</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">SKU</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Qty</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Unit price</th>
                        {{-- <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Line total</th> --}}
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($order->items as $item)
                        @php
                            $unit = $item->unit_price;
                            $qty  = $item->quantity;
                            $lineTotal = $item->total ?: ($unit * $qty);
                        @endphp
                        <tr>
                            <td class="px-3 py-2 text-gray-900 dark:text-gray-50">
                                {{ $adminText($item->product_name) }}
                                @if($item->attributes_snapshot)
                                    @php
                                        $attrsRaw = $item->attributes_snapshot;

                                        $attrs = is_array($attrsRaw)
                                            ? $attrsRaw
                                            : (is_string($attrsRaw) ? json_decode($attrsRaw, true) : []);

                                        $attrs = is_array($attrs) ? $attrs : [];
                                    @endphp
                                    @if(is_array($attrs) && count($attrs))
                                        <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                            @foreach($attrs as $name => $value)
                                                <span>{{ $adminText($name) }}: {{ $adminText($value) }}</span>@if(!$loop->last), @endif
                                            @endforeach
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300">
                                {{ $adminText($item->sku ?? null) }}
                            </td>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                                {{ $adminText($qty) }}
                            </td>
                            <td class="px-3 py-2 text-right text-gray-900 dark:text-gray-50">
                                ₹{{ $adminMoney($unit) }}
                            </td>
                            {{-- <td class="px-3 py-2 text-right text-gray-900 dark:text-gray-50">
                                ₹{{ $adminMoney($lineTotal) }}
                            </td> --}}
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

        {{-- Totals and invoice / payments --}}
        <div class="space-y-3">
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3 space-y-2">
                <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                    Order totals
                </p>

                <div class="space-y-1 text-[11px] text-gray-700 dark:text-gray-200">
                    <div class="flex items-center justify-between">
                        <span>Subtotal</span>
                        <span>₹{{ $adminMoney($order->subtotal) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Discount</span>
                        <span>-₹{{ $adminMoney($order->discount_total) }}</span>
                    </div>
                    

                    @if((float) ($order->bandara_credit_discount_total ?? 0) > 0)
                        <div class="flex items-center justify-between text-emerald-700 dark:text-emerald-300">
                            <span>Bandara Credit</span>
                            <span>-₹{{ $adminMoney($order->bandara_credit_discount_total) }}</span>
                        </div>
                        @if((int) ($order->bandara_credit_points_redeemed ?? 0) > 0)
                            <div class="flex items-center justify-between text-[10px] text-emerald-600 dark:text-emerald-300/80">
                                <span>Points redeemed</span>
                                <span>{{ $adminInt($order->bandara_credit_points_redeemed) }} pts</span>
                            </div>
                        @endif
                    @endif

                    {{-- GST breakdown --}}
                    @if($order->gst_type === 'intra_state')
                        <div class="flex items-center justify-between text-[10px] text-gray-500 dark:text-gray-400 pt-1">
                            <span>CGST (2.5%)</span>
                            <span>₹{{ $adminMoney($order->cgst_amount) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-[10px] text-gray-500 dark:text-gray-400">
                            <span>SGST (2.5%)</span>
                            <span>₹{{ $adminMoney($order->sgst_amount) }}</span>
                        </div>
                    @elseif($order->gst_type === 'inter_state')
                        <div class="flex items-center justify-between text-[10px] text-gray-500 dark:text-gray-400 pt-1">
                            <span>IGST (5%)</span>
                            <span>₹{{ $adminMoney($order->igst_amount) }}</span>
                        </div>
                    @endif

                    {{-- <div class="flex items-center justify-between">
                        <span>Tax</span>
                        <span>₹{{ $adminMoney($order->tax_total) }}</span>
                    </div> --}}
                    <div class="flex items-center justify-between">
                        <span>Shipping</span>
                        <span>₹{{ $adminMoney($order->shipping_total) }}</span>
                    </div>


                    <div class="flex items-center justify-between font-semibold border-t border-dashed border-gray-200 dark:border-gray-700 pt-1 mt-1">
                        <span class="text-gray-900 dark:text-gray-50">
                            Grand total
                        </span>
                        <span class="text-gray-900 dark:text-gray-50">
                            ₹{{ $adminMoney($order->grand_total) }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                {{-- Invoice --}}
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3 space-y-1">
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        Invoice
                    </p>
                    @if($order->invoice)
                        <p class="text-[11px] text-gray-700 dark:text-gray-200">
                            {{ $adminText($order->invoice->invoice_number) }} ({{ ucfirst($adminText($order->invoice->status, '')) }})
                        </p>
                        <div class="flex flex-wrap gap-2 mt-1">
                            <a href="{{ route('admin.invoices.show', $order->invoice) }}"
                               class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1 text-[10px] text-gray-800 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">
                                View invoice
                            </a>
                            {{-- <a href="{{ route('admin.invoices.pdf', $order->invoice) }}" --}}
                                <a href="#"
                               class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1 text-[10px] text-gray-800 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">
                                Download PDF
                            </a>
                        </div>
                    @else
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            No invoice record for this order.
                        </p>
                    @endif
                </div>

                {{-- Payments --}}
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3 space-y-1">
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        Payments
                    </p>

                    @if($order->payments && $order->payments->count())
                        <ul class="space-y-1 text-[10px] text-gray-600 dark:text-gray-300">
                            @foreach($order->payments as $payment)
                                <li class="flex items-center justify-between">
                                    <span>
                                        {{ strtoupper($adminText($payment->method, '')) }} · {{ ucfirst($adminText($payment->status, '')) }}
                                        @if($payment->transaction_id)
                                            · <span class="font-mono">{{ $adminText($payment->transaction_id) }}</span>
                                        @endif
                                    </span>
                                    <span class="font-medium">
                                        ₹{{ $adminMoney($payment->amount) }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                        <a href="{{ route('admin.payments.index') }}"
                           class="inline-flex items-center mt-2 text-[10px] text-gray-500 dark:text-gray-400 hover:underline">
                            View all payments
                        </a>
                    @else
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            No payment records for this order yet.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    
</div>
@endsection
