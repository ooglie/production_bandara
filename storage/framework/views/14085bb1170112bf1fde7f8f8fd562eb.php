<?php
    $pieceSelector = $pieceSelector ?? ['enabled' => false];
    $bands = $pieceSelector['bands'] ?? [];

    $pieceRangeOptions = collect(
    $pieceSelector['ranges']
        ?? $pieceSelector['range_options']
        ?? $pieceSelector['size_ranges']
        ?? $pieceSelector['slab_ranges']
        ?? $pieceSelector['groups']
        ?? $pieceSelector['buckets']
        ?? []
    )->map(function ($range, $index) {
        $label = data_get($range, 'label')
            ?? data_get($range, 'name')
            ?? data_get($range, 'title')
            ?? data_get($range, 'range_label')
            ?? data_get($range, 'size_label')
            ?? data_get($range, 'bucket_label');

        $label = is_string($label) ? trim($label) : null;

        if ($label === null || $label === '') {
            return null;
        }

        return [
            'value' => (string) (
                data_get($range, 'id')
                ?? data_get($range, 'key')
                ?? data_get($range, 'code')
                ?? data_get($range, 'slug')
                ?? $index
            ),
            'label' => $label,
            'match' => Str::lower(preg_replace('/\s+/', ' ', $label)),
        ];
    })->filter()->values();
?>

<?php if(($pieceSelector['enabled'] ?? false) && !empty($bands)): ?>
    <details class="relative">
        <summary
            class="list-none cursor-pointer relative inline-flex items-center justify-center h-8 w-8 rounded-sm border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800"
            title="Choose size"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                 class="h-4 w-4 text-gray-700 dark:text-gray-200"
                 fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M4.5 7.5h15M4.5 12h15M4.5 16.5h15" />
            </svg>
        </summary>

        <div class="absolute right-0 z-50 mt-2 w-56 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-lg p-2">
            <div class="px-2 pb-1 text-[10px] uppercase tracking-wide text-gray-400">
                Choose size
            </div>

            <div class="space-y-1">
                <?php $__currentLoopData = $bands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $band): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e(route('product.show', $product)); ?>?band=<?php echo e(urlencode($band['key'])); ?>#piece-selector-root"
                       class="block rounded-lg px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <div class="text-[12px] font-medium text-gray-900 dark:text-gray-50">
                            <?php echo e($band['label']); ?>

                        </div>
                        <div class="mt-0.5 text-[10px] text-gray-500 dark:text-gray-400">
                            <?php echo e($band['count']); ?> available ·
                            ₹<?php echo e(number_format((float) $band['price_min'], 2)); ?>

                            <?php if((float) $band['price_max'] > (float) $band['price_min']): ?>
                                – ₹<?php echo e(number_format((float) $band['price_max'], 2)); ?>

                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </details>
<?php endif; ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/products/_piece_band_picker_button.blade.php ENDPATH**/ ?>