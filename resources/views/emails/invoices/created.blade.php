@php
    $order = $invoice->order;
    $user  = $order?->user;
@endphp

<p>Dear {{ $user->name ?? 'Customer' }},</p>

<p>Thank you for your order <strong>#{{ $order->order_number ?? '—' }}</strong>.</p>

<p>Your tax invoice <strong>{{ $invoice->invoice_number }}</strong> is attached to this email.</p>

<p>Total amount: <strong>₹{{ number_format($invoice->grand_total, 2) }}</strong></p>

<p>You can also view this invoice anytime in your account under <strong>Invoices</strong>.</p>

<p>Best regards,<br>
Frozen - Bandara by Maytira</p>
