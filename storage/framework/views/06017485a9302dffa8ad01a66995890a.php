
<?php
    /** @var \App\Models\Product|null $product */

    $isEdit = isset($product) && $product instanceof \App\Models\Product && $product->exists;

    $vendors    = $vendors ?? collect();
    $hsnCodes   = $hsnCodes ?? collect();
    $countries  = $countries ?? collect();
    $categories = $categories ?? collect();
    $attributes = $attributes ?? collect();

    $selectedCategoryIds       = $selectedCategoryIds ?? [];
    $selectedAttributeValueIds = $selectedAttributeValueIds ?? [];
    $transformationSourceProducts = $transformationSourceProducts ?? collect();
    $selectedSourceProductIds = collect(old('source_product_ids', $selectedSourceProductIds ?? []))
        ->map(fn($v) => (int) $v)
        ->filter()
        ->values()
        ->all();
    $producesProducts = $producesProducts ?? collect();

    $oldB2CIncludes = old('b2c_price_includes_gst', $product->b2c_price_includes_gst ?? true);
    $b2cIncludesGst = (int) $oldB2CIncludes === 1 || $oldB2CIncludes === true || $oldB2CIncludes === '1';

    $oldB2BIncludes = old('b2b_price_includes_gst', $product->b2b_price_includes_gst ?? false);
    $b2bIncludesGst = (int) $oldB2BIncludes === 1 || $oldB2BIncludes === true || $oldB2BIncludes === '1';

    $specialAudience = old('special_audience', $product->special_audience ?? 'b2c');
    $selectedProductType = old('type', $product->type ?? 'simple');
    $selectedPackType = old('pack_type', $product->pack_type ?? 'quantity');
    $selectedSellUnit = old('sell_unit', $product->sell_unit ?? 'piece');
    $isSelectedVariableProduct = (string) $selectedProductType === 'variable';
    $isSelectedPhysicalChoice = ! $isSelectedVariableProduct
        && ((string) $selectedPackType === 'variable_weight' || (string) $selectedSellUnit === 'kg');

    $selectedHsnId    = old('hsn_code_id', $product->hsn_code_id ?? '');
    $selectedHsnIdInt = $selectedHsnId !== '' ? (int) $selectedHsnId : null;
    $selectedHsn      = $selectedHsnIdInt ? $hsnCodes->firstWhere('id', $selectedHsnIdInt) : null;

    $gstRate = (float) old('gst_rate', $selectedHsn?->gst_rate ?? ($product->gst_rate ?? 5));
    $divisor = 1 + ($gstRate / 100);

    // SELL PRICE (admin input) - stored in DB as base_price EXCL GST
    $sellInput = old('base_price');
    if ($sellInput === null) {
        if (($product->base_price ?? null) === null || ($isSelectedVariableProduct && (float) ($product->base_price ?? 0) <= 0)) {
            $sellInput = '';
        } else {
            $storedSellExcl = (float) ($product->base_price ?? 0);
            if ($b2cIncludesGst) {
                $sellInput = $divisor > 0 ? round($storedSellExcl * $divisor, 2) : round($storedSellExcl, 2);
            } else {
                $sellInput = round($storedSellExcl, 2);
            }
        }
    }

    // MRP (admin input) - stored in DB as mrp_price EXCL GST
    $mrpInput = old('mrp_price');
    if ($mrpInput === null) {
        $storedMrpExcl = (float) ($product->mrp_price ?? 0);

        if (($product->mrp_price ?? null) === null || (($isSelectedVariableProduct || $isSelectedPhysicalChoice) && (float) ($product->mrp_price ?? 0) <= 0)) {
            $mrpInput = '';
        } else {
            if ($b2cIncludesGst) {
                $mrpInput = $divisor > 0 ? round($storedMrpExcl * $divisor, 2) : round($storedMrpExcl, 2);
            } else {
                $mrpInput = round($storedMrpExcl, 2);
            }
        }
    }

    // Standard B2B price (admin input) - stored in DB as EXCL GST
    $standardB2BInput = old('standard_b2b_price');
    if ($standardB2BInput === null) {
        $storedB2BExcl = $product->standard_b2b_price ?? null;
        if ($storedB2BExcl === null || $storedB2BExcl === '') {
            $standardB2BInput = '';
        } else {
            $storedB2BExcl = (float) $storedB2BExcl;
            $standardB2BInput = $b2bIncludesGst && $divisor > 0
                ? round($storedB2BExcl * $divisor, 2)
                : round($storedB2BExcl, 2);
        }
    }

    // Special price - B2C/all specials follow B2C price mode; B2B specials follow B2B price mode.
    $specialInput = old('special_price');
    if ($specialInput === null) {
        $storedSpecialExcl = $product->special_price ?? null;
        if ($storedSpecialExcl === null || $storedSpecialExcl === '') {
            $specialInput = '';
        } else {
            $specialIncludesGst = $specialAudience === 'b2b' ? $b2bIncludesGst : $b2cIncludesGst;
            $storedSpecialExcl = (float) $storedSpecialExcl;
            $specialInput = $specialIncludesGst && $divisor > 0
                ? round($storedSpecialExcl * $divisor, 2)
                : round($storedSpecialExcl, 2);
        }
    }

    $weight = old('product_weight', $product->product_weight ?? '');
    $piecesPerPack = old('pieces_per_pack', $product->pieces_per_pack ?? '');
    $inventoryRole = old('inventory_role', $product->inventory_role ?? (($product->is_active ?? true) ? 'saleable' : 'internal'));
    $packType = $selectedPackType;

    $storageProfileOptions = \App\Models\Product::storageProfileOptions();
    $storageProfileTemplates = \App\Models\Product::storageProfileTemplates();
    $selectedStorageProfile = \App\Models\Product::normalizeStorageProfile(
        old('storage_profile', $product->storage_profile ?? \App\Models\Product::DEFAULT_STORAGE_PROFILE)
    );
    $storageGuidanceValue = old('storage_guidance');
    if ($storageGuidanceValue === null) {
        $storageGuidanceValue = $product->storage_guidance
            ?? \App\Models\Product::storageGuidanceTextForProfile($selectedStorageProfile);
    }
    $deliverySupportValue = old('delivery_support');
    if ($deliverySupportValue === null) {
        $deliverySupportValue = $product->delivery_support
            ?? \App\Models\Product::deliverySupportTextForProfile($selectedStorageProfile);
    }

    $hsnManageUrl = \Illuminate\Support\Facades\Route::has('admin.hsn-codes.index')
        ? route('admin.hsn-codes.index')
        : null;

    $hsnCreateUrl = \Illuminate\Support\Facades\Route::has('admin.hsn-codes.create')
        ? route('admin.hsn-codes.create')
        : null;

    $selectedCountry = old('country_of_origin', $product->country_of_origin ?? '');

    $card  = "rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900";
    $input = "mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2.5 text-[13px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-600";
    $select = $input;
    $hint  = "mt-1 text-[11px] text-gray-500 dark:text-gray-400";

    $oldCategoryIds = collect(old('category_ids', $selectedCategoryIds ?? []))
        ->map(fn($v) => (int) $v)
        ->toArray();

    $oldAttributeValueIds = collect(old('attribute_value_ids', $selectedAttributeValueIds ?? []))
        ->map(fn($v) => (int) $v)
        ->toArray();
?>

<form method="POST" action="<?php echo e($action); ?>" class="space-y-4" data-product-form>
    <?php echo csrf_field(); ?>
    <?php if($isEdit): ?>
        <?php echo method_field('PUT'); ?>
    <?php endif; ?>

    <?php if(session('status')): ?>
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-[12px] text-emerald-800">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <?php if($errors->any()): ?>
        <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-[12px] text-red-800">
            <div class="font-semibold mb-1">Please fix the following:</div>
            <ul class="list-disc pl-5 space-y-1">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    
    <section class="<?php echo e($card); ?>">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-50">
                        Product setup
                    </h2>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">
                        Save inactive drafts while pricing, variants or weight are still pending. Active simple products need parent prices; variant products use variant prices.
                    </p>
                </div>

                <div class="text-[11px] text-gray-500 dark:text-gray-400">
                    Fields marked <span class="text-red-500">*</span> are always required. Price and weight are required before activation.
                </div>
            </div>
        </div>

        <div class="px-5 py-5 space-y-5">
            
            <div class="grid gap-4 md:grid-cols-3">
                <div class="md:col-span-2">
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                        Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="name"
                        value="<?php echo e(old('name', $product->name ?? '')); ?>"
                        class="<?php echo e($input); ?>"
                        required
                        placeholder="Product name"
                    >
                    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                        SKU <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="sku"
                        value="<?php echo e(old('sku', $product->sku ?? '')); ?>"
                        class="<?php echo e($input); ?>"
                        required
                        placeholder="Unique SKU"
                    >
                    <?php $__errorArgs = ['sku'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="md:col-span-3">
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                        Short description <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="short_description"
                        value="<?php echo e(old('short_description', $product->short_description ?? '')); ?>"
                        class="<?php echo e($input); ?>"
                        maxlength="255"
                        required
                        placeholder="Short customer-facing summary"
                    >
                    <div class="<?php echo e($hint); ?>">Keep this concise. It appears in product cards and summary sections.</div>
                    <?php $__errorArgs = ['short_description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="md:col-span-3">
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                        Full description <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        name="description"
                        rows="6"
                        class="<?php echo e($input); ?>"
                        required
                        placeholder="Detailed product description"
                    ><?php echo e(old('description', $product->description ?? '')); ?></textarea>
                    <div class="<?php echo e($hint); ?>">Use this for the full product page description.</div>
                    <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="md:col-span-3">
                    <div class="flex flex-col gap-2 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-950/60 sm:flex-row sm:items-end sm:justify-between">
                        <div class="flex-1">
                            <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                                Storage profile <span class="text-red-500">*</span>
                            </label>
                            <select
                                name="storage_profile"
                                class="<?php echo e($select); ?>"
                                data-storage-profile
                                required
                            >
                                <?php $__currentLoopData = $storageProfileOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $profileValue => $profileLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($profileValue); ?>" <?php if($selectedStorageProfile === $profileValue): echo 'selected'; endif; ?>>
                                        <?php echo e($profileLabel); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <div class="<?php echo e($hint); ?>">
                                Frozen is selected by default. Changing the profile can auto-fill the guidance below; both text fields remain editable.
                            </div>
                            <?php $__errorArgs = ['storage_profile'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <button
                            type="button"
                            class="rounded-sm border border-gray-300 bg-white px-3 py-2 text-[12px] font-medium text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
                            data-storage-profile-apply
                        >
                            Use profile text
                        </button>
                    </div>
                </div>

                <div class="md:col-span-3 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                            Storage guidance
                        </label>
                        <textarea
                            name="storage_guidance"
                            rows="5"
                            class="<?php echo e($input); ?>"
                            placeholder="One guidance point per line"
                            data-storage-guidance
                        ><?php echo e($storageGuidanceValue); ?></textarea>
                        <div class="<?php echo e($hint); ?>">Shown in the product page Storage &amp; Delivery tab. Add one bullet point per line.</div>
                        <?php $__errorArgs = ['storage_guidance'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                            Delivery &amp; support
                        </label>
                        <textarea
                            name="delivery_support"
                            rows="5"
                            class="<?php echo e($input); ?>"
                            placeholder="One delivery/support point per line"
                            data-delivery-support
                        ><?php echo e($deliverySupportValue); ?></textarea>
                        <div class="<?php echo e($hint); ?>">Shown in the product page Storage &amp; Delivery tab. Add one bullet point per line.</div>
                        <?php $__errorArgs = ['delivery_support'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

            </div>

            
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                        HSN Code <span class="text-red-500">*</span>
                    </label>
                    <?php if($hsnCodes->isEmpty()): ?>
                        <div class="mt-1 rounded-sm border border-dashed border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-950/40 px-3 py-3 text-[12px] text-gray-500 dark:text-gray-400">
                            No HSN codes yet.
                            <?php if($hsnCreateUrl): ?>
                                <a href="<?php echo e($hsnCreateUrl); ?>" class="underline">Create one</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <select id="hsn_code_id" name="hsn_code_id" class="<?php echo e($select); ?>" required>
                            <option value="" data-rate="">Select HSN…</option>
                            <?php $__currentLoopData = $hsnCodes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hsn): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($hsn->id); ?>"
                                        data-rate="<?php echo e((float) $hsn->gst_rate); ?>"
                                        <?php if((int) old('hsn_code_id', $product->hsn_code_id ?? 0) === (int) $hsn->id): echo 'selected'; endif; ?>>
                                    <?php echo e($hsn->code); ?>

                                    <?php if($hsn->name): ?> — <?php echo e($hsn->name); ?> <?php endif; ?>
                                    (<?php echo e(number_format((float)$hsn->gst_rate, 2)); ?>% GST)
                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    <?php endif; ?>
                    <div class="<?php echo e($hint); ?>">
                        <?php if($hsnManageUrl): ?>
                            <a href="<?php echo e($hsnManageUrl); ?>" class="underline">Manage HSN codes</a>
                        <?php endif; ?>
                    </div>
                    <?php $__errorArgs = ['hsn_code_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                        Country of origin <span class="text-red-500">*</span>
                    </label>
                    <select name="country_of_origin" class="<?php echo e($select); ?>" required>
                        <option value="">Select country…</option>
                        <?php $__currentLoopData = $countries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($c->code); ?>" <?php if($selectedCountry === $c->code): echo 'selected'; endif; ?>>
                                <?php echo e(country_flag_emoji($c->code)); ?> <?php echo e($c->name); ?> (<?php echo e($c->code); ?>)
                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['country_of_origin'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                        <span data-mrp-label-text><?php echo e($isSelectedPhysicalChoice ? 'MRP / kg (₹)' : ($isSelectedVariableProduct ? 'Parent MRP (₹)' : 'MRP (₹)')); ?></span>
                        <span data-mrp-required-indicator class="text-red-500 <?php echo e(($isSelectedVariableProduct || $isSelectedPhysicalChoice) ? 'hidden' : ''); ?>">*</span>
                        <span data-mrp-mode-hint class="text-[10px] font-normal text-gray-400"><?php echo e($isSelectedVariableProduct ? 'variant-level only' : ($isSelectedPhysicalChoice ? 'optional for physical choice' : 'active direct-buy products only')); ?></span>
                    </label>
                    <input
                        id="mrp_input"
                        type="number"
                        step="0.01"
                        min="0"
                        name="mrp_price"
                        value="<?php echo e($mrpInput); ?>"
                        class="<?php echo e($input); ?>"
                        placeholder="<?php echo e($isSelectedPhysicalChoice ? 'Optional MRP per kg' : 'Maximum retail price'); ?>"
                    >
                    <div class="<?php echo e($hint); ?>" data-mrp-hint><?php echo e($isSelectedPhysicalChoice ? 'Optional for physical-choice products such as cheese blocks. Enter only if you want to show a per-kg MRP; exact block weights come from inward stock.' : 'Required for active direct-buy products. For variant-choice products such as prawns, leave blank here and enter MRP on each variant.'); ?></div>
                    <?php $__errorArgs = ['mrp_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                        <span data-sell-label-text><?php echo e($isSelectedPhysicalChoice ? 'Sell price / kg (₹)' : ($isSelectedVariableProduct ? 'Parent sell price (₹)' : 'Sell price (₹)')); ?></span>
                        <span data-sell-required-indicator class="text-red-500 <?php echo e($isSelectedVariableProduct ? 'hidden' : ''); ?>">*</span>
                        <span data-sell-mode-hint class="text-[10px] font-normal text-gray-400"><?php echo e($isSelectedVariableProduct ? 'variant-level only' : ($isSelectedPhysicalChoice ? 'required per kg' : 'active direct-buy products only')); ?></span>
                    </label>
                    <input
                        id="price_input"
                        type="number"
                        step="0.01"
                        min="0"
                        name="base_price"
                        value="<?php echo e($sellInput); ?>"
                        class="<?php echo e($input); ?>"
                        placeholder="<?php echo e($isSelectedPhysicalChoice ? 'Selling rate per kg' : 'Actual selling price'); ?>"
                    >
                    <div class="<?php echo e($hint); ?>" data-sell-hint><?php echo e($isSelectedPhysicalChoice ? 'Required per kg for active physical-choice products. Individual cheese block/slab weights are entered later from vendor inward stock.' : 'Required for active direct-buy products. Variant-choice products use the sell price saved on each variant.'); ?></div>
                    <?php $__errorArgs = ['base_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                        B2C price mode <span class="text-red-500">*</span>
                    </label>
                    <select id="b2c_price_includes_gst" name="b2c_price_includes_gst" class="<?php echo e($select); ?>" required>
                        <option value="1" <?php if($b2cIncludesGst): echo 'selected'; endif; ?>>With GST (inclusive)</option>
                        <option value="0" <?php if(!$b2cIncludesGst): echo 'selected'; endif; ?>>Without GST (exclusive)</option>
                    </select>
                    <div class="<?php echo e($hint); ?>">Default for retail/B2C is GST-inclusive.</div>
                    <?php $__errorArgs = ['b2c_price_includes_gst'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

            </div>

            
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Standard B2B price (₹)</label>
                    <input type="number" step="0.01" min="0" name="standard_b2b_price"
                           value="<?php echo e($standardB2BInput); ?>"
                           class="<?php echo e($input); ?>" placeholder="Leave empty if not available for B2B by default">
                    <div class="<?php echo e($hint); ?>">Customer-specific B2B prices override this. Default B2B entry is excl. GST.</div>
                    <?php $__errorArgs = ['standard_b2b_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">B2B price mode <span class="text-red-500">*</span></label>
                    <select id="b2b_price_includes_gst" name="b2b_price_includes_gst" class="<?php echo e($select); ?>" required>
                        <option value="0" <?php if(!$b2bIncludesGst): echo 'selected'; endif; ?>>Without GST (exclusive)</option>
                        <option value="1" <?php if($b2bIncludesGst): echo 'selected'; endif; ?>>With GST (inclusive)</option>
                    </select>
                    <div class="<?php echo e($hint); ?>">Default for B2B/account prices is GST-exclusive.</div>
                    <?php $__errorArgs = ['b2b_price_includes_gst'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Standard B2B MOQ</label>
                    <input type="number" step="0.001" min="0.001" name="standard_b2b_min_order_quantity"
                           value="<?php echo e(old('standard_b2b_min_order_quantity', $product->standard_b2b_min_order_quantity ?? '')); ?>"
                           class="<?php echo e($input); ?>" placeholder="Default 1 if empty">
                    <div class="<?php echo e($hint); ?>">Customer-specific or variant-level MOQ overrides this when configured.</div>
                    <?php $__errorArgs = ['standard_b2b_min_order_quantity'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                    <div class="text-[10px] uppercase tracking-wide text-gray-400">GST %</div>
                    <input
                        id="gst_rate"
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        name="gst_rate"
                        value="<?php echo e(number_format($gstRate, 2, '.', '')); ?>"
                        class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px] text-gray-900 dark:text-gray-100"
                    >
                    <div class="mt-1 text-[10px] text-gray-400">Auto-filled from HSN.</div>
                    <?php $__errorArgs = ['gst_rate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                    <div class="flex items-center justify-between text-[12px]">
                        <span class="text-gray-600 dark:text-gray-300">Base price (excl. GST)</span>
                        <span id="calc_base" class="font-semibold text-gray-900 dark:text-gray-50">₹0.00</span>
                    </div>

                    <div class="mt-3 flex items-center justify-between text-[12px]">
                        <span class="text-gray-600 dark:text-gray-300">Price (incl. GST)</span>
                        <span id="calc_incl" class="font-semibold text-gray-900 dark:text-gray-50">₹0.00</span>
                    </div>
                </div>

                <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                    <div class="flex items-center justify-between text-[12px]">
                        <span class="text-gray-600 dark:text-gray-300">Discount vs MRP</span>
                        <span id="mrp_discount" class="font-semibold text-gray-900 dark:text-gray-50">—</span>
                    </div>
                    <div class="mt-1 text-[10px] text-gray-400">
                        Based on current sell price and MRP.
                    </div>
                </div>
            </div>

            
            <div id="category-required-block">
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Classification (Categories) <span class="text-red-500">*</span>
                </label>

                <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 p-3 max-h-56 overflow-y-auto space-y-2">
                    <?php $__empty_1 = true; $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <label class="flex items-start gap-2">
                            <input type="checkbox"
                                   name="category_ids[]"
                                   value="<?php echo e($category->id); ?>"
                                   class="mt-0.5 rounded border-gray-300 dark:border-gray-700"
                                   <?php if(in_array($category->id, $oldCategoryIds, true)): echo 'checked'; endif; ?>>
                            <span class="text-[12px] text-gray-800 dark:text-gray-200">
                                <?php echo e($category->name); ?>

                                <?php if($category->parent): ?>
                                    <span class="text-[11px] text-gray-400 block">Child of <?php echo e($category->parent->name); ?></span>
                                <?php endif; ?>
                            </span>
                        </label>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="text-[12px] text-gray-500 dark:text-gray-400">
                            No categories defined yet.
                            <?php if(\Illuminate\Support\Facades\Route::has('admin.categories.create')): ?>
                                <a href="<?php echo e(route('admin.categories.create')); ?>" class="underline">Create one</a>.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <p id="category-required-error" class="hidden mt-2 text-[11px] text-red-600">
                    Please select at least one category.
                </p>

                <?php $__errorArgs = ['category_ids'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <?php $__errorArgs = ['category_ids.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>
    </section>

    
    <details class="fb-acc <?php echo e($card); ?>">
        <summary class="px-5 py-4 flex items-center justify-between gap-3 cursor-pointer">
            <div>
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Optional details</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Slug, vendor, barcode and product transformation relationships.</div>
            </div>
            <span class="text-[12px] text-gray-500 dark:text-gray-400 select-none">▾</span>
        </summary>

        <div class="px-5 pb-5 space-y-5">
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Slug</label>
                    <input
                        type="text"
                        name="slug"
                        value="<?php echo e(old('slug', $product->slug ?? '')); ?>"
                        class="<?php echo e($input); ?>"
                        placeholder="Auto-generated if empty"
                    >
                    <?php $__errorArgs = ['slug'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Vendor</label>
                    <select name="vendor_id" class="<?php echo e($select); ?>">
                        <option value="">— None —</option>
                        <?php $__currentLoopData = $vendors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($v->id); ?>" <?php if((int)old('vendor_id', $product->vendor_id ?? 0) === (int)$v->id): echo 'selected'; endif; ?>>
                                <?php echo e($v->code); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['vendor_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

            </div>

            <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 p-4 space-y-3">
                <div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Product transformation relationships</div>
                    <div class="<?php echo e($hint); ?>">Use this to document source products such as Pork Belly Full → Pork Slice Pack, Cheese Block → Cheese Portions, or Dimsum Master Box → Dimsum packs. This does not move stock by itself.</div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Produced from product(s)</label>
                        <select name="source_product_ids[]" multiple size="7" class="<?php echo e($select); ?>">
                            <?php $__currentLoopData = $transformationSourceProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sourceProduct): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($sourceProduct->id); ?>" <?php if(in_array((int) $sourceProduct->id, $selectedSourceProductIds, true)): echo 'selected'; endif; ?>>
                                    <?php echo e($sourceProduct->name); ?><?php echo e($sourceProduct->sku ? ' · '.$sourceProduct->sku : ''); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <div class="<?php echo e($hint); ?>">Hold Cmd/Ctrl to select multiple sources. Example: Pork Slice Pack can be produced from Full Belly and from Pork Slab.</div>
                        <?php $__errorArgs = ['source_product_ids'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <?php $__errorArgs = ['source_product_ids.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div>
                        <div class="text-[12px] font-medium text-gray-700 dark:text-gray-300">Produces</div>
                        <?php if($isEdit && $producesProducts->isNotEmpty()): ?>
                            <div class="mt-1 rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 divide-y divide-gray-100 dark:divide-gray-800">
                                <?php $__currentLoopData = $producesProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $producedProduct): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <a href="<?php echo e(route('admin.products.edit', $producedProduct)); ?>" class="block px-3 py-2 text-[12px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-900">
                                        <?php echo e($producedProduct->name); ?><?php echo e($producedProduct->sku ? ' · '.$producedProduct->sku : ''); ?>

                                    </a>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        <?php else: ?>
                            <div class="mt-1 rounded-sm border border-dashed border-gray-300 dark:border-gray-700 px-3 py-3 text-[11px] text-gray-500 dark:text-gray-400">
                                No products currently point to this product as a source.
                            </div>
                        <?php endif; ?>
                        <div class="<?php echo e($hint); ?>">This badge is automatic. If another product selects this one as a source, it becomes a Raw Source Product in the product list.</div>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Product barcode</label>
                <input
                    type="text"
                    name="barcode"
                    value="<?php echo e(old('barcode', $product->barcode ?? '')); ?>"
                    class="<?php echo e($input); ?>"
                    placeholder="EAN-13 / UPC / internal code"
                >
                <div class="<?php echo e($hint); ?>">Useful for simple products without variants.</div>
                <?php $__errorArgs = ['barcode'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>
    </details>

    
    <details class="fb-acc <?php echo e($card); ?>">
        <summary class="px-5 py-4 flex items-center justify-between gap-3 cursor-pointer">
            <div>
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Variant options</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Choose the option values allowed for variant-choice products such as Dimsum and Prawns.</div>
            </div>
            <span class="text-[12px] text-gray-500 dark:text-gray-400 select-none">▾</span>
        </summary>

        <div class="px-5 pb-5">
            <?php if($attributes->isEmpty()): ?>
                <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3 text-[12px] text-gray-500 dark:text-gray-400">
                    No variant option groups defined yet.
                    <?php if(\Illuminate\Support\Facades\Route::has('admin.attributes.index')): ?>
                        <a href="<?php echo e(route('admin.attributes.index')); ?>" class="underline">Manage variant options</a>.
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php $__currentLoopData = $attributes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attribute): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 p-3">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-[12px] font-semibold text-gray-900 dark:text-gray-50">
                                        <?php echo e($attribute->display_name ?? $attribute->name); ?>

                                    </div>
                                    <div class="text-[10px] text-gray-400"><?php echo e(ucfirst($attribute->frontend_type)); ?></div>
                                </div>

                                <div class="flex items-center gap-2 text-[10px]">
                                    <?php if(\Illuminate\Support\Facades\Route::has('admin.variant-option-values.index')): ?>
                                        <a href="<?php echo e(route('admin.variant-option-values.index', ['attribute_id' => $attribute->id])); ?>"
                                           class="text-gray-500 underline hover:text-gray-800 dark:hover:text-gray-200">
                                            Manage values
                                        </a>
                                    <?php elseif(\Illuminate\Support\Facades\Route::has('admin.attributes.values.index')): ?>
                                        <a href="<?php echo e(route('admin.attributes.values.index', $attribute)); ?>"
                                           class="text-gray-500 underline hover:text-gray-800 dark:hover:text-gray-200">
                                            Manage values
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if($attribute->values->isEmpty()): ?>
                                <div class="mt-1 text-[12px] text-gray-500 dark:text-gray-400">
                                    No option values defined.
                                    <?php if(\Illuminate\Support\Facades\Route::has('admin.attributes.values.index')): ?>
                                        <a href="<?php echo e(route('admin.attributes.values.index', $attribute)); ?>" class="underline">Add option values</a>.
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <?php $__currentLoopData = $attribute->values->sortBy('position'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <label class="inline-flex items-center gap-2 rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-1.5">
                                            <input type="checkbox"
                                                   name="attribute_value_ids[]"
                                                   value="<?php echo e($value->id); ?>"
                                                   class="rounded border-gray-300 dark:border-gray-700"
                                                   <?php if(in_array($value->id, $oldAttributeValueIds, true)): echo 'checked'; endif; ?>>
                                            <span class="text-[12px] text-gray-800 dark:text-gray-200"><?php echo e($value->name); ?></span>
                                        </label>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>

            <?php $__errorArgs = ['attribute_value_ids'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            <?php $__errorArgs = ['attribute_value_ids.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
    </details>

    
    <details class="fb-acc <?php echo e($card); ?>">
        <summary class="px-5 py-4 flex items-center justify-between gap-3 cursor-pointer">
            <div>
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Inventory & operations</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Stock, order controls and lot behaviour.</div>
            </div>
            <span class="text-[12px] text-gray-500 dark:text-gray-400 select-none">▾</span>
        </summary>

        <div class="px-5 pb-5 space-y-5">
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Stock quantity</label>
                    <input type="number" step="0.01" min="0" name="stock_quantity"
                           value="<?php echo e(old('stock_quantity', $product->stock_quantity ?? '')); ?>" class="<?php echo e($input); ?>">
                    <?php $__errorArgs = ['stock_quantity'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Low stock threshold</label>
                    <input type="number" step="0.01" min="0" name="low_stock_threshold"
                           value="<?php echo e(old('low_stock_threshold', $product->low_stock_threshold ?? '')); ?>" class="<?php echo e($input); ?>">
                    <?php $__errorArgs = ['low_stock_threshold'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Min order qty</label>
                    <input type="number" step="0.01" min="0" name="min_order_quantity"
                           value="<?php echo e(old('min_order_quantity', $product->min_order_quantity ?? '')); ?>" class="<?php echo e($input); ?>">
                    <?php $__errorArgs = ['min_order_quantity'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                        Default lot stage
                    </label>
                    <select name="lot_stage_default" class="<?php echo e($select); ?>">
                        <option value="">— None —</option>
                        <option value="raw"   <?php if(old('lot_stage_default', $product->lot_stage_default ?? '') === 'raw'): echo 'selected'; endif; ?>>Raw</option>
                        <option value="slab"  <?php if(old('lot_stage_default', $product->lot_stage_default ?? '') === 'slab'): echo 'selected'; endif; ?>>Slab</option>
                        <option value="slice" <?php if(old('lot_stage_default', $product->lot_stage_default ?? '') === 'slice'): echo 'selected'; endif; ?>>Slice</option>
                        <option value="trim"  <?php if(old('lot_stage_default', $product->lot_stage_default ?? '') === 'trim'): echo 'selected'; endif; ?>>Trim</option>
                        <option value="waste" <?php if(old('lot_stage_default', $product->lot_stage_default ?? '') === 'waste'): echo 'selected'; endif; ?>>Waste</option>
                    </select>
                    <div class="<?php echo e($hint); ?>">Used when inward or output lots are created.</div>
                    <?php $__errorArgs = ['lot_stage_default'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>
        </div>
    </details>

    
    <details class="fb-acc <?php echo e($card); ?>" open>
        <summary class="px-5 py-4 flex items-center justify-between gap-3 cursor-pointer">
            <div>
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Visibility & storefront options</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Storefront behaviour, publish state and merchandising flags.</div>
            </div>
            <span class="text-[12px] text-gray-500 dark:text-gray-400 select-none">▾</span>
        </summary>

        <div class="px-5 pb-5 space-y-3">
            <input type="hidden" name="manage_stock" value="0">
            <input type="hidden" name="dynamic_pricing_enabled" value="0">
            <input type="hidden" name="is_featured" value="0">
            <input type="hidden" name="is_new" value="0">
            <input type="hidden" name="is_special" value="0">
            <input type="hidden" name="is_active" value="0">
            <input type="hidden" name="inventory_is_saleable" value="0">
            <input type="hidden" name="inventory_can_repack" value="0">

            <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 p-4 space-y-4">
                <div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Storefront behaviour setup</div>
                    <div class="<?php echo e($hint); ?>">These fields decide whether the product behaves like Dimsum/Prawns, Pork/Salmon slabs, a simple retail pack, or an internal source item.</div>
                </div>

                <div class="rounded-sm border border-amber-200 bg-amber-50/70 p-3 dark:border-amber-900/60 dark:bg-amber-950/20">
                    <div class="text-[12px] font-semibold text-amber-900 dark:text-amber-100">Quick setup presets</div>
                    <div class="mt-1 text-[11px] text-amber-800/80 dark:text-amber-200/80">
                        Optional helper only. Presets fill the storefront, inventory and pricing fields below; you can still adjust any value before saving.
                    </div>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-4" data-product-presets>
                        <button type="button" data-product-preset="direct" class="rounded-sm border border-amber-200 bg-white px-3 py-2 text-left text-[11px] hover:bg-amber-50 dark:border-amber-900 dark:bg-gray-950 dark:hover:bg-amber-950/30">
                            <span class="block font-semibold text-gray-900 dark:text-gray-50">Direct buy</span>
                            <span class="block text-gray-500 dark:text-gray-400">One product, one price, normal cart button.</span>
                        </button>
                        <button type="button" data-product-preset="variant" class="rounded-sm border border-amber-200 bg-white px-3 py-2 text-left text-[11px] hover:bg-amber-50 dark:border-amber-900 dark:bg-gray-950 dark:hover:bg-amber-950/30">
                            <span class="block font-semibold text-gray-900 dark:text-gray-50">Variant choice</span>
                            <span class="block text-gray-500 dark:text-gray-400">Dimsum, prawns, pack/dropdown products.</span>
                        </button>
                        <button type="button" data-product-preset="physical" class="rounded-sm border border-amber-200 bg-white px-3 py-2 text-left text-[11px] hover:bg-amber-50 dark:border-amber-900 dark:bg-gray-950 dark:hover:bg-amber-950/30">
                            <span class="block font-semibold text-gray-900 dark:text-gray-50">Physical choice</span>
                            <span class="block text-gray-500 dark:text-gray-400">Pork, salmon, tuna, cheese blocks by exact weight.</span>
                        </button>
                        <button type="button" data-product-preset="internal" class="rounded-sm border border-amber-200 bg-white px-3 py-2 text-left text-[11px] hover:bg-amber-50 dark:border-amber-900 dark:bg-gray-950 dark:hover:bg-amber-950/30">
                            <span class="block font-semibold text-gray-900 dark:text-gray-50">Internal source</span>
                            <span class="block text-gray-500 dark:text-gray-400">Master boxes/raw stock used only for Transform Stock.</span>
                        </button>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                            Storefront experience <span class="text-red-500">*</span>
                        </label>
                        <select name="type" class="<?php echo e($select); ?>" required data-storefront-type>
                            <option value="simple" <?php if($selectedProductType === 'simple'): echo 'selected'; endif; ?>>Direct buy / physical choice product</option>
                            <option value="variable" <?php if($selectedProductType === 'variable'): echo 'selected'; endif; ?>>Variant choice product</option>
                        </select>
                        <p class="<?php echo e($hint); ?>">Variant choice is for Dimsum/Prawns. Direct buy also covers physical-choice products such as Pork Belly, Salmon, Tuna and cheese blocks.</p>
                        <?php $__errorArgs = ['type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Inventory / stock basis</label>
                        <select name="pack_type" class="<?php echo e($select); ?>" data-pack-type>
                            <option value="quantity" <?php if($packType === 'quantity'): echo 'selected'; endif; ?>>Finished quantity / pack stock</option>
                            <option value="bulk" <?php if($packType === 'bulk'): echo 'selected'; endif; ?>>Bulk / internal source stock</option>
                            <option value="fixed_weight_pack" <?php if($packType === 'fixed_weight_pack'): echo 'selected'; endif; ?>>Fixed weight finished pack</option>
                            <option value="fixed_piece_pack" <?php if($packType === 'fixed_piece_pack'): echo 'selected'; endif; ?>>Fixed piece finished pack</option>
                            <option value="variable_weight" <?php if($packType === 'variable_weight'): echo 'selected'; endif; ?>>Physical choice / catchweight pieces</option>
                        </select>
                        <div class="<?php echo e($hint); ?>">This is the stock logic behind the product. It does not create variants automatically.</div>
                        <?php $__errorArgs = ['pack_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Storefront / inward role</label>
                        <select name="inventory_role" class="<?php echo e($select); ?>" data-inventory-role>
                            <option value="saleable" <?php if($inventoryRole === 'saleable'): echo 'selected'; endif; ?>>Customer-facing product</option>
                            <option value="internal" <?php if($inventoryRole === 'internal'): echo 'selected'; endif; ?>>Internal source only</option>
                            <option value="both" <?php if($inventoryRole === 'both'): echo 'selected'; endif; ?>>Both customer-facing and inward source</option>
                        </select>
                        <div class="<?php echo e($hint); ?>">Use internal for master boxes/raw stock that should not be sold directly.</div>
                        <?php $__errorArgs = ['inventory_role'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-4">
                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Pricing unit</label>
                        <select name="sell_unit" class="<?php echo e($select); ?>" data-sell-unit>
                            <option value="piece" <?php if($selectedSellUnit === 'piece'): echo 'selected'; endif; ?>>Quantity / piece</option>
                            <option value="pack" <?php if($selectedSellUnit === 'pack'): echo 'selected'; endif; ?>>Pack</option>
                            <option value="kg" <?php if($selectedSellUnit === 'kg'): echo 'selected'; endif; ?>>Weight / kg</option>
                        </select>
                        <div class="<?php echo e($hint); ?>">Controls ₹/unit labels and kg-based slab pricing.</div>
                        <?php $__errorArgs = ['sell_unit'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                            Product / pack weight (kg)
                        </label>
                        <input
                            type="number"
                            name="product_weight"
                            step="0.001"
                            min="0"
                            value="<?php echo e($weight); ?>"
                            class="<?php echo e($input); ?>"
                            placeholder="e.g. 0.500 for 500g pack"
                        >
                        <div class="<?php echo e($hint); ?>" data-product-weight-hint><?php echo e($isSelectedPhysicalChoice ? 'For physical-choice products such as cheese blocks, leave this blank. Enter each real block/slab weight from vendor inward stock.' : 'Use for fixed-weight packs or a direct-buy catchweight item with one known weight. Slab-selector products can leave this blank and use inventory-piece weights.'); ?></div>
                        <?php $__errorArgs = ['product_weight'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Pieces per pack</label>
                        <input type="number" name="pieces_per_pack" step="0.001" min="0" value="<?php echo e($piecesPerPack); ?>" class="<?php echo e($input); ?>" placeholder="e.g. 10 for dimsum">
                        <div class="<?php echo e($hint); ?>">Use on product only for fixed piece packs. Variant products manage pack pieces on variants.</div>
                        <?php $__errorArgs = ['pieces_per_pack'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div id="product-behaviour-guide" class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 px-4 py-3 text-[11px] text-gray-600 dark:text-gray-300" aria-live="polite">
                        Select the storefront experience and inventory basis to see the recommended setup.
                    </div>
                </div>

                <div class="grid gap-2 md:grid-cols-2">
                    <label class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 px-3 py-2 flex items-start gap-2">
                        <input type="checkbox"
                               name="inventory_is_saleable"
                               value="1"
                               data-inventory-saleable
                               class="mt-0.5 rounded border-gray-300 dark:border-gray-700"
                               <?php if(old('inventory_is_saleable', $product->inventory_is_saleable ?? true)): echo 'checked'; endif; ?>>
                        <span>
                            <span class="block text-[12px] text-gray-800 dark:text-gray-200">Inventory lots/pieces can be sold directly</span>
                            <span class="block text-[10px] text-gray-500 dark:text-gray-400">Use for Pork Belly, Salmon, Tuna, Black Cod and cheese blocks where customers choose exact pieces.</span>
                        </span>
                    </label>

                    <label class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 px-3 py-2 flex items-start gap-2">
                        <input type="checkbox"
                               name="inventory_can_repack"
                               value="1"
                               data-inventory-repack
                               class="mt-0.5 rounded border-gray-300 dark:border-gray-700"
                               <?php if(old('inventory_can_repack', $product->inventory_can_repack ?? false)): echo 'checked'; endif; ?>>
                        <span>
                            <span class="block text-[12px] text-gray-800 dark:text-gray-200">Can be used as source in Transform Stock</span>
                            <span class="block text-[10px] text-gray-500 dark:text-gray-400">Use for master boxes, whole belly, salmon sides, cheese blocks and prawn 1kg stock.</span>
                        </span>
                    </label>
                </div>
            </div>

            <div>
                <div class="text-[12px] font-semibold text-gray-900 dark:text-gray-50">Storefront visibility & merchandising</div>
                <div class="<?php echo e($hint); ?>">Publish state, stock control and promotional flags shown on the customer storefront.</div>
            </div>

            <div class="grid gap-2 md:grid-cols-3">
                <label class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 flex items-center gap-2">
                    <input type="checkbox" name="manage_stock" value="1" data-manage-stock class="rounded border-gray-300 dark:border-gray-700"
                           <?php if(old('manage_stock', $product->manage_stock ?? false)): echo 'checked'; endif; ?>>
                    <span class="text-[12px] text-gray-800 dark:text-gray-200">Manage stock</span>
                </label>

                <label class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 flex items-center gap-2">
                    <input type="checkbox" name="dynamic_pricing_enabled" value="1" class="rounded border-gray-300 dark:border-gray-700"
                           <?php if(old('dynamic_pricing_enabled', $product->dynamic_pricing_enabled ?? false)): echo 'checked'; endif; ?>>
                    <span class="text-[12px] text-gray-800 dark:text-gray-200">Dynamic pricing</span>
                </label>

                <label class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 flex items-center gap-2">
                    <input type="checkbox" name="is_featured" value="1" class="rounded border-gray-300 dark:border-gray-700"
                           <?php if(old('is_featured', $product->is_featured ?? false)): echo 'checked'; endif; ?>>
                    <span class="text-[12px] text-gray-800 dark:text-gray-200">Featured</span>
                </label>

                <label class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 flex items-center gap-2">
                    <input type="checkbox" name="is_new" value="1" class="rounded border-gray-300 dark:border-gray-700"
                           <?php if(old('is_new', $product->is_new ?? false)): echo 'checked'; endif; ?>>
                    <span class="text-[12px] text-gray-800 dark:text-gray-200">New</span>
                </label>

                <label class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 flex items-center gap-2">
                    <input type="checkbox" name="is_special" value="1" class="rounded border-gray-300 dark:border-gray-700"
                           <?php if(old('is_special', $product->is_special ?? false)): echo 'checked'; endif; ?>>
                    <span class="text-[12px] text-gray-800 dark:text-gray-200">Special</span>
                </label>

                <label class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 dark:border-gray-700"
                           <?php if(old('is_active', $product->is_active ?? true)): echo 'checked'; endif; ?>>
                    <span class="text-[12px] text-gray-800 dark:text-gray-200">Active / publish to storefront</span>
                </label>
            </div>
        </div>
    </details>

    
    <details class="fb-acc <?php echo e($card); ?>">
        <summary class="px-5 py-4 flex items-center justify-between gap-3 cursor-pointer">
            <div>
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Special pricing</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Optional override pricing window.</div>
            </div>
            <span class="text-[12px] text-gray-500 dark:text-gray-400 select-none">▾</span>
        </summary>

        <div class="px-5 pb-5">
            <div class="grid gap-4 md:grid-cols-4">
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Special price (₹)</label>
                    <input type="number" step="0.01" min="0" name="special_price"
                           value="<?php echo e($specialInput); ?>" class="<?php echo e($input); ?>"
                           placeholder="Leave empty if none">
                    <?php $__errorArgs = ['special_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Special audience</label>
                    <select name="special_audience" class="<?php echo e($select); ?>">
                        <option value="b2c" <?php if($specialAudience === 'b2c'): echo 'selected'; endif; ?>>B2C / public only</option>
                        <option value="b2b" <?php if($specialAudience === 'b2b'): echo 'selected'; endif; ?>>B2B only</option>
                        <option value="all" <?php if($specialAudience === 'all'): echo 'selected'; endif; ?>>All customers</option>
                    </select>
                    <?php $__errorArgs = ['special_audience'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Special starts at</label>
                    <input type="datetime-local" name="special_starts_at"
                           value="<?php echo e(old('special_starts_at', optional($product->special_starts_at ?? null)->format('Y-m-d\TH:i'))); ?>"
                           class="<?php echo e($input); ?>">
                    <?php $__errorArgs = ['special_starts_at'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Special ends at</label>
                    <input type="datetime-local" name="special_ends_at"
                           value="<?php echo e(old('special_ends_at', optional($product->special_ends_at ?? null)->format('Y-m-d\TH:i'))); ?>"
                           class="<?php echo e($input); ?>">
                    <?php $__errorArgs = ['special_ends_at'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>
        </div>
    </details>

    
    <div class="sticky bottom-3 z-10">
        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white/95 dark:bg-gray-900/95 backdrop-blur px-4 py-3 flex items-center justify-between gap-3">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">
                <?php echo e($isEdit ? 'Editing existing product.' : 'Create a saleable product or save an inactive draft while pricing, variants or weight are pending.'); ?>

            </div>

            <div class="flex items-center gap-2">
                <a href="<?php echo e(route('admin.products.index')); ?>"
                   class="text-[12px] px-4 py-2 rounded-sm border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                    Cancel
                </a>

                <button type="submit"
                        name="save_as_draft"
                        value="1"
                        class="inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-5 py-2 text-[12px] font-semibold hover:bg-gray-50 dark:hover:bg-gray-800">
                    Save inactive draft
                </button>

                <button type="submit"
                        class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-5 py-2 text-[12px] font-semibold hover:bg-gray-800 dark:hover:bg-gray-200">
                    <?php echo e($isEdit ? 'Update product' : 'Create product'); ?>

                </button>
            </div>
        </div>
    </div>
</form>

<style>
    details.fb-acc summary {
        list-style: none;
        cursor: pointer;
    }
    details.fb-acc summary::-webkit-details-marker {
        display: none;
    }
</style>

<script>
(function () {
    const form = document.querySelector('form[data-product-form]');
    const categoryBoxes = Array.from(document.querySelectorAll('input[name="category_ids[]"]'));
    const categoryError = document.getElementById('category-required-error');
    const categoryBlock = document.getElementById('category-required-block');

    const modeEl  = document.getElementById('b2c_price_includes_gst');
    const hsnEl   = document.getElementById('hsn_code_id');
    const rateEl  = document.getElementById('gst_rate');
    const sellEl  = document.getElementById('price_input');
    const mrpEl   = document.getElementById('mrp_input');

    const outBase = document.getElementById('calc_base');
    const outIncl = document.getElementById('calc_incl');
    const outDisc = document.getElementById('mrp_discount');

    const storefrontTypeEl = document.querySelector('[data-storefront-type]');
    const packTypeEl = document.querySelector('[data-pack-type]');
    const inventoryRoleEl = document.querySelector('[data-inventory-role]');
    const sellUnitEl = document.querySelector('[data-sell-unit]');
    const inventorySaleableEl = document.querySelector('[data-inventory-saleable]');
    const inventoryRepackEl = document.querySelector('[data-inventory-repack]');
    const manageStockEl = document.querySelector('[data-manage-stock]');
    const behaviourGuide = document.getElementById('product-behaviour-guide');
    const storageProfileEl = document.querySelector('[data-storage-profile]');
    const storageGuidanceEl = document.querySelector('[data-storage-guidance]');
    const deliverySupportEl = document.querySelector('[data-delivery-support]');
    const storageProfileApplyEl = document.querySelector('[data-storage-profile-apply]');
    const storageProfileTemplates = <?php echo json_encode($storageProfileTemplates, 15, 512) ?>;
    const mrpLabelTextEl = document.querySelector('[data-mrp-label-text]');
    const mrpRequiredEl = document.querySelector('[data-mrp-required-indicator]');
    const mrpModeHintEl = document.querySelector('[data-mrp-mode-hint]');
    const mrpHintEl = document.querySelector('[data-mrp-hint]');
    const sellLabelTextEl = document.querySelector('[data-sell-label-text]');
    const sellRequiredEl = document.querySelector('[data-sell-required-indicator]');
    const sellModeHintEl = document.querySelector('[data-sell-mode-hint]');
    const sellHintEl = document.querySelector('[data-sell-hint]');
    const productWeightHintEl = document.querySelector('[data-product-weight-hint]');

    function normalizeTemplateText(value) {
        return String(value || '')
            .replace(/\r\n|\r/g, '\n')
            .split('\n')
            .map(line => line.trim())
            .filter(Boolean)
            .join('\n');
    }

    function matchesKnownStorageTemplate(value, key) {
        const normalized = normalizeTemplateText(value);
        if (!normalized) return true;

        return Object.values(storageProfileTemplates).some(template => {
            return normalizeTemplateText(template[key] || '') === normalized;
        });
    }

    function applyStorageProfileTemplate(force) {
        if (!storageProfileEl) return;

        const template = storageProfileTemplates[storageProfileEl.value];
        const hasTemplate = !!template;

        if (storageProfileApplyEl) {
            storageProfileApplyEl.disabled = !hasTemplate;
        }

        if (!hasTemplate) return;

        if (storageGuidanceEl && (force || matchesKnownStorageTemplate(storageGuidanceEl.value, 'storage_guidance'))) {
            storageGuidanceEl.value = template.storage_guidance || '';
        }

        if (deliverySupportEl && (force || matchesKnownStorageTemplate(deliverySupportEl.value, 'delivery_support'))) {
            deliverySupportEl.value = template.delivery_support || '';
        }
    }

    if (storageProfileEl) {
        storageProfileEl.addEventListener('change', function () {
            applyStorageProfileTemplate(false);
        });
        applyStorageProfileTemplate(false);
    }

    if (storageProfileApplyEl) {
        storageProfileApplyEl.addEventListener('click', function () {
            applyStorageProfileTemplate(true);
        });
    }

    function setSelectValue(el, value) {
        if (!el) return;
        el.value = value;
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function setCheckboxValue(el, checked) {
        if (!el) return;
        el.checked = !!checked;
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function applyProductPreset(preset) {
        if (preset === 'variant') {
            setSelectValue(storefrontTypeEl, 'variable');
            setSelectValue(packTypeEl, 'quantity');
            setSelectValue(inventoryRoleEl, 'saleable');
            setSelectValue(sellUnitEl, 'pack');
            setCheckboxValue(inventorySaleableEl, false);
            setCheckboxValue(inventoryRepackEl, false);
            setCheckboxValue(manageStockEl, false);
        } else if (preset === 'physical') {
            setSelectValue(storefrontTypeEl, 'simple');
            setSelectValue(packTypeEl, 'variable_weight');
            setSelectValue(inventoryRoleEl, 'both');
            setSelectValue(sellUnitEl, 'kg');
            setCheckboxValue(inventorySaleableEl, true);
            setCheckboxValue(inventoryRepackEl, true);
            setCheckboxValue(manageStockEl, true);
        } else if (preset === 'internal') {
            setSelectValue(storefrontTypeEl, 'simple');
            setSelectValue(packTypeEl, 'bulk');
            setSelectValue(inventoryRoleEl, 'internal');
            setSelectValue(sellUnitEl, 'piece');
            setCheckboxValue(inventorySaleableEl, false);
            setCheckboxValue(inventoryRepackEl, true);
            setCheckboxValue(manageStockEl, true);
        } else {
            setSelectValue(storefrontTypeEl, 'simple');
            setSelectValue(packTypeEl, 'quantity');
            setSelectValue(inventoryRoleEl, 'saleable');
            setSelectValue(sellUnitEl, 'pack');
            setCheckboxValue(inventorySaleableEl, false);
            setCheckboxValue(inventoryRepackEl, false);
            setCheckboxValue(manageStockEl, true);
        }
        updateProductBehaviourGuide();
    }

    document.querySelectorAll('[data-product-preset]').forEach(button => {
        button.addEventListener('click', function () {
            applyProductPreset(this.dataset.productPreset || 'direct');
        });
    });

    function pricingBasisSuffix(sellUnit) {
        if (sellUnit === 'kg') return '/ kg';
        if (sellUnit === 'piece') return '/ pc';
        return '/ pack';
    }

    function updatePricingFieldGuidance() {
        const storefrontType = storefrontTypeEl ? storefrontTypeEl.value : 'simple';
        const packType = packTypeEl ? packTypeEl.value : 'quantity';
        const sellUnit = sellUnitEl ? sellUnitEl.value : 'piece';
        const isVariable = storefrontType === 'variable';
        const isPhysicalChoice = !isVariable && (packType === 'variable_weight' || sellUnit === 'kg');
        const suffix = pricingBasisSuffix(sellUnit);

        if (mrpLabelTextEl) {
            mrpLabelTextEl.textContent = isVariable
                ? 'Parent MRP (₹)'
                : (isPhysicalChoice ? 'MRP / kg (₹)' : `MRP ${suffix} (₹)`);
        }
        if (sellLabelTextEl) {
            sellLabelTextEl.textContent = isVariable
                ? 'Parent sell price (₹)'
                : (isPhysicalChoice ? 'Sell price / kg (₹)' : `Sell price ${suffix} (₹)`);
        }

        if (mrpRequiredEl) {
            mrpRequiredEl.classList.toggle('hidden', isVariable || isPhysicalChoice);
        }
        if (sellRequiredEl) {
            sellRequiredEl.classList.toggle('hidden', isVariable);
        }

        if (mrpModeHintEl) {
            mrpModeHintEl.textContent = isVariable
                ? 'variant-level only'
                : (isPhysicalChoice ? 'optional for physical choice' : 'active direct-buy products only');
        }
        if (sellModeHintEl) {
            sellModeHintEl.textContent = isVariable
                ? 'variant-level only'
                : (isPhysicalChoice ? 'required per kg' : 'active direct-buy products only');
        }

        if (mrpHintEl) {
            mrpHintEl.textContent = isVariable
                ? 'Variant-choice products such as prawns use variant-level MRP. Leave this blank on the parent product.'
                : (isPhysicalChoice
                    ? 'Optional for physical-choice products such as cheese blocks. Enter only if you want to show a per-kg MRP; exact block weights come from inward stock.'
                    : 'Required for active direct-buy products. For variant-choice products such as prawns, leave blank here and enter MRP on each variant.');
        }
        if (sellHintEl) {
            sellHintEl.textContent = isVariable
                ? 'Variant-choice products use the sell price saved on each variant.'
                : (isPhysicalChoice
                    ? 'Required per kg for active physical-choice products. Individual cheese block/slab weights are entered later from vendor inward stock.'
                    : 'Required for active direct-buy products. Variant-choice products use the sell price saved on each variant.');
        }
        if (productWeightHintEl) {
            productWeightHintEl.textContent = isPhysicalChoice
                ? 'For physical-choice products such as cheese blocks, leave this blank. Enter each real block/slab weight from vendor inward stock.'
                : 'Use for fixed-weight packs or a direct-buy catchweight item with one known weight. Slab-selector products can leave this blank and use inventory-piece weights.';
        }
    }

    function updateProductBehaviourGuide() {
        const storefrontType = storefrontTypeEl ? storefrontTypeEl.value : 'simple';
        const packType = packTypeEl ? packTypeEl.value : 'quantity';
        const inventoryRole = inventoryRoleEl ? inventoryRoleEl.value : 'saleable';
        const sellUnit = sellUnitEl ? sellUnitEl.value : 'piece';

        updatePricingFieldGuidance();

        if (!behaviourGuide) return;

        let title = 'Direct buy / simple product';
        let body = 'Use this for one product, one price and normal add-to-cart stock.';

        if (inventoryRole === 'internal') {
            title = 'Internal source product';
            body = 'This product should stay off the storefront and be used for vendor inward stock or Transform Stock source lots.';
        } else if (storefrontType === 'variable') {
            title = 'Variant choice product';
            body = 'Use this for Dimsum, dumplings, buns, rolls and Prawns. Parent stock can be zero; price, stock, pack size and B2B/B2C visibility are managed on variants.';
        } else if (packType === 'variable_weight' || sellUnit === 'kg') {
            title = 'Physical choice / catchweight product';
            body = 'Use this for Pork Belly, Salmon, Tuna, Black Cod, Hamachi and cheese blocks. Customer chooses exact inventory pieces; price is calculated per kg. Enter the per-kg sell price here; enter actual block/slab weights in inward stock.';
        } else if (packType === 'fixed_weight_pack') {
            title = 'Fixed weight finished pack';
            body = 'Use this for same-size packs such as Pork Slice 500g or cheese portion packs. Enter product/pack weight and manage stock as pack count.';
        } else if (packType === 'fixed_piece_pack') {
            title = 'Fixed piece finished pack';
            body = 'Use this for a single fixed pack product. If customers choose 10/20/100 packs, use Variant choice instead and set pieces on each variant.';
        } else if (packType === 'bulk') {
            title = 'Bulk / inward source stock';
            body = 'Use this for source stock that can be transformed later, such as dimsum master boxes, prawn 1kg stock or raw bulk stock.';
        }

        behaviourGuide.innerHTML = '<div class="font-semibold text-gray-900 dark:text-gray-50">' + title + '</div><div class="mt-1">' + body + '</div>';
    }

    [storefrontTypeEl, packTypeEl, inventoryRoleEl, sellUnitEl].forEach(el => {
        if (el) el.addEventListener('change', updateProductBehaviourGuide);
    });
    updateProductBehaviourGuide();

    function validateCategories() {
        if (!categoryBoxes.length) return true;

        const hasOne = categoryBoxes.some(cb => cb.checked);
        if (categoryError) {
            categoryError.classList.toggle('hidden', hasOne);
        }
        return hasOne;
    }

    if (form && categoryBoxes.length) {
        form.addEventListener('submit', function (e) {
            if (!validateCategories()) {
                e.preventDefault();
                if (categoryBlock) {
                    categoryBlock.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });

        categoryBoxes.forEach(cb => {
            cb.addEventListener('change', validateCategories);
        });

        validateCategories();
    }

    if (!modeEl || !rateEl || !sellEl || !outBase || !outIncl) return;

    function money(n) {
        n = Number.isFinite(n) ? n : 0;
        return '₹' + n.toFixed(2);
    }

    function syncRateFromHsn() {
        if (!hsnEl) return;
        const opt = hsnEl.options[hsnEl.selectedIndex];
        const rate = opt ? parseFloat(opt.dataset.rate || '') : NaN;

        if (!isNaN(rate)) {
            rateEl.value = rate.toFixed(2);
            rateEl.readOnly = true;
            rateEl.classList.add('opacity-80');
        } else {
            rateEl.readOnly = false;
            rateEl.classList.remove('opacity-80');
        }
    }

    function recalc() {
        const includes = String(modeEl.value) === '1';
        const rate = parseFloat(rateEl.value || '0');
        const sellEntered = parseFloat(sellEl.value || '0');
        const mul = 1 + (rate / 100);

        let base = 0;
        let incl = 0;

        if (includes) {
            incl = sellEntered;
            base = mul > 0 ? (sellEntered / mul) : sellEntered;
        } else {
            base = sellEntered;
            incl = sellEntered * mul;
        }

        outBase.textContent = money(base);
        outIncl.textContent = money(incl);

        if (outDisc && mrpEl) {
            const mrp = parseFloat(mrpEl.value || '');
            if (Number.isFinite(mrp) && mrp > 0) {
                const diff = mrp - incl;
                const pct = mrp > 0 ? (diff / mrp) * 100 : 0;

                if (diff > 0.009) outDisc.textContent = `-${money(diff)} (${pct.toFixed(1)}%)`;
                else if (Math.abs(diff) <= 0.009) outDisc.textContent = 'No discount';
                else outDisc.textContent = 'Sell > MRP';
            } else {
                outDisc.textContent = '—';
            }
        }
    }

    if (hsnEl) {
        hsnEl.addEventListener('change', function () {
            syncRateFromHsn();
            recalc();
        });
    }

    ['change', 'input'].forEach(evt => {
        modeEl.addEventListener(evt, recalc);
        rateEl.addEventListener(evt, recalc);
        sellEl.addEventListener(evt, recalc);
        if (mrpEl) mrpEl.addEventListener(evt, recalc);
    });

    syncRateFromHsn();
    recalc();
})();
</script><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/products/_form.blade.php ENDPATH**/ ?>