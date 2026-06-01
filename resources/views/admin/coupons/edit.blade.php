@extends('layouts.company')

@section('title', 'Edit coupon')

@section('breadcrumb', 'Admin · Coupons · Edit')

@section('content')
    <div class="space-y-4">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
            Edit coupon
        </h1>

        @include('admin.coupons._form', [
            'action' => route('admin.coupons.update', $coupon),
            'coupon' => $coupon,
        ])
    </div>
@endsection
