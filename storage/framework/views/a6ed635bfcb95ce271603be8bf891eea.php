<?php $__env->startSection('title', 'Create order'); ?>

<?php $__env->startSection('content'); ?>
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

        <a href="<?php echo e(route('admin.orders.index')); ?>"
           class="text-[11px] text-gray-500 dark:text-gray-400 hover:underline">
            ← Back to orders
        </a>
    </div>

    <?php if($errors->any()): ?>
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
            <ul class="list-disc list-inside space-y-0.5">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(route('admin.orders.store')); ?>" class="space-y-4">
        <?php echo csrf_field(); ?>

        
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
                    <?php $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $defaultAddress = $customer->customerAddresses->first();
                        ?>
                        <option
                            value="<?php echo e($customer->id); ?>"
                            data-full-name="<?php echo e(e(optional($defaultAddress)->full_name ?? $customer->name)); ?>"
                            data-phone="<?php echo e(e(optional($defaultAddress)->phone ?? $customer->phone ?? '')); ?>"
                            data-line1="<?php echo e(e(optional($defaultAddress)->address_line1 ?? '')); ?>"
                            data-line2="<?php echo e(e(optional($defaultAddress)->address_line2 ?? '')); ?>"
                            data-city="<?php echo e(e(optional($defaultAddress)->city ?? '')); ?>"
                            data-state="<?php echo e(e(optional($defaultAddress)->state ?? '')); ?>"
                            data-state-code="<?php echo e(e(optional($defaultAddress)->state_code ?? '')); ?>"
                            data-country="<?php echo e(e(optional($defaultAddress)->country ?? 'India')); ?>"
                            data-pincode="<?php echo e(e(optional($defaultAddress)->pincode ?? '')); ?>"
                            data-gstin="<?php echo e(e(optional($defaultAddress)->gstin ?? '')); ?>"
                            <?php if(old('user_id') == $customer->id): echo 'selected'; endif; ?>
                        >
                            <?php echo e($customer->name); ?>

                            <?php if($customer->email): ?> (<?php echo e($customer->email); ?>) <?php endif; ?>
                            <?php if($defaultAddress): ?>
                                – <?php echo e($defaultAddress->city); ?>, <?php echo e($defaultAddress->state); ?>

                            <?php endif; ?>
                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <p class="text-[10px] text-gray-400 mt-1">
                    Default address will auto-fill below. You can still edit it manually.
                </p>
            </div>
        </div>

        
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-3 space-y-2">
            <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                Billing & shipping address
            </p>

            <div class="grid sm:grid-cols-2 gap-3">
                <div class="space-y-1">
                    <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                        Full name
                    </label>
                    <input type="text" name="full_name" id="addr_full_name" value="<?php echo e(old('full_name')); ?>"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>

                <div class="space-y-1">
                    <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                        Phone
                    </label>
                    <input type="text" name="phone" id="addr_phone" value="<?php echo e(old('phone')); ?>"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>

                <div class="space-y-1 sm:col-span-2">
                    <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                        Address line 1
                    </label>
                    <input type="text" name="address_line1" id="addr_line1" value="<?php echo e(old('address_line1')); ?>"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>

                <div class="space-y-1 sm:col-span-2">
                    <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                        Address line 2 (optional)
                    </label>
                    <input type="text" name="address_line2" id="addr_line2" value="<?php echo e(old('address_line2')); ?>"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>

                <div class="space-y-1">
                    <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                        City
                    </label>
                    <input type="text" name="city" id="addr_city" value="<?php echo e(old('city')); ?>"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>

                <div class="space-y-1">
                    <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                        State
                    </label>
                    <input type="text" name="state" id="addr_state" value="<?php echo e(old('state')); ?>"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>

                <div class="space-y-1">
                    <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                        State code (optional)
                    </label>
                    <input type="text" name="state_code" id="addr_state_code" value="<?php echo e(old('state_code')); ?>"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>

                <div class="space-y-1">
                    <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                        Country
                    </label>
                    <input type="text" name="country" id="addr_country" value="<?php echo e(old('country', 'India')); ?>"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>

                <div class="space-y-1">
                    <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                        Pincode
                    </label>
                    <input type="text" name="pincode" id="addr_pincode" value="<?php echo e(old('pincode')); ?>"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>

                <div class="space-y-1 sm:col-span-2">
                    <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                        GSTIN (optional)
                    </label>
                    <input type="text" name="gstin" id="addr_gstin" value="<?php echo e(old('gstin')); ?>" maxlength="15" autocomplete="off"
                           class="w-full uppercase rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>
            </div>
        </div>

        
        <?php
            $oldItems = old('items', [['product_id' => '', 'quantity' => 1, 'unit_price' => 0]]);
            $productOptionsHtml = '<option value=\"\">-- Select --</option>';
            foreach ($products as $product) {
                $price = $product->price ?? $product->base_price ?? 0;
                $productOptionsHtml .= '<option value=\"'.$product->id.'\" data-price=\"'.$price.'\">'.e($product->name).'</option>';
            }
        ?>

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
                        <?php $__currentLoopData = $oldItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr class="item-row">
                                <td class="px-2 py-1.5">
                                    <select name="items[<?php echo e($index); ?>][product_id]"
                                            class="item-product-select w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-1.5 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                                        <option value="">-- Select --</option>
                                        <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php
                                                $price = $product->price ?? $product->base_price ?? 0;
                                            ?>
                                            <option value="<?php echo e($product->id); ?>"
                                                    data-price="<?php echo e($price); ?>"
                                                    <?php if($item['product_id'] == $product->id): echo 'selected'; endif; ?>>
                                                <?php echo e($product->name); ?>

                                            </option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </td>
                                <td class="px-2 py-1.5 text-right">
                                    <input type="number" step="0.01" min="0.01"
                                           name="items[<?php echo e($index); ?>][quantity]"
                                           value="<?php echo e($item['quantity'] ?? 1); ?>"
                                           class="w-20 text-right rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-1 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500 item-qty">
                                </td>
                                <td class="px-2 py-1.5 text-right">
                                    <input type="number" step="0.01" min="0"
                                           name="items[<?php echo e($index); ?>][unit_price]"
                                           value="<?php echo e($item['unit_price'] ?? 0); ?>"
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
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                                       value="<?php echo e(old('shipping_total', 0)); ?>"
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

        
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-3 space-y-2">
            <div class="space-y-1">
                <label class="block text-[10px] text-gray-500 dark:text-gray-400">
                    Customer note (optional)
                </label>
                <textarea name="customer_note" rows="2"
                          class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"><?php echo e(old('customer_note')); ?></textarea>
            </div>

            <div class="flex items-center justify-between">
                <p class="text-[10px] text-gray-500 dark:text-gray-400">
                    With a GSTIN, GST follows its state code. Without a GSTIN, GST follows this order address. Customer checkout supports separate Bill-To and Ship-To addresses.
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
            <?php if(old('user_id')): ?>
                (function () {
                    const opt = customerSelect.selectedOptions[0];
                    fillAddressFromOption(opt);
                })();
            <?php endif; ?>
        }

        // ====== Items + totals + DB price auto-fill ======
        const itemsBody = document.getElementById('items-body');
        const addBtn    = document.getElementById('add-item-row');
        const productOptionsHtml = "<?php echo $productOptionsHtml; ?>";

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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/orders/create.blade.php ENDPATH**/ ?>