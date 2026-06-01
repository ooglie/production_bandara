@php
    /**
     * Reusable B2B Customer form.
     *
     * Expected variables:
     * - $action (string) required
     * - $mode ('create'|'edit') required
     * - $user (\App\Models\User|null) optional (required for edit)
     * - $backUrl (string) optional
     */

    $mode = $mode ?? 'create';
    $isEdit = $mode === 'edit';

    $user = $user ?? null;
    $terms = $terms ?? ($user?->b2bTerms ?? null);
    $payLaterSummary = $payLaterSummary ?? [];

    $payLaterEnabled = (bool) old('pay_later_enabled', (bool) ($terms?->pay_later_enabled ?? false));
    $creditLimit = old('credit_limit', $terms ? number_format((float) $terms->credit_limit, 2, '.', '') : '0.00');
    $paymentTermsDays = old('payment_terms_days', $terms?->payment_terms_days ?? 7);
    $creditStatus = old('credit_status', $terms?->credit_status ?? 'active');
    $creditNotes = old('credit_notes', $terms?->notes ?? '');
    $outstandingAmount = (float) ($payLaterSummary['outstanding_amount'] ?? 0);
    $availableCredit = (float) ($payLaterSummary['available_credit'] ?? max((float) $creditLimit - $outstandingAmount, 0));

    $backUrl = $backUrl
        ?? (\Illuminate\Support\Facades\Route::has('admin.b2b.customers.index')
            ? route('admin.b2b.customers.index')
            : url()->previous());

@endphp

@if(session('status'))
    <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
        {{ session('status') }}
    </div>
@endif

@if($errors->any())
    <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- ✅ Main form (no nested forms) --}}
<form id="{{ $isEdit ? 'b2b-update-form' : 'b2b-create-form' }}"
      method="POST"
      action="{{ $action }}"
      class="space-y-4">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    {{-- Force customer_type + role --}}
    <input type="hidden" name="customer_type" value="b2b">
    <input type="hidden" name="roles[]" value="Customer">

    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-4">
        <div class="grid gap-4">
            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Name</label>
                <input type="text" name="name"
                       value="{{ old('name', $user->name ?? '') }}"
                       required
                       class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]
                              focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700">
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Email</label>
                    <input type="email" name="email"
                           value="{{ old('email', $user->email ?? '') }}"
                           required
                           class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]
                                  focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700">
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Phone</label>
                    <input type="text" name="phone"
                           value="{{ old('phone', $user->phone ?? '') }}"
                           required
                           class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]
                                  focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">GSTIN</label>
                    <input type="text" name="gst_number"
                           value="{{ old('gst_number', $user->gst_number ?? '') }}"
                           class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]
                                  focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700">
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">FSSAI</label>
                    <input type="text" name="fssai_number"
                           value="{{ old('fssai_number', $user->fssai_number ?? '') }}"
                           class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]
                                  focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                        {{ $isEdit ? 'New password (optional)' : 'Password' }}
                    </label>
                    <input type="password" name="password" {{ $isEdit ? '' : 'required' }}
                           class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]
                                  focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700">
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                        Confirm {{ $isEdit ? 'new ' : '' }}password
                    </label>
                    <input type="password" name="password_confirmation" {{ $isEdit ? '' : 'required' }}
                           class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]
                                  focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700">
                </div>
            </div>

            <div class="flex flex-col gap-2 pt-1">
                <label class="inline-flex items-center gap-2 text-[11px] text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="mark_email_verified" value="1"
                           @checked(old('mark_email_verified', $isEdit ? (bool)($user?->email_verified_at) : false))>
                    <span>Mark email as verified</span>
                </label>

                <label class="inline-flex items-center gap-2 text-[11px] text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="is_active" value="1"
                           @checked(old('is_active', $isEdit ? (bool)($user?->is_active) : true))
                           @if($isEdit && $user && $user->id === auth()->id()) disabled @endif>
                    <span>
                        Active / allow login
                        @if($isEdit && $user && $user->id === auth()->id())
                            (you cannot deactivate yourself)
                        @endif
                    </span>
                </label>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-4">
        <div>
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">B2B Pay Later terms</h2>
            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                Pay Later is available only when enabled, active, and within available credit.
            </p>
        </div>

        @if(!empty($payLaterSummary))
            <div class="grid gap-2 sm:grid-cols-3 text-[11px]">
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 p-3">
                    <div class="text-gray-500 dark:text-gray-400">Outstanding</div>
                    <div class="mt-1 font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format((float) ($payLaterSummary['outstanding_amount'] ?? 0), 2) }}</div>
                </div>
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 p-3">
                    <div class="text-gray-500 dark:text-gray-400">Available credit</div>
                    <div class="mt-1 font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format((float) ($payLaterSummary['available_credit'] ?? 0), 2) }}</div>
                </div>
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 p-3">
                    <div class="text-gray-500 dark:text-gray-400">Status</div>
                    <div class="mt-1 font-semibold text-gray-900 dark:text-gray-50">{{ str_replace('_', ' ', ucfirst((string) ($payLaterSummary['credit_status'] ?? 'on_hold'))) }}</div>
                </div>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="inline-flex items-center gap-2 text-[11px] text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="pay_later_enabled" value="1"
                       @checked(old('pay_later_enabled', (bool)($terms?->pay_later_enabled ?? false)))>
                <span>Enable Pay Later for this customer</span>
            </label>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Credit status</label>
                <select name="credit_status" class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]">
                    <option value="active" @selected($creditStatus === 'active')>Active</option>
                    <option value="on_hold" @selected($creditStatus === 'on_hold')>On hold</option>
                    <option value="blocked" @selected($creditStatus === 'blocked')>Blocked</option>
                </select>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Credit limit</label>
                <input type="number" step="0.01" min="0" name="credit_limit"
                       value="{{ old('credit_limit', $terms?->credit_limit ?? 0) }}"
                       class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]">
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Payment terms days</label>
                <input type="number" min="1" max="365" name="payment_terms_days"
                       value="{{ old('payment_terms_days', $terms?->payment_terms_days ?? 7) }}"
                       class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]">
            </div>
        </div>

        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Internal credit note</label>
            <textarea name="credit_notes" rows="3"
                      class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]">{{ old('credit_notes', $terms?->notes ?? '') }}</textarea>
        </div>
    </div>

    {{-- Footer actions --}}
    <div class="flex items-center justify-between">
        <a href="{{ $backUrl }}" class="text-[11px] text-gray-500 dark:text-gray-400 hover:underline">
            Cancel
        </a>

        <button type="submit"
                class="inline-flex items-center justify-center rounded-full border border-gray-900 dark:border-gray-100
                       bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-[11px] font-medium
                       hover:bg-gray-800 dark:hover:bg-gray-200">
            {{ $isEdit ? 'Save' : 'Create B2B Customer' }}
        </button>
    </div>
</form>