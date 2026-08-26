<div class="flex flex-wrap gap-3">
    <a href="{{ route('business-account.index') }}" class="{{ config('b2b_application_corrective.ui.link', '') }}">Business Customers</a>
    @auth
        <a href="{{ route('business-account.continue') }}" class="{{ config('b2b_application_corrective.ui.link', '') }}">Business Account</a>
    @else
        <a href="{{ route('business-account.login') }}" class="{{ config('b2b_application_corrective.ui.link', '') }}">Business Customer Login</a>
    @endauth
</div>
