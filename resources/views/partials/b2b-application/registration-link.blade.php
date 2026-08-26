@php
    $linkClass = (string) config('b2b_application_corrective.ui.link', '');
    $mutedClass = (string) config('b2b_application_corrective.ui.muted', '');
@endphp

<div class="mt-4 text-center">
    <a href="{{ route('business-account.register') }}" class="{{ $linkClass }}">Registering as a business? Create a Business Account</a>

    <p class="mt-2 {{ $mutedClass }}">
        Already have a Bandara customer account?
        <a href="{{ route('business-account.login') }}" class="{{ $linkClass }}">Sign in with the existing account</a>.
    </p>
</div>
