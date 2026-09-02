<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\HomeSection;
use App\Models\HomeSectionItem;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\Recipe;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class MediaPathService
{
    public function publicDisk(): string
    {
        return (string) config('media.public_disk', 'public');
    }

    public function privateDisk(): string
    {
        return (string) config('media.private_disk', 'local');
    }

    public function migrationReportsDirectory(): string
    {
        return trim((string) config('media.migration_reports_dir', 'media-migrations'), '/');
    }

    public function productImagesDirectory(Product $product): string
    {
        return $this->productImagesDirectoryFor(
            (int) $product->getKey(),
            (string) ($product->slug ?: $product->name)
        );
    }

    public function productImagesDirectoryFor(int $productId, ?string $label): string
    {
        return $this->join(
            $this->publicRoot(),
            (string) config('media.paths.products', 'products'),
            $this->entitySegment($productId, $label, 'product'),
            'images'
        );
    }

    public function recipeImagesDirectory(Recipe $recipe): string
    {
        return $this->recipeImagesDirectoryFor(
            (int) $recipe->getKey(),
            $this->recipeLabel($recipe)
        );
    }

    public function recipeImagesDirectoryFor(int $recipeId, ?string $label): string
    {
        return $this->join(
            $this->publicRoot(),
            (string) config('media.paths.recipes', 'recipes'),
            $this->entitySegment($recipeId, $label, 'recipe'),
            'images'
        );
    }

    public function recipeVideosDirectory(Recipe $recipe): string
    {
        return $this->recipeVideosDirectoryFor(
            (int) $recipe->getKey(),
            $this->recipeLabel($recipe)
        );
    }

    public function recipeVideosDirectoryFor(int $recipeId, ?string $label): string
    {
        return $this->join(
            $this->publicRoot(),
            (string) config('media.paths.recipes', 'recipes'),
            $this->entitySegment($recipeId, $label, 'recipe'),
            'videos'
        );
    }

    public function avatarDirectory(User $user): string
    {
        return $this->avatarDirectoryFor((int) $user->getKey());
    }

    public function avatarDirectoryFor(int $userId): string
    {
        return $this->join(
            $this->publicRoot(),
            (string) config('media.paths.avatars', 'avatars'),
            (string) max(1, $userId)
        );
    }

    public function homeSectionImagesDirectory(HomeSection|string $section): string
    {
        $key = $section instanceof HomeSection ? (string) $section->key : $section;

        return $this->homeSectionImagesDirectoryFor($key);
    }

    public function homeSectionImagesDirectoryFor(?string $key): string
    {
        $segment = $this->safeSegment($key, 'section');

        return $this->join(
            $this->publicRoot(),
            (string) config('media.paths.home', 'home'),
            $segment,
            'images'
        );
    }

    public function homeItemImagesDirectory(HomeSection $section, HomeSectionItem $item): string
    {
        return $this->homeItemImagesDirectoryFor(
            (string) $section->key,
            (int) $item->getKey(),
            (string) ($item->title ?: $item->item_type ?: 'item')
        );
    }

    public function homeItemImagesDirectoryFor(?string $sectionKey, int $itemId, ?string $label): string
    {
        return $this->join(
            $this->publicRoot(),
            (string) config('media.paths.home', 'home'),
            $this->safeSegment($sectionKey, 'section'),
            'items',
            $this->entitySegment($itemId, $label, 'item'),
            'images'
        );
    }

    public function productCollectionImagesDirectory(ProductCollection $collection): string
    {
        return $this->productCollectionImagesDirectoryFor(
            (int) $collection->getKey(),
            (string) ($collection->slug ?: $collection->name)
        );
    }

    public function productCollectionImagesDirectoryFor(int $collectionId, ?string $label): string
    {
        return $this->join(
            $this->publicRoot(),
            (string) config('media.paths.home', 'home'),
            (string) config('media.paths.product_collections', 'product-collections'),
            $this->entitySegment($collectionId, $label, 'collection'),
            'images'
        );
    }

    public function announcementImagesDirectory(Announcement $announcement): string
    {
        return $this->announcementImagesDirectoryFor(
            (int) $announcement->getKey(),
            (string) ($announcement->title ?: $announcement->label ?: 'announcement')
        );
    }

    public function announcementImagesDirectoryFor(int $announcementId, ?string $label): string
    {
        return $this->join(
            $this->publicRoot(),
            (string) config('media.paths.home', 'home'),
            (string) config('media.paths.announcements', 'announcements'),
            $this->entitySegment($announcementId, $label, 'announcement'),
            'images'
        );
    }

    public function ticketAttachmentDirectory(Ticket $ticket): string
    {
        return $this->ticketAttachmentDirectoryFor(
            (int) $ticket->getKey(),
            (string) ($ticket->ticket_number ?: '')
        );
    }

    public function ticketAttachmentDirectoryFor(int $ticketId, ?string $ticketNumber): string
    {
        $ticketSegment = $this->safeSegment(
            filled($ticketNumber) ? $ticketNumber : 'ticket-'.$ticketId,
            'ticket-'.$ticketId,
            96
        );

        return $this->join(
            (string) config('media.paths.tickets', 'tickets'),
            $ticketSegment,
            'attachments'
        );
    }

    public function storePublic(UploadedFile $file, string $directory, string $role = 'image'): string
    {
        return $this->storeUploadedFile($file, $this->publicDisk(), $directory, $role);
    }

    public function storePrivate(UploadedFile $file, string $directory, string $role = 'attachment'): string
    {
        return $this->storeUploadedFile($file, $this->privateDisk(), $directory, $role);
    }

    public function duplicatePublicFile(?string $sourcePath, string $directory, string $role = 'image'): ?string
    {
        $sourcePath = $this->normalizeStoredPath($sourcePath);

        if (! $sourcePath || ! Storage::disk($this->publicDisk())->exists($sourcePath)) {
            return null;
        }

        $extension = $this->extensionFromPath($sourcePath);
        $destination = $this->join($directory, $this->generatedFilename($role, $extension));

        if (! Storage::disk($this->publicDisk())->copy($sourcePath, $destination)) {
            throw new RuntimeException('The media file could not be duplicated.');
        }

        return $destination;
    }

    public function deleteFromDisks(?string $path, array $disks): void
    {
        $normalized = $this->normalizeStoredPath($path);

        if (! $normalized || $this->isExternalReference($path)) {
            return;
        }

        foreach (array_values(array_unique(array_filter($disks))) as $disk) {
            if (Storage::disk($disk)->exists($normalized)) {
                Storage::disk($disk)->delete($normalized);
            }
        }
    }

    public function normalizeStoredPath(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        $path = str_replace('\\', '/', $path);

        if (Str::startsWith($path, ['data:', '//'])) {
            return null;
        }

        if (preg_match('#^https?://#i', $path)) {
            $urlPath = parse_url($path, PHP_URL_PATH);

            if (! is_string($urlPath) || ! Str::contains($urlPath, '/storage/')) {
                return null;
            }

            $path = Str::after($urlPath, '/storage/');
        } else {
            $path = preg_replace('/[?#].*$/', '', $path) ?? $path;
        }

        $path = ltrim($path, '/');

        foreach ([
            'storage/app/public/',
            'storage/app/private/',
            'public/storage/',
            'storage/',
            'public/',
        ] as $prefix) {
            if (Str::startsWith($path, $prefix)) {
                $path = Str::after($path, $prefix);
                break;
            }
        }

        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                return null;
            }

            $segments[] = $segment;
        }

        $normalized = implode('/', $segments);

        return $normalized !== '' ? $normalized : null;
    }

    public function isExternalReference(?string $path): bool
    {
        $path = trim((string) $path);

        if ($path === '') {
            return false;
        }

        if (Str::startsWith($path, ['data:', '//'])) {
            return true;
        }

        if (! preg_match('#^https?://#i', $path)) {
            return false;
        }

        $urlPath = parse_url($path, PHP_URL_PATH);

        return ! is_string($urlPath) || ! Str::contains($urlPath, '/storage/');
    }

    public function deterministicPath(
        string $directory,
        string $role,
        int|string|null $identity,
        ?string $sourcePath,
        ?string $fallbackOriginalName = null
    ): string {
        $extension = $this->extensionFromPath($sourcePath, $fallbackOriginalName);
        $identityPart = filled($identity) ? '-'.$this->safeSegment((string) $identity, 'file', 48) : '';
        $filename = $this->safeSegment($role, 'file', 80).$identityPart.'.'.$extension;

        return $this->join($directory, $filename);
    }

    public function extensionFromPath(?string $path, ?string $fallbackOriginalName = null): string
    {
        $candidate = $this->normalizeStoredPath($path) ?: trim((string) $fallbackOriginalName);
        $extension = strtolower((string) pathinfo($candidate, PATHINFO_EXTENSION));
        $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?? '';

        return $extension !== '' && strlen($extension) <= 12 ? $extension : 'bin';
    }

    protected function storeUploadedFile(
        UploadedFile $file,
        string $disk,
        string $directory,
        string $role
    ): string {
        $extension = strtolower((string) ($file->extension() ?: $file->getClientOriginalExtension() ?: 'bin'));
        $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?: 'bin';
        $path = $file->storeAs(
            trim($directory, '/'),
            $this->generatedFilename($role, $extension),
            $disk
        );

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('The uploaded media file could not be stored.');
        }

        return $path;
    }

    protected function generatedFilename(string $role, string $extension): string
    {
        return $this->safeSegment($role, 'file', 64)
            .'-'.Str::lower((string) Str::ulid())
            .'.'.$extension;
    }

    protected function publicRoot(): string
    {
        return trim((string) config('media.public_root', 'media'), '/');
    }

    protected function recipeLabel(Recipe $recipe): string
    {
        $slug = $recipe->tr('slug');
        $title = $recipe->tr('title');

        return (string) ($slug ?: $title ?: 'recipe');
    }

    protected function entitySegment(int $id, ?string $label, string $fallback): string
    {
        return max(1, $id).'-'.$this->safeSegment($label, $fallback, 96);
    }

    protected function safeSegment(?string $value, string $fallback, int $limit = 96): string
    {
        $slug = Str::slug((string) $value);

        if ($slug === '') {
            $slug = Str::slug($fallback) ?: 'item';
        }

        return Str::limit($slug, $limit, '');
    }

    protected function join(string ...$segments): string
    {
        return implode('/', array_values(array_filter(
            array_map(static fn (string $segment): string => trim($segment, '/'), $segments),
            static fn (string $segment): bool => $segment !== ''
        )));
    }
}
