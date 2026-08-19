<?php

namespace Tests\Unit;

use App\Services\MediaPathService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaPathServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'media.public_disk' => 'public',
            'media.private_disk' => 'local',
            'media.public_root' => 'media',
        ]);
    }

    public function test_it_builds_stable_readable_entity_directories(): void
    {
        $paths = app(MediaPathService::class);

        $this->assertSame(
            'media/products/184-pork-belly-full/images',
            $paths->productImagesDirectoryFor(184, 'Pork Belly Full')
        );
        $this->assertSame(
            'media/recipes/27-roast-pork-belly/images',
            $paths->recipeImagesDirectoryFor(27, 'Roast Pork Belly')
        );
        $this->assertSame(
            'media/avatars/39',
            $paths->avatarDirectoryFor(39)
        );
        $this->assertSame(
            'media/home/occasion-family/images',
            $paths->homeSectionImagesDirectoryFor('Occasion Family')
        );
        $this->assertSame(
            'media/home/product-collections/8-weekend-brunch/images',
            $paths->productCollectionImagesDirectoryFor(8, 'Weekend Brunch')
        );
        $this->assertSame(
            'tickets/ba-tkt-000123/attachments',
            $paths->ticketAttachmentDirectoryFor(123, 'BA-TKT-000123')
        );
    }

    public function test_it_normalizes_legacy_public_paths_without_accepting_traversal(): void
    {
        $paths = app(MediaPathService::class);

        $this->assertSame('products/pork.jpg', $paths->normalizeStoredPath('/storage/products/pork.jpg'));
        $this->assertSame('products/pork.jpg', $paths->normalizeStoredPath('storage/app/public/products/pork.jpg'));
        $this->assertSame('media/products/1-pork/images/primary.jpg', $paths->normalizeStoredPath(
            'https://bandara.shop/storage/media/products/1-pork/images/primary.jpg?version=2'
        ));
        $this->assertNull($paths->normalizeStoredPath('../private/secret.txt'));
        $this->assertTrue($paths->isExternalReference('https://cdn.example.test/photo.jpg'));
        $this->assertFalse($paths->isExternalReference('https://bandara.shop/storage/products/pork.jpg'));
    }

    public function test_public_and_private_uploads_use_the_requested_directories(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $paths = app(MediaPathService::class);
        $image = UploadedFile::fake()->image('product.jpg');
        $attachment = UploadedFile::fake()->create('invoice.pdf', 20, 'application/pdf');

        $imagePath = $paths->storePublic(
            $image,
            $paths->productImagesDirectoryFor(10, 'Salmon Fillet'),
            'image'
        );
        $attachmentPath = $paths->storePrivate(
            $attachment,
            $paths->ticketAttachmentDirectoryFor(20, 'BA-TKT-000020'),
            'attachment'
        );

        $this->assertStringStartsWith('media/products/10-salmon-fillet/images/image-', $imagePath);
        $this->assertStringEndsWith('.jpg', $imagePath);
        Storage::disk('public')->assertExists($imagePath);

        $this->assertStringStartsWith('tickets/ba-tkt-000020/attachments/attachment-', $attachmentPath);
        $this->assertStringEndsWith('.pdf', $attachmentPath);
        Storage::disk('local')->assertExists($attachmentPath);
        Storage::disk('public')->assertMissing($attachmentPath);
    }
}
