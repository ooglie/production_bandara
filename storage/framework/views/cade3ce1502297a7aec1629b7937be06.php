<?php $__env->startSection('title', 'Wishlist'); ?>

<?php $__env->startSection('content'); ?>
<?php
    use Illuminate\Support\Facades\Storage;

    $b2bTerms = app(\App\Services\B2BTermsService::class);
    $isB2BWishlist = (bool) ($isB2BWishlist ?? false);
?>

<div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Wishlist
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Save products you want to order later.
            </p>
        </div>
    </div>

    <?php if($items->isEmpty()): ?>
        <div class="rounded border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 text-xs text-gray-500 dark:text-gray-400">
            Your wishlist is empty.
        </div>
    <?php else: ?>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $product = $item->product;
                    if (!$product) continue;
                    $variant = $item->variant;
                    $canB2BBuy = $b2bTerms->canBuy(auth()->user(), $product, $variant);
                    $productUrl = route('product.show', $product);
                    $cartStoreUrl = route('cart.store');
                    $destroyUrl = route('wishlist.destroy', $item);
                ?>
                <div class="border border-gray-200 dark:border-gray-800 rounded-sm bg-white dark:bg-gray-900 p-3 flex flex-col text-xs">
                    <div class="aspect-[4/3] rounded-sm bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-3 overflow-hidden">
                        <?php if($product->primary_image): ?>
                            <img
                                src="<?php echo e(Storage::disk(config('media.public_disk', 'public'))->url($product->primary_image)); ?>"
                                alt="<?php echo e($product->name); ?>"
                                class="object-cover w-full h-full"
                            >
                        <?php else: ?>
                            <span class="text-[11px] text-gray-400 dark:text-gray-500">
                                No image
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="flex-1 space-y-1">
                        <a href="<?php echo e($productUrl); ?>"
                           class="text-xs font-medium text-gray-900 dark:text-gray-50 line-clamp-2 hover:underline">
                            <?php echo e($product->name); ?>

                        </a>

                        <?php if($variant): ?>
                            <?php
                                $parts = [];
                                foreach ($variant->attributeValues ?? [] as $value) {
                                    $parts[] = $value->attribute->name . ': ' . $value->value;
                                }
                                $variantName = trim((string) ($variant->name ?? ''));
                                $packType = (string) ($variant->pack_type ?? '');
                                if ($variantName !== '') {
                                    $variantLabel = $variantName;
                                } elseif ($packType === 'fixed_piece_pack' && (float) ($variant->pieces_per_pack ?? 0) > 0) {
                                    $variantLabel = rtrim(rtrim(number_format((float) $variant->pieces_per_pack, 3), '0'), '.') . ' pcs pack';
                                } elseif ($packType === 'fixed_weight_pack' && (float) ($variant->product_weight ?? 0) > 0) {
                                    $variantLabel = rtrim(rtrim(number_format((float) $variant->product_weight, 3), '0'), '.') . ' kg pack';
                                } else {
                                    $variantLabel = implode(' · ', $parts) ?: ('Variant #'.$variant->id);
                                }
                            ?>
                            <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                <?php echo e($variantLabel); ?>

                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-2 flex items-center justify-between gap-2">
                        <?php if(! $isB2BWishlist || $canB2BBuy): ?>
                            <form method="POST" action="<?php echo e($cartStoreUrl); ?>">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="product_id" value="<?php echo e($product->id); ?>">
                                <?php if($variant): ?>
                                    <input type="hidden" name="product_variant_id" value="<?php echo e($variant->id); ?>">
                                <?php endif; ?>
                                <button type="submit"
                                        class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-[11px] hover:bg-gray-800 dark:hover:bg-gray-200">
                                    <?php echo e($isB2BWishlist ? 'Add to B2B cart' : 'Add to cart'); ?>

                                </button>
                            </form>
                        <?php else: ?>
                            <a href="<?php echo e($productUrl); ?>" class="inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] hover:bg-gray-100 dark:hover:bg-gray-800">
                                Request access
                            </a>
                        <?php endif; ?>

                        <form method="POST" action="<?php echo e($destroyUrl); ?>">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit"
                                    class="text-[11px] text-red-600 hover:text-red-700">
                                Remove
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.customer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/customer/wishlist/index.blade.php ENDPATH**/ ?>