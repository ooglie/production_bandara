@extends('layouts.company')

@section('title', 'Edit B2B Product')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-6 text-xs space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Edit B2B catalog item</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Customer: {{ $user->name }}
            </p>
        </div>

        <a href="{{ route('admin.customers.b2b-products.index', $user) }}"
           class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
            Back
        </a>
    </div>

    @if($errors->any())
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
            <ul class="list-disc pl-4 space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.customers.b2b-products.update', [$user, $row]) }}" class="space-y-3">
        @csrf
        @method('PUT')

        @include('admin.customers.b2b-products._form', ['user' => $user, 'row' => $row, 'products' => collect()])

        <div class="flex items-center gap-2">
            <button type="submit"
                    class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                Update
            </button>
            <a href="{{ route('admin.customers.b2b-products.index', $user) }}"
               class="text-[11px] text-gray-500 dark:text-gray-400 hover:underline">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
