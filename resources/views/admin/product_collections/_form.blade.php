@php
    use Illuminate\Support\Facades\Storage;

    $startsAt = old('starts_at', optional($productCollection->starts_at)->format('Y-m-d\TH:i'));
    $endsAt = old('ends_at', optional($productCollection->ends_at)->format('Y-m-d\TH:i'));

    $attachedProducts = $productCollection->relationLoaded('products')
        ? $productCollection->products->keyBy('id')
        : collect();

    $selectedProductIds = collect(old('product_ids', $attachedProducts->keys()->all()))
        ->map(fn ($id) => (int) $id)
        ->all();
@endphp

@if($errors->any())
    <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">
        <p class="font-medium">Please fix the following:</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid gap-6 xl:grid-cols-[1.05fr,0.95fr]">
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-50">Collection content</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Title, slug, image, CTA, and descriptive copy.
            </p>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Name</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name', $productCollection->name) }}"
                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                        placeholder="Weeknight wins"
                        required
                    >
                </div>

                <div>
                    <label for="slug" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Slug</label>
                    <input
                        id="slug"
                        name="slug"
                        type="text"
                        value="{{ old('slug', $productCollection->slug) }}"
                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                        placeholder="weeknight-wins"
                    >
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Leave blank to auto-generate from the name.
                    </p>
                </div>

                <div>
                    <label for="kind" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Kind</label>
                    <select
                        id="kind"
                        name="kind"
                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >
                        <option value="general" @selected(old('kind', $productCollection->kind) === 'general')>General</option>
                        <option value="occasion" @selected(old('kind', $productCollection->kind) === 'occasion')>Occasion</option>
                        <option value="chef" @selected(old('kind', $productCollection->kind) === 'chef')>Chef</option>
                        <option value="seasonal" @selected(old('kind', $productCollection->kind) === 'seasonal')>Seasonal</option>
                        <option value="campaign" @selected(old('kind', $productCollection->kind) === 'campaign')>Campaign</option>
                    </select>
                </div>

                <div>
                    <label for="eyebrow" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Eyebrow</label>
                    <input
                        id="eyebrow"
                        name="eyebrow"
                        type="text"
                        value="{{ old('eyebrow', $productCollection->eyebrow) }}"
                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                        placeholder="Quick meals"
                    >
                </div>

                <div>
                    <label for="cta_text" class="block text-sm font-medium text-gray-700 dark:text-gray-200">CTA text</label>
                    <input
                        id="cta_text"
                        name="cta_text"
                        type="text"
                        value="{{ old('cta_text', $productCollection->cta_text) }}"
                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                        placeholder="Shop now"
                    >
                </div>

                <div class="sm:col-span-2">
                    <label for="cta_url" class="block text-sm font-medium text-gray-700 dark:text-gray-200">CTA URL</label>
                    <input
                        id="cta_url"
                        name="cta_url"
                        type="text"
                        value="{{ old('cta_url', $productCollection->cta_url) }}"
                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                        placeholder="/collections/weeknight-wins"
                    >
                </div>

                <div class="sm:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Description</label>
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                        placeholder="Describe this collection and why it matters on the homepage."
                    >{{ old('description', $productCollection->description) }}</textarea>
                </div>

                <div class="sm:col-span-2">
                    <input type="hidden" name="remove_image" value="0">

                    <label for="image" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        Collection image
                    </label>

                    @if(!empty($productCollection->image_path))
                        <div class="mt-3 overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40">
                            <img
                                src="{{ Storage::disk('public')->url($productCollection->image_path) }}"
                                alt="Current collection image"
                                class="h-40 w-full object-cover"
                            >
                        </div>

                        <label class="mt-3 inline-flex items-start gap-3">
                            <input
                                type="checkbox"
                                name="remove_image"
                                value="1"
                                class="mt-1 h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-500 dark:border-gray-700"
                            >
                            <span>
                                <span class="block text-sm font-medium text-gray-700 dark:text-gray-200">Remove current image</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">Use no image for this collection.</span>
                            </span>
                        </label>
                    @endif

                    <input
                        id="image"
                        name="image"
                        type="file"
                        accept=".jpg,.jpeg,.png,.webp,.avif,image/jpeg,image/png,image/webp,image/avif"
                        class="mt-3 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 file:mr-3 file:rounded-lg file:border-0 file:bg-gray-900 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-gray-800 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:file:bg-gray-100 dark:file:text-gray-900 dark:hover:file:bg-white"
                    >

                    @error('image')
                        <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-50">Attach products</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Select the products for this collection. Sort order controls display order.
                    </p>
                </div>

                <div class="w-full max-w-xs">
                    <input
                        type="text"
                        placeholder="Search products..."
                        class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                        data-product-search
                    >
                </div>
            </div>

            <div class="mt-4 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                <div class="grid grid-cols-[minmax(0,1fr)_100px_120px] gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-800 dark:bg-gray-950/40 dark:text-gray-400">
                    <div>Product</div>
                    <div>Sort</div>
                    <div>Featured</div>
                </div>

                <div class="max-h-[520px] overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($products as $product)
                        @php
                            $attached = $attachedProducts->get($product->id);
                            $selected = in_array($product->id, $selectedProductIds, true);
                            $sortValue = old("product_sort_orders.{$product->id}", optional($attached?->pivot)->sort_order ?? 0);
                            $isFeatured = (bool) old("product_featured.{$product->id}", optional($attached?->pivot)->is_featured ?? false);
                            $searchText = strtolower(trim(($product->name ?? '') . ' ' . ($product->sku ?? '')));
                        @endphp

                        <div
                            class="grid grid-cols-[minmax(0,1fr)_100px_120px] gap-3 px-4 py-3 items-start"
                            data-product-row
                            data-search-text="{{ $searchText }}"
                        >
                            <div class="min-w-0">
                                <div class="flex items-start gap-3">
                                    <input
                                        type="checkbox"
                                        name="product_ids[]"
                                        value="{{ $product->id }}"
                                        @checked($selected)
                                        class="mt-1 h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-500 dark:border-gray-700"
                                        data-product-toggle
                                    >

                                    <div class="min-w-0">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-50 line-clamp-2">
                                            {{ $product->name }}
                                        </div>

                                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                            @if($product->sku)
                                                <span>SKU: {{ $product->sku }}</span>
                                            @endif

                                            <span class="inline-flex rounded-full px-2 py-0.5 {{ $product->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                                                {{ $product->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <input
                                    type="number"
                                    min="0"
                                    name="product_sort_orders[{{ $product->id }}]"
                                    value="{{ $sortValue }}"
                                    @disabled(!$selected)
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 disabled:opacity-50"
                                    data-product-sort
                                >
                            </div>

                            <div>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                    <input
                                        type="checkbox"
                                        name="product_featured[{{ $product->id }}]"
                                        value="1"
                                        @checked($isFeatured)
                                        @disabled(!$selected)
                                        class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-500 dark:border-gray-700 disabled:opacity-50"
                                        data-product-featured
                                    >
                                    Highlight
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                Tip: choose only the products that should be part of this collection. Lower sort order appears first.
            </p>
        </div>
    </div>

    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-50">Display options</h2>

            <div class="mt-5 space-y-4">
                <div>
                    <input type="hidden" name="is_active" value="0">
                    <label class="flex items-start gap-3">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            @checked(old('is_active', $productCollection->is_active))
                            class="mt-1 h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-500 dark:border-gray-700"
                        >
                        <span>
                            <span class="block text-sm font-medium text-gray-700 dark:text-gray-200">Active</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">Only active collections should appear publicly.</span>
                        </span>
                    </label>
                </div>

                <div>
                    <input type="hidden" name="show_on_home" value="0">
                    <label class="flex items-start gap-3">
                        <input
                            type="checkbox"
                            name="show_on_home"
                            value="1"
                            @checked(old('show_on_home', $productCollection->show_on_home))
                            class="mt-1 h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-500 dark:border-gray-700"
                        >
                        <span>
                            <span class="block text-sm font-medium text-gray-700 dark:text-gray-200">Show on home</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">Enable this collection for homepage rendering.</span>
                        </span>
                    </label>
                </div>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="home_section" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Home section</label>
                    <select
                        id="home_section"
                        name="home_section"
                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >
                        <option value="">Not assigned</option>
                        <option value="occasions" @selected(old('home_section', $productCollection->home_section) === 'occasions')>Occasions</option>
                        <option value="chef_picks" @selected(old('home_section', $productCollection->home_section) === 'chef_picks')>Chef picks</option>
                        <option value="seasonal" @selected(old('home_section', $productCollection->home_section) === 'seasonal')>Seasonal</option>
                        <option value="general" @selected(old('home_section', $productCollection->home_section) === 'general')>General</option>
                    </select>
                </div>

                <div>
                    <label for="home_order" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Home order</label>
                    <input
                        id="home_order"
                        name="home_order"
                        type="number"
                        min="0"
                        value="{{ old('home_order', $productCollection->home_order ?? 0) }}"
                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >
                </div>

                <div>
                    <label for="starts_at" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Starts at</label>
                    <input
                        id="starts_at"
                        name="starts_at"
                        type="datetime-local"
                        value="{{ $startsAt }}"
                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >
                </div>

                <div>
                    <label for="ends_at" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Ends at</label>
                    <input
                        id="ends_at"
                        name="ends_at"
                        type="datetime-local"
                        value="{{ $endsAt }}"
                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-50">Summary</h2>

            <div class="mt-4 space-y-3 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Selected products</span>
                    <span class="font-medium text-gray-900 dark:text-gray-50" data-selected-count>{{ count($selectedProductIds) }}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Selection mode</span>
                    <span class="font-medium text-gray-900 dark:text-gray-50">Manual</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Homepage</span>
                    <span class="font-medium text-gray-900 dark:text-gray-50">
                        {{ old('show_on_home', $productCollection->show_on_home) ? 'Enabled' : 'Disabled' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-xl bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white"
            >
                Save collection
            </button>

            <a
                href="{{ route('admin.product-collections.index') }}"
                class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
            >
                Cancel
            </a>
        </div>
    </div>
</div>

@once
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.querySelector('[data-product-search]');
    const rows = Array.from(document.querySelectorAll('[data-product-row]'));
    const toggles = Array.from(document.querySelectorAll('[data-product-toggle]'));
    const countNode = document.querySelector('[data-selected-count]');

    function updateCount() {
        if (!countNode) return;
        countNode.textContent = toggles.filter(input => input.checked).length;
    }

    function filterRows() {
        if (!searchInput) return;

        const term = searchInput.value.trim().toLowerCase();

        rows.forEach(function (row) {
            const haystack = (row.getAttribute('data-search-text') || '').toLowerCase();
            row.classList.toggle('hidden', term !== '' && !haystack.includes(term));
        });
    }

    function syncRowState(toggle) {
        const row = toggle.closest('[data-product-row]');
        if (!row) return;

        const sortInput = row.querySelector('[data-product-sort]');
        const featuredInput = row.querySelector('[data-product-featured]');

        if (sortInput) {
            sortInput.disabled = !toggle.checked;
        }

        if (featuredInput) {
            featuredInput.disabled = !toggle.checked;

            if (!toggle.checked) {
                featuredInput.checked = false;
            }
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterRows);
    }

    toggles.forEach(function (input) {
        syncRowState(input);

        input.addEventListener('change', function () {
            syncRowState(input);
            updateCount();
        });
    });

    updateCount();
});
</script>
@endonce