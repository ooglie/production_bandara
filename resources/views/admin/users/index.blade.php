@extends('layouts.company')

@section('title', 'Users')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Users
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Manage customers and staff. You can edit roles, update details, and delete accounts.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.users.create') }}"
               class="rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-xs">
                + Add user
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap items-center gap-2 text-xs mb-3">
        <input
            type="text"
            name="q"
            value="{{ request('q') }}"
            placeholder="Search name / email / phone"
            class="rounded-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
        >
        <select
            name="role"
            class="rounded-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
        >
            <option value="">All roles</option>
            @foreach($roles as $role)
                <option value="{{ $role->name }}" @selected(request('role') === $role->name)>
                    {{ $role->name }}
                </option>
            @endforeach
        </select>
        <button
            class="rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5">
            Filter
        </button>
    </form>

    <div class="border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-900 overflow-hidden">
        <table class="w-full text-xs">
            <thead class="bg-gray-50 dark:bg-gray-900/60 border-b border-gray-200 dark:border-gray-800">
                <tr class="text-left text-[11px] text-gray-500 dark:text-gray-400">
                    <th class="px-3 py-2.5">Name</th>
                    <th class="px-3 py-2.5">Email</th>
                    <th class="px-3 py-2.5">Phone</th>
                    <th class="px-3 py-2.5">Roles</th>
                    <th class="px-3 py-2.5">Created</th>
                    <th class="px-3 py-2.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr class="border-t border-gray-100 dark:border-gray-800">
                        <td class="px-3 py-2 align-top">
                            <div class="font-medium text-gray-900 dark:text-gray-50">
                                {{ $user->name }}
                                @if($user->id === auth()->id())
                                    <span class="ml-1 text-[10px] text-gray-400">(you)</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-3 py-2 align-top">
                            <div class="text-[11px] text-gray-800 dark:text-gray-100">
                                {{ $user->email }}
                            </div>
                            <div class="text-[10px] text-gray-400">
                                @if($user->email_verified_at)
                                    Verified {{ $user->email_verified_at->format('d M Y') }}
                                @else
                                    Not verified
                                @endif
                            </div>
                        </td>
                        <td class="px-3 py-2 align-top">
                            {{ $user->phone ?? '—' }}
                        </td>
                        <td class="px-3 py-2 align-top">
                            @php
                                $roleNames = $user->getRoleNames();
                            @endphp
                            @if($roleNames->isEmpty())
                                <span class="text-[11px] text-gray-400">No roles</span>
                            @else
                                <div class="flex flex-wrap gap-1">
                                    @foreach($roleNames as $role)
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200">
                                            {{ $role }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="px-3 py-2 align-top">
                            {{ $user->created_at->format('d M Y') }}
                        </td>
                        <td class="px-3 py-2 align-top text-right">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route('admin.users.edit', $user) }}"
                                   class="text-[11px] text-gray-700 dark:text-gray-200 underline">
                                    Edit
                                </a>
                                @if($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                          onsubmit="return confirm('Delete this user?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-[11px] text-red-600 dark:text-red-400 underline">
                                            Delete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-4 text-center text-[11px] text-gray-500 dark:text-gray-400">
                            No users found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-3 py-2 border-t border-gray-100 dark:border-gray-800">
            {{ $users->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
