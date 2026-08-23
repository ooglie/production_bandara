<?php $__env->startSection('title', 'Products'); ?>

<?php $__env->startSection('breadcrumb', 'Admin · Products'); ?>

<?php $__env->startSection('content'); ?>
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Products
            </h1>

            <a href="<?php echo e(route('admin.products.create')); ?>"
               class="inline-flex items-center px-3 py-1.5 text-xs rounded border border-gray-300 dark:border-gray-700 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-200">
                + New product
            </a>
        </div>

        
        <form method="GET" action="<?php echo e(route('admin.products.index')); ?>" class="flex flex-wrap items-end gap-3 text-xs">
            <div>
                <div class="flex items-center gap-2">
                    <label for="scan-barcode-open" class="text-[10px] text-gray-600 dark:text-gray-300">
                        Scan product to open:
                    </label>
                    <input id="scan-barcode-open"
                        type="text"
                        name="barcode"
                        autocomplete="off"
                        class="w-40 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                        placeholder="Focus & scan">
                </div>

                <?php if(session('error')): ?>
                    <span class="text-[10px] text-red-500">
                        <?php echo e(session('error')); ?>

                    </span>
                <?php endif; ?>
                <?php if(session('status')): ?>
                    <span class="text-[10px] text-emerald-600">
                        <?php echo e(session('status')); ?>

                    </span>
                <?php endif; ?>
            </div>
            <div>
                <label class="block text-[10px] font-medium text-gray-600 dark:text-gray-300">
                    Search
                </label>
                <input
                    type="text"
                    name="q"
                    value="<?php echo e(request('q')); ?>"
                    placeholder="Name or SKU"
                    class="w-40 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
            </div>

            <div>
                <label class="block text-[10px] font-medium text-gray-600 dark:text-gray-300">
                    Status
                </label>
                <select
                    name="status"
                    class="mt-1 w-32 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                    <option value="">All</option>
                    <option value="active" <?php if(request('status') === 'active'): echo 'selected'; endif; ?>>Active</option>
                    <option value="inactive" <?php if(request('status') === 'inactive'): echo 'selected'; endif; ?>>Inactive</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-medium text-gray-600 dark:text-gray-300">
                    Storefront
                </label>
                <select
                    name="type"
                    class="mt-1 w-32 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                    <option value="">All</option>
                    <option value="simple" <?php if(request('type') === 'simple'): echo 'selected'; endif; ?>>Direct / Physical</option>
                    <option value="variable" <?php if(request('type') === 'variable'): echo 'selected'; endif; ?>>Variant choice</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-medium text-gray-600 dark:text-gray-300">
                    Product model
                </label>
                <select
                    name="model"
                    class="mt-1 w-44 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                    <option value="">All</option>
                    <option value="simple" <?php if(request('model') === 'simple'): echo 'selected'; endif; ?>>Simple Products</option>
                    <option value="variable_pack" <?php if(request('model') === 'variable_pack'): echo 'selected'; endif; ?>>Variant Choice Products</option>
                    <option value="catchweight" <?php if(request('model') === 'catchweight'): echo 'selected'; endif; ?>>Catchweight Products</option>
                    <option value="produced" <?php if(request('model') === 'produced'): echo 'selected'; endif; ?>>Produced Products</option>
                    <option value="raw_source" <?php if(request('model') === 'raw_source'): echo 'selected'; endif; ?>>Raw Source Products</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-medium text-gray-600 dark:text-gray-300">
                    Flag
                </label>
                <select
                    name="flag"
                    class="mt-1 w-32 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                    <option value="">All</option>
                    <option value="featured" <?php if(request('flag') === 'featured'): echo 'selected'; endif; ?>>Featured</option>
                    <option value="new"      <?php if(request('flag') === 'new'): echo 'selected'; endif; ?>>New</option>
                    <option value="special"  <?php if(request('flag') === 'special'): echo 'selected'; endif; ?>>Special</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-medium text-gray-600 dark:text-gray-300">
                    Rows
                </label>
                <select
                    name="per_page"
                    onchange="this.form.submit()"
                    class="mt-1 w-24 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                    <?php $__currentLoopData = ($allowedPerPage ?? [20, 50, 100]); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($option); ?>" <?php if((int) request('per_page', $perPage ?? 20) === (int) $option): echo 'selected'; endif; ?>>
                            <?php echo e($option); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="submit"
                    class="mt-5 inline-flex items-center px-3 py-1.5 rounded border border-gray-300 dark:border-gray-700 text-xs hover:bg-gray-100 dark:hover:bg-gray-800"
                >
                    Apply
                </button>

                <?php if(request()->hasAny(['q', 'status', 'type', 'model', 'flag', 'per_page'])): ?>
                    <a href="<?php echo e(route('admin.products.index')); ?>"
                       class="mt-5 inline-flex items-center px-3 py-1.5 rounded border border-transparent text-xs text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-100">
                        Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
        
        <?php
            $formatStockNumber = static function ($value, int $decimals = 3): string {
                if ($value === null || $value === '') {
                    return '—';
                }

                $formatted = number_format((float) $value, $decimals);

                return rtrim(rtrim($formatted, '0'), '.') ?: '0';
            };
        ?>

        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs text-gray-600 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-300">
            <div>
                <?php if($products->total() > 0): ?>
                    Showing
                    <span class="font-medium text-gray-900 dark:text-gray-50"><?php echo e($products->firstItem()); ?></span>
                    –
                    <span class="font-medium text-gray-900 dark:text-gray-50"><?php echo e($products->lastItem()); ?></span>
                    of
                    <span class="font-medium text-gray-900 dark:text-gray-50"><?php echo e($products->total()); ?></span>
                    products
                <?php else: ?>
                    No products match the current filters.
                <?php endif; ?>
            </div>

            <?php if($products->hasPages()): ?>
                <div class="product-pagination product-pagination-top">
                    <?php echo e($products->onEachSide(1)->links()); ?>

                </div>
            <?php endif; ?>
        </div>

        <div class="overflow-x-auto border border-gray-200 dark:border-gray-800 rounded-lg text-xs">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr class="text-[11px] uppercase text-gray-500 dark:text-gray-400">
                        <th class="px-3 py-2 text-left">Name</th>
                        <th class="px-3 py-2 text-left">Storefront</th>
                        <th class="px-3 py-2 text-right">Price (₹)</th>
                        <th class="px-3 py-2 text-right">Stock Weight</th>
                        <th class="px-3 py-2 text-right">Stock Qty</th>
                        <th class="px-3 py-2 text-center">Status</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-white dark:bg-gray-950">
                    <?php $__empty_1 = true; $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="px-3 py-2 align-top">
                                <?php
                                    $hasPublicProductUrl = (string) ($product->slug ?? '') !== '';
                                    $productUrl = $hasPublicProductUrl
                                        ? route('product.show', $product->slug)
                                        : route('admin.products.edit', $product);

                                    $packType = (string) ($product->pack_type ?? 'quantity');
                                    $sellUnit = (string) ($product->sell_unit ?? '');
                                    $isRawSource = (int) ($product->produces_count ?? 0) > 0;
                                    $isProduced = (int) ($product->produced_from_count ?? 0) > 0;
                                    $modelLabel = (string) ($product->type ?? 'simple') === 'variable'
                                        ? 'Variable Pack'
                                        : ($packType === 'variable_weight' || $sellUnit === 'kg' ? 'Catchweight' : 'Simple');
                                ?>

                                <div class="font-medium text-gray-900 dark:text-gray-50">
                                    <a href="<?php echo e($productUrl); ?>"
                                       <?php if($hasPublicProductUrl): ?> target="_blank" rel="noopener" <?php endif; ?>
                                       class="hover:underline hover:text-gray-700 dark:hover:text-gray-200"
                                       title="<?php echo e($hasPublicProductUrl ? 'View product page' : 'Open product edit page'); ?>">
                                        <?php echo e($product->name); ?>

                                    </a>
                                </div>

                                <div class="mt-1 flex flex-wrap items-center gap-1">
                                    <?php $__empty_2 = true; $__currentLoopData = $product->categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                                        <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-[10px] text-gray-600 dark:text-gray-300">
                                            <?php echo e($cat->name); ?>

                                        </span>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                                        <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-[10px] text-gray-500 dark:text-gray-400">
                                            Uncategorised
                                        </span>
                                    <?php endif; ?>

                                    <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-[10px] text-gray-600 dark:text-gray-300">
                                        <?php echo e($modelLabel); ?>

                                    </span>
                                    <?php if($isProduced): ?>
                                        <span class="inline-flex items-center rounded-full bg-indigo-50 dark:bg-indigo-900/30 px-2 py-0.5 text-[10px] text-indigo-700 dark:text-indigo-300">
                                            Produced Product
                                        </span>
                                    <?php endif; ?>
                                    <?php if($isRawSource): ?>
                                        <span class="inline-flex items-center rounded-full bg-amber-50 dark:bg-amber-900/30 px-2 py-0.5 text-[10px] text-amber-700 dark:text-amber-300">
                                            Raw Source Product
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if($product->producedFromProducts->isNotEmpty()): ?>
                                    <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                                        ↳ Produced from:
                                        <?php echo e($product->producedFromProducts->pluck('name')->take(3)->implode(', ')); ?><?php echo e($product->producedFromProducts->count() > 3 ? ' +' . ($product->producedFromProducts->count() - 3) : ''); ?>

                                    </div>
                                <?php endif; ?>

                                <?php if((int)($product->variants_count ?? 0) > 0): ?>
                                    <div class="mt-1 text-[11px]">
                                        <a href="<?php echo e(route('admin.products.variants.index', $product)); ?>"
                                        class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                                            <?php echo e((int)($product->variants_count ?? 0)); ?> variant(s)
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <div class="mt-1 flex flex-wrap gap-1">
                                    <?php if($product->is_featured): ?>
                                        <span class="inline-flex items-center rounded-full bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-2 py-0.5 text-[10px]">
                                            Featured
                                        </span>
                                    <?php endif; ?>
                                    <?php if($product->is_new): ?>
                                        <span class="inline-flex items-center rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-2 py-0.5 text-[10px]">
                                            New
                                        </span>
                                    <?php endif; ?>
                                    <?php if($product->is_special): ?>
                                        <span class="inline-flex items-center rounded-full bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 px-2 py-0.5 text-[10px]">
                                            Special
                                        </span>
                                    <?php endif; ?>
                                </div>

                            </td>
                            <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300">
                                <?php
                                    $packType = (string) ($product->pack_type ?? 'quantity');
                                    $sellUnit = (string) ($product->sell_unit ?? '');
                                    $typeLabel = (string) ($product->type ?? 'simple') === 'variable'
                                        ? 'Variable choice'
                                        : ($packType === 'variable_weight' || $sellUnit === 'kg' ? 'Physical choice' : 'Direct buy');
                                ?>
                                <span class="rounded-full border border-gray-200 dark:border-gray-700 px-2 py-0.5 text-[11px]">
                                    <?php echo e($typeLabel); ?>

                                </span>
                            </td>
                            <td class="px-3 py-2 align-top text-right">
                                <?php
                                    $isVariablePriceProduct = (string) ($product->type ?? 'simple') === 'variable';
                                    $variantMinPrice = $product->variant_min_price !== null ? (float) $product->variant_min_price : null;
                                    $variantMaxPrice = $product->variant_max_price !== null ? (float) $product->variant_max_price : null;
                                ?>

                                <?php if($isVariablePriceProduct): ?>
                                    <?php if($variantMinPrice !== null && $variantMinPrice > 0): ?>
                                        <div class="font-medium text-gray-900 dark:text-gray-50">
                                            ₹<?php echo e(number_format($variantMinPrice, 2)); ?>

                                            <?php if($variantMaxPrice !== null && $variantMaxPrice > $variantMinPrice): ?>
                                                – ₹<?php echo e(number_format($variantMaxPrice, 2)); ?>

                                            <?php endif; ?>
                                        </div>
                                        <div class="text-[10px] text-gray-400">Variant prices</div>
                                    <?php else: ?>
                                        <span class="text-[11px] text-amber-600 dark:text-amber-300">Set on variants</span>
                                    <?php endif; ?>
                                <?php elseif($product->base_price !== null): ?>
                                    ₹<?php echo e(number_format((float) $product->base_price, 2)); ?>

                                <?php else: ?>
                                    <span class="text-[11px] text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                            <?php
                                $storedStock = $product->stock_quantity !== null ? (float) $product->stock_quantity : null;
                                $unitWeight = (float) ($product->product_weight ?? 0);
                                $inventoryStockWeight = (float) ($product->inventory_stock_weight_kg ?? 0);
                                $inventoryStockQty = (float) ($product->inventory_stock_qty ?? 0);
                                $variantStockWeight = (float) ($product->variant_stock_weight_kg ?? 0);
                                $variantStockQty = (float) ($product->variant_stock_qty ?? 0);
                                $isVariableProduct = (string) ($product->type ?? 'simple') === 'variable';
                                $usesWeightStock = $sellUnit === 'kg' || $packType === 'variable_weight';

                                if ($inventoryStockWeight > 0) {
                                    $stockWeight = $inventoryStockWeight;
                                } elseif ($isVariableProduct && $variantStockWeight > 0) {
                                    $stockWeight = $variantStockWeight;
                                } elseif ($storedStock !== null && $usesWeightStock) {
                                    $stockWeight = $storedStock;
                                } elseif ($storedStock !== null && abs($storedStock) < 0.0005) {
                                    $stockWeight = 0;
                                } elseif ($storedStock !== null && $unitWeight > 0) {
                                    $stockWeight = $storedStock * $unitWeight;
                                } else {
                                    $stockWeight = null;
                                }

                                if ($inventoryStockQty > 0) {
                                    $stockQty = $inventoryStockQty;
                                } elseif ($isVariableProduct && $variantStockQty > 0) {
                                    $stockQty = $variantStockQty;
                                } elseif ($storedStock !== null && ! $usesWeightStock) {
                                    $stockQty = $storedStock;
                                } elseif ($storedStock !== null && $usesWeightStock && $unitWeight > 0) {
                                    $stockQty = $storedStock / $unitWeight;
                                } elseif ($storedStock !== null && abs($storedStock) < 0.0005) {
                                    $stockQty = 0;
                                } else {
                                    $stockQty = null;
                                }
                            ?>
                            <td class="px-3 py-2 align-top text-right text-gray-700 dark:text-gray-300">
                                <?php if($stockWeight !== null): ?>
                                    <?php echo e($formatStockNumber($stockWeight)); ?> kg
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-2 align-top text-right text-gray-700 dark:text-gray-300">
                                <?php echo e($stockQty !== null ? $formatStockNumber($stockQty) : '—'); ?>

                            </td>
                            <td class="px-3 py-2 align-top text-center">
                                <?php if($product->is_active): ?>
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 px-2 py-0.5 text-[11px]">
                                        Active
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 px-2 py-0.5 text-[11px]">
                                        Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-2 align-top text-right">
                                <div class="inline-flex items-center gap-2">
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('manage labels')): ?>
                                        <a href="<?php echo e(route('admin.labels.edit', $product)); ?>"
                                           class="text-[11px] text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">
                                            Label
                                        </a>
                                    <?php endif; ?>

                                    <a href="<?php echo e(route('admin.products.images.index', $product)); ?>"
                                    class="text-[11px] text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">
                                        Images
                                    </a>

                                    <a href="<?php echo e(route('admin.products.edit', $product)); ?>"
                                       class="text-[11px] text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
                                        Edit
                                    </a>
                                    <form method="POST"
                                          action="<?php echo e(route('admin.products.destroy', $product)); ?>"
                                          onsubmit="return confirm('Delete this product?');"
                                    >
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit"
                                                class="text-[11px] text-red-600 hover:text-red-700">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="px-3 py-6 text-center text-xs text-gray-500 dark:text-gray-400">
                                No products found. <a href="<?php echo e(route('admin.products.create')); ?>" class="underline">Create one</a>.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($products->hasPages()): ?>
            <div class="product-pagination product-pagination-bottom">
                <?php echo e($products->onEachSide(1)->links()); ?>

            </div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/products/index.blade.php ENDPATH**/ ?>