<?php $__env->startSection('title', 'Label - ' . $product->name); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $input = 'mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-xs text-gray-900 shadow-sm focus:border-gray-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100';
        $labelClass = 'block text-[11px] font-medium text-gray-700 dark:text-gray-300';
    ?>

    <style>
        <?php echo $__env->make('labels._styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        .label-browser-preview { width: 384px; max-width: 100%; }
        .label-browser-preview .product-label-canvas { transform-origin: top left; }
    </style>

    <div class="max-w-7xl mx-auto space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <a href="<?php echo e(route('admin.labels.index')); ?>" class="text-[11px] text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">← Product labels</a>
                <h1 class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50"><?php echo e($product->name); ?></h1>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Database values are prefilled. Changes here affect only this print job.</p>
            </div>
            <?php if($batchEnabled): ?>
                <a href="<?php echo e(route('admin.labels.batch.edit', $product)); ?>"
                   class="rounded-md bg-gray-900 px-3 py-2 text-xs font-medium text-white dark:bg-gray-100 dark:text-gray-900">
                    Batch by weight
                </a>
            <?php endif; ?>
        </div>

        <form method="POST" action="<?php echo e(route('admin.labels.pdf', $product)); ?>" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_430px]">
            <?php echo csrf_field(); ?>

            <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-950">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="category" class="<?php echo e($labelClass); ?>">Category badge</label>
                        <input id="category" name="category" value="<?php echo e(old('category', $form['category'])); ?>" maxlength="24" required class="<?php echo e($input); ?>">
                        <?php $__errorArgs = ['category'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label for="country" class="<?php echo e($labelClass); ?>">Country of origin</label>
                        <input id="country" name="country" value="<?php echo e(old('country', $form['country'])); ?>" maxlength="32" required class="<?php echo e($input); ?>">
                        <?php $__errorArgs = ['country'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="product_name" class="<?php echo e($labelClass); ?>">Product name</label>
                        <input id="product_name" name="product_name" value="<?php echo e(old('product_name', $form['product_name'])); ?>" maxlength="64" required class="<?php echo e($input); ?>">
                        <?php $__errorArgs = ['product_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label for="price" class="<?php echo e($labelClass); ?>">MRP including taxes (₹)</label>
                        <input id="price" name="price" type="number" min="0" max="999999.99" step="0.01" value="<?php echo e(old('price', $form['price'])); ?>" required class="<?php echo e($input); ?>">
                        <?php $__errorArgs = ['price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label for="unit_label" class="<?php echo e($labelClass); ?>">Weight / pack text</label>
                        <input id="unit_label" name="unit_label" value="<?php echo e(old('unit_label', $form['unit_label'])); ?>" maxlength="24" required class="<?php echo e($input); ?>">
                        <?php $__errorArgs = ['unit_label'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label for="company_name" class="<?php echo e($labelClass); ?>">Company name</label>
                        <input id="company_name" name="company_name" value="<?php echo e(old('company_name', $form['company_name'])); ?>" maxlength="40" required class="<?php echo e($input); ?>">
                        <?php $__errorArgs = ['company_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label for="fssai" class="<?php echo e($labelClass); ?>">FSSAI licence number</label>
                        <input id="fssai" name="fssai" inputmode="numeric" value="<?php echo e(old('fssai', $form['fssai'])); ?>" maxlength="14" required class="<?php echo e($input); ?>">
                        <?php $__errorArgs = ['fssai'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="website" class="<?php echo e($labelClass); ?>">Website</label>
                        <input id="website" name="website" type="url" value="<?php echo e(old('website', $form['website'])); ?>" maxlength="100" required class="<?php echo e($input); ?>">
                        <?php $__errorArgs = ['website'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label for="best_before" class="<?php echo e($labelClass); ?>">Best before month</label>
                        <input id="best_before" name="best_before" type="month" value="<?php echo e(old('best_before', $form['best_before'])); ?>" required class="<?php echo e($input); ?>">
                        <?php $__errorArgs = ['best_before'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label for="copies" class="<?php echo e($labelClass); ?>">Copies</label>
                        <input id="copies" name="copies" type="number" min="1" max="100" value="<?php echo e(old('copies', $form['copies'])); ?>" required class="<?php echo e($input); ?>">
                        <?php $__errorArgs = ['copies'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap gap-2 border-t border-gray-200 pt-5 dark:border-gray-800">
                    <button type="submit" name="disposition" value="inline" formtarget="_blank"
                            class="rounded-md bg-gray-900 px-4 py-2 text-xs font-medium text-white dark:bg-gray-100 dark:text-gray-900">
                        Preview PDF
                    </button>
                    <button type="submit" name="disposition" value="download"
                            class="rounded-md border border-gray-300 px-4 py-2 text-xs font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">
                        Download PDF
                    </button>
                </div>
            </div>

            <aside class="space-y-3">
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-xs font-semibold text-gray-900 dark:text-gray-100">Live preview</h2>
                        <span class="text-[10px] text-gray-400">4 × 3 inches</span>
                    </div>
                    <div class="overflow-auto rounded-md border border-gray-200 bg-gray-100 p-2 dark:border-gray-700 dark:bg-gray-900">
                        <div class="label-browser-preview">
                            <?php echo $__env->make('labels._canvas', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                    </div>
                </div>
                <p class="text-[10px] leading-4 text-gray-500 dark:text-gray-400">
                    Print at 100% / actual size. Disable “fit to page” in the printer dialog.
                </p>
            </aside>
        </form>
    </div>

    <script>
        (() => {
            const field = (name) => document.getElementById(name);
            const targets = (name) => document.querySelectorAll(`[data-label-field="${name}"]`);
            const write = (name, value) => targets(name).forEach((node) => node.textContent = value);

            const categorySize = (value) => value.length <= 5 ? 21.3 : value.length <= 8 ? 16 : value.length <= 12 ? 12 : 9;
            const productSize = (value) => value.length <= 20 ? 10 : value.length <= 28 ? 8.2 : value.length <= 36 ? 7 : 6;

            const refresh = () => {
                const category = field('category').value.trim();
                const country = field('country').value.trim().toUpperCase();
                const productName = field('product_name').value.trim().toUpperCase();
                const price = Number.parseFloat(field('price').value || '0').toFixed(2);
                const unit = field('unit_label').value.trim();
                const company = field('company_name').value.trim().toUpperCase();
                const website = field('website').value.trim();

                write('category', category);
                targets('category').forEach((node) => {
                    const size = categorySize(category);
                    node.style.fontSize = `${size}pt`;
                    node.style.top = `${-5.2 + ((21.3 - size) * 0.45)}pt`;
                });
                write('country', country);
                targets('country').forEach((node) => node.style.fontSize = `${country.length <= 18 ? 8 : 6.6}pt`);
                write('product_name', productName);
                targets('product_name').forEach((node) => node.style.fontSize = `${productSize(productName)}pt`);
                write('price', price);
                write('unit_label', unit);
                write('company_name', company);
                targets('company_name').forEach((node) => node.style.fontSize = `${company.length <= 16 ? 8 : 6.8}pt`);
                write('fssai', field('fssai').value.trim());
                write('website', website);
                targets('website').forEach((node) => node.style.fontSize = `${website.length <= 27 ? 6 : 5}pt`);

                const month = field('best_before').value;
                if (month) {
                    const [year, monthNumber] = month.split('-').map(Number);
                    const label = new Intl.DateTimeFormat('en', { month: 'long', year: 'numeric', timeZone: 'UTC' })
                        .format(new Date(Date.UTC(year, monthNumber - 1, 1)));
                    write('best_before_label', label);
                }
            };

            ['category', 'country', 'product_name', 'price', 'unit_label', 'company_name', 'fssai', 'website', 'best_before']
                .forEach((name) => field(name).addEventListener('input', refresh));

            refresh();
        })();
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/product-labels/edit.blade.php ENDPATH**/ ?>