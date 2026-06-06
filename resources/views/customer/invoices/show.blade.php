@extends('layouts.customer')

@section('title', 'Invoice ' . $invoice->invoice_number)

@section('content')
@php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $order = $invoice->order;
    $shipping = $order?->addresses?->firstWhere('type', 'shipping');
    $billing  = $order?->addresses?->firstWhere('type', 'billing');

    $invoiceStatusMeta = function (?string $status) {
        $status = strtolower((string) $status);

        return match ($status) {
            'paid' => [
                'label' => 'Paid',
                'class' => 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-800',
            ],
            'past_due' => [
                'label' => 'Past due',
                'class' => 'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/40 dark:text-red-300 dark:border-red-800',
            ],
            'due' => [
                'label' => 'Due',
                'class' => 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:border-amber-800',
            ],
            'pending' => [
                'label' => 'Pending',
                'class' => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700',
            ],
            default => [
                'label' => Str::headline(str_replace('_', ' ', $status ?: 'Unknown')),
                'class' => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700',
            ],
        };
    };

    $orderStatusMeta = function (?string $status) {
        $status = strtolower((string) $status);

        return match ($status) {
            'processing' => [
                'label' => 'Processing',
                'class' => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-900/30 dark:text-sky-300 dark:border-sky-800',
            ],
            'shipped' => [
                'label' => 'Shipped',
                'class' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800',
            ],
            'delivered' => [
                'label' => 'Delivered',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800',
            ],
            'cancelled' => [
                'label' => 'Cancelled',
                'class' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800',
            ],
            default => [
                'label' => Str::headline($status ?: 'Unknown'),
                'class' => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700',
            ],
        };
    };

    $invoiceStatus = $invoiceStatusMeta($invoice->status ?? null);
    $orderStatus = $order ? $orderStatusMeta($order->status ?? null) : null;
    $paidAmount = (float) ($invoice->amount_paid ?? 0);
    $balanceAmount = (float) ($invoice->balance_amount ?? max(0, ($invoice->grand_total ?? 0) - $paidAmount));

    $pdfUrl = null;
    if (!empty($invoice->pdf_path)) {
        $pdfPath = trim((string) $invoice->pdf_path);

        if (Str::startsWith($pdfPath, ['http://', 'https://'])) {
            $pdfUrl = $pdfPath;
        } elseif (Str::startsWith($pdfPath, '/storage/')) {
            $pdfUrl = $pdfPath;
        } elseif (Str::startsWith($pdfPath, 'storage/')) {
            $pdfUrl = '/' . ltrim($pdfPath, '/');
        } elseif (Str::startsWith($pdfPath, 'storage/app/public/')) {
            $pdfUrl = '/storage/' . ltrim(Str::after($pdfPath, 'storage/app/public/'), '/');
        } else {
            $pdfUrl = Storage::disk('public')->url($pdfPath);
        }
    }
@endphp

<div class="max-w-6xl mx-auto px-4 py-6 space-y-4 text-xs">
    @if(session('status'))
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">
                    Invoice {{ $invoice->invoice_number }}
                </h1>

                <span class="inline-flex items-center rounded-sm border px-2 py-0.5 text-[10px] font-medium {{ $invoiceStatus['class'] }}">
                    {{ $invoiceStatus['label'] }}
                </span>
            </div>

            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                Order #{{ $order->order_number ?? '—' }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('invoices.index') }}"
               class="inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                Back to invoices
            </a>

            @if($order && Route::has('orders.show'))
                <a href="{{ route('orders.show', $order) }}"
                   class="inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                    View order
                </a>
            @endif
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-[2fr,1.4fr]">
        <div class="space-y-4">
            {{-- Billing / Shipping --}}
            <div class="border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-900 px-4 py-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <h2 class="text-[11px] font-semibold text-gray-800 dark:text-gray-100 mb-2 uppercase tracking-wide">
                        Billing address
                    </h2>

                    @if($billing)
                        <div class="space-y-0.5 text-[11px] text-gray-700 dark:text-gray-300">
                            <div>{{ $billing->full_name }}</div>
                            <div>{{ $billing->phone }}</div>
                            <div>{{ $billing->address_line1 }}</div>
                            @if($billing->address_line2)
                                <div>{{ $billing->address_line2 }}</div>
                            @endif
                            <div>{{ $billing->city }}, {{ $billing->state }} – {{ $billing->pincode }}</div>
                            <div>{{ $billing->country }}</div>
                            @if($billing->gstin)
                                <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                    GSTIN: {{ $billing->gstin }}
                                </div>
                            @endif
                        </div>
                    @else
                        <p class="text-[11px] text-gray-400 dark:text-gray-500">
                            No billing address stored.
                        </p>
                    @endif
                </div>

                <div>
                    <h2 class="text-[11px] font-semibold text-gray-800 dark:text-gray-100 mb-2 uppercase tracking-wide">
                        Shipping address
                    </h2>

                    @if($shipping)
                        <div class="space-y-0.5 text-[11px] text-gray-700 dark:text-gray-300">
                            <div>{{ $shipping->full_name }}</div>
                            <div>{{ $shipping->phone }}</div>
                            <div>{{ $shipping->address_line1 }}</div>
                            @if($shipping->address_line2)
                                <div>{{ $shipping->address_line2 }}</div>
                            @endif
                            <div>{{ $shipping->city }}, {{ $shipping->state }} – {{ $shipping->pincode }}</div>
                            <div>{{ $shipping->country }}</div>
                            @if($shipping->gstin)
                                <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                    GSTIN: {{ $shipping->gstin }}
                                </div>
                            @endif
                        </div>
                    @else
                        <p class="text-[11px] text-gray-400 dark:text-gray-500">
                            No shipping address stored.
                        </p>
                    @endif
                </div>
            </div>

            {{-- Line items --}}
            <div class="border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-900 px-4 py-4 space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-[11px] font-semibold text-gray-800 dark:text-gray-100 uppercase tracking-wide">
                        Line items
                    </h2>

                    <span class="text-[10px] text-gray-400">
                        {{ $invoice->items->count() }} item(s)
                    </span>
                </div>

                <div class="space-y-2">
                    @forelse($invoice->items as $item)
                        <div class="flex items-start justify-between gap-3 border-b border-gray-100 dark:border-gray-800 pb-2 last:border-b-0">
                            <div class="flex-1">
                                <div class="text-[11px] text-gray-900 dark:text-gray-50">
                                    {{ $item->description }}
                                </div>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                    Qty: {{ $item->quantity }} × ₹{{ number_format($item->unit_price, 2) }} <span class="text-[10px] text-gray-400">excl GST</span>
                                    @if((float) ($item->tax_amount ?? 0) > 0)
                                        · GST ₹{{ number_format($item->tax_amount, 2) }}
                                    @endif
                                </div>
                            </div>
                            <div class="text-right text-[11px] text-gray-900 dark:text-gray-50 font-medium">
                                ₹{{ number_format($item->total, 2) }}
                                <div class="text-[10px] font-normal text-gray-400">incl GST</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            No items recorded for this invoice.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Right summary column --}}
        <div class="space-y-4">
            <div class="border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-900 px-4 py-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-[11px] font-semibold text-gray-800 dark:text-gray-100 uppercase tracking-wide">
                        Invoice summary
                    </h2>

                    <span class="inline-flex items-center rounded-sm border px-2 py-0.5 text-[10px] font-medium {{ $invoiceStatus['class'] }}">
                        {{ $invoiceStatus['label'] }}
                    </span>
                </div>

                <dl class="space-y-2 text-[11px] text-gray-700 dark:text-gray-300">
                    <div class="flex justify-between gap-3">
                        <dt>Invoice #</dt>
                        <dd class="text-right text-gray-900 dark:text-gray-50 font-medium">{{ $invoice->invoice_number }}</dd>
                    </div>

                    <div class="flex justify-between gap-3">
                        <dt>Invoice date</dt>
                        <dd class="text-right">{{ optional($invoice->invoice_date)->format('d M Y') ?? '—' }}</dd>
                    </div>

                    <div class="flex justify-between gap-3">
                        <dt>Due date</dt>
                        <dd class="text-right">{{ optional($invoice->due_date)->format('d M Y') ?? '—' }}</dd>
                    </div>

                    <div class="flex justify-between gap-3">
                        <dt>Payment method</dt>
                        <dd class="text-right">{{ $invoice->payment_method_label }}</dd>
                    </div>

                    <div class="flex justify-between gap-3">
                        <dt>Payment status</dt>
                        <dd class="text-right">{{ $invoice->payment_status_label }}</dd>
                    </div>

                    <div class="flex justify-between gap-3">
                        <dt>Order #</dt>
                        <dd class="text-right text-gray-900 dark:text-gray-50 font-medium">{{ $order->order_number ?? '—' }}</dd>
                    </div>

                    @if($orderStatus)
                        <div class="flex justify-between gap-3 items-center">
                            <dt>Order status</dt>
                            <dd>
                                <span class="inline-flex items-center rounded-sm border px-2 py-0.5 text-[10px] font-medium {{ $orderStatus['class'] }}">
                                    {{ $orderStatus['label'] }}
                                </span>
                            </dd>
                        </div>
                    @endif
                </dl>

                <div class="border-t border-gray-200 dark:border-gray-800 pt-3 space-y-1 text-[11px] text-gray-700 dark:text-gray-300">
                    <div class="flex justify-between">
                        <span>Subtotal <span class="text-[10px] text-gray-400">excl GST</span></span>
                        <span>₹{{ number_format($invoice->subtotal, 2) }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span>Discount</span>
                        <span>- ₹{{ number_format($invoice->discount_total, 2) }}</span>
                    </div>

                    @if((float) ($invoice->delivery_fee ?? 0) > 0)
                        <div class="flex justify-between">
                            <span>Delivery fee <span class="text-[10px] text-gray-400">excl GST</span></span>
                            <span>₹{{ number_format($invoice->delivery_fee, 2) }}</span>
                        </div>
                    @endif

                    @if((float) ($invoice->handling_fee ?? 0) > 0)
                        <div class="flex justify-between">
                            <span>Cold-chain handling & packing <span class="text-[10px] text-gray-400">excl GST</span></span>
                            <span>₹{{ number_format($invoice->handling_fee, 2) }}</span>
                        </div>
                    @endif

                    <div class="flex justify-between">
                        <span>GST</span>
                        <span>₹{{ number_format($invoice->tax_total, 2) }}</span>
                    </div>

                    @if((float) ($invoice->bandara_credit_redeemed_amount ?? 0) > 0)
                        <div class="flex justify-between text-emerald-700 dark:text-emerald-300">
                            <span>Bandara Credit redeemed ({{ number_format((int) ($invoice->bandara_credit_redeemed_points ?? 0)) }} pts)</span>
                            <span>- ₹{{ number_format($invoice->bandara_credit_redeemed_amount, 2) }}</span>
                        </div>
                    @endif

                    <div class="flex justify-between font-semibold text-gray-900 dark:text-gray-50 pt-1">
                        <span>Grand total <span class="text-[10px] font-normal text-gray-400">incl GST</span></span>
                        <span>₹{{ number_format($invoice->grand_total, 2) }}</span>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-800 mt-2 pt-2 space-y-1">
                        <div class="flex justify-between">
                            <span>Paid</span>
                            <span>₹{{ number_format($paidAmount, 2) }}</span>
                        </div>
                        <div class="flex justify-between font-medium text-gray-900 dark:text-gray-50">
                            <span>Balance due</span>
                            <span>₹{{ number_format($balanceAmount, 2) }}</span>
                        </div>
                    </div>
                </div>

                @include('customer.invoices.partials.payment-widget', [
                    'invoice' => $invoice,
                    'balanceAmount' => $balanceAmount,
                ])

                @if($pdfUrl)
                    <div class="pt-3">
                        <a href="{{ $pdfUrl }}"
                           class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                            Download tax invoice (PDF)
                        </a>
                    </div>
                @else
                    <p class="pt-3 text-[10px] text-gray-400 dark:text-gray-500">
                        PDF download will be available once invoice generation is enabled.
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection