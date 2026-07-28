@props(['context' => 'account'])

@php
    $copy = match ($context) {
        'checkout-b2b' => 'By confirming this order, you agree to Bandara’s Terms & Policies, including the B2B commercial terms.',
        'checkout-b2c' => 'By placing this order, you agree to Bandara’s Terms & Policies, including the delivery, cancellation and refund conditions.',
        default => 'By continuing, you agree to the Terms & Policies and acknowledge the Privacy Policy.',
    };
@endphp

<p {{ $attributes->class(['text-xs font-light leading-5 text-slate-500 dark:text-slate-400']) }}>
    {{ $copy }}
    <a href="{{ route('content.terms') }}" class="underline underline-offset-2 hover:text-slate-900 dark:hover:text-white">Terms & Policies</a>
    <span aria-hidden="true">·</span>
    <a href="{{ route('content.privacy') }}" class="underline underline-offset-2 hover:text-slate-900 dark:hover:text-white">Privacy Policy</a>
</p>
