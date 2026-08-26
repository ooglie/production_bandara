@props(['status'])

@php
    $resolved = $status instanceof \App\Enums\B2BApplicationStatus
        ? $status
        : \App\Enums\B2BApplicationStatus::tryFrom((string) $status);
@endphp

@if ($resolved)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset '.$resolved->badgeClasses()]) }}>
        {{ $resolved->label() }}
    </span>
@endif
