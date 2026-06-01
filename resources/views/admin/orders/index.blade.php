@extends('layouts.company')

@section('title', 'Orders')

@section('content')
@php
    use Illuminate\Support\Facades\Route;

    $status = request('status');
    $unprintedOnly = request()->boolean('unprinted');

    $printNewRouteExists = Route::has('admin.orders.print.new');
    $printBulkRouteExists = Route::has('admin.orders.print.bulk');
    $markUnprintedBulkExists = Route::has('admin.orders.markUnprinted.bulk');
    $markUnprintedSingleExists = Route::has('admin.orders.markUnprinted');
    $ordersShowExists = Route::has('admin.orders.show');
    $ordersPrintSingleExists = Route::has('admin.orders.print');

    // Use the original working route name from web.php
    $bulkStatusRouteName = 'admin.orders.bulk-status';
    $bulkStatusRouteExists = Route::has($bulkStatusRouteName);

    $statusOptions = [
        '' => 'All statuses',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
    ];
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-xs space-y-4">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Orders</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Manage orders, bulk actions, and printing.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if($printNewRouteExists)
                <a href="{{ route('admin.orders.print.new') }}"
                   target="_blank"
                   class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                    Print new orders
                </a>
            @endif

            @if($printBulkRouteExists)
                <form id="bulk-print-form" method="POST" action="{{ route('admin.orders.print.bulk') }}" target="_blank" class="inline">
                    @csrf
                    <button type="submit"
                        class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Print selected
                    </button>
                </form>
            @endif

            @if($markUnprintedBulkExists)
                <form id="bulk-unprint-form" method="POST" action="{{ route('admin.orders.markUnprinted.bulk') }}" class="inline"
                      onsubmit="return confirm('Mark selected orders as unprinted?');">
                    @csrf
                    <button type="submit"
                        class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Mark selected unprinted
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if(session('status'))
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
            <ul class="list-disc pl-4 space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Filters --}}
    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-3">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            {{-- Filter form --}}
            <form method="GET" action="{{ url()->current() }}"
                  class="flex flex-col gap-2 sm:flex-row sm:items-center">

                @foreach(request()->except(['status','unprinted','page']) as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach

                <div class="flex items-center gap-2">
                    <label class="text-[11px] text-gray-600 dark:text-gray-300">Status</label>
                    <select name="status"
                            class="rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                        @foreach($statusOptions as $val => $label)
                            <option value="{{ $val }}" @selected((string)$status === (string)$val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <label class="inline-flex items-center gap-2 text-[11px] text-gray-700 dark:text-gray-200">
                    <input type="checkbox" name="unprinted" value="1" @checked($unprintedOnly)>
                    <span>Unprinted only</span>
                </label>

                <button type="submit"
                        class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                    Apply
                </button>

                @if(request()->has('status') || request()->has('unprinted'))
                    <a href="{{ url()->current() }}"
                       class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Clear
                    </a>
                @endif
            </form>

            {{-- Bulk status --}}
            <div class="flex items-center gap-2">
                @if($bulkStatusRouteExists)
                    <form id="bulk-status-form" method="POST" action="{{ route($bulkStatusRouteName) }}" class="flex items-center gap-2">
                        @csrf
                        <label class="text-[11px] text-gray-600 dark:text-gray-300">Bulk status</label>
                        <select name="new_status"
                                class="rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                            <option value="">Choose…</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>

                        <button type="submit"
                                class="text-[11px] px-3 py-1 rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-200">
                            Update selected
                        </button>
                    </form>
                @else
                    <span class="text-[10px] text-gray-400">
                        Bulk status route not found (expected: <code>{{ $bulkStatusRouteName }}</code>).
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Orders Table --}}
    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-[11px]">
                <thead class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800">
                    <tr class="text-left text-gray-600 dark:text-gray-300">
                        <th class="px-3 py-2 w-10">
                            <input type="checkbox" id="select-all">
                        </th>
                        <th class="px-3 py-2 font-medium">Order</th>
                        <th class="px-3 py-2 font-medium">Customer</th>
                        <th class="px-3 py-2 font-medium">Placed</th>
                        <th class="px-3 py-2 font-medium">Status</th>
                        <th class="px-3 py-2 font-medium">Payment</th>
                        <th class="px-3 py-2 font-medium text-right">Total</th>
                        <th class="px-3 py-2 font-medium">Printed</th>
                        <th class="px-3 py-2 font-medium text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($orders as $order)
                    @php
                        $placed = $order->placed_at ?? $order->created_at;

                        $orderLabel = $order->order_number ?? ('#'.$order->id);
                        $customerName = $order->user?->name ?? 'Customer';
                        $customerPhone = $order->user?->phone ?? null;

                        $st = (string)($order->status ?? 'processing');
                        $pay = (string)($order->payment_status ?? 'pending');
                        $total = (float)($order->grand_total ?? 0);

                        $stBadge = match($st) {
                            'delivered' => 'border-emerald-300 bg-emerald-50 text-emerald-800',
                            'shipped' => 'border-blue-300 bg-blue-50 text-blue-800',
                            'cancelled' => 'border-red-300 bg-red-50 text-red-800',
                            default => 'border-gray-300 bg-gray-50 text-gray-800',
                        };

                        $payBadge = match($pay) {
                            'paid' => 'border-emerald-300 bg-emerald-50 text-emerald-800',
                            'failed' => 'border-red-300 bg-red-50 text-red-800',
                            'refunded' => 'border-gray-300 bg-gray-50 text-gray-700',
                            default => 'border-yellow-300 bg-yellow-50 text-yellow-800',
                        };

                        $printedAt = $order->printed_at ?? null;
                        $printedBy = method_exists($order, 'printedBy') ? $order->printedBy : null;

                        $showUrl = $ordersShowExists ? route('admin.orders.show', $order) : url('/admin/orders/'.$order->id);
                        $printUrl = $ordersPrintSingleExists ? route('admin.orders.print', $order) : null;
                    @endphp

                    <tr class="text-gray-700 dark:text-gray-200">
                        <td class="px-3 py-2">
                            <input type="checkbox" class="order-checkbox" value="{{ $order->id }}">
                        </td>

                        <td class="px-3 py-2">
                            <a href="{{ $showUrl }}" class="font-semibold text-gray-900 dark:text-gray-50 hover:underline">
                                {{ $orderLabel }}
                            </a>
                            <div class="text-[10px] text-gray-400">ID: {{ $order->id }}</div>
                        </td>

                        <td class="px-3 py-2">
                            <div class="font-medium text-gray-900 dark:text-gray-50">{{ $customerName }}</div>
                            @if($customerPhone)
                                <div class="text-[10px] text-gray-400">{{ $customerPhone }}</div>
                            @endif
                        </td>

                        <td class="px-3 py-2 whitespace-nowrap">
                            {{ $placed?->format('d M Y') }}
                            <div class="text-[10px] text-gray-400">{{ $placed?->format('h:i A') }}</div>
                        </td>

                        <td class="px-3 py-2">
                            <span class="text-[10px] px-2 py-0.5 rounded-full border {{ $stBadge }}">
                                {{ ucfirst($st) }}
                            </span>
                        </td>

                        <td class="px-3 py-2">
                            <span class="text-[10px] px-2 py-0.5 rounded-full border {{ $payBadge }}">
                                {{ ucfirst($pay) }}
                            </span>
                        </td>

                        <td class="px-3 py-2 text-right whitespace-nowrap">
                            ₹{{ number_format($total, 2) }}
                        </td>

                        <td class="px-3 py-2">
                            @if($printedAt)
                                <span class="text-[10px] px-2 py-0.5 rounded-full border border-emerald-300 bg-emerald-50 text-emerald-800">
                                    Printed
                                </span>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $printedAt->format('d M, h:i A') }}
                                </div>
                                @if($printedBy)
                                    <div class="text-[10px] text-gray-400">
                                        by {{ $printedBy->name }}
                                    </div>
                                @endif
                            @else
                                <span class="text-[10px] px-2 py-0.5 rounded-full border border-gray-300 text-gray-700 dark:text-gray-300">
                                    Unprinted
                                </span>
                            @endif
                        </td>

                        <td class="px-3 py-2 text-right whitespace-nowrap">
                            <a href="{{ $showUrl }}"
                               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                View
                            </a>

                            @if($printUrl)
                                <a href="{{ $printUrl }}" target="_blank"
                                   class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                    Print
                                </a>
                            @endif

                            @if($printedAt && $markUnprintedSingleExists)
                                <form method="POST" action="{{ route('admin.orders.markUnprinted', $order) }}"
                                      class="inline"
                                      onsubmit="return confirm('Mark this order as unprinted?');">
                                    @csrf
                                    <button type="submit"
                                        class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                        Mark unprinted
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-3 py-6 text-center text-[11px] text-gray-500 dark:text-gray-400">
                            No orders found for the current filters.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($orders, 'links'))
            <div class="px-3 py-3 border-t border-gray-200 dark:border-gray-800">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>

<script>
(function () {
    const selectAll = document.getElementById('select-all');
    const boxes = () => Array.from(document.querySelectorAll('.order-checkbox'));

    function selectedIds() {
        return boxes().filter(b => b.checked).map(b => b.value);
    }

    function injectIds(form) {
        form.querySelectorAll('input[name="order_ids[]"][data-injected="1"]').forEach(el => el.remove());

        const ids = selectedIds();
        ids.forEach(id => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'order_ids[]';
            inp.value = id;
            inp.setAttribute('data-injected', '1');
            form.appendChild(inp);
        });

        return ids.length;
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            boxes().forEach(b => b.checked = selectAll.checked);
        });
    }

    const printForm = document.getElementById('bulk-print-form');
    if (printForm) {
        printForm.addEventListener('submit', function (e) {
            const count = injectIds(printForm);
            if (!count) {
                e.preventDefault();
                alert('Please select at least one order.');
            }
        });
    }

    const unprintForm = document.getElementById('bulk-unprint-form');
    if (unprintForm) {
        unprintForm.addEventListener('submit', function (e) {
            const count = injectIds(unprintForm);
            if (!count) {
                e.preventDefault();
                alert('Please select at least one order.');
            }
        });
    }

    const bulkStatusForm = document.getElementById('bulk-status-form');
    if (bulkStatusForm) {
        bulkStatusForm.addEventListener('submit', function (e) {
            const count = injectIds(bulkStatusForm);
            if (!count) {
                e.preventDefault();
                alert('Please select at least one order.');
                return;
            }

            const statusSel = bulkStatusForm.querySelector('select[name="new_status"]');
            if (statusSel && !statusSel.value) {
                e.preventDefault();
                alert('Please choose a status to apply.');
            }
        });
    }
})();
</script>
@endsection