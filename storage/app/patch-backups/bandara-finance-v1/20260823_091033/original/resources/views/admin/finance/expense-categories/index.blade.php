<x-layouts.admin title="Expense categories" heading="Expense categories">
    @include('admin.finance.partials.flash')
    @include('admin.finance.partials.nav')

    <div class="grid gap-6 xl:grid-cols-[22rem_minmax(0,1fr)]">
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-lg font-semibold text-slate-950 dark:text-white">Add category</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Custom categories appear alongside the standard operating categories.</p>

            <form method="POST" action="{{ route('admin.finance.expense-categories.store') }}" class="mt-5 space-y-4">
                @csrf
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Name</span>
                    <input name="name" value="{{ old('name') }}" required maxlength="120" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Description</span>
                    <textarea name="description" rows="3" maxlength="2000" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">{{ old('description') }}</textarea>
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Sort order</span>
                    <input type="number" name="sort_order" value="{{ old('sort_order', 1000) }}" min="0" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-slate-300">
                    Active
                </label>
                <button type="submit" class="w-full rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-950">Create category</button>
            </form>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div>
                <h2 class="text-lg font-semibold text-slate-950 dark:text-white">Manage categories</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">System categories and categories already in use are deactivated rather than deleted.</p>
            </div>

            <div class="mt-5 space-y-4">
                @foreach ($categories as $category)
                    <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                        <form method="POST" action="{{ route('admin.finance.expense-categories.update', $category) }}" class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_8rem_auto] lg:items-end">
                            @csrf
                            @method('PUT')
                            <div class="grid gap-3 sm:grid-cols-2">
                                <label class="block">
                                    <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Name</span>
                                    <input name="name" value="{{ old('name_'.$category->id, $category->name) }}" required maxlength="120" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Description</span>
                                    <input name="description" value="{{ $category->description }}" maxlength="2000" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                </label>
                            </div>
                            <label class="block">
                                <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Sort</span>
                                <input type="number" name="sort_order" value="{{ $category->sort_order }}" min="0" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="hidden" name="is_active" value="0">
                                <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                                    <input type="checkbox" name="is_active" value="1" @checked($category->is_active) class="rounded border-slate-300">
                                    Active
                                </label>
                                <button type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Save</button>
                            </div>
                        </form>

                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
                            <span>{{ $category->is_system ? 'Standard category' : 'Custom category' }} · {{ $category->expenses_count }} expense(s) · {{ $category->recurring_templates_count }} recurring template(s)</span>
                            <form method="POST" action="{{ route('admin.finance.expense-categories.destroy', $category) }}" onsubmit="return confirm('Delete or deactivate this category?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="font-medium text-rose-600 hover:text-rose-700 dark:text-rose-400">{{ $category->is_system || $category->expenses_count || $category->recurring_templates_count ? 'Deactivate' : 'Delete' }}</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-layouts.admin>
