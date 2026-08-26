@php
    $order = $invoice->order;
    $user  = $order?->user;
@endphp

<p>Dear {{ $user->name ?? 'Customer' }},</p>

<p>We’ve received your payment for invoice <strong>{{ $invoice->invoice_number }}</strong>
for order <strong>#{{ $order->order_number ?? '—' }}</strong>.</p>

<p>Amount paid: <strong>₹{{ number_format($invoice->grand_total, 2) }}</strong></p>

<p>Your tax invoice is attached again for your records. You can also download it anytime from your account.</p>

<p>Thank you for shopping with Frozen - Bandara by Maytira.</p>
