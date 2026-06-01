@extends('layouts.customer')

@section('title', $page->tr('meta_title') ?: $page->tr('title'))

@section('content')
<div class="max-w-4xl mx-auto px-4 py-6 space-y-6 text-xs">
    <nav class="text-[11px] text-gray-500 dark:text-gray-400">
        <a href="{{ route('home') }}" class="hover:underline">Home</a>
        <span class="mx-1">/</span>
        <span class="text-gray-700 dark:text-gray-200">{{ $page->tr('title') }}</span>
    </nav>

    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 space-y-4">
        <div class="space-y-1">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">
                {{ $page->tr('title') }}
            </h1>

            @if($page->tr('excerpt'))
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    {{ $page->tr('excerpt') }}
                </p>
            @endif
        </div>

        @if($page->tr('content'))
            <div class="prose prose-sm max-w-none text-gray-700 dark:text-gray-200 dark:prose-invert">
                {!! nl2br(e($page->tr('content'))) !!}
            </div>
        @endif
    </div>
</div>
@endsection