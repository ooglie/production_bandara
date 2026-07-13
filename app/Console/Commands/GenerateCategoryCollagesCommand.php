<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Services\CategoryCollageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class GenerateCategoryCollagesCommand extends Command
{
    protected $signature = 'bandara:generate-category-collages
                            {category? : Optional category ID or slug}
                            {--category= : Optional category ID or slug}
                            {--limit=6 : Product images per collage, maximum 9}
                            {--force : Regenerate even when a collage already exists}';

    protected $description = 'Generate category collage images from product images';

    public function handle(CategoryCollageService $collages): int
    {
        if (! Schema::hasColumn('categories', 'collage_image_path')) {
            $this->error('categories.collage_image_path is missing. Run php artisan migrate first.');
            return self::FAILURE;
        }

        $selector = $this->option('category') ?: $this->argument('category');
        $limit = max(1, min(9, (int) $this->option('limit')));
        $force = (bool) $this->option('force');

        $query = Category::query()->orderBy('name');

        if (filled($selector)) {
            $query->where(function ($query) use ($selector) {
                if (is_numeric($selector)) {
                    $query->where('id', (int) $selector);
                }

                $query->orWhere('slug', (string) $selector)
                    ->orWhere('name', (string) $selector);
            });
        }

        $categories = $query->get();

        if ($categories->isEmpty()) {
            $this->error('No matching categories found.');
            return self::FAILURE;
        }

        $rows = [];
        $failed = 0;

        foreach ($categories as $category) {
            try {
                $result = $collages->generate($category, $limit, $force || filled($selector));
                $rows[] = [
                    $category->id,
                    $category->name,
                    $result['status'],
                    $result['image_count'],
                    $result['path'] ?? '—',
                ];
            } catch (\Throwable $e) {
                $failed++;
                $rows[] = [
                    $category->id,
                    $category->name,
                    'failed',
                    0,
                    $e->getMessage(),
                ];
            }
        }

        $this->table(['ID', 'Category', 'Status', 'Images', 'Path / message'], $rows);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
