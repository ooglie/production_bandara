@extends('layouts.customer')

@section('title', 'Business Account')

@section('content')
@php
    $ui = (array) config('b2b_application_corrective.ui', []);
    $container = $ui['container'] ?? 'space-y-6';
    $panel = $ui['panel'] ?? 'border p-4';
    $heading = $ui['heading'] ?? 'text-2xl font-medium';
    $subheading = $ui['subheading'] ?? 'text-lg font-medium';
    $text = $ui['text'] ?? 'text-sm';
    $muted = $ui['muted'] ?? 'text-sm opacity-75';
    $primary = $ui['button_primary'] ?? 'inline-flex items-center px-4 py-2';
    $secondary = $ui['button_secondary'] ?? 'inline-flex items-center px-4 py-2';
@endphp

<div class="{{ $container }}">
    <section class="{{ $panel }}">
        <p class="{{ $muted }}">Bandara for business</p>
        <h1 class="mt-2 {{ $heading }}">A business account for restaurants, hotels, retailers and professional kitchens</h1>
        <p class="mt-3 {{ $text }}">
            Apply for eligible business pricing, commercial quantities, GST invoices and approved payment terms. Every application is reviewed before B2B access is enabled.
        </p>

        <div class="mt-5 flex flex-wrap gap-3">
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
    </section>

    <div class="grid gap-4 md:grid-cols-3">
        <section class="{{ $panel }}">
            <h2 class="{{ $subheading }}">Who can apply</h2>
            <p class="mt-2 {{ $text }}">Restaurants, hotels, cafés, caterers, cloud kitchens, retailers, distributors, manufacturers and institutions.</p>
        </section>
        <section class="{{ $panel }}">
            <h2 class="{{ $subheading }}">What we review</h2>
            <p class="mt-2 {{ $text }}">Your business details, delivery location, product interests, expected quantities and purchase frequency.</p>
        </section>
        <section class="{{ $panel }}">
            <h2 class="{{ $subheading }}">When access changes</h2>
            <p class="mt-2 {{ $text }}">Your account remains a normal customer account until Bandara approves the application. Approval converts the same account to B2B.</p>
        </section>
    </div>

    <section class="{{ $panel }}">
        <h2 class="{{ $subheading }}">Already shop with Bandara?</h2>
        <p class="mt-2 {{ $text }}">
            Do not create another account. Sign in with your existing B2C customer account and submit the business application. Your addresses, orders and account history remain attached to the same login.
        </p>
        @guest
            <div class="mt-4">
                <a href="{{ route('business-account.login') }}" class="{{ $secondary }}">Sign in and convert an existing account</a>
            </div>
        @endguest
    </section>
</div>
@endsection
