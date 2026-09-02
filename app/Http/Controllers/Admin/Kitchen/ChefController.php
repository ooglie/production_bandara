<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Kitchen;

use App\Http\Controllers\Controller;
use App\Models\Chef;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
                    ->orWhere('signature_dish_name', 'like', "%{$search}%")
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

        return view('admin.kitchen.chefs.create', compact('chef'));
    }

    public function store(Request $request): RedirectResponse
    {
        [$data, $featureOnHomepage] = $this->validatedPayload($request);

        $staffId = Auth::guard('staff')->id();
        $data['created_by_id'] = $staffId;
        $data['updated_by_id'] = $staffId;

        $newPaths = [];

        try {
            /** @var Chef $chef */
            $chef = DB::transaction(function () use ($request, $data, $featureOnHomepage, &$newPaths): Chef {
                $this->lockHomepageFeatureRows();

                $chef = Chef::query()->create($data);
                $media = $this->storeUploadedMedia($request, $chef, $newPaths);

                if ($media !== []) {
                    $chef->fill($media)->save();
                }

                $this->applyHomepageSelection($chef, $featureOnHomepage);

                return $chef->fresh();
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
        return view('admin.kitchen.chefs.edit', compact('chef'));
    }

    public function update(Request $request, Chef $chef): RedirectResponse
    {
        [$data, $featureOnHomepage] = $this->validatedPayload($request, $chef);
        $data['updated_by_id'] = Auth::guard('staff')->id();

        $newPaths = [];
        $pathsToDeleteAfterCommit = [];

        try {
            DB::transaction(function () use (
                $request,
                $chef,
                $data,
                $featureOnHomepage,
                &$newPaths,
                &$pathsToDeleteAfterCommit
            ): void {
                $this->lockHomepageFeatureRows();
                $chef->refresh();

                $media = $this->storeUploadedMedia($request, $chef, $newPaths);

                foreach ([
                    'portrait_image_path' => 'remove_portrait_image',
                    'hero_image_path' => 'remove_hero_image',
                    'signature_dish_image_path' => 'remove_signature_dish_image',
                ] as $pathField => $removeField) {
                    if (array_key_exists($pathField, $media) && filled($chef->{$pathField})) {
                        $pathsToDeleteAfterCommit[] = $chef->{$pathField};
                    }

                    if ($request->boolean($removeField) && ! array_key_exists($pathField, $media)) {
                        if (filled($chef->{$pathField})) {
                            $pathsToDeleteAfterCommit[] = $chef->{$pathField};
                        }

                        $media[$pathField] = null;
                    }
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

        return back()->with('status', 'The homepage chef selection has been cleared.');
    }

    /**
     * @return array{0: array<string, mixed>, 1: bool}
     */
    private function validatedPayload(Request $request, ?Chef $chef = null): array
    {
        $validator = Validator::make($request->all(), [
            'display_name' => ['required', 'string', 'max:150'],
            'professional_title' => ['required', 'string', 'max:180'],
            'organisation_name' => ['nullable', 'string', 'max:200'],
            'city' => ['nullable', 'string', 'max:120'],
            'short_intro' => ['nullable', 'string', 'max:700'],
            'biography' => ['nullable', 'string', 'max:12000'],
            'signature_dish_name' => ['nullable', 'string', 'max:200'],
            'signature_dishes' => ['nullable', 'string', 'max:3000'],
            'portrait_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'hero_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:12288'],
            'signature_dish_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:12288'],
            'gallery_images' => ['nullable', 'array', 'max:4'],
            'gallery_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:12288'],
            'remove_portrait_image' => ['nullable', 'boolean'],
            'remove_hero_image' => ['nullable', 'boolean'],
            'remove_signature_dish_image' => ['nullable', 'boolean'],
            'remove_gallery_paths' => ['nullable', 'array'],
            'remove_gallery_paths.*' => ['string', 'max:500'],
            'photographer_credit' => ['nullable', 'string', 'max:200'],
            'website_url' => ['nullable', 'url:http,https', 'max:255'],
            'instagram_url' => ['nullable', 'url:http,https', 'max:255'],
            'linkedin_url' => ['nullable', 'url:http,https', 'max:255'],
            'content_and_images_approved' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(Chef::STATUSES)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'published_at' => ['nullable', 'date'],
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
            $remainingGalleryCount = $existingGallery->diff($removedGallery)->count();

            if ($newGalleryCount > 0 && $remainingGalleryCount + $newGalleryCount > 4) {
                $validator->errors()->add('gallery_images', 'The simplified profile allows up to four gallery images. Existing additional images may be retained, but no more can be added until the total is four or fewer.');
            }

            if ($request->input('status') !== Chef::STATUS_PUBLISHED) {
                return;
            }

            $willHavePortrait = $request->hasFile('portrait_image')
                || (filled($chef?->portrait_image_path) && ! $request->boolean('remove_portrait_image'));
            $willHaveSignatureImage = $request->hasFile('signature_dish_image')
                || (filled($chef?->signature_dish_image_path) && ! $request->boolean('remove_signature_dish_image'));

            if (! $willHavePortrait) {
                $validator->errors()->add('portrait_image', 'A Chef portrait is required before publishing.');
            }

            if (blank($request->input('short_intro'))) {
                $validator->errors()->add('short_intro', 'A short Chef brief is required before publishing.');
            }

            if (blank($request->input('biography'))) {
                $validator->errors()->add('biography', 'The Chef story is required before publishing.');
            }

            if (blank($request->input('signature_dish_name'))) {
                $validator->errors()->add('signature_dish_name', 'A signature dish name is required before publishing.');
            }

            if (blank($request->input('signature_dishes'))) {
                $validator->errors()->add('signature_dishes', 'A short signature dish description is required before publishing.');
            }

            if (! $willHaveSignatureImage) {
                $validator->errors()->add('signature_dish_image', 'A photograph of the signature dish is required before publishing.');
            }

            if (! $request->boolean('content_and_images_approved')) {
                $validator->errors()->add('content_and_images_approved', 'Confirm that the Chef has approved the profile and supplied images before publishing.');
            }

            if ($request->boolean('feature_on_homepage')) {
                $effectivePublication = $request->input('published_at') ?: $chef?->published_at;

                if (filled($effectivePublication)) {
                    try {
                        if (Carbon::parse((string) $effectivePublication)->isFuture()) {
                            $validator->errors()->add('feature_on_homepage', 'A future-scheduled Chef cannot be featured until publication time is reached.');
                        }
                    } catch (Throwable) {
                        // The normal date rule reports malformed values.
                    }
                }
            }
        });

        $validated = $validator->validate();
        $approved = $request->boolean('content_and_images_approved');
        $status = (string) $validated['status'];

        $displayName = trim((string) $validated['display_name']);
        $data = [
            'display_name' => $displayName,
            'slug' => $chef?->slug ?: $this->uniqueSlug($displayName),
            'professional_title' => trim((string) $validated['professional_title']),
            'organisation_name' => $this->nullableText($validated['organisation_name'] ?? null),
            // The original schema requires a non-null city. An omitted city is
            // stored as an empty string and never rendered publicly.
            'city' => trim((string) ($validated['city'] ?? '')),
            'short_intro' => $this->nullableText($validated['short_intro'] ?? null),
            'biography' => $this->nullableText($validated['biography'] ?? null),
            'signature_dish_name' => $this->nullableText($validated['signature_dish_name'] ?? null),
            // The original signature_dishes column is retained as the single
            // signature-dish description to preserve existing Chef data.
            'signature_dishes' => $this->nullableText($validated['signature_dishes'] ?? null),
            'photographer_credit' => $this->nullableText($validated['photographer_credit'] ?? null),
            'website_url' => $this->nullableText($validated['website_url'] ?? null),
            'instagram_url' => $this->nullableText($validated['instagram_url'] ?? null),
            'linkedin_url' => $this->nullableText($validated['linkedin_url'] ?? null),
            'image_rights_confirmed' => $approved,
            'profile_use_approved' => $approved,
            'restaurant_mention_approved' => $approved,
            'content_approved_at' => $approved ? ($chef?->content_approved_at ?: now()) : null,
            'status' => $status,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'published_at' => $validated['published_at'] ?? null,
        ];

        if ($chef === null) {
            $data['country'] = 'India';
        }

        if ($status === Chef::STATUS_PUBLISHED && blank($data['published_at'])) {
            $data['published_at'] = $chef?->published_at ?: now();
        }

        $featureOnHomepage = $status === Chef::STATUS_PUBLISHED
            && $request->boolean('feature_on_homepage');

        return [$data, $featureOnHomepage];
    }

    private function nullableText(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
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
     * @param list<string> $newPaths
     * @return array<string, mixed>
     */
    private function storeUploadedMedia(Request $request, Chef $chef, array &$newPaths): array
    {
        $media = [];
        $directory = 'chefs/'.$chef->getKey().'-'.$chef->slug;

        foreach ([
            'portrait_image' => ['portrait_image_path', "{$directory}/portrait"],
            'hero_image' => ['hero_image_path', "{$directory}/working"],
            'signature_dish_image' => ['signature_dish_image_path', "{$directory}/signature-dish"],
        ] as $requestField => [$modelField, $storageDirectory]) {
            if (! $request->hasFile($requestField)) {
                continue;
            }

            $path = $this->storeImage($request->file($requestField), $storageDirectory);
            $media[$modelField] = $path;
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
                    'feature_on_homepage' => 'Only a currently published Chef can be featured.',
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
            report($exception);
        }
    }
}
