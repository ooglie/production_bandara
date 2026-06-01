@php
    use Illuminate\Support\Facades\Storage;

    $startsAt = old('starts_at', optional($announcement->starts_at)->format('Y-m-d\TH:i'));
    $endsAt = old('ends_at', optional($announcement->ends_at)->format('Y-m-d\TH:i'));

    $previewType = old('type', $announcement->type ?? 'info');

    $previewThemes = [
        'info' => [
            'wrap' => 'border-sky-200 bg-sky-50/70 dark:border-sky-900/50 dark:bg-sky-950/20',
            'badge' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-200',
            'icon' => '📢',
        ],
        'special' => [
            'wrap' => 'border-amber-200 bg-amber-50/70 dark:border-amber-900/50 dark:bg-amber-950/20',
            'badge' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-200',
            'icon' => '✨',
        ],
        'festive' => [
            'wrap' => 'border-fuchsia-200 bg-fuchsia-50/70 dark:border-fuchsia-900/50 dark:bg-fuchsia-950/20',
            'badge' => 'bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-900/40 dark:text-fuchsia-200',
            'icon' => '🎉',
        ],
    ];

    $previewTheme = $previewThemes[$previewType] ?? $previewThemes['info'];
@endphp

@if($errors->any())
    <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">
        <p class="font-medium">Please fix the following:</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid gap-6 lg:grid-cols-[1.45fr,0.95fr]">
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-50">Content</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Main message, label and CTA details for the home banner.
            </p>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Title</label>
                    <input
                        id="title"
                        name="title"
                        type="text"
                        value="{{ old('title', $announcement->title) }}"
                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                        placeholder="Weekend special offers are live"
                        required
                    >
                </div>

                <div>
                    <label for="label" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Label</label>
                    <input
                        id="label"
                        name="label"
                        type="text"
                        value="{{ old('label', $announcement->label) }}"
                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                        placeholder="Special Offer"
                    >
                </div>

                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Type</label>
                    <select
                        id="type"
                        name="type"
                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >
                        <option value="info" @selected(old('type', $announcement->type) === 'info')>Info</option>
                        <option value="special" @selected(old('type', $announcement->type) === 'special')>Special</option>
                        <option value="festive" @selected(old('type', $announcement->type) === 'festive')>Festive</option>
                    </select>
                </div>

                <div>
                    <label for="icon" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Icon / Emoji</label>
                    <input
                        id="icon"
                        name="icon"
                        type="text"
                        value="{{ old('icon', $announcement->icon) }}"
                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                        placeholder="🎉"
                    >
                </div>

                <div class="sm:col-span-2">
                    <input type="hidden" name="remove_background_image" value="0">

                    <label for="background_image" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        Background image
                    </label>

                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Optional. This image appears on the right side of the banner with a soft fade into transparency.
                    </p>

                    @if(!empty($announcement->background_image_path))
                        <div class="mt-3 overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40">
                            <img
                                src="{{ Storage::disk('public')->url($announcement->background_image_path) }}"
                                alt="Current announcement background"
                                class="h-32 w-full object-cover"
                            >
                        </div>

                        <label class="mt-3 inline-flex items-start gap-3">
                            <input
                                type="checkbox"
                                name="remove_background_image"
                                value="1"
                                class="mt-1 h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-500 dark:border-gray-700"
                            >
                            <span>
                                <span class="block text-sm font-medium text-gray-700 dark:text-gray-200">Remove current image</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">Use only the color/gradient background.</span>
                            </span>
                        </label>
                    @endif

                    <input
                        id="background_image"
                        name="background_image"
                        type="file"
                        accept=".jpg,.jpeg,.png,.webp,.avif,image/jpeg,image/png,image/webp,image/avif"
                        class="mt-3 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 file:mr-3 file:rounded-lg file:border-0 file:bg-gray-900 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-gray-800 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:file:bg-gray-100 dark:file:text-gray-900 dark:hover:file:bg-white"
                    >

                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Recommended: a wide image, around 1600×500 or similar.
                    </p>

                    @error('background_image')
                        <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Message</label>
                    <textarea
                        id="message"
                        name="message"
                        rows="4"
                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                        placeholder="Celebrate with curated frozen favorites, festive wishes or a limited-time special."
                    >{{ old('message', $announcement->message) }}</textarea>
                </div>

                <div>
                    <label for="cta_text" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Primary CTA text</label>
                    <input
                        id="cta_text"
                        name="cta_text"
                        type="text"
                        value="{{ old('cta_text', $announcement->cta_text) }}"
                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                        placeholder="Shop now"
                    >
                </div>

                <div>
                    <label for="cta_url" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Primary CTA URL</label>
                    <input
                        id="cta_url"
                        name="cta_url"
                        type="text"
                        value="{{ old('cta_url', $announcement->cta_url) }}"
                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                        placeholder="/shop or #specials"
                    >
                </div>

                <div>
                    <label for="secondary_text" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Secondary CTA text</label>
                    <input
                        id="secondary_text"
                        name="secondary_text"
                        type="text"
                        value="{{ old('secondary_text', $announcement->secondary_text) }}"
                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                        placeholder="View specials"
                    >
                </div>

                <div>
                    <label for="secondary_url" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Secondary CTA URL</label>
                    <input
                        id="secondary_url"
                        name="secondary_url"
                        type="text"
                        value="{{ old('secondary_url', $announcement->secondary_url) }}"
                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                        placeholder="/offers or #categories"
                    >
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-50">Schedule and priority</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Higher priority wins if multiple announcements qualify for the home page.
            </p>

            <div class="mt-5 grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="starts_at" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Starts at</label>
                    <input
                        id="starts_at"
                        name="starts_at"
                        type="datetime-local"
                        value="{{ $startsAt }}"
                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >
                </div>

                <div>
                    <label for="ends_at" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Ends at</label>
                    <input
                        id="ends_at"
                        name="ends_at"
                        type="datetime-local"
                        value="{{ $endsAt }}"
                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >
                </div>

                <div>
                    <label for="priority" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Priority</label>
                    <input
                        id="priority"
                        name="priority"
                        type="number"
                        min="0"
                        value="{{ old('priority', $announcement->priority ?? 0) }}"
                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-500 focus:outline-none focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-50">Display options</h2>

            <div class="mt-5 space-y-4">
                <div>
                    <input type="hidden" name="is_active" value="0">
                    <label class="flex items-start gap-3">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            @checked(old('is_active', $announcement->is_active))
                            class="mt-1 h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-500 dark:border-gray-700"
                        >
                        <span>
                            <span class="block text-sm font-medium text-gray-700 dark:text-gray-200">Active</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">Only active announcements can appear on the home page.</span>
                        </span>
                    </label>
                </div>

                <div>
                    <input type="hidden" name="show_on_home" value="0">
                    <label class="flex items-start gap-3">
                        <input
                            type="checkbox"
                            name="show_on_home"
                            value="1"
                            @checked(old('show_on_home', $announcement->show_on_home))
                            class="mt-1 h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-500 dark:border-gray-700"
                        >
                        <span>
                            <span class="block text-sm font-medium text-gray-700 dark:text-gray-200">Show on home</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">Enables this banner for the public home screen.</span>
                        </span>
                    </label>
                </div>

                <div>
                    <input type="hidden" name="is_dismissible" value="0">
                    <label class="flex items-start gap-3">
                        <input
                            type="checkbox"
                            name="is_dismissible"
                            value="1"
                            @checked(old('is_dismissible', $announcement->is_dismissible))
                            class="mt-1 h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-500 dark:border-gray-700"
                        >
                        <span>
                            <span class="block text-sm font-medium text-gray-700 dark:text-gray-200">Dismissible</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">Lets customers close the banner on their device.</span>
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-50">Preview</h2>

            <div class="mt-4 rounded-2xl border p-4 {{ $previewTheme['wrap'] }}">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/80 text-lg shadow-sm dark:bg-gray-950/40">
                        {{ old('icon', $announcement->icon) ?: $previewTheme['icon'] }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide {{ $previewTheme['badge'] }}">
                            {{ old('label', $announcement->label) ?: ucfirst($previewType) }}
                        </span>

                        <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-50">
                            {{ old('title', $announcement->title) ?: 'Announcement title preview' }}
                        </div>

                        <div class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                            {{ old('message', $announcement->message) ?: 'Your banner message preview will appear here.' }}
                        </div>

                        @if(old('cta_text', $announcement->cta_text) || old('secondary_text', $announcement->secondary_text))
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                @if(old('cta_text', $announcement->cta_text))
                                    <span class="inline-flex rounded-lg bg-gray-900 px-3 py-1.5 text-[11px] font-medium text-white dark:bg-gray-100 dark:text-gray-900">
                                        {{ old('cta_text', $announcement->cta_text) }}
                                    </span>
                                @endif

                                @if(old('secondary_text', $announcement->secondary_text))
                                    <span class="text-[11px] font-medium underline underline-offset-4 text-gray-700 dark:text-gray-300">
                                        {{ old('secondary_text', $announcement->secondary_text) }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-xl bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white"
            >
                Save announcement
            </button>

            <a
                href="{{ route('admin.announcements.index') }}"
                class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
            >
                Cancel
            </a>
        </div>
    </div>
</div>