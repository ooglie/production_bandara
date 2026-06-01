<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductCollectionRequest;
use App\Models\Product;
use App\Models\ProductCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductCollectionController extends Controller
{
    public function index()
    {
        $collections = ProductCollection::query()
            ->withCount('products')
            ->orderByDesc('is_active')
            ->orderBy('home_section')
            ->orderBy('home_order')
            ->latest()
            ->paginate(15);

        return view('admin.product_collections.index', compact('collections'));
    }

    public function create()
    {
        $productCollection = new ProductCollection([
            'kind' => 'general',
            'selection_mode' => 'manual',
            'is_active' => true,
            'show_on_home' => false,
            'home_order' => 0,
        ]);

        $products = $this->productsForForm();

        return view('admin.product_collections.create', compact('productCollection', 'products'));
    }

    public function store(ProductCollectionRequest $request)
    {
        $productCollection = new ProductCollection($this->payload($request));
        $this->syncImage($request, $productCollection);
        $productCollection->save();

        $this->syncProducts($request, $productCollection);

        return redirect()
            ->route('admin.product-collections.index')
            ->with('success', 'Collection created successfully.');
    }

    public function edit(ProductCollection $productCollection)
    {
        $productCollection->load(['products' => function ($q) {
            $q->orderBy('product_collection_product.sort_order');
        }]);

        $products = $this->productsForForm();

        return view('admin.product_collections.edit', compact('productCollection', 'products'));
    }

    public function update(ProductCollectionRequest $request, ProductCollection $productCollection)
    {
        $productCollection->fill($this->payload($request, $productCollection));
        $this->syncImage($request, $productCollection);
        $productCollection->save();

        $this->syncProducts($request, $productCollection);

        return redirect()
            ->route('admin.product-collections.index')
            ->with('success', 'Collection updated successfully.');
    }

    public function destroy(ProductCollection $productCollection)
    {
        $this->deleteImage($productCollection->image_path);
        $productCollection->delete();

        return redirect()
            ->route('admin.product-collections.index')
            ->with('success', 'Collection deleted successfully.');
    }

    protected function productsForForm(): Collection
    {
        return Product::query()
            ->select(['id', 'name', 'sku', 'is_active'])
            ->orderBy('name')
            ->get();
    }

    protected function payload(ProductCollectionRequest $request, ?ProductCollection $productCollection = null): array
    {
        $data = $request->validated();

        unset(
            $data['image'],
            $data['remove_image'],
            $data['product_ids'],
            $data['product_sort_orders'],
            $data['product_featured']
        );

        $data['name'] = trim($data['name']);
        $data['slug'] = $this->resolveSlug(
            $request->input('slug'),
            $data['name'],
            $productCollection
        );

        $data['kind'] = $data['kind'] ?? 'general';
        $data['eyebrow'] = filled($data['eyebrow'] ?? null) ? trim($data['eyebrow']) : null;
        $data['description'] = filled($data['description'] ?? null) ? trim($data['description']) : null;
        $data['cta_text'] = filled($data['cta_text'] ?? null) ? trim($data['cta_text']) : null;
        $data['cta_url'] = filled($data['cta_url'] ?? null) ? trim($data['cta_url']) : null;

        $data['selection_mode'] = 'manual';
        $data['is_active'] = $request->boolean('is_active');
        $data['show_on_home'] = $request->boolean('show_on_home');

        $data['home_section'] = filled($data['home_section'] ?? null) ? $data['home_section'] : null;
        $data['home_order'] = (int) ($data['home_order'] ?? 0);

        $data['starts_at'] = $data['starts_at'] ?? null;
        $data['ends_at'] = $data['ends_at'] ?? null;

        return $data;
    }

    protected function resolveSlug(?string $input, string $name, ?ProductCollection $productCollection = null): string
    {
        $slug = filled($input) ? Str::slug($input) : Str::slug($name);

        if ($slug === '') {
            $slug = 'collection';
        }

        $base = $slug;
        $i = 2;

        $query = ProductCollection::query();
        if ($productCollection) {
            $query->whereKeyNot($productCollection->id);
        }

        while ((clone $query)->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    protected function syncProducts(ProductCollectionRequest $request, ProductCollection $productCollection): void
    {
        $selectedIds = collect($request->input('product_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $sortOrders = $request->input('product_sort_orders', []);
        $featuredFlags = $request->input('product_featured', []);

        $syncPayload = [];

        foreach ($selectedIds as $productId) {
            $syncPayload[$productId] = [
                'sort_order' => (int) data_get($sortOrders, $productId, 0),
                'is_featured' => (bool) data_get($featuredFlags, $productId, false),
            ];
        }

        $productCollection->products()->sync($syncPayload);
    }

    protected function syncImage(ProductCollectionRequest $request, ProductCollection $productCollection): void
    {
        if ($request->boolean('remove_image') && $productCollection->image_path) {
            $this->deleteImage($productCollection->image_path);
            $productCollection->image_path = null;
        }

        if ($request->hasFile('image')) {
            if ($productCollection->image_path) {
                $this->deleteImage($productCollection->image_path);
            }

            $productCollection->image_path = $this->storeImage($request->file('image'));
        }
    }

    protected function storeImage(UploadedFile $file): string
    {
        return $file->store('product-collections', 'public');
    }

    protected function deleteImage(?string $path): void
    {
        if (!filled($path)) {
            return;
        }

        $normalized = Str::startsWith($path, '/storage/')
            ? ltrim(Str::after($path, '/storage/'), '/')
            : ltrim($path, '/');

        Storage::disk('public')->delete($normalized);
    }
}