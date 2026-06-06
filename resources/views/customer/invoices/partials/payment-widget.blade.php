@php
    use Illuminate\Support\Facades\Route;

    $paymentWidgetInvoice = $invoice ?? null;
    $paymentWidgetPaid = $paymentWidgetInvoice ? (float) ($paymentWidgetInvoice->amount_paid ?? 0) : 0.0;
    $paymentWidgetBalance = isset($balanceAmount)
        ? (float) $balanceAmount
        : ($paymentWidgetInvoice
            ? (float) ($paymentWidgetInvoice->balance_amount ?? max(0, ($paymentWidgetInvoice->grand_total ?? 0) - $paymentWidgetPaid))
            : 0.0);

    $paymentWidgetPendingAmount = $paymentWidgetInvoice
        ? (float) ($paymentWidgetInvoice->pending_submitted_payment_amount ?? 0)
        : 0.0;
    $paymentWidgetOfflineLimit = max(0, $paymentWidgetBalance - $paymentWidgetPendingAmount);

    $paymentWidgetRazorpayEnabled = $paymentWidgetInvoice
        && $paymentWidgetBalance > 0.00001
        && Route::has('invoices.pay.razorpay')
        && config('services.razorpay.key')
        && config('services.razorpay.secret');

    $paymentWidgetOfflineEnabled = $paymentWidgetInvoice
        && $paymentWidgetBalance > 0.00001
        && $paymentWidgetOfflineLimit > 0.00001
        && Route::has('invoices.offline-payment.store');

    $paymentWidgetCanPay = $paymentWidgetRazorpayEnabled || $paymentWidgetOfflineEnabled;
    $paymentWidgetId = 'invoice-payment-widget-' . ($paymentWidgetInvoice?->id ?? uniqid());
    $paymentWidgetSelectedMethod = old('offline_method') ? old('offline_method') : 'razorpay';
    if ($paymentWidgetSelectedMethod === 'bank') {
        $paymentWidgetSelectedMethod = 'bank_transfer';
    }
    if ($paymentWidgetSelectedMethod === 'razorpay' && ! $paymentWidgetRazorpayEnabled && $paymentWidgetOfflineEnabled) {
        $paymentWidgetSelectedMethod = 'bank_transfer';
    }
    if ($paymentWidgetSelectedMethod !== 'razorpay' && ! $paymentWidgetOfflineEnabled && $paymentWidgetRazorpayEnabled) {
        $paymentWidgetSelectedMethod = 'razorpay';
    }

    $paymentWidgetDefaultAmount = old('offline_amount', number_format($paymentWidgetBalance, 2, '.', ''));
    $paymentWidgetOnlineTitle = $paymentTitle ?? 'Pay this invoice';
    $paymentWidgetOnlineDescription = $paymentDescription ?? 'Pay online by Razorpay or submit offline payment details for Admin / Manager / Accounts approval.';
    $paymentWidgetButtonLabel = $paymentButtonLabel ?? 'Pay now';
    $paymentWidgetSubmissions = $paymentWidgetInvoice?->paymentSubmissions ?? collect();
    $paymentWidgetPaidOn = old('offline_paid_on', now()->toDateString());
@endphp

@if($paymentWidgetCanPay)
    <div id="{{ $paymentWidgetId }}"
         data-invoice-payment-widget
         data-invoice-balance="{{ number_format($paymentWidgetBalance, 2, '.', '') }}"
         data-offline-limit="{{ number_format($paymentWidgetOfflineLimit, 2, '.', '') }}"
         class="mt-3 rounded-sm border border-sky-200 dark:border-sky-900/40 bg-sky-50/80 dark:bg-sky-950/20 px-3 py-3">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="text-[11px] font-semibold text-sky-900 dark:text-sky-100">
                    {{ $paymentWidgetOnlineTitle }}
                </div>
                <p class="mt-1 text-[10px] text-sky-700 dark:text-sky-300">
                    {{ $paymentWidgetOnlineDescription }}
                </p>
            </div>
            <div class="text-[10px] text-sky-700 dark:text-sky-300 sm:text-right">
                Outstanding: <span class="font-semibold">₹{{ number_format($paymentWidgetBalance, 2) }}</span>
                @if($paymentWidgetPendingAmount > 0)
                    <br>Pending approval: <span class="font-semibold">₹{{ number_format($paymentWidgetPendingAmount, 2) }}</span>
                @endif
            </div>
        </div>

        <div class="mt-3 grid gap-2 sm:grid-cols-2">
            <div>
                <label class="block text-[10px] text-sky-800 dark:text-sky-200 mb-1">Payment method</label>
                <select data-payment-method
                        class="w-full rounded-sm border border-sky-200 dark:border-sky-900/60 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-900 dark:text-gray-50 focus:outline-none focus:ring-1 focus:ring-sky-400">
                    @if($paymentWidgetRazorpayEnabled)
                        <option value="razorpay" @selected($paymentWidgetSelectedMethod === 'razorpay')>Razorpay / online</option>
                    @endif
                    @if($paymentWidgetOfflineEnabled)
                        <option value="bank_transfer" @selected($paymentWidgetSelectedMethod === 'bank_transfer')>NEFT / RTGS / IMPS</option>
                        <option value="upi" @selected($paymentWidgetSelectedMethod === 'upi')>UPI</option>
                        <option value="cheque" @selected($paymentWidgetSelectedMethod === 'cheque')>Cheque</option>
                        <option value="cash" @selected($paymentWidgetSelectedMethod === 'cash')>Cash</option>
                        <option value="other" @selected($paymentWidgetSelectedMethod === 'other')>Other</option>
                    @endif
                </select>
            </div>

            <div>
                <label class="block text-[10px] text-sky-800 dark:text-sky-200 mb-1">Amount</label>
                <input type="number"
                       data-payment-amount
                       step="0.01"
                       min="0.01"
                       max="{{ number_format($paymentWidgetBalance, 2, '.', '') }}"
                       value="{{ $paymentWidgetDefaultAmount }}"
                       class="w-full rounded-sm border border-sky-200 dark:border-sky-900/60 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-900 dark:text-gray-50 focus:outline-none focus:ring-1 focus:ring-sky-400">
                <p data-offline-limit-message class="mt-1 text-[10px] text-amber-700 dark:text-amber-300 hidden">
                    Offline submissions available now: ₹{{ number_format($paymentWidgetOfflineLimit, 2) }}.
                </p>
            </div>
        </div>

        @if($paymentWidgetRazorpayEnabled)
            <form method="GET"
                  action="{{ route('invoices.pay.razorpay', $paymentWidgetInvoice) }}"
                  data-razorpay-payment-form
                  class="mt-3">
                <input type="hidden" name="amount" data-razorpay-amount value="{{ $paymentWidgetDefaultAmount }}">
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                    {{ $paymentWidgetButtonLabel }} with Razorpay
                </button>
                <p class="mt-2 text-[10px] text-sky-600 dark:text-sky-300">
                    Online payments are applied immediately after Razorpay confirmation.
                </p>
            </form>
        @endif

        @if($paymentWidgetOfflineEnabled)
            <form method="POST"
                  action="{{ route('invoices.offline-payment.store', $paymentWidgetInvoice) }}"
                  enctype="multipart/form-data"
                  data-offline-payment-form
                  class="mt-3 hidden">
                @csrf
                <input type="hidden" name="offline_amount" data-offline-amount value="{{ $paymentWidgetDefaultAmount }}">
                <input type="hidden" name="offline_method" data-offline-method value="{{ $paymentWidgetSelectedMethod === 'razorpay' ? 'bank_transfer' : $paymentWidgetSelectedMethod }}">
                <input type="hidden" name="offline_paid_on" data-offline-paid-on value="{{ $paymentWidgetPaidOn }}">

                <div data-offline-help class="rounded-sm border border-amber-200 dark:border-amber-900/40 bg-amber-50/80 dark:bg-amber-950/20 px-2 py-2 text-[10px] text-amber-800 dark:text-amber-200">
                    Offline payment details are submitted for approval. The invoice balance changes only after Admin / Manager / Accounts approval.
                </div>

                <div data-bank-fields class="mt-3 grid gap-2 sm:grid-cols-2 hidden">
                    <div>
                        <label class="block text-[10px] text-amber-800 dark:text-amber-200 mb-1">UTR / transaction reference</label>
                        <input type="text" data-offline-field disabled name="offline_reference" value="{{ old('offline_reference') }}" class="w-full rounded-sm border border-amber-200 dark:border-amber-900/60 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-900 dark:text-gray-50">
                    </div>
                    <div>
                        <label class="block text-[10px] text-amber-800 dark:text-amber-200 mb-1">Payment date</label>
                        <input type="date" data-offline-field disabled data-paid-on-source value="{{ $paymentWidgetPaidOn }}" class="w-full rounded-sm border border-amber-200 dark:border-amber-900/60 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-900 dark:text-gray-50">
                    </div>
                    <div>
                        <label class="block text-[10px] text-amber-800 dark:text-amber-200 mb-1">Bank / UPI app name</label>
                        <input type="text" data-offline-field disabled name="offline_bank_name" value="{{ old('offline_bank_name') }}" class="w-full rounded-sm border border-amber-200 dark:border-amber-900/60 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-900 dark:text-gray-50">
                    </div>
                    <div>
                        <label class="block text-[10px] text-amber-800 dark:text-amber-200 mb-1">Account holder name</label>
                        <input type="text" data-offline-field disabled name="offline_account_holder_name" value="{{ old('offline_account_holder_name') }}" class="w-full rounded-sm border border-amber-200 dark:border-amber-900/60 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-900 dark:text-gray-50">
                    </div>
                </div>

                <div data-cheque-fields class="mt-3 grid gap-2 sm:grid-cols-2 hidden">
                    <div>
                        <label class="block text-[10px] text-amber-800 dark:text-amber-200 mb-1">Cheque number</label>
                        <input type="text" data-offline-field disabled name="offline_cheque_number" value="{{ old('offline_cheque_number') }}" class="w-full rounded-sm border border-amber-200 dark:border-amber-900/60 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-900 dark:text-gray-50">
                    </div>
                    <div>
                        <label class="block text-[10px] text-amber-800 dark:text-amber-200 mb-1">Cheque date</label>
                        <input type="date" data-offline-field disabled data-paid-on-source name="offline_cheque_date" value="{{ old('offline_cheque_date', $paymentWidgetPaidOn) }}" class="w-full rounded-sm border border-amber-200 dark:border-amber-900/60 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-900 dark:text-gray-50">
                    </div>
                    <div>
                        <label class="block text-[10px] text-amber-800 dark:text-amber-200 mb-1">Cheque bank name</label>
                        <input type="text" data-offline-field disabled name="offline_cheque_bank_name" value="{{ old('offline_cheque_bank_name') }}" class="w-full rounded-sm border border-amber-200 dark:border-amber-900/60 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-900 dark:text-gray-50">
                    </div>
                    <div>
                        <label class="block text-[10px] text-amber-800 dark:text-amber-200 mb-1">Branch, optional</label>
                        <input type="text" data-offline-field disabled name="offline_cheque_branch_name" value="{{ old('offline_cheque_branch_name') }}" class="w-full rounded-sm border border-amber-200 dark:border-amber-900/60 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-900 dark:text-gray-50">
                    </div>
                </div>

                <div data-other-fields class="mt-3 grid gap-2 sm:grid-cols-2 hidden">
                    <div>
                        <label class="block text-[10px] text-amber-800 dark:text-amber-200 mb-1">Reference / receipt number</label>
                        <input type="text" data-offline-field disabled name="offline_reference" value="{{ old('offline_reference') }}" class="w-full rounded-sm border border-amber-200 dark:border-amber-900/60 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-900 dark:text-gray-50">
                    </div>
                    <div>
                        <label class="block text-[10px] text-amber-800 dark:text-amber-200 mb-1">Payment date</label>
                        <input type="date" data-offline-field disabled data-paid-on-source value="{{ $paymentWidgetPaidOn }}" class="w-full rounded-sm border border-amber-200 dark:border-amber-900/60 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-900 dark:text-gray-50">
                    </div>
                </div>

                <div data-proof-note-fields class="mt-3 grid gap-2 sm:grid-cols-2 hidden">
                    <div>
                        <label class="block text-[10px] text-amber-800 dark:text-amber-200 mb-1">Upload proof, optional</label>
                        <input type="file" data-offline-field disabled name="offline_proof" accept=".jpg,.jpeg,.png,.pdf" class="block w-full text-[10px] text-gray-700 dark:text-gray-300 file:mr-2 file:rounded-sm file:border-0 file:bg-gray-900 file:px-2 file:py-1 file:text-[10px] file:text-white dark:file:bg-gray-100 dark:file:text-gray-900">
                    </div>
                    <div>
                        <label class="block text-[10px] text-amber-800 dark:text-amber-200 mb-1">Note, optional</label>
                        <textarea data-offline-field disabled name="offline_note" rows="2" class="w-full rounded-sm border border-amber-200 dark:border-amber-900/60 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-900 dark:text-gray-50">{{ old('offline_note') }}</textarea>
                    </div>
                </div>

                <button type="submit"
                        class="mt-3 inline-flex items-center justify-center rounded-sm border border-amber-700 bg-amber-700 text-white px-3 py-1.5 text-[11px] font-medium hover:bg-amber-800">
                    Submit payment details for approval
                </button>
            </form>
        @endif
    </div>
@endif

@if($paymentWidgetSubmissions->isNotEmpty())
    <div class="mt-3 rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-3">
        <div class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">Submitted offline payments</div>
        <div class="mt-2 space-y-1.5">
            @foreach($paymentWidgetSubmissions->take(8) as $submission)
                <div class="flex items-start justify-between gap-3 rounded-sm border border-gray-100 dark:border-gray-800 px-2 py-1.5 text-[10px]">
                    <div>
                        <div class="font-medium text-gray-800 dark:text-gray-100">
                            ₹{{ number_format($submission->amount, 2) }} · {{ $submission->method_label }}
                        </div>
                        <div class="text-gray-500 dark:text-gray-400">
                            {{ optional($submission->paid_on)->format('d M Y') ?? optional($submission->created_at)->format('d M Y') }}
                            @if($submission->reference)
                                · Ref: {{ $submission->reference }}
                            @endif
                        </div>
                        @if($submission->admin_note)
                            <div class="mt-0.5 text-gray-500 dark:text-gray-400">Note: {{ $submission->admin_note }}</div>
                        @endif
                    </div>
                    <span class="shrink-0 inline-flex rounded-full px-2 py-0.5
                        @if($submission->status === 'approved') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300
                        @elseif($submission->status === 'rejected') bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300
                        @else bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300
                        @endif">
                        {{ $submission->status_label }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
@endif

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('[data-invoice-payment-widget]').forEach((widget) => {
                    const methodSelect = widget.querySelector('[data-payment-method]');
                    const amountInput = widget.querySelector('[data-payment-amount]');
                    const razorpayForm = widget.querySelector('[data-razorpay-payment-form]');
                    const razorpayAmount = widget.querySelector('[data-razorpay-amount]');
                    const offlineForm = widget.querySelector('[data-offline-payment-form]');
                    const offlineAmount = widget.querySelector('[data-offline-amount]');
                    const offlineMethod = widget.querySelector('[data-offline-method]');
                    const offlinePaidOn = widget.querySelector('[data-offline-paid-on]');
                    const bankFields = widget.querySelector('[data-bank-fields]');
                    const chequeFields = widget.querySelector('[data-cheque-fields]');
                    const otherFields = widget.querySelector('[data-other-fields]');
                    const proofNoteFields = widget.querySelector('[data-proof-note-fields]');
                    const offlineLimitMessage = widget.querySelector('[data-offline-limit-message]');
                    const allOfflineSections = [bankFields, chequeFields, otherFields, proofNoteFields].filter(Boolean);
                    const balanceLimit = Number.parseFloat(widget.dataset.invoiceBalance || '0') || 0;
                    const offlineLimit = Number.parseFloat(widget.dataset.offlineLimit || '0') || 0;

                    const syncAmount = () => {
                        const value = amountInput?.value || '';
                        if (razorpayAmount) razorpayAmount.value = value;
                        if (offlineAmount) offlineAmount.value = value;
                    };

                    const setSectionVisible = (section, visible) => {
                        if (!section) return;
                        section.classList.toggle('hidden', !visible);
                        section.querySelectorAll('input, textarea, select').forEach((field) => {
                            field.disabled = !visible;
                            if (!visible) {
                                field.required = false;
                            }
                        });
                    };

                    const syncPaidOn = () => {
                        if (!offlinePaidOn) return;
                        const source = widget.querySelector('[data-paid-on-source]:not(:disabled)');
                        if (source && source.value) {
                            offlinePaidOn.value = source.value;
                        }
                    };

                    const update = () => {
                        const method = methodSelect?.value || 'razorpay';
                        const isRazorpay = method === 'razorpay';
                        const isOffline = !isRazorpay;
                        const isBankLike = method === 'bank_transfer' || method === 'upi';
                        const isCheque = method === 'cheque';
                        const isCash = method === 'cash';
                        const isOther = method === 'other';

                        if (razorpayForm) razorpayForm.classList.toggle('hidden', !isRazorpay);
                        if (offlineForm) offlineForm.classList.toggle('hidden', !isOffline);
                        if (offlineLimitMessage) offlineLimitMessage.classList.toggle('hidden', !isOffline);
                        if (offlineMethod && isOffline) offlineMethod.value = method;
                        if (amountInput) {
                            const max = isOffline && offlineLimit > 0 ? offlineLimit : balanceLimit;
                            amountInput.max = max.toFixed(2);
                            const current = Number.parseFloat(amountInput.value || '0') || 0;
                            if (max > 0 && current > max) {
                                amountInput.value = max.toFixed(2);
                            }
                        }

                        allOfflineSections.forEach((section) => setSectionVisible(section, false));

                        if (isBankLike) {
                            setSectionVisible(bankFields, true);
                            setSectionVisible(proofNoteFields, true);
                            const reference = bankFields?.querySelector('[name="offline_reference"]');
                            if (reference) reference.required = true;
                        } else if (isCheque) {
                            setSectionVisible(chequeFields, true);
                            setSectionVisible(proofNoteFields, true);
                            chequeFields?.querySelectorAll('[name="offline_cheque_number"], [name="offline_cheque_date"], [name="offline_cheque_bank_name"]').forEach((field) => {
                                field.required = true;
                            });
                        } else if (isOther) {
                            setSectionVisible(otherFields, true);
                            setSectionVisible(proofNoteFields, true);
                        } else if (isCash) {
                            // Cash intentionally keeps all detail fields hidden; only amount + submit are visible.
                        }

                        syncAmount();
                        syncPaidOn();
                    };

                    amountInput?.addEventListener('input', syncAmount);
                    methodSelect?.addEventListener('change', update);
                    widget.querySelectorAll('[data-paid-on-source]').forEach((input) => input.addEventListener('change', syncPaidOn));
                    offlineForm?.addEventListener('submit', () => {
                        syncAmount();
                        syncPaidOn();
                    });
                    razorpayForm?.addEventListener('submit', syncAmount);
                    update();
                });
            });
        </script>
    @endpush
@endonce
