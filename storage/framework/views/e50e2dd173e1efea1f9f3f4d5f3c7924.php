<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Barcode – <?php echo e($product->name); ?></title>
    <style>
        @media print {
            body {
                margin: 0;
            }
            .label {
                page-break-after: always;
            }
        }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #ffffff;
        }
        .label {
            width: 60mm;
            padding: 6mm;
            border: 1px solid #ddd;
            margin: 10mm auto;
            text-align: center;
        }
        .name {
            font-size: 10px;
            margin-bottom: 4px;
        }
        .code {
            font-size: 10px;
            margin-top: 4px;
            font-family: monospace;
        }
    </style>
</head>
<body>
<div class="label">
    <div class="name"><?php echo e($product->name); ?></div>
    <div>
        <?php echo DNS1D::getBarcodeSVG($product->barcode, 'C128', 2, 60, 'black', true); ?>

    </div>
    <div class="code"><?php echo e($product->barcode); ?></div>
</div>

<script>
    // Auto-open print dialog; user can cancel if they want
    window.print();
</script>
</body>
</html>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/products/barcode_label.blade.php ENDPATH**/ ?>