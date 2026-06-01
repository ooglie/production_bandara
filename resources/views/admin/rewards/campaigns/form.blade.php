@extends('layouts.company')
@section('title', $campaign->exists ? 'Edit Reward Campaign' : 'Create Reward Campaign')
@section('breadcrumb', 'Admin · Rewards · Campaigns')
@section('content')
<div class="space-y-4">
    <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">{{ $campaign->exists ? 'Edit' : 'Create' }} Reward Campaign</h1>
    @include('admin.rewards._nav')
    @if($errors->any())<div class="rounded border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ $campaign->exists ? route('admin.rewards.campaigns.update', $campaign) : route('admin.rewards.campaigns.store') }}" class="space-y-4 rounded-lg border border-gray-200 bg-white p-4 text-xs dark:border-gray-800 dark:bg-gray-950">
        @csrf
        @if($campaign->exists) @method('PUT') @endif
        <div class="grid gap-3 md:grid-cols-2">
            <div><label class="block text-[10px] text-gray-500">Name</label><input name="name" value="{{ old('name', $campaign->name) }}" class="w-full rounded border px-2 py-1.5 dark:border-gray-700 dark:bg-gray-950"></div>
            <div><label class="block text-[10px] text-gray-500">Status</label><select name="status" class="w-full rounded border px-2 py-1.5 dark:border-gray-700 dark:bg-gray-950">@foreach(['draft','active','paused','expired'] as $s)<option value="{{ $s }}" @selected(old('status', $campaign->status ?: 'draft')===$s)>{{ str($s)->headline() }}</option>@endforeach</select></div>
            <div><label class="block text-[10px] text-gray-500">Type</label><select name="type" class="w-full rounded border px-2 py-1.5 dark:border-gray-700 dark:bg-gray-950">@foreach(['order','product','category','fixed_bonus'] as $t)<option value="{{ $t }}" @selected(old('type', $campaign->type ?: 'order')===$t)>{{ str($t)->headline() }}</option>@endforeach</select></div>
            <div><label class="block text-[10px] text-gray-500">Eligible tiers</label><select multiple name="eligible_tiers[]" class="w-full rounded border px-2 py-1.5 dark:border-gray-700 dark:bg-gray-950">@php($selectedTiers=old('eligible_tiers', $campaign->eligible_tiers ?? []))@foreach($tiers as $tier)<option value="{{ $tier->key }}" @selected(in_array($tier->key, $selectedTiers, true))>{{ $tier->name }}</option>@endforeach</select><div class="mt-1 text-[10px] text-gray-500">Leave blank for all tiers.</div></div>
            <div><label class="block text-[10px] text-gray-500">Starts at</label><input type="datetime-local" name="starts_at" value="{{ old('starts_at', optional($campaign->starts_at)->format('Y-m-d\TH:i')) }}" class="w-full rounded border px-2 py-1.5 dark:border-gray-700 dark:bg-gray-950"></div>
            <div><label class="block text-[10px] text-gray-500">Ends at</label><input type="datetime-local" name="ends_at" value="{{ old('ends_at', optional($campaign->ends_at)->format('Y-m-d\TH:i')) }}" class="w-full rounded border px-2 py-1.5 dark:border-gray-700 dark:bg-gray-950"></div>
            <div><label class="block text-[10px] text-gray-500">Minimum order amount</label><input type="number" step="0.01" name="min_order_amount" value="{{ old('min_order_amount', $campaign->min_order_amount) }}" class="w-full rounded border px-2 py-1.5 dark:border-gray-700 dark:bg-gray-950"></div>
            <div><label class="block text-[10px] text-gray-500">Multiplier</label><input type="number" step="0.001" min="1" name="multiplier" value="{{ old('multiplier', $campaign->multiplier ?: 1) }}" class="w-full rounded border px-2 py-1.5 dark:border-gray-700 dark:bg-gray-950"><div class="mt-1 text-[10px] text-gray-500">2 means double normal tier earning.</div></div>
            <div><label class="block text-[10px] text-gray-500">Fixed bonus points</label><input type="number" name="fixed_bonus_points" value="{{ old('fixed_bonus_points', $campaign->fixed_bonus_points) }}" class="w-full rounded border px-2 py-1.5 dark:border-gray-700 dark:bg-gray-950"></div>
            <div><label class="block text-[10px] text-gray-500">Max bonus/order</label><input type="number" name="max_bonus_per_order" value="{{ old('max_bonus_per_order', $campaign->max_bonus_per_order) }}" class="w-full rounded border px-2 py-1.5 dark:border-gray-700 dark:bg-gray-950"></div>
            <div><label class="block text-[10px] text-gray-500">Max bonus/customer</label><input type="number" name="max_bonus_per_customer" value="{{ old('max_bonus_per_customer', $campaign->max_bonus_per_customer) }}" class="w-full rounded border px-2 py-1.5 dark:border-gray-700 dark:bg-gray-950"></div>
            <div><label class="block text-[10px] text-gray-500">Budget points</label><input type="number" name="budget_points" value="{{ old('budget_points', $campaign->budget_points) }}" class="w-full rounded border px-2 py-1.5 dark:border-gray-700 dark:bg-gray-950"></div>
        </div>
        <div><label class="block text-[10px] text-gray-500">Description</label><textarea name="description" rows="3" class="w-full rounded border px-2 py-1.5 dark:border-gray-700 dark:bg-gray-950">{{ old('description', $campaign->description) }}</textarea></div>
        <label class="inline-flex items-center gap-2"><input type="checkbox" name="counts_toward_tier" value="1" @checked(old('counts_toward_tier', $campaign->counts_toward_tier))><span>Promo bonus counts toward tier progress</span></label>
        <div class="grid gap-3 md:grid-cols-2">
            <div><label class="block text-[10px] text-gray-500">Product scope</label><select multiple name="product_ids[]" class="h-44 w-full rounded border px-2 py-1.5 dark:border-gray-700 dark:bg-gray-950">@php($selectedProducts=old('product_ids', $campaign->exists ? $campaign->products()->pluck('products.id')->all() : []))@foreach($products as $product)<option value="{{ $product->id }}" @selected(in_array($product->id, $selectedProducts))>{{ $product->name }} @if($product->sku) · {{ $product->sku }} @endif</option>@endforeach</select></div>
            <div><label class="block text-[10px] text-gray-500">Category scope</label><select multiple name="category_ids[]" class="h-44 w-full rounded border px-2 py-1.5 dark:border-gray-700 dark:bg-gray-950">@php($selectedCategories=old('category_ids', $campaign->exists ? $campaign->categories()->pluck('categories.id')->all() : []))@foreach($categories as $category)<option value="{{ $category->id }}" @selected(in_array($category->id, $selectedCategories))>{{ $category->name }}</option>@endforeach</select></div>
        </div>
        <div class="flex gap-2"><button class="rounded bg-gray-900 px-3 py-1.5 text-white dark:bg-gray-100 dark:text-gray-900">Save campaign</button><a href="{{ route('admin.rewards.campaigns.index') }}" class="rounded border px-3 py-1.5 dark:border-gray-700">Cancel</a></div>
    </form>
</div>
@endsection
