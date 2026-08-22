<?php
    /** @var \App\Models\CustomerAddress|null $address */
    $isEdit = $address && $address->exists;

    // Selected values (supports old() after validation errors)
    $stateCode = strtoupper(trim((string) old('state_code', $selectedStateCode ?? ($address->state_code ?? ''))));
    $city      = (string) old('city', $address->city ?? '');

    $states = $states ?? collect();
    $cities = $cities ?? collect();
?>

<div class="border border-gray-200 dark:border-gray-800 rounded-sm-lg bg-white dark:bg-gray-900 px-4 py-4 space-y-4 text-xs">
    <div class="space-y-1">
        <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">
            <?php echo e($isEdit ? 'Edit address' : 'New address'); ?>

        </h2>
        <p class="text-[11px] text-gray-500 dark:text-gray-400">
            This address can be used as shipping or billing during checkout.
        </p>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                Full name
            </label>
            <input
                type="text"
                name="full_name"
                value="<?php echo e(old('full_name', $address->full_name ?? auth()->user()->name)); ?>"
                required
                class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
            >
            <?php $__errorArgs = ['full_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                Mobile number
            </label>
            <input
                type="text"
                name="phone"
                value="<?php echo e(old('phone', $address->phone ?? auth()->user()->phone)); ?>"
                required
                class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
            >
            <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
    </div>

    <div>
        <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
            Address line 1
        </label>
        <input
            type="text"
            name="address_line1"
            value="<?php echo e(old('address_line1', $address->address_line1 ?? '')); ?>"
            required
            class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
        >
        <?php $__errorArgs = ['address_line1'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    <div>
        <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
            Address line 2 (optional)
        </label>
        <input
            type="text"
            name="address_line2"
            value="<?php echo e(old('address_line2', $address->address_line2 ?? '')); ?>"
            class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
        >
        <?php $__errorArgs = ['address_line2'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                State
            </label>

            <?php if($states->isEmpty()): ?>
                <div class="mt-1 rounded-sm border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[11px] text-gray-500 dark:text-gray-400">
                    No states found in database (states table is empty).
                </div>
            <?php else: ?>
                <select
                    name="state_code"
                    id="state_code"
                    required
                    class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                    <option value="" disabled <?php if($stateCode === ''): echo 'selected'; endif; ?>>Select state</option>
                    <?php $__currentLoopData = $states; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($s->code); ?>" <?php if($stateCode === (string)$s->code): echo 'selected'; endif; ?>>
                            <?php echo e($s->name); ?> (<?php echo e($s->code); ?>)
                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            <?php endif; ?>

            <?php $__errorArgs = ['state_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

            <p class="mt-1 text-[10px] text-gray-400 dark:text-gray-500">
                Used to validate a GSTIN saved with this billing address.
            </p>
        </div>

        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                City
            </label>

            <select
                name="city"
                id="city"
                required
                <?php if($stateCode === ''): echo 'disabled'; endif; ?>
                class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500 disabled:opacity-60"
            >
                <option value="" disabled <?php if($city === ''): echo 'selected'; endif; ?>>
                    <?php echo e($stateCode === '' ? 'Select a state first' : 'Select city'); ?>

                </option>

                
                <?php $cityOptions = collect($cities); ?>
                <?php if($city !== '' && !$cityOptions->contains($city)): ?>
                    <option value="<?php echo e($city); ?>" selected><?php echo e($city); ?></option>
                <?php endif; ?>

                <?php $__currentLoopData = $cityOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($c); ?>" <?php if($city === $c): echo 'selected'; endif; ?>><?php echo e($c); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>

            <?php $__errorArgs = ['city'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

            <p class="mt-1 text-[10px] text-gray-400 dark:text-gray-500">
                Cities are filtered by state (India only).
            </p>
        </div>

        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                PIN code
            </label>
            <input
                type="text"
                name="pincode"
                value="<?php echo e(old('pincode', $address->pincode ?? '')); ?>"
                required
                class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
            >
            <?php $__errorArgs = ['pincode'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                Country
            </label>

            
            <input
                type="text"
                value="India"
                disabled
                class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-2 py-1.5 text-xs opacity-90"
            >
            <input type="hidden" name="country" value="India">
        </div>

        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                GSTIN (optional)
            </label>
            <input
                type="text"
                name="gstin"
                value="<?php echo e(old('gstin', $address->gstin ?? '')); ?>"
                maxlength="15"
                autocomplete="off"
                class="mt-1 w-full uppercase rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
            >
            <?php $__errorArgs = ['gstin'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            <p class="mt-1 text-[10px] text-gray-400 dark:text-gray-500">
                The first two GSTIN digits must match this address state. At checkout, a Bill-To GSTIN determines CGST/SGST or IGST; without one, the delivery state is used.
            </p>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-4">
        <label class="inline-flex items-center gap-2 text-[11px] text-gray-700 dark:text-gray-300">
            <input
                type="checkbox"
                name="is_default_shipping"
                value="1"
                <?php if(old('is_default_shipping', $address->is_default_shipping ?? false)): echo 'checked'; endif; ?>
            >
            <span>Default shipping address</span>
        </label>

        <label class="inline-flex items-center gap-2 text-[11px] text-gray-700 dark:text-gray-300">
            <input
                type="checkbox"
                name="is_default_billing"
                value="1"
                <?php if(old('is_default_billing', $address->is_default_billing ?? false)): echo 'checked'; endif; ?>
            >
            <span>Default billing address</span>
        </label>
    </div>

    <div class="flex items-center gap-3 pt-2">
        <button
            type="submit"
            class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200"
        >
            <?php echo e($isEdit ? 'Save changes' : 'Save address'); ?>

        </button>

        <a href="<?php echo e(route('account.addresses.index')); ?>"
           class="text-[11px] text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
            Cancel
        </a>
    </div>
</div>

<script>
(function () {
    const stateEl = document.getElementById('state_code');
    const cityEl  = document.getElementById('city');

    if (!stateEl || !cityEl) return;

    const citiesUrl = <?php echo json_encode(\Illuminate\Support\Facades\Route::has('account.addresses.cities')
        ? route('account.addresses.cities')
        : null
    , 15, 512) ?>;

    if (!citiesUrl) return;

    function resetCity(placeholderText) {
        cityEl.innerHTML = '';
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = placeholderText || 'Select city';
        opt.disabled = true;
        opt.selected = true;
        cityEl.appendChild(opt);
    }

    async function loadCities(stateCode) {
        resetCity('Loading…');
        cityEl.disabled = true;

        try {
            const res = await fetch(citiesUrl + '?state_code=' + encodeURIComponent(stateCode), {
                headers: { 'Accept': 'application/json' }
            });

            const json = await res.json();
            const list = (json && json.ok && Array.isArray(json.cities)) ? json.cities : [];

            cityEl.innerHTML = '';
            const first = document.createElement('option');
            first.value = '';
            first.textContent = 'Select city';
            first.disabled = true;
            first.selected = true;
            cityEl.appendChild(first);

            list.forEach((name) => {
                const o = document.createElement('option');
                o.value = name;
                o.textContent = name;
                cityEl.appendChild(o);
            });

            cityEl.disabled = false;
        } catch (e) {
            resetCity('Select city');
            cityEl.disabled = false;
        }
    }

    stateEl.addEventListener('change', function () {
        const code = (stateEl.value || '').trim();
        if (!code) {
            resetCity('Select a state first');
            cityEl.disabled = true;
            return;
        }
        loadCities(code);
    });

    // If state is already selected but city list is empty (rare), load once.
    if ((stateEl.value || '').trim() && cityEl.options.length <= 1) {
        loadCities((stateEl.value || '').trim());
    }
})();
</script>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/customer/addresses/_form.blade.php ENDPATH**/ ?>