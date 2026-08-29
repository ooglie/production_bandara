@extends('layouts.company')

@section('title', 'Chefs | Bandara Kitchen')

@section('content')
    <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500 dark:text-slate-500">Bandara Kitchen</p>
                <h1 class="mt-1 text-2xl font-light tracking-tight text-slate-950 dark:text-white">Chefs</h1>
                <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-400">Manage concise Chef stories, signature dishes and the single manually selected homepage Chef.</p>
            </div>
            <a href="{{ route('admin.kitchen.chefs.create') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200">Add Chef</a>
        </div>

        @if (session('status'))
            <div class="mt-5 rounded-lg border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="mt-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-300" role="alert">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <section class="mt-6 rounded-xl border border-slate-200/80 bg-white p-5 dark:border-slate-800 dark:bg-slate-950" aria-labelledby="homepage-chef-heading">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500 dark:text-slate-500">Manual homepage selection</p>
                    <h2 id="homepage-chef-heading" class="mt-1 text-lg font-light text-slate-950 dark:text-white">{{ $featuredChef?->display_name ?? 'No Chef selected' }}</h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                        @if ($featuredChef)
                            This Chef remains featured until an administrator selects another Chef or clears the selection.
                        @else
                            The homepage Chef section is hidden until a published Chef is selected.
                        @endif
                    </p>
                </div>
                @if ($featuredChef)
                    <form method="POST" action="{{ route('admin.kitchen.chefs.unfeature') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:border-slate-500 dark:border-slate-700 dark:text-slate-300 dark:hover:border-slate-500">Clear homepage selection</button>
                    </form>
                @endif
            </div>
        </section>

        <form method="GET" action="{{ route('admin.kitchen.chefs.index') }}" class="mt-6 grid gap-3 rounded-xl border border-slate-200/80 bg-white p-4 sm:grid-cols-[minmax(0,1fr)_12rem_auto] dark:border-slate-800 dark:bg-slate-950">
            <label class="block">
                <span class="sr-only">Search Chefs</span>
                <input type="search" name="q" value="{{ $search }}" placeholder="Search Chef, title, organisation, dish or city" class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:border-slate-500 focus:ring-0 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
            </label>
            <label class="block">
                <span class="sr-only">Filter by status</span>
                <select name="status" class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:border-slate-500 focus:ring-0 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                    <option value="">All statuses</option>
                    @foreach (\App\Models\Chef::STATUSES as $option)
                        <option value="{{ $option }}" @selected($status === $option)>{{ ucfirst($option) }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex gap-2">
                <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-slate-950 px-4 text-sm font-medium text-white dark:bg-white dark:text-slate-950">Filter</button>
                @if ($search !== '' || $status !== '')
                    <a href="{{ route('admin.kitchen.chefs.index') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm text-slate-700 dark:border-slate-700 dark:text-slate-300">Clear</a>
                @endif
            </div>
        </form>

        <div class="mt-6 overflow-hidden rounded-xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-950">
            @if ($chefs->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm dark:divide-slate-800">
                        <thead class="bg-slate-50 dark:bg-slate-900/70">
                            <tr>
                                <th class="px-4 py-3 font-medium text-slate-600 dark:text-slate-400">Chef</th>
                                <th class="px-4 py-3 font-medium text-slate-600 dark:text-slate-400">Status</th>
                                <th class="px-4 py-3 font-medium text-slate-600 dark:text-slate-400">Signature dish</th>
                                <th class="px-4 py-3 font-medium text-slate-600 dark:text-slate-400">Homepage</th>
                                <th class="px-4 py-3 text-right font-medium text-slate-600 dark:text-slate-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            @foreach ($chefs as $chef)
                                @php
                                    $dishReady = filled($chef->signature_dish_name) && filled($chef->signature_dish_image_path);
                                @endphp
                                <tr class="align-top">
                                    <td class="px-4 py-4">
                                        <div class="flex min-w-72 items-center gap-3">
                                            <div class="h-14 w-11 shrink-0 overflow-hidden rounded-md bg-slate-100 dark:bg-slate-900">
                                                @if ($chef->portraitUrl())
                                                    <img src="{{ $chef->portraitUrl() }}" alt="" class="h-full w-full object-cover">
                                                @else
                                                    <div class="flex h-full items-center justify-center text-xs text-slate-500">{{ \App\Support\BandaraKitchen::initials($chef->display_name) }}</div>
                                                @endif
                                            </div>
                                            <div>
                                                <a href="{{ route('admin.kitchen.chefs.edit', $chef) }}" class="font-medium text-slate-950 hover:underline dark:text-white">{{ $chef->display_name }}</a>
                                                <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-500">
                                                    {{ $chef->professional_title }}
                                                    @if ($chef->organisation_name)<span aria-hidden="true"> · </span>{{ $chef->organisation_name }}@endif
                                                </p>
                                                @if ($chef->city)<p class="text-xs text-slate-500 dark:text-slate-500">{{ $chef->city }}</p>@endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="inline-flex rounded-md border border-slate-300 px-2.5 py-1 text-xs capitalize text-slate-700 dark:border-slate-700 dark:text-slate-300">{{ $chef->status }}</span>
                                        @if ($chef->published_at)<p class="mt-2 whitespace-nowrap text-xs text-slate-500">{{ $chef->published_at->format('d M Y') }}</p>@endif
                                    </td>
                                    <td class="px-4 py-4">
                                        @if ($dishReady)
                                            <p class="max-w-56 text-sm text-slate-700 dark:text-slate-300">{{ $chef->signature_dish_name }}</p>
                                        @else
                                            <span class="text-xs text-slate-400">Needs dish name and photograph</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        @if ($chef->isHomepageFeaturedSelection())
                                            <span class="inline-flex rounded-md bg-slate-950 px-2.5 py-1 text-xs text-white dark:bg-white dark:text-slate-950">Featured</span>
                                        @elseif ($chef->isPublished())
                                            <form method="POST" action="{{ route('admin.kitchen.chefs.feature', $chef) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-xs font-medium text-slate-700 underline-offset-4 hover:underline dark:text-slate-300">Feature on homepage</button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-400">Publish first</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex justify-end gap-3 whitespace-nowrap">
                                            @if ($chef->isPublished())
                                                <a href="{{ route('kitchen.chefs.show', $chef) }}" target="_blank" rel="noopener" class="text-xs text-slate-600 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white">View</a>
                                            @endif
                                            <a href="{{ route('admin.kitchen.chefs.edit', $chef) }}" class="text-xs font-medium text-slate-700 hover:text-slate-950 dark:text-slate-300 dark:hover:text-white">Edit</a>
                                            <form method="POST" action="{{ route('admin.kitchen.chefs.destroy', $chef) }}" onsubmit="return confirm('Remove this Chef profile? The uploaded media will be retained for recovery.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-red-700 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Remove</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($chefs->hasPages())<div class="border-t border-slate-200 px-4 py-4 dark:border-slate-800">{{ $chefs->links() }}</div>@endif
            @else
                <div class="px-6 py-12 text-center">
                    <h2 class="text-lg font-light text-slate-950 dark:text-white">No Chef profiles found.</h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Create the first Chef profile or clear the current filters.</p>
                </div>
            @endif
        </div>
    </div>
@endsection
