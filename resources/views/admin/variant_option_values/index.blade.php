@extends('layouts.company')

@section('title', 'Variant Option Values')

@section('breadcrumb', 'Admin · Variant Options · Values')

@section('content')
    @php
        $currentQuery = request()->only(['attribute_id', 'q', 'page']);
        $queryString = http_build_query($currentQuery);
    @endphp

    <div class="space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                    Variant Option Values
                </h1>
                <p class="mt-1 text-[12px] text-gray-500 dark:text-gray-400">
                    Manage all selectable values used by product variants, such as pack size, prawn size, or cut option.
                </p>
            </div>

            <a href="{{ route('admin.attributes.index') }}"
               class="inline-flex items-center px-3 py-1.5 text-xs rounded border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                Manage option groups
            </a>
        </div>

        @if(session('status'))
            <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-100">
                {{ session('status') }}
            </div>
        @endif

        @if(session('error'))
            <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-100">
                {{ session('error') }}
            </div>
        @endif

        <div class="rounded-lg border border-gray-200 bg-white p-4 text-xs dark:border-gray-800 dark:bg-gray-950">
            <form method="POST" action="{{ route('admin.variant-option-values.store') }}" class="grid gap-3 md:grid-cols-5 md:items-end">
                @csrf

                <div class="md:col-span-1">
                    <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">
                        Option group
                    </label>
                    <select name="attribute_id" required
                            class="mt-1 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-gray-500">
                        <option value="">Choose group</option>
                        @foreach($attributes as $attribute)
                            <option value="{{ $attribute->id }}" @selected((int) old('attribute_id', $selectedAttributeId) === (int) $attribute->id)>
                                {{ $attribute->display_name ?: $attribute->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('attribute_id') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-1">
                    <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">
                        Display value
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. 500g / Jumbo"
                           class="mt-1 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-gray-500">
                    @error('name') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-1">
                    <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">
                        Internal value/code
                    </label>
                    <input type="text" name="value" value="{{ old('value') }}" placeholder="Optional"
                           class="mt-1 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-gray-500">
                    @error('value') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-1">
                    <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">
                        Position
                    </label>
                    <input type="number" name="position" value="{{ old('position', 0) }}"
                           class="mt-1 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-gray-500">
                    @error('position') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-1">
                    <button type="submit"
                            class="mt-1 inline-flex w-full items-center justify-center rounded border border-gray-900 bg-gray-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-800 dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">
                        + Add value
                    </button>
                </div>
            </form>
        </div>

        <form method="GET" class="flex flex-wrap items-end gap-3 text-xs">
            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">
                    Option group
                </label>
                <select name="attribute_id"
                        class="mt-1 w-56 rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-gray-500">
                    <option value="">All groups</option>
                    @foreach($attributes as $attribute)
                        <option value="{{ $attribute->id }}" @selected((int) $selectedAttributeId === (int) $attribute->id)>
                            {{ $attribute->display_name ?: $attribute->name }} ({{ $attribute->values_count }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">
                    Search
                </label>
                <input type="text" name="q" value="{{ $search }}" placeholder="Value, code, or group"
                       class="mt-1 w-64 rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-gray-500">
            </div>

            <button type="submit"
                    class="inline-flex items-center px-3 py-1.5 rounded border border-gray-300 text-xs hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800">
                Apply
            </button>

            @if($selectedAttributeId || $search !== '')
                <a href="{{ route('admin.variant-option-values.index') }}"
                   class="inline-flex items-center px-3 py-1.5 rounded border border-gray-300 text-xs hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800">
                    Clear
                </a>
            @endif
        </form>

        <div class="overflow-x-auto rounded-lg border border-gray-200 text-xs dark:border-gray-800">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr class="text-[11px] uppercase text-gray-500 dark:text-gray-400">
                        <th class="px-3 py-2 text-left">Option group</th>
                        <th class="px-3 py-2 text-left">Display value</th>
                        <th class="px-3 py-2 text-left">Internal value/code</th>
                        <th class="px-3 py-2 text-right">Position</th>
                        <th class="px-3 py-2 text-right">Used by</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-gray-950">
                    @forelse($values as $value)
                        @php
                            $formId = 'update-option-value-' . $value->id;
                            $deleteFormId = 'delete-option-value-' . $value->id;
                            $variantCount = $variantUsageCounts[$value->id] ?? 0;
                            $isUsed = ((int) $value->products_count > 0) || ((int) $variantCount > 0);
                            $actionQuery = $queryString ? '?' . $queryString : '';
                        @endphp
                        <tr>
                            <td class="px-3 py-2 align-top text-gray-800 dark:text-gray-100">
                                <div class="font-medium">
                                    {{ $value->attribute?->display_name ?: $value->attribute?->name ?: '—' }}
                                </div>
                                @if($value->attribute?->slug)
                                    <div class="text-[10px] text-gray-400">
                                        {{ $value->attribute->slug }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-3 py-2 align-top">
                                <input form="{{ $formId }}" type="text" name="name" value="{{ $value->name }}" required
                                       class="w-48 rounded border border-gray-300 bg-white px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-gray-500">
                            </td>

                            <td class="px-3 py-2 align-top">
                                <input form="{{ $formId }}" type="text" name="value" value="{{ $value->value }}" placeholder="—"
                                       class="w-44 rounded border border-gray-300 bg-white px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-gray-500">
                            </td>

                            <td class="px-3 py-2 align-top text-right">
                                <input form="{{ $formId }}" type="number" name="position" value="{{ $value->position ?? 0 }}"
                                       class="w-20 rounded border border-gray-300 bg-white px-2 py-1 text-right text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-gray-500">
                            </td>

                            <td class="px-3 py-2 align-top text-right text-gray-700 dark:text-gray-300">
                                <div>{{ (int) $value->products_count }} product(s)</div>
                                <div class="text-[10px] text-gray-400">{{ (int) $variantCount }} variant(s)</div>
                            </td>

                            <td class="px-3 py-2 align-top text-right">
                                <div class="inline-flex items-center gap-2">
                                    <form id="{{ $formId }}" method="POST" action="{{ route('admin.variant-option-values.update', $value) }}{{ $actionQuery }}">
                                        @csrf
                                        @method('PUT')
                                    </form>
                                    <button form="{{ $formId }}" type="submit"
                                            class="text-[11px] text-gray-700 hover:text-gray-950 dark:text-gray-300 dark:hover:text-gray-100">
                                        Save
                                    </button>

                                    @if($isUsed)
                                        <span class="text-[11px] text-gray-400" title="Remove this value from products and variants before deleting it.">
                                            Delete locked
                                        </span>
                                    @else
                                        <form id="{{ $deleteFormId }}" method="POST" action="{{ route('admin.variant-option-values.destroy', $value) }}{{ $actionQuery }}"
                                              onsubmit="return confirm('Delete this variant option value?');">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                        <button form="{{ $deleteFormId }}" type="submit"
                                                class="text-[11px] text-red-600 hover:text-red-700">
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-xs text-gray-500 dark:text-gray-400">
                                No variant option values found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $values->links() }}
        </div>
    </div>
@endsection
