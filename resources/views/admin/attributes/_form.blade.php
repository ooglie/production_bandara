@php
    /** @var \App\Models\Attribute|null $attribute */
    $isEdit = isset($attribute);
@endphp

<form method="POST" action="{{ $action }}">
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

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Name
                </label>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name', $attribute->name ?? '') }}"
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
                    value="{{ old('slug', $attribute->slug ?? '') }}"
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
                    Display name (optional)
                </label>
                <input
                    type="text"
                    name="display_name"
                    value="{{ old('display_name', $attribute->display_name ?? '') }}"
                    class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                @error('display_name')
                    <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Frontend type
                </label>
                <select
                    name="frontend_type"
                    class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                    @php
                        $currentType = old('frontend_type', $attribute->frontend_type ?? 'select');
                    @endphp
                    <option value="select" @selected($currentType === 'select')>Select</option>
                    <option value="radio"  @selected($currentType === 'radio')>Radio buttons</option>
                    <option value="label"  @selected($currentType === 'label')>Label tags</option>
                    <option value="text"   @selected($currentType === 'text')>Free text</option>
                </select>
                @error('frontend_type')
                    <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label class="inline-flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300">
                <input
                    type="checkbox"
                    name="is_filterable"
                    value="1"
                    @checked(old('is_filterable', $attribute->is_filterable ?? true))
                >
                <span>Use as filter in shop</span>
            </label>
        </div>

        <div class="flex items-center gap-3">
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-xs font-medium hover:bg-gray-800 dark:hover:bg-gray-200"
            >
                {{ $isEdit ? 'Update attribute' : 'Create attribute' }}
            </button>

            <a href="{{ route('admin.attributes.index') }}"
               class="text-xs text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">
                Cancel
            </a>
        </div>
    </div>
</form>
