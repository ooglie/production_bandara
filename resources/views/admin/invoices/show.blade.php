@extends('layouts.company')

@section('title', 'Invoice ' . $invoice->invoice_number)

@section('content')
@php
    $order = $invoice->order;
    $shipping = $order?->addresses?->firstWhere('type', 'shipping');
    $billing  = $order?->addresses?->firstWhere('type', 'billing');

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
    $paidAmount = (float) ($invoice->amount_paid ?? 0);
    $balanceAmount = (float) ($invoice->balance_amount ?? max(0, ($invoice->grand_total ?? 0) - $paidAmount));
@endphp

<div class="max-w-6xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Invoice {{ $adminText($invoice->invoice_number) }}
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[12px]
                    @if($invoice->status === 'paid')
                        bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300
                    @elseif($invoice->status === 'part_payment')
                        bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300
                    @elseif($invoice->status === 'past_due')
                        bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300
                    @elseif($invoice->status === 'due')
                        bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300
                    @else
                        bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200
                    @endif
                ">
                    {{ ucfirst(str_replace('_', ' ', $adminText($invoice->status, ''))) }}
                </span>
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Order #{{ $adminText($order?->order_number ?? null) }} ·
                Customer: {{ $adminText($order?->user?->name ?? null) }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            @can('manage sales')
                @if($balanceAmount > 0.00001)
                    <form method="POST" action="{{ route('admin.invoices.payment-form') }}">
                        @csrf
                        <input type="hidden" name="invoice_ids[]" value="{{ $invoice->id }}">
                        <input type="hidden" name="status" value="part_payment">
                        <button type="submit"
                                class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                            Record payment
                        </button>
                    </form>
                @endif
            @endcan
            <a href="{{ route('admin.invoices.index') }}"
               class="text-[11px] text-gray-500 dark:text-gray-400 underline">
                Back to invoices
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            {{ $adminText(session('status')) }}
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-[2fr,1.4fr]">
        {{-- Left: addresses + items --}}
        <div class="space-y-4">
            <div class="border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-900 px-4 py-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <h2 class="text-[11px] font-semibold text-gray-800 dark:text-gray-100 mb-1">
                        Billing address
                    </h2>
                    @if($billing)
                        <div class="space-y-0.5 text-[11px] text-gray-700 dark:text-gray-300">
                            <div>{{ $adminText($billing->full_name) }}</div>
                            <div>{{ $adminText($billing->phone) }}</div>
                            <div>{{ $adminText($billing->address_line1) }}</div>
                            @if($billing->address_line2)
                                <div>{{ $adminText($billing->address_line2) }}</div>
                            @endif
                            <div>{{ $adminText($billing->city) }}, {{ $adminText($billing->state) }} – {{ $adminText($billing->pincode) }}</div>
                            <div>{{ $adminText($billing->country) }}</div>
                            @if($billing->gstin)
                                <div class="text-[10px] text-gray-500 dark:text-gray-400">GSTIN: {{ $adminText($billing->gstin) }}</div>
                            @endif
                        </div>
                    @else
                        <p class="text-[11px] text-gray-400 dark:text-gray-500">No billing address stored.</p>
                    @endif
                </div>

                <div>
                    <h2 class="text-[11px] font-semibold text-gray-800 dark:text-gray-100 mb-1">
                        Shipping address
                    </h2>
                    @if($shipping)
                        <div class="space-y-0.5 text-[11px] text-gray-700 dark:text-gray-300">
                            <div>{{ $adminText($shipping->full_name) }}</div>
                            <div>{{ $adminText($shipping->phone) }}</div>
                            <div>{{ $adminText($shipping->address_line1) }}</div>
                            @if($shipping->address_line2)
                                <div>{{ $adminText($shipping->address_line2) }}</div>
                            @endif
                            <div>{{ $adminText($shipping->city) }}, {{ $adminText($shipping->state) }} – {{ $adminText($shipping->pincode) }}</div>
                            <div>{{ $adminText($shipping->country) }}</div>
                            @if($shipping->gstin)
                                <div class="text-[10px] text-gray-500 dark:text-gray-400">GSTIN: {{ $adminText($shipping->gstin) }}</div>
                            @endif
                        </div>
                    @else
                        <p class="text-[11px] text-gray-400 dark:text-gray-500">No shipping address stored.</p>
                    @endif
                </div>
            </div>

            <div class="border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-900 px-4 py-4 space-y-3">
                <h2 class="text-[11px] font-semibold text-gray-800 dark:text-gray-100">
                    Line items
                </h2>

                <div class="space-y-2">
                    @forelse($invoice->items as $item)
                        <div class="flex items-start justify-between gap-3 border-b border-gray-100 dark:border-gray-800 pb-2 last:border-b-0">
                            <div class="flex-1">
                                <div class="text-[11px] text-gray-900 dark:text-gray-50">
                                    {{ $adminText($item->description) }}
                                </div>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                    Qty: {{ $adminText($item->quantity) }} × ₹{{ $adminMoney($item->unit_price) }} <span class="text-[10px] text-gray-400">excl GST</span>
                                    @if((float) ($item->tax_amount ?? 0) > 0)
                                        · GST ₹{{ $adminMoney($item->tax_amount) }}
                                    @endif
                                </div>
                            </div>
                            <div class="text-right text-[11px] text-gray-900 dark:text-gray-50">
                                ₹{{ $adminMoney($item->total) }}
                                <div class="text-[10px] font-normal text-gray-400">incl GST</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            No invoice items recorded.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Right: totals + status --}}
        <div class="space-y-4">
            <div class="border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-900 px-4 py-4 space-y-3">
                <h2 class="text-[11px] font-semibold text-gray-800 dark:text-gray-100">
                    Invoice summary
                </h2>

                <dl class="space-y-1 text-[11px] text-gray-700 dark:text-gray-300">
                    <div class="flex justify-between">
                        <dt>Invoice #</dt>
                        <dd>{{ $adminText($invoice->invoice_number) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Order #</dt>
                        <dd>{{ $adminText($order?->order_number ?? null) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Invoice date</dt>
                        <dd>{{ optional($invoice->invoice_date)->format('d M Y') ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Due date</dt>
                        <dd>{{ optional($invoice->due_date)->format('d M Y') ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Payment method</dt>
                        <dd>{{ $invoice->payment_method_label }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Payment status</dt>
                        <dd>{{ $invoice->payment_status_label }}</dd>
                    </div>
                    {{-- <div class="flex justify-between">
                        <dt>Status</dt>
                        <dd>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px]
                                @if($invoice->status === 'paid')
                                    bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300
                                @elseif($invoice->status === 'past_due')
                                    bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300
                                @elseif($invoice->status === 'due')
                                    bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300
                                @else
                                    bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200
                                @endif
                            ">
                                {{ ucfirst(str_replace('_', ' ', $adminText($invoice->status, ''))) }}
                            </span>
                        </dd>
                    </div> --}}
                </dl>

                <div class="border-t border-gray-200 dark:border-gray-800 pt-3 space-y-1 text-[11px] text-gray-700 dark:text-gray-300">
                    <div class="flex justify-between">
                        <span>Subtotal <span class="text-[10px] text-gray-400">excl GST</span></span>
                        <span>₹{{ $adminMoney($invoice->subtotal) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Discount</span>
                        <span>- ₹{{ $adminMoney($invoice->discount_total) }}</span>
                    </div>
                    {{-- GST breakdown --}}
                    @if(($order?->gst_type ?? null) === 'intra_state')
                        <div class="flex justify-between">
                            <span>SGST</span>
                            <span>₹{{ $adminMoney($order?->sgst_amount ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>CGST</span>
                            <span>₹{{ $adminMoney($order?->cgst_amount ?? 0) }}</span>
                        </div>
                    @elseif(($order?->gst_type ?? null) === 'inter_state')
                        <div class="flex justify-between">
                            <span>IGST</span>
                            <span>₹{{ $adminMoney($order?->igst_amount ?? 0) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span>GST total</span>
                        <span>₹{{ $adminMoney($invoice->tax_total) }}</span>
                    </div>

                    @if((float) ($invoice->bandara_credit_discount_total ?? 0) > 0)
                        <div class="flex justify-between text-emerald-700 dark:text-emerald-300">
                            <span>Bandara Credit</span>
                            <span>- ₹{{ $adminMoney($invoice->bandara_credit_discount_total) }}</span>
                        </div>
                        @if((int) ($invoice->bandara_credit_points_redeemed ?? 0) > 0)
                            <div class="flex justify-between text-[10px] text-emerald-600 dark:text-emerald-300/80">
                                <span>Points redeemed</span>
                                <span>{{ $adminInt($invoice->bandara_credit_points_redeemed) }} pts</span>
                            </div>
                        @endif
                    @endif

                    <div class="flex justify-between font-semibold text-gray-900 dark:text-gray-50 pt-1">
                        <span>Grand total <span class="text-[10px] font-normal text-gray-400">incl GST</span></span>
                        <span>₹{{ $adminMoney($invoice->grand_total) }}</span>
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-800 mt-2 pt-2 space-y-1">
                        <div class="flex justify-between">
                            <span>Paid</span>
                            <span>₹{{ $adminMoney($paidAmount) }}</span>
                        </div>
                        <div class="flex justify-between font-medium text-gray-900 dark:text-gray-50">
                            <span>Balance due</span>
                            <span>₹{{ $adminMoney($balanceAmount) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- <div class="border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-900 px-4 py-4 space-y-3">
                <h2 class="text-[11px] font-semibold text-gray-800 dark:text-gray-100">
                    Update status
                </h2>

                <form method="POST" action="{{ route('admin.invoices.status', $invoice) }}" class="flex flex-col gap-2 text-[11px]">
                    @csrf
                    <select
                        name="status"
                        class="rounded-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                    >
                        @foreach(['pending', 'due', 'past_due', 'paid'] as $status)
                            <option value="{{ $status }}" @selected($invoice->status === $status)>
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </select>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200"
                    >
                        Save status
                    </button>
                </form>

                <p class="text-[10px] text-gray-400 dark:text-gray-500">
                    Payment status from Razorpay will later be used to auto‑move invoices to <strong>paid</strong>.
                </p>
            </div> --}}

            {{-- Payment history --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-3">
            {{-- <div class="flex items-center justify-between mb-2">
                <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                    Payment history
                </p>
            </div> --}}

            @if($invoice->payments->isEmpty())
                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                    No payments recorded for this invoice.
                    
                </p>
            @else
             <div class="flex items-center justify-between mb-2">
                <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                    Payment history
                </p>
            </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-[11px]">
                        <thead class="bg-gray-50 dark:bg-gray-950/40">
                            <tr>
                                <th class="px-3 py-1.5 text-left font-medium text-gray-500 dark:text-gray-400">Date</th>
                                <th class="px-3 py-1.5 text-left font-medium text-gray-500 dark:text-gray-400">Method</th>
                                <th class="px-3 py-1.5 text-left font-medium text-gray-500 dark:text-gray-400">Reference</th>
                                <th class="px-3 py-1.5 text-right font-medium text-gray-500 dark:text-gray-400">Applied amount</th>
                                <th class="px-3 py-1.5 text-left font-medium text-gray-500 dark:text-gray-400">Recorded by</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($invoice->payments as $payment)
                                @php
                                    $applied = optional($payment->pivot)->amount_applied ?? 0;
                                @endphp
                                <tr>
                                    <td class="px-3 py-1.5 text-gray-700 dark:text-gray-200">
                                        {{ optional($payment->paid_at ?? $payment->created_at)->format('d M Y, H:i') }}
                                    </td>
                                    <td class="px-3 py-1.5 text-gray-700 dark:text-gray-200">
                                        {{ ucfirst($adminText($payment->method, '')) }}
                                    </td>
                                    <td class="px-3 py-1.5 text-gray-600 dark:text-gray-300">
                                        {{ $adminText($payment->reference ?? $payment->transaction_id ?? null) }}
                                    </td>
                                    <td class="px-3 py-1.5 text-right text-gray-900 dark:text-gray-50">
                                        ₹{{ $adminMoney($applied) }}
                                    </td>
                                    <td class="px-3 py-1.5 text-gray-600 dark:text-gray-300">
                                        {{ $adminText($payment->recordedBy?->name, 'System') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if(($invoice->paymentSubmissions ?? collect())->isNotEmpty())
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        Customer-submitted payment details
                    </p>
                    @if(\Illuminate\Support\Facades\Route::has('admin.invoice-payment-submissions.index'))
                        <a href="{{ route('admin.invoice-payment-submissions.index', ['status' => 'pending']) }}" class="text-[10px] underline text-gray-500">Review all pending</a>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-[11px]">
                        <thead class="bg-gray-50 dark:bg-gray-950/40">
                            <tr>
                                <th class="px-3 py-1.5 text-left font-medium text-gray-500 dark:text-gray-400">Submitted</th>
                                <th class="px-3 py-1.5 text-left font-medium text-gray-500 dark:text-gray-400">Method</th>
                                <th class="px-3 py-1.5 text-left font-medium text-gray-500 dark:text-gray-400">Reference</th>
                                <th class="px-3 py-1.5 text-right font-medium text-gray-500 dark:text-gray-400">Amount</th>
                                <th class="px-3 py-1.5 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($invoice->paymentSubmissions as $submission)
                                <tr>
                                    <td class="px-3 py-1.5 text-gray-700 dark:text-gray-200">{{ optional($submission->created_at)->format('d M Y, H:i') }}</td>
                                    <td class="px-3 py-1.5 text-gray-700 dark:text-gray-200">{{ $submission->method_label }}</td>
                                    <td class="px-3 py-1.5 text-gray-600 dark:text-gray-300">{{ $adminText($submission->reference ?? $submission->cheque_number ?? null) }}</td>
                                    <td class="px-3 py-1.5 text-right text-gray-900 dark:text-gray-50">₹{{ $adminMoney($submission->amount) }}</td>
                                    <td class="px-3 py-1.5 text-gray-600 dark:text-gray-300">{{ $submission->status_label }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
        </div>
    </div>
</div>
@endsection
