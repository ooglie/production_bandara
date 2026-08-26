@php
    $intentKey = (string) config('b2b_application_corrective.entry_intent.session_key', 'bandara.business_account_intent');
    $businessRegistrationSelected = session()->has($intentKey);
@endphp
<div class="mt-4 {{ config('b2b_application_corrective.ui.panel_compact', '') }}">
    @if ($businessRegistrationSelected)
        <p class="{{ config('b2b_application_corrective.ui.text', '') }}">
            Business registration selected. Complete the existing registration form above; after registration you will continue to the Business Account application.
        </p>
    @else
        <p class="{{ config('b2b_application_corrective.ui.text', '') }}">Registering for a restaurant, hotel, retailer or other business?</p>
        <a href="{{ route('business-account.register') }}" class="{{ config('b2b_application_corrective.ui.link', '') }}">Start business registration</a>
    @endif
    <p class="mt-2 {{ config('b2b_application_corrective.ui.muted', '') }}">
        Already have a Bandara customer account?
        <a href="{{ route('business-account.login') }}" class="{{ config('b2b_application_corrective.ui.link', '') }}">Use the existing account</a> instead of registering again.
    </p>
</div>
