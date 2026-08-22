<?php
    /** @var \App\Models\ProductImage|null $image */
    $isEdit = isset($image);
    $maxUploadMb = 10;
    $maxUploadBytes = 10240 * 10240; // 10 MB in bytes
    $maxFiles = 25;
?>

<form
    method="POST"
    action="<?php echo e($action); ?>"
    enctype="multipart/form-data"
    data-image-upload-form
    data-max-bytes="<?php echo e($maxUploadBytes); ?>"
    data-max-files="<?php echo e($maxFiles); ?>"
>
    <?php echo csrf_field(); ?>
    <?php if($isEdit): ?>
        <?php echo method_field('PUT'); ?>
    <?php endif; ?>

    <div class="space-y-5">
        <?php if(session('status')): ?>
            <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
                <?php echo e(session('status')); ?>

            </div>
        <?php endif; ?>

        <?php if(!$isEdit): ?>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Image files
                </label>

                

                <input
                    type="file"
                    name="images[]"
                    accept="image/*"
                    multiple
                    required
                    data-image-upload-input
                    class="mt-3 w-full rounded-sm border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 file:mr-3 file:rounded-sm file:border-0 file:bg-gray-900 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-gray-800 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:file:bg-gray-100 dark:file:text-gray-900 dark:hover:file:bg-white"
                >

                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                    Select up to <?php echo e($maxFiles); ?> product images. Max file size: <?php echo e($maxUploadMb); ?> MB per image. You can edit alt text, order and primary image after upload.
                </p>

                <p
                    data-image-upload-summary
                    class="mt-1 hidden text-[11px] text-gray-500 dark:text-gray-400"
                ></p>

                <p
                    data-image-upload-error
                    class="mt-1 hidden text-[11px] text-red-600"
                ></p>

                <?php $__errorArgs = ['images'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                <?php $__errorArgs = ['images.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                
                <?php $__errorArgs = ['image'];
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
        <?php else: ?>
            <div class="flex gap-4">
                <div class="w-32 h-32 border border-gray-200 dark:border-gray-700 rounded overflow-hidden bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                    <?php if($image->file_path): ?>
                        <img
                            src="<?php echo e(Storage::disk(config('media.public_disk', 'public'))->url($image->file_path)); ?>"
                            alt="<?php echo e($image->alt_text); ?>"
                            class="object-contain max-h-full max-w-full"
                        >
                    <?php else: ?>
                        <span class="text-[11px] text-gray-500 dark:text-gray-400">
                            No preview
                        </span>
                    <?php endif; ?>
                </div>

                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Replace image (optional)
                    </label>

                    

                    <input
                        type="file"
                        name="image"
                        accept="image/*"
                        data-image-upload-input
                        class="mt-1 block w-full text-xs text-gray-700 dark:text-gray-300"
                    >

                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        Max file size: <?php echo e($maxUploadMb); ?> MB.
                    </p>

                    <p
                        data-image-upload-error
                        class="mt-1 hidden text-[11px] text-red-600"
                    ></p>

                    <?php $__errorArgs = ['image'];
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
        <?php endif; ?>

        <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                <?php echo e($isEdit ? 'Alt text (for accessibility/SEO)' : 'Default alt text (optional)'); ?>

            </label>
            <input
                type="text"
                name="alt_text"
                value="<?php echo e(old('alt_text', $image->alt_text ?? '')); ?>"
                class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                placeholder="<?php echo e($isEdit ? 'Describe this image' : 'Applied to all uploaded images; can be changed later'); ?>"
            >
            <?php if(!$isEdit): ?>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                    Leave blank if you prefer to add specific alt text after reviewing each image.
                </p>
            <?php endif; ?>
            <?php $__errorArgs = ['alt_text'];
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

        <div class="grid gap-4 sm:grid-cols-2 text-xs">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    <?php echo e($isEdit ? 'Position (sort order)' : 'Starting position'); ?>

                </label>
                <input
                    type="number"
                    name="position"
                    value="<?php echo e(old('position', $image->position ?? 0)); ?>"
                    class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                <?php $__errorArgs = ['position'];
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

            <div class="flex items-center mt-6">
                <label class="inline-flex items-center gap-2">
                    <input
                        type="checkbox"
                        name="is_primary"
                        value="1"
                        <?php if(old('is_primary', $image->is_primary ?? false)): echo 'checked'; endif; ?>
                    >
                    <span><?php echo e($isEdit ? 'Set as primary image' : 'Set first uploaded image as primary'); ?></span>
                </label>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-xs font-medium hover:bg-gray-800 dark:hover:bg-gray-200"
            >
                <?php echo e($isEdit ? 'Update image' : 'Upload images'); ?>

            </button>

            <a href="<?php echo e(route('admin.products.images.index', $product)); ?>"
               class="text-xs text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">
                Cancel
            </a>
        </div>
    </div>
</form>

<?php if (! $__env->hasRenderedOnce('441615b1-f807-41f8-b113-7030b54c6e8c')): $__env->markAsRenderedOnce('441615b1-f807-41f8-b113-7030b54c6e8c'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form[data-image-upload-form]').forEach(function (form) {
        const input = form.querySelector('[data-image-upload-input]');
        const errorEl = form.querySelector('[data-image-upload-error]');
        const maxBytes = Number(form.dataset.maxBytes || 0);
        const maxFiles = Number(form.dataset.maxFiles || 0);

        if (!input || !errorEl) {
            return;
        }

        function showError(message) {
            errorEl.textContent = message || '';
            errorEl.classList.toggle('hidden', !message);
        }

        function updateSummary(files) {
            const summaryEl = form.querySelector('[data-image-upload-summary]');

            if (!summaryEl) {
                return;
            }

            if (!files.length) {
                summaryEl.textContent = '';
                summaryEl.classList.add('hidden');
                return;
            }

            const totalBytes = files.reduce(function (sum, file) {
                return sum + (file.size || 0);
            }, 0);

            const totalMb = totalBytes / 1024 / 1024;
            summaryEl.textContent = `${files.length} image${files.length === 1 ? '' : 's'} selected · ${totalMb.toFixed(1)} MB total`;
            summaryEl.classList.remove('hidden');
        }

        function validateFile() {
            const files = Array.from(input.files || []);

            updateSummary(files);

            if (!files.length) {
                showError('');
                return true;
            }

            if (maxFiles && files.length > maxFiles) {
                showError(`Please choose no more than ${maxFiles} images at a time.`);
                return false;
            }

            const invalidFile = files.find(function (file) {
                return !file.type || !file.type.startsWith('image/');
            });

            if (invalidFile) {
                showError(`Please choose image files only. "${invalidFile.name}" is not an image.`);
                return false;
            }

            if (maxBytes) {
                const oversizedFile = files.find(function (file) {
                    return file.size > maxBytes;
                });

                if (oversizedFile) {
                    const maxMb = Math.round(maxBytes / 1024 / 1024);
                    showError(`"${oversizedFile.name}" is larger than ${maxMb} MB.`);
                    return false;
                }
            }

            showError('');
            return true;
        }

        input.addEventListener('change', validateFile);

        form.addEventListener('submit', function (event) {
            if (!validateFile()) {
                event.preventDefault();
                input.focus();
            }
        });
    });
});
</script>
<?php endif; ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/products/images/_form.blade.php ENDPATH**/ ?>