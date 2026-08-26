@props(['status'])
@php
    $resolved = $status instanceof \App\Enums\B2BApplicationStatus
        ? $status
        : (is_string($status) ? \App\Enums\B2BApplicationStatus::tryFrom($status) : null);
@endphp
<span {{ $attributes->merge(['class' => config('b2b_application_corrective.ui.badge', '')]) }}>
    {{ $resolved?->label() ?? ucfirst(str_replace('_', ' ', (string) $status)) }}
</span>
