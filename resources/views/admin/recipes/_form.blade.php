@php
    $recipe = $recipe ?? null;
    $isEdit = $recipe && $recipe->exists;

    $products = $products ?? collect();
    $selectedProductIds = collect(old('product_ids', $selectedProductIds ?? []))->map(fn($id) => (int)$id)->all();

    $english = fn ($field) => $recipe && is_array($recipe->{$field} ?? null)
        ? ($recipe->{$field}['en'] ?? '')
        : '';

    $ingredientsText = old('ingredients_text', $recipe ? implode("\n", $recipe->trList('ingredients', 'en')) : '');
    $stepsText = old('steps_text', $recipe ? implode("\n", $recipe->trList('steps', 'en')) : '');

    $imagePreviewUrl = null;
    if ($recipe?->image_path) {
        $imagePreviewUrl = \Illuminate\Support\Str::startsWith($recipe->image_path, ['http://', 'https://'])
            ? $recipe->image_path
            : asset('storage/' . ltrim($recipe->image_path, '/'));
    }

    $videoPreviewUrl = null;
    if ($recipe?->video_url) {
        $videoPreviewUrl = \Illuminate\Support\Str::startsWith($recipe->video_url, ['http://', 'https://'])
            ? $recipe->video_url
            : asset('storage/' . ltrim($recipe->video_url, '/'));
    }
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-4">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    @if($errors->any())
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
            <div class="font-medium mb-1">Please fix the following:</div>
            <ul class="list-disc pl-4 space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded border border-sky-300 bg-sky-50 px-3 py-2 text-[11px] text-sky-800">
        Enter recipe content in English only. Other active languages are auto-generated when translation is configured.
    </div>

    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-4 text-xs">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Title</label>
                <input type="text" name="title" required
                       value="{{ old('title', $english('title')) }}"
                       class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2">
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Slug (optional)</label>
                <input type="text" name="slug"
                       value="{{ old('slug', $english('slug')) }}"
                       class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2"
                       placeholder="auto-generated from title if empty">
            </div>
        </div>

        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Short description</label>
            <input type="text" name="short_description"
                   value="{{ old('short_description', $english('short_description')) }}"
                   class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2">
        </div>

        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Description</label>
            <textarea name="description" rows="4"
                      class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2">{{ old('description', $english('description')) }}</textarea>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                    Ingredients (one per line)
                </label>
                <textarea name="ingredients_text" rows="8"
                          class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2"
                          placeholder="500g pork belly&#10;1 tsp salt&#10;1 tsp pepper">{{ $ingredientsText }}</textarea>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                    Steps (one per line)
                </label>
                <textarea name="steps_text" rows="8"
                          class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2"
                          placeholder="Score the skin&#10;Season well&#10;Roast for 45 minutes">{{ $stepsText }}</textarea>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Prep time (mins)</label>
                <input type="number" min="0" name="prep_time_minutes"
                       value="{{ old('prep_time_minutes', $recipe->prep_time_minutes ?? '') }}"
                       class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2">
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Cook time (mins)</label>
                <input type="number" min="0" name="cook_time_minutes"
                       value="{{ old('cook_time_minutes', $recipe->cook_time_minutes ?? '') }}"
                       class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2">
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Servings</label>
                <input type="number" min="1" name="servings"
                       value="{{ old('servings', $recipe->servings ?? '') }}"
                       class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2">
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Sort order</label>
                <input type="number" min="0" name="sort_order"
                       value="{{ old('sort_order', $recipe->sort_order ?? 0) }}"
                       class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2">
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Recipe image (optional)</label>
                <input type="file" name="image" accept="image/*"
                       class="mt-1 block w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2">

                @if($imagePreviewUrl)
                    <div class="mt-2">
                        <div class="text-[11px] text-gray-500 dark:text-gray-400">Current image</div>
                        <img src="{{ $imagePreviewUrl }}"
                             alt="Recipe image"
                             class="mt-1 h-28 w-auto rounded border border-gray-200 dark:border-gray-800 object-cover">
                    </div>
                @endif
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Recipe video (optional)</label>
                <input type="file" name="video" accept="video/mp4,video/webm,video/quicktime"
                       class="mt-1 block w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2">

                @if($videoPreviewUrl)
                    <div class="mt-2">
                        <div class="text-[11px] text-gray-500 dark:text-gray-400">Current video</div>
                        <video controls class="mt-1 max-h-44 w-full rounded border border-gray-200 dark:border-gray-800 bg-black">
                            <source src="{{ $videoPreviewUrl }}">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                @endif
            </div>
        </div>

        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-2">
                Linked products
            </label>

            <div class="max-h-56 overflow-y-auto rounded border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 p-3 space-y-2">
                @forelse($products as $product)
                    <label class="flex items-start gap-2">
                        <input type="checkbox" name="product_ids[]"
                               value="{{ $product->id }}"
                               class="mt-0.5 rounded border-gray-300 dark:border-gray-700"
                               @checked(in_array((int)$product->id, $selectedProductIds, true))>
                        <span class="text-[12px] text-gray-800 dark:text-gray-200">
                            {{ $product->name }}
                        </span>
                    </label>
                @empty
                    <div class="text-[11px] text-gray-500 dark:text-gray-400">
                        No products found.
                    </div>
                @endforelse
            </div>
        </div>

        <div>
            <label class="inline-flex items-center gap-2 text-[11px] text-gray-700 dark:text-gray-300">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       @checked(old('is_active', $recipe->is_active ?? true))>
                <span>Active</span>
            </label>
        </div>
    </div>

    <div class="flex items-center justify-between">
        <a href="{{ route('admin.recipes.index') }}"
           class="text-[11px] text-gray-500 dark:text-gray-400 hover:underline">
            Cancel
        </a>

        <button type="submit"
                class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
            {{ $isEdit ? 'Update recipe' : 'Create recipe' }}
        </button>
    </div>
</form>