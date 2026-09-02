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
    $danger = $ui['button_danger'] ?? $secondary;
@endphp

<div class="{{ $container }}">
    <h1 class="{{ $heading }}">Business Account</h1>

    @if (! $application && $isB2B)
        <section class="{{ $panel }}">
            <h2 class="{{ $subheading }}">Your account is enabled for B2B purchasing</h2>
            <p class="mt-2 {{ $text }}">This account already had business access before the application workflow was introduced. Your existing access remains unchanged.</p>
        </section>
    @elseif ($application)
        <section class="{{ $panel }}">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="{{ $muted }}">{{ $application->application_number }}</p>
                    <h2 class="mt-1 {{ $subheading }}">{{ $application->legal_business_name }}</h2>
                    <p class="mt-1 {{ $muted }}">Submitted {{ $application->submitted_at?->format('d M Y, g:i a') ?? 'not yet submitted' }}</p>
                </div>
                <x-b2b.status-badge :status="$application->status" />
            </div>

            @if ($application->customer_message)
                <div class="mt-4 {{ $ui['alert_info'] ?? $panel }}">{{ $application->customer_message }}</div>
            @endif

            <div class="mt-4 flex flex-wrap gap-3">
                @if ($application->status->customerCanEdit())
                    <a href="{{ route('account.business-application.step-one') }}" class="{{ $primary }}">Edit business details</a>
                    <a href="{{ route('account.business-application.step-two') }}" class="{{ $secondary }}">Edit purchase requirements</a>
                @endif
                @if (in_array($application->status, [\App\Enums\B2BApplicationStatus::Rejected, \App\Enums\B2BApplicationStatus::Withdrawn], true))
                    <form method="POST" action="{{ route('account.business-application.restart') }}">
                        @csrf
                        <button class="{{ $primary }}">Reapply using these details</button>
                    </form>
                @endif
                @if ($application->status->customerCanWithdraw())
                    <form method="POST" action="{{ route('account.business-application.withdraw') }}" onsubmit="return confirm('Withdraw this application?')">
                        @csrf
                        <button class="{{ $danger }}">Withdraw application</button>
                    </form>
                @endif
            </div>
        </section>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="{{ $panel }}">
                <h3 class="{{ $subheading }}">Business details</h3>
                <dl class="mt-4 space-y-3 {{ $text }}">
                    @foreach ([
                        'Contact' => trim($application->contact_first_name.' '.$application->contact_last_name),
                        'Email' => $application->email,
                        'Phone' => $application->phone,
                        'Business type' => config('b2b_application.business_types.'.$application->business_type, $application->business_type),
                        'GSTIN' => $application->gstin ?: 'Not provided',
                        'FSSAI' => $application->fssai_number ?: 'Not provided',
                        'Location' => $application->city_name.', '.$application->state_name.' '.$application->postal_code,
                    ] as $label => $value)
                        <div>
                            <dt class="{{ $muted }}">{{ $label }}</dt>
                            <dd>{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section class="{{ $panel }}">
                <h3 class="{{ $subheading }}">Purchase requirements</h3>
                <dl class="mt-4 space-y-3 {{ $text }}">
                    <div>
                        <dt class="{{ $muted }}">Categories</dt>
                        <dd>{{ collect($application->interested_categories ?? [])->map(fn ($item) => config('b2b_application.product_categories.'.$item, $item))->join(', ') ?: 'Not provided' }}</dd>
                    </div>
                    <div>
                        <dt class="{{ $muted }}">Monthly purchase</dt>
                        <dd>{{ config('b2b_application.monthly_purchase_ranges.'.$application->estimated_monthly_purchase, 'Not provided') }}</dd>
                    </div>
                    <div>
                        <dt class="{{ $muted }}">Frequency</dt>
                        <dd>{{ config('b2b_application.purchase_frequencies.'.$application->purchase_frequency, 'Not provided') }}</dd>
                    </div>
                    <div>
                        <dt class="{{ $muted }}">Notes</dt>
                        <dd class="whitespace-pre-line">{{ $application->requirements_message ?: 'Not provided' }}</dd>
                    </div>
                </dl>
            </section>
        </div>

        @if ($application->status === \App\Enums\B2BApplicationStatus::Approved && $application->profile)
            <section class="{{ $panel }}">
                <h3 class="{{ $subheading }}">Approved commercial terms</h3>
                <dl class="mt-4 grid gap-4 md:grid-cols-3 {{ $text }}">
                    <div><dt class="{{ $muted }}">Pay later</dt><dd>{{ $application->profile->pay_later_enabled ? 'Enabled' : 'Not enabled' }}</dd></div>
                    <div><dt class="{{ $muted }}">Payment terms</dt><dd>{{ $application->profile->payment_terms_days }} days</dd></div>
                    <div><dt class="{{ $muted }}">Minimum order value</dt><dd>₹{{ number_format((float) $application->profile->minimum_order_value, 2) }}</dd></div>
                </dl>
            </section>
        @endif

        <section class="{{ $panel }}">
            <h3 class="{{ $subheading }}">Application timeline</h3>
            <ol class="mt-4 space-y-4">
                @forelse ($application->histories as $history)
                    <li>
                        <div class="flex flex-wrap items-center gap-2">
                            <strong class="{{ $text }}">{{ str($history->event)->replace('_', ' ')->title() }}</strong>
                            <time class="{{ $muted }}">{{ $history->created_at?->format('d M Y, g:i a') }}</time>
                        </div>
                        @if ($history->message)
                            <p class="mt-1 {{ $text }}">{{ $history->message }}</p>
                        @endif
                    </li>
                @empty
                    <li class="{{ $muted }}">No timeline entries yet.</li>
                @endforelse
            </ol>
        </section>
    @endif
</div>
@endsection
