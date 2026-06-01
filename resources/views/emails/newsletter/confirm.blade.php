@php
    $name = $subscriber->name ?? 'there';
@endphp

<p>Hi {{ $name }},</p>

<p>Thank you for your interest in the Frozen - Bandara newsletter.</p>

<p>
    Please confirm your subscription by clicking the link below:
</p>

<p>
    <a href="{{ $confirmUrl }}">
        Confirm my subscription
    </a>
</p>

<p>
    If you did not request this, you can ignore this email,
    or unsubscribe here:
    <a href="{{ $unsubscribeUrl }}">Unsubscribe</a>
</p>

<p>Warm regards,<br>
Frozen - Bandara by Maytira</p>
