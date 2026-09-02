<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\HomeSection;
use App\Models\HomeSectionItem;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\ProductImage;
use App\Models\Recipe;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class MediaOrganizationService
{
    public const MANIFEST_VERSION = 1;

    public function __construct(protected MediaPathService $paths)
    {
    }

    /**
     * Build a read-only migration plan. The only files written are private
     * diagnostic reports under storage/app/private/media-migrations.
     *
     * @return array{run_id:string, summary:array<string,int>, run_directory:string}
     */
    public function prepare(): array
    {
        $runId = now()->format('Ymd_His').'-'.Str::lower(Str::random(6));
        $entries = [];

        $this->scanProducts($entries);
        $this->scanRecipes($entries);
        $this->scanTicketAttachments($entries);
        $this->scanAvatars($entries);
        $this->scanHomeMedia($entries);
        $this->scanProductCollections($entries);
        $this->scanAnnouncements($entries);

        $entries = array_values($entries);
        $summary = $this->summarizeEntries($entries);
        $runDirectory = $this->runDirectory($runId);

        $manifest = [
            'version' => self::MANIFEST_VERSION,
            'run_id' => $runId,
            'created_at' => now()->toIso8601String(),
            'application' => [
                'name' => (string) config('app.name'),
                'url' => (string) config('app.url'),
                'environment' => (string) app()->environment(),
            ],
            'disks' => [
                'public' => $this->paths->publicDisk(),
                'private' => $this->paths->privateDisk(),
            ],
            'summary' => $summary,
            'entries' => $entries,
        ];

        $this->putJson($runDirectory.'/manifest.json', $manifest);
        $this->putJson($runDirectory.'/database-backup.json', [
            'version' => self::MANIFEST_VERSION,
            'run_id' => $runId,
            'created_at' => now()->toIso8601String(),
            'references' => array_map(static fn (array $entry): array => [
                'id' => $entry['id'],
                'table' => $entry['table'],
                'record_id' => $entry['record_id'],
                'column' => $entry['column'],
                'json_path' => $entry['json_path'],
                'old_path_raw' => $entry['old_path_raw'],
                'new_path' => $entry['new_path'],
            ], $entries),
        ]);
        $this->putCsv($runDirectory.'/manifest.csv', $entries, [
            'id', 'kind', 'table', 'record_id', 'column', 'json_path',
            'owner_id', 'owner_label', 'old_path_raw', 'old_path', 'new_path',
            'source_disk', 'destination_disk', 'status', 'source_size', 'note',
        ]);

        $missing = array_values(array_filter(
            $entries,
            static fn (array $entry): bool => in_array($entry['status'], ['missing', 'invalid', 'ambiguous'], true)
        ));
        $this->putCsv($runDirectory.'/missing-files.csv', $missing, [
            'id', 'kind', 'owner_id', 'owner_label', 'old_path_raw', 'old_path',
            'source_disk', 'status', 'note',
        ]);

        $duplicates = $this->duplicateReferenceRows($entries);
        $this->putCsv($runDirectory.'/duplicate-references.csv', $duplicates, [
            'source_disk', 'old_path', 'reference_count', 'entry_ids', 'kinds',
        ]);

        $unreferenced = $this->unreferencedLegacyFiles($entries);
        $this->putCsv($runDirectory.'/unreferenced-files.csv', $unreferenced, [
            'disk', 'path', 'size',
        ]);

        $this->putJson($runDirectory.'/run-summary.json', [
            'run_id' => $runId,
            'prepared_at' => now()->toIso8601String(),
            'summary' => $summary,
            'duplicate_reference_groups' => count($duplicates),
            'unreferenced_legacy_files' => count($unreferenced),
        ]);

        return [
            'run_id' => $runId,
            'summary' => $summary,
            'run_directory' => $runDirectory,
        ];
    }

    /** @return array{summary:array<string,int>, results:array<int,array<string,mixed>>} */
    public function copy(string $runId): array
    {
        $manifest = $this->loadManifest($runId);
        $results = [];
        $transferCache = [];

        foreach ($manifest['entries'] as $entry) {
            $result = $this->copyEntry($entry, $runId, $transferCache);
            $results[] = $result;
        }

        $summary = $this->summarizeOperationResults($results);
        $runDirectory = $this->runDirectory($runId);

        $payload = [
            'run_id' => $runId,
            'copied_at' => now()->toIso8601String(),
            'summary' => $summary,
            'results' => $results,
        ];

        $this->putJson($runDirectory.'/copy-results.json', $payload);
        $this->putCsv($runDirectory.'/copy-results.csv', $results, [
            'id', 'kind', 'old_path', 'new_path', 'source_disk',
            'destination_disk', 'result', 'source_checksum',
            'destination_checksum', 'note',
        ]);

        return ['summary' => $summary, 'results' => $results];
    }

    /** @return array{success:bool, summary:array<string,int>, results:array<int,array<string,mixed>>} */
    public function verify(string $runId): array
    {
        $manifest = $this->loadManifest($runId);
        $results = [];
        $checksumCache = [];

        foreach ($manifest['entries'] as $entry) {
            $results[] = $this->verifyEntry($entry, $checksumCache);
        }

        $summary = $this->summarizeOperationResults($results);
        $blockingResults = array_filter($results, static function (array $result): bool {
            if (in_array($result['result'], ['external', 'skipped_empty'], true)) {
                return false;
            }

            return ! in_array($result['result'], ['verified', 'already_organized'], true);
        });
        $success = count($blockingResults) === 0;
        $runDirectory = $this->runDirectory($runId);

        $payload = [
            'run_id' => $runId,
            'verified_at' => now()->toIso8601String(),
            'success' => $success,
            'summary' => $summary,
            'results' => $results,
        ];

        $this->putJson($runDirectory.'/verification-results.json', $payload);
        $this->putCsv($runDirectory.'/verification-results.csv', $results, [
            'id', 'kind', 'old_path', 'new_path', 'source_disk',
            'destination_disk', 'result', 'source_size', 'destination_size',
            'source_checksum', 'destination_checksum', 'note',
        ]);

        return [
            'success' => $success,
            'summary' => $summary,
            'results' => $results,
        ];
    }

    /** @return array{success:bool, summary:array<string,int>, results:array<int,array<string,mixed>>} */
    public function commit(string $runId): array
    {
        $manifest = $this->loadManifest($runId);
        $verification = $this->loadJson($this->runDirectory($runId).'/verification-results.json');

        if (! is_array($verification)) {
            throw new RuntimeException('Verification results are missing. Run --verify before --commit.');
        }

        if (! ($verification['success'] ?? false)) {
            throw new RuntimeException('Verification did not pass. Fix the reported files before committing.');
        }

        $verifiedById = collect($verification['results'] ?? [])->keyBy('id');
        $results = [];

        DB::transaction(function () use ($manifest, $verifiedById, &$results): void {
            foreach ($manifest['entries'] as $entry) {
                $verified = $verifiedById->get($entry['id']);
                $verificationResult = is_array($verified) ? ($verified['result'] ?? null) : null;

                if (! in_array($verificationResult, ['verified', 'already_organized'], true)) {
                    $results[] = $this->operationResult($entry, 'skipped_unverified', 'The destination was not verified.');
                    continue;
                }

                try {
                    $results[] = $this->updateDatabaseReference($entry, $entry['old_path_raw'], $entry['new_path']);
                } catch (\Throwable $e) {
                    $results[] = $this->operationResult($entry, 'failed', $e->getMessage());
                    throw $e;
                }
            }
        });

        $summary = $this->summarizeOperationResults($results);
        $success = ! collect($results)->contains(fn (array $row): bool => $row['result'] === 'failed');
        $runDirectory = $this->runDirectory($runId);

        $payload = [
            'run_id' => $runId,
            'committed_at' => now()->toIso8601String(),
            'success' => $success,
            'summary' => $summary,
            'results' => $results,
        ];

        $this->putJson($runDirectory.'/commit-results.json', $payload);
        $this->putCsv($runDirectory.'/commit-results.csv', $results, [
            'id', 'kind', 'table', 'record_id', 'column', 'json_path',
            'result', 'note',
        ]);

        return [
            'success' => $success,
            'summary' => $summary,
            'results' => $results,
        ];
    }

    /** @return array{success:bool, summary:array<string,int>, results:array<int,array<string,mixed>>} */
    public function rollback(string $runId): array
    {
        $manifest = $this->loadManifest($runId);
        $commitResults = $this->loadJson($this->runDirectory($runId).'/commit-results.json');

        if (! is_array($commitResults)) {
            throw new RuntimeException('Commit results are missing. This run has not been committed.');
        }

        $committedIds = collect($commitResults['results'] ?? [])
            ->filter(fn (array $row): bool => in_array($row['result'] ?? null, ['updated', 'already_updated'], true))
            ->pluck('id')
            ->flip();

        $results = [];

        DB::transaction(function () use ($manifest, $committedIds, &$results): void {
            foreach (array_reverse($manifest['entries']) as $entry) {
                if (! $committedIds->has($entry['id'])) {
                    $results[] = $this->operationResult($entry, 'skipped_not_committed', 'This entry was not changed by the commit.');
                    continue;
                }

                try {
                    $results[] = $this->updateDatabaseReference($entry, $entry['new_path'], $entry['old_path_raw']);
                } catch (\Throwable $e) {
                    $results[] = $this->operationResult($entry, 'failed', $e->getMessage());
                    throw $e;
                }
            }
        });

        $summary = $this->summarizeOperationResults($results);
        $success = ! collect($results)->contains(fn (array $row): bool => $row['result'] === 'failed');
        $runDirectory = $this->runDirectory($runId);

        $payload = [
            'run_id' => $runId,
            'rolled_back_at' => now()->toIso8601String(),
            'success' => $success,
            'summary' => $summary,
            'results' => $results,
        ];

        $this->putJson($runDirectory.'/rollback-results.json', $payload);
        $this->putCsv($runDirectory.'/rollback-results.csv', $results, [
            'id', 'kind', 'table', 'record_id', 'column', 'json_path',
            'result', 'note',
        ]);

        return [
            'success' => $success,
            'summary' => $summary,
            'results' => $results,
        ];
    }

    protected function scanProducts(array &$entries): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'primary_image')) {
            return;
        }

        $products = Product::query()
            ->withTrashed()
            ->get(['id', 'name', 'slug', 'primary_image'])
            ->keyBy('id');

        $galleryDestinationBySource = [];

        if (Schema::hasTable('product_images')) {
            $column = Schema::hasColumn('product_images', 'file_path')
                ? 'file_path'
                : (Schema::hasColumn('product_images', 'path') ? 'path' : null);

            if ($column) {
                ProductImage::query()
                    ->withTrashed()
                    ->orderBy('id')
                    ->chunkById(200, function ($images) use (&$entries, $products, $column, &$galleryDestinationBySource): void {
                        foreach ($images as $image) {
                            /** @var Product|null $product */
                            $product = $products->get((int) $image->product_id);
                            $rawPath = (string) ($image->{$column} ?? '');

                            if (! $product) {
                                $entries[] = $this->invalidOwnerEntry(
                                    'product_image',
                                    'product_images',
                                    (int) $image->id,
                                    $column,
                                    $rawPath,
                                    (int) $image->product_id,
                                    'Missing product owner'
                                );
                                continue;
                            }

                            $normalized = $this->paths->normalizeStoredPath($rawPath);
                            $directory = $this->paths->productImagesDirectory($product);
                            $destination = $this->paths->deterministicPath(
                                $directory,
                                'image',
                                (int) $image->id,
                                $rawPath
                            );

                            $entry = $this->buildEntry(
                                id: 'product_image:'.$image->id.':'.$column,
                                kind: 'product_image',
                                table: 'product_images',
                                recordId: (int) $image->id,
                                column: $column,
                                rawPath: $rawPath,
                                newPath: $destination,
                                destinationDisk: $this->paths->publicDisk(),
                                preferredSourceDisks: [$this->paths->publicDisk(), $this->paths->privateDisk()],
                                ownerId: (int) $product->id,
                                ownerLabel: (string) $product->name,
                            );

                            $entries[] = $entry;

                            if ($normalized) {
                                $galleryDestinationBySource[$product->id.'|'.$normalized] = $destination;
                            }
                        }
                    });
            }
        }

        foreach ($products as $product) {
            $rawPath = (string) ($product->primary_image ?? '');
            if (trim($rawPath) === '') {
                continue;
            }

            $normalized = $this->paths->normalizeStoredPath($rawPath);
            $destination = $normalized
                ? ($galleryDestinationBySource[$product->id.'|'.$normalized] ?? null)
                : null;

            $destination ??= $this->paths->deterministicPath(
                $this->paths->productImagesDirectory($product),
                'primary',
                null,
                $rawPath
            );

            $entries[] = $this->buildEntry(
                id: 'product:'.$product->id.':primary_image',
                kind: 'product_primary',
                table: 'products',
                recordId: (int) $product->id,
                column: 'primary_image',
                rawPath: $rawPath,
                newPath: $destination,
                destinationDisk: $this->paths->publicDisk(),
                preferredSourceDisks: [$this->paths->publicDisk(), $this->paths->privateDisk()],
                ownerId: (int) $product->id,
                ownerLabel: (string) $product->name,
            );
        }
    }

    protected function scanRecipes(array &$entries): void
    {
        if (! Schema::hasTable('recipes')) {
            return;
        }

        Recipe::query()
            ->withTrashed()
            ->orderBy('id')
            ->chunkById(200, function ($recipes) use (&$entries): void {
                foreach ($recipes as $recipe) {
                    $label = (string) ($recipe->tr('title') ?: 'Recipe #'.$recipe->id);

                    if (filled($recipe->image_path)) {
                        $entries[] = $this->buildEntry(
                            id: 'recipe:'.$recipe->id.':image_path',
                            kind: 'recipe_image',
                            table: 'recipes',
                            recordId: (int) $recipe->id,
                            column: 'image_path',
                            rawPath: (string) $recipe->image_path,
                            newPath: $this->paths->deterministicPath(
                                $this->paths->recipeImagesDirectory($recipe),
                                'cover',
                                null,
                                (string) $recipe->image_path
                            ),
                            destinationDisk: $this->paths->publicDisk(),
                            preferredSourceDisks: [$this->paths->publicDisk(), $this->paths->privateDisk()],
                            ownerId: (int) $recipe->id,
                            ownerLabel: $label,
                        );
                    }

                    if (filled($recipe->video_url)) {
                        $entries[] = $this->buildEntry(
                            id: 'recipe:'.$recipe->id.':video_url',
                            kind: 'recipe_video',
                            table: 'recipes',
                            recordId: (int) $recipe->id,
                            column: 'video_url',
                            rawPath: (string) $recipe->video_url,
                            newPath: $this->paths->deterministicPath(
                                $this->paths->recipeVideosDirectory($recipe),
                                'video',
                                null,
                                (string) $recipe->video_url
                            ),
                            destinationDisk: $this->paths->publicDisk(),
                            preferredSourceDisks: [$this->paths->publicDisk(), $this->paths->privateDisk()],
                            ownerId: (int) $recipe->id,
                            ownerLabel: $label,
                        );
                    }
                }
            });
    }

    protected function scanTicketAttachments(array &$entries): void
    {
        if (! Schema::hasTable('ticket_attachments')) {
            return;
        }

        $column = Schema::hasColumn('ticket_attachments', 'file_path')
            ? 'file_path'
            : (Schema::hasColumn('ticket_attachments', 'path') ? 'path' : null);

        if (! $column) {
            return;
        }

        TicketAttachment::query()
            ->with(['message.ticket', 'ticket'])
            ->orderBy('id')
            ->chunkById(200, function ($attachments) use (&$entries, $column): void {
                foreach ($attachments as $attachment) {
                    $ticket = $attachment->message?->ticket ?? $attachment->ticket;
                    $rawPath = (string) ($attachment->{$column} ?? '');

                    if (! $ticket) {
                        $entries[] = $this->invalidOwnerEntry(
                            'ticket_attachment',
                            'ticket_attachments',
                            (int) $attachment->id,
                            $column,
                            $rawPath,
                            null,
                            'Missing ticket owner'
                        );
                        continue;
                    }

                    $entries[] = $this->buildEntry(
                        id: 'ticket_attachment:'.$attachment->id.':'.$column,
                        kind: 'ticket_attachment',
                        table: 'ticket_attachments',
                        recordId: (int) $attachment->id,
                        column: $column,
                        rawPath: $rawPath,
                        newPath: $this->paths->deterministicPath(
                            $this->paths->ticketAttachmentDirectory($ticket),
                            'attachment',
                            (int) $attachment->id,
                            $rawPath,
                            (string) ($attachment->original_name ?? '')
                        ),
                        destinationDisk: $this->paths->privateDisk(),
                        preferredSourceDisks: [$this->paths->privateDisk(), $this->paths->publicDisk()],
                        ownerId: (int) $ticket->id,
                        ownerLabel: (string) ($ticket->ticket_number ?: '#'.$ticket->id),
                    );
                }
            });
    }

    protected function scanAvatars(array &$entries): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'avatar_path')) {
            return;
        }

        User::query()
            ->whereNotNull('avatar_path')
            ->where('avatar_path', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($users) use (&$entries): void {
                foreach ($users as $user) {
                    $rawPath = (string) $user->avatar_path;
                    $entries[] = $this->buildEntry(
                        id: 'user:'.$user->id.':avatar_path',
                        kind: 'avatar',
                        table: 'users',
                        recordId: (int) $user->id,
                        column: 'avatar_path',
                        rawPath: $rawPath,
                        newPath: $this->paths->deterministicPath(
                            $this->paths->avatarDirectory($user),
                            'avatar',
                            null,
                            $rawPath
                        ),
                        destinationDisk: $this->paths->publicDisk(),
                        preferredSourceDisks: [$this->paths->publicDisk(), $this->paths->privateDisk()],
                        ownerId: (int) $user->id,
                        ownerLabel: (string) ($user->name ?: $user->email ?: 'User #'.$user->id),
                    );
                }
            });
    }

    protected function scanHomeMedia(array &$entries): void
    {
        if (! Schema::hasTable('home_sections')) {
            return;
        }

        HomeSection::query()
            ->orderBy('id')
            ->chunkById(100, function ($sections) use (&$entries): void {
                foreach ($sections as $section) {
                    $directory = $this->paths->homeSectionImagesDirectory($section);
                    $label = (string) ($section->title ?: $section->key);

                    foreach ([
                        'image_path' => 'section-desktop',
                        'mobile_image_path' => 'section-mobile',
                    ] as $column => $role) {
                        if (! Schema::hasColumn('home_sections', $column) || ! filled($section->{$column})) {
                            continue;
                        }

                        $entries[] = $this->buildEntry(
                            id: 'home_section:'.$section->id.':'.$column,
                            kind: $column === 'image_path' ? 'home_section_image' : 'home_section_mobile_image',
                            table: 'home_sections',
                            recordId: (int) $section->id,
                            column: $column,
                            rawPath: (string) $section->{$column},
                            newPath: $this->paths->deterministicPath(
                                $directory,
                                $role,
                                (int) $section->id,
                                (string) $section->{$column}
                            ),
                            destinationDisk: $this->paths->publicDisk(),
                            preferredSourceDisks: [$this->paths->publicDisk(), $this->paths->privateDisk()],
                            ownerId: (int) $section->id,
                            ownerLabel: $label,
                        );
                    }

                    $fallbackImages = data_get($section->settings ?? [], 'fallback_images', []);
                    if (is_array($fallbackImages)) {
                        foreach ($fallbackImages as $index => $fallbackImage) {
                            if (! is_string($fallbackImage) || trim($fallbackImage) === '') {
                                continue;
                            }

                            $entries[] = $this->buildEntry(
                                id: 'home_section:'.$section->id.':settings:fallback_images.'.$index,
                                kind: 'home_section_fallback_image',
                                table: 'home_sections',
                                recordId: (int) $section->id,
                                column: 'settings',
                                jsonPath: 'fallback_images.'.$index,
                                rawPath: $fallbackImage,
                                newPath: $this->paths->deterministicPath(
                                    $directory,
                                    'fallback',
                                    (int) $index + 1,
                                    $fallbackImage
                                ),
                                destinationDisk: $this->paths->publicDisk(),
                                preferredSourceDisks: [$this->paths->publicDisk(), $this->paths->privateDisk()],
                                ownerId: (int) $section->id,
                                ownerLabel: $label,
                            );
                        }
                    }
                }
            });

        if (! Schema::hasTable('home_section_items') || ! Schema::hasColumn('home_section_items', 'image_path')) {
            return;
        }

        HomeSectionItem::query()
            ->with('section:id,key,title')
            ->whereNotNull('image_path')
            ->where('image_path', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($items) use (&$entries): void {
                foreach ($items as $item) {
                    $section = $item->section;
                    $rawPath = (string) $item->image_path;

                    if (! $section) {
                        $entries[] = $this->invalidOwnerEntry(
                            'home_item_image',
                            'home_section_items',
                            (int) $item->id,
                            'image_path',
                            $rawPath,
                            (int) $item->home_section_id,
                            'Missing home section owner'
                        );
                        continue;
                    }

                    $entries[] = $this->buildEntry(
                        id: 'home_item:'.$item->id.':image_path',
                        kind: 'home_item_image',
                        table: 'home_section_items',
                        recordId: (int) $item->id,
                        column: 'image_path',
                        rawPath: $rawPath,
                        newPath: $this->paths->deterministicPath(
                            $this->paths->homeItemImagesDirectory($section, $item),
                            'image',
                            (int) $item->id,
                            $rawPath
                        ),
                        destinationDisk: $this->paths->publicDisk(),
                        preferredSourceDisks: [$this->paths->publicDisk(), $this->paths->privateDisk()],
                        ownerId: (int) $section->id,
                        ownerLabel: (string) ($section->title ?: $section->key),
                    );
                }
            });
    }

    protected function scanProductCollections(array &$entries): void
    {
        if (! Schema::hasTable('product_collections') || ! Schema::hasColumn('product_collections', 'image_path')) {
            return;
        }

        ProductCollection::query()
            ->whereNotNull('image_path')
            ->where('image_path', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($collections) use (&$entries): void {
                foreach ($collections as $collection) {
                    $rawPath = (string) $collection->image_path;
                    $entries[] = $this->buildEntry(
                        id: 'product_collection:'.$collection->id.':image_path',
                        kind: 'product_collection_image',
                        table: 'product_collections',
                        recordId: (int) $collection->id,
                        column: 'image_path',
                        rawPath: $rawPath,
                        newPath: $this->paths->deterministicPath(
                            $this->paths->productCollectionImagesDirectory($collection),
                            'cover',
                            null,
                            $rawPath
                        ),
                        destinationDisk: $this->paths->publicDisk(),
                        preferredSourceDisks: [$this->paths->publicDisk(), $this->paths->privateDisk()],
                        ownerId: (int) $collection->id,
                        ownerLabel: (string) $collection->name,
                    );
                }
            });
    }

    protected function scanAnnouncements(array &$entries): void
    {
        if (! Schema::hasTable('announcements') || ! Schema::hasColumn('announcements', 'background_image_path')) {
            return;
        }

        Announcement::query()
            ->whereNotNull('background_image_path')
            ->where('background_image_path', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($announcements) use (&$entries): void {
                foreach ($announcements as $announcement) {
                    $rawPath = (string) $announcement->background_image_path;
                    $entries[] = $this->buildEntry(
                        id: 'announcement:'.$announcement->id.':background_image_path',
                        kind: 'announcement_image',
                        table: 'announcements',
                        recordId: (int) $announcement->id,
                        column: 'background_image_path',
                        rawPath: $rawPath,
                        newPath: $this->paths->deterministicPath(
                            $this->paths->announcementImagesDirectory($announcement),
                            'background',
                            null,
                            $rawPath
                        ),
                        destinationDisk: $this->paths->publicDisk(),
                        preferredSourceDisks: [$this->paths->publicDisk(), $this->paths->privateDisk()],
                        ownerId: (int) $announcement->id,
                        ownerLabel: (string) ($announcement->title ?: 'Announcement #'.$announcement->id),
                    );
                }
            });
    }

    protected function buildEntry(
        string $id,
        string $kind,
        string $table,
        int $recordId,
        string $column,
        string $rawPath,
        string $newPath,
        string $destinationDisk,
        array $preferredSourceDisks,
        ?int $ownerId,
        string $ownerLabel,
        ?string $jsonPath = null,
    ): array {
        $rawPath = trim($rawPath);
        $normalized = $this->paths->normalizeStoredPath($rawPath);

        $base = [
            'id' => $id,
            'kind' => $kind,
            'table' => $table,
            'record_id' => $recordId,
            'column' => $column,
            'json_path' => $jsonPath,
            'owner_id' => $ownerId,
            'owner_label' => $ownerLabel,
            'old_path_raw' => $rawPath,
            'old_path' => $normalized,
            'new_path' => $newPath,
            'source_disk' => null,
            'destination_disk' => $destinationDisk,
            'status' => 'invalid',
            'source_size' => null,
            'note' => null,
        ];

        if ($rawPath === '') {
            $base['status'] = 'empty';
            $base['note'] = 'The database path is empty.';
            return $base;
        }

        if ($this->paths->isExternalReference($rawPath)) {
            $base['status'] = 'external';
            $base['note'] = 'External URL; no local file migration is required.';
            return $base;
        }

        if (! $normalized) {
            $base['status'] = 'invalid';
            $base['note'] = 'The path could not be normalized safely.';
            return $base;
        }

        $location = $this->locateSource($normalized, $preferredSourceDisks);
        $base['source_disk'] = $location['disk'];
        $base['source_size'] = $location['size'];
        $base['note'] = $location['note'];

        if ($location['status'] !== 'ready') {
            $base['status'] = $location['status'];
            return $base;
        }

        if ($location['disk'] === $destinationDisk && $normalized === $newPath) {
            $base['status'] = $rawPath === $newPath ? 'already_organized' : 'ready';
            $base['note'] = $rawPath === $newPath
                ? 'The file and database path are already organized.'
                : 'The file is already at the destination; only the database path format needs normalization.';
            return $base;
        }

        $base['status'] = 'ready';

        return $base;
    }

    protected function invalidOwnerEntry(
        string $kind,
        string $table,
        int $recordId,
        string $column,
        string $rawPath,
        ?int $ownerId,
        string $note
    ): array {
        return [
            'id' => $kind.':'.$recordId.':'.$column,
            'kind' => $kind,
            'table' => $table,
            'record_id' => $recordId,
            'column' => $column,
            'json_path' => null,
            'owner_id' => $ownerId,
            'owner_label' => '',
            'old_path_raw' => $rawPath,
            'old_path' => $this->paths->normalizeStoredPath($rawPath),
            'new_path' => null,
            'source_disk' => null,
            'destination_disk' => null,
            'status' => 'invalid',
            'source_size' => null,
            'note' => $note,
        ];
    }

    /** @return array{status:string,disk:?string,size:?int,note:?string} */
    protected function locateSource(string $path, array $preferredDisks): array
    {
        $disks = array_values(array_unique(array_filter($preferredDisks)));
        $matches = [];

        foreach ($disks as $disk) {
            try {
                if (Storage::disk($disk)->exists($path)) {
                    $matches[] = [
                        'disk' => $disk,
                        'size' => $this->fileSize($disk, $path),
                    ];
                }
            } catch (\Throwable $e) {
                // Continue checking other configured disks. The final report
                // will show the path as missing if no readable copy is found.
            }
        }

        if ($matches === []) {
            return [
                'status' => 'missing',
                'disk' => null,
                'size' => null,
                'note' => 'No file exists on the expected public or private disk.',
            ];
        }

        if (count($matches) === 1) {
            return [
                'status' => 'ready',
                'disk' => $matches[0]['disk'],
                'size' => $matches[0]['size'],
                'note' => null,
            ];
        }

        $first = $matches[0];
        $allSameSize = collect($matches)->every(fn (array $row): bool => $row['size'] === $first['size']);
        $allSameChecksum = false;

        if ($allSameSize) {
            $checksums = collect($matches)
                ->map(fn (array $row): ?string => $this->checksum($row['disk'], $path))
                ->filter()
                ->unique();
            $allSameChecksum = $checksums->count() === 1;
        }

        if (! $allSameChecksum) {
            return [
                'status' => 'ambiguous',
                'disk' => null,
                'size' => null,
                'note' => 'Different files exist at the same relative path on more than one disk.',
            ];
        }

        return [
            'status' => 'ready',
            'disk' => $first['disk'],
            'size' => $first['size'],
            'note' => 'Identical legacy copies exist on multiple disks; the preferred copy will be used.',
        ];
    }

    protected function copyEntry(array $entry, string $runId, array &$cache): array
    {
        if (($entry['status'] ?? null) === 'external') {
            return $this->operationResult($entry, 'external', $entry['note'] ?? null);
        }

        if (($entry['status'] ?? null) === 'empty') {
            return $this->operationResult($entry, 'skipped_empty', $entry['note'] ?? null);
        }

        if (($entry['status'] ?? null) === 'already_organized') {
            $checksum = $this->checksum($entry['destination_disk'], $entry['new_path']);
            return $this->operationResult($entry, 'already_organized', $entry['note'] ?? null, [
                'source_checksum' => $checksum,
                'destination_checksum' => $checksum,
            ]);
        }

        if (($entry['status'] ?? null) !== 'ready') {
            return $this->operationResult($entry, 'skipped_'.$entry['status'], $entry['note'] ?? null);
        }

        $sourceDisk = $entry['source_disk'];
        $destinationDisk = $entry['destination_disk'];
        $sourcePath = $entry['old_path'];
        $destinationPath = $entry['new_path'];
        $transferKey = implode('|', [$sourceDisk, $sourcePath, $destinationDisk, $destinationPath]);

        if (isset($cache[$transferKey])) {
            return $this->operationResult($entry, $cache[$transferKey]['result'], 'Reused the result of an identical file transfer.', $cache[$transferKey]);
        }

        if (! $sourceDisk || ! $sourcePath || ! $destinationDisk || ! $destinationPath) {
            return $this->operationResult($entry, 'failed', 'The source or destination path is incomplete.');
        }

        if (! Storage::disk($sourceDisk)->exists($sourcePath)) {
            return $this->operationResult($entry, 'failed', 'The source file no longer exists.');
        }

        $sourceChecksum = $this->checksum($sourceDisk, $sourcePath);
        if (! $sourceChecksum) {
            return $this->operationResult($entry, 'failed', 'The source checksum could not be calculated.');
        }

        if (Storage::disk($destinationDisk)->exists($destinationPath)) {
            $destinationChecksum = $this->checksum($destinationDisk, $destinationPath);

            if ($destinationChecksum === $sourceChecksum) {
                $cache[$transferKey] = [
                    'result' => 'already_copied',
                    'source_checksum' => $sourceChecksum,
                    'destination_checksum' => $destinationChecksum,
                ];

                return $this->operationResult($entry, 'already_copied', 'An identical destination file already exists.', $cache[$transferKey]);
            }

            return $this->operationResult($entry, 'conflict', 'A different file already exists at the destination. The file was not overwritten.');
        }

        $temporaryPath = $destinationPath.'.part-'.$runId;
        Storage::disk($destinationDisk)->delete($temporaryPath);

        $stream = Storage::disk($sourceDisk)->readStream($sourcePath);
        if ($stream === false) {
            return $this->operationResult($entry, 'failed', 'The source file could not be opened.');
        }

        try {
            $written = Storage::disk($destinationDisk)->writeStream($temporaryPath, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if (! $written) {
            Storage::disk($destinationDisk)->delete($temporaryPath);
            return $this->operationResult($entry, 'failed', 'The temporary destination file could not be written.');
        }

        $temporaryChecksum = $this->checksum($destinationDisk, $temporaryPath);
        if ($temporaryChecksum !== $sourceChecksum) {
            Storage::disk($destinationDisk)->delete($temporaryPath);
            return $this->operationResult($entry, 'failed', 'The copied file checksum does not match the source.');
        }

        if (! Storage::disk($destinationDisk)->move($temporaryPath, $destinationPath)) {
            Storage::disk($destinationDisk)->delete($temporaryPath);
            return $this->operationResult($entry, 'failed', 'The verified temporary file could not be moved into place.');
        }

        $destinationChecksum = $this->checksum($destinationDisk, $destinationPath);
        $cache[$transferKey] = [
            'result' => 'copied',
            'source_checksum' => $sourceChecksum,
            'destination_checksum' => $destinationChecksum,
        ];

        return $this->operationResult($entry, 'copied', null, $cache[$transferKey]);
    }

    protected function verifyEntry(array $entry, array &$checksumCache): array
    {
        if (($entry['status'] ?? null) === 'external') {
            return $this->operationResult($entry, 'external', $entry['note'] ?? null);
        }

        if (($entry['status'] ?? null) === 'empty') {
            return $this->operationResult($entry, 'skipped_empty', $entry['note'] ?? null);
        }

        if (! in_array($entry['status'] ?? null, ['ready', 'already_organized'], true)) {
            return $this->operationResult($entry, 'failed_'.$entry['status'], $entry['note'] ?? null);
        }

        $sourceDisk = $entry['source_disk'] ?: $entry['destination_disk'];
        $destinationDisk = $entry['destination_disk'];
        $sourcePath = $entry['old_path'];
        $destinationPath = $entry['new_path'];

        if (! $sourceDisk || ! $sourcePath || ! $destinationDisk || ! $destinationPath) {
            return $this->operationResult($entry, 'failed', 'The source or destination path is incomplete.');
        }

        if (! Storage::disk($sourceDisk)->exists($sourcePath)) {
            return $this->operationResult($entry, 'failed', 'The source file is missing.');
        }

        if (! Storage::disk($destinationDisk)->exists($destinationPath)) {
            return $this->operationResult($entry, 'failed', 'The destination file is missing.');
        }

        $sourceKey = $sourceDisk.'|'.$sourcePath;
        $destinationKey = $destinationDisk.'|'.$destinationPath;
        $sourceChecksum = $checksumCache[$sourceKey] ??= $this->checksum($sourceDisk, $sourcePath);
        $destinationChecksum = $checksumCache[$destinationKey] ??= $this->checksum($destinationDisk, $destinationPath);
        $sourceSize = $this->fileSize($sourceDisk, $sourcePath);
        $destinationSize = $this->fileSize($destinationDisk, $destinationPath);

        if (! $sourceChecksum || ! $destinationChecksum) {
            return $this->operationResult($entry, 'failed', 'A checksum could not be calculated.', [
                'source_size' => $sourceSize,
                'destination_size' => $destinationSize,
            ]);
        }

        if ($sourceSize !== $destinationSize || $sourceChecksum !== $destinationChecksum) {
            return $this->operationResult($entry, 'failed', 'Source and destination do not match.', [
                'source_size' => $sourceSize,
                'destination_size' => $destinationSize,
                'source_checksum' => $sourceChecksum,
                'destination_checksum' => $destinationChecksum,
            ]);
        }

        return $this->operationResult(
            $entry,
            ($entry['status'] ?? null) === 'already_organized' ? 'already_organized' : 'verified',
            null,
            [
                'source_size' => $sourceSize,
                'destination_size' => $destinationSize,
                'source_checksum' => $sourceChecksum,
                'destination_checksum' => $destinationChecksum,
            ]
        );
    }

    protected function updateDatabaseReference(array $entry, mixed $expected, mixed $replacement): array
    {
        $table = (string) ($entry['table'] ?? '');
        $recordId = (int) ($entry['record_id'] ?? 0);
        $column = (string) ($entry['column'] ?? '');
        $jsonPath = $entry['json_path'] ?? null;

        if ($table === '' || $recordId <= 0 || $column === '') {
            throw new RuntimeException('The manifest contains an invalid database target.');
        }

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            throw new RuntimeException("Database target {$table}.{$column} is missing.");
        }

        $row = DB::table($table)->where('id', $recordId)->first([$column]);
        if (! $row) {
            throw new RuntimeException("Database row {$table} #{$recordId} no longer exists.");
        }

        if ($jsonPath) {
            $document = $this->decodeJsonDocument($row->{$column} ?? null);
            $current = data_get($document, $jsonPath);

            if ($current === $replacement) {
                return $this->operationResult($entry, 'already_updated', 'The database already contains the requested path.');
            }

            if ($current !== $expected) {
                throw new RuntimeException('The JSON media reference changed after the dry run; it was not overwritten.');
            }

            data_set($document, $jsonPath, $replacement);
            $update = [$column => json_encode($document, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)];
        } else {
            $current = $row->{$column} ?? null;

            if ($current === $replacement) {
                return $this->operationResult($entry, 'already_updated', 'The database already contains the requested path.');
            }

            if ($current !== $expected) {
                throw new RuntimeException('The media reference changed after the dry run; it was not overwritten.');
            }

            $update = [$column => $replacement];
        }

        if (Schema::hasColumn($table, 'updated_at')) {
            $update['updated_at'] = now();
        }

        DB::table($table)->where('id', $recordId)->update($update);

        return $this->operationResult($entry, 'updated', null);
    }

    protected function decodeJsonDocument(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function operationResult(array $entry, string $result, ?string $note = null, array $extra = []): array
    {
        return array_merge([
            'id' => $entry['id'] ?? null,
            'kind' => $entry['kind'] ?? null,
            'table' => $entry['table'] ?? null,
            'record_id' => $entry['record_id'] ?? null,
            'column' => $entry['column'] ?? null,
            'json_path' => $entry['json_path'] ?? null,
            'old_path' => $entry['old_path'] ?? null,
            'new_path' => $entry['new_path'] ?? null,
            'source_disk' => $entry['source_disk'] ?? null,
            'destination_disk' => $entry['destination_disk'] ?? null,
            'result' => $result,
            'source_size' => null,
            'destination_size' => null,
            'source_checksum' => null,
            'destination_checksum' => null,
            'note' => $note,
        ], $extra);
    }

    protected function summarizeEntries(array $entries): array
    {
        $summary = ['total' => count($entries)];

        foreach ($entries as $entry) {
            $key = (string) ($entry['status'] ?? 'unknown');
            $summary[$key] = ($summary[$key] ?? 0) + 1;
        }

        ksort($summary);
        $summary = ['total' => count($entries)] + $summary;

        return $summary;
    }

    protected function summarizeOperationResults(array $results): array
    {
        $summary = ['total' => count($results)];

        foreach ($results as $result) {
            $key = (string) ($result['result'] ?? 'unknown');
            $summary[$key] = ($summary[$key] ?? 0) + 1;
        }

        ksort($summary);
        $summary = ['total' => count($results)] + $summary;

        return $summary;
    }

    protected function duplicateReferenceRows(array $entries): array
    {
        return collect($entries)
            ->filter(fn (array $entry): bool => filled($entry['source_disk'] ?? null) && filled($entry['old_path'] ?? null))
            ->groupBy(fn (array $entry): string => $entry['source_disk'].'|'.$entry['old_path'])
            ->filter(fn ($group): bool => $group->count() > 1)
            ->map(function ($group): array {
                $first = $group->first();

                return [
                    'source_disk' => $first['source_disk'],
                    'old_path' => $first['old_path'],
                    'reference_count' => $group->count(),
                    'entry_ids' => $group->pluck('id')->implode(' | '),
                    'kinds' => $group->pluck('kind')->unique()->implode(' | '),
                ];
            })
            ->values()
            ->all();
    }

    protected function unreferencedLegacyFiles(array $entries): array
    {
        $referenced = collect($entries)
            ->filter(fn (array $entry): bool => filled($entry['source_disk'] ?? null) && filled($entry['old_path'] ?? null))
            ->groupBy('source_disk')
            ->map(fn ($group) => $group->pluck('old_path')->flip());

        $rows = [];
        $rootsByDisk = (array) config('media.legacy_roots', []);

        foreach ($rootsByDisk as $diskAlias => $roots) {
            $disk = $diskAlias === 'public' ? $this->paths->publicDisk() : $this->paths->privateDisk();
            $referencedOnDisk = $referenced->get($disk, collect());

            foreach ((array) $roots as $root) {
                try {
                    foreach (Storage::disk($disk)->allFiles((string) $root) as $path) {
                        if (Str::startsWith($path, [
                            trim((string) config('media.public_root', 'media'), '/').'/',
                            $this->paths->migrationReportsDirectory().'/',
                        ])) {
                            continue;
                        }

                        if ($referencedOnDisk->has($path)) {
                            continue;
                        }

                        $rows[$disk.'|'.$path] = [
                            'disk' => $disk,
                            'path' => $path,
                            'size' => $this->fileSize($disk, $path),
                        ];
                    }
                } catch (\Throwable $e) {
                    // A missing legacy root is normal and should not fail the run.
                }
            }
        }

        ksort($rows);

        return array_values($rows);
    }

    protected function loadManifest(string $runId): array
    {
        $runId = $this->validateRunId($runId);
        $manifest = $this->loadJson($this->runDirectory($runId).'/manifest.json');

        if (! is_array($manifest) || ($manifest['version'] ?? null) !== self::MANIFEST_VERSION) {
            throw new RuntimeException('The media migration manifest is missing or has an unsupported version.');
        }

        return $manifest;
    }

    protected function validateRunId(string $runId): string
    {
        $runId = trim($runId);

        if ($runId === '' || ! preg_match('/^[A-Za-z0-9._-]+$/', $runId)) {
            throw new RuntimeException('The media migration run ID is invalid.');
        }

        return $runId;
    }

    protected function runDirectory(string $runId): string
    {
        return $this->paths->migrationReportsDirectory().'/'.$this->validateRunId($runId);
    }

    protected function putJson(string $path, array $payload): void
    {
        $json = json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        if (! Storage::disk($this->paths->privateDisk())->put($path, $json)) {
            throw new RuntimeException("Could not write report: {$path}");
        }
    }

    protected function loadJson(string $path): ?array
    {
        if (! Storage::disk($this->paths->privateDisk())->exists($path)) {
            return null;
        }

        $decoded = json_decode(
            Storage::disk($this->paths->privateDisk())->get($path),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        return is_array($decoded) ? $decoded : null;
    }

    protected function putCsv(string $path, array $rows, array $columns): void
    {
        $handle = fopen('php://temp', 'w+');
        if ($handle === false) {
            throw new RuntimeException('Could not create the CSV report stream.');
        }

        fputcsv($handle, $columns);

        foreach ($rows as $row) {
            $values = [];
            foreach ($columns as $column) {
                $value = $row[$column] ?? null;
                $values[] = is_scalar($value) || $value === null
                    ? $value
                    : json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            fputcsv($handle, $values);
        }

        rewind($handle);
        $contents = stream_get_contents($handle);
        fclose($handle);

        if (! is_string($contents) || ! Storage::disk($this->paths->privateDisk())->put($path, $contents)) {
            throw new RuntimeException("Could not write report: {$path}");
        }
    }

    protected function fileSize(string $disk, string $path): ?int
    {
        try {
            return (int) Storage::disk($disk)->size($path);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function checksum(string $disk, string $path): ?string
    {
        try {
            $filesystem = Storage::disk($disk);

            if (method_exists($filesystem, 'path')) {
                try {
                    $absolutePath = $filesystem->path($path);
                    if (is_file($absolutePath)) {
                        $hash = hash_file('sha256', $absolutePath);
                        return is_string($hash) ? $hash : null;
                    }
                } catch (\Throwable) {
                    // Remote disks may not expose a local absolute path.
                }
            }

            $stream = $filesystem->readStream($path);
            if ($stream === false) {
                return null;
            }

            try {
                $context = hash_init('sha256');
                hash_update_stream($context, $stream);
                return hash_final($context);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        } catch (\Throwable $e) {
            return null;
        }
    }
}
