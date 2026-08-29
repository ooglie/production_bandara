<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Kitchen;

use App\Http\Controllers\Controller;
use App\Models\Chef;
use App\Models\Recipe;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class ChefController extends Controller
{
    public function index(Request $request): View
    {
        $query = Chef::query()
            ->withCount('recipes')
            ->orderByDesc('homepage_feature_slot')
            ->orderBy('sort_order')
            ->orderBy('display_name');

        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));

        if ($search !== '') {
            $query->where(function (Builder $chefQuery) use ($search): void {
                $chefQuery
                    ->where('display_name', 'like', "%{$search}%")
                    ->orWhere('professional_title', 'like', "%{$search}%")
                    ->orWhere('organisation_name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if (in_array($status, Chef::STATUSES, true)) {
            $query->where('status', $status);
        }

        $chefs = $query->paginate(20)->withQueryString();
        $featuredChef = Chef::query()->homepageFeatured()->first();

        return view('admin.kitchen.chefs.index', compact('chefs', 'featuredChef', 'search', 'status'));
    }

    public function create(): View
    {
        $chef = new Chef([
            'country' => 'India',
            'status' => Chef::STATUS_DRAFT,
            'sort_order' => 0,
        ]);

        $recipes = $this->availableRecipes();

        return view('admin.kitchen.chefs.create', compact('chef', 'recipes'));
    }

    public function store(Request $request): RedirectResponse
    {
        [$data, $recipeIds, $featureOnHomepage] = $this->validatedPayload($request);

        $staffId = Auth::guard('staff')->id();
        $data['created_by_id'] = $staffId;
        $data['updated_by_id'] = $staffId;

        $newPaths = [];

        try {
            /** @var Chef $chef */
            $chef = DB::transaction(function () use ($request, $data, $recipeIds, $featureOnHomepage, &$newPaths): Chef {
                $this->lockHomepageFeatureRows();
                $chef = Chef::query()->create($data);

                $media = $this->storeUploadedMedia($request, $chef, $newPaths);
                $chef->fill($media)->save();

                $chef->recipes()->sync($this->recipeSyncPayload($recipeIds, $chef->featured_recipe_id));
                $this->applyHomepageSelection($chef, $featureOnHomepage);

                return $chef->fresh(['recipes', 'featuredRecipe']);
            });
        } catch (Throwable $exception) {
            $this->deletePublicPaths($newPaths);
            throw $exception;
        }

        return redirect()
            ->route('admin.kitchen.chefs.edit', $chef)
            ->with('status', 'Chef profile created.');
    }

    public function edit(Chef $chef): View
    {
        $assignedRecipeIds = $chef->recipes()->pluck('recipes.id')->all();
        $recipes = $this->availableRecipes($assignedRecipeIds);
        $chef->load('recipes');

        return view('admin.kitchen.chefs.edit', compact('chef', 'recipes'));
    }

    public function update(Request $request, Chef $chef): RedirectResponse
    {
        [$data, $recipeIds, $featureOnHomepage] = $this->validatedPayload($request, $chef);
        $data['updated_by_id'] = Auth::guard('staff')->id();

        $newPaths = [];
        $pathsToDeleteAfterCommit = [];

        try {
            DB::transaction(function () use (
                $request,
                $chef,
                $data,
                $recipeIds,
                $featureOnHomepage,
                &$newPaths,
                &$pathsToDeleteAfterCommit
            ): void {
                $this->lockHomepageFeatureRows();
                $chef->refresh();

                $media = $this->storeUploadedMedia($request, $chef, $newPaths);

                if (array_key_exists('portrait_image_path', $media) && filled($chef->portrait_image_path)) {
                    $pathsToDeleteAfterCommit[] = $chef->portrait_image_path;
                }

                if (array_key_exists('hero_image_path', $media) && filled($chef->hero_image_path)) {
                    $pathsToDeleteAfterCommit[] = $chef->hero_image_path;
                }

                if ($request->boolean('remove_portrait_image') && ! array_key_exists('portrait_image_path', $media)) {
                    if (filled($chef->portrait_image_path)) {
                        $pathsToDeleteAfterCommit[] = $chef->portrait_image_path;
                    }
                    $media['portrait_image_path'] = null;
                }

                if ($request->boolean('remove_hero_image') && ! array_key_exists('hero_image_path', $media)) {
                    if (filled($chef->hero_image_path)) {
                        $pathsToDeleteAfterCommit[] = $chef->hero_image_path;
                    }
                    $media['hero_image_path'] = null;
                }

                $existingGallery = collect($chef->gallery_image_paths ?? [])
                    ->filter(fn (mixed $path): bool => is_string($path) && $path !== '')
                    ->values();

                $requestedRemovals = collect($request->input('remove_gallery_paths', []))
                    ->filter(fn (mixed $path): bool => is_string($path))
                    ->intersect($existingGallery)
                    ->values();

                $pathsToDeleteAfterCommit = array_merge(
                    $pathsToDeleteAfterCommit,
                    $requestedRemovals->all()
                );

                $newGallery = collect($media['gallery_image_paths'] ?? []);
                $media['gallery_image_paths'] = $existingGallery
                    ->diff($requestedRemovals)
                    ->concat($newGallery)
                    ->unique()
                    ->values()
                    ->all();

                $chef->fill(array_merge($data, $media))->save();
                $chef->recipes()->sync($this->recipeSyncPayload($recipeIds, $chef->featured_recipe_id));
                $this->applyHomepageSelection($chef, $featureOnHomepage);
            });
        } catch (Throwable $exception) {
            $this->deletePublicPaths($newPaths);
            throw $exception;
        }

        $this->deletePublicPaths(array_values(array_unique($pathsToDeleteAfterCommit)));

        return redirect()
            ->route('admin.kitchen.chefs.edit', $chef)
            ->with('status', 'Chef profile updated.');
    }

    public function destroy(Chef $chef): RedirectResponse
    {
        DB::transaction(function () use ($chef): void {
            $this->lockHomepageFeatureRows();
            $chef->refresh();
            $chef->forceFill(['is_featured' => false, 'homepage_feature_slot' => null])->save();
            $chef->delete();
        });

        return redirect()
            ->route('admin.kitchen.chefs.index')
            ->with('status', 'Chef profile removed. Uploaded media has been retained for audit and recovery.');
    }

    public function feature(Chef $chef): RedirectResponse
    {
        $selectedName = $chef->display_name;

        DB::transaction(function () use ($chef, &$selectedName): void {
            $this->lockHomepageFeatureRows();

            $lockedChef = Chef::query()->whereKey($chef->getKey())->firstOrFail();
            if (! $lockedChef->isPublished()) {
                throw ValidationException::withMessages([
                    'chef' => 'Only a currently published chef can be featured on the homepage.',
                ]);
            }

            Chef::query()
                ->where(function (Builder $query): void {
                    $query->where('is_featured', true)->orWhereNotNull('homepage_feature_slot');
                })
                ->update(['is_featured' => false, 'homepage_feature_slot' => null]);

            $lockedChef->forceFill(['is_featured' => true, 'homepage_feature_slot' => 1])->save();
            $selectedName = $lockedChef->display_name;
        });

        return back()->with('status', "{$selectedName} is now the manually selected homepage chef.");
    }

    public function unfeature(): RedirectResponse
    {
        DB::transaction(function (): void {
            $this->lockHomepageFeatureRows();
            Chef::query()
                ->where(function (Builder $query): void {
                    $query->where('is_featured', true)->orWhereNotNull('homepage_feature_slot');
                })
                ->update(['is_featured' => false, 'homepage_feature_slot' => null]);
        });

        return back()->with('status', 'The homepage chef selection has been cleared. The section will remain hidden until another chef is selected.');
    }

    /**
     * @return array{0: array<string, mixed>, 1: list<int>, 2: bool}
     */
    private function validatedPayload(Request $request, ?Chef $chef = null): array
    {
        $chefId = $chef?->getKey();
        $assignedRecipeIds = $chef
            ? $chef->recipes()->pluck('recipes.id')->map(fn (mixed $id): int => (int) $id)->all()
            : [];
        $allowedRecipeIds = $this->availableRecipes($assignedRecipeIds)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $validator = Validator::make($request->all(), [
            'display_name' => ['required', 'string', 'max:150'],
            'slug' => [
                'nullable',
                'string',
                'max:180',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('chefs', 'slug')->ignore($chefId),
            ],
            'professional_title' => ['required', 'string', 'max:180'],
            'organisation_name' => ['nullable', 'string', 'max:200'],
            'city' => ['required', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:80'],
            'short_intro' => ['nullable', 'string', 'max:700'],
            'biography' => ['nullable', 'string', 'max:12000'],
            'cooking_philosophy' => ['nullable', 'string', 'max:3000'],
            'quote' => ['nullable', 'string', 'max:700'],
            'specialties_text' => ['nullable', 'string', 'max:2000'],
            'signature_dishes' => ['nullable', 'string', 'max:3000'],
            'culinary_training' => ['nullable', 'string', 'max:5000'],
            'career_highlights' => ['nullable', 'string', 'max:5000'],
            'awards' => ['nullable', 'string', 'max:5000'],
            'qa_favourite_ingredient' => ['nullable', 'string', 'max:2000'],
            'qa_first_dish' => ['nullable', 'string', 'max:2000'],
            'qa_off_duty_meal' => ['nullable', 'string', 'max:2000'],
            'qa_young_cook_advice' => ['nullable', 'string', 'max:2000'],
            'qa_home_cook_technique' => ['nullable', 'string', 'max:2000'],
            'portrait_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'hero_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:12288'],
            'gallery_images' => ['nullable', 'array', 'max:10'],
            'gallery_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:12288'],
            'remove_portrait_image' => ['nullable', 'boolean'],
            'remove_hero_image' => ['nullable', 'boolean'],
            'remove_gallery_paths' => ['nullable', 'array'],
            'remove_gallery_paths.*' => ['string', 'max:500'],
            'photographer_credit' => ['nullable', 'string', 'max:200'],
            'website_url' => ['nullable', 'url:http,https', 'max:255'],
            'instagram_url' => ['nullable', 'url:http,https', 'max:255'],
            'linkedin_url' => ['nullable', 'url:http,https', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:180'],
            'contact_email' => ['nullable', 'email:rfc', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'contact_person_name' => ['nullable', 'string', 'max:180'],
            'preferred_contact_method' => ['nullable', Rule::in(['email', 'phone', 'whatsapp', 'assistant'])],
            'best_contact_time' => ['nullable', 'string', 'max:120'],
            'internal_notes' => ['nullable', 'string', 'max:10000'],
            'image_rights_confirmed' => ['nullable', 'boolean'],
            'profile_use_approved' => ['nullable', 'boolean'],
            'restaurant_mention_approved' => ['nullable', 'boolean'],
            'recipe_use_approved' => ['nullable', 'boolean'],
            'social_promotion_approved' => ['nullable', 'boolean'],
            'content_approved_at' => ['nullable', 'date', 'before_or_equal:now'],
            'approval_notes' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', Rule::in(Chef::STATUSES)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'published_at' => ['nullable', 'date'],
            'recipes' => ['nullable', 'array'],
            'recipes.*' => ['integer', 'distinct', Rule::in($allowedRecipeIds)],
            'featured_recipe_id' => ['nullable', 'integer', Rule::in($allowedRecipeIds)],
            'feature_on_homepage' => ['nullable', 'boolean'],
        ]);

        $validator->after(function ($validator) use ($request, $chef): void {
            $existingGallery = collect($chef?->gallery_image_paths ?? [])
                ->filter(fn (mixed $path): bool => is_string($path) && $path !== '');
            $removedGallery = collect($request->input('remove_gallery_paths', []))
                ->filter(fn (mixed $path): bool => is_string($path))
                ->intersect($existingGallery);
            $newGalleryCount = collect($request->file('gallery_images', []))
                ->filter(fn (mixed $file): bool => $file instanceof UploadedFile)
                ->count();

            if ($existingGallery->diff($removedGallery)->count() + $newGalleryCount > 10) {
                $validator->errors()->add('gallery_images', 'A chef profile can contain no more than 10 gallery images.');
            }

            if ($request->input('status') !== Chef::STATUS_PUBLISHED) {
                return;
            }

            $willHavePortrait = $request->hasFile('portrait_image')
                || (filled($chef?->portrait_image_path) && ! $request->boolean('remove_portrait_image'));

            if (! $willHavePortrait) {
                $validator->errors()->add('portrait_image', 'A portrait is required before publishing a chef.');
            }

            if (blank($request->input('short_intro'))) {
                $validator->errors()->add('short_intro', 'A short introduction is required before publishing.');
            }

            if (blank($request->input('biography'))) {
                $validator->errors()->add('biography', 'The chef biography is required before publishing.');
            }

            if (! $request->boolean('image_rights_confirmed')) {
                $validator->errors()->add('image_rights_confirmed', 'Image rights must be confirmed before publishing.');
            }

            if (! $request->boolean('profile_use_approved')) {
                $validator->errors()->add('profile_use_approved', 'Profile and likeness use must be approved before publishing.');
            }

            if (blank($request->input('content_approved_at'))) {
                $validator->errors()->add('content_approved_at', 'Record the final content approval date before publishing.');
            }

            $hasLinkedRecipes = ! empty($request->input('recipes', []))
                || filled($request->input('featured_recipe_id'));
            if ($hasLinkedRecipes && ! $request->boolean('recipe_use_approved')) {
                $validator->errors()->add('recipe_use_approved', 'Recipe publication permission is required when recipes are linked to a published chef.');
            }

            if ($request->boolean('feature_on_homepage')
                && ! $validator->errors()->has('published_at')
                && filled($request->input('published_at'))) {
                try {
                    if (Carbon::parse((string) $request->input('published_at'))->isFuture()) {
                        $validator->errors()->add('feature_on_homepage', 'A future-scheduled chef cannot be featured until the publication time is reached.');
                    }
                } catch (Throwable) {
                    // The normal date rule reports malformed values.
                }
            }
        });

        $validated = $validator->validate();

        $displayName = trim((string) $validated['display_name']);
        $slug = trim((string) ($validated['slug'] ?? ''));

        if ($slug === '') {
            $slug = $chef?->slug ?: $this->uniqueSlug($displayName);
        }

        $recipeIds = collect($validated['recipes'] ?? [])
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        $featuredRecipeId = isset($validated['featured_recipe_id'])
            ? (int) $validated['featured_recipe_id']
            : null;

        if ($featuredRecipeId && ! $recipeIds->contains($featuredRecipeId)) {
            $recipeIds->push($featuredRecipeId);
        }

        $specialties = collect(preg_split('/[\r\n,]+/u', (string) ($validated['specialties_text'] ?? '')) ?: [])
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->unique(fn (string $item): string => mb_strtolower($item))
            ->values()
            ->all();

        $qaMap = [
            'Which ingredient do you never compromise on?' => $validated['qa_favourite_ingredient'] ?? null,
            'What was the first dish you learned to cook?' => $validated['qa_first_dish'] ?? null,
            'What is your favourite meal when you are not working?' => $validated['qa_off_duty_meal'] ?? null,
            'What advice would you give a young cook?' => $validated['qa_young_cook_advice'] ?? null,
            'Which technique should every home cook understand?' => $validated['qa_home_cook_technique'] ?? null,
        ];

        $qa = collect($qaMap)
            ->map(fn (mixed $answer): string => trim((string) $answer))
            ->filter()
            ->all();

        $data = collect($validated)
            ->except([
                'specialties_text',
                'qa_favourite_ingredient',
                'qa_first_dish',
                'qa_off_duty_meal',
                'qa_young_cook_advice',
                'qa_home_cook_technique',
                'portrait_image',
                'hero_image',
                'gallery_images',
                'remove_portrait_image',
                'remove_hero_image',
                'remove_gallery_paths',
                'recipes',
                'feature_on_homepage',
            ])
            ->all();

        $data['display_name'] = $displayName;
        $data['slug'] = $slug;
        $data['specialties'] = $specialties;
        $data['qa'] = $qa;
        $data['featured_recipe_id'] = $featuredRecipeId;
        $data['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $data['image_rights_confirmed'] = $request->boolean('image_rights_confirmed');
        $data['profile_use_approved'] = $request->boolean('profile_use_approved');
        $data['restaurant_mention_approved'] = $request->boolean('restaurant_mention_approved');
        $data['recipe_use_approved'] = $request->boolean('recipe_use_approved');
        $data['social_promotion_approved'] = $request->boolean('social_promotion_approved');

        if ($data['status'] === Chef::STATUS_PUBLISHED && blank($data['published_at'] ?? null)) {
            $data['published_at'] = $chef?->published_at ?: now();
        }

        if ($data['status'] !== Chef::STATUS_PUBLISHED) {
            $data['is_featured'] = false;
            $data['homepage_feature_slot'] = null;
        }

        $featureOnHomepage = $data['status'] === Chef::STATUS_PUBLISHED
            && $request->boolean('feature_on_homepage');

        return [$data, $recipeIds->all(), $featureOnHomepage];
    }

    /**
     * @param list<int> $alsoIncludeIds
     */
    private function availableRecipes(array $alsoIncludeIds = [])
    {
        return Recipe::query()
            ->where(function ($query) use ($alsoIncludeIds): void {
                $query->where('is_active', true);

                if ($alsoIncludeIds !== []) {
                    $query->orWhereIn('id', $alsoIncludeIds);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'chef';
        $slug = $base;
        $counter = 2;

        while (Chef::query()->withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * @param list<int> $recipeIds
     * @return array<int, array{sort_order: int, is_featured: bool}>
     */
    private function recipeSyncPayload(array $recipeIds, ?int $featuredRecipeId): array
    {
        $payload = [];

        foreach (array_values($recipeIds) as $index => $recipeId) {
            $payload[$recipeId] = [
                'sort_order' => $index,
                'is_featured' => $featuredRecipeId === $recipeId,
            ];
        }

        return $payload;
    }

    /**
     * @param list<string> $newPaths
     * @return array<string, mixed>
     */
    private function storeUploadedMedia(Request $request, Chef $chef, array &$newPaths): array
    {
        $media = [];
        $directory = 'chefs/'.$chef->getKey().'-'.$chef->slug;

        if ($request->hasFile('portrait_image')) {
            $path = $this->storeImage($request->file('portrait_image'), "{$directory}/portrait");
            $media['portrait_image_path'] = $path;
            $newPaths[] = $path;
        }

        if ($request->hasFile('hero_image')) {
            $path = $this->storeImage($request->file('hero_image'), "{$directory}/hero");
            $media['hero_image_path'] = $path;
            $newPaths[] = $path;
        }

        $galleryPaths = [];
        foreach ($request->file('gallery_images', []) as $galleryImage) {
            if (! $galleryImage instanceof UploadedFile) {
                continue;
            }

            $path = $this->storeImage($galleryImage, "{$directory}/gallery");
            $galleryPaths[] = $path;
            $newPaths[] = $path;
        }

        if ($galleryPaths !== []) {
            $media['gallery_image_paths'] = $galleryPaths;
        }

        return $media;
    }

    private function storeImage(UploadedFile $file, string $directory): string
    {
        $path = $file->store($directory, 'public');

        if (! is_string($path) || $path === '') {
            throw ValidationException::withMessages([
                'media' => 'The image could not be stored on the public media disk.',
            ]);
        }

        return $path;
    }

    private function applyHomepageSelection(Chef $chef, bool $featureOnHomepage): void
    {
        if ($featureOnHomepage) {
            if (! $chef->isPublished()) {
                throw ValidationException::withMessages([
                    'feature_on_homepage' => 'Only a currently published chef can be featured.',
                ]);
            }

            $this->lockHomepageFeatureRows();
            Chef::query()
                ->where('id', '<>', $chef->getKey())
                ->where(function (Builder $query): void {
                    $query->where('is_featured', true)->orWhereNotNull('homepage_feature_slot');
                })
                ->update(['is_featured' => false, 'homepage_feature_slot' => null]);

            if (! $chef->is_featured || $chef->homepage_feature_slot !== 1) {
                $chef->forceFill(['is_featured' => true, 'homepage_feature_slot' => 1])->save();
            }

            return;
        }

        if ($chef->is_featured || $chef->homepage_feature_slot !== null) {
            $chef->forceFill(['is_featured' => false, 'homepage_feature_slot' => null])->save();
        }
    }

    private function lockHomepageFeatureRows(): void
    {
        // Every transaction that can create, edit, remove or select a Chef
        // acquires the same ordered lock before changing rows. This avoids
        // lock-order inversion and keeps the manual selection deterministic.
        Chef::query()->select('id')->orderBy('id')->lockForUpdate()->get();
    }

    /**
     * @param list<string> $paths
     */
    private function deletePublicPaths(array $paths): void
    {
        $safePaths = collect($paths)
            ->filter(fn (mixed $path): bool => is_string($path) && str_starts_with($path, 'chefs/'))
            ->unique()
            ->values()
            ->all();

        if ($safePaths === []) {
            return;
        }

        try {
            Storage::disk('public')->delete($safePaths);
        } catch (Throwable $exception) {
            // Media cleanup must never turn a successful database commit into
            // an apparent failed save. Laravel will still record the exception.
            report($exception);
        }
    }
}
