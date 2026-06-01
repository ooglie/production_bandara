@extends('layouts.customer')

@section('title', 'Edit address')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-6 space-y-4">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Edit address
            </h1>
        </div>
        <a href="{{ route('account.addresses.index') }}"
           class="text-[11px] text-gray-500 dark:text-gray-400 underline">
            Back to addresses
        </a>
    </div>

    <form method="POST" action="{{ route('account.addresses.update', $address) }}">
        @csrf
        @method('PUT')
        @include('customer.addresses._form', ['address' => $address])
    </form>
</div>
@endsection
