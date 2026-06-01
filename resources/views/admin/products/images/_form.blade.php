@php
    /** @var \App\Models\ProductImage|null $image */
    $isEdit = isset($image);
    $maxUploadMb = 10;
    $maxUploadBytes = 10240 * 10240; // 10 MB in bytes
@endphp

<form
    method="POST"
    action="{{ $action }}"
    enctype="multipart/form-data"
    data-image-upload-form
    data-max-bytes="{{ $maxUploadBytes }}"
>
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="space-y-5">
        @if(session('status'))
            <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if(!$isEdit)
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Image file
                </label>

                {{-- <input type="hidden" name="MAX_FILE_SIZE" value="{{ $maxUploadBytes }}"> --}}

                <input
                    type="file"
                    name="image"
                    accept="image/*"
                    required
                    data-image-upload-input
                    class="mt-3 w-full rounded-sm border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 file:mr-3 file:rounded-sm file:border-0 file:bg-gray-900 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-gray-800 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:file:bg-gray-100 dark:file:text-gray-900 dark:hover:file:bg-white"
                >

                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                    Max file size: {{ $maxUploadMb }} MB.
                </p>

                <p
                    data-image-upload-error
                    class="mt-1 hidden text-[11px] text-red-600"
                ></p>

                @error('image')
                    <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @else
            <div class="flex gap-4">
                <div class="w-32 h-32 border border-gray-200 dark:border-gray-700 rounded overflow-hidden bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                    @if($image->file_path)
                        <img
                            src="{{ Storage::url($image->file_path) }}"
                            alt="{{ $image->alt_text }}"
                            class="object-contain max-h-full max-w-full"
                        >
                    @else
                        <span class="text-[11px] text-gray-500 dark:text-gray-400">
                            No preview
                        </span>
                    @endif
                </div>

                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Replace image (optional)
                    </label>

                    {{-- <input type="hidden" name="MAX_FILE_SIZE" value="{{ $maxUploadBytes }}"> --}}

                    <input
                        type="file"
                        name="image"
                        accept="image/*"
                        data-image-upload-input
                        class="mt-1 block w-full text-xs text-gray-700 dark:text-gray-300"
                    >

                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        Max file size: {{ $maxUploadMb }} MB.
                    </p>

                    <p
                        data-image-upload-error
                        class="mt-1 hidden text-[11px] text-red-600"
                    ></p>

                    @error('image')
                        <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        @endif

        <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                Alt text (for accessibility/SEO)
            </label>
            <input
                type="text"
                name="alt_text"
                value="{{ old('alt_text', $image->alt_text ?? '') }}"
                class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
            >
            @error('alt_text')
                <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2 text-xs">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Position (sort order)
                </label>
                <input
                    type="number"
                    name="position"
                    value="{{ old('position', $image->position ?? 0) }}"
                    class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                @error('position')
                    <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center mt-6">
                <label class="inline-flex items-center gap-2">
                    <input
                        type="checkbox"
                        name="is_primary"
                        value="1"
                        @checked(old('is_primary', $image->is_primary ?? false))
                    >
                    <span>Set as primary image</span>
                </label>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-xs font-medium hover:bg-gray-800 dark:hover:bg-gray-200"
            >
                {{ $isEdit ? 'Update image' : 'Upload image' }}
            </button>

            <a href="{{ route('admin.products.images.index', $product) }}"
               class="text-xs text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">
                Cancel
            </a>
        </div>
    </div>
</form>

@once
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form[data-image-upload-form]').forEach(function (form) {
        const input = form.querySelector('[data-image-upload-input]');
        const errorEl = form.querySelector('[data-image-upload-error]');
        const maxBytes = Number(form.dataset.maxBytes || 0);

        if (!input || !errorEl) {
            return;
        }

        function showError(message) {
            errorEl.textContent = message || '';
            errorEl.classList.toggle('hidden', !message);
        }

        function validateFile() {
            const file = input.files && input.files[0] ? input.files[0] : null;

            if (!file) {
                showError('');
                return true;
            }

            if (!file.type || !file.type.startsWith('image/')) {
                showError('Please choose a valid image file.');
                return false;
            }

            if (maxBytes && file.size > maxBytes) {
                const maxMb = Math.round(maxBytes / 1024 / 1024);
                showError(`Please choose an image smaller than ${maxMb} MB.`);
                return false;
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
@endonce