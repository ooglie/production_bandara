@php
    /** @var \App\Models\Category|null $category */
    $isEdit = isset($category);
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data">
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

        @if(session('error'))
            <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Name
                </label>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name', $category->name ?? '') }}"
                    required
                    class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                @error('name')
                    <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Slug (optional)
                </label>
                <input
                    type="text"
                    name="slug"
                    value="{{ old('slug', $category->slug ?? '') }}"
                    placeholder="auto-generated if empty"
                    class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                @error('slug')
                    <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Parent category
                </label>
                <select
                    name="parent_id"
                    class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                    <option value="">— None (root) —</option>
                    @foreach($parents as $id => $name)
                        <option value="{{ $id }}"
                            @selected((int) old('parent_id', $category->parent_id ?? 0) === (int) $id)
                        >
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
                @error('parent_id')
                    <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Position (sort order)
                </label>
                <input
                    type="number"
                    name="position"
                    value="{{ old('position', $category->position ?? 0) }}"
                    class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                @error('position')
                    <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                Description
            </label>
            <textarea
                name="description"
                rows="3"
                class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
            >{{ old('description', $category->description ?? '') }}</textarea>
            @error('description')
                <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-900/40 p-4 space-y-4">
            <div>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Category image</h2>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                    Upload a manual category image, or generate a collage from product images after saving the category.
                    Manual image is shown first; generated collage is used as fallback.
                </p>
            </div>

            @php
                $manualImageUrl = $isEdit && filled($category?->image_path) ? Storage::disk('public')->url($category->image_path) : null;
                $collageImageUrl = $isEdit && filled($category?->collage_image_path) ? Storage::disk('public')->url($category->collage_image_path) : null;
            @endphp

            @if($manualImageUrl || $collageImageUrl)
                <div class="grid gap-3 sm:grid-cols-2">
                    @if($manualImageUrl)
                        <div class="rounded border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 p-3">
                            <div class="text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-2">Manual image</div>
                            <img src="{{ $manualImageUrl }}" alt="{{ $category->name ?? 'Category image' }}" class="w-full aspect-[3/2] object-cover rounded">
                            <label class="mt-3 inline-flex items-center gap-2 text-[11px] text-red-600">
                                <input type="checkbox" name="remove_category_image" value="1" @checked(old('remove_category_image'))>
                                <span>Remove manual image on save</span>
                            </label>
                        </div>
                    @endif

                    @if($collageImageUrl)
                        <div class="rounded border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 p-3">
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <div class="text-[11px] font-medium text-gray-700 dark:text-gray-300">Generated collage</div>
                                @if($category?->collage_updated_at)
                                    <div class="text-[10px] text-gray-400">{{ $category->collage_updated_at->format('d M Y, H:i') }}</div>
                                @endif
                            </div>
                            <img src="{{ $collageImageUrl }}" alt="Generated collage for {{ $category->name ?? 'category' }}" class="w-full aspect-[3/2] object-cover rounded">
                        </div>
                    @endif
                </div>
            @endif

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Upload manual category image
                </label>
                <input
                    type="file"
                    name="category_image"
                    accept="image/*"
                    class="mt-1 block w-full text-xs text-gray-600 dark:text-gray-300 file:mr-3 file:rounded file:border-0 file:bg-gray-900 file:px-3 file:py-1.5 file:text-xs file:text-white hover:file:bg-gray-800 dark:file:bg-gray-100 dark:file:text-gray-900"
                >
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Optional. Max 10 MB. If uploaded, this overrides the generated collage on the storefront.</p>
                @error('category_image')
                    <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label class="inline-flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    @checked(old('is_active', $category->is_active ?? true))
                >
                <span>Active</span>
            </label>
        </div>

        <div class="flex items-center gap-3">
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-xs font-medium hover:bg-gray-800 dark:hover:bg-gray-200"
            >
                {{ $isEdit ? 'Update category' : 'Create category' }}
            </button>

            <a href="{{ route('admin.categories.index') }}"
               class="text-xs text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">
                Cancel
            </a>
        </div>
    </div>
</form>
