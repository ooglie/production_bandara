@extends('layouts.company')

@section('title', 'Customer payment submissions')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-5 text-xs space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-50">
                Customer payment submissions
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Review bank transfer, UPI, cheque and cash payment details submitted by customers. Approving a submission records the payment against the invoice.
            </p>
        </div>
        <a href="{{ route('admin.invoices.index') }}"
           class="inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
            Back to invoices
        </a>
    </div>

    @if(session('status'))
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="GET" action="{{ route('admin.invoice-payment-submissions.index') }}" class="rounded border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-3 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-[10px] text-gray-500 dark:text-gray-400 mb-1">Status</label>
            <select name="status" class="rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px]">
                <option value="">Pending by default</option>
                <option value="pending" @selected(request('status', 'pending') === 'pending')>Pending</option>
                <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
            </select>
        </div>
        <div>
            <label class="block text-[10px] text-gray-500 dark:text-gray-400 mb-1">Method</label>
            <select name="method" class="rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px]">
                <option value="">All</option>
                <option value="bank_transfer" @selected(request('method') === 'bank_transfer')>NEFT / RTGS / IMPS</option>
                <option value="upi" @selected(request('method') === 'upi')>UPI</option>
                <option value="cheque" @selected(request('method') === 'cheque')>Cheque</option>
                <option value="cash" @selected(request('method') === 'cash')>Cash</option>
                <option value="other" @selected(request('method') === 'other')>Other</option>
            </select>
        </div>
        <button type="submit" class="inline-flex items-center rounded-sm border border-gray-900 bg-gray-900 px-3 py-1.5 text-[11px] font-medium text-white hover:bg-gray-800">Filter</button>
    </form>

    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
        <table class="min-w-full text-xs">
            <thead class="bg-gray-50 dark:bg-gray-900/70 text-left text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">
                <tr>
                    <th class="px-3 py-2">Submitted</th>
                    <th class="px-3 py-2">Customer / invoice</th>
                    <th class="px-3 py-2">Method</th>
                    <th class="px-3 py-2 text-right">Amount</th>
                    <th class="px-3 py-2">Reference</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($submissions as $submission)
                    @php
                        $invoice = $submission->invoice;
                        $order = $invoice?->order;
                        $customer = $submission->user ?? $order?->user;
                    @endphp
                    <tr class="align-top">
                        <td class="px-3 py-3 whitespace-nowrap text-gray-500 dark:text-gray-400">
                            {{ optional($submission->created_at)->format('d M Y, H:i') }}
                            <div class="text-[10px]">Paid on {{ optional($submission->paid_on)->format('d M Y') ?? '—' }}</div>
                        </td>
                        <td class="px-3 py-3">
                            <div class="font-medium text-gray-900 dark:text-gray-50">{{ $customer?->name ?? '—' }}</div>
                            <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                @if($invoice)
                                    Invoice <a href="{{ route('admin.invoices.show', $invoice) }}" class="underline">{{ $invoice->invoice_number }}</a>
                                    · Balance ₹{{ number_format($invoice->balance_amount ?? 0, 2) }}
                                @else
                                    Invoice missing
                                @endif
                            </div>
                        </td>
                        <td class="px-3 py-3">
                            {{ $submission->method_label }}
                            @if($submission->method === 'cheque')
                                <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                    Cheque {{ $submission->cheque_number ?? '—' }} · {{ optional($submission->cheque_date)->format('d M Y') ?? '—' }}
                                </div>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400">{{ $submission->cheque_bank_name }}</div>
                            @elseif($submission->bank_name)
                                <div class="text-[10px] text-gray-500 dark:text-gray-400">{{ $submission->bank_name }}</div>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-right font-medium text-gray-900 dark:text-gray-50">
                            ₹{{ number_format($submission->amount, 2) }}
                        </td>
                        <td class="px-3 py-3 text-gray-600 dark:text-gray-300">
                            {{ $submission->reference ?? '—' }}
                            @if($submission->proof_path && route('admin.invoice-payment-submissions.proof', $submission))
                                <div>
                                    <a href="{{ route('admin.invoice-payment-submissions.proof', $submission) }}" class="text-[10px] underline" target="_blank">Download proof</a>
                                </div>
                            @endif
                            @if($submission->customer_note)
                                <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">{{ $submission->customer_note }}</div>
                            @endif
                        </td>
                        <td class="px-3 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[10px]
                                @if($submission->status === 'approved') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300
                                @elseif($submission->status === 'rejected') bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300
                                @else bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300
                                @endif">
                                {{ $submission->status_label }}
                            </span>
                            @if($submission->admin_note)
                                <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">{{ $submission->admin_note }}</div>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-right">
                            @if($submission->status === 'pending')
                                <div class="space-y-2 min-w-[220px]">
                                    <form method="POST" action="{{ route('admin.invoice-payment-submissions.approve', $submission) }}" class="space-y-1">
                                        @csrf
                                        <textarea name="admin_note" rows="2" placeholder="Approval note, optional" class="w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[10px]"></textarea>
                                        <button type="submit" class="w-full rounded-sm border border-emerald-700 bg-emerald-700 px-2 py-1 text-[10px] font-medium text-white hover:bg-emerald-800">Approve & apply</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.invoice-payment-submissions.reject', $submission) }}" class="space-y-1">
                                        @csrf
                                        <textarea name="admin_note" rows="2" placeholder="Rejection reason" required class="w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[10px]"></textarea>
                                        <button type="submit" class="w-full rounded-sm border border-red-700 bg-red-700 px-2 py-1 text-[10px] font-medium text-white hover:bg-red-800">Reject</button>
                                    </form>
                                </div>
                            @elseif($submission->payment)
                                <a href="{{ route('admin.payments.show', $submission->payment) }}" class="text-[10px] underline">View payment</a>
                            @else
                                <span class="text-[10px] text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-5 text-center text-[11px] text-gray-500 dark:text-gray-400">
                            No payment submissions found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $submissions->links() }}</div>
</div>
@endsection
