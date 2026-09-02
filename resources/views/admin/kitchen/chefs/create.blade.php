@extends('layouts.company')

@section('title', 'Add Chef | Bandara Kitchen')

@section('content')
    <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500 dark:text-slate-500">Bandara Kitchen</p>
                <h1 class="mt-1 text-2xl font-light tracking-tight text-slate-950 dark:text-white">Add Chef</h1>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Create a concise Chef story with one signature dish and the essential photographs.</p>
            </div>
            <a href="{{ route('admin.kitchen.chefs.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:border-slate-500 dark:border-slate-700 dark:text-slate-300 dark:hover:border-slate-500">Back to Chefs</a>
        </div>

        <form method="POST" action="{{ route('admin.kitchen.chefs.store') }}" enctype="multipart/form-data">
            @csrf
            @include('admin.kitchen.chefs._form')
        </form>
    </div>
@endsection
