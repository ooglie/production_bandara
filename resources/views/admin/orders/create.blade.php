{{-- resources/views/admin/orders/create.blade.php --}}
@extends('layouts.company')

@section('title', 'Create order')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-5 text-xs space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-1">
        <div>
            <h1 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-50">
                Create order for customer
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Admin / Manager can create orders on behalf of customers. An invoice will be generated automatically.
            </p>
        </div>

        <a href="{{ route('admin.orders.index') }}"
           class="text-[11px] text-gray-500 dark:text-gray-400 hover:underline">
            ← Back to orders
        </a>
    </div>

    @if($errors->any())
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.orders.store') }}" class="space-y-4">
        @csrf

        {{-- Customer --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-3 space-y-2">
            <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                Customer
            </p>

            <div class="space-y-1">
                <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                    Select customer
                </label>
                <select name="user_id"
                        id="customer-select"
                        class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                    <option value="">-- Choose --</option>
                    @foreach($customers as $customer)
                        @php
                            $defaultAddress = $customer->customerAddresses->first();
                        @endphp
                        <option
                            value="{{ $customer->id }}"
                            data-full-name="{{ e(optional($defaultAddress)->full_name ?? $customer->name) }}"
                            data-phone="{{ e(optional($defaultAddress)->phone ?? $customer->phone ?? '') }}"
                            data-line1="{{ e(optional($defaultAddress)->address_line1 ?? '') }}"
                            data-line2="{{ e(optional($defaultAddress)->address_line2 ?? '') }}"
                            data-city="{{ e(optional($defaultAddress)->city ?? '') }}"
                            data-state="{{ e(optional($defaultAddress)->state ?? '') }}"
                            data-state-code="{{ e(optional($defaultAddress)->state_code ?? '') }}"
                            data-country="{{ e(optional($defaultAddress)->country ?? 'India') }}"
                            data-pincode="{{ e(optional($defaultAddress)->pincode ?? '') }}"
                            data-gstin="{{ e(optional($defaultAddress)->gstin ?? '') }}"
                            @selected(old('user_id') == $customer->id)
                        >
                            {{ $customer->name }}
                            @if($customer->email) ({{ $customer->email }}) @endif
                            @if($defaultAddress)
                                – {{ $defaultAddress->city }}, {{ $defaultAddress->state }}
                            @endif
                        </option>
                    @endforeach
                </select>
                <p class="text-[10px] text-gray-400 mt-1">
                    Default address will auto-fill below. You can still edit it manually.
                </p>
            </div>
        </div>

        {{-- Address --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-3 space-y-2">
            <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                Billing & shipping address
            </p>

            <div class="grid sm:grid-cols-2 gap-3">
                <div class="space-y-1">
                    <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                        Full name
                    </label>
                    <input type="text" name="full_name" id="addr_full_name" value="{{ old('full_name') }}"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>

                <div class="space-y-1">
                    <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                        Phone
                    </label>
                    <input type="text" name="phone" id="addr_phone" value="{{ old('phone') }}"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>

                <div class="space-y-1 sm:col-span-2">
                    <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                        Address line 1
                    </label>
                    <input type="text" name="address_line1" id="addr_line1" value="{{ old('address_line1') }}"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>

                <div class="space-y-1 sm:col-span-2">
                    <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                        Address line 2 (optional)
                    </label>
                    <input type="text" name="address_line2" id="addr_line2" value="{{ old('address_line2') }}"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>

                <div class="space-y-1">
                    <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                        City
                    </label>
                    <input type="text" name="city" id="addr_city" value="{{ old('city') }}"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>

                <div class="space-y-1">
                    <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                        State
                    </label>
                    <input type="text" name="state" id="addr_state" value="{{ old('state') }}"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>

                <div class="space-y-1">
                    <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                        State code (optional)
                    </label>
                    <input type="text" name="state_code" id="addr_state_code" value="{{ old('state_code') }}"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>

                <div class="space-y-1">
                    <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                        Country
                    </label>
                    <input type="text" name="country" id="addr_country" value="{{ old('country', 'India') }}"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>

                <div class="space-y-1">
                    <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                        Pincode
                    </label>
                    <input type="text" name="pincode" id="addr_pincode" value="{{ old('pincode') }}"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>

                <div class="space-y-1 sm:col-span-2">
                    <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                        GSTIN (optional)
                    </label>
                    <input type="text" name="gstin" id="addr_gstin" value="{{ old('gstin') }}"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>
            </div>
        </div>

        {{-- Items --}}
        @php
            $oldItems = old('items', [['product_id' => '', 'quantity' => 1, 'unit_price' => 0]]);
            $productOptionsHtml = '<option value=\"\">-- Select --</option>';
            foreach ($products as $product) {
                $price = $product->price ?? $product->base_price ?? 0;
                $productOptionsHtml .= '<option value=\"'.$product->id.'\" data-price=\"'.$price.'\">'.e($product->name).'</option>';
            }
        @endphp

        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-3 space-y-2">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                    Items
                </p>
                <button type="button" id="add-item-row"
                        class="text-[11px] px-2 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                    + Add item
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-[11px]">
                    <thead class="bg-gray-50 dark:bg-gray-950/40">
                        <tr>
                            <th class="px-2 py-1.5 text-left font-medium text-gray-500 dark:text-gray-400">Product</th>
                            <th class="px-2 py-1.5 text-right font-medium text-gray-500 dark:text-gray-400">Qty</th>
                            <th class="px-2 py-1.5 text-right font-medium text-gray-500 dark:text-gray-400">Unit price</th>
                            <th class="px-2 py-1.5 text-right font-medium text-gray-500 dark:text-gray-400">Line total</th>
                            <th class="px-2 py-1.5"></th>
                        </tr>
                    </thead>
                    <tbody id="items-body" class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($oldItems as $index => $item)
                            <tr class="item-row">
                                <td class="px-2 py-1.5">
                                    <select name="items[{{ $index }}][product_id]"
                                            class="item-product-select w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-1.5 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                                        <option value="">-- Select --</option>
                                        @foreach($products as $product)
                                            @php
                                                $price = $product->price ?? $product->base_price ?? 0;
                                            @endphp
                                            <option value="{{ $product->id }}"
                                                    data-price="{{ $price }}"
                                                    @selected($item['product_id'] == $product->id)>
                                                {{ $product->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-2 py-1.5 text-right">
                                    <input type="number" step="0.01" min="0.01"
                                           name="items[{{ $index }}][quantity]"
                                           value="{{ $item['quantity'] ?? 1 }}"
                                           class="w-20 text-right rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-1 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500 item-qty">
                                </td>
                                <td class="px-2 py-1.5 text-right">
                                    <input type="number" step="0.01" min="0"
                                           name="items[{{ $index }}][unit_price]"
                                           value="{{ $item['unit_price'] ?? 0 }}"
                                           class="w-24 text-right rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-1 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500 item-price">
                                </td>
                                <td class="px-2 py-1.5 text-right text-gray-900 dark:text-gray-50">
                                    ₹<span class="item-total">0.00</span>
                                </td>
                                <td class="px-2 py-1.5 text-right">
                                    <button type="button" class="remove-item text-[10px] text-red-500 hover:underline">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-gray-950/40">
                        <tr>
                            <td colspan="3" class="px-2 py-1.5 text-right text-[11px] text-gray-600 dark:text-gray-300">
                                Subtotal
                            </td>
                            <td class="px-2 py-1.5 text-right text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                                ₹<span id="subtotal-display">0.00</span>
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="px-2 py-1.5 text-right text-[11px] text-gray-600 dark:text-gray-300">
                                Shipping
                            </td>
                            <td class="px-2 py-1.5 text-right">
                                <input type="number" step="0.01" min="0"
                                       name="shipping_total"
                                       value="{{ old('shipping_total', 0) }}"
                                       class="w-24 text-right rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-1 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                                       id="shipping-input">
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="px-2 py-1.5 text-right text-[11px] text-gray-600 dark:text-gray-300">
                                Approx. total (excl. GST)
                            </td>
                            <td class="px-2 py-1.5 text-right text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                                ₹<span id="approx-total-display">0.00</span>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Note + submit --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-3 space-y-2">
            <div class="space-y-1">
                <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                    Customer note (optional)
                </label>
                <textarea name="customer_note" rows="2"
                          class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">{{ old('customer_note') }}</textarea>
            </div>

            <div class="flex items-center justify-between">
                <p class="text-[10px] text-gray-500 dark:text-gray-400">
                    GST will be calculated automatically based on state (Maharashtra vs other states).
                </p>
                <button type="submit"
                        class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                    Create order & invoice
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    (function () {
        // ====== Auto-fill address based on customer default address ======
        const customerSelect = document.getElementById('customer-select');

        function fillAddressFromOption(option) {
            if (!option) return;
            document.getElementById('addr_full_name').value = option.dataset.fullName || '';
            document.getElementById('addr_phone').value      = option.dataset.phone || '';
            document.getElementById('addr_line1').value      = option.dataset.line1 || '';
            document.getElementById('addr_line2').value      = option.dataset.line2 || '';
            document.getElementById('addr_city').value       = option.dataset.city || '';
            document.getElementById('addr_state').value      = option.dataset.state || '';
            document.getElementById('addr_state_code').value = option.dataset.stateCode || '';
            document.getElementById('addr_country').value    = option.dataset.country || 'India';
            document.getElementById('addr_pincode').value    = option.dataset.pincode || '';
            document.getElementById('addr_gstin').value      = option.dataset.gstin || '';
        }

        if (customerSelect) {
            customerSelect.addEventListener('change', function () {
                const opt = this.selectedOptions[0];
                fillAddressFromOption(opt);
            });

            // If old user_id exists, auto fill on load
            @if(old('user_id'))
                (function () {
                    const opt = customerSelect.selectedOptions[0];
                    fillAddressFromOption(opt);
                })();
            @endif
        }

        // ====== Items + totals + DB price auto-fill ======
        const itemsBody = document.getElementById('items-body');
        const addBtn    = document.getElementById('add-item-row');
        const productOptionsHtml = "{!! $productOptionsHtml !!}";

        function recalcTotals() {
            let subtotal = 0;

            document.querySelectorAll('#items-body tr.item-row').forEach(function (row) {
                const qtyInput   = row.querySelector('.item-qty');
                const priceInput = row.querySelector('.item-price');
                const totalSpan  = row.querySelector('.item-total');

                const qty   = parseFloat(qtyInput.value || '0');
                const price = parseFloat(priceInput.value || '0');
                const line  = qty * price;

                subtotal += line;
                totalSpan.textContent = line.toFixed(2);
            });

            const shippingInput = document.getElementById('shipping-input');
            const shipping      = parseFloat(shippingInput.value || '0');

            document.getElementById('subtotal-display').textContent = subtotal.toFixed(2);
            document.getElementById('approx-total-display').textContent = (subtotal + shipping).toFixed(2);
        }

        function bindRowEvents(row) {
            row.querySelectorAll('.item-qty, .item-price').forEach(function (input) {
                input.addEventListener('input', recalcTotals);
            });

            const removeBtn = row.querySelector('.remove-item');
            removeBtn.addEventListener('click', function () {
                if (document.querySelectorAll('#items-body tr.item-row').length > 1) {
                    row.remove();
                    recalcTotals();
                }
            });

            const productSelect = row.querySelector('.item-product-select');
            if (productSelect) {
                productSelect.addEventListener('change', function () {
                    const selected = this.selectedOptions[0];
                    if (selected && selected.dataset.price !== undefined) {
                        const priceInput = row.querySelector('.item-price');
                        priceInput.value = parseFloat(selected.dataset.price || '0').toFixed(2);
                        recalcTotals();
                    }
                });
            }
        }

        if (addBtn && itemsBody) {
            addBtn.addEventListener('click', function () {
                const index = document.querySelectorAll('#items-body tr.item-row').length;

                const tr = document.createElement('tr');
                tr.classList.add('item-row');
                tr.innerHTML = `
                    <td class="px-2 py-1.5">
                        <select name="items[${index}][product_id]"
                                class="item-product-select w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-1.5 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                            ${productOptionsHtml}
                        </select>
                    </td>
                    <td class="px-2 py-1.5 text-right">
                        <input type="number" step="0.01" min="0.01"
                               name="items[${index}][quantity]"
                               value="1"
                               class="w-20 text-right rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-1 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500 item-qty">
                    </td>
                    <td class="px-2 py-1.5 text-right">
                        <input type="number" step="0.01" min="0"
                               name="items[${index}][unit_price]"
                               value="0"
                               class="w-24 text-right rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-1 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500 item-price">
                    </td>
                    <td class="px-2 py-1.5 text-right text-gray-900 dark:text-gray-50">
                        ₹<span class="item-total">0.00</span>
                    </td>
                    <td class="px-2 py-1.5 text-right">
                        <button type="button" class="remove-item text-[10px] text-red-500 hover:underline">
                            Remove
                        </button>
                    </td>
                `;
                itemsBody.appendChild(tr);
                bindRowEvents(tr);
                recalcTotals();
            });
        }

        document.querySelectorAll('#items-body tr.item-row').forEach(bindRowEvents);

        const shippingInput = document.getElementById('shipping-input');
        if (shippingInput) {
            shippingInput.addEventListener('input', recalcTotals);
        }

        recalcTotals();
    })();
</script>
@endsection
