<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RecipeController extends Controller
{
    public function index(Request $request)
    {
        $query = Recipe::query()->withCount('products');

        if ($request->filled('q')) {
            $q = trim((string) $request->q);

            $query->where(function ($sub) use ($q) {
                $sub->where('title->en', 'like', "%{$q}%")
                    ->orWhere('short_description->en', 'like', "%{$q}%");
            });
        }

        if ($request->filled('product_id')) {
            $productId = (int) $request->product_id;

            $query->whereHas('products', function ($q) use ($productId) {
                $q->where('products.id', $productId);
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $recipes = $query
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $products = Product::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedProduct = null;
        if ($request->filled('product_id')) {
            $selectedProduct = Product::find((int) $request->product_id);
        }

        return view('admin.recipes.index', compact('recipes', 'products', 'selectedProduct'));
    }

    public function create(Request $request)
    {
        $products = Product::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedProductIds = [];
        if ($request->filled('product_id')) {
            $selectedProductIds[] = (int) $request->product_id;
        }

        return view('admin.recipes.create', compact('products', 'selectedProductIds'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $media = $this->handleUploads($request);

        $payload = $this->preparePayload(array_merge($data, $media['data']));
        $payload['created_by_id'] = $request->user()?->id;

        try {
            DB::transaction(function () use ($payload, $data) {
                $recipe = Recipe::create($payload);
                $recipe->products()->sync($data['product_ids'] ?? []);
            });
        } catch (\Throwable $e) {
            $this->deleteStoredFiles($media['new_files']);
            throw $e;
        }

        return redirect()
            ->route('admin.recipes.index')
            ->with('status', 'Recipe created.');
    }

    public function edit(Recipe $recipe)
    {
        $recipe->load('products:id');

        $products = Product::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedProductIds = $recipe->products->pluck('id')->map(fn ($id) => (int) $id)->all();

        return view('admin.recipes.edit', compact('recipe', 'products', 'selectedProductIds'));
    }

    public function update(Request $request, Recipe $recipe)
    {
        $data = $this->validatedData($request);
        $media = $this->handleUploads($request, $recipe);

        $payload = $this->preparePayload(array_merge($data, $media['data']));
        $payload['updated_by_id'] = $request->user()?->id;

        try {
            DB::transaction(function () use ($recipe, $payload, $data) {
                $recipe->update($payload);
                $recipe->products()->sync($data['product_ids'] ?? []);
            });
        } catch (\Throwable $e) {
            $this->deleteStoredFiles($media['new_files']);
            throw $e;
        }

        $this->deleteStoredFiles($media['old_files']);

        return redirect()
            ->route('admin.recipes.index')
            ->with('status', 'Recipe updated.');
    }

    public function destroy(Recipe $recipe)
    {
        $deleteFiles = ! $this->usesSoftDeletes($recipe);

        $filesToDelete = $deleteFiles
            ? [$recipe->image_path, $recipe->video_url]
            : [];

        DB::transaction(function () use ($recipe, $deleteFiles) {
            if ($deleteFiles) {
                $recipe->products()->detach();
            }

            $recipe->delete();
        });

        if ($deleteFiles) {
            $this->deleteStoredFiles($filesToDelete);
        }

        return redirect()
            ->route('admin.recipes.index')
            ->with('status', 'Recipe deleted.');
    }

    protected function validatedData(Request $request): array
    {
        $this->throwIfPhpUploadFailed('image', 'image');
        $this->throwIfPhpUploadFailed('video', 'video');

        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],

            'ingredients_text' => ['nullable', 'string', 'max:20000'],
            'steps_text' => ['nullable', 'string', 'max:30000'],

            'prep_time_minutes' => ['nullable', 'integer', 'min:0'],
            'cook_time_minutes' => ['nullable', 'integer', 'min:0'],
            'servings' => ['nullable', 'integer', 'min:1'],

            'image' => ['nullable', 'image', 'max:4096'],
            'video' => ['nullable', 'file', 'mimes:mp4,webm,mov,m4v', 'max:51200'],

            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],

            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ], [], [
            'image' => 'recipe image',
            'video' => 'recipe video',
        ]);
    }

    protected function throwIfPhpUploadFailed(string $field, string $label): void
    {
        if (! isset($_FILES[$field])) {
            return;
        }

        $error = (int) ($_FILES[$field]['error'] ?? UPLOAD_ERR_OK);

        if (in_array($error, [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE], true)) {
            return;
        }

        throw ValidationException::withMessages([
            $field => $this->phpUploadErrorMessage($label, $error),
        ]);
    }

    protected function phpUploadErrorMessage(string $label, int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE,
            UPLOAD_ERR_FORM_SIZE => "The {$label} is larger than the server upload limit. Increase upload_max_filesize and post_max_size.",
            UPLOAD_ERR_PARTIAL => "The {$label} was only partially uploaded. Please try again.",
            UPLOAD_ERR_NO_TMP_DIR => "The server is missing a temporary upload folder. Check upload_tmp_dir.",
            UPLOAD_ERR_CANT_WRITE => "The server could not write the {$label} to disk. Check temp-folder and storage permissions.",
            UPLOAD_ERR_EXTENSION => "A PHP extension stopped the {$label} upload.",
            default => "The {$label} could not be uploaded because of a server-side upload error.",
        };
    }

    protected function preparePayload(array $data): array
    {
        $titleEn = trim((string) ($data['title'] ?? ''));
        $slugEn = trim((string) ($data['slug'] ?? ''));
        $shortEn = trim((string) ($data['short_description'] ?? ''));
        $descriptionEn = trim((string) ($data['description'] ?? ''));

        $ingredientsEn = $this->parseLines($data['ingredients_text'] ?? null);
        $stepsEn = $this->parseLines($data['steps_text'] ?? null);

        $payload = [
            'title' => ['en' => $titleEn],
            'slug' => ['en' => $slugEn !== '' ? Str::slug($slugEn) : Str::slug($titleEn)],
            'short_description' => $shortEn !== '' ? ['en' => $shortEn] : [],
            'description' => $descriptionEn !== '' ? ['en' => $descriptionEn] : [],
            'ingredients' => !empty($ingredientsEn) ? ['en' => $ingredientsEn] : [],
            'steps' => !empty($stepsEn) ? ['en' => $stepsEn] : [],

            'prep_time_minutes' => $data['prep_time_minutes'] ?? null,
            'cook_time_minutes' => $data['cook_time_minutes'] ?? null,
            'servings' => $data['servings'] ?? null,

            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];

        if (array_key_exists('image_path', $data)) {
            $payload['image_path'] = $data['image_path'];
        }

        if (array_key_exists('video_url', $data)) {
            $payload['video_url'] = $data['video_url'];
        }

        return $payload;
    }

    protected function parseLines(?string $text): array
    {
        if ($text === null) {
            return [];
        }

        $lines = preg_split("/\r\n|\n|\r/", $text);
        $lines = array_map(fn ($line) => trim((string) $line), $lines);
        $lines = array_values(array_filter($lines, fn ($line) => $line !== ''));

        return $lines;
    }

    protected function handleUploads(Request $request, ?Recipe $existingRecipe = null): array
    {
        $data = [];
        $newFiles = [];
        $oldFiles = [];

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store(config('media.recipes.images_dir'));

            $data['image_path'] = $path;
            $newFiles[] = $path;

            if ($existingRecipe && $this->isLocalStoragePath($existingRecipe->image_path)) {
                $oldFiles[] = $this->normalizeStoragePath($existingRecipe->image_path);
            }
        }

        if ($request->hasFile('video')) {
            $path = $request->file('video')->store(config('media.recipes.videos_dir'));

            $data['video_url'] = $path;
            $newFiles[] = $path;

            if ($existingRecipe && $this->isLocalStoragePath($existingRecipe->video_url)) {
                $oldFiles[] = $this->normalizeStoragePath($existingRecipe->video_url);
            }
        }

        return [
            'data' => $data,
            'new_files' => $newFiles,
            'old_files' => $oldFiles,
        ];
    }

    protected function deleteStoredFiles(array $paths): void
    {
        foreach ($paths as $path) {
            $this->deleteStoredFileIfLocal($path);
        }
    }

    protected function deleteStoredFileIfLocal(?string $path): void
    {
        if (! $this->isLocalStoragePath($path)) {
            return;
        }

        $normalizedPath = $this->normalizeStoragePath($path);

        if ($normalizedPath !== '' && Storage::exists($normalizedPath)) {
            Storage::delete($normalizedPath);
        }
    }

    protected function isLocalStoragePath(?string $path): bool
    {
        $path = trim((string) $path);

        if ($path === '') {
            return false;
        }

        return ! Str::startsWith($path, ['http://', 'https://', '//', 'data:']);
    }

    protected function normalizeStoragePath(?string $path): string
    {
        $path = ltrim((string) $path, '/');

        if (Str::startsWith($path, 'storage/')) {
            $path = Str::after($path, 'storage/');
        }

        return $path;
    }

    protected function usesSoftDeletes(object|string $model): bool
    {
        $class = is_string($model) ? $model : $model::class;

        return in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($class), true);
    }
}