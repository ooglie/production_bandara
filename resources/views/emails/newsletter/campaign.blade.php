@php
    $name = $subscriber->name ?? 'there';
@endphp

<p>Hi {{ $name }},</p>

{!! $campaign->content_html !!}

<hr>

<p style="font-size: 11px; color: #777;">
    You are receiving this email because you subscribed to the Frozen - Bandara newsletter.
    If you no longer wish to receive these emails, you can
    <a href="{{ $unsubscribeUrl }}">unsubscribe here</a>.
</p>
