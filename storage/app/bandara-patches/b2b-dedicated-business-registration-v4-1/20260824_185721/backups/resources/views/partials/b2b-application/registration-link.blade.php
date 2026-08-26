@php
    $intentKey = (string) config('b2b_application_corrective.entry_intent.session_key', 'bandara.business_account_intent');
    $businessRegistrationSelected = session()->has($intentKey);
    $linkClass = (string) config('b2b_application_corrective.ui.link', '');
    $mutedClass = (string) config('b2b_application_corrective.ui.muted', '');
@endphp

<div class="mt-4 text-center">
    @if ($businessRegistrationSelected)
        <p class="{{ $mutedClass }}">Business Account application selected. Complete the existing registration form to continue.</p>
    @else
        <a href="{{ route('business-account.register') }}" class="{{ $linkClass }}">Registering as a business? Apply for a Business Account</a>
    @endif

    <p class="mt-2 {{ $mutedClass }}">
        Already have a Bandara customer account?
        <a href="{{ route('business-account.login') }}" class="{{ $linkClass }}">Sign in with the existing account</a>.
    </p>
</div>
