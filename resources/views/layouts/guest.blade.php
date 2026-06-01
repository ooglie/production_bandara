{{-- resources/views/layouts/customer.blade.php --}}
@extends('layouts.base')

@section('body')

@if(auth()->check() && session()->has('impersonator_id'))
    <div class="bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-100 text-[11px] px-4 py-2 flex items-center justify-between">
        <span>
            You are currently impersonating
            <strong>{{ auth()->user()->name }}</strong>.
        </span>
        <form method="POST" action="{{ route('impersonation.stop') }}">
            @csrf
            <button
                type="submit"
                class="underline">
                Stop impersonating
            </button>
        </form>
    </div>
@endif
@include('partials.nav.customer')
<div class="min-h-screen flex flex-col bg-gray-50 dark:bg-gray-950">
    {{-- Page content --}}
    <main class="flex-1">
        @yield('content')
    </main>

    @include('partials.footer.customer')
</div>
@endsection
