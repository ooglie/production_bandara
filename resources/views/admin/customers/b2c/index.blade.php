@extends('layouts.company')

@section('title', 'B2C Customers')

@section('content')
@php
    $customers = $customers ?? $users ?? collect();
    $has = fn($r) => \Illuminate\Support\Facades\Route::has($r);

    // IMPORTANT:
    // On this screen we intentionally do NOT fall back to admin.users.* for Edit/Delete.
    // That fallback is what typically leads to 404s and/or unintended deletes when routes/middleware differ.
    $createUrl = $has('admin.customers.b2c.create') ? route('admin.customers.b2c.create') : null;
    $indexAction = $has('admin.customers.b2c.index') ? route('admin.customers.b2c.index') : url()->current();

    $q = request('q', '');

    $missingCrudRoutes = [];
    foreach ([
        'admin.customers.b2c.index',
        'admin.customers.b2c.create',
        'admin.customers.b2c.store',
        'admin.customers.b2c.edit',
        'admin.customers.b2c.update',
        'admin.customers.b2c.destroy',
    ] as $r) {
        if (!$has($r)) $missingCrudRoutes[] = $r;
    }

    $fmtDate = fn($d) => $d ? optional(\Carbon\Carbon::parse($d))->format('d M Y') : '—';
@endphp

<div class="max-w-7xl mx-auto px-4 py-5 text-xs space-y-4">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-50">
                B2C Customers
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Manage retail customers only (B2C). Staff are excluded.
            </p>
        </div>

        <div class="flex items-center gap-2">
            @if($createUrl)
                <a href="{{ $createUrl }}"
                   class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                    Add B2C Customer
                </a>
            @else
                <span class="text-[11px] px-3 py-1 rounded-full border border-yellow-300 bg-yellow-50 text-yellow-800">
                    Create route missing
                </span>
            @endif
        </div>
    </div>

    @if(session('status'))
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
            <ul class="list-disc pl-4 space-y-0.5">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(!empty($missingCrudRoutes))
        <div class="rounded border border-yellow-300 bg-yellow-50 px-3 py-2 text-[11px] text-yellow-900">
            <div class="font-medium">B2C Customer CRUD routes missing</div>
            <div class="mt-1 text-[10px] text-yellow-800">
                Edit/Delete is disabled on this screen until these routes exist:
                <ul class="list-disc pl-4 mt-1 space-y-0.5">
                    @foreach($missingCrudRoutes as $r)
                        <li><code>{{ $r }}</code></li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Search --}}
    <form method="GET" action="{{ $indexAction }}" class="flex flex-wrap items-center gap-2">
        <input type="text" name="q" value="{{ $q }}"
               placeholder="Search name / email / phone"
               class="w-64 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px]">

        <button type="submit"
                class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
            Search
        </button>

        @if($q)
            <a href="{{ url()->current() }}"
               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                Clear
            </a>
        @endif
    </form>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
        <table class="min-w-full text-[11px]">
            <thead class="bg-gray-50 dark:bg-gray-950/40">
            <tr>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Customer</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Phone</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Verified</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Active</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Created</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Actions</th>
            </tr>
            </thead>

            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse($customers as $c)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                    <td class="px-3 py-2">
                        <div class="font-medium text-gray-900 dark:text-gray-50">
                            {{ $c->name ?? '—' }}
                        </div>
                        <div class="text-[10px] text-gray-400">
                            {{ $c->email ?? '—' }} · #{{ $c->id }}
                        </div>
                    </td>

                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                        {{ $c->phone ?? '—' }}
                    </td>

                    <td class="px-3 py-2">
                        @if(!empty($c->email_verified_at))
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px]
                                border-emerald-200 bg-emerald-50 text-emerald-700
                                dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                                Verified
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px]
                                border-gray-200 bg-gray-50 text-gray-500
                                dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400">
                                Not verified
                            </span>
                        @endif
                    </td>

                    <td class="px-3 py-2">
                        @if((bool)($c->is_active ?? true))
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px]
                                border-emerald-200 bg-emerald-50 text-emerald-700
                                dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                                Active
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px]
                                border-red-200 bg-red-50 text-red-700
                                dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
                                Disabled
                            </span>
                        @endif
                    </td>

                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">
                        {{ optional($c->created_at)->format('d M Y') }}
                    </td>

                    <td class="px-3 py-2">
                        <div class="flex flex-wrap gap-2">

                            @if($has('admin.customers.b2c.edit'))
                                <a href="{{ route('admin.customers.b2c.edit', $c) }}"
                                   class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                    Edit
                                </a>
                            @else
                                <span class="text-[11px] px-3 py-1 rounded-full border border-gray-200 text-gray-400">
                                    Edit (route missing)
                                </span>
                            @endif

                            @if($has('admin.customers.b2c.destroy'))
                                <form method="POST"
                                      action="{{ route('admin.customers.b2c.destroy', $c) }}"
                                      class="inline"
                                      onsubmit="return confirm('Delete this B2C customer? This will delete the user account.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-[11px] px-3 py-1 rounded-full border border-red-300 text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-200 dark:hover:bg-red-900/20">
                                        Delete
                                    </button>
                                </form>
                            @else
                                <span class="text-[11px] px-3 py-1 rounded-full border border-gray-200 text-gray-400">
                                    Delete (route missing)
                                </span>
                            @endif

                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                        No B2C customers found.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if(is_object($customers) && method_exists($customers, 'links'))
        <div class="mt-3">
            {{ $customers->links() }}
        </div>
    @endif

</div>
@endsection
