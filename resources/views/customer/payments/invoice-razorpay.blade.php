@extends('layouts.customer')

@section('title', 'Pay invoice ' . $invoice->invoice_number)

@section('content')
@php
    use Illuminate\Support\Str;

    $invoiceStatus = strtolower((string) ($invoice->status ?? 'pending'));
    $isPartial = round((float) $amountToPay, 2) + 0.00001 < round((float) $balance, 2);

    $invoiceStatusMeta = match ($invoiceStatus) {
        'paid' => [
            'label' => 'Paid',
            'class' => 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800',
        ],
        'part_payment' => [
            'label' => 'Part payment',
            'class' => 'bg-sky-100 text-sky-700 border-sky-200 dark:bg-sky-900/30 dark:text-sky-300 dark:border-sky-800',
        ],
        'past_due' => [
            'label' => 'Past due',
            'class' => 'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800',
        ],
        'due' => [
            'label' => 'Due',
            'class' => 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800',
        ],
        default => [
            'label' => Str::headline($invoiceStatus ?: 'Pending'),
            'class' => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
        ],
    };
@endphp

<div class="max-w-4xl mx-auto px-4 py-6 space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <div class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-1 text-[10px] font-medium uppercase tracking-[0.14em] text-gray-600 dark:text-gray-300">
                Secure Razorpay invoice payment
            </div>

            <h1 class="mt-3 text-2xl font-semibold text-gray-900 dark:text-gray-50">
                {{ $isPartial ? 'Make part payment' : 'Pay invoice balance' }}
            </h1>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                Invoice {{ $invoice->invoice_number }} • Paying ₹{{ number_format($amountToPay, 2) }}
            </p>
        </div>

        <a href="{{ route('invoices.show', $invoice) }}"
           class="inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-2 text-[11px] font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
            Back to invoice
        </a>
    </div>

    <div class="grid gap-4 lg:grid-cols-[1.2fr,0.8fr]">
        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-5 py-5 space-y-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                    Payment in progress
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    A secure Razorpay window should open automatically. If it does not, use the button below.
                </p>
            </div>

            <div class="rounded-sm border border-amber-200 dark:border-amber-900/40 bg-amber-50/80 dark:bg-amber-950/20 px-4 py-4">
                <div class="text-sm font-medium text-amber-800 dark:text-amber-200">
                    Do not close this page while payment is being confirmed
                </div>
                <p class="mt-1 text-[11px] leading-relaxed text-amber-700 dark:text-amber-300">
                    Once the payment succeeds, we will verify it, apply it to your invoice, and redirect you back to the invoice page.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button id="razorpay-pay-btn"
                        type="button"
                        class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-xs font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                    Open Razorpay payment
                </button>

                <p id="payment-inline-status" class="hidden text-[11px] text-gray-500 dark:text-gray-400">
                    Opening secure payment window…
                </p>
            </div>

            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                If you close the Razorpay window before completing payment, no amount will be applied to your invoice.
            </p>
        </div>

        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-5 py-5 space-y-4">
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-50">
                    Invoice payment summary
                </h2>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                    Part payments are accepted. The invoice status updates automatically based on the remaining balance.
                </p>
            </div>

            <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-950/40 px-4 py-4 space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">Invoice</div>
                        <div class="mt-1 text-sm font-semibold font-mono text-gray-900 dark:text-gray-50">
                            {{ $invoice->invoice_number }}
                        </div>
                    </div>
                    <span class="inline-flex items-center rounded-sm border px-2.5 py-0.5 text-[10px] font-medium {{ $invoiceStatusMeta['class'] }}">
                        {{ $invoiceStatusMeta['label'] }}
                    </span>
                </div>

                <div class="space-y-1 text-[11px] text-gray-700 dark:text-gray-300">
                    <div class="flex justify-between gap-3">
                        <span>Invoice total</span>
                        <span>₹{{ number_format($invoice->grand_total, 2) }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span>Already paid</span>
                        <span>₹{{ number_format($invoice->amount_paid, 2) }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span>Balance before this payment</span>
                        <span>₹{{ number_format($balance, 2) }}</span>
                    </div>
                    <div class="flex justify-between gap-3 font-semibold text-gray-900 dark:text-gray-50 pt-2 border-t border-gray-200 dark:border-gray-800">
                        <span>Amount being paid now</span>
                        <span>₹{{ number_format($amountToPay, 2) }}</span>
                    </div>
                    <div class="flex justify-between gap-3 text-gray-500 dark:text-gray-400">
                        <span>Balance after success</span>
                        <span>₹{{ number_format(max(0, $balance - $amountToPay), 2) }}</span>
                    </div>
                </div>
            </div>

            @if($isPartial)
                <div class="rounded-sm border border-sky-200 dark:border-sky-900/40 bg-sky-50/80 dark:bg-sky-950/20 px-4 py-4">
                    <div class="text-[11px] font-medium text-sky-800 dark:text-sky-200">
                        Part payment
                    </div>
                    <p class="mt-1 text-[11px] leading-relaxed text-sky-700 dark:text-sky-300">
                        This payment will reduce your balance but the invoice will remain open until the full balance is paid.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

<div id="payment-processing-overlay"
     class="hidden fixed inset-0 z-[80] bg-white/80 dark:bg-gray-950/80 backdrop-blur-sm"
     aria-live="polite"
     aria-busy="true">
    <div class="flex min-h-full items-center justify-center px-4">
        <div class="w-full max-w-md rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-6 py-6 text-center shadow-xl">
            <div class="mx-auto h-12 w-12 rounded-full border-2 border-gray-300 dark:border-gray-700 border-t-gray-900 dark:border-t-gray-100 animate-spin"></div>
            <h2 id="payment-processing-title" class="mt-4 text-lg font-semibold text-gray-900 dark:text-gray-50">
                Verifying your payment
            </h2>
            <p id="payment-processing-text" class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                Please wait while we confirm your payment and apply it to your invoice.
            </p>
            <p class="mt-3 text-[11px] text-gray-500 dark:text-gray-400">
                Do not refresh or close this page.
            </p>
        </div>
    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var payBtn = document.getElementById('razorpay-pay-btn');
        var inlineStatus = document.getElementById('payment-inline-status');
        var overlay = document.getElementById('payment-processing-overlay');
        var overlayTitle = document.getElementById('payment-processing-title');
        var overlayText = document.getElementById('payment-processing-text');

        if (!payBtn) return;

        var isVerifying = false;
        var launchCooldown = false;

        function setInlineStatus(message, visible) {
            if (!inlineStatus) return;
            inlineStatus.textContent = message || '';
            inlineStatus.classList.toggle('hidden', !visible);
        }

        function setButtonBusy(isBusy) {
            payBtn.disabled = isBusy;
            payBtn.classList.toggle('opacity-60', isBusy);
            payBtn.classList.toggle('cursor-not-allowed', isBusy);
        }

        function showProcessing(title, message) {
            if (overlayTitle) overlayTitle.textContent = title || 'Verifying your payment';
            if (overlayText) overlayText.textContent = message || 'Please wait while we confirm your payment and apply it to your invoice.';
            if (overlay) overlay.classList.remove('hidden');
        }

        function hideProcessing() {
            if (overlay) overlay.classList.add('hidden');
        }

        var options = {
            key: "{{ $razorpayKey }}",
            amount: "{{ $amountPaise }}",
            currency: "INR",
            name: "Frozen - Bandara",
            description: "Invoice {{ $invoice->invoice_number }}",
            order_id: "{{ $razorpayOrderId }}",
            prefill: {
                name: "{{ $user->name }}",
                email: "{{ $user->email }}",
                contact: "{{ $user->phone }}"
            },
            notes: {
                invoice_id: "{{ $invoice->id }}",
                invoice_number: "{{ $invoice->invoice_number }}",
                internal_order_id: "{{ $order?->id }}"
            },
            theme: {
                color: "#111827"
            },
            handler: function (response) {
                isVerifying = true;
                setButtonBusy(true);
                setInlineStatus('', false);
                showProcessing(
                    'Verifying your payment',
                    'Please wait while we confirm the Razorpay payment and update your invoice balance.'
                );

                fetch("{{ route('payment.razorpay.invoice-callback') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({
                        razorpay_order_id: response.razorpay_order_id,
                        razorpay_payment_id: response.razorpay_payment_id,
                        razorpay_signature: response.razorpay_signature
                    })
                })
                .then(async function (res) {
                    var data = {};
                    try {
                        data = await res.json();
                    } catch (e) {
                        data = {};
                    }

                    if (!res.ok) {
                        throw new Error(data.message || 'Payment verification failed. Please contact support.');
                    }

                    return data;
                })
                .then(function (data) {
                    if (data.status === 'ok' && data.redirect_url) {
                        showProcessing(
                            'Payment confirmed',
                            'Your invoice balance has been updated. Redirecting you now.'
                        );
                        window.location.href = data.redirect_url;
                        return;
                    }

                    throw new Error(data.message || 'Payment verification failed. Please contact support.');
                })
                .catch(function (error) {
                    isVerifying = false;
                    setButtonBusy(false);
                    hideProcessing();
                    alert((error && error.message) ? error.message : 'Something went wrong while verifying your payment. Please contact support.');
                    window.location.href = "{{ route('invoices.show', $invoice) }}";
                });
            },
            modal: {
                ondismiss: function () {
                    if (isVerifying) return;
                    window.location.href = "{{ route('invoices.show', $invoice) }}";
                }
            }
        };

        function openCheckout() {
            if (isVerifying || launchCooldown) return;

            if (typeof Razorpay === 'undefined') {
                alert('Secure payment window could not be loaded. Please refresh the page and try again.');
                return;
            }

            launchCooldown = true;
            setInlineStatus('Opening secure payment window…', true);

            setTimeout(function () {
                launchCooldown = false;
            }, 1000);

            var rzp = new Razorpay(options);
            rzp.open();
        }

        setTimeout(openCheckout, 400);

        payBtn.addEventListener('click', function (e) {
            e.preventDefault();
            openCheckout();
        });
    });
</script>
@endsection
