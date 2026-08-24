<x-layouts.business-account title="B2B application {{ $b2bApplication->application_number }}" heading="B2B application">
    @php
        $status = $b2bApplication->status;
        $field = 'mt-1 block w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white';
        $canReview = in_array($status, [\App\Enums\B2BApplicationStatus::Submitted, \App\Enums\B2BApplicationStatus::UnderReview], true);
        $canApprove = $canReview;
        $canReject = in_array($status, [\App\Enums\B2BApplicationStatus::Submitted, \App\Enums\B2BApplicationStatus::UnderReview, \App\Enums\B2BApplicationStatus::MoreInformationRequired], true);
    @endphp

    <div class="space-y-6">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-200"><p class="font-medium">Please correct the following:</p><ul class="mt-2 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <a href="{{ route('admin.b2b-applications.index') }}" class="text-sm text-sky-700 hover:underline dark:text-sky-300">← All B2B applications</a>
                <p class="mt-4 text-xs uppercase tracking-[0.16em] text-slate-500">{{ $b2bApplication->application_number }}</p>
                <h2 class="mt-2 text-2xl font-light text-slate-950 dark:text-white">{{ $b2bApplication->legal_business_name }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $b2bApplication->contact_first_name }} {{ $b2bApplication->contact_last_name }} · {{ $b2bApplication->email }} · {{ $b2bApplication->phone }}</p>
            </div>
            <x-b2b.status-badge :status="$status" />
        </div>

        <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
            <div class="space-y-6">
                <section class="grid gap-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-2 dark:border-slate-800 dark:bg-slate-900">
                    <div>
                        <h3 class="font-medium text-slate-950 dark:text-white">Business and registration</h3>
                        <dl class="mt-5 space-y-3 text-sm">
                            @foreach ([
                                'Trading name' => $b2bApplication->trading_name ?: '—',
                                'Business type' => config('b2b_application.business_types.'.$b2bApplication->business_type, $b2bApplication->business_type),
                                'GST registered' => $b2bApplication->gst_registered ? 'Yes' : 'No',
                                'GSTIN' => $b2bApplication->gstin ?: '—',
                                'PAN' => $b2bApplication->pan ?: '—',
                                'FSSAI' => $b2bApplication->fssai_number ?: '—',
                                'Website' => $b2bApplication->website ?: '—',
                            ] as $label => $value)<div class="grid grid-cols-[8rem_1fr] gap-3"><dt class="text-slate-500">{{ $label }}</dt><dd class="break-words text-slate-800 dark:text-slate-200">{{ $value }}</dd></div>@endforeach
                        </dl>
                    </div>
                    <div>
                        <h3 class="font-medium text-slate-950 dark:text-white">Contact and delivery location</h3>
                        <dl class="mt-5 space-y-3 text-sm">
                            @foreach ([
                                'Contact method' => ucfirst($b2bApplication->preferred_contact_method),
                                'WhatsApp' => $b2bApplication->whatsapp ?: '—',
                                'Address' => trim($b2bApplication->address_line_1.' '.$b2bApplication->address_line_2),
                                'City' => $b2bApplication->city_name,
                                'State' => $b2bApplication->state_name,
                                'PIN code' => $b2bApplication->postal_code,
                            ] as $label => $value)<div class="grid grid-cols-[8rem_1fr] gap-3"><dt class="text-slate-500">{{ $label }}</dt><dd class="text-slate-800 dark:text-slate-200">{{ $value }}</dd></div>@endforeach
                        </dl>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="font-medium text-slate-950 dark:text-white">Purchase interest</h3>
                    <div class="mt-5 grid gap-5 md:grid-cols-3">
                        <div><p class="text-xs uppercase tracking-[0.12em] text-slate-500">Categories</p><p class="mt-2 text-sm leading-6 text-slate-800 dark:text-slate-200">{{ collect($b2bApplication->interested_categories ?? [])->map(fn ($item) => config('b2b_application.product_categories.'.$item, $item))->join(', ') ?: 'Not provided' }}</p></div>
                        <div><p class="text-xs uppercase tracking-[0.12em] text-slate-500">Expected monthly purchase</p><p class="mt-2 text-sm text-slate-800 dark:text-slate-200">{{ config('b2b_application.monthly_purchase_ranges.'.$b2bApplication->estimated_monthly_purchase, 'Not provided') }}</p></div>
                        <div><p class="text-xs uppercase tracking-[0.12em] text-slate-500">Frequency</p><p class="mt-2 text-sm text-slate-800 dark:text-slate-200">{{ config('b2b_application.purchase_frequencies.'.$b2bApplication->purchase_frequency, 'Not provided') }}</p></div>
                    </div>
                    <div class="mt-5 rounded-lg bg-slate-50 p-4 text-sm leading-6 text-slate-700 dark:bg-slate-950/60 dark:text-slate-300"><p class="text-xs uppercase tracking-[0.12em] text-slate-500">Requirements</p><p class="mt-2 whitespace-pre-line">{{ $b2bApplication->requirements_message ?: 'No additional requirements provided.' }}</p></div>
                </section>

                @if ($b2bApplication->customer_message)
                    <section class="rounded-xl border border-sky-200 bg-sky-50 p-5 dark:border-sky-900 dark:bg-sky-950/40">
                        <h3 class="font-medium text-sky-950 dark:text-sky-100">Current customer-visible message</h3>
                        <p class="mt-2 text-sm leading-6 text-sky-900 dark:text-sky-200">{{ $b2bApplication->customer_message }}</p>
                    </section>
                @endif

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="font-medium text-slate-950 dark:text-white">Audit timeline</h3>
                    <ol class="mt-6 space-y-5">
                        @forelse ($b2bApplication->histories as $history)
                            <li class="relative pl-7 before:absolute before:left-[0.32rem] before:top-2 before:h-full before:w-px before:bg-slate-200 last:before:hidden dark:before:bg-slate-700">
                                <span class="absolute left-0 top-1.5 h-3 w-3 rounded-full border-2 border-white {{ $history->visibility === 'customer' ? 'bg-sky-500' : 'bg-slate-400' }} ring-1 ring-slate-200 dark:border-slate-900 dark:ring-slate-700"></span>
                                <div class="flex flex-wrap items-center gap-2"><p class="text-sm font-medium text-slate-900 dark:text-white">{{ str($history->event)->replace('_', ' ')->title() }}</p><span class="rounded-full bg-slate-100 px-2 py-0.5 text-[0.65rem] uppercase tracking-wide text-slate-500 dark:bg-slate-800">{{ $history->visibility }}</span><time class="text-xs text-slate-500">{{ $history->created_at?->format('d M Y, g:i a') }}</time></div>
                                <p class="mt-1 text-xs text-slate-500">{{ $history->actor_label ?: 'System' }}</p>
                                @if ($history->message)<p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $history->message }}</p>@endif
                            </li>
                        @empty
                            <li class="text-sm text-slate-500">No audit entries.</li>
                        @endforelse
                    </ol>
                </section>
            </div>

            <aside class="space-y-5">
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="font-medium text-slate-950 dark:text-white">Assignment</h3>
                    <form method="POST" action="{{ route('admin.b2b-applications.assign', $b2bApplication) }}" class="mt-4 space-y-3">@csrf<label class="block text-sm text-slate-600 dark:text-slate-300">Reviewer<select name="assigned_to" class="{{ $field }}"><option value="">Unassigned</option>@foreach ($staff as $member)<option value="{{ $member->id }}" @selected((string) $b2bApplication->assigned_to === (string) $member->id)>{{ $member->name ?: $member->email }}</option>@endforeach</select></label><button class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-700 dark:text-slate-200">Update assignment</button></form>
                    @if ($status === \App\Enums\B2BApplicationStatus::Submitted)
                        <form method="POST" action="{{ route('admin.b2b-applications.start-review', $b2bApplication) }}" class="mt-3">@csrf<button class="w-full rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600">Start review</button></form>
                    @endif
                </section>

                <details class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm open:ring-1 open:ring-sky-200 dark:border-slate-800 dark:bg-slate-900 dark:open:ring-sky-900" @if($status === \App\Enums\B2BApplicationStatus::MoreInformationRequired) open @endif>
                    <summary class="cursor-pointer font-medium text-slate-950 dark:text-white">Request information</summary>
                    <form method="POST" action="{{ route('admin.b2b-applications.request-information', $b2bApplication) }}" class="mt-4 space-y-3">@csrf<textarea name="customer_message" rows="5" class="{{ $field }}" placeholder="Explain exactly what the customer should add or correct." required>{{ old('customer_message') }}</textarea><button @disabled(!$canReview) class="w-full rounded-lg bg-orange-500 px-4 py-2 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-40">Send request</button></form>
                </details>

                <details class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <summary class="cursor-pointer font-medium text-slate-950 dark:text-white">Add internal note</summary>
                    <form method="POST" action="{{ route('admin.b2b-applications.note', $b2bApplication) }}" class="mt-4 space-y-3">@csrf<textarea name="note" rows="4" class="{{ $field }}" placeholder="Visible only to staff." required>{{ old('note') }}</textarea><button class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-700 dark:text-slate-200">Add note</button></form>
                </details>

                <details class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm dark:border-emerald-900 dark:bg-emerald-950/30" @if($canApprove) open @endif>
                    <summary class="cursor-pointer font-medium text-emerald-950 dark:text-emerald-100">Approve business account</summary>
                    <form method="POST" action="{{ route('admin.b2b-applications.approve', $b2bApplication) }}" class="mt-4 space-y-3">@csrf
                        <label class="block text-sm text-emerald-900 dark:text-emerald-200">Price group ID<input type="number" min="1" name="approved_price_group_id" value="{{ old('approved_price_group_id', $b2bApplication->approved_price_group_id) }}" class="{{ $field }}"></label>
                        <label class="flex items-start gap-3 rounded-lg border border-emerald-200 bg-white/70 p-3 dark:border-emerald-900 dark:bg-slate-950/50"><input type="checkbox" name="pay_later_enabled" value="1" @checked(old('pay_later_enabled', $b2bApplication->pay_later_enabled)) class="mt-1 rounded border-slate-300 text-emerald-600"><span class="text-sm text-emerald-900 dark:text-emerald-200">Enable pay later</span></label>
                        <div class="grid grid-cols-2 gap-3"><label class="block text-sm text-emerald-900 dark:text-emerald-200">Credit limit<input type="number" min="0" step="0.01" name="credit_limit" value="{{ old('credit_limit', $b2bApplication->credit_limit) }}" class="{{ $field }}"></label><label class="block text-sm text-emerald-900 dark:text-emerald-200">Credit days<input type="number" min="0" max="365" name="payment_terms_days" value="{{ old('payment_terms_days', $b2bApplication->payment_terms_days) }}" class="{{ $field }}"></label></div>
                        <label class="block text-sm text-emerald-900 dark:text-emerald-200">Minimum order value<input type="number" min="0" step="0.01" name="minimum_order_value" value="{{ old('minimum_order_value', $b2bApplication->minimum_order_value) }}" class="{{ $field }}"></label>
                        <label class="block text-sm text-emerald-900 dark:text-emerald-200">Account manager<select name="approved_account_manager_id" class="{{ $field }}"><option value="">Not assigned</option>@foreach ($staff as $member)<option value="{{ $member->id }}" @selected((string) old('approved_account_manager_id', $b2bApplication->approved_account_manager_id) === (string) $member->id)>{{ $member->name ?: $member->email }}</option>@endforeach</select></label>
                        <label class="block text-sm text-emerald-900 dark:text-emerald-200">Delivery arrangement<textarea name="delivery_arrangement" rows="3" class="{{ $field }}">{{ old('delivery_arrangement', $b2bApplication->delivery_arrangement) }}</textarea></label>
                        <label class="block text-sm text-emerald-900 dark:text-emerald-200">Approval message<textarea name="customer_message" rows="3" class="{{ $field }}">{{ old('customer_message', 'Your Bandara Business Account has been approved. You can now sign in to view business pricing and place B2B orders.') }}</textarea></label>
                        <button @disabled(!$canApprove) onclick="return confirm('Approve this application and convert the customer to B2B?')" class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-40">Approve and enable B2B</button>
                    </form>
                </details>

                <details class="rounded-xl border border-rose-200 bg-rose-50 p-5 shadow-sm dark:border-rose-900 dark:bg-rose-950/30">
                    <summary class="cursor-pointer font-medium text-rose-950 dark:text-rose-100">Do not approve</summary>
                    <form method="POST" action="{{ route('admin.b2b-applications.reject', $b2bApplication) }}" class="mt-4 space-y-3">@csrf<textarea name="customer_message" rows="4" class="{{ $field }}" placeholder="Use customer-friendly wording." required></textarea><button @disabled(!$canReject) onclick="return confirm('Update this application as not approved?')" class="w-full rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-40">Update status</button></form>
                </details>

                @if ($b2bApplication->profile)
                    <section class="rounded-xl border border-slate-200 bg-white p-5 text-sm shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <h3 class="font-medium text-slate-950 dark:text-white">Active B2B profile</h3>
                        <dl class="mt-4 space-y-2 text-slate-600 dark:text-slate-300"><div class="flex justify-between gap-3"><dt>Pay later</dt><dd>{{ $b2bApplication->profile->pay_later_enabled ? 'Yes' : 'No' }}</dd></div><div class="flex justify-between gap-3"><dt>Credit days</dt><dd>{{ $b2bApplication->profile->payment_terms_days }}</dd></div><div class="flex justify-between gap-3"><dt>Credit limit</dt><dd>₹{{ number_format((float) $b2bApplication->profile->credit_limit, 2) }}</dd></div></dl>
                    </section>
                @endif
            </aside>
        </div>
    </div>
</x-layouts.business-account>
