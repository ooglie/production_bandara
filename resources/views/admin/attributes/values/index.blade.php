@extends('layouts.company')

@section('title', 'Attribute values')

@section('breadcrumb')
    Admin · Attributes · {{ $attribute->name }} · Values
@endsection

@section('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                    Values – {{ $attribute->name }}
                </h1>
                @if($attribute->display_name)
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">
                        Display name: {{ $attribute->display_name }}
                    </p>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.attributes.index') }}"
                   class="text-[11px] text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">
                    Back to attributes
                </a>

                <a href="{{ route('admin.attributes.values.create', $attribute) }}"
                   class="inline-flex items-center px-3 py-1.5 text-xs rounded border border-gray-300 dark:border-gray-700 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-200">
                    + New value
                </a>
            </div>
        </div>

        @if(session('status'))
            <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="overflow-x-auto border border-gray-200 dark:border-gray-800 rounded-lg text-xs">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr class="text-[11px] uppercase text-gray-500 dark:text-gray-400">
                        <th class="px-3 py-2 text-left">Name</th>
                        <th class="px-3 py-2 text-left">Value</th>
                        <th class="px-3 py-2 text-right">Position</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-white dark:bg-gray-950">
                    @forelse($values as $value)
                        <tr>
                            <td class="px-3 py-2 align-top text-gray-800 dark:text-gray-100">
                                {{ $value->name }}
                            </td>
                            <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300">
                                {{ $value->value ?? '—' }}
                            </td>
                            <td class="px-3 py-2 align-top text-right text-gray-700 dark:text-gray-300">
                                {{ $value->position ?? 0 }}
                            </td>
                            <td class="px-3 py-2 align-top text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('admin.attributes.values.edit', $value) }}"
                                       class="text-[11px] text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
                                        Edit
                                    </a>
                                    <form method="POST"
                                          action="{{ route('admin.attributes.values.destroy', $value) }}"
                                          onsubmit="return confirm('Delete this value?');">
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
                            <td colspan="4" class="px-3 py-6 text-center text-xs text-gray-500 dark:text-gray-400">
                                No values defined for this attribute.
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
