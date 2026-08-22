<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo e($product->name); ?> batch labels</title>
    <style>
        @page { size: 4in 3in; margin: 0; }
        html, body { margin: 0; padding: 0; background: #fff; }
        .label-sheet { width: 288pt; height: 216pt; margin: 0; padding: 0; page-break-after: always; }
        .label-sheet:last-child { page-break-after: auto; }
        <?php echo $__env->make('labels._styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </style>
</head>
<body>
<?php $__currentLoopData = $labels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="label-sheet">
        <?php echo $__env->make('labels._canvas', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</body>
</html>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/labels/batch.blade.php ENDPATH**/ ?>