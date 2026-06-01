@extends('layouts.company')

@section('title', 'Create coupon')

@section('breadcrumb', 'Admin · Coupons · Create')

@section('content')
    <div class="space-y-4">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
            Create coupon
        </h1>

        @include('admin.coupons._form', [
            'action' => route('admin.coupons.store'),
            'coupon' => null,
        ])
    </div>
@endsection
