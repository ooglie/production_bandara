<?php
    /** @var \App\Models\User $user */
    /** @var \App\Models\CustomerProductPrice|null $price */
    $selectedProductId = old('product_id', $price->product_id ?? '');
    $selectedVariantId = old('product_variant_id', $price->product_variant_id ?? '');
?>

<div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-4">
    <div class="grid gap-3 md:grid-cols-2">
        <div>
            <label class="block text-[11px] text-gray-600 dark:text-gray-300 mb-1">Product</label>
            <select id="b2b-price-product"
                    name="product_id"
                    class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]"
                    required>
                <option value="">Select…</option>
                <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($p->id); ?>"
                        <?php if((int) $selectedProductId === (int) $p->id): echo 'selected'; endif; ?>>
                        <?php echo e($p->name); ?> <?php if($p->sku): ?> (<?php echo e($p->sku); ?>) <?php endif; ?>
                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <?php $__errorArgs = ['product_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div>
            <label class="block text-[11px] text-gray-600 dark:text-gray-300 mb-1">
                Variant (optional)
            </label>

            <select id="b2b-price-variant"
                    name="product_variant_id"
                    data-url-template="<?php echo e(route('admin.products.variants.options', ['product' => '__PRODUCT__'])); ?>"
                    data-selected="<?php echo e($selectedVariantId); ?>"
                    class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]">
                <option value="">Product-level (no variant)</option>
            </select>

            <p class="mt-1 text-[10px] text-gray-400">
                Use variants for customer-specific pack prices such as Dimsum 10 pcs / 20 pcs.
            </p>

            <?php $__errorArgs = ['product_variant_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
    </div>

    <div class="grid gap-3 md:grid-cols-4">
        <div>
            <label class="block text-[11px] text-gray-600 dark:text-gray-300 mb-1">Price (₹, excl GST by default)</label>
            <input type="number" step="0.01" min="0" name="price"
                   value="<?php echo e(old('price', $price->price ?? '')); ?>"
                   class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]"
                   required>
            <?php $__errorArgs = ['price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div>
            <label class="block text-[11px] text-gray-600 dark:text-gray-300 mb-1">Currency</label>
            <input type="text" name="currency" maxlength="3"
                   value="<?php echo e(old('currency', $price->currency ?? 'INR')); ?>"
                   class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]">
            <?php $__errorArgs = ['currency'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div>
            <label class="block text-[11px] text-gray-600 dark:text-gray-300 mb-1">Valid from (optional)</label>
            <input type="date" name="valid_from"
                   value="<?php echo e(old('valid_from', $price->valid_from ?? '')); ?>"
                   class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]">
            <?php $__errorArgs = ['valid_from'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div>
            <label class="block text-[11px] text-gray-600 dark:text-gray-300 mb-1">Valid to (optional)</label>
            <input type="date" name="valid_to"
                   value="<?php echo e(old('valid_to', $price->valid_to ?? '')); ?>"
                   class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]">
            <?php $__errorArgs = ['valid_to'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
    </div>

    <label class="inline-flex items-center gap-2 text-[11px] text-gray-700 dark:text-gray-200">
        <input type="checkbox" name="is_active" value="1" <?php if(old('is_active', $price->is_active ?? true)): echo 'checked'; endif; ?>>
        <span>Active</span>
    </label>
</div>

<script>
(function () {
    const productSelect = document.getElementById('b2b-price-product');
    const variantSelect = document.getElementById('b2b-price-variant');
    if (!productSelect || !variantSelect) return;

    const urlTemplate = variantSelect.dataset.urlTemplate;
    const selectedVariantId = (variantSelect.dataset.selected || '').toString();

    function setVariantOptions(options, selectedId) {
        variantSelect.innerHTML = '<option value="">Product-level (no variant)</option>';

        (options || []).forEach(v => {
            const opt = document.createElement('option');
            opt.value = v.id;
            opt.textContent = v.label || ('Variant #' + v.id);
            variantSelect.appendChild(opt);
        });

        if (selectedId) {
            variantSelect.value = selectedId;
        }
    }

    async function loadVariants(productId, selectedId) {
        if (!productId) {
            setVariantOptions([], '');
            variantSelect.disabled = true;
            return;
        }

        variantSelect.disabled = true;
        setVariantOptions([], selectedId);

        const url = urlTemplate.replace('__PRODUCT__', productId);

        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('Failed to load variants');
            const data = await res.json();

            if (!data || !data.ok) throw new Error('Failed to load variants');

            setVariantOptions(data.variants || [], selectedId);
        } catch (e) {
            setVariantOptions([], '');
        } finally {
            variantSelect.disabled = false;
        }
    }

    productSelect.addEventListener('change', function () {
        loadVariants(productSelect.value, '');
    });

    if (productSelect.value) {
        loadVariants(productSelect.value, selectedVariantId);
    }
})();
</script>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/b2b/prices/_form.blade.php ENDPATH**/ ?>