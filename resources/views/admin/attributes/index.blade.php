@extends('layouts.company')

@section('title', 'Variant Options')

@section('breadcrumb', 'Admin · Variant Options')

@section('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Variant Options
            </h1>

            <a href="{{ route('admin.attributes.create') }}"
               class="inline-flex items-center px-3 py-1.5 text-xs rounded border border-gray-300 dark:border-gray-700 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-200">
                + New option group
            </a>
        </div>

        @if(session('status'))
            <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
                {{ session('status') }}
            </div>
        @endif
        <div class="rounded-lg border border-amber-200 bg-amber-50/70 px-4 py-3 text-[12px] text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100">
            Variant options are used only for products where customers choose a fixed option, such as
            <span class="font-semibold">Dimsum pack size</span> or
            <span class="font-semibold">Prawns size + pack weight</span>.
            Do not use them for physical pieces like pork slabs, salmon cuts, tuna, or cheese blocks.
        </div>


        <form method="GET" class="flex flex-wrap items-end gap-3 text-xs">
            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">
                    Search
                </label>
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Name or slug"
                    class="mt-1 w-56 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
            </div>

            <div>
                <button
                    type="submit"
                    class="mt-5 inline-flex items-center px-3 py-1.5 rounded border border-gray-300 dark:border-gray-700 text-xs hover:bg-gray-100 dark:hover:bg-gray-800"
                >
                    Apply
                </button>
            </div>
        </form>

        <div class="overflow-x-auto border border-gray-200 dark:border-gray-800 rounded-lg text-xs">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr class="text-[11px] uppercase text-gray-500 dark:text-gray-400">
                        <th class="px-3 py-2 text-left">Name</th>
                        <th class="px-3 py-2 text-left">Slug</th>
                        <th class="px-3 py-2 text-left">Display</th>
                        <th class="px-3 py-2 text-center">Filterable</th>
                        <th class="px-3 py-2 text-right">Option values</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-white dark:bg-gray-950">
                    @forelse($attributes as $attribute)
                        <tr>
                            <td class="px-3 py-2 align-top">
                                <div class="font-medium text-gray-900 dark:text-gray-50">
                                    {{ $attribute->name }}
                                </div>
                                @if($attribute->display_name)
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                        Label: {{ $attribute->display_name }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300">
                                {{ $attribute->slug ?? '—' }}
                            </td>
                            <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300">
                                {{ ucfirst($attribute->frontend_type) }}
                            </td>
                            <td class="px-3 py-2 align-top text-center">
                                @if($attribute->is_filterable)
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 px-2 py-0.5 text-[11px]">
                                        Yes
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 px-2 py-0.5 text-[11px]">
                                        No
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-2 align-top text-right text-gray-700 dark:text-gray-300">
                                <a href="{{ route('admin.attributes.values.index', $attribute) }}"
                                   class="text-[11px] text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
                                    {{ $attribute->values_count }} option value(s)
                                </a>
                            </td>
                            <td class="px-3 py-2 align-top text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('admin.attributes.edit', $attribute) }}"
                                       class="text-[11px] text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
                                        Edit
                                    </a>
                                    <form method="POST"
                                          action="{{ route('admin.attributes.destroy', $attribute) }}"
                                          onsubmit="return confirm('Delete this variant option group? This will also affect products/variants using it.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-[11px] text-red-600 hover:text-red-700">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-xs text-gray-500 dark:text-gray-400">
                                No variant option groups defined yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $attributes->links() }}
        </div>
    </div>
@endsection
