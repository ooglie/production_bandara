<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\MediaPathService;
use App\Services\MediaReferenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductImageController extends Controller
{
    public function __construct(
        protected MediaPathService $media,
        protected MediaReferenceService $mediaReferences,
    ) {
    }

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

        $storedPaths = [];
        $createdImages = collect();
        $makePrimary = $request->boolean('is_primary');

        try {
            // Store files before opening the database transaction so large image
            // uploads never keep product rows or indexes locked unnecessarily.
            foreach ($files as $file) {
                $storedPaths[] = $this->media->storePublic(
                    $file,
                    $this->media->productImagesDirectory($product),
                    'image'
                );
            }

            DB::transaction(function () use ($product, $data, $storedPaths, $makePrimary, &$createdImages): void {
                $startPosition = array_key_exists('position', $data) && $data['position'] !== null
                    ? (int) $data['position']
                    : ((int) $product->images()->max('position') + 1);

                $hasPrimaryImage = $product->images()->where('is_primary', true)->exists()
                    || filled($product->primary_image);

                foreach ($storedPaths as $index => $path) {
                    $image = new ProductImage([
                        'file_path'  => $path,
                        'alt_text'   => $data['alt_text'] ?? null,
                        'position'   => $startPosition + $index,
                        'is_primary' => false,
                    ]);

                    $product->images()->save($image);
                    $createdImages->push($image);
                }

                $primaryImage = ($makePrimary || ! $hasPrimaryImage)
                    ? $createdImages->first()
                    : null;

                if ($primaryImage) {
                    $this->setPrimaryImage($product, $primaryImage);
                }
            });
        } catch (\Throwable $e) {
            foreach ($storedPaths as $storedPath) {
                $this->media->deleteFromDisks($storedPath, [$this->media->publicDisk()]);
            }

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

        $oldPath = $image->file_path;
        $newPath = null;

        if ($request->hasFile('image')) {
            $newPath = $this->media->storePublic(
                $request->file('image'),
                $this->media->productImagesDirectory($product),
                'image'
            );
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
                $this->media->deleteFromDisks($newPath, [$this->media->publicDisk()]);
            }

            throw $e;
        }

        if ($newPath && $oldPath) {
            $this->mediaReferences->deletePublicFileIfUnreferenced($oldPath);
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

        if ($oldPath) {
            $this->mediaReferences->deletePublicFileIfUnreferenced($oldPath);
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