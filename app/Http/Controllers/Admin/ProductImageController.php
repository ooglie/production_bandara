<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductImageController extends Controller
{
    public function index(Product $product)
    {
        $images = $product->images()
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return view('admin.products.images.index', compact('product', 'images'));
    }

    public function create(Product $product)
    {
        return view('admin.products.images.create', compact('product'));
    }

    public function store(Request $request, Product $product)
    {
        $this->throwIfPhpUploadFailed('images');
        $this->throwIfPhpUploadFailed('image');

        $data = $request->validate([
            'images'      => ['required_without:image', 'array', 'min:1', 'max:25'],
            'images.*'    => ['image', 'max:10240'], // 10 MB each
            'image'       => ['required_without:images', 'nullable', 'image', 'max:10240'], // Backward-compatible single upload
            'alt_text'    => ['nullable', 'string', 'max:255'],
            'position'    => ['nullable', 'integer', 'min:0'],
            'is_primary'  => ['nullable', 'boolean'],
        ]);

        $files = collect($request->file('images', []))
            ->filter()
            ->values();

        if ($files->isEmpty() && $request->hasFile('image')) {
            $files = collect([$request->file('image')]);
        }

        if ($files->isEmpty()) {
            throw ValidationException::withMessages([
                'images' => 'Please choose at least one product image.',
            ]);
        }

        $disk = $this->mediaDisk();
        $storedPaths = [];
        $createdImages = collect();

        try {
            DB::transaction(function () use ($request, $product, $data, $files, $disk, &$storedPaths, &$createdImages) {
                $startPosition = array_key_exists('position', $data) && $data['position'] !== null
                    ? (int) $data['position']
                    : ((int) $product->images()->max('position') + 1);

                $hasPrimaryImage = $product->images()->where('is_primary', true)->exists()
                    || filled($product->primary_image);

                foreach ($files as $index => $file) {
                    $path = $file->store('products', $disk);
                    $storedPaths[] = $path;

                    $image = new ProductImage([
                        'file_path'  => $path,
                        'alt_text'   => $data['alt_text'] ?? null,
                        'position'   => $startPosition + $index,
                        'is_primary' => false,
                    ]);

                    $product->images()->save($image);
                    $createdImages->push($image);
                }

                $primaryImage = null;

                if ($request->boolean('is_primary')) {
                    $primaryImage = $createdImages->first();
                } elseif (! $hasPrimaryImage) {
                    // Product cards read products.primary_image directly. Make the
                    // first uploaded image primary when the product has no primary yet.
                    $primaryImage = $createdImages->first();
                }

                if ($primaryImage) {
                    $this->setPrimaryImage($product, $primaryImage);
                }
            });
        } catch (\Throwable $e) {
            Storage::disk($disk)->delete($storedPaths);
            throw $e;
        }

        $count = $createdImages->count();

        return redirect()
            ->route('admin.products.images.index', $product)
            ->with('status', $count === 1 ? 'Image uploaded.' : $count . ' images uploaded.');
    }

    public function edit(ProductImage $image)
    {
        $product = $image->product;

        return view('admin.products.images.edit', compact('product', 'image'));
    }

    public function update(Request $request, ProductImage $image)
    {
        $product = $image->product;
        $wasPrimary = (bool) $image->is_primary;

        $this->throwIfPhpUploadFailed('image');

        $data = $request->validate([
            'alt_text'   => ['nullable', 'string', 'max:255'],
            'position'   => ['nullable', 'integer', 'min:0'],
            'is_primary' => ['nullable', 'boolean'],
            'image'      => ['nullable', 'image', 'max:10240'], // 10 MB
        ]);

        $disk = $this->mediaDisk();
        $oldPath = $image->file_path;
        $newPath = null;

        if ($request->hasFile('image')) {
            $newPath = $request->file('image')->store('products', $disk);
        }

        try {
            DB::transaction(function () use ($request, $data, $product, $image, $newPath, $wasPrimary) {
                if ($newPath) {
                    $image->file_path = $newPath;
                }

                $image->alt_text = $data['alt_text'] ?? null;

                if (array_key_exists('position', $data) && $data['position'] !== null) {
                    $image->position = (int) $data['position'];
                }

                $image->is_primary = $request->boolean('is_primary');
                $image->save();

                if ($image->is_primary) {
                    $this->setPrimaryImage($product, $image);
                    return;
                }

                if ($wasPrimary) {
                    $next = $product->images()
                        ->where('id', '!=', $image->id)
                        ->orderBy('position')
                        ->orderBy('id')
                        ->first();

                    if ($next) {
                        $this->setPrimaryImage($product, $next);
                    } else {
                        $product->primary_image = null;
                        $product->save();
                    }
                }
            });
        } catch (\Throwable $e) {
            if ($newPath) {
                Storage::disk($disk)->delete($newPath);
            }

            throw $e;
        }

        if ($newPath && $oldPath && Storage::disk($disk)->exists($oldPath)) {
            Storage::disk($disk)->delete($oldPath);
        }

        return redirect()
            ->route('admin.products.images.index', $product)
            ->with('status', 'Image updated.');
    }


    public function makePrimary(ProductImage $image)
    {
        $product = $image->product;

        abort_unless($product, 404);

        DB::transaction(function () use ($product, $image) {
            $this->setPrimaryImage($product, $image);
        });

        return redirect()
            ->route('admin.products.images.index', $product)
            ->with('status', 'Primary image updated.');
    }

    public function destroy(ProductImage $image)
    {
        $product = $image->product;
        $disk = $this->mediaDisk();
        $oldPath = $image->file_path;
        $wasPrimary = (bool) $image->is_primary;

        DB::transaction(function () use ($image, $product, $wasPrimary) {
            $image->delete();

            if ($wasPrimary) {
                $next = $product->images()
                    ->orderBy('position')
                    ->orderBy('id')
                    ->first();

                if ($next) {
                    $this->setPrimaryImage($product, $next);
                } else {
                    $product->primary_image = null;
                    $product->save();
                }
            }
        });

        if ($oldPath && Storage::disk($disk)->exists($oldPath)) {
            Storage::disk($disk)->delete($oldPath);
        }

        return redirect()
            ->route('admin.products.images.index', $product)
            ->with('status', 'Image deleted.');
    }

    protected function setPrimaryImage(Product $product, ProductImage $image): void
    {
        $product->images()
            ->where('id', '!=', $image->id)
            ->update(['is_primary' => false]);

        if (! $image->is_primary) {
            $image->is_primary = true;
            $image->save();
        }

        $product->primary_image = $image->file_path;
        $product->save();
    }

    protected function mediaDisk(): string
    {
        return config('filesystems.default', 'public');
    }

    protected function throwIfPhpUploadFailed(string $field): void
    {
        if (! isset($_FILES[$field])) {
            return;
        }

        $errors = $_FILES[$field]['error'] ?? UPLOAD_ERR_OK;

        foreach ($this->flattenUploadErrors($errors) as $error) {
            $error = (int) $error;

            if (in_array($error, [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE], true)) {
                continue;
            }

            throw ValidationException::withMessages([
                $field => $this->phpUploadErrorMessage($error),
            ]);
        }
    }

    /**
     * @return array<int, int|string>
     */
    protected function flattenUploadErrors(mixed $errors): array
    {
        if (! is_array($errors)) {
            return [$errors];
        }

        $flat = [];

        array_walk_recursive($errors, function ($error) use (&$flat) {
            $flat[] = $error;
        });

        return $flat;
    }

    protected function phpUploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE =>
                'The image exceeds PHP upload_max_filesize. Check the active php.ini used by this request.',
            UPLOAD_ERR_FORM_SIZE =>
                'The image exceeds the MAX_FILE_SIZE limit from the HTML form. Remove that hidden field or increase it.',
            UPLOAD_ERR_PARTIAL =>
                'The image was only partially uploaded. Please try again.',
            UPLOAD_ERR_NO_FILE =>
                'No image file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR =>
                'PHP is missing a temporary upload folder. Check upload_tmp_dir.',
            UPLOAD_ERR_CANT_WRITE =>
                'PHP could not write the uploaded image to the temporary folder.',
            UPLOAD_ERR_EXTENSION =>
                'A PHP extension stopped the image upload.',
            default =>
                'The image failed during the PHP upload step.',
        };
    }

    protected function uploadDebugSnapshot(string $field): array
    {
        return [
            'loaded_php_ini' => php_ini_loaded_file(),
            'scanned_ini_files' => php_ini_scanned_files(),
            'file_uploads' => ini_get('file_uploads'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'upload_tmp_dir' => ini_get('upload_tmp_dir'),
            'sys_temp_dir' => ini_get('sys_temp_dir'),
            'sys_get_temp_dir' => sys_get_temp_dir(),
            'files_entry' => $_FILES[$field] ?? null,
        ];
    }
}