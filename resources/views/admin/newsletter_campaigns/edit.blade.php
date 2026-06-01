@extends('layouts.company')

@section('title', 'Edit campaign: ' . $campaign->name)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Edit campaign
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                {{ $campaign->name }}
            </p>
        </div>
        <a href="{{ route('admin.newsletter-campaigns.index') }}"
           class="text-[11px] text-gray-500 dark:text-gray-400 underline">
            Back to campaigns
        </a>
    </div>

    @if($errors->any())
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.newsletter-campaigns.update', $campaign) }}">
        @method('PUT')
        @include('admin.newsletter_campaigns._form')
    </form>
</div>
@endsection
