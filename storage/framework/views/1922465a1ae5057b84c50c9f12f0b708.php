<?php
    $bands = $pieceSelector['bands'] ?? [];
    $showBandChooser = count($bands) > 1;
    $requestedWeightKg = request()->query('piece_weight_kg', request()->query('weight'));
    $oldSelectedWeightKg = old('piece_weight_kg', $requestedWeightKg !== null && $requestedWeightKg !== ''
        ? number_format((float) $requestedWeightKg, 3, '.', '')
        : null);
    $requestedBand = request()->query('band');

    $initialBand = null;

    if ($requestedBand && collect($bands)->contains(fn ($band) => $band['key'] === $requestedBand)) {
        $initialBand = $requestedBand;
    } elseif ($oldSelectedWeightKg) {
        foreach ($bands as $band) {
            foreach (($band['choices'] ?? []) as $choice) {
                if ((string) $choice['key'] === (string) $oldSelectedWeightKg) {
                    $initialBand = $band['key'];
                    break 2;
                }
            }
        }
    }

    if (!$initialBand && !empty($bands)) {
        $initialBand = $bands[0]['key'];
    }
?>

<div id="piece-selector-root"
     class="space-y-4"
     data-initial-band="<?php echo e($initialBand); ?>"
     data-mrp-ratio="<?php echo e(number_format((float) ($piecePricingRatio ?? 0), 6, '.', '')); ?>">
    <?php if($showBandChooser): ?>
        <div class="space-y-2">
            <div class="text-[11px] font-medium text-gray-700 dark:text-gray-300">
                Choose slab size
            </div>

            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                <?php $__currentLoopData = $bands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $band): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <button
                        type="button"
                        class="piece-band-btn rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-3 text-left transition hover:bg-white dark:hover:bg-gray-900"
                        data-piece-band="<?php echo e($band['key']); ?>"
                        data-price-min="<?php echo e(number_format((float) $band['price_min'], 2, '.', '')); ?>"
                        data-price-max="<?php echo e(number_format((float) $band['price_max'], 2, '.', '')); ?>"
                    >
                        <div class="text-[12px] font-semibold text-gray-900 dark:text-gray-50">
                            <?php echo e($band['label']); ?>

                        </div>
                        <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                            <?php echo e($band['count']); ?> available
                        </div>
                        <div class="mt-1 text-[11px] font-medium text-gray-700 dark:text-gray-200">
                            ₹<?php echo e(number_format((float) $band['price_min'], 2)); ?>

                            <?php if((float) $band['price_max'] > (float) $band['price_min']): ?>
                                – ₹<?php echo e(number_format((float) $band['price_max'], 2)); ?>

                            <?php endif; ?>
                        </div>
                    </button>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="space-y-2">
        <div class="text-[11px] font-medium text-gray-700 dark:text-gray-300">
            Choose exact slab
        </div>

        <?php $__currentLoopData = $bands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $band): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div
                class="grid gap-2 sm:grid-cols-2 <?php echo e($showBandChooser ? 'hidden' : ''); ?>"
                data-piece-band-panel="<?php echo e($band['key']); ?>"
                data-price-min="<?php echo e(number_format((float) $band['price_min'], 2, '.', '')); ?>"
                data-price-max="<?php echo e(number_format((float) $band['price_max'], 2, '.', '')); ?>"
            >
                <?php $__currentLoopData = $band['choices']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $choice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label class="piece-option-card block cursor-pointer rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-3 transition hover:bg-white dark:hover:bg-gray-900">
                        <input
                            type="radio"
                            name="piece_weight_kg"
                            value="<?php echo e(number_format((float) $choice['weight_kg'], 3, '.', '')); ?>"
                            class="sr-only piece-option-radio"
                            data-price="<?php echo e(number_format((float) $choice['price'], 2, '.', '')); ?>"
                            data-weight="<?php echo e(number_format((float) $choice['weight_kg'], 3, '.', '')); ?>"
                            data-weight-label="<?php echo e($choice['weight_label']); ?>"
                            data-count="<?php echo e((int) $choice['count']); ?>"
                            <?php if((string) $oldSelectedWeightKg === (string) number_format((float) $choice['weight_kg'], 3, '.', '')): echo 'checked'; endif; ?>
                        >

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-[12px] font-semibold text-gray-900 dark:text-gray-50">
                                <?php echo e($choice['weight_label']); ?>

                            </span>
                            <span class="text-[12px] font-semibold text-gray-900 dark:text-gray-50">
                                ₹<?php echo e(number_format((float) $choice['price'], 2)); ?>

                            </span>
                        </div>
                    </label>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <div class="space-y-2">
        <div class="text-[11px] font-medium text-gray-700 dark:text-gray-300">
            Quantity
        </div>

        <select
            name="quantity"
            id="piece-quantity-select"
            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2.5 py-2 text-[11px] text-gray-700 dark:text-gray-200"
            disabled
        >
            <option value="">Select slab first…</option>
        </select>
    </div>

    <div id="selected-piece-summary"
         class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3 text-[12px] text-gray-600 dark:text-gray-300">
        Select a slab to continue.
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('piece-selector-root');
    if (!root || root.dataset.bound === 'true') return;
    root.dataset.bound = 'true';

    const initialBand = root.dataset.initialBand || null;
    const mrpRatio = parseFloat(root.dataset.mrpRatio || '0');

    const bandButtons = Array.from(root.querySelectorAll('[data-piece-band]'));
    const bandPanels = Array.from(root.querySelectorAll('[data-piece-band-panel]'));
    const radios = Array.from(root.querySelectorAll('.piece-option-radio'));
    const cards = Array.from(root.querySelectorAll('.piece-option-card'));
    const qtySelect = document.getElementById('piece-quantity-select');
    const summary = document.getElementById('selected-piece-summary');
    const submitBtn = document.getElementById('add-to-cart-submit');

    const topPrice = document.getElementById('piece-top-price');
    const topMrp = document.getElementById('piece-top-mrp');
    const saveCard = document.getElementById('piece-top-save-card');
    const saveAmount = document.getElementById('piece-top-save-amount');
    const savePct = document.getElementById('piece-top-save-pct');

    let activeBandKey = initialBand;

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

    function setSubmitEnabled(enabled) {
        if (!submitBtn) return;
        submitBtn.disabled = !enabled;
        submitBtn.classList.toggle('opacity-50', !enabled);
        submitBtn.classList.toggle('cursor-not-allowed', !enabled);
    }

    function checkedRadio() {
        return radios.find(r => r.checked) || null;
    }

    function getBandPanel(key) {
        return bandPanels.find(panel => panel.getAttribute('data-piece-band-panel') === key) || null;
    }

    function updateTopPricing(minPrice, maxPrice) {
        if (!topPrice) return;

        minPrice = Number(minPrice || 0);
        maxPrice = Number(maxPrice || minPrice);

        topPrice.textContent = moneyRange(minPrice, maxPrice);

        if (!topMrp || !saveCard || !saveAmount || !savePct) {
            return;
        }

        if (mrpRatio > 1.0001) {
            const mrpMin = minPrice * mrpRatio;
            const mrpMax = maxPrice * mrpRatio;

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
            saveCard.classList.add('hidden');
            saveAmount.textContent = '';
            savePct.textContent = '';
        }
    }

    function updateTopPricingFromActiveBand() {
        const panel = getBandPanel(activeBandKey);
        if (!panel) return;

        updateTopPricing(
            panel.dataset.priceMin || '0',
            panel.dataset.priceMax || panel.dataset.priceMin || '0'
        );
    }

    function activateBand(key) {
        activeBandKey = key;

        bandButtons.forEach(function (btn) {
            const active = btn.getAttribute('data-piece-band') === key;
            btn.classList.toggle('ring-2', active);
            btn.classList.toggle('ring-gray-400', active);
            btn.classList.toggle('dark:ring-gray-500', active);
            btn.classList.toggle('bg-white', active);
            btn.classList.toggle('dark:bg-gray-900', active);
        });

        bandPanels.forEach(function (panel) {
            const active = panel.getAttribute('data-piece-band-panel') === key;
            panel.classList.toggle('hidden', !active);
        });

        const selected = checkedRadio();
        if (selected) {
            const panel = selected.closest('[data-piece-band-panel]');
            if (!panel || panel.getAttribute('data-piece-band-panel') !== key) {
                radios.forEach(r => r.checked = false);
            }
        }

        updateSelection();
    }

    function updateCardStates() {
        cards.forEach(function (card) {
            const radio = card.querySelector('.piece-option-radio');
            const checked = radio && radio.checked;

            card.classList.toggle('ring-2', checked);
            card.classList.toggle('ring-gray-400', checked);
            card.classList.toggle('dark:ring-gray-500', checked);
            card.classList.toggle('bg-white', checked);
            card.classList.toggle('dark:bg-gray-900', checked);
        });
    }

    function rebuildQuantityOptions() {
        const selected = checkedRadio();
        const currentValue = parseInt(qtySelect.value || '1', 10) || 1;

        qtySelect.innerHTML = '';

        if (!selected) {
            qtySelect.disabled = true;
            qtySelect.innerHTML = '<option value="">Select slab first…</option>';
            return;
        }

        const maxCount = Math.max(parseInt(selected.dataset.count || '1', 10), 1);

        for (let i = 1; i <= maxCount; i++) {
            const opt = document.createElement('option');
            opt.value = String(i);
            opt.textContent = String(i);
            qtySelect.appendChild(opt);
        }

        qtySelect.disabled = false;
        qtySelect.value = String(Math.min(currentValue, maxCount));
    }

    function updateSelection() {
        const selected = checkedRadio();

        if (!selected) {
            summary.textContent = 'Select a slab to continue.';
            rebuildQuantityOptions();
            setSubmitEnabled(false);
            updateCardStates();
            updateTopPricingFromActiveBand();
            return;
        }

        rebuildQuantityOptions();

        const qty = parseInt(qtySelect.value || '1', 10) || 1;
        const weightLabel = selected.dataset.weightLabel || '';
        const price = parseFloat(selected.dataset.price || '0');
        const total = price * qty;

        summary.innerHTML =
            '<div class="font-medium text-gray-900 dark:text-gray-50">Selected slab: ' + weightLabel + ' × ' + qty + '</div>' +
            '<div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Choose quantity and continue to add.</div>';

        updateTopPricing(total, total);
        setSubmitEnabled(true);
        updateCardStates();
    }

    bandButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            activateBand(btn.getAttribute('data-piece-band'));
        });
    });

    radios.forEach(function (radio) {
        radio.addEventListener('change', updateSelection);
    });

    qtySelect.addEventListener('change', updateSelection);

    if (bandButtons.length > 0) {
        activateBand(initialBand || bandButtons[0].getAttribute('data-piece-band'));
    } else if (bandPanels.length > 0) {
        activeBandKey = initialBand || bandPanels[0].getAttribute('data-piece-band-panel');
        updateSelection();
    } else {
        updateSelection();
    }
});
</script>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/products/_piece_selector.blade.php ENDPATH**/ ?>