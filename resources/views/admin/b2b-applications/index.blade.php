@extends('layouts.company')

@section('title', 'B2B Applications')

@section('content')
@php
    $ui = (array) config('b2b_application_corrective.ui', []);
    $container = $ui['container'] ?? 'space-y-6';
    $panel = $ui['panel'] ?? 'border p-4';
    $panelCompact = $ui['panel_compact'] ?? 'border p-3';
    $heading = $ui['heading'] ?? 'text-2xl font-medium';
    $text = $ui['text'] ?? 'text-sm';
    $muted = $ui['muted'] ?? 'text-sm opacity-75';
    $labelClass = $ui['label'] ?? 'block text-sm';
    $field = $ui['field'] ?? 'block w-full';
    $primary = $ui['button_primary'] ?? 'inline-flex items-center px-4 py-2';
    $secondary = $ui['button_secondary'] ?? 'inline-flex items-center px-4 py-2';
    $link = $ui['link'] ?? '';
    $table = $ui['table'] ?? 'w-full';
    $tableHead = $ui['table_head'] ?? '';
    $tableCell = $ui['table_cell'] ?? 'p-2';
@endphp

<div class="{{ $container }}">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="{{ $heading }}">B2B Applications</h1>
            <p class="mt-1 {{ $muted }}">Review requests from existing and newly registered customer accounts.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="{{ $ui['alert_success'] ?? $panel }}">{{ session('success') }}</div>
    @endif

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ([
            'submitted' => 'Received',
            'under_review' => 'Under review',
            'more_information_required' => 'Information required',
            'approved' => 'Approved',
            'rejected' => 'Not approved',
        ] as $value => $label)
            <a href="{{ route('admin.b2b-applications.index', ['status' => $value]) }}" class="{{ $panelCompact }}">
                <p class="{{ $muted }}">{{ $label }}</p>
                <p class="mt-1 {{ $heading }}">{{ number_format((int) ($counts[$value] ?? 0)) }}</p>
            </a>
        @endforeach
    </section>

    <section class="{{ $panel }}">
        <form method="GET" action="{{ route('admin.b2b-applications.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <label class="xl:col-span-2">
                <span class="{{ $labelClass }}">Search</span>
                <input name="search" value="{{ request('search') }}" placeholder="Application, business, GSTIN…" class="{{ $field }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Status</span>
                <select name="status" class="{{ $field }}">
                    <option value="">All statuses</option>
                    @foreach (\App\Enums\B2BApplicationStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="{{ $labelClass }}">Business type</span>
                <select name="business_type" class="{{ $field }}">
                    <option value="">All types</option>
                    @foreach ((array) config('b2b_application.business_types', []) as $value => $label)
                        <option value="{{ $value }}" @selected(request('business_type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="{{ $labelClass }}">State</span>
                <select name="state_id" class="{{ $field }}">
                    <option value="">All states</option>
                    @foreach ($states as $state)
                        <option value="{{ $state->id }}" @selected((string) request('state_id') === (string) $state->id)>{{ $state->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="{{ $labelClass }}">Assigned to</span>
                <select name="assigned_to" class="{{ $field }}">
                    <option value="">Anyone</option>
                    <option value="unassigned" @selected(request('assigned_to') === 'unassigned')>Unassigned</option>
                    @foreach ($staff as $member)
                        <option value="{{ $member->id }}" @selected((string) request('assigned_to') === (string) $member->id)>{{ $member->name ?: $member->email }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex items-end gap-2 xl:col-span-6">
                <button class="{{ $primary }}">Apply filters</button>
                <a href="{{ route('admin.b2b-applications.index') }}" class="{{ $secondary }}">Reset</a>
            </div>
        </form>
    </section>

    <section class="{{ $panel }} overflow-x-auto">
        <table class="{{ $table }}">
            <thead class="{{ $tableHead }}">
                <tr>
                    <th class="{{ $tableCell }} text-left">Application</th>
                    <th class="{{ $tableCell }} text-left">Business</th>
                    <th class="{{ $tableCell }} text-left">Location</th>
                    <th class="{{ $tableCell }} text-left">Expected purchase</th>
                    <th class="{{ $tableCell }} text-left">Assigned</th>
                    <th class="{{ $tableCell }} text-left">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($applications as $application)
                    <tr>
                        <td class="{{ $tableCell }} align-top">
                            <a href="{{ route('admin.b2b-applications.show', $application) }}" class="{{ $link }}">{{ $application->application_number }}</a>
                            <p class="{{ $muted }}">{{ $application->submitted_at?->format('d M Y') ?? 'Draft' }}</p>
                        </td>
                        <td class="{{ $tableCell }} align-top">
                            <p class="{{ $text }}">{{ $application->legal_business_name }}</p>
                            <p class="{{ $muted }}">{{ config('b2b_application.business_types.'.$application->business_type, $application->business_type) }} · {{ $application->contact_first_name }} {{ $application->contact_last_name }}</p>
                        </td>
                        <td class="{{ $tableCell }} align-top">{{ $application->city_name }}, {{ $application->state_name }}</td>
                        <td class="{{ $tableCell }} align-top">{{ config('b2b_application.monthly_purchase_ranges.'.$application->estimated_monthly_purchase, 'Not provided') }}</td>
                        <td class="{{ $tableCell }} align-top">{{ $application->assignee?->name ?? $application->assignee?->email ?? 'Unassigned' }}</td>
                        <td class="{{ $tableCell }} align-top"><x-b2b.status-badge :status="$application->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="{{ $tableCell }} text-center">No B2B applications match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>

        @if ($applications->hasPages())
            <div class="mt-4">{{ $applications->links() }}</div>
        @endif
    </section>
</div>
@endsection
