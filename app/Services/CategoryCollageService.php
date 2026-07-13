<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CategoryCollageService
{
    public const DEFAULT_LIMIT = 6;
    public const DEFAULT_WIDTH = 1200;
    public const DEFAULT_HEIGHT = 800;

    public function generate(Category $category, int $limit = self::DEFAULT_LIMIT, bool $force = true): array
    {
        $limit = max(1, min(9, $limit));

        if (! $force && filled($category->collage_image_path)) {
            return [
                'status' => 'skipped',
                'path' => $category->collage_image_path,
                'image_count' => 0,
                'message' => 'Existing collage kept. Use force to regenerate.',
            ];
        }

        if (! function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('The PHP GD extension is required to generate category collages.');
        }

        $images = $this->productImagesForCategory($category, $limit);

        if ($images->isEmpty()) {
            throw new RuntimeException('No usable product images were found for this category.');
        }

        $path = $this->writeCollage($category, $images);

        $category->forceFill([
            'collage_image_path' => $path,
            'collage_updated_at' => now(),
        ])->save();

        return [
            'status' => 'generated',
            'path' => $path,
            'image_count' => $images->count(),
            'message' => "Generated collage using {$images->count()} product image(s).",
        ];
    }

    public function clear(Category $category): void
    {
        $path = $category->collage_image_path;

        if (filled($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        $category->forceFill([
            'collage_image_path' => null,
            'collage_updated_at' => null,
        ])->save();
    }

    public function productImagesForCategory(Category $category, int $limit = self::DEFAULT_LIMIT): Collection
    {
        $categoryIds = $this->categoryAndDescendantIds($category);

        $query = Product::query()
            ->with([
                'images' => function ($query) {
                    $query->orderByDesc('is_primary')->orderBy('position')->orderBy('id');
                },
            ])
            ->whereHas('categories', function ($query) use ($categoryIds) {
                $query->whereIn('categories.id', $categoryIds);
            });

        if (Schema::hasColumn('products', 'is_active')) {
            $query->where('is_active', true);
        }

        if (Schema::hasColumn('products', 'inventory_role')) {
            $query->where(function ($query) {
                $query->whereNull('inventory_role')
                    ->orWhere('inventory_role', '<>', 'internal');
            });
        }

        return $query
            ->orderByDesc(Schema::hasColumn('products', 'is_featured') ? 'is_featured' : 'id')
            ->orderByDesc(Schema::hasColumn('products', 'is_new') ? 'is_new' : 'id')
            ->orderBy('name')
            ->limit(80)
            ->get()
            ->map(fn (Product $product) => $this->bestImagePathForProduct($product))
            ->filter()
            ->unique()
            ->take($limit)
            ->values();
    }

    protected function bestImagePathForProduct(Product $product): ?string
    {
        $candidates = collect();

        if (filled($product->primary_image)) {
            $candidates->push($product->primary_image);
        }

        foreach (($product->images ?? collect()) as $image) {
            if ($image instanceof ProductImage && filled($image->file_path)) {
                $candidates->push($image->file_path);
            }
        }

        foreach ($candidates->filter()->unique() as $path) {
            $absolutePath = $this->resolveLocalImagePath((string) $path);

            if ($absolutePath && is_readable($absolutePath) && @getimagesize($absolutePath)) {
                return $absolutePath;
            }
        }

        return null;
    }

    protected function categoryAndDescendantIds(Category $category): array
    {
        $ids = [(int) $category->id];
        $frontier = [(int) $category->id];

        while (! empty($frontier)) {
            $children = Category::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $children = array_values(array_diff($children, $ids));

            if (empty($children)) {
                break;
            }

            $ids = array_values(array_unique(array_merge($ids, $children)));
            $frontier = $children;
        }

        return $ids;
    }

    protected function writeCollage(Category $category, Collection $images): string
    {
        [$cols, $rows] = $this->gridForCount($images->count());
        $width = self::DEFAULT_WIDTH;
        $height = self::DEFAULT_HEIGHT;
        $gap = 10;

        $canvas = imagecreatetruecolor($width, $height);
        $background = imagecolorallocate($canvas, 247, 243, 238);
        imagefill($canvas, 0, 0, $background);

        $cellWidth = (int) floor(($width - ($gap * ($cols + 1))) / $cols);
        $cellHeight = (int) floor(($height - ($gap * ($rows + 1))) / $rows);

        foreach ($images->values() as $index => $imagePath) {
            $row = (int) floor($index / $cols);
            $col = $index % $cols;

            if ($row >= $rows) {
                break;
            }

            $dstX = $gap + ($col * ($cellWidth + $gap));
            $dstY = $gap + ($row * ($cellHeight + $gap));

            $this->copyCover($canvas, $imagePath, $dstX, $dstY, $cellWidth, $cellHeight);
        }

        $slug = Str::slug($category->slug ?: $category->name) ?: 'category';
        $relativePath = "category-collages/category-{$category->id}-{$slug}.jpg";
        $absolutePath = Storage::disk('public')->path($relativePath);

        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0755, true);
        }

        if (! imagejpeg($canvas, $absolutePath, 88)) {
            imagedestroy($canvas);
            throw new RuntimeException('Unable to write the generated category collage image.');
        }

        imagedestroy($canvas);

        return $relativePath;
    }

    protected function gridForCount(int $count): array
    {
        if ($count <= 1) {
            return [1, 1];
        }

        if ($count <= 4) {
            return [2, 2];
        }

        if ($count <= 6) {
            return [3, 2];
        }

        return [3, 3];
    }

    protected function copyCover($canvas, string $imagePath, int $dstX, int $dstY, int $dstW, int $dstH): void
    {
        $contents = @file_get_contents($imagePath);

        if ($contents === false) {
            return;
        }

        $source = @imagecreatefromstring($contents);

        if (! $source) {
            return;
        }

        $srcW = imagesx($source);
        $srcH = imagesy($source);

        if ($srcW <= 0 || $srcH <= 0) {
            imagedestroy($source);
            return;
        }

        $srcRatio = $srcW / $srcH;
        $dstRatio = $dstW / $dstH;

        if ($srcRatio > $dstRatio) {
            $cropH = $srcH;
            $cropW = (int) round($srcH * $dstRatio);
            $srcX = (int) floor(($srcW - $cropW) / 2);
            $srcY = 0;
        } else {
            $cropW = $srcW;
            $cropH = (int) round($srcW / $dstRatio);
            $srcX = 0;
            $srcY = (int) floor(($srcH - $cropH) / 2);
        }

        imagecopyresampled(
            $canvas,
            $source,
            $dstX,
            $dstY,
            $srcX,
            $srcY,
            $dstW,
            $dstH,
            $cropW,
            $cropH
        );

        imagedestroy($source);
    }

    protected function resolveLocalImagePath(string $path): ?string
    {
        $path = trim($path);

        if ($path === '' || Str::startsWith($path, ['http://', 'https://'])) {
            return null;
        }

        $candidates = [];

        if (Str::startsWith($path, '/storage/')) {
            $relative = ltrim(Str::after($path, '/storage/'), '/');
            $candidates[] = public_path(ltrim($path, '/'));
            $candidates[] = storage_path('app/public/' . $relative);
        } elseif (Str::startsWith($path, 'storage/')) {
            $relative = ltrim(Str::after($path, 'storage/'), '/');
            $candidates[] = public_path($path);
            $candidates[] = storage_path('app/public/' . $relative);
        } elseif (Str::startsWith($path, 'public/')) {
            $relative = ltrim(Str::after($path, 'public/'), '/');
            $candidates[] = storage_path('app/public/' . $relative);
        } elseif (Str::startsWith($path, '/')) {
            $candidates[] = public_path(ltrim($path, '/'));
        } else {
            $candidates[] = storage_path('app/public/' . ltrim($path, '/'));
            $candidates[] = storage_path('app/private/' . ltrim($path, '/'));
            $candidates[] = public_path(ltrim($path, '/'));
        }

        foreach ($candidates as $candidate) {
            if ($candidate && file_exists($candidate)) {
                return $candidate;
            }
        }

        foreach (['public', config('filesystems.default', 'local')] as $diskName) {
            try {
                if (Storage::disk($diskName)->exists($path)) {
                    return Storage::disk($diskName)->path($path);
                }
            } catch (\Throwable) {
                // Ignore disks that cannot produce local paths.
            }
        }

        return null;
    }
}
