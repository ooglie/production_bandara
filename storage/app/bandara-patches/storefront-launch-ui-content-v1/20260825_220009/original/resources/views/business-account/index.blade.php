@extends('layouts.customer')

@section('title', 'Business Account')

@section('content')
@php
    $ui = (array) config('b2b_application_corrective.ui', []);
    $heading = $ui['heading'] ?? 'text-2xl font-medium';
    $subheading = $ui['subheading'] ?? 'text-lg font-medium';
    $text = $ui['text'] ?? 'text-sm';
    $muted = $ui['muted'] ?? 'text-sm opacity-75';
    $primary = $ui['button_primary'] ?? 'inline-flex items-center px-4 py-2';
    $secondary = $ui['button_secondary'] ?? 'inline-flex items-center px-4 py-2';
@endphp

<div class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="max-w-3xl">
        <p class="{{ $muted }}">Bandara for business</p>
        <h1 class="mt-2 {{ $heading }}">Business accounts for restaurants, hotels, retailers and professional kitchens</h1>
        <p class="mt-4 {{ $text }}">
            Apply for eligible business pricing, commercial quantities, GST invoices and approved payment terms. Every application is reviewed before B2B access is enabled.
        </p>

        <div class="mt-6 flex flex-wrap gap-3">
            @auth
                @if ($isB2B)
                    <a href="{{ route('account.business-application.show') }}" class="{{ $primary }}">View business account</a>
                @elseif ($application)
                    <a href="{{ route('business-account.continue') }}" class="{{ $primary }}">
                        {{ $application->status->customerCanEdit() ? 'Continue business application' : 'View application status' }}
                    </a>
                @else
                    <a href="{{ route('business-account.continue') }}" class="{{ $primary }}">Apply using my existing account</a>
                @endif
            @else
                <a href="{{ route('business-account.register') }}" class="{{ $primary }}">Apply for a Business Account</a>
                <a href="{{ route('business-account.login') }}" class="{{ $secondary }}">Business Customer Login</a>
            @endauth
        </div>
    </div>

    <div class="mt-12 grid gap-8 md:grid-cols-3">
        <section>
            <h2 class="{{ $subheading }}">Who can apply</h2>
            <p class="mt-2 {{ $text }}">Restaurants, hotels, cafés, caterers, cloud kitchens, retailers, distributors, manufacturers and institutions.</p>
        </section>

        <section>
            <h2 class="{{ $subheading }}">What Bandara reviews</h2>
            <p class="mt-2 {{ $text }}">Business details, delivery location, product interests, expected quantities and purchase frequency.</p>
        </section>

        <section>
            <h2 class="{{ $subheading }}">When B2B access begins</h2>
            <p class="mt-2 {{ $text }}">The account remains a normal customer account until Bandara approves the application. Approval converts the same account to B2B.</p>
        </section>
    </div>

    <section class="mt-12 max-w-3xl">
        <h2 class="{{ $subheading }}">Already shop with Bandara?</h2>
        <p class="mt-3 {{ $text }}">
            Do not create another account. Sign in with the existing B2C customer account and submit the business application. Addresses, orders and account history remain attached to the same login.
        </p>
        @guest
            <div class="mt-5">
                <a href="{{ route('business-account.login') }}" class="{{ $secondary }}">Sign in and convert an existing account</a>
            </div>
        @endguest
    </section>

    <section class="mt-12 max-w-4xl">
        <h2 class="{{ $subheading }}">How the application works</h2>
        <div class="mt-5 grid gap-6 md:grid-cols-3">
            <div>
                <p class="{{ $muted }}">Step 1</p>
                <p class="mt-1 {{ $text }}">Use a new or existing Bandara customer login.</p>
            </div>
            <div>
                <p class="{{ $muted }}">Step 2</p>
                <p class="mt-1 {{ $text }}">Submit business details and product requirements.</p>
            </div>
            <div>
                <p class="{{ $muted }}">Step 3</p>
                <p class="mt-1 {{ $text }}">Bandara reviews the application and enables B2B access after approval.</p>
            </div>
        </div>
    </section>
</div>
@endsection
