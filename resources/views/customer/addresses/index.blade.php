@extends('layouts.customer')

@section('title', 'My addresses')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Saved addresses
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Manage your shipping and billing addresses for faster checkout.
            </p>
        </div>
        
        <a href="{{ route('account.addresses.create') }}"
           class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
            Add new address
        </a>
    </div>

    @if(session('status'))
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if($addresses->isEmpty())
        <div class="rounded border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 text-xs text-gray-500 dark:text-gray-400">
            You don’t have any saved addresses yet.
        </div>
    @else
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($addresses as $address)
                <div class="border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-900 px-4 py-3 text-xs flex flex-col gap-2">
                    <div class="flex items-start justify-between gap-2">
                        <div class="space-y-0.5">
                            <div class="font-medium text-gray-900 dark:text-gray-50">
                                {{ $address->full_name }}
                            </div>
                            <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                {{ $address->phone }}
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            @if($address->is_default_shipping)
                                <span class="inline-flex items-center rounded-sm bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-2 py-0.5 text-[10px]">
                                    Default shipping
                                </span>
                            @endif
                            @if($address->is_default_billing)
                                <span class="inline-flex items-center rounded-sm bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 px-2 py-0.5 text-[10px]">
                                    Default billing
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="text-[11px] text-gray-700 dark:text-gray-300 space-y-0.5">
                        <div>{{ $address->address_line1 }}</div>
                        @if($address->address_line2)
                            <div>{{ $address->address_line2 }}</div>
                        @endif
                        <div>
                            {{ $address->city }},
                            {{ $address->state }}
                            @if($address->pincode)
                                – {{ $address->pincode }}
                            @endif
                        </div>
                        <div>{{ $address->country }}</div>
                        @if($address->gstin)
                            <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                GSTIN: {{ $address->gstin }}
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <a href="{{ route('account.addresses.edit', $address) }}"
                           class="text-[11px] text-gray-600 dark:text-gray-300 underline">
                            Edit
                        </a>

                        <form method="POST" action="{{ route('account.addresses.destroy', $address) }}"
                              onsubmit="return confirm('Remove this address?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="text-[11px] text-red-600 hover:text-red-700">
                                Delete
                            </button>
                        </form>
                    </div>

                    <p class="text-[10px] text-gray-400 dark:text-gray-500">
                        This address will be available during checkout.
                    </p>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
