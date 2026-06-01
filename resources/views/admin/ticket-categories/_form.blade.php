@php
    /**
     * Reusable Ticket Category form.
     *
     * Variables:
     * - $action (string) required
     * - $mode ('create'|'edit') default 'create'
     * - $category (\App\Models\TicketCategory|null) default null
     * - $backUrl (string|null) optional
     */

    $mode = $mode ?? 'create';
    $isEdit = $mode === 'edit';
    $category = $category ?? null;

    $backUrl = $backUrl
        ?? (\Illuminate\Support\Facades\Route::has('admin.ticket-categories.index')
            ? route('admin.ticket-categories.index')
            : url()->previous());
@endphp

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

<form id="{{ $isEdit ? 'ticket-category-edit-form' : 'ticket-category-create-form' }}"
      method="POST"
      action="{{ $action }}"
      class="space-y-4">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                <input name="name"
                       value="{{ old('name', $category->name ?? '') }}"
                       required
                       class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]
                              focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Slug <span class="text-[10px] text-gray-400">(optional)</span>
                </label>
                <input name="slug"
                       value="{{ old('slug', $category->slug ?? '') }}"
                       placeholder="Leave blank to auto-generate"
                       class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]
                              focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700">
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">Position</label>
                <input type="number"
                       min="0"
                       name="position"
                       value="{{ old('position', $category->position ?? 0) }}"
                       class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]
                              focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700">
                <div class="mt-1 text-[10px] text-gray-400">Lower numbers appear first.</div>
            </div>

            <div class="flex items-center gap-3 sm:justify-end">
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950 px-3 py-2 w-full sm:w-auto">
                    <label class="inline-flex items-center gap-2 text-[12px] text-gray-800 dark:text-gray-200">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               @checked(old('is_active', $category->is_active ?? 1))
                               class="rounded border-gray-300 dark:border-gray-700">
                        <span>Active</span>
                    </label>
                </div>
            </div>

            <div class="sm:col-span-2">
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Description <span class="text-[10px] text-gray-400">(optional)</span>
                </label>
                <textarea name="description"
                          rows="4"
                          class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]
                                 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700">{{ old('description', $category->description ?? '') }}</textarea>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between">
        <a href="{{ $backUrl }}"
           class="text-[11px] text-gray-500 dark:text-gray-400 hover:underline">
            Cancel
        </a>

        <button type="submit"
                class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100
                       bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-[11px] font-medium
                       hover:bg-gray-800 dark:hover:bg-gray-200">
            {{ $isEdit ? 'Save' : 'Create' }}
        </button>
    </div>
</form>