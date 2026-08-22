<?php
    /** @var \App\Models\User $user */
    /** @var \App\Models\B2BCustomerProduct|null $row */
    $isEdit = isset($row);
    $priceValue = old('price', isset($priceOverride) ? $priceOverride?->price : null);
?>

<div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3 space-y-3">
    <?php if (! ($isEdit)): ?>
        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                Product / variant option
            </label>
            <select name="assignment_target"
                    class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                    required>
                <option value="">Select product or variant…</option>
                <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <optgroup label="<?php echo e($p->name); ?><?php if($p->sku): ?> (<?php echo e($p->sku); ?>)<?php endif; ?>">
                        <option value="product:<?php echo e($p->id); ?>" <?php if(old('assignment_target') === 'product:' . $p->id): echo 'selected'; endif; ?>>
                            Product-level access
                        </option>
                        <?php $__currentLoopData = $p->variants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $variant): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php ($variantLabel = $variant->name ?: ($variant->sku ?: ('Variant #' . $variant->id))); ?>
                            <option value="variant:<?php echo e($variant->id); ?>" <?php if(old('assignment_target') === 'variant:' . $variant->id): echo 'selected'; endif; ?>>
                                <?php echo e($variantLabel); ?> <?php if($variant->sku): ?> · <?php echo e($variant->sku); ?> <?php endif; ?>
                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </optgroup>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <p class="mt-1 text-[10px] text-gray-400">
                Use product-level access for simple products. Use variants for pack choices such as Dimsum 10 pcs / 20 pcs.
            </p>
            <?php $__errorArgs = ['assignment_target'];
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
    <?php else: ?>
        <div class="rounded-lg border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 text-[11px] text-gray-700 dark:text-gray-200">
            <div>
                <span class="text-gray-500 dark:text-gray-400">Product:</span>
                <span class="font-medium"><?php echo e($row->product?->name ?? ('Product #' . $row->product_id)); ?></span>
            </div>
            <div class="mt-0.5">
                <span class="text-gray-500 dark:text-gray-400">Variant:</span>
                <span class="font-medium"><?php echo e($row->productVariant?->name ?: ($row->productVariant?->sku ?: 'Product-level access')); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid gap-3 sm:grid-cols-3">
        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                MOQ
            </label>
            <input type="number"
                   step="0.01"
                   min="0.01"
                   name="min_order_quantity"
                   value="<?php echo e(old('min_order_quantity', $row->min_order_quantity ?? 1)); ?>"
                   class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
            <p class="mt-1 text-[10px] text-gray-400">
                Applies to this product or variant only. Default is 1.
            </p>
            <?php $__errorArgs = ['min_order_quantity'];
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
                B2B price optional
            </label>
            <input type="number"
                   step="0.01"
                   min="0"
                   name="price"
                   value="<?php echo e($priceValue); ?>"
                   placeholder="Leave blank to keep existing/fallback"
                   class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
            <p class="mt-1 text-[10px] text-gray-400">
                Saved as a customer-specific product/variant price.
            </p>
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

        <div class="flex items-center gap-2 pt-5">
            <input type="checkbox"
                   id="is_active"
                   name="is_active"
                   value="1"
                   <?php if(old('is_active', $row->is_active ?? true)): echo 'checked'; endif; ?>>
            <label for="is_active" class="text-[11px] text-gray-700 dark:text-gray-200">
                Active (visible to customer)
            </label>
        </div>
    </div>
</div>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/customers/b2b-products/_form.blade.php ENDPATH**/ ?>