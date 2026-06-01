@extends('layouts.company')

@section('title', 'B2B Product Requests')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 text-xs space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">B2B Product Requests</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">Approve customer catalog requests and optionally set MOQ/pricing.</p>
        </div>
        <form method="GET" action="{{ route('admin.b2b.product-requests.index') }}" class="flex items-center gap-2">
            <select name="status" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">
                @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled', 'all' => 'All'] as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="rounded-lg bg-gray-900 px-4 py-2 text-xs font-medium text-white dark:bg-gray-100 dark:text-gray-900">Filter</button>
        </form>
    </div>

    @if(session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-[11px] text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-[11px] text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">{{ $errors->first() }}</div>
    @endif

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <table class="min-w-full text-[11px]">
            <thead class="bg-gray-50 dark:bg-gray-950/40">
                <tr class="text-left text-gray-500 dark:text-gray-400">
                    <th class="px-3 py-2 font-medium">Customer</th>
                    <th class="px-3 py-2 font-medium">Product</th>
                    <th class="px-3 py-2 font-medium">Request</th>
                    <th class="px-3 py-2 font-medium">Status</th>
                    <th class="px-3 py-2 font-medium text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($requests as $row)
                    <tr class="align-top">
                        <td class="px-3 py-3">
                            <div class="font-medium text-gray-900 dark:text-gray-50">{{ $row->user?->name ?? ('User #' . $row->user_id) }}</div>
                            <div class="text-gray-500 dark:text-gray-400">{{ $row->user?->email }}</div>
                        </td>
                        <td class="px-3 py-3">
                            <div class="font-medium text-gray-900 dark:text-gray-50">{{ $row->product?->name ?? ('Product #' . $row->product_id) }}</div>
                            <div class="text-gray-500 dark:text-gray-400">SKU: {{ $row->product?->sku ?: '—' }}</div>
                            @if($row->productSellUnit)
                                <div class="mt-1 text-gray-500 dark:text-gray-400">Requested unit: {{ $row->productSellUnit->display_label }}</div>
                            @endif
                            @if($row->productVariant)
                                <div class="mt-1 text-gray-500 dark:text-gray-400">Variant: {{ $row->productVariant->name ?: $row->productVariant->sku }}</div>
                            @endif
                        </td>
                        <td class="px-3 py-3 max-w-xs">
                            <div>Qty: {{ $row->requested_quantity ? rtrim(rtrim(number_format((float) $row->requested_quantity, 2), '0'), '.') : '—' }}</div>
                            @if($row->message)
                                <div class="mt-1 text-gray-500 dark:text-gray-400">{{ $row->message }}</div>
                            @endif
                            @if($row->admin_note)
                                <div class="mt-1 rounded bg-gray-50 p-2 text-gray-500 dark:bg-gray-950 dark:text-gray-400">Admin: {{ $row->admin_note }}</div>
                            @endif
                        </td>
                        <td class="px-3 py-3">
                            @php
                                $badge = match($row->status) {
                                    'approved' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200',
                                    'rejected' => 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200',
                                    'cancelled' => 'border-gray-200 bg-gray-50 text-gray-600 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300',
                                    default => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200',
                                };
                            @endphp
                            <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] {{ $badge }}">{{ ucfirst($row->status) }}</span>
                            <div class="mt-1 text-gray-500 dark:text-gray-400">{{ $row->created_at?->format('d M Y') }}</div>
                        </td>
                        <td class="px-3 py-3 text-right min-w-[280px]">
                            @if($row->status === 'pending')
                                <form method="POST" action="{{ route('admin.b2b.product-requests.approve', $row) }}" class="mb-2 rounded-lg border border-gray-200 p-2 text-left dark:border-gray-800">
                                    @csrf
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        <input type="number" name="min_order_quantity" step="0.01" min="0.01" placeholder="MOQ" value="1" class="rounded border border-gray-300 px-2 py-1 text-[11px] dark:border-gray-700 dark:bg-gray-950">
                                        <input type="number" name="price" step="0.01" min="0" placeholder="B2B price optional" class="rounded border border-gray-300 px-2 py-1 text-[11px] dark:border-gray-700 dark:bg-gray-950">
                                    </div>
                                    @if($row->productSellUnit || $row->product_variant_id)
                                        <select name="price_scope" class="mt-2 w-full rounded border border-gray-300 px-2 py-1 text-[11px] dark:border-gray-700 dark:bg-gray-950">
                                            <option value="product">Apply price at product level</option>
                                            @if($row->productSellUnit)
                                                <option value="sell_unit" selected>Apply price to requested sellable unit</option>
                                            @endif
                                            @if($row->product_variant_id)
                                                <option value="variant">Apply price to requested variant</option>
                                            @endif
                                        </select>
                                    @else
                                        <input type="hidden" name="price_scope" value="product">
                                    @endif
                                    <input type="text" name="admin_note" placeholder="Admin note optional" class="mt-2 w-full rounded border border-gray-300 px-2 py-1 text-[11px] dark:border-gray-700 dark:bg-gray-950">
                                    <button class="mt-2 rounded-full bg-gray-900 px-3 py-1 text-[11px] font-medium text-white dark:bg-gray-100 dark:text-gray-900">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('admin.b2b.product-requests.reject', $row) }}" class="text-left">
                                    @csrf
                                    <div class="flex gap-2">
                                        <input type="text" name="admin_note" placeholder="Rejection note optional" class="min-w-0 flex-1 rounded border border-gray-300 px-2 py-1 text-[11px] dark:border-gray-700 dark:bg-gray-950">
                                        <button class="rounded-full border border-red-300 px-3 py-1 text-[11px] text-red-700 hover:bg-red-50 dark:border-red-900 dark:text-red-200 dark:hover:bg-red-950/40">Reject</button>
                                    </div>
                                </form>
                            @else
                                <span class="text-gray-500 dark:text-gray-400">Resolved {{ $row->resolved_at?->format('d M Y') ?: '—' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">No requests found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $requests->links() }}
</div>
@endsection
