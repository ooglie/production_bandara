<?php $__env->startSection('title', 'Add Customer Price'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $variantsRouteExists = \Illuminate\Support\Facades\Route::has('admin.products.variants.options');
?>

<div class="max-w-4xl mx-auto px-4 py-5 text-xs space-y-4">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-50">
                Add Customer-specific Price
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Customer: <span class="text-gray-900 dark:text-gray-50 font-medium"><?php echo e($user->name ?? '—'); ?></span>
                <span class="text-gray-400">(#<?php echo e($user->id); ?>)</span>
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="<?php echo e(route('admin.b2b.prices.index', $user)); ?>"
               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                Back
            </a>
        </div>
    </div>

    <?php if($errors->any()): ?>
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
            <ul class="list-disc pl-4 space-y-0.5">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(route('admin.b2b.prices.store', $user)); ?>"
          class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-4">
        <?php echo csrf_field(); ?>

        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Product
                </label>
                <select name="product_id" id="product_id"
                        class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]">
                    <option value="">Select product…</option>
                    <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($p->id); ?>" <?php if((int)old('product_id') === (int)$p->id): echo 'selected'; endif; ?>>
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
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Variant (optional)
                </label>

                <select name="product_variant_id" id="product_variant_id"
                        class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]">
                    <option value="">Product-level (no variant)</option>
                </select>

                <p class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                    If you select a variant, it overrides product-level pricing for that variant only.
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

        <div class="grid gap-3 sm:grid-cols-3">
            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Price (₹)
                </label>
                <input type="number" step="0.01" min="0"
                       name="price" value="<?php echo e(old('price')); ?>"
                       class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]">
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
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Valid from (optional)
                </label>
                <input type="date" name="valid_from" value="<?php echo e(old('valid_from')); ?>"
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
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Valid to (optional)
                </label>
                <input type="date" name="valid_to" value="<?php echo e(old('valid_to')); ?>"
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

        <div class="flex items-center gap-2">
            <label class="inline-flex items-center gap-2 text-[11px] text-gray-700 dark:text-gray-200">
                <input type="checkbox" name="is_active" value="1" <?php if(old('is_active', true)): echo 'checked'; endif; ?>>
                <span>Active</span>
            </label>

            <input type="hidden" name="currency" value="INR">
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
            <a href="<?php echo e(route('admin.b2b.prices.index', $user)); ?>"
               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                Cancel
            </a>
            <button type="submit"
                    class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                Save
            </button>
        </div>
    </form>

    <?php if(!$variantsRouteExists): ?>
        <div class="rounded border border-yellow-300 bg-yellow-50 px-3 py-2 text-[11px] text-yellow-800">
            Variant dropdown endpoint missing. Add route <code>admin.products.variants.options</code> to enable variants.
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    const productSelect = document.getElementById('product_id');
    const variantSelect = document.getElementById('product_variant_id');

    const selectedVariantId = <?php echo json_encode(old('product_variant_id'), 15, 512) ?>;
    const routeExists = <?php echo json_encode(\Illuminate\Support\Facades\Route::has('admin.products.variants.options'), 15, 512) ?>;

    const urlTemplate = routeExists
        ? <?php echo json_encode(route('admin.products.variants.options', ['product' => '__PRODUCT__']), 512) ?>
        : null;

    function resetVariants() {
        variantSelect.innerHTML = '';
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = 'Product-level (no variant)';
        variantSelect.appendChild(opt);
    }

    async function loadVariants(productId, preselect = null) {
        resetVariants();
        if (!productId || !urlTemplate) return;

        const url = urlTemplate.replace('__PRODUCT__', productId);

        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
            if (!res.ok) return;

            const data = await res.json();
            const variants = (data && data.variants) ? data.variants : [];

            variants.forEach(v => {
                const opt = document.createElement('option');
                opt.value = v.id;
                opt.textContent = v.label || ('Variant #' + v.id);
                variantSelect.appendChild(opt);
            });

            if (preselect) {
                variantSelect.value = String(preselect);
            }
        } catch (e) {
            // silent
        }
    }

    if (productSelect) {
        productSelect.addEventListener('change', function () {
            loadVariants(this.value, null);
        });

        // Initial load (if product already selected due to validation)
        if (productSelect.value) {
            loadVariants(productSelect.value, selectedVariantId);
        }
    }
})();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/b2b/prices/create.blade.php ENDPATH**/ ?>