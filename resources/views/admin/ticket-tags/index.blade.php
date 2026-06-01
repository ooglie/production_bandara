@extends('layouts.company')

@section('title', 'Ticket Tags')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6 text-xs space-y-4">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Ticket Tags</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">Manage tags used for ticket classification.</p>
        </div>

        <a href="{{ route('admin.ticket-tags.create') }}"
           class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
            New tag
        </a>
    </div>

    @if(session('status'))
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
        <table class="min-w-full text-[11px]">
            <thead class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800">
                <tr class="text-left text-gray-600 dark:text-gray-300">
                    <th class="px-3 py-2 font-medium">Name</th>
                    <th class="px-3 py-2 font-medium">Slug</th>
                    <th class="px-3 py-2 font-medium">Color</th>
                    <th class="px-3 py-2 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($tags as $tag)
                    <tr>
                        <td class="px-3 py-2 text-gray-900 dark:text-gray-50 font-medium">{{ $tag->name }}</td>
                        <td class="px-3 py-2 text-gray-500 dark:text-gray-400">{{ $tag->slug }}</td>
                        <td class="px-3 py-2">
                            @if($tag->color)
                                <span class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-2 py-0.5 text-[10px]">
                                    {{ $tag->color }}
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-right space-x-2">
                            <a href="{{ route('admin.ticket-tags.edit', $tag) }}"
                               class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1 text-[10px] hover:bg-gray-100 dark:hover:bg-gray-800">
                                Edit
                            </a>
                            <form class="inline" method="POST" action="{{ route('admin.ticket-tags.destroy', $tag) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        onclick="return confirm('Delete this tag?')"
                                        class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1 text-[10px] hover:bg-gray-100 dark:hover:bg-gray-800">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                            No tags found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-3 py-3 border-t border-gray-200 dark:border-gray-800">
            {{ $tags->links() }}
        </div>
    </div>
</div>
@endsection
