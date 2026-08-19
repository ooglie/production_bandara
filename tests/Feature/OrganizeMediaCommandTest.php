<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\MediaPathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrganizeMediaCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');

        config([
            'cache.default' => 'array',
            'media.public_disk' => 'public',
            'media.private_disk' => 'local',
            'media.public_root' => 'media',
            'media.migration_reports_dir' => 'media-migrations',
        ]);
    }

    public function test_media_organization_is_copy_verify_commit_and_rollback_safe(): void
    {
        $product = Product::query()->create([
            'name' => 'Pork Belly Full',
            'slug' => 'pork-belly-full',
            'primary_image' => 'products/legacy-pork.jpg',
            'base_price' => 1000,
            'is_active' => true,
        ]);

        Storage::disk('public')->put('products/legacy-pork.jpg', 'product-image-bytes');

        $this->artisan('bandara:organize-media', ['--dry-run' => true])
            ->expectsOutputToContain('Dry run completed')
            ->assertSuccessful();

        $manifestPath = collect(Storage::disk('local')->allFiles('media-migrations'))
            ->first(fn (string $path): bool => str_ends_with($path, '/manifest.json'));

        $this->assertNotNull($manifestPath);
        $runId = basename(dirname($manifestPath));
        $destination = app(MediaPathService::class)->deterministicPath(
            app(MediaPathService::class)->productImagesDirectory($product),
            'primary',
            null,
            'products/legacy-pork.jpg'
        );

        $this->artisan('bandara:organize-media', ['--copy' => true, '--run-id' => $runId])
            ->assertSuccessful();
        Storage::disk('public')->assertExists($destination);
        Storage::disk('public')->assertExists('products/legacy-pork.jpg');

        $this->artisan('bandara:organize-media', ['--verify' => true, '--run-id' => $runId])
            ->expectsOutputToContain('Verification passed')
            ->assertSuccessful();

        $this->artisan('bandara:organize-media', [
            '--commit' => true,
            '--run-id' => $runId,
            '--force' => true,
        ])->assertSuccessful();

        $this->assertSame($destination, $product->fresh()->primary_image);
        Storage::disk('public')->assertExists('products/legacy-pork.jpg');

        $this->artisan('bandara:organize-media', [
            '--rollback' => true,
            '--run-id' => $runId,
            '--force' => true,
        ])->assertSuccessful();

        $this->assertSame('products/legacy-pork.jpg', $product->fresh()->primary_image);
        Storage::disk('public')->assertExists($destination);
    }

    public function test_missing_source_file_prevents_copy_from_reporting_success(): void
    {
        Product::query()->create([
            'name' => 'Missing Product Image',
            'slug' => 'missing-product-image',
            'primary_image' => 'products/does-not-exist.jpg',
            'base_price' => 100,
            'is_active' => true,
        ]);

        $this->artisan('bandara:organize-media', ['--dry-run' => true])->assertSuccessful();

        $manifestPath = collect(Storage::disk('local')->allFiles('media-migrations'))
            ->first(fn (string $path): bool => str_ends_with($path, '/manifest.json'));
        $runId = basename(dirname((string) $manifestPath));

        $this->artisan('bandara:organize-media', ['--copy' => true, '--run-id' => $runId])
            ->expectsOutputToContain('One or more files were not copied')
            ->assertFailed();
    }
}
