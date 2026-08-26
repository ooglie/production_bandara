<?php $__env->startSection('title', $product->name); ?>

<?php $__env->startSection('content'); ?>
<?php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $product->loadMissing([
        'images' => function ($q) {
            $q->orderBy('position')->orderBy('id');
        },
        'activeRecipes',
        'variants.attributeValues.attribute',
        'variants.attributeValues.attributeValue',
    ]);

    $variants = $variants ?? collect();

    $currentCustomerType = auth()->check() && ((auth()->user()->customer_type ?? 'b2c') === 'b2b')
        ? 'b2b'
        : 'b2c';

    $variantIsVisibleToCustomer = function ($variant) use ($currentCustomerType) {
        if (method_exists($variant, 'isVisibleToCustomerType')) {
            return $variant->isVisibleToCustomerType($currentCustomerType);
        }

        $visibility = (string) ($variant->customer_visibility ?? 'all');

        return $visibility === 'all' || $visibility === $currentCustomerType;
    };

    $variantIsActive = function ($variant) use ($variantIsVisibleToCustomer) {
        $isActive = $variant->getAttribute('is_active');

        return ($isActive === null || (bool) $isActive) && $variantIsVisibleToCustomer($variant);
    };

    $variantIsAvailable = function ($variant) use ($variantIsActive) {
        if (! $variantIsActive($variant)) {
            return false;
        }

        // For variable pack products, the variant is the real stock target.
        // Parent product stock/manage_stock must not make the product look
        // available when every pack variant is sold out.
        if ($variant->stock_quantity !== null) {
            return (float) $variant->stock_quantity > 0;
        }

        if ((bool) ($variant->manage_stock ?? false)) {
            return false;
        }

        return true;
    };

    $isVariableProduct = (string) ($product->type ?? 'simple') === 'variable';

    if ($isVariableProduct) {
        $variants = $variants->filter($variantIsActive)->values();
    }

    $availableVariants = $isVariableProduct
        ? $variants->filter($variantIsAvailable)->values()
        : collect();

    $pieceSelector = $pieceSelector ?? ['enabled' => false];
    $hasPieceSelector = (bool) ($pieceSelector['enabled'] ?? false);

    $primaryImage = $product->primary_image;
    $images = $product->images ?? collect();
    $recipes = $product->activeRecipes ?? collect();

    $pricingService = app(\App\Services\PricingService::class);
    $priceQuote = $pricingService->quote(auth()->user(), $product);
    $isB2BPrice = ($priceQuote['customer_type'] ?? 'b2c') === 'b2b';
    $effectivePrice = (float) ($priceQuote['price'] ?? 0);
    $basePrice      = (float) ($priceQuote['compare_at_price'] ?? $product->base_price ?? 0);
    $b2bMoq         = (float) ($priceQuote['moq'] ?? 1);

    $gstRate = app(\App\Services\GstRateService::class)->rateForProduct($product, auth()->user());

    $mrpDisplay = (float) ($product->mrp_price ?? 0);
    if ($mrpDisplay > 0 && ($product->b2c_price_includes_gst ?? true) && $gstRate > 0) {
        $mrpDisplay = round($mrpDisplay * (1 + ($gstRate / 100)), 2);
    }

    $standardPricingUnit = strtolower((string) ($product->sell_unit === 'kg' ? 'kg' : 'pack'));
    $standardWeightMultiplier = 1.0;
    if ($standardPricingUnit === 'kg' && (float) ($product->product_weight ?? 0) > 0) {
        // Direct-buy/catchweight item with one known unit weight.
        // Example: cheese block 2.470kg at ₹1400/kg should display/charge ₹3458 per block.
        $standardWeightMultiplier = round((float) $product->product_weight, 3);
    }

    $effectiveDisplayPrice = round($effectivePrice * $standardWeightMultiplier, 2);
    $baseDisplayPrice = round($basePrice * $standardWeightMultiplier, 2);
    $mrpDisplayTotal = round($mrpDisplay * $standardWeightMultiplier, 2);
    $showsWeightedUnitTotal = $standardPricingUnit === 'kg' && $standardWeightMultiplier > 1 && ! $isVariableProduct;

    $displayPriceForSellUnit = function (float $unitPrice, $variant = null) use ($product): float {
        $pricingUnit = strtolower((string) ($variant?->pricing_unit ?? ($product->sell_unit === 'kg' ? 'kg' : 'pack')));
        $pricingUnit = in_array($pricingUnit, ['kg', 'pack'], true) ? $pricingUnit : 'pack';

        if ($pricingUnit !== 'kg') {
            return round($unitPrice, 2);
        }

        $weight = round((float) ($variant?->product_weight ?? 0), 3);
        if ($weight <= 0) {
            $weight = round((float) ($product->product_weight ?? 0), 3);
        }

        return round($unitPrice * ($weight > 0 ? $weight : 1), 2);
    };

    $hasMrpSavings = $mrpDisplayTotal > 0 && $mrpDisplayTotal > $effectiveDisplayPrice;
    $mrpSavings = $hasMrpSavings ? round($mrpDisplayTotal - $effectiveDisplayPrice, 2) : 0;
    $mrpSavingsPct = $hasMrpSavings && $mrpDisplayTotal > 0
        ? round((($mrpDisplayTotal - $effectiveDisplayPrice) / $mrpDisplayTotal) * 100)
        : 0;

    $imageUrl = function ($path) {
        if (!$path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '/storage/', '/'])) {
            return $path;
        }

        return Storage::disk(config('media.public_disk', 'public'))->url($path);
    };

    $mainImageUrl = $imageUrl($primaryImage)
        ?: ($images->isNotEmpty() ? $imageUrl($images->first()->file_path) : null);

    $sellUnitLabel = match($product->sell_unit ?? 'piece') {
        'kg'   => 'Per kg',
        'pack' => 'Per pack',
        default => 'Per piece',
    };

    $hasVariantStockContext = $isVariableProduct;

    if ($hasVariantStockContext) {
        $stockValue = (float) $variants
            ->filter(fn ($variant) => $variant->stock_quantity !== null)
            ->sum(fn ($variant) => max((float) ($variant->stock_quantity ?? 0), 0));
        $manageStock = true;
        $inStock = $availableVariants->isNotEmpty();
    } else {
        $stockValue = (float) ($product->stock_quantity ?? 0);
        $manageStock = (bool) ($product->manage_stock ?? false);
        $inStock = !$manageStock || $stockValue > 0;
    }

    $stockLabel = $inStock ? 'In stock' : 'Out of stock';

    $productWeightLabel = !empty($product->product_weight)
        ? number_format((float) $product->product_weight, 3) . ' kg'
        : null;

    $variantLabel = function ($variant) {
        $name = trim((string) ($variant->name ?? ''));
        if ($name !== '') {
            return $name;
        }

        $packType = (string) ($variant->pack_type ?? '');
        if ($packType === 'fixed_piece_pack' && (float) ($variant->pieces_per_pack ?? 0) > 0) {
            return rtrim(rtrim(number_format((float) $variant->pieces_per_pack, 3), '0'), '.') . ' pcs pack';
        }

        if ($packType === 'fixed_weight_pack' && (float) ($variant->product_weight ?? 0) > 0) {
            return rtrim(rtrim(number_format((float) $variant->product_weight, 3), '0'), '.') . ' kg pack';
        }

        $parts = [];

        foreach (($variant->attributeValues ?? collect()) as $value) {
            $attributeName = $value->attribute->name ?? 'Option';
            $valueName = $value->value ?? $value->name ?? $value->attributeValue?->name ?? '';
            if ($valueName !== '') {
                $parts[] = $attributeName . ': ' . $valueName;
            }
        }

        if (!empty($parts)) {
            return implode(' · ', $parts);
        }

        return $variant->sku ?? ('Variant ' . $variant->id);
    };

    $recipeText = function ($recipe, $field) {
        if (method_exists($recipe, 'tr')) {
            return $recipe->tr($field);
        }

        $value = $recipe->{$field} ?? null;

        if (is_array($value)) {
            return $value[app()->getLocale()] ?? $value['en'] ?? (count($value) ? reset($value) : null);
        }

        return $value;
    };

    $recipeList = function ($recipe, $field) {
        if (method_exists($recipe, 'trList')) {
            return $recipe->trList($field);
        }

        $value = $recipe->{$field} ?? [];

        if (!is_array($value)) {
            return [];
        }

        if (isset($value[app()->getLocale()]) && is_array($value[app()->getLocale()])) {
            return $value[app()->getLocale()];
        }

        if (isset($value['en']) && is_array($value['en'])) {
            return $value['en'];
        }

        return array_values($value);
    };

    $originCode = $product->country_of_origin ?? null;

    $displayVariantPrice = function ($variant) use ($product, $pricingService, $displayPriceForSellUnit) {
        $unitPrice = round((float) $pricingService->priceFor(auth()->user(), $product, $variant), 2);
        return $displayPriceForSellUnit($unitPrice, $variant);
    };

    $hasVariantSelector = !$hasPieceSelector && $isVariableProduct;
    $hasSelectableVariant = $hasVariantSelector && $availableVariants->isNotEmpty();

    $variantDisplaySource = $hasSelectableVariant ? $availableVariants : $variants;

    $variantDisplayPrices = $hasVariantSelector
        ? $variantDisplaySource
            ->map(fn ($variant) => $displayVariantPrice($variant))
            ->filter(fn ($price) => $price > 0)
            ->values()
        : collect();

    $selectedVariantOld = null;
    if ($hasVariantSelector && old('product_variant_id')) {
        $selectedVariantOld = $variants->firstWhere('id', (int) old('product_variant_id'));
    }

    $variantOptionPayloads = [];
    $variantAttributeGroups = collect();
    $selectedVariantAttributeValueIds = [];

    if ($hasVariantSelector) {
        $variantOptionPayloads = $variants
            ->map(function ($variant) use ($displayVariantPrice, $variantLabel, $variantIsAvailable) {
                $attributes = collect($variant->attributeValues ?? collect())
                    ->map(function ($value) {
                        $attribute = $value->attribute ?? null;
                        $attributeValue = $value->attributeValue ?? null;
                        $valueName = $value->value
                            ?? $value->name
                            ?? $attributeValue?->name
                            ?? '';

                        if (trim((string) $valueName) === '') {
                            return null;
                        }

                        return [
                            'attribute_id' => (int) ($value->attribute_id ?? 0),
                            'attribute_name' => (string) ($attribute?->display_name ?? $attribute?->name ?? 'Option'),
                            'value_id' => (int) ($value->id ?? 0),
                            'value_name' => (string) $valueName,
                        ];
                    })
                    ->filter(fn ($row) => ! empty($row['attribute_id']) && ! empty($row['value_id']))
                    ->values();

                return [
                    'id' => (int) $variant->id,
                    'label' => $variantLabel($variant),
                    'price' => $displayVariantPrice($variant),
                    'available' => $variantIsAvailable($variant),
                    'stock_label' => $variantIsAvailable($variant) ? null : 'Out of stock',
                    'attributes' => $attributes->all(),
                ];
            })
            ->values()
            ->all();

        $variantAttributeGroups = collect($variantOptionPayloads)
            ->flatMap(fn ($variant) => $variant['attributes'] ?? [])
            ->groupBy('attribute_id')
            ->map(function ($rows, $attributeId) {
                return [
                    'id' => (int) $attributeId,
                    'name' => (string) ($rows->first()['attribute_name'] ?? 'Option'),
                    'values' => $rows
                        ->map(fn ($row) => [
                            'id' => (int) $row['value_id'],
                            'name' => (string) $row['value_name'],
                        ])
                        ->unique('id')
                        ->values()
                        ->all(),
                ];
            })
            ->filter(fn ($group) => count($group['values']) > 0)
            ->values();

        if ($selectedVariantOld) {
            $selectedVariantAttributeValueIds = collect($selectedVariantOld->attributeValues ?? collect())
                ->mapWithKeys(fn ($value) => [(int) ($value->attribute_id ?? 0) => (int) ($value->id ?? 0)])
                ->filter(fn ($value, $key) => $key > 0 && $value > 0)
                ->all();
        }
    }

    $useMultiVariantSelector = $hasVariantSelector && $variantAttributeGroups->count() > 1;

    $piecePricingRatio = ($hasPieceSelector && $effectivePrice > 0 && $mrpDisplay > $effectivePrice)
        ? ($mrpDisplay / $effectivePrice)
        : (($hasMrpSavings && $effectivePrice > 0) ? ($mrpDisplay / $effectivePrice) : 0);

    $variantMrpRatio = ($hasMrpSavings && $effectivePrice > 0)
        ? ($mrpDisplay / $effectivePrice)
        : 0;

    $formatPriceText = function (float $min, ?float $max = null) {
        $max = $max ?? $min;

        if ($max > $min + 0.009) {
            return '₹' . number_format($min, 2) . ' – ₹' . number_format($max, 2);
        }

        return '₹' . number_format($min, 2);
    };

    if ($hasPieceSelector) {
        $topPriceMin = (float) ($pieceSelector['price_min'] ?? $effectivePrice);
        $topPriceMax = (float) ($pieceSelector['price_max'] ?? $topPriceMin);
    } elseif ($hasVariantSelector && $selectedVariantOld) {
        $selectedOldPrice = $displayVariantPrice($selectedVariantOld);
        $topPriceMin = $selectedOldPrice;
        $topPriceMax = $selectedOldPrice;
    } elseif ($hasVariantSelector && $variantDisplayPrices->isNotEmpty()) {
        $topPriceMin = (float) $variantDisplayPrices->min();
        $topPriceMax = (float) $variantDisplayPrices->max();
    } else {
        $topPriceMin = $effectiveDisplayPrice;
        $topPriceMax = $effectiveDisplayPrice;
    }

    $topPriceText = $formatPriceText($topPriceMin, $topPriceMax);

    $topMrpText = null;
    $topSaveText = null;
    $topSavePct = 0;

    if ($piecePricingRatio > 1) {
        $mrpMin = round($topPriceMin * $piecePricingRatio, 2);
        $mrpMax = round($topPriceMax * $piecePricingRatio, 2);

        $saveMin = max(round($mrpMin - $topPriceMin, 2), 0);
        $saveMax = max(round($mrpMax - $topPriceMax, 2), 0);

        $topMrpText = $formatPriceText($mrpMin, $mrpMax);
        $topSaveText = $formatPriceText($saveMin, $saveMax);
        $topSavePct = $mrpMin > 0 ? round((($mrpMin - $topPriceMin) / $mrpMin) * 100) : 0;
    } elseif ($hasMrpSavings) {
        if ($hasVariantSelector && $topPriceMin > 0) {
            $mrpMin = round($topPriceMin * $variantMrpRatio, 2);
            $mrpMax = round($topPriceMax * $variantMrpRatio, 2);

            $saveMin = max(round($mrpMin - $topPriceMin, 2), 0);
            $saveMax = max(round($mrpMax - $topPriceMax, 2), 0);

            $topMrpText = $formatPriceText($mrpMin, $mrpMax);
            $topSaveText = $formatPriceText($saveMin, $saveMax);
            $topSavePct = $mrpMin > 0 ? round((($mrpMin - $topPriceMin) / $mrpMin) * 100) : 0;
        } else {
            $topMrpText = '₹' . number_format($mrpDisplayTotal, 2);
            $topSaveText = '₹' . number_format($mrpSavings, 2);
            $topSavePct = $mrpSavingsPct;
        }
    }
?>

<div class="max-w-6xl mx-auto px-4 py-6 space-y-8">
    
    <nav class="text-[11px] text-gray-500 dark:text-gray-400">
        <a href="<?php echo e(route('home')); ?>" class="hover:underline">Home</a>
        <span class="mx-1">/</span>
        <a href="<?php echo e(route('shop.index')); ?>" class="hover:underline">Shop</a>
        <span class="mx-1">/</span>
        <span class="text-gray-700 dark:text-gray-200"><?php echo e($product->name); ?></span>
    </nav>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,540px)_minmax(0,1fr)] lg:items-start">
        
        <div class="space-y-4 lg:max-w-[540px]">
            <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-3">
                <div class="relative aspect-[4/3] rounded-sm overflow-hidden bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                    <?php if($mainImageUrl): ?>
                        <img
                            id="product-main-image"
                            src="<?php echo e($mainImageUrl); ?>"
                            alt="<?php echo e($product->name); ?>"
                            class="object-cover w-full h-full"
                        >
                    <?php else: ?>
                        <span class="text-[11px] text-gray-400 dark:text-gray-500">No image available</span>
                    <?php endif; ?>

                    <div class="absolute left-3 top-3 flex flex-wrap gap-2 text-[10px]">
                        <?php if($product->is_new): ?>
                            <span class="inline-flex items-center rounded-sm bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-2 py-1">
                                New
                            </span>
                        <?php endif; ?>

                        <?php if($product->is_special): ?>
                            <span class="inline-flex items-center rounded-sm bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 px-2 py-1">
                                Special
                            </span>
                        <?php endif; ?>

                        <?php if($product->is_featured): ?>
                            <span class="inline-flex items-center rounded-sm bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 px-2 py-1">
                                Featured
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if($images->isNotEmpty() || $primaryImage): ?>
                <div class="flex gap-2 overflow-x-auto pb-1">
                    <?php $__currentLoopData = $images; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $image): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php $thumbUrl = $imageUrl($image->file_path); ?>
                        <?php if($thumbUrl): ?>
                            <button type="button"
                                    class="gallery-thumb shrink-0 h-16 w-16 rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden"
                                    data-image-src="<?php echo e($thumbUrl); ?>">
                                <img src="<?php echo e($thumbUrl); ?>" alt="<?php echo e($product->name); ?>" class="object-cover w-full h-full">
                            </button>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>
        </div>

        
        <div class="space-y-4">
            <div class="space-y-2">
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-50 leading-tight">
                    <?php echo e($product->name); ?>

                </h1>

                <?php if($product->short_description): ?>
                    <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                        <?php echo e($product->short_description); ?>

                    </p>
                <?php endif; ?>
            </div>

            <div class="flex flex-wrap gap-2 text-[10px]">
                <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1 text-gray-600 dark:text-gray-300">
                    <?php echo e($sellUnitLabel); ?>

                </span>

                <span class="inline-flex items-center rounded-sm border px-2 py-1
                    <?php echo e($inStock
                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200'
                        : 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200'); ?>">
                    <?php echo e($stockLabel); ?>

                </span>

                <?php if($productWeightLabel): ?>
                    <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1 text-gray-600 dark:text-gray-300">
                        <?php echo e($productWeightLabel); ?>

                    </span>
                <?php endif; ?>

                <?php if($originCode): ?>
                    <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1 text-gray-600 dark:text-gray-300">
                        Origin: <?php echo e($originCode); ?>

                    </span>
                <?php endif; ?>
            </div>

            <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 space-y-4">
                
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-end gap-2">
                            <span id="piece-top-price" class="text-2xl font-semibold text-gray-900 dark:text-gray-50">
                                <?php echo e($topPriceText); ?>

                            </span>

                            <span id="piece-top-mrp"
                                  class="text-sm text-gray-400 line-through <?php echo e($topMrpText ? '' : 'hidden'); ?>">
                                <?php echo e($topMrpText); ?>

                            </span>
                        </div>

                        <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                            <?php if($isB2BPrice): ?>
                                Your B2B account price <?php echo e(($priceQuote['display_price_includes_gst'] ?? false) ? 'includes GST' : 'excludes GST'); ?><?php echo e($b2bMoq > 1 ? ' · MOQ '.rtrim(rtrim(number_format($b2bMoq, 3), '0'), '.') : ''); ?>.
                            <?php else: ?>
                                Price shown <?php echo e(($priceQuote['display_price_includes_gst'] ?? true) ? 'includes' : 'excludes'); ?> applicable GST.
                            <?php endif; ?>

                            <?php if($showsWeightedUnitTotal && !$hasPieceSelector && !$hasVariantSelector): ?>
                                <span class="block mt-0.5">
                                    Calculated as ₹<?php echo e(number_format($effectivePrice, 2)); ?>/kg × <?php echo e(rtrim(rtrim(number_format($standardWeightMultiplier, 3), '0'), '.')); ?> kg.
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="piece-top-save-card"
                         class="<?php echo e($topSaveText ? '' : 'hidden '); ?>rounded-lg border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 px-3 py-2 text-right">
                        <div class="text-[10px] uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                            You save
                        </div>
                        <div id="piece-top-save-amount" class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">
                            <?php echo e($topSaveText); ?>

                        </div>
                        <div id="piece-top-save-pct" class="text-[10px] text-emerald-600 dark:text-emerald-400">
                            <?php echo e($topSavePct > 0 ? $topSavePct . '% off' : ''); ?>

                        </div>
                    </div>
                </div>

                <form method="POST" action="<?php echo e(route('cart.add')); ?>" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="product_id" value="<?php echo e($product->id); ?>">

                    <?php if($hasPieceSelector): ?>
                        <?php echo $__env->make('products._piece_selector', ['pieceSelector' => $pieceSelector], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php endif; ?>

                    
                    <?php if($hasVariantSelector): ?>
                        <?php if($useMultiVariantSelector): ?>
                            <div class="space-y-3" id="product-multi-variant-root">
                                <input
                                    type="hidden"
                                    id="product-variant-id"
                                    name="product_variant_id"
                                    value="<?php echo e(old('product_variant_id')); ?>"
                                >

                                <?php $__currentLoopData = $variantAttributeGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Choose <?php echo e($group['name']); ?>

                                        </label>
                                        <select
                                            class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                                            data-variant-attribute="<?php echo e($group['id']); ?>"
                                            required
                                        >
                                            <option value="">Select <?php echo e(strtolower($group['name'])); ?>…</option>
                                            <?php $__currentLoopData = $group['values']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $optionValue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option
                                                    value="<?php echo e($optionValue['id']); ?>"
                                                    <?php if((int) ($selectedVariantAttributeValueIds[$group['id']] ?? 0) === (int) $optionValue['id']): echo 'selected'; endif; ?>
                                                >
                                                    <?php echo e($optionValue['name']); ?>

                                                </option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                <p id="product-variant-summary" class="text-[11px] text-gray-500 dark:text-gray-400">
                                    Choose options to see price and availability.
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
                        <?php else: ?>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Choose variant
                                </label>
                                <select
                                    id="product-variant-select"
                                    name="product_variant_id"
                                    class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                                    required
                                >
                                    <option value="">Select…</option>
                                    <?php $__currentLoopData = $variants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $variant): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                            $label = $variantLabel($variant);
                                            $variantPrice = $displayVariantPrice($variant);
                                            $variantAvailable = $variantIsAvailable($variant);
                                        ?>
                                        <option
                                            value="<?php echo e($variant->id); ?>"
                                            data-display-price="<?php echo e(number_format($variantPrice, 2, '.', '')); ?>"
                                            <?php if((int) old('product_variant_id', 0) === (int) $variant->id): echo 'selected'; endif; ?>
                                            <?php if(! $variantAvailable): echo 'disabled'; endif; ?>
                                        >
                                            <?php echo e($label); ?> — ₹<?php echo e(number_format($variantPrice, 2)); ?><?php echo e($variantAvailable ? '' : ' (Out of stock)'); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
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
                        <?php endif; ?>
                    <?php endif; ?>

                    
                    <?php if(!$hasPieceSelector && !$hasVariantSelector): ?>
                        <div class="text-sm text-gray-900 dark:text-gray-50">
                            <?php if(! $isB2BPrice && $product->is_special && $effectiveDisplayPrice < $baseDisplayPrice): ?>
                                <span class="text-base font-semibold">
                                    ₹<?php echo e(number_format($effectiveDisplayPrice, 2)); ?>

                                </span>
                                <span class="ml-2 text-xs text-gray-400 line-through">
                                    ₹<?php echo e(number_format($baseDisplayPrice, 2)); ?>

                                </span>
                            <?php else: ?>
                                <span class="text-base font-semibold">
                                    ₹<?php echo e(number_format($effectiveDisplayPrice, 2)); ?>

                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if(!$hasPieceSelector): ?>
                        <div class="flex items-center gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                    Quantity
                                </label>
                                <input
                                    type="number"
                                    name="quantity"
                                    value="<?php echo e(old('quantity', 1)); ?>"
                                    min="1"
                                    class="mt-1 w-20 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                                >
                                <?php $__errorArgs = ['quantity'];
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
                    <?php endif; ?>

                    <?php
                        $disableAddToCart = $hasPieceSelector
                            || ($hasVariantSelector && ! $hasSelectableVariant)
                            || ($useMultiVariantSelector && $hasSelectableVariant);

                        $addToCartLabel = $hasPieceSelector
                            ? 'Select slab to add'
                            : (($hasVariantSelector && ! $hasSelectableVariant)
                                ? 'Out of stock'
                                : ($useMultiVariantSelector ? 'Choose options' : 'Add to cart'));
                    ?>

                    <div class="flex items-end gap-3">
                        <button
                            type="submit"
                            id="add-to-cart-submit"
                            <?php if($disableAddToCart): echo 'disabled'; endif; ?>
                            class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-xs font-medium hover:bg-gray-800 dark:hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <?php echo e($addToCartLabel); ?>

                        </button>

                        <?php if(config('features.wishlist', true)): ?>
                            <?php if(auth()->guard()->check()): ?>
                                <button
                                    type="submit"
                                    formaction="<?php echo e(route('wishlist.store')); ?>"
                                    formmethod="POST"
                                    class="inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-4 py-2 text-xs hover:bg-gray-100 dark:hover:bg-gray-800">
                                    Save to wishlist
                                </button>
                            <?php else: ?>
                                <a href="<?php echo e(route('login')); ?>"
                                   class="text-[11px] text-gray-600 dark:text-gray-300 underline">
                                    Sign in to save
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="rounded-sm border border-dashed border-gray-300 dark:border-gray-700 px-4 py-3 text-[11px] text-gray-600 dark:text-gray-300">
                    Need larger quantities, business pricing, or storage guidance?
                    <?php if(Route::has('tickets.create')): ?>
                        <a href="<?php echo e(route('tickets.create')); ?>" class="underline font-medium">Contact support</a>.
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    
    <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden" data-product-tabs>
        <div class="border-b border-gray-200 dark:border-gray-800 px-4 sm:px-6">
            <div class="flex flex-wrap gap-2 py-3">
                <button type="button"
                        class="tab-btn inline-flex items-center rounded-sm px-4 py-2 text-[11px] font-medium transition"
                        data-tab-target="description">
                    Description
                </button>

                <button type="button"
                        class="tab-btn inline-flex items-center rounded-sm px-4 py-2 text-[11px] font-medium transition"
                        data-tab-target="recipes">
                    Recipes
                    <?php if($recipes->isNotEmpty()): ?>
                        <span class="ml-2 inline-flex min-w-5 items-center justify-center rounded-sm bg-gray-100 text-gray-700 dark:bg-gray-300 dark:text-gray700 px-1.5 py-0.5 text-[10px]">
                            <?php echo e($recipes->count()); ?>

                        </span>
                    <?php endif; ?>
                </button>

                <button type="button"
                        class="tab-btn inline-flex items-center rounded-sm px-4 py-2 text-[11px] font-medium transition"
                        data-tab-target="storage">
                    Storage & Delivery
                </button>

                <button type="button"
                        class="tab-btn inline-flex items-center rounded-sm px-4 py-2 text-[11px] font-medium transition"
                        data-tab-target="info">
                    Product Info
                </button>
            </div>
        </div>

        <div class="p-4 sm:p-6">
            
            <div class="tab-panel space-y-4" data-tab-panel="description">
                <?php if($product->description): ?>
                    <div class="prose prose-sm max-w-none dark:prose-invert text-gray-700 dark:text-gray-200">
                        <?php echo nl2br(e($product->description)); ?>

                    </div>
                <?php elseif($product->short_description): ?>
                    <div class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                        <?php echo e($product->short_description); ?>

                    </div>
                <?php else: ?>
                    <div class="rounded-sm border border-dashed border-gray-300 dark:border-gray-700 px-4 py-5 text-sm text-gray-500 dark:text-gray-400">
                        Description will appear here once product details are added.
                    </div>
                <?php endif; ?>

                <div class="grid gap-3 md:grid-cols-3">
                    <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">Selling unit</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50"><?php echo e($sellUnitLabel); ?></div>
                    </div>

                    <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">Availability</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50"><?php echo e($stockLabel); ?></div>
                    </div>

                    <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">GST</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50"><?php echo e(number_format($gstRate, 2)); ?>%</div>
                    </div>
                </div>
            </div>

            
            <div class="tab-panel hidden space-y-4" data-tab-panel="recipes">
                <?php if($recipes->isEmpty()): ?>
                    <div class="rounded-sm border border-dashed border-gray-300 dark:border-gray-700 px-4 py-5 text-sm text-gray-500 dark:text-gray-400">
                        Recipes for this product will appear here soon.
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php $__currentLoopData = $recipes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $recipe): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $title = $recipeText($recipe, 'title');
                                $short = $recipeText($recipe, 'short_description');
                                $description = $recipeText($recipe, 'description');
                                $ingredients = $recipeList($recipe, 'ingredients');
                                $steps = $recipeList($recipe, 'steps');
                                $recipeImage = $imageUrl($recipe->image_path ?? null);
                            ?>

                            <details class="group rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 overflow-hidden">
                                <summary class="list-none cursor-pointer px-4 py-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex gap-4">
                                            <?php if($recipeImage): ?>
                                                <div class="h-20 w-20 shrink-0 overflow-hidden rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
                                                    <img src="<?php echo e($recipeImage); ?>"
                                                         alt="<?php echo e($title); ?>"
                                                         class="h-full w-full object-cover">
                                                </div>
                                            <?php endif; ?>

                                            <div class="space-y-2">
                                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                                                    <?php echo e($title); ?>

                                                </div>

                                                <?php if($short): ?>
                                                    <div class="text-xs text-gray-600 dark:text-gray-300">
                                                        <?php echo e($short); ?>

                                                    </div>
                                                <?php endif; ?>

                                                <div class="flex flex-wrap gap-2 text-[10px]">
                                                    <?php if($recipe->prep_time_minutes): ?>
                                                        <span class="rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1 text-gray-600 dark:text-gray-300">
                                                            Prep <?php echo e($recipe->prep_time_minutes); ?> mins
                                                        </span>
                                                    <?php endif; ?>

                                                    <?php if($recipe->cook_time_minutes): ?>
                                                        <span class="rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1 text-gray-600 dark:text-gray-300">
                                                            Cook <?php echo e($recipe->cook_time_minutes); ?> mins
                                                        </span>
                                                    <?php endif; ?>

                                                    <?php if($recipe->servings): ?>
                                                        <span class="rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1 text-gray-600 dark:text-gray-300">
                                                            Serves <?php echo e($recipe->servings); ?>

                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <span class="mt-1 text-gray-400 transition group-open:rotate-180">⌄</span>
                                    </div>
                                </summary>

                                <div class="border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 space-y-4">
                                    <?php if($description): ?>
                                        <div class="text-xs leading-relaxed text-gray-600 dark:text-gray-300">
                                            <?php echo nl2br(e($description)); ?>

                                        </div>
                                    <?php endif; ?>

                                    <div class="grid gap-4 lg:grid-cols-2">
                                        <div>
                                            <div class="text-[11px] font-semibold text-gray-900 dark:text-gray-50 mb-2">
                                                Ingredients
                                            </div>
                                            <?php if(!empty($ingredients)): ?>
                                                <ul class="space-y-1 text-xs text-gray-600 dark:text-gray-300">
                                                    <?php $__currentLoopData = $ingredients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ingredient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <li class="flex items-start gap-2">
                                                            <span class="mt-[5px] h-1.5 w-1.5 rounded-sm bg-gray-400"></span>
                                                            <span><?php echo e($ingredient); ?></span>
                                                        </li>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </ul>
                                            <?php else: ?>
                                                <div class="text-xs text-gray-400">Ingredients not added yet.</div>
                                            <?php endif; ?>
                                        </div>

                                        <div>
                                            <div class="text-[11px] font-semibold text-gray-900 dark:text-gray-50 mb-2">
                                                Method
                                            </div>
                                            <?php if(!empty($steps)): ?>
                                                <ol class="space-y-2 text-xs text-gray-600 dark:text-gray-300">
                                                    <?php $__currentLoopData = $steps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <li class="flex items-start gap-2">
                                                            <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-sm bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 text-[10px] font-semibold">
                                                                <?php echo e($loop->iteration); ?>

                                                            </span>
                                                            <span><?php echo e($step); ?></span>
                                                        </li>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </ol>
                                            <?php else: ?>
                                                <div class="text-xs text-gray-400">Cooking steps not added yet.</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </details>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endif; ?>
            </div>

            
            <div class="tab-panel hidden space-y-4" data-tab-panel="storage">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 p-4 space-y-3">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Storage guidance</h3>
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                            <?php $__currentLoopData = $product->storageGuidanceLines(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $line): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li class="flex items-start gap-2">
                                    <span class="mt-[6px] h-1.5 w-1.5 rounded-sm bg-gray-400"></span>
                                    <span><?php echo e($line); ?></span>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>

                    <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 p-4 space-y-3">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Delivery & support</h3>
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                            <?php $__currentLoopData = $product->deliverySupportLines(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $line): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li class="flex items-start gap-2">
                                    <span class="mt-[6px] h-1.5 w-1.5 rounded-sm bg-gray-400"></span>
                                    <span><?php echo e($line); ?></span>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>

                        <?php if(Route::has('tickets.create')): ?>
                            <a href="<?php echo e(route('tickets.create')); ?>"
                               class="inline-flex items-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] font-medium hover:bg-gray-100 dark:hover:bg-gray-800">
                                Need help? Contact support
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            
            <div class="tab-panel hidden" data-tab-panel="info">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">Selling unit</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50"><?php echo e($sellUnitLabel); ?></div>
                    </div>

                    <?php if($productWeightLabel): ?>
                        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                            <div class="text-[10px] uppercase tracking-wide text-gray-400">Pack / product weight</div>
                            <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50"><?php echo e($productWeightLabel); ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">Availability</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50"><?php echo e($stockLabel); ?></div>
                    </div>

                    <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">GST</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50"><?php echo e(number_format($gstRate, 2)); ?>%</div>
                    </div>

                    <?php if($originCode): ?>
                        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                            <div class="text-[10px] uppercase tracking-wide text-gray-400">Country of origin</div>
                            <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50"><?php echo e($originCode); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if(!empty($product->sku)): ?>
                        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                            <div class="text-[10px] uppercase tracking-wide text-gray-400">SKU</div>
                            <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50"><?php echo e($product->sku); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if(!empty($product->barcode)): ?>
                        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                            <div class="text-[10px] uppercase tracking-wide text-gray-400">Barcode</div>
                            <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50"><?php echo e($product->barcode); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const mainImage = document.getElementById('product-main-image');
    const thumbs = document.querySelectorAll('.gallery-thumb');

    thumbs.forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            if (!mainImage) return;

            const nextSrc = thumb.getAttribute('data-image-src');
            if (!nextSrc) return;

            mainImage.setAttribute('src', nextSrc);

            thumbs.forEach(function (t) {
                t.classList.remove('ring-2', 'ring-gray-400', 'dark:ring-gray-500');
            });

            thumb.classList.add('ring-2', 'ring-gray-400', 'dark:ring-gray-500');
        });
    });

    if (thumbs.length) {
        thumbs[0].classList.add('ring-2', 'ring-gray-400', 'dark:ring-gray-500');
    }

    const tabRoots = document.querySelectorAll('[data-product-tabs]');

    tabRoots.forEach(function (root) {
        const buttons = root.querySelectorAll('[data-tab-target]');
        const panels = root.querySelectorAll('[data-tab-panel]');

        function activate(target) {
            buttons.forEach(function (btn) {
                const active = btn.getAttribute('data-tab-target') === target;

                btn.classList.toggle('bg-gray-900', active);
                btn.classList.toggle('text-white', active);
                btn.classList.toggle('dark:bg-gray-100', active);
                btn.classList.toggle('dark:text-gray-900', active);

                btn.classList.toggle('bg-gray-100', !active);
                btn.classList.toggle('text-gray-700', !active);
                btn.classList.toggle('dark:bg-gray-800', !active);
                btn.classList.toggle('dark:text-gray-200', !active);
            });

            panels.forEach(function (panel) {
                panel.classList.toggle('hidden', panel.getAttribute('data-tab-panel') !== target);
            });
        }

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                activate(btn.getAttribute('data-tab-target'));
            });
        });

        activate('description');
    });
})();

document.addEventListener('DOMContentLoaded', function () {
    const topPrice = document.getElementById('piece-top-price');
    const topMrp = document.getElementById('piece-top-mrp');
    const saveCard = document.getElementById('piece-top-save-card');
    const saveAmount = document.getElementById('piece-top-save-amount');
    const savePct = document.getElementById('piece-top-save-pct');

    function money(value) {
        return '₹' + Number(value).toFixed(2);
    }

    function moneyRange(min, max) {
        min = Number(min || 0);
        max = Number(max || min);

        if (max > min + 0.009) {
            return money(min) + ' – ' + money(max);
        }

        return money(min);
    }

    function updateTopPricing(minPrice, maxPrice, ratio) {
        if (!topPrice) return;

        minPrice = Number(minPrice || 0);
        maxPrice = Number(maxPrice || minPrice);
        ratio = Number(ratio || 0);

        topPrice.textContent = moneyRange(minPrice, maxPrice);

        if (!topMrp || !saveCard || !saveAmount || !savePct) {
            return;
        }

        if (ratio > 1.0001) {
            const mrpMin = minPrice * ratio;
            const mrpMax = maxPrice * ratio;
            const saveMin = Math.max(mrpMin - minPrice, 0);
            const saveMax = Math.max(mrpMax - maxPrice, 0);

            topMrp.textContent = moneyRange(mrpMin, mrpMax);
            topMrp.classList.remove('hidden');

            saveAmount.textContent = moneyRange(saveMin, saveMax);

            const pct = mrpMin > 0 ? Math.round((saveMin / mrpMin) * 100) : 0;
            savePct.textContent = pct > 0 ? (pct + '% off') : '';
            saveCard.classList.remove('hidden');
        } else {
            topMrp.textContent = '';
            topMrp.classList.add('hidden');
            saveAmount.textContent = '';
            savePct.textContent = '';
            saveCard.classList.add('hidden');
        }
    }

    // ----------------------------
    // Variant price sync
    // ----------------------------
    const variantSelect = document.getElementById('product-variant-select');
    if (variantSelect && topPrice) {
        const basePriceText = topPrice.textContent;
        const baseMrpText = topMrp ? topMrp.textContent : '';
        const baseSaveAmountText = saveAmount ? saveAmount.textContent : '';
        const baseSavePctText = savePct ? savePct.textContent : '';
        const baseSaveVisible = saveCard ? !saveCard.classList.contains('hidden') : false;
        const baseMrpVisible = topMrp ? !topMrp.classList.contains('hidden') : false;
        const variantMrpRatio = parseFloat(<?php echo json_encode((float) $variantMrpRatio, 15, 512) ?>) || 0;

        function restoreBaseVariantUI() {
            topPrice.textContent = basePriceText;

            if (topMrp) {
                topMrp.textContent = baseMrpText;
                if (baseMrpVisible) topMrp.classList.remove('hidden');
                else topMrp.classList.add('hidden');
            }

            if (saveCard && saveAmount && savePct) {
                saveAmount.textContent = baseSaveAmountText;
                savePct.textContent = baseSavePctText;
                if (baseSaveVisible) saveCard.classList.remove('hidden');
                else saveCard.classList.add('hidden');
            }
        }

        function syncVariantPrice() {
            const option = variantSelect.options[variantSelect.selectedIndex];

            if (!option || !option.value) {
                restoreBaseVariantUI();
                return;
            }

            const displayPrice = parseFloat(option.dataset.displayPrice || '0') || 0;
            updateTopPricing(displayPrice, displayPrice, variantMrpRatio);
        }

        variantSelect.addEventListener('change', syncVariantPrice);
        syncVariantPrice();
    }

    // ----------------------------
    // Multi-level variant price sync
    // ----------------------------
    const multiVariantRoot = document.getElementById('product-multi-variant-root');
    if (multiVariantRoot && topPrice) {
        const hiddenVariantInput = document.getElementById('product-variant-id');
        const variantSummary = document.getElementById('product-variant-summary');
        const addButton = document.getElementById('add-to-cart-submit');
        const attributeSelects = Array.from(multiVariantRoot.querySelectorAll('[data-variant-attribute]'));
        const variantOptions = <?php echo json_encode($variantOptionPayloads, 15, 512) ?>;
        const multiVariantMrpRatio = parseFloat(<?php echo json_encode((float) $variantMrpRatio, 15, 512) ?>) || 0;
        const basePriceText = topPrice.textContent;
        const baseMrpText = topMrp ? topMrp.textContent : '';
        const baseSaveAmountText = saveAmount ? saveAmount.textContent : '';
        const baseSavePctText = savePct ? savePct.textContent : '';
        const baseSaveVisible = saveCard ? !saveCard.classList.contains('hidden') : false;
        const baseMrpVisible = topMrp ? !topMrp.classList.contains('hidden') : false;

        function restoreMultiBaseUI() {
            topPrice.textContent = basePriceText;

            if (topMrp) {
                topMrp.textContent = baseMrpText;
                topMrp.classList.toggle('hidden', !baseMrpVisible);
            }

            if (saveCard && saveAmount && savePct) {
                saveAmount.textContent = baseSaveAmountText;
                savePct.textContent = baseSavePctText;
                saveCard.classList.toggle('hidden', !baseSaveVisible);
            }
        }

        function selectedAttributeMap() {
            const selected = {};

            attributeSelects.forEach(function (select) {
                const attributeId = String(select.getAttribute('data-variant-attribute') || '');
                const valueId = String(select.value || '');

                if (attributeId && valueId) {
                    selected[attributeId] = valueId;
                }
            });

            return selected;
        }

        function allAttributesSelected(selected) {
            return attributeSelects.every(function (select) {
                const attributeId = String(select.getAttribute('data-variant-attribute') || '');
                return Boolean(attributeId && selected[attributeId]);
            });
        }

        function variantMatchesSelection(variant, selected) {
            const attrs = Array.isArray(variant.attributes) ? variant.attributes : [];

            return Object.keys(selected).every(function (attributeId) {
                return attrs.some(function (attr) {
                    return String(attr.attribute_id) === String(attributeId)
                        && String(attr.value_id) === String(selected[attributeId]);
                });
            });
        }

        function optionPossible(attributeId, valueId, selected) {
            const testSelection = Object.assign({}, selected);
            testSelection[String(attributeId)] = String(valueId);

            return variantOptions.some(function (variant) {
                return variant.available && variantMatchesSelection(variant, testSelection);
            });
        }

        function refreshOptionAvailability(selected) {
            attributeSelects.forEach(function (select) {
                const attributeId = String(select.getAttribute('data-variant-attribute') || '');

                Array.from(select.options).forEach(function (option) {
                    if (!option.value) {
                        option.disabled = false;
                        return;
                    }

                    option.disabled = !optionPossible(attributeId, option.value, selected);
                });
            });
        }

        function syncMultiVariant() {
            const selected = selectedAttributeMap();
            refreshOptionAvailability(selected);

            if (!allAttributesSelected(selected)) {
                if (hiddenVariantInput) hiddenVariantInput.value = '';
                if (variantSummary) variantSummary.textContent = 'Choose options to see price and availability.';
                restoreMultiBaseUI();
                if (addButton) {
                    addButton.disabled = true;
                    addButton.textContent = 'Choose options';
                }
                return;
            }

            const match = variantOptions.find(function (variant) {
                return variant.available && variantMatchesSelection(variant, selected);
            });

            if (!match) {
                if (hiddenVariantInput) hiddenVariantInput.value = '';
                if (variantSummary) variantSummary.textContent = 'This combination is currently unavailable.';
                restoreMultiBaseUI();
                if (addButton) {
                    addButton.disabled = true;
                    addButton.textContent = 'Unavailable';
                }
                return;
            }

            if (hiddenVariantInput) hiddenVariantInput.value = match.id;

            const price = parseFloat(match.price || '0') || 0;
            updateTopPricing(price, price, multiVariantMrpRatio);

            if (variantSummary) {
                const stockText = match.stock_label ? ' · ' + match.stock_label : '';
                variantSummary.textContent = match.label + ' · ₹' + price.toFixed(2) + stockText;
            }

            if (addButton) {
                addButton.disabled = false;
                addButton.textContent = 'Add to cart';
            }
        }

        attributeSelects.forEach(function (select) {
            select.addEventListener('change', syncMultiVariant);
        });

        syncMultiVariant();
    }

    // ----------------------------
    // Piece/slab price sync
    // ----------------------------
    const pieceRoot = document.getElementById('piece-selector-root');
    if (pieceRoot && topPrice) {
        const pieceMrpRatio = parseFloat(<?php echo json_encode((float) $piecePricingRatio, 15, 512) ?>) || 0;
        const bandButtons = Array.from(pieceRoot.querySelectorAll('[data-piece-band]'));
        const bandPanels = Array.from(pieceRoot.querySelectorAll('[data-piece-band-panel]'));
        const radios = Array.from(pieceRoot.querySelectorAll('.piece-option-radio'));
        const qtySelect = document.getElementById('piece-quantity-select');
        const summary = document.getElementById('selected-piece-summary');

        function checkedRadio() {
            return radios.find(r => r.checked) || null;
        }

        function currentVisiblePanel() {
            const selected = checkedRadio();
            if (selected) {
                const panel = selected.closest('[data-piece-band-panel]');
                if (panel) return panel;
            }

            return bandPanels.find(panel => !panel.classList.contains('hidden')) || bandPanels[0] || null;
        }

        function panelRange(panel) {
            if (!panel) return { min: 0, max: 0 };

            const prices = Array.from(panel.querySelectorAll('.piece-option-radio'))
                .map(r => parseFloat(r.dataset.price || '0'))
                .filter(v => Number.isFinite(v) && v > 0);

            if (!prices.length) {
                return { min: 0, max: 0 };
            }

            return {
                min: Math.min.apply(null, prices),
                max: Math.max.apply(null, prices),
            };
        }

        function updatePieceSummaryWithoutPrice() {
            if (!summary) return;

            const selected = checkedRadio();
            if (!selected) {
                summary.textContent = 'Select a slab to continue.';
                return;
            }

            const qty = parseInt((qtySelect && qtySelect.value) ? qtySelect.value : '1', 10) || 1;
            const weightLabel = selected.dataset.weightLabel || '';

            summary.innerHTML =
                '<div class="font-medium text-gray-900 dark:text-gray-50">Selected slab: ' + weightLabel + ' × ' + qty + '</div>' +
                '<div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Choose quantity and continue to add.</div>';
        }

        function syncPiecePricingUI() {
            const selected = checkedRadio();

            if (selected) {
                const qty = parseInt((qtySelect && qtySelect.value) ? qtySelect.value : '1', 10) || 1;
                const price = parseFloat(selected.dataset.price || '0') || 0;
                const total = price * qty;

                updateTopPricing(total, total, pieceMrpRatio);
                updatePieceSummaryWithoutPrice();
                return;
            }

            const panel = currentVisiblePanel();
            const range = panelRange(panel);

            updateTopPricing(range.min, range.max, pieceMrpRatio);
            updatePieceSummaryWithoutPrice();
        }

        bandButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                setTimeout(syncPiecePricingUI, 0);
            });
        });

        radios.forEach(function (radio) {
            radio.addEventListener('change', function () {
                setTimeout(syncPiecePricingUI, 0);
            });
        });

        if (qtySelect) {
            qtySelect.addEventListener('change', function () {
                setTimeout(syncPiecePricingUI, 0);
            });
        }

        setTimeout(syncPiecePricingUI, 0);
    }
});
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.customer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/products/show.blade.php ENDPATH**/ ?>