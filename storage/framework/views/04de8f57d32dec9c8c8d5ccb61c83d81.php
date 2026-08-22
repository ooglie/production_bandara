<?php
    // Choose a safe default type – C128 is very flexible
    $barcodeValue = $barcodeValue ?? ($product->barcode ?? null);
    $barcodeType  = $barcodeType  ?? 'C128';
?>

<?php if($barcodeValue): ?>
    <div class="inline-flex flex-col items-center gap-1 p-2 bg-white dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-700">
        <div class="text-[10px] text-gray-500 dark:text-gray-400 mb-1">
            Barcode for: <?php echo e($product->name); ?>

        </div>

        
        <div class="bg-white px-2 py-1">
            <?php echo DNS1D::getBarcodeSVG($barcodeValue, $barcodeType, 2, 60, 'black', true); ?>

        </div>

        <div class="text-[11px] font-mono text-gray-700 dark:text-gray-200 mt-1">
            <?php echo e($barcodeValue); ?>

        </div>
    </div>
<?php else: ?>
    <p class="text-[11px] text-gray-500 dark:text-gray-400">
        No barcode set for this product.
    </p>
<?php endif; ?>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/products/partials/barcode.blade.php ENDPATH**/ ?>