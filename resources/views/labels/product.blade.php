<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $label['product_name'] }} label</title>
    <style>
        @page { size: 4in 3in; margin: 0; }
        html, body { margin: 0; padding: 0; background: #fff; }
        .label-sheet { width: 288pt; height: 216pt; margin: 0; padding: 0; page-break-after: always; }
        .label-sheet:last-child { page-break-after: auto; }
        @include('labels._styles')
    </style>
</head>
<body>
@for($copy = 0; $copy < $copies; $copy++)
    <div class="label-sheet">
        @include('labels._canvas')
    </div>
@endfor
</body>
</html>
