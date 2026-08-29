<?php $__env->startSection('title', 'Business Account Application'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $ui = (array) config('b2b_application_corrective.ui', []);
    $container = $ui['container'] ?? 'space-y-6';
    $panel = $ui['panel'] ?? 'border p-4';
    $heading = $ui['heading'] ?? 'text-2xl font-medium';
    $subheading = $ui['subheading'] ?? 'text-lg font-medium';
    $text = $ui['text'] ?? 'text-sm';
    $muted = $ui['muted'] ?? 'text-sm opacity-75';
    $labelClass = $ui['label'] ?? 'block text-sm';
    $fieldClass = $ui['field'] ?? 'block w-full';
    $checkboxClass = $ui['checkbox'] ?? '';
    $primary = $ui['button_primary'] ?? 'inline-flex items-center px-4 py-2';
    $secondary = $ui['button_secondary'] ?? 'inline-flex items-center px-4 py-2';
    $errorClass = $ui['alert_error'] ?? 'border p-3';
?>

<div class="<?php echo e($container); ?>">
    <div>
        <p class="<?php echo e($muted); ?>">Step 1 of 2</p>
        <h1 class="mt-1 <?php echo e($heading); ?>">Business and contact details</h1>
        <p class="mt-2 <?php echo e($text); ?>">You are applying from your existing Bandara customer account. B2B access is enabled only after approval.</p>
    </div>

    <?php if($errors->any()): ?>
        <div class="<?php echo e($errorClass); ?>" role="alert">
            <p>Please correct the following information:</p>
            <ul class="mt-2 list-disc pl-5">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if($application?->status === \App\Enums\B2BApplicationStatus::MoreInformationRequired && $application->customer_message): ?>
        <div class="<?php echo e($ui['alert_info'] ?? $panel); ?>">
            <strong>Bandara requested additional information</strong>
            <p class="mt-1 <?php echo e($text); ?>"><?php echo e($application->customer_message); ?></p>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(route('account.business-application.step-one.save')); ?>" class="space-y-6">
        <?php echo csrf_field(); ?>

        <section class="<?php echo e($panel); ?>">
            <h2 class="<?php echo e($subheading); ?>">Contact information</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="<?php echo e($labelClass); ?>">First name *</span>
                    <input name="contact_first_name" value="<?php echo e(old('contact_first_name', $application?->contact_first_name ?? $defaults['contact_first_name'])); ?>" class="<?php echo e($fieldClass); ?>" required>
                </label>
                <label class="block">
                    <span class="<?php echo e($labelClass); ?>">Last name</span>
                    <input name="contact_last_name" value="<?php echo e(old('contact_last_name', $application?->contact_last_name ?? $defaults['contact_last_name'])); ?>" class="<?php echo e($fieldClass); ?>">
                </label>
                <label class="block">
                    <span class="<?php echo e($labelClass); ?>">Email *</span>
                    <input type="email" name="email" value="<?php echo e(old('email', $application?->email ?? $defaults['email'])); ?>" class="<?php echo e($fieldClass); ?>" required>
                </label>
                <label class="block">
                    <span class="<?php echo e($labelClass); ?>">Mobile number *</span>
                    <input name="phone" value="<?php echo e(old('phone', $application?->phone ?? $defaults['phone'])); ?>" class="<?php echo e($fieldClass); ?>" required>
                </label>
                <label class="block">
                    <span class="<?php echo e($labelClass); ?>">WhatsApp number</span>
                    <input name="whatsapp" value="<?php echo e(old('whatsapp', $application?->whatsapp ?? $defaults['whatsapp'])); ?>" class="<?php echo e($fieldClass); ?>">
                </label>
                <label class="block">
                    <span class="<?php echo e($labelClass); ?>">Preferred contact method *</span>
                    <select name="preferred_contact_method" class="<?php echo e($fieldClass); ?>" required>
                        <?php $__currentLoopData = ['phone' => 'Phone', 'whatsapp' => 'WhatsApp', 'email' => 'Email']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php if(old('preferred_contact_method', $application?->preferred_contact_method ?? 'phone') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </label>
            </div>
        </section>

        <section class="<?php echo e($panel); ?>">
            <h2 class="<?php echo e($subheading); ?>">Business information</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label class="block md:col-span-2">
                    <span class="<?php echo e($labelClass); ?>">Legal business name *</span>
                    <input name="legal_business_name" value="<?php echo e(old('legal_business_name', $application?->legal_business_name)); ?>" class="<?php echo e($fieldClass); ?>" required>
                </label>
                <label class="block">
                    <span class="<?php echo e($labelClass); ?>">Trading name</span>
                    <input name="trading_name" value="<?php echo e(old('trading_name', $application?->trading_name)); ?>" class="<?php echo e($fieldClass); ?>">
                </label>
                <label class="block">
                    <span class="<?php echo e($labelClass); ?>">Business type *</span>
                    <select name="business_type" class="<?php echo e($fieldClass); ?>" required>
                        <option value="">Select business type</option>
                        <?php $__currentLoopData = (array) config('b2b_application.business_types', []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php if(old('business_type', $application?->business_type) === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </label>

                <div class="md:col-span-2">
                    <input type="hidden" name="gst_registered" value="0">
                    <label class="flex items-start gap-2">
                        <input id="gst_registered" type="checkbox" name="gst_registered" value="1" <?php if(old('gst_registered', $application?->gst_registered ?? false)): echo 'checked'; endif; ?> class="<?php echo e($checkboxClass); ?>">
                        <span class="<?php echo e($labelClass); ?>">This business is registered for GST</span>
                    </label>
                </div>

                <label id="gstin_field" class="block">
                    <span class="<?php echo e($labelClass); ?>">GSTIN</span>
                    <input name="gstin" maxlength="15" value="<?php echo e(old('gstin', $application?->gstin)); ?>" class="<?php echo e($fieldClass); ?> uppercase">
                </label>
                <label class="block">
                    <span class="<?php echo e($labelClass); ?>">PAN</span>
                    <input name="pan" maxlength="10" value="<?php echo e(old('pan', $application?->pan)); ?>" class="<?php echo e($fieldClass); ?> uppercase">
                </label>
                <label class="block">
                    <span class="<?php echo e($labelClass); ?>">FSSAI number</span>
                    <input name="fssai_number" maxlength="14" value="<?php echo e(old('fssai_number', $application?->fssai_number)); ?>" class="<?php echo e($fieldClass); ?>">
                </label>
                <label class="block">
                    <span class="<?php echo e($labelClass); ?>">Website or social page</span>
                    <input type="url" name="website" value="<?php echo e(old('website', $application?->website)); ?>" placeholder="https://" class="<?php echo e($fieldClass); ?>">
                </label>
            </div>
        </section>

        <section class="<?php echo e($panel); ?>">
            <h2 class="<?php echo e($subheading); ?>">Business address</h2>
            <p class="mt-1 <?php echo e($muted); ?>">This is used to assess delivery coverage and servicing requirements.</p>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label class="block md:col-span-2">
                    <span class="<?php echo e($labelClass); ?>">Address line 1 *</span>
                    <input name="address_line_1" value="<?php echo e(old('address_line_1', $application?->address_line_1)); ?>" class="<?php echo e($fieldClass); ?>" required>
                </label>
                <label class="block md:col-span-2">
                    <span class="<?php echo e($labelClass); ?>">Address line 2</span>
                    <input name="address_line_2" value="<?php echo e(old('address_line_2', $application?->address_line_2)); ?>" class="<?php echo e($fieldClass); ?>">
                </label>
                <label class="block">
                    <span class="<?php echo e($labelClass); ?>">State *</span>
                    <select id="state_id" name="state_id" class="<?php echo e($fieldClass); ?>" required>
                        <option value="">Select state</option>
                        <?php $__currentLoopData = $states; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $state): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($state->id); ?>" <?php if((string) old('state_id', $application?->state_id) === (string) $state->id): echo 'selected'; endif; ?>><?php echo e($state->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </label>
                <label class="block">
                    <span class="<?php echo e($labelClass); ?>">City *</span>
                    <select id="city_id" name="city_id" data-selected="<?php echo e(old('city_id', $application?->city_id)); ?>" class="<?php echo e($fieldClass); ?>" required>
                        <option value="">Select city</option>
                        <?php $__currentLoopData = $cities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($city->id); ?>" <?php if((string) old('city_id', $application?->city_id) === (string) $city->id): echo 'selected'; endif; ?>><?php echo e($city->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </label>
                <label class="block">
                    <span class="<?php echo e($labelClass); ?>">PIN code *</span>
                    <input name="postal_code" maxlength="6" inputmode="numeric" value="<?php echo e(old('postal_code', $application?->postal_code)); ?>" class="<?php echo e($fieldClass); ?>" required>
                </label>
            </div>
        </section>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="<?php echo e(route('business-account.index')); ?>" class="<?php echo e($secondary); ?>">Cancel</a>
            <button type="submit" class="<?php echo e($primary); ?>">Save and continue</button>
        </div>
    </form>
</div>

<script>
(() => {
    const stateSelect = document.getElementById('state_id');
    const citySelect = document.getElementById('city_id');
    const gstCheckbox = document.getElementById('gst_registered');
    const gstinField = document.getElementById('gstin_field');
    const citiesUrl = <?php echo json_encode(route('account.business-application.cities'), 15, 512) ?>;
    let requestController = null;

    const setGstinVisibility = () => {
        if (gstinField) {
            gstinField.hidden = !gstCheckbox?.checked;
        }
    };

    const resetCities = (label = 'Select city') => {
        if (!citySelect) return;
        citySelect.replaceChildren(new Option(label, ''));
    };

    const loadCities = async () => {
        if (!stateSelect || !citySelect) return;

        const stateId = stateSelect.value;
        const selectedCity = citySelect.dataset.selected || '';
        citySelect.dataset.selected = '';

        if (!stateId) {
            resetCities();
            citySelect.disabled = false;
            return;
        }

        requestController?.abort();
        requestController = new AbortController();
        resetCities('Loading cities…');
        citySelect.disabled = true;

        try {
            const url = new URL(citiesUrl, window.location.origin);
            url.searchParams.set('state_id', stateId);
            const response = await fetch(url.toString(), {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: requestController.signal,
            });

            if (!response.ok) {
                throw new Error(`City request failed with status ${response.status}`);
            }

            const payload = await response.json();
            const records = Array.isArray(payload) ? payload : (Array.isArray(payload.data) ? payload.data : []);
            resetCities();

            records.forEach((record) => {
                const option = new Option(String(record.name ?? ''), String(record.id ?? ''));
                option.selected = selectedCity !== '' && String(record.id) === String(selectedCity);
                citySelect.add(option);
            });
        } catch (error) {
            if (error?.name !== 'AbortError') {
                console.error('Bandara business city lookup failed.', error);
                resetCities('Could not load cities');
            }
        } finally {
            citySelect.disabled = false;
        }
    };

    gstCheckbox?.addEventListener('change', setGstinVisibility);
    setGstinVisibility();

    stateSelect?.addEventListener('change', () => {
        if (citySelect) citySelect.dataset.selected = '';
        loadCities();
    });
})();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.customer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/account/business-application/step-one.blade.php ENDPATH**/ ?>