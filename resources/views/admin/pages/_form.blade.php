@php
    $page = $page ?? null;
    $isEdit = $page && $page->exists;

    $english = fn ($field) => $page && is_array($page->{$field} ?? null)
        ? ($page->{$field}['en'] ?? '')
        : '';
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4">
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
        Enter page content in English only. Other active languages are auto-generated when translation is configured.
    </div>

    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-4 text-xs">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Key</label>
                <input type="text" name="key" required
                       value="{{ old('key', $page->key ?? '') }}"
                       placeholder="about / terms / privacy"
                       class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2">
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Title</label>
                <input type="text" name="title" required
                       value="{{ old('title', $english('title')) }}"
                       class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2">
            </div>
        </div>

        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Slug (optional)</label>
            <input type="text" name="slug"
                   value="{{ old('slug', $english('slug')) }}"
                   placeholder="auto-generated from title if empty"
                   class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2">
        </div>

        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Excerpt</label>
            <textarea name="excerpt" rows="3"
                      class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2">{{ old('excerpt', $english('excerpt')) }}</textarea>
        </div>

        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Content</label>
            <textarea name="content" rows="10"
                      class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2">{{ old('content', $english('content')) }}</textarea>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Meta title</label>
                <input type="text" name="meta_title"
                       value="{{ old('meta_title', $english('meta_title')) }}"
                       class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2">
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Meta description</label>
                <textarea name="meta_description" rows="3"
                          class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2">{{ old('meta_description', $english('meta_description')) }}</textarea>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Sort order</label>
                <input type="number" min="0" name="sort_order"
                       value="{{ old('sort_order', $page->sort_order ?? 0) }}"
                       class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2">
            </div>

            <div class="flex items-end">
                <label class="inline-flex items-center gap-2 text-[11px] text-gray-700 dark:text-gray-300">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           @checked(old('is_active', $page->is_active ?? true))>
                    <span>Active</span>
                </label>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between">
        <a href="{{ route('admin.pages.index') }}"
           class="text-[11px] text-gray-500 dark:text-gray-400 hover:underline">
            Cancel
        </a>

        <button type="submit"
                class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
            {{ $isEdit ? 'Update page' : 'Create page' }}
        </button>
    </div>
</form>