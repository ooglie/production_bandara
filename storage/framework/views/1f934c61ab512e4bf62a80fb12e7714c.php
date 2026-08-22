<?php
    /** @var \App\Models\ProductVariant|null $variant */
    $isEdit = isset($variant);

    $defaultPricingUnit = old(
        'pricing_unit',
        $variant->pricing_unit ?? (($product->sell_unit ?? 'piece') === 'kg' ? 'kg' : 'pack')
    );

    $gstRate = (float) ($product->effective_gst_rate ?? $product->gst_rate ?? 0);
    $factor = 1 + ($gstRate / 100);
    $b2cIncludesGst = (bool) ($product->b2c_price_includes_gst ?? true);
    $b2bIncludesGst = (bool) ($product->b2b_price_includes_gst ?? false);

    $variantPriceInput = old('price');
    if ($variantPriceInput === null) {
        $stored = (float) ($variant->price ?? $product->base_price ?? 0);
        $variantPriceInput = $b2cIncludesGst && $factor > 0 ? round($stored * $factor, 2) : round($stored, 2);
    }

    $variantMrpInput = old('mrp_price');
    if ($variantMrpInput === null) {
        $storedMrp = $variant->mrp_price ?? null;
        $variantMrpInput = $storedMrp === null || $storedMrp === ''
            ? ''
            : ($b2cIncludesGst && $factor > 0 ? round((float) $storedMrp * $factor, 2) : round((float) $storedMrp, 2));
    }

    $defaultPackType = old('pack_type', $variant->pack_type ?? 'quantity');
    $defaultCustomerVisibility = old('customer_visibility', $variant->customer_visibility ?? 'all');

    $variantB2BPriceInput = old('standard_b2b_price');
    if ($variantB2BPriceInput === null) {
        $storedB2B = $variant->standard_b2b_price ?? null;
        $variantB2BPriceInput = $storedB2B === null || $storedB2B === ''
            ? ''
            : ($b2bIncludesGst && $factor > 0 ? round((float) $storedB2B * $factor, 2) : round((float) $storedB2B, 2));
    }
?>

<form method="POST" action="<?php echo e($action); ?>">
    <?php echo csrf_field(); ?>
    <?php if($isEdit): ?>
        <?php echo method_field('PUT'); ?>
    <?php endif; ?>

    <div class="space-y-5">
        <?php if(session('status')): ?>
            <div class="rounded-sm border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
                <?php echo e(session('status')); ?>

            </div>
        <?php endif; ?>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    SKU
                </label>
                <input
                    type="text"
                    name="sku"
                    value="<?php echo e(old('sku', $variant->sku ?? '')); ?>"
                    required
                    class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                <?php $__errorArgs = ['sku'];
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
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Pack option label
                </label>
                <input
                    type="text"
                    name="name"
                    value="<?php echo e(old('name', $variant->name ?? '')); ?>"
                    placeholder="e.g. 10 pcs pack or 20 pcs pack"
                    class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                <p class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                    This is the customer-facing option shown in the variant dropdown.
                </p>
                <?php $__errorArgs = ['name'];
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

        <div class="rounded-sm border border-amber-200 dark:border-amber-900/60 bg-amber-50 dark:bg-amber-950/20 px-3 py-2 text-[11px] text-amber-800 dark:text-amber-200">
            Use variants only for true customer choices such as Dimsum 10/20/100 pcs or Prawns Jumbo 500g / Jumbo 1kg. Do not create variants for vendor lots, pork belly pieces, or slab weights.
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Pack variant type
                </label>
                <select
                    name="pack_type"
                    data-pack-type
                    class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                    <option value="quantity" <?php if($defaultPackType === 'quantity'): echo 'selected'; endif; ?>>Quantity / normal pack</option>
                    <option value="fixed_piece_pack" <?php if($defaultPackType === 'fixed_piece_pack'): echo 'selected'; endif; ?>>Fixed piece pack</option>
                    <option value="fixed_weight_pack" <?php if($defaultPackType === 'fixed_weight_pack'): echo 'selected'; endif; ?>>Fixed weight pack</option>
                </select>
                <?php $__errorArgs = ['pack_type'];
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

            <div data-pieces-per-pack-wrap>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Pieces per pack
                </label>
                <input
                    type="number"
                    step="0.001"
                    min="0"
                    name="pieces_per_pack"
                    value="<?php echo e(old('pieces_per_pack', $variant->pieces_per_pack ?? '')); ?>"
                    placeholder="e.g. 10 or 20"
                    class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                <?php $__errorArgs = ['pieces_per_pack'];
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
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    MRP (₹<?php echo e($b2cIncludesGst ? ', incl GST' : ', excl GST'); ?>) <span class="text-red-500">*</span> <span class="text-[10px] font-normal text-gray-400">when active</span>
                </label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="mrp_price"
                    value="<?php echo e($variantMrpInput); ?>"
                    placeholder="Required for active variants"
                    class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                <?php $__errorArgs = ['mrp_price'];
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
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    B2C variant price (₹<?php echo e($b2cIncludesGst ? ', incl GST' : ', excl GST'); ?>) <span class="text-red-500">*</span> <span class="text-[10px] font-normal text-gray-400">when active</span>
                </label>
                <input
                    type="number"
                    step="0.01"
                    name="price"
                    value="<?php echo e($variantPriceInput); ?>"
                    class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Active variants need their own MRP and sell price; parent product pricing can stay blank.</p>
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
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Standard B2B price (₹, excl GST by default)
                </label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="standard_b2b_price"
                    value="<?php echo e($variantB2BPriceInput); ?>"
                    placeholder="Optional B2B price"
                    class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                <?php $__errorArgs = ['standard_b2b_price'];
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
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Standard B2B MOQ
                </label>
                <input
                    type="number"
                    step="0.001"
                    min="0"
                    name="standard_b2b_min_order_quantity"
                    value="<?php echo e(old('standard_b2b_min_order_quantity', $variant->standard_b2b_min_order_quantity ?? '')); ?>"
                    placeholder="Optional"
                    class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                <?php $__errorArgs = ['standard_b2b_min_order_quantity'];
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
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Customer visibility
                </label>
                <select
                    name="customer_visibility"
                    class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                    <option value="all" <?php if($defaultCustomerVisibility === 'all'): echo 'selected'; endif; ?>>B2C + B2B</option>
                    <option value="b2c" <?php if($defaultCustomerVisibility === 'b2c'): echo 'selected'; endif; ?>>B2C only</option>
                    <option value="b2b" <?php if($defaultCustomerVisibility === 'b2b'): echo 'selected'; endif; ?>>B2B only</option>
                </select>
                <p class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">Use B2B only for options such as Box of 12 cheese packs.</p>
                <?php $__errorArgs = ['customer_visibility'];
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

            <div data-product-weight-wrap>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Pack weight (kg)
                </label>
                <input
                    type="number"
                    step="0.001"
                    min="0"
                    name="product_weight"
                    value="<?php echo e(old('product_weight', $variant->product_weight ?? '')); ?>"
                    placeholder="e.g. 0.500 for 500 g"
                    class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                <p class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                    Required for fixed-weight packs. Leave blank for piece packs such as Dimsum 10 pcs.
                </p>
                <?php $__errorArgs = ['product_weight'];
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
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Pricing unit
                </label>
                <select
                    name="pricing_unit"
                    class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                    <option value="pack" <?php if($defaultPricingUnit === 'pack'): echo 'selected'; endif; ?>>Pack</option>
                    <option value="kg" <?php if($defaultPricingUnit === 'kg'): echo 'selected'; endif; ?>>Kg</option>
                </select>
                <?php $__errorArgs = ['pricing_unit'];
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
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Stock qty
                </label>
                <input
                    type="number"
                    step="0.01"
                    name="stock_quantity"
                    value="<?php echo e(old('stock_quantity', $variant->stock_quantity ?? '')); ?>"
                    class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                <?php $__errorArgs = ['stock_quantity'];
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
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Low stock threshold
                </label>
                <input
                    type="number"
                    step="0.01"
                    name="low_stock_threshold"
                    value="<?php echo e(old('low_stock_threshold', $variant->low_stock_threshold ?? '')); ?>"
                    class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                <?php $__errorArgs = ['low_stock_threshold'];
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
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Min order qty
                </label>
                <input
                    type="number"
                    step="0.01"
                    name="min_order_quantity"
                    value="<?php echo e(old('min_order_quantity', $variant->min_order_quantity ?? '')); ?>"
                    class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
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
        </div>

        <div class="flex flex-wrap gap-4 text-xs">
            <label class="inline-flex items-center gap-2">
                <input type="hidden" name="manage_stock" value="0">
                <input
                    type="checkbox"
                    name="manage_stock"
                    value="1"
                    <?php if(old('manage_stock', $variant->manage_stock ?? true)): echo 'checked'; endif; ?>
                >
                <span>Manage stock</span>
            </label>

            <label class="inline-flex items-center gap-2">
                <input type="hidden" name="inventory_can_repack" value="0">
                <input
                    type="checkbox"
                    name="inventory_can_repack"
                    value="1"
                    <?php if(old('inventory_can_repack', $variant->inventory_can_repack ?? false)): echo 'checked'; endif; ?>
                >
                <span>Can be used as source in Transform Stock</span>
            </label>

            <label class="inline-flex items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    <?php if(old('is_active', $variant->is_active ?? true)): echo 'checked'; endif; ?>
                >
                <span>Active</span>
            </label>
        </div>

        <p class="text-[10px] text-gray-500 dark:text-gray-400">
            Enable this only for inward/master-carton variants such as Dimsum 100 pcs or Cheese box of 12. It does not change customer visibility or storefront behaviour.
        </p>

        <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                Variant option combination
            </label>

            <?php if($attributeValuesByAttribute->isEmpty()): ?>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                    No variant options configured for this product.
                    Set allowed option values on the product first. For two-level products such as Prawns, add option groups like Size and Pack Size, then assign both values to each variant.
                </p>
            <?php else: ?>
                <div class="space-y-3 text-xs">
                    <?php $__currentLoopData = $attributeValuesByAttribute; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attributeId => $values): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $attribute = $values->first()->attribute;
                            $name = $attribute->display_name ?? $attribute->name;
                            $selectedValueId = old("variant_attributes.$attributeId", $existingVariantAttributes[$attributeId] ?? null);
                        ?>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                <?php echo e($name); ?>

                            </label>
                            <select
                                name="variant_attributes[<?php echo e($attributeId); ?>]"
                                class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                            >
                                <option value="">— Not set —</option>
                                <?php $__currentLoopData = $values->sortBy('position'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($value->id); ?>" <?php if((int)$selectedValueId === (int)$value->id): echo 'selected'; endif; ?>>
                                        <?php echo e($value->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>

            <?php $__errorArgs = ['variant_attributes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            <?php $__errorArgs = ['variant_attributes.*'];
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

        <div class="flex items-center gap-3">
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-xs font-medium hover:bg-gray-800 dark:hover:bg-gray-200"
            >
                <?php echo e($isEdit ? 'Update variant' : 'Create variant'); ?>

            </button>

            <a href="<?php echo e(route('admin.products.variants.index', $product)); ?>"
               class="text-xs text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">
                Cancel
            </a>
        </div>
    </div>
</form>

<?php $__env->startPush('scripts'); ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const packType = document.querySelector('[data-pack-type]');
        const piecesWrap = document.querySelector('[data-pieces-per-pack-wrap]');
        const weightWrap = document.querySelector('[data-product-weight-wrap]');

        function refreshPackFields() {
            const type = packType ? packType.value : 'quantity';
            if (piecesWrap) {
                piecesWrap.classList.toggle('hidden', type !== 'fixed_piece_pack');
            }
            if (weightWrap) {
                weightWrap.classList.toggle('hidden', type === 'fixed_piece_pack');
            }
        }

        if (packType) {
            packType.addEventListener('change', refreshPackFields);
        }
        refreshPackFields();
    });
</script>
<?php $__env->stopPush(); ?>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/products/variants/_form.blade.php ENDPATH**/ ?>