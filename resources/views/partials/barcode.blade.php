@php
    // Choose a safe default type – C128 is very flexible
    $barcodeValue = $barcodeValue ?? ($product->barcode ?? null);
    $barcodeType  = $barcodeType  ?? 'C128';
@endphp

@if($barcodeValue)
    <div class="inline-flex flex-col items-center gap-1 p-2 bg-white dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-700">
        <div class="text-[10px] text-gray-500 dark:text-gray-400 mb-1">
            Barcode for: {{ $product->name }}
        </div>

        {{-- Inline SVG barcode --}}
        <div class="bg-white px-2 py-1">
            {!! DNS1D::getBarcodeSVG($barcodeValue, $barcodeType, 2, 60, 'black', true) !!}
        </div>

        <div class="text-[11px] font-mono text-gray-700 dark:text-gray-200 mt-1">
            {{ $barcodeValue }}
        </div>
    </div>
@else
    <p class="text-[11px] text-gray-500 dark:text-gray-400">
        No barcode set for this product.
    </p>
@endif
