@extends('layouts.company')

@section('title', 'Roles & Permissions')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-5 text-xs space-y-4">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-50">
                Roles & Permissions
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Manage what each role can view or manage (add/edit/delete) across the system.
            </p>
        </div>
    </div>

    @if(session('status'))
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
        <table class="min-w-full text-[11px]">
            <thead class="bg-gray-50 dark:bg-gray-950/40">
            <tr>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Role</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Permissions</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Actions</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @foreach($roles as $role)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                    <td class="px-3 py-2 text-gray-900 dark:text-gray-50 font-medium">
                        {{ $role->name }}
                    </td>
                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                        {{ $role->permissions_count }}
                    </td>
                    <td class="px-3 py-2">
                        <a href="{{ route('admin.roles.edit', $role) }}"
                           class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                            Manage permissions
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
        <p class="text-[11px] text-gray-600 dark:text-gray-300">
            Note: Admin role is enforced as full access (to prevent lockouts).
        </p>
    </div>
</div>
@endsection
