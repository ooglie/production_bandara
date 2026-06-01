@if(config('features.newsletter', true))
    <div class="max-w-md">
        @if(session('newsletter_status'))
            <div class="mb-2 rounded-sm border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
                {{ session('newsletter_status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('newsletter.subscribe') }}" class="flex flex-col sm:flex-row gap-2 text-xs">
            @csrf
            
            <input
                type="email"
                name="email"
                value="{{ old('email', auth()->user()->email ?? '') }}"
                placeholder="Your email"
                required
                class="flex-1 rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
            >
            {{-- <input
                type="text"
                name="name"
                value="{{ old('name', auth()->user()->name ?? '') }}"
                placeholder="Name (optional)"
                class="flex-1 rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
            > --}}

            <input type="hidden" name="source" value="footer">

            <button
                type="submit"
                class="sm:w-auto rounded-sm border border-gray-500 dark:border-gray-400 bg-gray-500 text-white dark:bg-gray-600 dark:text-gray-100 px-4 py-1.5 font-medium hover:bg-gray-800 dark:hover:bg-gray-800"
            >
                Subscribe
            </button>
        </form>

        @error('email')
            <p class="mt-1 text-[10px] text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>
@endif
