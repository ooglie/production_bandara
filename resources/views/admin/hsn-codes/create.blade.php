@extends('layouts.company')

@section('title', 'New HSN Code')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-6 text-xs space-y-4">
    <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">New HSN / GST Code</h1>

    @if($errors->any())
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
            <ul class="list-disc pl-4 space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.hsn-codes.store') }}" class="space-y-3">
        @csrf

        @include('admin.hsn-codes._form')

        <div class="flex items-center justify-between">
            <a href="{{ route('admin.hsn-codes.index') }}" class="text-[11px] text-gray-500 hover:underline">Cancel</a>
            <button type="submit"
                    class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                Create
            </button>
        </div>
    </form>
</div>
@endsection
