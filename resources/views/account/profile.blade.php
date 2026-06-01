@extends('layouts.customer')

@section('title', 'My profile')

@section('content')
@php
    use Illuminate\Support\Str;

    $nameParts = preg_split('/\s+/', trim((string) ($user->name ?? '')));
    $initials = collect($nameParts)
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');

    $initials = $initials !== '' ? $initials : 'U';

    $avatarPath = trim((string) ($user->avatar_path ?? ''));
    $avatarUrl = null;

    if ($avatarPath !== '') {
        if (Str::startsWith($avatarPath, ['http://', 'https://', '//', 'data:'])) {
            $avatarUrl = $avatarPath;
        } else {
            $normalized = ltrim($avatarPath, '/');
            $avatarUrl = Str::startsWith($normalized, 'storage/')
                ? '/' . $normalized
                : '/storage/' . $normalized;
        }
    }
@endphp

<div class="min-h-screen bg-gray-50 dark:bg-gray-950">
    <div class="max-w-5xl mx-auto px-4 py-6 space-y-6">

        <section class="overflow-hidden rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
            <div class="grid gap-0 lg:grid-cols-[320px_minmax(0,1fr)]">
                <div class="relative overflow-hidden border-b lg:border-b-0 lg:border-r border-gray-200 dark:border-gray-800 bg-gradient-to-br from-sky-50 via-white to-cyan-50 dark:from-sky-950/20 dark:via-gray-900 dark:to-cyan-950/10">
                    <div class="absolute inset-0 opacity-60">
                        <div class="absolute -top-10 -right-10 h-32 w-32 rounded-sm bg-sky-100 dark:bg-sky-900/20 blur-2xl"></div>
                        <div class="absolute -bottom-8 -left-8 h-28 w-28 rounded-sm bg-cyan-100 dark:bg-cyan-900/20 blur-2xl"></div>
                    </div>

                    <div class="relative p-6 sm:p-7 space-y-5">
                        <div class="h-20 w-20 overflow-hidden rounded-sm border border-white/70 dark:border-gray-700 bg-white dark:bg-gray-950/50 shadow-sm">
                            @if($avatarUrl)
                                <img
                                    src="{{ $avatarUrl }}"
                                    alt="{{ $user->name }}"
                                    class="h-full w-full object-cover"
                                >
                            @else
                                <div class="flex h-full w-full items-center justify-center bg-gray-900 text-xl font-semibold text-white dark:bg-gray-100 dark:text-gray-900">
                                    {{ $initials }}
                                </div>
                            @endif
                        </div>

                        <div class="space-y-2">
                            <div class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white/90 dark:bg-gray-950/50 px-3 py-1 text-[10px] font-medium uppercase tracking-[0.14em] text-gray-600 dark:text-gray-300">
                                Account settings
                            </div>

                            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-50">
                                My profile
                            </h1>

                            <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                                Keep your contact details up to date, add a profile photo, and secure your account with a strong password.
                            </p>
                        </div>

                        <div class="space-y-3">
                            <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-950/40 px-4 py-3">
                                <div class="text-[10px] uppercase tracking-wide text-gray-400">Account name</div>
                                <div class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-50">
                                    {{ $user->name ?: 'Not added' }}
                                </div>
                            </div>

                            <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-950/40 px-4 py-3">
                                <div class="text-[10px] uppercase tracking-wide text-gray-400">Email</div>
                                <div class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-50 break-all">
                                    {{ $user->email ?: 'Not added' }}
                                </div>
                            </div>

                            <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-950/40 px-4 py-3">
                                <div class="text-[10px] uppercase tracking-wide text-gray-400">Phone</div>
                                <div class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-50">
                                    {{ $user->phone ?: 'Not added' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4 sm:p-6 lg:p-7 space-y-5">
                    @if(session('status'))
                        <div class="rounded-sm border border-emerald-300 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/20 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-300">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="rounded-sm border border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-950/20 px-4 py-3 text-sm text-red-800 dark:text-red-300">
                            <div class="font-medium mb-1">Please fix the following:</div>
                            <ul class="list-disc list-inside space-y-0.5 text-xs">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="grid gap-5 xl:grid-cols-2">
                        {{-- Personal details --}}
                        <section class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
                            <div class="border-b border-gray-100 dark:border-gray-800 px-5 py-4">
                                <div class="text-[11px] uppercase tracking-[0.14em] text-gray-400">
                                    Personal details
                                </div>
                                <h2 class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">
                                    Contact information
                                </h2>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Update the details used for your account and order communication.
                                </p>
                            </div>

                            <form method="POST" action="{{ route('account.profile.update') }}" enctype="multipart/form-data" class="px-5 py-5 space-y-4">
                                @csrf
                                @method('PATCH')

                                <div class="space-y-2">
                                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                                        Profile photo
                                    </label>

                                    <div class="flex items-center gap-4 rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-4">
                                        <div class="h-16 w-16 overflow-hidden rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                                            @if($avatarUrl)
                                                <img
                                                    id="avatar-preview"
                                                    src="{{ $avatarUrl }}"
                                                    alt="{{ $user->name }}"
                                                    class="h-full w-full object-cover"
                                                >
                                            @else
                                                <div
                                                    id="avatar-placeholder"
                                                    class="flex h-full w-full items-center justify-center bg-gray-900 text-sm font-semibold text-white dark:bg-gray-100 dark:text-gray-900"
                                                >
                                                    {{ $initials }}
                                                </div>
                                                <img
                                                    id="avatar-preview"
                                                    src=""
                                                    alt="{{ $user->name }}"
                                                    class="hidden h-full w-full object-cover"
                                                >
                                            @endif
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <input
                                                id="avatar"
                                                type="file"
                                                name="avatar"
                                                accept="image/png,image/jpeg,image/webp"
                                                class="block w-full text-xs text-gray-700 dark:text-gray-300"
                                            >
                                            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                                JPG, PNG, or WEBP. Max 2 MB.
                                            </p>
                                            @error('avatar')
                                                <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-1.5">
                                    <label for="name" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                                        Full name
                                    </label>
                                    <input
                                        id="name"
                                        type="text"
                                        name="name"
                                        value="{{ old('name', $user->name) }}"
                                        required
                                        class="w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2.5 text-sm text-gray-900 dark:text-gray-50 placeholder:text-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                                    >
                                    @error('name')
                                        <p class="text-[11px] text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="space-y-1.5">
                                    <label for="email" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                                        Email address
                                    </label>
                                    <input
                                        id="email"
                                        type="email"
                                        name="email"
                                        value="{{ old('email', $user->email) }}"
                                        required
                                        class="w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2.5 text-sm text-gray-900 dark:text-gray-50 placeholder:text-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                                    >
                                    @error('email')
                                        <p class="text-[11px] text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="space-y-1.5">
                                    <label for="phone" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                                        Phone number
                                    </label>
                                    <input
                                        id="phone"
                                        type="text"
                                        name="phone"
                                        value="{{ old('phone', $user->phone) }}"
                                        required
                                        class="w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2.5 text-sm text-gray-900 dark:text-gray-50 placeholder:text-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                                    >
                                    @error('phone')
                                        <p class="text-[11px] text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="pt-1">
                                    <button
                                        type="submit"
                                        class="inline-flex items-center justify-center rounded-sm bg-gray-900 dark:bg-gray-100 px-4 py-2.5 text-sm font-medium text-white dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-white"
                                    >
                                        Save changes
                                    </button>
                                </div>
                            </form>
                        </section>

                        {{-- Password --}}
                        <section class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
                            <div class="border-b border-gray-100 dark:border-gray-800 px-5 py-4">
                                <div class="text-[11px] uppercase tracking-[0.14em] text-gray-400">
                                    Security
                                </div>
                                <h2 class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">
                                    Change password
                                </h2>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Use a strong password you don’t use anywhere else.
                                </p>
                            </div>

                            <form method="POST" action="{{ route('account.profile.password') }}" class="px-5 py-5 space-y-4" >
                                @csrf
                                @method('PATCH')

                                <div class="space-y-1.5">
                                    <label for="current_password" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                                        Current password
                                    </label>
                                    <input
                                        id="current_password"
                                        type="password"
                                        name="current_password"
                                        required
                                        class="w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2.5 text-sm text-gray-900 dark:text-gray-50 placeholder:text-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                                    >
                                    @error('current_password')
                                        <p class="text-[11px] text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="space-y-1.5">
                                    <label for="password" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                                        New password
                                    </label>
                                    <input
                                        id="password"
                                        type="password"
                                        name="password"
                                        required
                                        class="w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2.5 text-sm text-gray-900 dark:text-gray-50 placeholder:text-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                                    >
                                    @error('password')
                                        <p class="text-[11px] text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="space-y-1.5">
                                    <label for="password_confirmation" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                                        Confirm new password
                                    </label>
                                    <input
                                        id="password_confirmation"
                                        type="password"
                                        name="password_confirmation"
                                        required
                                        class="w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2.5 text-sm text-gray-900 dark:text-gray-50 placeholder:text-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                                    >
                                </div>

                                <div class="rounded-sm border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/20 px-4 py-3">
                                    <div class="text-[11px] font-medium text-amber-900 dark:text-amber-200">
                                        Password tip
                                    </div>
                                    <p class="mt-1 text-[11px] leading-relaxed text-amber-800 dark:text-amber-300">
                                        Choose a unique password with a mix of letters, numbers, and symbols.
                                    </p>
                                </div>

                                <div class="pt-1">
                                    <button
                                        type="submit"
                                        class="inline-flex items-center justify-center rounded-sm bg-gray-900 dark:bg-gray-100 px-4 py-2.5 text-sm font-medium text-white dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-white"
                                    >
                                        Update password
                                    </button>
                                </div>
                            </form>
                        </section>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('avatar');
    const preview = document.getElementById('avatar-preview');
    const placeholder = document.getElementById('avatar-placeholder');

    if (!input || !preview) return;

    input.addEventListener('change', function (event) {
        const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;

        if (!file) return;

        const reader = new FileReader();

        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');

            if (placeholder) {
                placeholder.classList.add('hidden');
            }
        };

        reader.readAsDataURL(file);
    });
});
</script>
@endsection