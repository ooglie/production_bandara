@extends('layouts.company')

@section('title', 'Coupons')

@section('breadcrumb', 'Admin · Coupons')

@section('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Coupons
            </h1>

            <a href="{{ route('admin.coupons.create') }}"
               class="inline-flex items-center px-3 py-1.5 text-xs rounded border border-gray-300 dark:border-gray-700 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-200">
                + New coupon
            </a>
        </div>

        @if(session('status'))
            <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        {{-- Filters --}}
        <form method="GET" class="flex flex-wrap items-end gap-3 text-xs">
            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">
                    Search
                </label>
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Code or name"
                    class="mt-1 w-56 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">
                    Status
                </label>
                <select
                    name="status"
                    class="mt-1 w-32 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                    <option value="">All</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">
                    Type
                </label>
                <select
                    name="type"
                    class="mt-1 w-32 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                    <option value="">All</option>
                    <option value="fixed"   @selected(request('type') === 'fixed')>Flat</option>
                    <option value="percent" @selected(request('type') === 'percent')>Percent</option>
                </select>
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

        {{-- Table --}}
        <div class="overflow-x-auto border border-gray-200 dark:border-gray-800 rounded-lg text-xs">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr class="text-[11px] uppercase text-gray-500 dark:text-gray-400">
                        <th class="px-3 py-2 text-left">Code</th>
                        <th class="px-3 py-2 text-left">Name</th>
                        <th class="px-3 py-2 text-left">Type</th>
                        <th class="px-3 py-2 text-left">Value</th>
                        <th class="px-3 py-2 text-left">Usage</th>
                        <th class="px-3 py-2 text-left">Period</th>
                        <th class="px-3 py-2 text-center">Status</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-white dark:bg-gray-950">
                    @forelse($coupons as $coupon)
                        <tr>
                            <td class="px-3 py-2 align-top">
                                <div class="font-mono text-xs text-gray-900 dark:text-gray-50">
                                    {{ $coupon->code }}
                                </div>
                            </td>

                            <td class="px-3 py-2 align-top text-gray-800 dark:text-gray-100">
                                <div class="font-medium">
                                    {{ $coupon->name ?? '—' }}
                                </div>
                                @if($coupon->description)
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400 line-clamp-1">
                                        {{ $coupon->description }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300">
                                {{ ucfirst($coupon->discount_type) }}
                            </td>

                            <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300">
                                @if($coupon->discount_type === 'fixed')
                                    ₹{{ number_format($coupon->discount_value, 2) }}
                                @else
                                    {{ rtrim(rtrim(number_format($coupon->discount_value, 2), '0'), '.') }}%
                                    @if($coupon->max_discount_amount)
                                        <span class="text-[10px] text-gray-400">
                                            (Max ₹{{ number_format($coupon->max_discount_amount, 0) }})
                                        </span>
                                    @endif
                                @endif
                            </td>

                            <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300">
                                @php
                                    $total = $coupon->usage_limit ?? '∞';
                                    $used  = $coupon->redemptions_count ?? 0;
                                @endphp
                                <div>
                                    {{ $used }} / {{ $total }}
                                </div>
                                @if($coupon->usage_limit_per_user)
                                    <div class="text-[10px] text-gray-400">
                                        Per user: {{ $coupon->usage_limit_per_user }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300">
                                @if($coupon->starts_at || $coupon->ends_at)
                                    <div class="text-[11px]">
                                        @if($coupon->starts_at)
                                            From {{ $coupon->starts_at->format('d M Y') }}
                                        @endif
                                        @if($coupon->ends_at)
                                            <br>Until {{ $coupon->ends_at->format('d M Y') }}
                                        @endif
                                    </div>
                                @else
                                    <span class="text-[11px] text-gray-400">No limit</span>
                                @endif
                            </td>

                            <td class="px-3 py-2 align-top text-center">
                                @php
                                    $isCurrentlyActive = $coupon->isCurrentlyActive();
                                @endphp
                                @if($isCurrentlyActive)
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 px-2 py-0.5 text-[11px]">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 px-2 py-0.5 text-[11px]">
                                        Inactive
                                    </span>
                                @endif
                            </td>

                            <td class="px-3 py-2 align-top text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('admin.coupons.edit', $coupon) }}"
                                       class="text-[11px] text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
                                        Edit
                                    </a>
                                    <form method="POST"
                                          action="{{ route('admin.coupons.destroy', $coupon) }}"
                                          onsubmit="return confirm('Delete this coupon?');">
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
                            <td colspan="8" class="px-3 py-6 text-center text-xs text-gray-500 dark:text-gray-400">
                                No coupons found.
                                <a href="{{ route('admin.coupons.create') }}" class="underline">
                                    Create the first one
                                </a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $coupons->links() }}
        </div>
    </div>
@endsection
