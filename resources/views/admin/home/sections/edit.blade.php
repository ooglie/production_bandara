@extends('layouts.company')

@section('title', 'Edit Homepage Section')

@section('content')
@php
    use Illuminate\Support\Str;

    $settingsJson = old('settings_json', $homeSection->settings ? json_encode($homeSection->settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '');
    $previewUrl = route('home') . '#home-section-' . $homeSection->key;
    $mediaUrl = function (?string $path) {
        if (! filled($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        if (Str::startsWith($path, ['home/'])) {
            return asset('storage/' . ltrim($path, '/'));
        }

        return asset(ltrim($path, '/'));
    };
    $linkedResourceValue = function ($item) use ($linkableClassAliases) {
        if (! $item || ! $item->linked_type || ! $item->linked_id) {
            return '';
        }

        $alias = $linkableClassAliases[$item->linked_type] ?? null;

        return $alias ? $alias . ':' . $item->linked_id : '';
    };
    $linkedLabel = function ($item) use ($linkableClassAliases, $linkableTypeLabels) {
        if (! $item || ! $item->linked_type || ! $item->linked_id) {
            return null;
        }

        $alias = $linkableClassAliases[$item->linked_type] ?? null;
        $typeLabel = $alias ? ($linkableTypeLabels[$alias] ?? class_basename($item->linked_type)) : class_basename($item->linked_type);
        $linked = $item->linked;
        $name = $linked?->name
            ?? ($linked && method_exists($linked, 'tr') ? $linked->tr('title') : null)
            ?? $linked?->title
            ?? ('#' . $item->linked_id);

        return $typeLabel . ': ' . $name;
    };
@endphp

<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <a href="{{ route('admin.home-sections.index') }}" class="text-xs text-gray-500 hover:underline dark:text-gray-400">← Homepage sections</a>
            <h1 class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50">{{ $homeSection->title ?: $homeSection->key }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Edit section copy, images, scheduling, linked products/categories/collections, and repeatable cards.</p>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Empty fields use storefront fallback copy, but that fallback is not stored in the database or shown as editable admin content.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ $previewUrl }}" target="_blank" class="inline-flex items-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Preview section</a>
            <form method="POST" action="{{ route('admin.home-sections.toggle', $homeSection) }}">
                @csrf
                <button class="inline-flex items-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                    {{ $homeSection->is_active ? 'Disable section' : 'Enable section' }}
                </button>
            </form>
        </div>
    </div>

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">
            <p class="font-medium">Please fix the following:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-300">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.home-sections.update', $homeSection) }}" enctype="multipart/form-data" class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 space-y-5">
        @csrf
        @method('PUT')
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Key</label>
                <input name="key" value="{{ old('key', $homeSection->key) }}" required class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Type</label>
                <input name="type" value="{{ old('type', $homeSection->type) }}" required class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Eyebrow</label>
                <input name="eyebrow" value="{{ old('eyebrow', $homeSection->eyebrow) }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Title</label>
                <input name="title" value="{{ old('title', $homeSection->title) }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Subtitle</label>
                <textarea name="subtitle" rows="2" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">{{ old('subtitle', $homeSection->subtitle) }}</textarea>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Body</label>
                <textarea name="body" rows="4" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">{{ old('body', $homeSection->body) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">CTA text</label>
                <input name="cta_text" value="{{ old('cta_text', $homeSection->cta_text) }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">CTA URL</label>
                <input name="cta_url" value="{{ old('cta_url', $homeSection->cta_url) }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Secondary CTA text</label>
                <input name="secondary_cta_text" value="{{ old('secondary_cta_text', $homeSection->secondary_cta_text) }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Secondary CTA URL</label>
                <input name="secondary_cta_url" value="{{ old('secondary_cta_url', $homeSection->secondary_cta_url) }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Image path</label>
                <input name="image_path" value="{{ old('image_path', $homeSection->image_path) }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
                <input type="file" name="image_upload" class="mt-2 block w-full text-sm text-gray-600 dark:text-gray-300">
                @if($homeSection->image_path)
                    <div class="mt-2 flex items-center gap-3">
                        @if($mediaUrl($homeSection->image_path))<img src="{{ $mediaUrl($homeSection->image_path) }}" alt="Current section image" class="h-16 w-24 rounded-lg object-cover border border-gray-200 dark:border-gray-800">@endif
                        <label class="inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300"><input type="checkbox" name="remove_image" value="1"> Remove image</label>
                    </div>
                @endif
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Mobile image path</label>
                <input name="mobile_image_path" value="{{ old('mobile_image_path', $homeSection->mobile_image_path) }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
                <input type="file" name="mobile_image_upload" class="mt-2 block w-full text-sm text-gray-600 dark:text-gray-300">
                @if($homeSection->mobile_image_path)
                    <div class="mt-2 flex items-center gap-3">
                        @if($mediaUrl($homeSection->mobile_image_path))<img src="{{ $mediaUrl($homeSection->mobile_image_path) }}" alt="Current mobile image" class="h-16 w-24 rounded-lg object-cover border border-gray-200 dark:border-gray-800">@endif
                        <label class="inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300"><input type="checkbox" name="remove_mobile_image" value="1"> Remove mobile image</label>
                    </div>
                @endif
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Layout</label>
                <input name="layout" value="{{ old('layout', $homeSection->layout) }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Sort order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $homeSection->sort_order) }}" required class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Starts at</label>
                <input type="datetime-local" name="starts_at" value="{{ old('starts_at', optional($homeSection->starts_at)->format('Y-m-d\TH:i')) }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Ends at</label>
                <input type="datetime-local" name="ends_at" value="{{ old('ends_at', optional($homeSection->ends_at)->format('Y-m-d\TH:i')) }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Settings JSON</label>
                <textarea name="settings_json" rows="7" class="mt-1 font-mono w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">{{ $settingsJson }}</textarea>
                <div class="mt-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-[11px] text-gray-500 dark:border-gray-800 dark:bg-gray-950/40 dark:text-gray-300">
                    <p class="font-medium text-gray-800 dark:text-gray-100">Useful product section settings</p>
                    <p class="mt-1">Automatic: <code>{"limit":8,"mode":"featured_new_special"}</code></p>
                    <p>Manual linked products: <code>{"limit":8,"mode":"manual_items"}</code></p>
                    <p>Collection products: <code>{"limit":8,"mode":"collection","collection_id":1}</code></p>
                    <p>Category products: <code>{"limit":8,"mode":"category","category_id":1}</code></p>
                </div>
            </div>
            <label class="sm:col-span-2 inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $homeSection->is_active))>
                Active on homepage
            </label>
        </div>
        <button class="inline-flex rounded-xl bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900">Save section</button>
    </form>

    <div class="grid gap-6 lg:grid-cols-[1.2fr,0.8fr]">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-50">Section items</h2>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Items can be plain content cards or linked records. Product/category/collection links drive manual homepage sections.</p>
            <div class="mt-4 space-y-4">
                @foreach($homeSection->items as $item)
                    @php
                        $itemSettings = old('settings_json', $item->settings ? json_encode($item->settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '');
                        $currentLinkedResource = old('linked_resource', $linkedResourceValue($item));
                    @endphp
                    <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800 space-y-3">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">#{{ $item->sort_order }}</span>
                                @if($item->isCurrentlyVisible())
                                    <span class="rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-medium text-sky-700 dark:bg-sky-900/30 dark:text-sky-300">Visible</span>
                                @elseif($item->is_active)
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">Scheduled / outside window</span>
                                @else
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">Inactive</span>
                                @endif
                                @if($linkedLabel($item))
                                    <span class="text-[11px] text-gray-500 dark:text-gray-400">{{ $linkedLabel($item) }}</span>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                <form method="POST" action="{{ route('admin.home-sections.items.move-up', [$homeSection, $item]) }}">@csrf<button class="rounded-lg border border-gray-300 px-2 py-1 text-xs text-gray-700 dark:border-gray-700 dark:text-gray-200">↑</button></form>
                                <form method="POST" action="{{ route('admin.home-sections.items.move-down', [$homeSection, $item]) }}">@csrf<button class="rounded-lg border border-gray-300 px-2 py-1 text-xs text-gray-700 dark:border-gray-700 dark:text-gray-200">↓</button></form>
                                <form method="POST" action="{{ route('admin.home-sections.items.toggle', [$homeSection, $item]) }}">@csrf<button class="rounded-lg border border-gray-300 px-2 py-1 text-xs text-gray-700 dark:border-gray-700 dark:text-gray-200">{{ $item->is_active ? 'Disable' : 'Enable' }}</button></form>
                                <form method="POST" action="{{ route('admin.home-sections.items.duplicate', [$homeSection, $item]) }}">@csrf<button class="rounded-lg border border-gray-300 px-2 py-1 text-xs text-gray-700 dark:border-gray-700 dark:text-gray-200">Duplicate</button></form>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.home-sections.items.update', [$homeSection, $item]) }}" enctype="multipart/form-data" class="space-y-3">
                            @csrf @method('PUT')
                            <div class="grid gap-3 sm:grid-cols-2">
                                <input name="item_type" value="{{ old('item_type', $item->item_type) }}" placeholder="Item type" class="rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
                                <input type="number" name="sort_order" value="{{ old('sort_order', $item->sort_order) }}" placeholder="Sort" class="rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
                                <input name="eyebrow" value="{{ old('eyebrow', $item->eyebrow) }}" placeholder="Eyebrow" class="rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
                                <input name="icon" value="{{ old('icon', $item->icon) }}" placeholder="Icon/emoji" class="rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
                                <input name="title" value="{{ old('title', $item->title) }}" placeholder="Title" class="sm:col-span-2 rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
                                <textarea name="description" rows="2" placeholder="Description" class="sm:col-span-2 rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">{{ old('description', $item->description) }}</textarea>
                                <input name="cta_text" value="{{ old('cta_text', $item->cta_text) }}" placeholder="CTA text" class="rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
                                <input name="cta_url" value="{{ old('cta_url', $item->cta_url) }}" placeholder="CTA URL" class="rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
                                <div class="sm:col-span-2">
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Linked record</label>
                                    <select name="linked_resource" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
                                        <option value="">No linked record</option>
                                        @foreach($linkableOptions as $alias => $options)
                                            <optgroup label="{{ $linkableTypeLabels[$alias] ?? ucfirst($alias) }}">
                                                @foreach($options as $option)
                                                    @php($value = $alias . ':' . $option['id'])
                                                    <option value="{{ $value }}" @selected($currentLinkedResource === $value)>{{ $option['label'] }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>
                                <input name="image_path" value="{{ old('image_path', $item->image_path) }}" placeholder="Image path" class="sm:col-span-2 rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
                                @if($item->image_path && $mediaUrl($item->image_path))
                                    <div class="sm:col-span-2 flex items-center gap-3">
                                        <img src="{{ $mediaUrl($item->image_path) }}" alt="Current item image" class="h-16 w-24 rounded-lg object-cover border border-gray-200 dark:border-gray-800">
                                        <label class="inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300"><input type="checkbox" name="remove_item_image" value="1"> Remove image</label>
                                    </div>
                                @endif
                                <input type="file" name="item_image_upload" class="sm:col-span-2 block w-full text-sm text-gray-600 dark:text-gray-300">
                                <textarea name="settings_json" rows="4" placeholder="Settings JSON" class="sm:col-span-2 font-mono rounded-xl border border-gray-300 px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">{{ $itemSettings }}</textarea>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active))> Active</label>
                            </div>
                            <button class="rounded-xl bg-gray-900 px-3 py-2 text-xs font-medium text-white dark:bg-gray-100 dark:text-gray-900">Save item</button>
                        </form>
                        <form method="POST" action="{{ route('admin.home-sections.items.destroy', [$homeSection, $item]) }}" onsubmit="return confirm('Delete this item?')">
                            @csrf @method('DELETE')
                            <button class="text-xs text-red-600 hover:underline">Delete item</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>

        <form method="POST" action="{{ route('admin.home-sections.items.store', $homeSection) }}" enctype="multipart/form-data" class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 space-y-3">
            @csrf
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-50">Add item</h2>
            <input name="item_type" value="card" placeholder="Item type" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
            <input type="number" name="sort_order" value="{{ ($homeSection->items->max('sort_order') ?? 0) + 10 }}" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
            <select name="linked_resource" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
                <option value="">No linked record</option>
                @foreach($linkableOptions as $alias => $options)
                    <optgroup label="{{ $linkableTypeLabels[$alias] ?? ucfirst($alias) }}">
                        @foreach($options as $option)
                            <option value="{{ $alias }}:{{ $option['id'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            <input name="eyebrow" placeholder="Eyebrow" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
            <input name="icon" placeholder="Icon/emoji" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
            <input name="title" placeholder="Title" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
            <textarea name="description" rows="3" placeholder="Description" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"></textarea>
            <input name="cta_text" placeholder="CTA text" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
            <input name="cta_url" placeholder="CTA URL" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
            <input name="image_path" placeholder="Image path" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
            <input type="file" name="item_image_upload" class="block w-full text-sm text-gray-600 dark:text-gray-300">
            <textarea name="settings_json" rows="4" placeholder='{"accent":"bg-sky-50"}' class="font-mono w-full rounded-xl border border-gray-300 px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"></textarea>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200"><input type="checkbox" name="is_active" value="1" checked> Active</label>
            <button class="block w-full rounded-xl bg-gray-900 px-3 py-2 text-sm font-medium text-white dark:bg-gray-100 dark:text-gray-900">Add item</button>
        </form>
    </div>
</div>
@endsection
