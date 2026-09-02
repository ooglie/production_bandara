@php
    /** @var \App\Models\Chef $chef */
    $inputClass = 'mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-slate-500 focus:ring-0 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:placeholder:text-slate-600';
    $labelClass = 'block text-xs font-medium uppercase tracking-[0.12em] text-slate-600 dark:text-slate-400';
    $helpClass = 'mt-1 text-xs normal-case leading-5 tracking-normal text-slate-500 dark:text-slate-500';
    $errorClass = 'mt-1 text-xs normal-case tracking-normal text-red-700 dark:text-red-400';
    $panelClass = 'rounded-xl border border-slate-200/80 bg-white p-5 sm:p-6 dark:border-slate-800 dark:bg-slate-950';
    $galleryPaths = collect($chef->gallery_image_paths ?? [])->filter()->values();
    $approvalConfirmed = (bool) old(
        'content_and_images_approved',
        $chef->image_rights_confirmed && $chef->profile_use_approved
    );
@endphp

@if ($errors->any())
    <div class="mb-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-300" role="alert">
        <p class="font-medium">Please correct the highlighted information.</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="space-y-5" data-chef-simple-form>
    <section class="{{ $panelClass }}" aria-labelledby="chef-profile-heading">
        <div class="mb-6">
            <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500 dark:text-slate-500">1. Chef profile</p>
            <h2 id="chef-profile-heading" class="mt-1 text-lg font-light text-slate-950 dark:text-white">A concise introduction and the Chef’s story</h2>
            <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-400">Education, awards, career history and cooking philosophy can be written naturally inside the story rather than entered as separate fields.</p>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <label class="{{ $labelClass }}">
                Chef’s public name <span class="text-red-600">*</span>
                <input type="text" name="display_name" value="{{ old('display_name', $chef->display_name) }}" required maxlength="150" class="{{ $inputClass }}">
                @error('display_name')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>

            <label class="{{ $labelClass }}">
                Professional title <span class="text-red-600">*</span>
                <input type="text" name="professional_title" value="{{ old('professional_title', $chef->professional_title) }}" required maxlength="180" placeholder="Executive Chef" class="{{ $inputClass }}">
                @error('professional_title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>

            <label class="{{ $labelClass }}">
                Restaurant, hotel or culinary brand
                <input type="text" name="organisation_name" value="{{ old('organisation_name', $chef->organisation_name) }}" maxlength="200" class="{{ $inputClass }}">
                @error('organisation_name')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>

            <label class="{{ $labelClass }}">
                City
                <input type="text" name="city" value="{{ old('city', $chef->city) }}" maxlength="120" class="{{ $inputClass }}">
                @error('city')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>
        </div>

        <label class="{{ $labelClass }} mt-5">
            Short Chef brief
            <textarea name="short_intro" rows="3" maxlength="700" class="{{ $inputClass }}" placeholder="A concise 30–60 word introduction for the homepage and Chef cards.">{{ old('short_intro', $chef->short_intro) }}</textarea>
            <p class="{{ $helpClass }}">Required before publishing.</p>
            @error('short_intro')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </label>

        <label class="{{ $labelClass }} mt-5">
            The Chef’s story
            <textarea name="biography" rows="9" maxlength="12000" class="{{ $inputClass }}" placeholder="Write one well-edited profile covering the Chef’s background, influences, experience and approach to food.">{{ old('biography', $chef->biography) }}</textarea>
            <p class="{{ $helpClass }}">A focused editorial story of approximately 150–350 words is ideal. Required before publishing.</p>
            @error('biography')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </label>
    </section>

    <section class="{{ $panelClass }}" aria-labelledby="signature-dish-heading">
        <div class="mb-6">
            <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500 dark:text-slate-500">2. Signature dish</p>
            <h2 id="signature-dish-heading" class="mt-1 text-lg font-light text-slate-950 dark:text-white">One dish that represents the Chef</h2>
            <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-400">This is an editorial showcase, not a recipe. No ingredients, method or existing Recipe record is required.</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="space-y-5">
                <label class="{{ $labelClass }}">
                    Signature dish name
                    <input type="text" name="signature_dish_name" value="{{ old('signature_dish_name', $chef->signature_dish_name) }}" maxlength="200" placeholder="Charred Pork Neck with Smoked Chilli Jus" class="{{ $inputClass }}">
                    <p class="{{ $helpClass }}">Required before publishing.</p>
                    @error('signature_dish_name')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </label>

                <label class="{{ $labelClass }}">
                    Dish description or story
                    <textarea name="signature_dishes" rows="6" maxlength="3000" class="{{ $inputClass }}" placeholder="Describe the inspiration, technique, flavours or why the Chef chose this dish.">{{ old('signature_dishes', $chef->signature_dishes) }}</textarea>
                    <p class="{{ $helpClass }}">Approximately 60–150 words. Required before publishing.</p>
                    @error('signature_dishes')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </label>
            </div>

            <div>
                <p class="{{ $labelClass }}">Signature dish photograph</p>
                <div class="mt-1 aspect-[4/3] overflow-hidden rounded-lg border border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
                    @if ($chef->signatureDishImageUrl())
                        <img src="{{ $chef->signatureDishImageUrl() }}" alt="Current signature dish" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full items-center justify-center px-5 text-center text-xs leading-5 text-slate-500">A strong horizontal or square photograph will be displayed here.</div>
                    @endif
                </div>
                <input type="file" name="signature_dish_image" accept="image/jpeg,image/png,image/webp" class="mt-3 block w-full text-xs text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-medium file:text-slate-700 dark:text-slate-400 dark:file:bg-slate-900 dark:file:text-slate-300">
                <p class="{{ $helpClass }}">JPG, PNG or WebP, up to 12 MB. Required before publishing.</p>
                @error('signature_dish_image')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror

                @if ($chef->signature_dish_image_path)
                    <label class="mt-3 flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                        <input type="hidden" name="remove_signature_dish_image" value="0">
                        <input type="checkbox" name="remove_signature_dish_image" value="1" @checked(old('remove_signature_dish_image')) class="rounded border-slate-300 text-slate-950 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-900">
                        Remove current dish photograph
                    </label>
                @endif
            </div>
        </div>
    </section>

    <section class="{{ $panelClass }}" aria-labelledby="chef-media-heading">
        <div class="mb-6">
            <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500 dark:text-slate-500">3. Photographs & links</p>
            <h2 id="chef-media-heading" class="mt-1 text-lg font-light text-slate-950 dark:text-white">The essential public media</h2>
            <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-400">A portrait is essential. A working photograph, small gallery and professional links are optional.</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                <p class="{{ $labelClass }}">Chef portrait</p>
                <div class="mt-3 flex gap-4">
                    <div class="h-40 w-32 shrink-0 overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-900">
                        @if ($chef->portraitUrl())
                            <img src="{{ $chef->portraitUrl() }}" alt="Current Chef portrait" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full items-center justify-center text-xs text-slate-500">4:5 portrait</div>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <input type="file" name="portrait_image" accept="image/jpeg,image/png,image/webp" class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-medium file:text-slate-700 dark:text-slate-400 dark:file:bg-slate-900 dark:file:text-slate-300">
                        <p class="{{ $helpClass }}">Vertical 4:5, up to 8 MB. Required before publishing.</p>
                        @error('portrait_image')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                        @if ($chef->portrait_image_path)
                            <label class="mt-3 flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                                <input type="hidden" name="remove_portrait_image" value="0">
                                <input type="checkbox" name="remove_portrait_image" value="1" @checked(old('remove_portrait_image')) class="rounded border-slate-300 text-slate-950 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-900">
                                Remove current portrait
                            </label>
                        @endif
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                <p class="{{ $labelClass }}">Chef at work / kitchen photograph</p>
                <div class="mt-3 aspect-[16/9] overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-900">
                    @if ($chef->workingImageUrl())
                        <img src="{{ $chef->workingImageUrl() }}" alt="Current working photograph" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full items-center justify-center text-xs text-slate-500">Optional horizontal image</div>
                    @endif
                </div>
                <input type="file" name="hero_image" accept="image/jpeg,image/png,image/webp" class="mt-3 block w-full text-xs text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-medium file:text-slate-700 dark:text-slate-400 dark:file:bg-slate-900 dark:file:text-slate-300">
                @error('hero_image')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                @if ($chef->hero_image_path)
                    <label class="mt-3 flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                        <input type="hidden" name="remove_hero_image" value="0">
                        <input type="checkbox" name="remove_hero_image" value="1" @checked(old('remove_hero_image')) class="rounded border-slate-300 text-slate-950 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-900">
                        Remove current working photograph
                    </label>
                @endif
            </div>
        </div>

        <div class="mt-6 border-t border-slate-200 pt-6 dark:border-slate-800">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                <div>
                    <label class="{{ $labelClass }}">
                        Optional gallery photographs
                        <input type="file" name="gallery_images[]" accept="image/jpeg,image/png,image/webp" multiple class="mt-2 block w-full text-xs text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-medium file:text-slate-700 dark:text-slate-400 dark:file:bg-slate-900 dark:file:text-slate-300">
                        <p class="{{ $helpClass }}">Up to four public gallery images. Existing additional images are preserved but only the first four are shown.</p>
                        @error('gallery_images')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                        @error('gallery_images.*')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                    </label>

                    @if ($galleryPaths->isNotEmpty())
                        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                            @foreach ($galleryPaths as $galleryPath)
                                @php
                                    $galleryUrl = str_starts_with($galleryPath, 'http://') || str_starts_with($galleryPath, 'https://') || str_starts_with($galleryPath, '/')
                                        ? $galleryPath
                                        : \Illuminate\Support\Facades\Storage::disk('public')->url($galleryPath);
                                @endphp
                                <label class="group relative block aspect-[4/3] overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-900">
                                    <img src="{{ $galleryUrl }}" alt="Chef gallery photograph" class="h-full w-full object-cover">
                                    <span class="absolute inset-x-0 bottom-0 flex items-center gap-2 bg-black/60 px-2 py-1.5 text-[11px] text-white">
                                        <input type="checkbox" name="remove_gallery_paths[]" value="{{ $galleryPath }}" @checked(in_array($galleryPath, old('remove_gallery_paths', []), true)) class="rounded border-white/60 bg-transparent text-white focus:ring-white">
                                        Remove
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="grid content-start gap-5 sm:grid-cols-2">
                    <label class="{{ $labelClass }} sm:col-span-2">
                        Photography credit
                        <input type="text" name="photographer_credit" value="{{ old('photographer_credit', $chef->photographer_credit) }}" maxlength="200" class="{{ $inputClass }}">
                        @error('photographer_credit')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                    </label>
                    <label class="{{ $labelClass }} sm:col-span-2">
                        Website or restaurant page
                        <input type="url" name="website_url" value="{{ old('website_url', $chef->website_url) }}" placeholder="https://" class="{{ $inputClass }}">
                        @error('website_url')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                    </label>
                    <label class="{{ $labelClass }}">
                        Instagram
                        <input type="url" name="instagram_url" value="{{ old('instagram_url', $chef->instagram_url) }}" placeholder="https://" class="{{ $inputClass }}">
                        @error('instagram_url')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                    </label>
                    <label class="{{ $labelClass }}">
                        LinkedIn
                        <input type="url" name="linkedin_url" value="{{ old('linkedin_url', $chef->linkedin_url) }}" placeholder="https://" class="{{ $inputClass }}">
                        @error('linkedin_url')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                    </label>
                </div>
            </div>
        </div>
    </section>

    <section class="{{ $panelClass }}" aria-labelledby="chef-publishing-heading">
        <div class="mb-6">
            <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500 dark:text-slate-500">4. Publishing</p>
            <h2 id="chef-publishing-heading" class="mt-1 text-lg font-light text-slate-950 dark:text-white">Approval and manual homepage selection</h2>
        </div>

        <div class="grid gap-5 sm:grid-cols-3">
            <label class="{{ $labelClass }}">
                Status <span class="text-red-600">*</span>
                <select name="status" required class="{{ $inputClass }}" data-chef-status>
                    @foreach (\App\Models\Chef::STATUSES as $option)
                        <option value="{{ $option }}" @selected(old('status', $chef->status) === $option)>{{ ucfirst($option) }}</option>
                    @endforeach
                </select>
                @error('status')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>

            <label class="{{ $labelClass }}">
                Directory display order
                <input type="number" name="sort_order" min="0" max="100000" value="{{ old('sort_order', $chef->sort_order ?? 0) }}" class="{{ $inputClass }}">
                @error('sort_order')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>

            <label class="{{ $labelClass }}">
                Publication date
                <input type="datetime-local" name="published_at" value="{{ old('published_at', $chef->published_at?->format('Y-m-d\TH:i')) }}" class="{{ $inputClass }}">
                <p class="{{ $helpClass }}">Leave blank to publish immediately.</p>
                @error('published_at')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            <label class="flex cursor-pointer gap-3 rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                <input type="hidden" name="content_and_images_approved" value="0">
                <input type="checkbox" name="content_and_images_approved" value="1" @checked($approvalConfirmed) class="mt-0.5 rounded border-slate-300 text-slate-950 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-900">
                <span>
                    <span class="block text-sm font-medium text-slate-950 dark:text-white">Chef profile and images approved</span>
                    <span class="mt-1 block text-xs leading-5 text-slate-500">Confirm that the Chef has approved the final profile, organisation mention and supplied photographs for Bandara Kitchen publication.</span>
                    @error('content_and_images_approved')<span class="{{ $errorClass }} block">{{ $message }}</span>@enderror
                </span>
            </label>

            <label class="flex gap-3 rounded-lg border border-slate-200 p-4 dark:border-slate-800" data-chef-feature-label>
                <input type="hidden" name="feature_on_homepage" value="0">
                <input type="checkbox" name="feature_on_homepage" value="1" @checked(old('feature_on_homepage', $chef->isHomepageFeaturedSelection())) class="mt-0.5 rounded border-slate-300 text-slate-950 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-900" data-chef-feature-checkbox>
                <span>
                    <span class="block text-sm font-medium text-slate-950 dark:text-white">Feature on homepage</span>
                    <span class="mt-1 block text-xs leading-5 text-slate-500">Manual launch mode. Selecting this published Chef automatically replaces the previous homepage Chef.</span>
                    @error('feature_on_homepage')<span class="{{ $errorClass }} block">{{ $message }}</span>@enderror
                </span>
            </label>
        </div>
    </section>

    <div class="flex flex-col-reverse gap-3 rounded-xl border border-slate-200/80 bg-white p-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800 dark:bg-slate-950">
        <p class="text-xs leading-5 text-slate-500">
            @if ($chef->exists)
                Last updated {{ $chef->updated_at?->format('d M Y, H:i') }}. Legacy profile and recipe data remains stored but is not shown in this simplified form.
            @else
                Start as Draft and publish after the portrait, story, signature dish and approval are complete.
            @endif
        </p>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.kitchen.chefs.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:border-slate-500 dark:border-slate-700 dark:text-slate-300 dark:hover:border-slate-500">Cancel</a>
            <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-slate-950 px-5 py-2 text-sm font-medium text-white hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200">
                {{ $chef->exists ? 'Save Chef' : 'Create Chef' }}
            </button>
        </div>
    </div>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-chef-simple-form]').forEach(function (root) {
                const statusSelect = root.querySelector('[data-chef-status]');
                const featureCheckbox = root.querySelector('[data-chef-feature-checkbox]');
                const featureLabel = root.querySelector('[data-chef-feature-label]');

                function syncFeatureAvailability() {
                    if (!statusSelect || !featureCheckbox) return;
                    const canFeature = statusSelect.value === 'published';
                    if (!canFeature) featureCheckbox.checked = false;
                    featureCheckbox.disabled = !canFeature;
                    featureLabel?.classList.toggle('opacity-60', !canFeature);
                }

                statusSelect?.addEventListener('change', syncFeatureAvailability);
                syncFeatureAvailability();
            });
        });
    </script>
@endonce
