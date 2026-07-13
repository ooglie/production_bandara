<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\CategoryCollageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::query()
            ->with('parent')
            ->withCount('products');

        if ($search = $request->get('q')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status = $request->get('status')) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $categories = $query
            ->orderBy('position')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        $parents = Category::orderBy('name')->pluck('name', 'id');

        return view('admin.categories.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        unset($data['category_image'], $data['remove_category_image']);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('category_image')) {
            $data['image_path'] = $request->file('category_image')->store('category-images', 'public');
        }

        Category::create($data);

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Category created.');
    }

    public function edit(Category $category)
    {
        $parents = Category::where('id', '!=', $category->id)
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('admin.categories.edit', compact('category', 'parents'));
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validatedData($request, $category->id);
        unset($data['category_image']);
        $removeCategoryImage = (bool) ($data['remove_category_image'] ?? false);
        unset($data['remove_category_image']);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['is_active'] = $request->boolean('is_active');

        if ($removeCategoryImage) {
            $this->deletePublicFile($category->image_path);
            $data['image_path'] = null;
        }

        if ($request->hasFile('category_image')) {
            $this->deletePublicFile($category->image_path);
            $data['image_path'] = $request->file('category_image')->store('category-images', 'public');
        }

        $category->update($data);

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        $this->deletePublicFile($category->image_path);
        $this->deletePublicFile($category->collage_image_path);

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Category deleted.');
    }

    public function generateCollage(Request $request, Category $category, CategoryCollageService $collages)
    {
        $data = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:9'],
        ]);

        try {
            $result = $collages->generate($category, (int) ($data['limit'] ?? CategoryCollageService::DEFAULT_LIMIT), true);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', $result['message'] ?? 'Category collage generated.');
    }

    public function removeCollage(Category $category, CategoryCollageService $collages)
    {
        $collages->clear($category);

        return back()->with('status', 'Generated category collage removed.');
    }

    protected function validatedData(Request $request, ?int $categoryId = null): array
    {
        $slugRule = Rule::unique('categories', 'slug')
            ->whereNull('deleted_at');

        if ($categoryId) {
            $slugRule->ignore($categoryId);
        }

        return $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'slug'                  => ['nullable', 'string', 'max:255', $slugRule],
            'parent_id'             => ['nullable', 'exists:categories,id'],
            'description'           => ['nullable', 'string'],
            'position'              => ['nullable', 'integer'],
            'category_image'        => ['nullable', 'image', 'max:10240'],
            'remove_category_image' => ['nullable', 'boolean'],
        ]);
    }

    protected function deletePublicFile(?string $path): void
    {
        if (filled($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
