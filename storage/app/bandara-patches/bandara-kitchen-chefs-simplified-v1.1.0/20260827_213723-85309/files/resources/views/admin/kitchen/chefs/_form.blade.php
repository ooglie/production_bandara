@php
    /** @var \App\Models\Chef $chef */
    $inputClass = 'mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-slate-500 focus:ring-0 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:placeholder:text-slate-600';
    $labelClass = 'block text-xs font-medium uppercase tracking-[0.12em] text-slate-600 dark:text-slate-400';
    $helpClass = 'mt-1 text-xs leading-5 text-slate-500 dark:text-slate-500';
    $errorClass = 'mt-1 text-xs text-red-700 dark:text-red-400';
    $panelClass = 'rounded-xl border border-slate-200/80 bg-white p-5 sm:p-6 dark:border-slate-800 dark:bg-slate-950';

    $selectedRecipeIds = collect(old('recipes', $chef->exists ? $chef->recipes->pluck('id')->all() : []))
        ->map(fn ($id) => (int) $id)
        ->filter()
        ->values();
    $selectedFeaturedRecipeId = old('featured_recipe_id', $chef->featured_recipe_id);
    $specialtiesText = old('specialties_text', collect($chef->specialtyList())->implode("\n"));
    $qa = $chef->qa ?? [];
    $galleryPaths = collect($chef->gallery_image_paths ?? [])->filter()->values();
    $activeTab = old('_chef_tab', 'basic');
    $validTabs = ['basic', 'story', 'recipes', 'media', 'approval'];
    if (! in_array($activeTab, $validTabs, true)) $activeTab = 'basic';
@endphp

@if ($errors->any())
    <div class="mb-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-300" role="alert">
        <p class="font-medium">Please correct the highlighted information.</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<div data-chef-form-tabs>
    <input type="hidden" name="_chef_tab" value="{{ $activeTab }}" data-chef-active-tab>

    <div class="mb-5 overflow-x-auto border-b border-slate-200 dark:border-slate-800" role="tablist" aria-label="Chef profile sections">
        <div class="flex min-w-max gap-6">
            @foreach ([
                'basic' => '1. Basic Details',
                'story' => '2. Chef Story',
                'recipes' => '3. Recipes',
                'media' => '4. Photographs',
                'approval' => '5. Permissions & Approval',
            ] as $tabKey => $tabLabel)
                <button type="button"
                        role="tab"
                        id="chef-tab-{{ $tabKey }}"
                        aria-controls="chef-panel-{{ $tabKey }}"
                        aria-selected="{{ $activeTab === $tabKey ? 'true' : 'false' }}"
                        data-chef-tab="{{ $tabKey }}"
                        class="border-b-2 px-1 pb-3 text-sm transition {{ $activeTab === $tabKey ? 'border-slate-950 font-medium text-slate-950 dark:border-white dark:text-white' : 'border-transparent text-slate-500 hover:text-slate-900 dark:text-slate-500 dark:hover:text-slate-200' }}">
                    {{ $tabLabel }}
                </button>
            @endforeach
        </div>
    </div>

    <section id="chef-panel-basic" role="tabpanel" aria-labelledby="chef-tab-basic" data-chef-panel="basic" @if ($activeTab !== 'basic') hidden @endif class="{{ $panelClass }}">
        <div class="mb-6">
            <h2 class="text-lg font-light text-slate-950 dark:text-white">Basic professional profile</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Public identity, current role and private collaboration contact information.</p>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <label class="{{ $labelClass }}">
                Public/display name <span class="text-red-600">*</span>
                <input type="text" name="display_name" value="{{ old('display_name', $chef->display_name) }}" required maxlength="150" class="{{ $inputClass }}">
                @error('display_name')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>

            <label class="{{ $labelClass }}">
                Page slug
                <input type="text" name="slug" value="{{ old('slug', $chef->slug) }}" maxlength="180" placeholder="Created automatically from the name" class="{{ $inputClass }}">
                <p class="{{ $helpClass }}">Lowercase letters, numbers and hyphens only. Leave blank when creating.</p>
                @error('slug')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>

            <label class="{{ $labelClass }}">
                Professional title <span class="text-red-600">*</span>
                <input type="text" name="professional_title" value="{{ old('professional_title', $chef->professional_title) }}" required maxlength="180" placeholder="Executive Chef" class="{{ $inputClass }}">
                @error('professional_title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>

            <label class="{{ $labelClass }}">
                Restaurant, hotel or company
                <input type="text" name="organisation_name" value="{{ old('organisation_name', $chef->organisation_name) }}" maxlength="200" class="{{ $inputClass }}">
                <p class="{{ $helpClass }}">Displayed publicly only when restaurant/company mention approval is recorded.</p>
                @error('organisation_name')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>

            <label class="{{ $labelClass }}">
                City <span class="text-red-600">*</span>
                <input type="text" name="city" value="{{ old('city', $chef->city) }}" required maxlength="120" class="{{ $inputClass }}">
                @error('city')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>

            <label class="{{ $labelClass }}">
                Country
                <input type="text" name="country" value="{{ old('country', $chef->country ?: 'India') }}" maxlength="120" class="{{ $inputClass }}">
                @error('country')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>

            <label class="{{ $labelClass }}">
                Years in the industry
                <input type="number" min="0" max="80" name="years_experience" value="{{ old('years_experience', $chef->years_experience) }}" class="{{ $inputClass }}">
                @error('years_experience')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>

            <label class="{{ $labelClass }}">
                Legal name <span class="normal-case tracking-normal text-slate-400">(private)</span>
                <input type="text" name="legal_name" value="{{ old('legal_name', $chef->legal_name) }}" maxlength="180" class="{{ $inputClass }}">
                @error('legal_name')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>
        </div>

        <div class="mt-8 border-t border-slate-200 pt-6 dark:border-slate-800">
            <h3 class="text-sm font-medium text-slate-950 dark:text-white">Private collaboration contact</h3>
            <p class="mt-1 text-xs text-slate-500">These fields never appear on the public chef page.</p>
            <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <label class="{{ $labelClass }}">
                    Contact email
                    <input type="email" name="contact_email" value="{{ old('contact_email', $chef->contact_email) }}" class="{{ $inputClass }}">
                    @error('contact_email')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </label>
                <label class="{{ $labelClass }}">
                    Contact phone
                    <input type="text" name="contact_phone" value="{{ old('contact_phone', $chef->contact_phone) }}" maxlength="40" class="{{ $inputClass }}">
                    @error('contact_phone')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </label>
                <label class="{{ $labelClass }}">
                    Assistant / PR contact
                    <input type="text" name="contact_person_name" value="{{ old('contact_person_name', $chef->contact_person_name) }}" maxlength="180" class="{{ $inputClass }}">
                    @error('contact_person_name')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </label>
                <label class="{{ $labelClass }}">
                    Preferred contact method
                    <select name="preferred_contact_method" class="{{ $inputClass }}">
                        <option value="">Select</option>
                        @foreach (['email' => 'Email', 'phone' => 'Phone', 'whatsapp' => 'WhatsApp', 'assistant' => 'Assistant / PR'] as $value => $text)
                            <option value="{{ $value }}" @selected(old('preferred_contact_method', $chef->preferred_contact_method) === $value)>{{ $text }}</option>
                        @endforeach
                    </select>
                    @error('preferred_contact_method')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </label>
                <label class="{{ $labelClass }} sm:col-span-2">
                    Best time to contact
                    <input type="text" name="best_contact_time" value="{{ old('best_contact_time', $chef->best_contact_time) }}" maxlength="120" class="{{ $inputClass }}">
                    @error('best_contact_time')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </label>
            </div>
            <label class="{{ $labelClass }} mt-5">
                Internal notes <span class="normal-case tracking-normal text-slate-400">(private)</span>
                <textarea name="internal_notes" rows="4" class="{{ $inputClass }}">{{ old('internal_notes', $chef->internal_notes) }}</textarea>
                @error('internal_notes')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>
        </div>
    </section>

    <section id="chef-panel-story" role="tabpanel" aria-labelledby="chef-tab-story" data-chef-panel="story" @if ($activeTab !== 'story') hidden @endif class="{{ $panelClass }}">
        <div class="mb-6">
            <h2 class="text-lg font-light text-slate-950 dark:text-white">Chef story and editorial profile</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Bandara can prepare and edit this copy, then record final approval in the last tab.</p>
        </div>

        <div class="space-y-5">
            <label class="{{ $labelClass }}">
                Short homepage introduction
                <textarea name="short_intro" rows="3" maxlength="700" class="{{ $inputClass }}">{{ old('short_intro', $chef->short_intro) }}</textarea>
                <p class="{{ $helpClass }}">Required before publishing. Recommended length: 30–60 words.</p>
                @error('short_intro')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>

            <label class="{{ $labelClass }}">
                Full biography
                <textarea name="biography" rows="9" class="{{ $inputClass }}">{{ old('biography', $chef->biography) }}</textarea>
                <p class="{{ $helpClass }}">Required before publishing. Recommended length: 200–350 words.</p>
                @error('biography')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>

            <label class="{{ $labelClass }}">
                Cooking philosophy
                <textarea name="cooking_philosophy" rows="5" class="{{ $inputClass }}">{{ old('cooking_philosophy', $chef->cooking_philosophy) }}</textarea>
                @error('cooking_philosophy')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>

            <label class="{{ $labelClass }}">
                Chef quote
                <textarea name="quote" rows="3" maxlength="700" class="{{ $inputClass }}">{{ old('quote', $chef->quote) }}</textarea>
                @error('quote')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>

            <div class="grid gap-5 sm:grid-cols-2">
                <label class="{{ $labelClass }}">
                    Specialties
                    <textarea name="specialties_text" rows="5" placeholder="Seafood&#10;European cuisine&#10;French technique" class="{{ $inputClass }}">{{ $specialtiesText }}</textarea>
                    <p class="{{ $helpClass }}">One per line or separated with commas.</p>
                    @error('specialties_text')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </label>
                <label class="{{ $labelClass }}">
                    Signature dishes
                    <textarea name="signature_dishes" rows="5" class="{{ $inputClass }}">{{ old('signature_dishes', $chef->signature_dishes) }}</textarea>
                    @error('signature_dishes')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </label>
                <label class="{{ $labelClass }}">
                    Culinary education or training
                    <textarea name="culinary_training" rows="5" class="{{ $inputClass }}">{{ old('culinary_training', $chef->culinary_training) }}</textarea>
                    @error('culinary_training')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </label>
                <label class="{{ $labelClass }}">
                    Career highlights
                    <textarea name="career_highlights" rows="5" class="{{ $inputClass }}">{{ old('career_highlights', $chef->career_highlights) }}</textarea>
                    @error('career_highlights')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </label>
            </div>

            <label class="{{ $labelClass }}">
                Awards and recognition
                <textarea name="awards" rows="4" class="{{ $inputClass }}">{{ old('awards', $chef->awards) }}</textarea>
                <p class="{{ $helpClass }}">Optional. A profile does not require formal awards.</p>
                @error('awards')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>
        </div>

        <div class="mt-8 border-t border-slate-200 pt-6 dark:border-slate-800">
            <h3 class="text-sm font-medium text-slate-950 dark:text-white">Five-question chef Q&amp;A</h3>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <label class="{{ $labelClass }}">
                    Which ingredient do you never compromise on?
                    <textarea name="qa_favourite_ingredient" rows="4" class="{{ $inputClass }}">{{ old('qa_favourite_ingredient', $qa['Which ingredient do you never compromise on?'] ?? '') }}</textarea>
                    @error('qa_favourite_ingredient')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </label>
                <label class="{{ $labelClass }}">
                    What was the first dish you learned to cook?
                    <textarea name="qa_first_dish" rows="4" class="{{ $inputClass }}">{{ old('qa_first_dish', $qa['What was the first dish you learned to cook?'] ?? '') }}</textarea>
                    @error('qa_first_dish')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </label>
                <label class="{{ $labelClass }}">
                    Favourite meal when not working?
                    <textarea name="qa_off_duty_meal" rows="4" class="{{ $inputClass }}">{{ old('qa_off_duty_meal', $qa['What is your favourite meal when you are not working?'] ?? '') }}</textarea>
                    @error('qa_off_duty_meal')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </label>
                <label class="{{ $labelClass }}">
                    Advice for a young cook?
                    <textarea name="qa_young_cook_advice" rows="4" class="{{ $inputClass }}">{{ old('qa_young_cook_advice', $qa['What advice would you give a young cook?'] ?? '') }}</textarea>
                    @error('qa_young_cook_advice')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </label>
                <label class="{{ $labelClass }} sm:col-span-2">
                    Which technique should every home cook understand?
                    <textarea name="qa_home_cook_technique" rows="4" class="{{ $inputClass }}">{{ old('qa_home_cook_technique', $qa['Which technique should every home cook understand?'] ?? '') }}</textarea>
                    @error('qa_home_cook_technique')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </label>
            </div>
        </div>
    </section>

    <section id="chef-panel-recipes" role="tabpanel" aria-labelledby="chef-tab-recipes" data-chef-panel="recipes" @if ($activeTab !== 'recipes') hidden @endif class="{{ $panelClass }}">
        <div class="mb-6">
            <h2 class="text-lg font-light text-slate-950 dark:text-white">Recipes by this chef</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Link existing Bandara recipes without changing the current recipe records or homepage recipe-refresh logic.</p>
        </div>

        @if ($recipes->isNotEmpty())
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($recipes as $recipe)
                    @php $recipeTitle = \App\Support\BandaraKitchen::recipeTitle($recipe); @endphp
                    <label class="flex cursor-pointer gap-3 rounded-lg border border-slate-200 p-3 text-sm hover:border-slate-400 dark:border-slate-800 dark:hover:border-slate-600">
                        <input type="checkbox" name="recipes[]" value="{{ $recipe->getKey() }}" @checked($selectedRecipeIds->contains((int) $recipe->getKey())) class="mt-0.5 rounded border-slate-300 text-slate-950 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-900">
                        <span>
                            <span class="block font-medium text-slate-900 dark:text-slate-100">{{ $recipeTitle }}</span>
                            <span class="mt-1 block text-xs text-slate-500">Recipe #{{ $recipe->getKey() }}{{ $recipe->is_active ? '' : ' · inactive' }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('recipes')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            @error('recipes.*')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror

            <label class="{{ $labelClass }} mt-6 max-w-xl">
                Featured recipe for this chef
                <select name="featured_recipe_id" class="{{ $inputClass }}">
                    <option value="">Use the first linked recipe</option>
                    @foreach ($recipes as $recipe)
                        <option value="{{ $recipe->getKey() }}" @selected((string) $selectedFeaturedRecipeId === (string) $recipe->getKey())>{{ \App\Support\BandaraKitchen::recipeTitle($recipe) }}</option>
                    @endforeach
                </select>
                <p class="{{ $helpClass }}">Selecting a featured recipe automatically links it to the chef.</p>
                @error('featured_recipe_id')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>
        @else
            <div class="rounded-lg border border-slate-200 px-4 py-8 text-center text-sm text-slate-600 dark:border-slate-800 dark:text-slate-400">
                No recipe records are available. Create recipes in the existing Recipes module first.
            </div>
        @endif
    </section>

    <section id="chef-panel-media" role="tabpanel" aria-labelledby="chef-tab-media" data-chef-panel="media" @if ($activeTab !== 'media') hidden @endif class="{{ $panelClass }}">
        <div class="mb-6">
            <h2 class="text-lg font-light text-slate-950 dark:text-white">Photographs and professional links</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Upload original high-resolution files. Bandara will crop them for the 4:5 portrait and horizontal hero positions.</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                <h3 class="text-sm font-medium text-slate-950 dark:text-white">Primary portrait</h3>
                @if ($chef->portraitUrl())
                    <img src="{{ $chef->portraitUrl() }}" alt="Current portrait" class="mt-3 h-56 w-44 rounded-lg object-cover">
                    <label class="mt-3 flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                        <input type="hidden" name="remove_portrait_image" value="0">
                        <input type="checkbox" name="remove_portrait_image" value="1" @checked(old('remove_portrait_image')) class="rounded border-slate-300 text-slate-950 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-900">
                        Remove current portrait
                    </label>
                @endif
                <label class="{{ $labelClass }} mt-4">
                    {{ $chef->portrait_image_path ? 'Replace portrait' : 'Upload portrait' }}
                    <input type="file" name="portrait_image" accept="image/jpeg,image/png,image/webp" class="{{ $inputClass }}">
                    <p class="{{ $helpClass }}">Required before publishing. Recommended: vertical 4:5, at least 1600 × 2000 px. Maximum 8 MB.</p>
                    @error('portrait_image')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </label>
            </div>

            <div class="rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                <h3 class="text-sm font-medium text-slate-950 dark:text-white">Homepage / hero image</h3>
                @if ($chef->hero_image_path)
                    <img src="{{ $chef->heroImageUrl() }}" alt="Current hero" class="mt-3 aspect-[4/3] w-full max-w-sm rounded-lg object-cover">
                    <label class="mt-3 flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                        <input type="hidden" name="remove_hero_image" value="0">
                        <input type="checkbox" name="remove_hero_image" value="1" @checked(old('remove_hero_image')) class="rounded border-slate-300 text-slate-950 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-900">
                        Remove current hero image
                    </label>
                @endif
                <label class="{{ $labelClass }} mt-4">
                    {{ $chef->hero_image_path ? 'Replace hero image' : 'Upload hero image' }}
                    <input type="file" name="hero_image" accept="image/jpeg,image/png,image/webp" class="{{ $inputClass }}">
                    <p class="{{ $helpClass }}">Optional. A horizontal 3:2 image is preferred; the portrait is used as fallback. Maximum 12 MB.</p>
                    @error('hero_image')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </label>
            </div>
        </div>

        <div class="mt-6 rounded-lg border border-slate-200 p-4 dark:border-slate-800">
            <h3 class="text-sm font-medium text-slate-950 dark:text-white">Kitchen gallery</h3>
            @if ($galleryPaths->isNotEmpty())
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($galleryPaths as $path)
                        @php
                            $galleryUrl = str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')
                                ? $path
                                : \Illuminate\Support\Facades\Storage::disk('public')->url($path);
                        @endphp
                        <label class="block rounded-lg border border-slate-200 p-2 dark:border-slate-800">
                            <img src="{{ $galleryUrl }}" alt="Current gallery image" class="aspect-[4/3] w-full rounded-md object-cover">
                            <span class="mt-2 flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                                <input type="checkbox" name="remove_gallery_paths[]" value="{{ $path }}" @checked(in_array($path, old('remove_gallery_paths', []), true)) class="rounded border-slate-300 text-slate-950 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-900">
                                Remove image
                            </span>
                        </label>
                    @endforeach
                </div>
            @endif
            <label class="{{ $labelClass }} mt-4">
                Add gallery photographs
                <input type="file" name="gallery_images[]" multiple accept="image/jpeg,image/png,image/webp" class="{{ $inputClass }}">
                <p class="{{ $helpClass }}">Up to 10 files per save, maximum 12 MB each.</p>
                @error('gallery_images')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                @error('gallery_images.*')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>
        </div>

        <div class="mt-6 grid gap-5 sm:grid-cols-2">
            <label class="{{ $labelClass }} sm:col-span-2">
                Photographer credit
                <input type="text" name="photographer_credit" value="{{ old('photographer_credit', $chef->photographer_credit) }}" maxlength="200" class="{{ $inputClass }}">
                @error('photographer_credit')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>
            <label class="{{ $labelClass }}">
                Professional / restaurant website
                <input type="url" name="website_url" value="{{ old('website_url', $chef->website_url) }}" placeholder="https://" class="{{ $inputClass }}">
                @error('website_url')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>
            <label class="{{ $labelClass }}">
                Instagram URL
                <input type="url" name="instagram_url" value="{{ old('instagram_url', $chef->instagram_url) }}" placeholder="https://" class="{{ $inputClass }}">
                @error('instagram_url')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>
            <label class="{{ $labelClass }}">
                LinkedIn URL
                <input type="url" name="linkedin_url" value="{{ old('linkedin_url', $chef->linkedin_url) }}" placeholder="https://" class="{{ $inputClass }}">
                @error('linkedin_url')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>
        </div>
    </section>

    <section id="chef-panel-approval" role="tabpanel" aria-labelledby="chef-tab-approval" data-chef-panel="approval" @if ($activeTab !== 'approval') hidden @endif class="{{ $panelClass }}">
        <div class="mb-6">
            <h2 class="text-lg font-light text-slate-950 dark:text-white">Permissions, approval and publication</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Publishing is blocked until the essential rights and final content approval are recorded.</p>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ([
                'image_rights_confirmed' => ['Image rights confirmed', 'Chef owns or is authorised to provide every photograph.'],
                'profile_use_approved' => ['Name and likeness approved', 'Bandara may publish the chef profile and approved photographs.'],
                'restaurant_mention_approved' => ['Restaurant/company mention approved', 'Current organisation may be named on public pages.'],
                'recipe_use_approved' => ['Recipe publication approved', 'Required before publishing linked recipes under this chef.'],
                'social_promotion_approved' => ['Social/email promotion approved', 'Bandara may promote approved extracts and images.'],
            ] as $field => [$heading, $description])
                <label class="flex cursor-pointer gap-3 rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                    <input type="hidden" name="{{ $field }}" value="0">
                    <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $chef->{$field})) class="mt-0.5 rounded border-slate-300 text-slate-950 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-900">
                    <span>
                        <span class="block text-sm font-medium text-slate-950 dark:text-white">{{ $heading }}</span>
                        <span class="mt-1 block text-xs leading-5 text-slate-500">{{ $description }}</span>
                        @error($field)<span class="{{ $errorClass }} block">{{ $message }}</span>@enderror
                    </span>
                </label>
            @endforeach
        </div>

        <div class="mt-6 grid gap-5 sm:grid-cols-2">
            <label class="{{ $labelClass }}">
                Final content approval date
                <input type="datetime-local" name="content_approved_at" value="{{ old('content_approved_at', $chef->content_approved_at?->format('Y-m-d\TH:i')) }}" class="{{ $inputClass }}">
                <p class="{{ $helpClass }}">Required before publishing.</p>
                @error('content_approved_at')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>
            <label class="{{ $labelClass }}">
                Public publication date
                <input type="datetime-local" name="published_at" value="{{ old('published_at', $chef->published_at?->format('Y-m-d\TH:i')) }}" class="{{ $inputClass }}">
                <p class="{{ $helpClass }}">Leave blank to publish immediately when status is Published.</p>
                @error('published_at')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </label>
        </div>

        <label class="{{ $labelClass }} mt-5">
            Approval notes and restrictions
            <textarea name="approval_notes" rows="4" class="{{ $inputClass }}">{{ old('approval_notes', $chef->approval_notes) }}</textarea>
            @error('approval_notes')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </label>

        <div class="mt-8 grid gap-5 border-t border-slate-200 pt-6 sm:grid-cols-3 dark:border-slate-800">
            <label class="{{ $labelClass }}">
                Workflow status <span class="text-red-600">*</span>
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
            <label class="flex gap-3 rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                <input type="hidden" name="feature_on_homepage" value="0">
                <input type="checkbox" name="feature_on_homepage" value="1" @checked(old('feature_on_homepage', $chef->isHomepageFeaturedSelection())) class="mt-0.5 rounded border-slate-300 text-slate-950 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-900" data-chef-feature-checkbox>
                <span>
                    <span class="block text-sm font-medium text-slate-950 dark:text-white">Feature on homepage</span>
                    <span class="mt-1 block text-xs leading-5 text-slate-500">Manual launch mode. Selecting this published chef automatically clears the previous selection.</span>
                    @error('feature_on_homepage')<span class="{{ $errorClass }} block">{{ $message }}</span>@enderror
                </span>
            </label>
        </div>
    </section>

    <div class="mt-5 flex flex-col-reverse gap-3 rounded-xl border border-slate-200/80 bg-white p-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800 dark:bg-slate-950">
        <p class="text-xs leading-5 text-slate-500">
            @if ($chef->exists)
                Last updated {{ $chef->updated_at?->format('d M Y, H:i') }}.
            @else
                New profiles are saved as drafts unless you explicitly select another status.
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
                document.querySelectorAll('[data-chef-form-tabs]').forEach(function (root) {
                    const buttons = Array.from(root.querySelectorAll('[data-chef-tab]'));
                    const panels = Array.from(root.querySelectorAll('[data-chef-panel]'));
                    const hiddenInput = root.querySelector('[data-chef-active-tab]');

                    function activate(name, focusButton) {
                        buttons.forEach(function (button) {
                            const active = button.dataset.chefTab === name;
                            button.setAttribute('aria-selected', active ? 'true' : 'false');
                            button.classList.toggle('border-slate-950', active);
                            button.classList.toggle('dark:border-white', active);
                            button.classList.toggle('font-medium', active);
                            button.classList.toggle('text-slate-950', active);
                            button.classList.toggle('dark:text-white', active);
                            button.classList.toggle('border-transparent', !active);
                            button.classList.toggle('text-slate-500', !active);
                            button.classList.toggle('dark:text-slate-500', !active);
                            button.classList.toggle('hover:text-slate-900', !active);
                            button.classList.toggle('dark:hover:text-slate-200', !active);
                            if (active && focusButton) button.focus();
                        });

                        panels.forEach(function (panel) {
                            panel.hidden = panel.dataset.chefPanel !== name;
                        });

                        if (hiddenInput) hiddenInput.value = name;
                    }

                    const statusSelect = root.querySelector('[data-chef-status]');
                    const featureCheckbox = root.querySelector('[data-chef-feature-checkbox]');
                    function syncFeatureAvailability() {
                        if (!statusSelect || !featureCheckbox) return;
                        const canFeature = statusSelect.value === 'published';
                        if (!canFeature) featureCheckbox.checked = false;
                        featureCheckbox.disabled = !canFeature;
                        featureCheckbox.closest('label')?.classList.toggle('opacity-60', !canFeature);
                    }
                    statusSelect?.addEventListener('change', syncFeatureAvailability);
                    syncFeatureAvailability();

                    buttons.forEach(function (button) {
                        button.addEventListener('click', function () {
                            activate(button.dataset.chefTab, false);
                        });

                        button.addEventListener('keydown', function (event) {
                            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
                            event.preventDefault();
                            const current = buttons.indexOf(button);
                            let target = current;
                            if (event.key === 'ArrowLeft') target = (current - 1 + buttons.length) % buttons.length;
                            if (event.key === 'ArrowRight') target = (current + 1) % buttons.length;
                            if (event.key === 'Home') target = 0;
                            if (event.key === 'End') target = buttons.length - 1;
                            activate(buttons[target].dataset.chefTab, true);
                        });
                    });
                });
            });
        </script>
@endonce
