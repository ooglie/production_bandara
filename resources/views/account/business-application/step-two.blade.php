@extends('layouts.customer')

@section('title', 'Business Purchase Requirements')

@section('content')
@php
    $ui = (array) config('b2b_application_corrective.ui', []);
    $container = $ui['container'] ?? 'space-y-6';
    $panel = $ui['panel'] ?? 'border p-4';
    $panelCompact = $ui['panel_compact'] ?? 'border p-3';
    $heading = $ui['heading'] ?? 'text-2xl font-medium';
    $subheading = $ui['subheading'] ?? 'text-lg font-medium';
    $text = $ui['text'] ?? 'text-sm';
    $muted = $ui['muted'] ?? 'text-sm opacity-75';
    $labelClass = $ui['label'] ?? 'block text-sm';
    $fieldClass = $ui['field'] ?? 'block w-full';
    $checkboxClass = $ui['checkbox'] ?? '';
    $primary = $ui['button_primary'] ?? 'inline-flex items-center px-4 py-2';
    $secondary = $ui['button_secondary'] ?? 'inline-flex items-center px-4 py-2';
    $selectedCategories = old('interested_categories', $application->interested_categories ?? []);
@endphp

<div class="{{ $container }}">
    <div>
        <p class="{{ $muted }}">Step 2 of 2</p>
        <h1 class="mt-1 {{ $heading }}">Purchase requirements</h1>
        <p class="mt-2 {{ $text }}">Tell us what your business needs so the Bandara team can review the application appropriately.</p>
    </div>

    @if ($application->status === \App\Enums\B2BApplicationStatus::MoreInformationRequired && $application->customer_message)
        <div class="{{ $ui['alert_info'] ?? $panel }}">
            <strong>Bandara requested additional information</strong>
            <p class="mt-1 {{ $text }}">{{ $application->customer_message }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('account.business-application.step-two.save') }}" class="space-y-6">
        @csrf

        <section class="{{ $panel }}">
            <h2 class="{{ $subheading }}">Product categories</h2>
            <p class="mt-1 {{ $muted }}">Select all categories relevant to your business.</p>
            <div class="mt-4 grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                @foreach ((array) config('b2b_application.product_categories', []) as $value => $label)
                    <label class="{{ $panelCompact }} flex items-start gap-2">
                        <input type="checkbox" name="interested_categories[]" value="{{ $value }}" @checked(in_array($value, $selectedCategories, true)) class="{{ $checkboxClass }}">
                        <span class="{{ $labelClass }}">{{ $label }}</span>
                    </label>
                @endforeach
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="{{ $labelClass }}">Expected monthly purchase</span>
                    <select name="estimated_monthly_purchase" class="{{ $fieldClass }}">
                        <option value="">Select a range</option>
                        @foreach ((array) config('b2b_application.monthly_purchase_ranges', []) as $value => $label)
                            <option value="{{ $value }}" @selected(old('estimated_monthly_purchase', $application->estimated_monthly_purchase) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="{{ $labelClass }}">Purchase frequency</span>
                    <select name="purchase_frequency" class="{{ $fieldClass }}">
                        <option value="">Select frequency</option>
                        @foreach ((array) config('b2b_application.purchase_frequencies', []) as $value => $label)
                            <option value="{{ $value }}" @selected(old('purchase_frequency', $application->purchase_frequency) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block md:col-span-2">
                    <span class="{{ $labelClass }}">Products, pack sizes or quantities required</span>
                    <textarea name="requirements_message" rows="6" class="{{ $fieldClass }}" placeholder="For example: salmon fillet, pork belly slabs and 20-piece dimsum packs every week.">{{ old('requirements_message', $application->requirements_message) }}</textarea>
                </label>
            </div>
        </section>

        <section class="{{ $panel }}">
            <label class="flex items-start gap-2">
                <input type="checkbox" name="terms_accepted" value="1" @checked(old('terms_accepted', (bool) $application->terms_accepted_at)) class="{{ $checkboxClass }}">
                <span class="{{ $text }}">I confirm that the information is accurate and understand that submission does not automatically grant B2B prices, credit or payment terms. Bandara may contact me for verification.</span>
            </label>
        </section>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('account.business-application.step-one') }}" class="{{ $secondary }}">Back to business details</a>
            <div class="flex flex-wrap gap-3">
                <button type="submit" name="intent" value="save" class="{{ $secondary }}">Save without submitting</button>
                <button type="submit" name="intent" value="submit" class="{{ $primary }}">Submit for review</button>
            </div>
        </div>
    </form>
</div>
@endsection
