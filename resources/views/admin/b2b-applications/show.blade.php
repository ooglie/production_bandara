@extends('layouts.company')

@section('title', 'B2B Application '.$b2bApplication->application_number)

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
    $field = $ui['field'] ?? 'block w-full';
    $checkbox = $ui['checkbox'] ?? '';
    $primary = $ui['button_primary'] ?? 'inline-flex items-center px-4 py-2';
    $secondary = $ui['button_secondary'] ?? 'inline-flex items-center px-4 py-2';
    $danger = $ui['button_danger'] ?? $secondary;
    $link = $ui['link'] ?? '';
    $status = $b2bApplication->status;
    $canReview = in_array($status, [\App\Enums\B2BApplicationStatus::Submitted, \App\Enums\B2BApplicationStatus::UnderReview], true);
    $canApprove = $canReview;
    $canReject = in_array($status, [\App\Enums\B2BApplicationStatus::Submitted, \App\Enums\B2BApplicationStatus::UnderReview, \App\Enums\B2BApplicationStatus::MoreInformationRequired], true);
@endphp

<div class="{{ $container }}">
    @if (session('success'))
        <div class="{{ $ui['alert_success'] ?? $panel }}">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="{{ $ui['alert_error'] ?? $panel }}" role="alert">
            <p>Please correct the following:</p>
            <ul class="mt-2 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="{{ route('admin.b2b-applications.index') }}" class="{{ $link }}">← All B2B applications</a>
            <p class="mt-3 {{ $muted }}">{{ $b2bApplication->application_number }}</p>
            <h1 class="mt-1 {{ $heading }}">{{ $b2bApplication->legal_business_name }}</h1>
            <p class="mt-1 {{ $muted }}">{{ $b2bApplication->contact_first_name }} {{ $b2bApplication->contact_last_name }} · {{ $b2bApplication->email }} · {{ $b2bApplication->phone }}</p>
        </div>
        <x-b2b.status-badge :status="$status" />
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="space-y-6">
            <section class="{{ $panel }}">
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <h2 class="{{ $subheading }}">Business and registration</h2>
                        <dl class="mt-4 space-y-3 {{ $text }}">
                            @foreach ([
                                'Trading name' => $b2bApplication->trading_name ?: '—',
                                'Business type' => config('b2b_application.business_types.'.$b2bApplication->business_type, $b2bApplication->business_type),
                                'GST registered' => $b2bApplication->gst_registered ? 'Yes' : 'No',
                                'GSTIN' => $b2bApplication->gstin ?: '—',
                                'PAN' => $b2bApplication->pan ?: '—',
                                'FSSAI' => $b2bApplication->fssai_number ?: '—',
                                'Website' => $b2bApplication->website ?: '—',
                            ] as $label => $value)
                                <div><dt class="{{ $muted }}">{{ $label }}</dt><dd class="break-words">{{ $value }}</dd></div>
                            @endforeach
                        </dl>
                    </div>
                    <div>
                        <h2 class="{{ $subheading }}">Contact and delivery location</h2>
                        <dl class="mt-4 space-y-3 {{ $text }}">
                            @foreach ([
                                'Contact method' => ucfirst($b2bApplication->preferred_contact_method),
                                'WhatsApp' => $b2bApplication->whatsapp ?: '—',
                                'Address' => trim($b2bApplication->address_line_1.' '.$b2bApplication->address_line_2),
                                'City' => $b2bApplication->city_name,
                                'State' => $b2bApplication->state_name,
                                'PIN code' => $b2bApplication->postal_code,
                            ] as $label => $value)
                                <div><dt class="{{ $muted }}">{{ $label }}</dt><dd>{{ $value }}</dd></div>
                            @endforeach
                        </dl>
                    </div>
                </div>
            </section>

            <section class="{{ $panel }}">
                <h2 class="{{ $subheading }}">Purchase interest</h2>
                <dl class="mt-4 grid gap-4 md:grid-cols-3 {{ $text }}">
                    <div><dt class="{{ $muted }}">Categories</dt><dd>{{ collect($b2bApplication->interested_categories ?? [])->map(fn ($item) => config('b2b_application.product_categories.'.$item, $item))->join(', ') ?: 'Not provided' }}</dd></div>
                    <div><dt class="{{ $muted }}">Expected monthly purchase</dt><dd>{{ config('b2b_application.monthly_purchase_ranges.'.$b2bApplication->estimated_monthly_purchase, 'Not provided') }}</dd></div>
                    <div><dt class="{{ $muted }}">Frequency</dt><dd>{{ config('b2b_application.purchase_frequencies.'.$b2bApplication->purchase_frequency, 'Not provided') }}</dd></div>
                </dl>
                <div class="mt-4 {{ $panelCompact }}">
                    <p class="{{ $muted }}">Requirements</p>
                    <p class="mt-1 whitespace-pre-line {{ $text }}">{{ $b2bApplication->requirements_message ?: 'No additional requirements provided.' }}</p>
                </div>
            </section>

            @if ($b2bApplication->customer_message)
                <section class="{{ $ui['alert_info'] ?? $panel }}">
                    <h2 class="{{ $subheading }}">Current customer-visible message</h2>
                    <p class="mt-2 {{ $text }}">{{ $b2bApplication->customer_message }}</p>
                </section>
            @endif

            <section class="{{ $panel }}">
                <h2 class="{{ $subheading }}">Audit timeline</h2>
                <ol class="mt-4 space-y-4">
                    @forelse ($b2bApplication->histories as $history)
                        <li>
                            <div class="flex flex-wrap items-center gap-2">
                                <strong class="{{ $text }}">{{ str($history->event)->replace('_', ' ')->title() }}</strong>
                                <span class="{{ $ui['badge'] ?? '' }}">{{ $history->visibility }}</span>
                                <time class="{{ $muted }}">{{ $history->created_at?->format('d M Y, g:i a') }}</time>
                            </div>
                            <p class="{{ $muted }}">{{ $history->actor_label ?: 'System' }}</p>
                            @if ($history->message)<p class="mt-1 whitespace-pre-line {{ $text }}">{{ $history->message }}</p>@endif
                        </li>
                    @empty
                        <li class="{{ $muted }}">No audit entries.</li>
                    @endforelse
                </ol>
            </section>
        </div>

        <aside class="space-y-5">
            <section class="{{ $panel }}">
                <h2 class="{{ $subheading }}">Assignment</h2>
                <form method="POST" action="{{ route('admin.b2b-applications.assign', $b2bApplication) }}" class="mt-4 space-y-3">
                    @csrf
                    <label class="block">
                        <span class="{{ $labelClass }}">Reviewer</span>
                        <select name="assigned_to" class="{{ $field }}">
                            <option value="">Unassigned</option>
                            @foreach ($staff as $member)
                                <option value="{{ $member->id }}" @selected((string) $b2bApplication->assigned_to === (string) $member->id)>{{ $member->name ?: $member->email }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button class="{{ $secondary }} w-full">Update assignment</button>
                </form>
                @if ($status === \App\Enums\B2BApplicationStatus::Submitted)
                    <form method="POST" action="{{ route('admin.b2b-applications.start-review', $b2bApplication) }}" class="mt-3">
                        @csrf
                        <button class="{{ $primary }} w-full">Start review</button>
                    </form>
                @endif
            </section>

            <details class="{{ $panel }}" @if($status === \App\Enums\B2BApplicationStatus::MoreInformationRequired) open @endif>
                <summary class="{{ $subheading }} cursor-pointer">Request information</summary>
                <form method="POST" action="{{ route('admin.b2b-applications.request-information', $b2bApplication) }}" class="mt-4 space-y-3">
                    @csrf
                    <textarea name="customer_message" rows="5" class="{{ $field }}" placeholder="Explain exactly what the customer should add or correct." required>{{ old('customer_message') }}</textarea>
                    <button @disabled(!$canReview) class="{{ $primary }} w-full disabled:opacity-50">Send request</button>
                </form>
            </details>

            <details class="{{ $panel }}">
                <summary class="{{ $subheading }} cursor-pointer">Add internal note</summary>
                <form method="POST" action="{{ route('admin.b2b-applications.note', $b2bApplication) }}" class="mt-4 space-y-3">
                    @csrf
                    <textarea name="note" rows="4" class="{{ $field }}" placeholder="Visible only to staff." required>{{ old('note') }}</textarea>
                    <button class="{{ $secondary }} w-full">Add note</button>
                </form>
            </details>

            <details class="{{ $panel }}" @if($canApprove) open @endif>
                <summary class="{{ $subheading }} cursor-pointer">Approve business account</summary>
                <form method="POST" action="{{ route('admin.b2b-applications.approve', $b2bApplication) }}" class="mt-4 space-y-3">
                    @csrf
                    <label class="block"><span class="{{ $labelClass }}">Price group ID</span><input type="number" min="1" name="approved_price_group_id" value="{{ old('approved_price_group_id', $b2bApplication->approved_price_group_id) }}" class="{{ $field }}"></label>
                    <label class="flex items-start gap-2"><input type="checkbox" name="pay_later_enabled" value="1" @checked(old('pay_later_enabled', $b2bApplication->pay_later_enabled)) class="{{ $checkbox }}"><span class="{{ $labelClass }}">Enable pay later</span></label>
                    <label class="block"><span class="{{ $labelClass }}">Credit limit</span><input type="number" min="0" step="0.01" name="credit_limit" value="{{ old('credit_limit', $b2bApplication->credit_limit) }}" class="{{ $field }}"></label>
                    <label class="block"><span class="{{ $labelClass }}">Payment terms in days</span><input type="number" min="0" max="365" name="payment_terms_days" value="{{ old('payment_terms_days', $b2bApplication->payment_terms_days) }}" class="{{ $field }}"></label>
                    <label class="block"><span class="{{ $labelClass }}">Minimum order value</span><input type="number" min="0" step="0.01" name="minimum_order_value" value="{{ old('minimum_order_value', $b2bApplication->minimum_order_value) }}" class="{{ $field }}"></label>
                    <label class="block"><span class="{{ $labelClass }}">Account manager</span><select name="approved_account_manager_id" class="{{ $field }}"><option value="">Not assigned</option>@foreach ($staff as $member)<option value="{{ $member->id }}" @selected((string) old('approved_account_manager_id', $b2bApplication->approved_account_manager_id) === (string) $member->id)>{{ $member->name ?: $member->email }}</option>@endforeach</select></label>
                    <label class="block"><span class="{{ $labelClass }}">Delivery arrangement</span><textarea name="delivery_arrangement" rows="3" class="{{ $field }}">{{ old('delivery_arrangement', $b2bApplication->delivery_arrangement) }}</textarea></label>
                    <label class="block"><span class="{{ $labelClass }}">Approval message</span><textarea name="customer_message" rows="3" class="{{ $field }}">{{ old('customer_message', 'Your Bandara Business Account has been approved. You can now sign in to view business pricing and place B2B orders.') }}</textarea></label>
                    <button @disabled(!$canApprove) onclick="return confirm('Approve this application and convert the customer to B2B?')" class="{{ $primary }} w-full disabled:opacity-50">Approve and enable B2B</button>
                </form>
            </details>

            <details class="{{ $panel }}">
                <summary class="{{ $subheading }} cursor-pointer">Do not approve</summary>
                <form method="POST" action="{{ route('admin.b2b-applications.reject', $b2bApplication) }}" class="mt-4 space-y-3">
                    @csrf
                    <textarea name="customer_message" rows="4" class="{{ $field }}" placeholder="Use customer-friendly wording." required></textarea>
                    <button @disabled(!$canReject) onclick="return confirm('Update this application as not approved?')" class="{{ $danger }} w-full disabled:opacity-50">Update status</button>
                </form>
            </details>

            @if ($b2bApplication->profile)
                <section class="{{ $panel }}">
                    <h2 class="{{ $subheading }}">Active B2B profile</h2>
                    <dl class="mt-4 space-y-2 {{ $text }}">
                        <div class="flex justify-between gap-3"><dt>Pay later</dt><dd>{{ $b2bApplication->profile->pay_later_enabled ? 'Yes' : 'No' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt>Credit days</dt><dd>{{ $b2bApplication->profile->payment_terms_days }}</dd></div>
                        <div class="flex justify-between gap-3"><dt>Credit limit</dt><dd>₹{{ number_format((float) $b2bApplication->profile->credit_limit, 2) }}</dd></div>
                    </dl>
                </section>
            @endif
        </aside>
    </div>
</div>
@endsection
