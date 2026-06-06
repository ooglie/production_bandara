<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\HomeSectionItem;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HomeSectionController extends Controller
{
    public function index()
    {
        $sections = HomeSection::query()
            ->withCount('items')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.home.sections.index', compact('sections'));
    }

    public function edit(HomeSection $homeSection)
    {
        $homeSection->load(['items.linked']);

        return view('admin.home.sections.edit', [
            'homeSection' => $homeSection,
            'linkableTypeLabels' => $this->linkableTypeLabels(),
            'linkableClassAliases' => $this->linkableClassAliases(),
            'linkableOptions' => $this->linkableOptions(),
        ]);
    }

    public function update(Request $request, HomeSection $homeSection)
    {
        $data = $this->validatedSection($request, $homeSection);

        if ($request->boolean('remove_image')) {
            $this->deletePublicFile($homeSection->image_path);
            $data['image_path'] = null;
        }

        if ($request->boolean('remove_mobile_image')) {
            $this->deletePublicFile($homeSection->mobile_image_path);
            $data['mobile_image_path'] = null;
        }

        if ($request->hasFile('image_upload')) {
            $this->deletePublicFile($homeSection->image_path);
            $data['image_path'] = $this->storeImage($request->file('image_upload'));
        }

        if ($request->hasFile('mobile_image_upload')) {
            $this->deletePublicFile($homeSection->mobile_image_path);
            $data['mobile_image_path'] = $this->storeImage($request->file('mobile_image_upload'));
        }

        $homeSection->update($data);

        return redirect()
            ->route('admin.home-sections.edit', $homeSection)
            ->with('success', 'Home section updated successfully.');
    }

    public function toggle(HomeSection $homeSection)
    {
        $homeSection->update(['is_active' => ! $homeSection->is_active]);

        return redirect()
            ->route('admin.home-sections.index')
            ->with('success', 'Home section status updated.');
    }

    public function moveUp(HomeSection $homeSection)
    {
        $this->moveSection($homeSection, 'up');

        return redirect()
            ->route('admin.home-sections.index')
            ->with('success', 'Home section order updated.');
    }

    public function moveDown(HomeSection $homeSection)
    {
        $this->moveSection($homeSection, 'down');

        return redirect()
            ->route('admin.home-sections.index')
            ->with('success', 'Home section order updated.');
    }

    public function storeItem(Request $request, HomeSection $homeSection)
    {
        $data = $this->validatedItem($request);
        $data['home_section_id'] = $homeSection->id;

        if ($request->hasFile('item_image_upload')) {
            $data['image_path'] = $this->storeImage($request->file('item_image_upload'));
        }

        $homeSection->items()->create($data);

        return redirect()
            ->route('admin.home-sections.edit', $homeSection)
            ->with('success', 'Home item added successfully.');
    }

    public function updateItem(Request $request, HomeSection $homeSection, HomeSectionItem $item)
    {
        abort_unless((int) $item->home_section_id === (int) $homeSection->id, 404);

        $data = $this->validatedItem($request);

        if ($request->boolean('remove_item_image')) {
            $this->deletePublicFile($item->image_path);
            $data['image_path'] = null;
        }

        if ($request->hasFile('item_image_upload')) {
            $this->deletePublicFile($item->image_path);
            $data['image_path'] = $this->storeImage($request->file('item_image_upload'));
        }

        $item->update($data);

        return redirect()
            ->route('admin.home-sections.edit', $homeSection)
            ->with('success', 'Home item updated successfully.');
    }

    public function toggleItem(HomeSection $homeSection, HomeSectionItem $item)
    {
        abort_unless((int) $item->home_section_id === (int) $homeSection->id, 404);

        $item->update(['is_active' => ! $item->is_active]);

        return redirect()
            ->route('admin.home-sections.edit', $homeSection)
            ->with('success', 'Home item status updated.');
    }

    public function duplicateItem(HomeSection $homeSection, HomeSectionItem $item)
    {
        abort_unless((int) $item->home_section_id === (int) $homeSection->id, 404);

        $copy = $item->replicate();
        $copy->sort_order = ((int) $homeSection->items()->max('sort_order')) + 10;
        $copy->title = filled($copy->title) ? $copy->title . ' (copy)' : $copy->title;
        $copy->save();

        return redirect()
            ->route('admin.home-sections.edit', $homeSection)
            ->with('success', 'Home item duplicated.');
    }

    public function moveItemUp(HomeSection $homeSection, HomeSectionItem $item)
    {
        abort_unless((int) $item->home_section_id === (int) $homeSection->id, 404);

        $this->moveItem($homeSection, $item, 'up');

        return redirect()
            ->route('admin.home-sections.edit', $homeSection)
            ->with('success', 'Home item order updated.');
    }

    public function moveItemDown(HomeSection $homeSection, HomeSectionItem $item)
    {
        abort_unless((int) $item->home_section_id === (int) $homeSection->id, 404);

        $this->moveItem($homeSection, $item, 'down');

        return redirect()
            ->route('admin.home-sections.edit', $homeSection)
            ->with('success', 'Home item order updated.');
    }

    public function destroyItem(HomeSection $homeSection, HomeSectionItem $item)
    {
        abort_unless((int) $item->home_section_id === (int) $homeSection->id, 404);

        $this->deletePublicFile($item->image_path);
        $item->delete();

        return redirect()
            ->route('admin.home-sections.edit', $homeSection)
            ->with('success', 'Home item deleted successfully.');
    }

    protected function validatedSection(Request $request, HomeSection $homeSection): array
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:80', Rule::unique('home_sections', 'key')->ignore($homeSection->id)],
            'type' => ['required', 'string', 'max:80'],
            'eyebrow' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'cta_text' => ['nullable', 'string', 'max:255'],
            'cta_url' => ['nullable', 'string', 'max:500'],
            'secondary_cta_text' => ['nullable', 'string', 'max:255'],
            'secondary_cta_url' => ['nullable', 'string', 'max:500'],
            'image_path' => ['nullable', 'string', 'max:500'],
            'mobile_image_path' => ['nullable', 'string', 'max:500'],
            'layout' => ['nullable', 'string', 'max:80'],
            'settings_json' => ['nullable', 'json'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:-100000', 'max:100000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'image_upload' => ['nullable', 'image', 'max:4096'],
            'mobile_image_upload' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
            'remove_mobile_image' => ['nullable', 'boolean'],
        ]);

        $data['settings'] = $this->decodeJson($data['settings_json'] ?? null);
        $data['is_active'] = $request->boolean('is_active');

        unset(
            $data['settings_json'],
            $data['image_upload'],
            $data['mobile_image_upload'],
            $data['remove_image'],
            $data['remove_mobile_image']
        );

        return $data;
    }

    protected function validatedItem(Request $request): array
    {
        $data = $request->validate([
            'item_type' => ['required', 'string', 'max:80'],
            'eyebrow' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:80'],
            'image_path' => ['nullable', 'string', 'max:500'],
            'cta_text' => ['nullable', 'string', 'max:255'],
            'cta_url' => ['nullable', 'string', 'max:500'],
            'linked_resource' => ['nullable', 'string', 'max:255'],
            'settings_json' => ['nullable', 'json'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:-100000', 'max:100000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'item_image_upload' => ['nullable', 'image', 'max:4096'],
            'remove_item_image' => ['nullable', 'boolean'],
        ]);

        [$linkedType, $linkedId] = $this->parseLinkedResource($data['linked_resource'] ?? null);

        $data['linked_type'] = $linkedType;
        $data['linked_id'] = $linkedId;
        $data['settings'] = $this->decodeJson($data['settings_json'] ?? null);
        $data['is_active'] = $request->boolean('is_active');

        unset(
            $data['settings_json'],
            $data['linked_resource'],
            $data['item_image_upload'],
            $data['remove_item_image']
        );

        return $data;
    }

    protected function decodeJson(?string $json): ?array
    {
        if (! filled($json)) {
            return null;
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }

    protected function parseLinkedResource(?string $resource): array
    {
        if (! filled($resource)) {
            return [null, null];
        }

        if (! str_contains($resource, ':')) {
            throw ValidationException::withMessages([
                'linked_resource' => 'Please choose a valid linked homepage record.',
            ]);
        }

        [$alias, $id] = explode(':', $resource, 2);
        $alias = trim($alias);
        $id = (int) $id;
        $types = $this->linkableTypes();

        if (! isset($types[$alias]) || $id <= 0) {
            throw ValidationException::withMessages([
                'linked_resource' => 'Please choose a valid linked homepage record.',
            ]);
        }

        $class = $types[$alias]['class'];

        if (! $class::query()->whereKey($id)->exists()) {
            throw ValidationException::withMessages([
                'linked_resource' => 'The selected linked record no longer exists.',
            ]);
        }

        return [$class, $id];
    }

    protected function moveSection(HomeSection $homeSection, string $direction): void
    {
        $sections = HomeSection::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $this->moveWithinCollection($sections, $homeSection->id, $direction);
    }

    protected function moveItem(HomeSection $homeSection, HomeSectionItem $item, string $direction): void
    {
        $items = HomeSectionItem::query()
            ->where('home_section_id', $homeSection->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $this->moveWithinCollection($items, $item->id, $direction);
    }

    protected function moveWithinCollection($records, int $targetId, string $direction): void
    {
        $records = $records->values();
        $index = $records->search(fn ($record) => (int) $record->id === $targetId);

        if ($index === false) {
            return;
        }

        $newIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if ($newIndex < 0 || $newIndex >= $records->count()) {
            return;
        }

        $ordered = $records->all();
        $moving = array_splice($ordered, $index, 1)[0];
        array_splice($ordered, $newIndex, 0, [$moving]);

        foreach ($ordered as $position => $record) {
            $record->forceFill(['sort_order' => ($position + 1) * 10])->save();
        }
    }

    protected function linkableTypes(): array
    {
        return [
            'product' => ['label' => 'Product', 'class' => Product::class],
            'category' => ['label' => 'Category', 'class' => Category::class],
            'collection' => ['label' => 'Product collection', 'class' => ProductCollection::class],
            'recipe' => ['label' => 'Recipe', 'class' => Recipe::class],
        ];
    }

    protected function linkableTypeLabels(): array
    {
        return collect($this->linkableTypes())
            ->mapWithKeys(fn ($definition, $alias) => [$alias => $definition['label']])
            ->all();
    }

    protected function linkableClassAliases(): array
    {
        return collect($this->linkableTypes())
            ->mapWithKeys(fn ($definition, $alias) => [$definition['class'] => $alias])
            ->all();
    }

    protected function linkableOptions(): array
    {
        return [
            'product' => Product::query()
                ->orderBy('name')
                ->limit(300)
                ->get(['id', 'name', 'sku'])
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'label' => trim($product->name . ($product->sku ? ' · ' . $product->sku : '')),
                ])
                ->all(),
            'category' => Category::query()
                ->orderBy('name')
                ->limit(300)
                ->get(['id', 'name'])
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'label' => $category->name,
                ])
                ->all(),
            'collection' => ProductCollection::query()
                ->orderBy('name')
                ->limit(300)
                ->get(['id', 'name'])
                ->map(fn (ProductCollection $collection) => [
                    'id' => $collection->id,
                    'label' => $collection->name,
                ])
                ->all(),
            'recipe' => Recipe::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->limit(300)
                ->get(['id', 'title'])
                ->map(fn (Recipe $recipe) => [
                    'id' => $recipe->id,
                    'label' => $recipe->tr('title') ?: 'Recipe #' . $recipe->id,
                ])
                ->all(),
        ];
    }

    protected function storeImage(UploadedFile $file): string
    {
        return $file->store('home', 'public');
    }

    protected function deletePublicFile(?string $path): void
    {
        if (! filled($path)) {
            return;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return;
        }

        $normalized = Str::startsWith($path, '/storage/')
            ? ltrim(Str::after($path, '/storage/'), '/')
            : ltrim($path, '/');

        Storage::disk('public')->delete($normalized);
    }
}
