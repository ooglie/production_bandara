<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MediaReferenceService
{
    /** @var array<string,bool> */
    protected array $tableCache = [];

    /** @var array<string,bool> */
    protected array $columnCache = [];

    public function __construct(protected MediaPathService $paths)
    {
    }

    public function deletePublicFileIfUnreferenced(?string $path): bool
    {
        $normalized = $this->paths->normalizeStoredPath($path);

        if (! $normalized || $this->paths->isExternalReference($path)) {
            return false;
        }

        if ($this->isPublicPathReferenced($path)) {
            return false;
        }

        $this->paths->deleteFromDisks($normalized, [
            $this->paths->publicDisk(),
            $this->paths->privateDisk(),
        ]);

        return true;
    }

    public function isPublicPathReferenced(?string $path): bool
    {
        $normalized = $this->paths->normalizeStoredPath($path);

        if (! $normalized) {
            return false;
        }

        $candidates = $this->storedPathCandidates((string) $path, $normalized);

        foreach ([
            ['products', 'primary_image'],
            ['product_images', 'file_path'],
            ['product_images', 'path'],
            ['recipes', 'image_path'],
            ['recipes', 'video_url'],
            ['users', 'avatar_path'],
            ['home_sections', 'image_path'],
            ['home_sections', 'mobile_image_path'],
            ['home_section_items', 'image_path'],
            ['product_collections', 'image_path'],
            ['announcements', 'background_image_path'],
            ['categories', 'image_path'],
            ['categories', 'collage_image_path'],
            ['ticket_attachments', 'file_path'],
            ['ticket_attachments', 'path'],
        ] as [$table, $column]) {
            if (! $this->hasColumn($table, $column)) {
                continue;
            }

            if (DB::table($table)->whereIn($column, $candidates)->exists()) {
                return true;
            }
        }

        if ($this->hasColumn('home_sections', 'settings')) {
            $escapedSlashPath = str_replace('/', '\\/', $normalized);

            $settingsMatch = DB::table('home_sections')
                ->whereNotNull('settings')
                ->pluck('settings')
                ->contains(static function ($settings) use ($normalized, $escapedSlashPath): bool {
                    $text = is_string($settings) ? $settings : json_encode($settings);

                    return is_string($text)
                        && (str_contains($text, $normalized) || str_contains($text, $escapedSlashPath));
                });

            if ($settingsMatch) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int,string> */
    protected function storedPathCandidates(string $rawPath, string $normalized): array
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        return array_values(array_unique(array_filter([
            trim($rawPath),
            $normalized,
            '/'.$normalized,
            'storage/'.$normalized,
            '/storage/'.$normalized,
            'public/storage/'.$normalized,
            'storage/app/public/'.$normalized,
            'storage/app/private/'.$normalized,
            $baseUrl !== '' ? $baseUrl.'/storage/'.$normalized : null,
        ], static fn ($value): bool => is_string($value) && $value !== '')));
    }

    protected function hasTable(string $table): bool
    {
        return $this->tableCache[$table] ??= Schema::hasTable($table);
    }

    protected function hasColumn(string $table, string $column): bool
    {
        $key = $table.'.'.$column;

        if (array_key_exists($key, $this->columnCache)) {
            return $this->columnCache[$key];
        }

        return $this->columnCache[$key] = $this->hasTable($table) && Schema::hasColumn($table, $column);
    }

}
