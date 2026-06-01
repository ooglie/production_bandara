@extends('layouts.customer')

@section('title', 'Order details')

@section('content')
@php
    use Illuminate\Support\Str;

    $invoice = $order->invoice ?? null;

    $razorpayEnabled =
        config('services.razorpay.key')
        && config('services.razorpay.secret')
        && ($invoice && in_array(strtolower((string) $invoice->status), ['pending', 'due'], true))
        && strtolower((string) ($order->payment_status ?? 'pending')) === 'pending';

    $unitLabel = function (?string $u) {
        $u = strtolower((string) $u);

        return match ($u) {
            'kg' => 'kg',
            'pack' => 'pack',
            default => 'pc',
        };
    };

    $formatNumber = function ($value, int $decimals = 3) {
        $number = (float) ($value ?? 0);

        if (abs($number - round($number)) < 0.000001) {
            return number_format($number, 0, '.', '');
        }

        return rtrim(rtrim(number_format($number, $decimals, '.', ''), '0'), '.');
    };

    $orderStatusMeta = function (?string $status) {
        $status = strtolower((string) $status);

        return match ($status) {
            'processing' => [
                'label' => 'Processing',
                'class' => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-900/20 dark:text-sky-300 dark:border-sky-800',
            ],
            'shipped' => [
                'label' => 'Shipped',
                'class' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-300 dark:border-blue-800',
            ],
            'delivered' => [
                'label' => 'Delivered',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-300 dark:border-emerald-800',
            ],
            'cancelled' => [
                'label' => 'Cancelled',
                'class' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800',
            ],
            default => [
                'label' => Str::headline($status ?: 'Unknown'),
                'class' => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
            ],
        };
    };

    $invoiceStatusMeta = function ($invoice) {
        if (!$invoice || !($invoice->status ?? null)) {
            return [
                'label' => 'Not generated',
                'class' => 'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
            ];
        }

        $status = strtolower((string) $invoice->status);

        return match ($status) {
            'paid' => [
                'label' => 'Paid',
                'class' => 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800',
            ],
            'past_due' => [
                'label' => 'Past due',
                'class' => 'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800',
            ],
            'due' => [
                'label' => 'Due',
                'class' => 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800',
            ],
            'pending' => [
                'label' => 'Pending',
                'class' => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
            ],
            default => [
                'label' => Str::headline($status),
                'class' => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
            ],
        };
    };

    $orderStatus = $orderStatusMeta($order->status ?? null);
    $invoiceStatus = $invoiceStatusMeta($invoice);

    $items = collect($order->items ?? []);
    $itemsCount = $items->sum(fn ($item) => (float) ($item->quantity ?? 0));

    $lineTotal = function ($item) {
        // if (isset($item->total) && $item->total !== null) {
        //     return (float) $item->total;
        // }

        // if (isset($item->line_total) && $item->line_total !== null) {
        //     return (float) $item->line_total;
        // }

        // if (isset($item->total_amount) && $item->total_amount !== null) {
        //     return (float) $item->total_amount;
        // }

        // if (isset($item->subtotal) && $item->subtotal !== null) {
        //     return (float) $item->subtotal;
        // }

        // return (float) ($item->unit_price ?? 0) * (float) ($item->quantity ?? 0);
        return (float) $item->subtotal;
    };
@endphp

<div class="max-w-6xl mx-auto px-4 py-6 space-y-5">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <div class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-1 text-[10px] font-medium uppercase tracking-[0.14em] text-gray-600 dark:text-gray-300">
                Order details
            </div>

            <h1 class="mt-3 text-2xl font-semibold text-gray-900 dark:text-gray-50">
                {{ $order->order_number ?? ('#' . $order->id) }}
            </h1>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                Placed on {{ $order->created_at->format('d M Y, H:i') }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('orders.index') }}"
               class="inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-2 text-[11px] font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                Back to orders
            </a>

            @if($invoice)
                <a href="{{ route('orders.invoice', $order) }}"
                   class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-2 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                    Download invoice
                </a>
            @endif
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Order number</div>
            <div class="mt-1 text-sm font-semibold font-mono text-gray-900 dark:text-gray-50">
                {{ $order->order_number ?? ('#' . $order->id) }}
            </div>
        </div>

        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Status</div>
            <div class="mt-2">
                <span class="inline-flex items-center rounded-sm border px-2.5 py-0.5 text-[11px] font-medium {{ $orderStatus['class'] }}">
                    {{ $orderStatus['label'] }}
                </span>
            </div>
        </div>

        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Total quantity</div>
            <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">
                {{ $formatNumber($itemsCount) }}
            </div>
        </div>

        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Grand total</div>
            <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">
                ₹{{ number_format($order->grand_total ?? 0, 2) }}
            </div>
        </div>
    </div>

    {{-- Main content --}}
    <div class="grid gap-4 lg:grid-cols-[1.35fr,0.75fr]">
        {{-- Order summary --}}
        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 space-y-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                        Order summary
                    </h2>
                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        Review the products, quantities, and tax details for this order.
                    </p>
                </div>
            </div>

            <div class="space-y-3">
                @forelse($items as $item)
                    @php
                        $product = $products[$item->product_id] ?? null;
                        $sellUnit = strtolower((string) ($item->sell_unit ?? 'piece'));
                        $gstRate = (float) ($product->gst_rate ?? 0);
                        $variantLabel = $item->variant_label ?? null;
                        $itemLineTotal = $lineTotal($item);
                        $qtyText = $formatNumber($item->quantity ?? 0);
                        $weightText = $formatNumber($item->item_weight ?? 0);
                    @endphp

                    <div class="rounded-sm border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-4">
                        <div class="gap-4flex justify-between">
                            <div class="min-w-0">
                                <div class="flex items-start gap-3 flex justify-between">
                                    <div class="mt-0.5 inline-flex h-6 min-w-[24px] items-center justify-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-[10px] font-medium text-gray-500 dark:text-gray-300">
                                        {{ $loop->iteration }}
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-50">
                                            {{ $item->product_name ?? 'Item' }}
                                        </div>

                                        @if($variantLabel)
                                            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                                {{ $variantLabel }}
                                            </div>
                                        @endif

                                        <div class="mt-2 flex flex-wrap gap-2 text-[11px] flex justify-between">
                                            <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-0.5 text-gray-600 dark:text-gray-300">
                                                Qty {{ $qtyText }} 
                                                &nbsp;&nbsp;&nbsp;&nbsp;
                                                @if(!empty($item->item_weight))
                                                    Wt {{ $weightText }}
                                                @endif
                                                &nbsp;&nbsp;&nbsp;&nbsp;
                                                 GST {{ $formatNumber($gstRate, 2) }}%
                                                &nbsp;&nbsp;&nbsp;&nbsp;
                                                 Price per {{ $unitLabel($sellUnit) }} ₹{{ number_format($item->unit_price ?? 0, 2) }} 
                                            </span>

                                            {{-- @if(!empty($item->item_weight))
                                                <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-0.5 text-gray-600 dark:text-gray-300">
                                                    Wt {{ $weightText }}
                                                </span>
                                            @endif

                                            <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-0.5 text-gray-600 dark:text-gray-300">
                                                GST {{ $formatNumber($gstRate, 2) }}%
                                            </span>
                                            
                                            <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-0.5 text-gray-600 dark:text-gray-300">
                                                Price per {{ $unitLabel($sellUnit) }} ₹{{ number_format($item->unit_price ?? 0, 2) }} 
                                            </span> --}}

                                            <span class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50 text-left sm:text-right">
                                                 ₹{{ number_format($itemLineTotal, 2) }}
                                            </span>


                                            @if(!empty($product?->product_weight))
                                                {{-- <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-0.5 text-gray-600 dark:text-gray-300">
                                                    Pack wt {{ $formatNumber($product->product_weight) }}
                                                </span> --}}
                                            @endif
                                        </div>
                                    </div>
                                    
                                </div>
                                
                                

                            </div>

                            {{-- <div class="text-left sm:text-right"> --}}
                                {{-- <div class="text-[10px] uppercase tracking-wide text-gray-400 sm:text-right">
                                    Price per {{ $unitLabel($sellUnit) }}
                                </div>
                                <div class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-50">
                                    ₹{{ number_format($item->unit_price ?? 0, 2) }} 
                                </div> --}}

                                {{-- <div class="mt-3 text-[10px] uppercase tracking-wide text-gray-400">
                                    Line total
                                </div> --}}
                                {{-- <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50 text-left sm:text-right">
                                    ₹{{ number_format($itemLineTotal, 2) }}
                                </div> --}}
                            {{-- </div> --}}
                        </div>
                        
                    </div>
                @empty
                    <div class="rounded-sm border border-dashed border-gray-300 dark:border-gray-700 px-4 py-5 text-sm text-gray-500 dark:text-gray-400">
                        No items found.
                    </div>
                @endforelse
            </div>

            {{-- Totals --}}
            <div class="rounded-sm border border-gray-100 dark:border-gray-800 bg-gray-100 dark:bg-black px-4 py-4 space-y-2 text-sm">
                <div class="flex justify-between text-gray-700 dark:text-gray-300">
                    <span>Subtotal</span>
                    <span>₹{{ number_format($order->subtotal ?? 0, 2) }}</span>
                </div>

                @if(!empty($order->discount_total))
                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>Discount</span>
                        <span>- ₹{{ number_format($order->discount_total, 2) }}</span>
                    </div>
                @endif

                @if($order->gst_type === 'intra_state')
                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>SGST</span>
                        <span>₹{{ number_format($order->sgst_amount ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>CGST</span>
                        <span>₹{{ number_format($order->cgst_amount ?? 0, 2) }}</span>
                    </div>
                @elseif($order->gst_type === 'inter_state')
                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>IGST</span>
                        <span>₹{{ number_format($order->igst_amount ?? 0, 2) }}</span>
                    </div>
                @endif

                @if((float) ($order->bandara_credit_redeemed_amount ?? 0) > 0)
                    <div class="flex justify-between text-emerald-700 dark:text-emerald-300">
                        <span>Bandara Credit redeemed ({{ number_format((int) ($order->bandara_credit_redeemed_points ?? 0)) }} pts)</span>
                        <span>- ₹{{ number_format($order->bandara_credit_redeemed_amount, 2) }}</span>
                    </div>
                @endif

                <div class="border-t border-gray-100 dark:border-gray-800 pt-2 flex justify-between font-semibold text-gray-900 dark:text-gray-50">
                    <span>Grand total</span>
                    <span>₹{{ number_format($order->grand_total ?? 0, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Invoice / payment card --}}
        <div class="rounded-sm border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-50">
                    Invoice & payment
                </h2>
            </div>

            @if($invoice)
                <div class="relative rounded-sm border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-4 pr-24">
                    <div class="text-[10px] uppercase tracking-wide text-gray-400">Invoice number</div>
                    <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">
                        {{ $invoice->invoice_number ?? $invoice->id }}
                    </div>

                    <div class="mt-3 text-[10px] uppercase tracking-wide text-gray-400">Invoice date</div>
                    <div class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                        {{ ($invoice->created_at ?? $order->created_at)->format('d M Y') }}
                    </div>

                    <div class="mt-3 text-[10px] uppercase tracking-wide text-gray-400">Invoice total</div>
                    <div class="mt-1 text-base font-semibold text-gray-900 dark:text-gray-50">
                        ₹{{ number_format($invoice->total_amount ?? $order->grand_total ?? 0, 2) }}
                    </div>

                    <div class="mt-3 text-[10px] uppercase tracking-wide text-gray-400">Payment method</div>
                    <div class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                        {{ ($order->payment_method ?? 'razorpay') === 'pay_later' ? 'Pay Later on invoice' : 'Pay Now / Razorpay' }}
                        @if(($order->payment_method ?? 'razorpay') === 'pay_later' && !empty($order->payment_due_at))
                            · Due {{ $order->payment_due_at->format('d M Y') }}
                        @endif
                    </div>

                    <span class="absolute right-3 top-3 inline-flex items-center rounded-sm border px-2 py-0.5 text-[10px] font-medium {{ $invoiceStatus['class'] }}">
                        {{ $invoiceStatus['label'] }}
                    </span>
                </div>

                @if($razorpayEnabled)
                    <div class="rounded-sm border border-amber-100 dark:border-amber-900/40 bg-gray-50 dark:bg-amber-950/20 px-4 py-4">
                        <div class="text-sm font-medium text-amber-800 dark:text-amber-200">
                            Payment pending
                        </div>
                        <p class="mt-1 text-[11px] text-amber-700 dark:text-amber-300">
                            @if(($order->payment_method ?? 'razorpay') === 'pay_later')
                                This order was placed on Pay Later terms. You can still pay this invoice online when ready.
                                @if(!empty($order->payment_due_at)) Due date: {{ $order->payment_due_at->format('d M Y') }}. @endif
                            @else
                                Your invoice is still pending. Complete the payment securely through Razorpay.
                            @endif
                        </p>

                        <div class="mt-3">
                            <a href="{{ route('orders.pay.razorpay', $order) }}"
                               class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                                Pay now with Razorpay
                            </a>
                        </div>
                    </div>
                @endif

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('orders.invoice', $order) }}"
                       class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                        Download invoice (PDF)
                    </a>
                </div>
            @else
                <div class="rounded-sm border border-dashed border-gray-300 dark:border-gray-700 px-4 py-5 text-sm text-gray-500 dark:text-gray-400">
                    Invoice has not been generated yet. You’ll be able to download it once your order is processed.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection