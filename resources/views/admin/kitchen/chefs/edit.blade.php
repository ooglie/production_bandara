@extends('layouts.company')

@section('title', 'Edit '.$chef->display_name.' | Bandara Kitchen')

@section('content')
    <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500 dark:text-slate-500">Bandara Kitchen</p>
                <h1 class="mt-1 text-2xl font-light tracking-tight text-slate-950 dark:text-white">{{ $chef->display_name }}</h1>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Edit the Chef story, signature dish, photographs and public links.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($chef->isPublished())
                    <a href="{{ route('kitchen.chefs.show', $chef) }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:border-slate-500 dark:border-slate-700 dark:text-slate-300 dark:hover:border-slate-500">View public page ↗</a>
                @endif
                <a href="{{ route('admin.kitchen.chefs.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:border-slate-500 dark:border-slate-700 dark:text-slate-300 dark:hover:border-slate-500">Back to Chefs</a>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.kitchen.chefs.update', $chef) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('admin.kitchen.chefs._form')
        </form>
    </div>
@endsection
