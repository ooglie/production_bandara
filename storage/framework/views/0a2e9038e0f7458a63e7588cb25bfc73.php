<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo e($label['product_name']); ?> label</title>
    <style>
        @page { size: 4in 3in; margin: 0; }
        html, body { margin: 0; padding: 0; background: #fff; }
        .label-sheet { width: 288pt; height: 216pt; margin: 0; padding: 0; page-break-after: always; }
        .label-sheet:last-child { page-break-after: auto; }
        <?php echo $__env->make('labels._styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </style>
</head>
<body>
<?php for($copy = 0; $copy < $copies; $copy++): ?>
    <div class="label-sheet">
        <?php echo $__env->make('labels._canvas', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
<?php endfor; ?>
</body>
</html>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/labels/product.blade.php ENDPATH**/ ?>