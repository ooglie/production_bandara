@extends('layouts.company')

@section('title', 'Expense categories')
@section('breadcrumb', 'Admin · Finance · Expense categories')

@section('content')
@php
    $inputClass = 'mt-1 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-950 dark:focus:ring-gray-500';
@endphp

<div class="space-y-4">
    <div>
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Expense categories</h1>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Manage the standard and custom categories available for business expenses.</p>
    </div>

    @include('admin.finance.partials.nav')
    @include('admin.finance.partials.flash')

    <div class="grid gap-4 xl:grid-cols-[20rem_minmax(0,1fr)]">
        <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Add category</h2>
            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Custom categories appear alongside the standard list.</p>

            <form method="POST" action="{{ route('admin.finance.expense-categories.store') }}" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label for="new-category-name" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Name</label>
                    <input id="new-category-name" name="name" value="{{ old('name') }}" required maxlength="120" class="{{ $inputClass }}">
                    @error('name')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="new-category-description" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Description</label>
                    <textarea id="new-category-description" name="description" rows="3" maxlength="2000" class="{{ $inputClass }}">{{ old('description') }}</textarea>
                    @error('description')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="new-category-sort" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Sort order</label>
                    <input id="new-category-sort" type="number" name="sort_order" value="{{ old('sort_order', 1000) }}" min="0" class="{{ $inputClass }}">
                    @error('sort_order')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', true)) class="rounded border-gray-300 dark:border-gray-700">
                    Active
                </label>
                <button type="submit" class="w-full rounded border border-gray-900 bg-gray-900 px-3 py-1.5 text-[11px] font-medium text-white hover:bg-gray-800 dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">Create category</button>
            </form>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <div>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Manage categories</h2>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">System categories and categories already in use are deactivated instead of deleted.</p>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($categories as $category)
                    <div class="rounded border border-gray-200 p-3 dark:border-gray-800">
                        <form method="POST" action="{{ route('admin.finance.expense-categories.update', $category) }}" class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_7rem_auto] lg:items-end">
                            @csrf
                            @method('PUT')
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="category-name-{{ $category->id }}" class="block text-[10px] text-gray-500">Name</label>
                                    <input id="category-name-{{ $category->id }}" name="name" value="{{ $category->name }}" required maxlength="120" class="{{ $inputClass }}">
                                </div>
                                <div>
                                    <label for="category-description-{{ $category->id }}" class="block text-[10px] text-gray-500">Description</label>
                                    <input id="category-description-{{ $category->id }}" name="description" value="{{ $category->description }}" maxlength="2000" class="{{ $inputClass }}">
                                </div>
                            </div>
                            <div>
                                <label for="category-sort-{{ $category->id }}" class="block text-[10px] text-gray-500">Sort</label>
                                <input id="category-sort-{{ $category->id }}" type="number" name="sort_order" value="{{ $category->sort_order }}" min="0" class="{{ $inputClass }}">
                            </div>
                            <div class="flex items-center gap-2 pb-0.5">
                                <input type="hidden" name="is_active" value="0">
                                <label class="flex items-center gap-1.5 text-[11px] text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" name="is_active" value="1" @checked($category->is_active) class="rounded border-gray-300 dark:border-gray-700">
                                    Active
                                </label>
                                <button type="submit" class="rounded border border-gray-300 px-2.5 py-1.5 text-[11px] font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900">Save</button>
                            </div>
                        </form>

                        <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 pt-2 text-[10px] text-gray-500 dark:border-gray-800">
                            <span>{{ $category->is_system ? 'Standard category' : 'Custom category' }} · {{ $category->expenses_count }} expense(s) · {{ $category->recurring_templates_count }} recurring template(s)</span>
                            <form method="POST" action="{{ route('admin.finance.expense-categories.destroy', $category) }}" onsubmit="return confirm('Delete or deactivate this category?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="font-medium text-red-600 hover:underline dark:text-red-400">{{ $category->is_system || $category->expenses_count || $category->recurring_templates_count ? 'Deactivate' : 'Delete' }}</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="rounded border border-dashed border-gray-300 px-3 py-8 text-center text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">No expense categories found.</div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
