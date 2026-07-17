<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('categories')) {
            return;
        }

        $hasManualImage = Schema::hasColumn('categories', 'image_path');
        $hasCollageImage = Schema::hasColumn('categories', 'collage_image_path');

        if ($hasManualImage || $hasCollageImage) {
            $columns = ['id'];

            if ($hasManualImage) {
                $columns[] = 'image_path';
            }

            if ($hasCollageImage) {
                $columns[] = 'collage_image_path';
            }

            DB::table('categories')
                ->select($columns)
                ->orderBy('id')
                ->chunkById(100, function ($categories) use ($hasManualImage, $hasCollageImage): void {
                    foreach ($categories as $category) {
                        $paths = [];

                        if ($hasManualImage) {
                            $paths[] = $category->image_path ?? null;
                        }

                        if ($hasCollageImage) {
                            $paths[] = $category->collage_image_path ?? null;
                        }

                        foreach (array_filter($paths) as $path) {
                            $path = ltrim((string) $path, '/');

                            if (
                                str_starts_with($path, 'category-images/')
                                || str_starts_with($path, 'category-collages/')
                            ) {
                                Storage::disk('public')->delete($path);
                            }
                        }
                    }
                }, 'id');

            Storage::disk('public')->deleteDirectory('category-images');
            Storage::disk('public')->deleteDirectory('category-collages');
        }

        $dropColumns = array_values(array_filter([
            Schema::hasColumn('categories', 'collage_updated_at') ? 'collage_updated_at' : null,
            $hasCollageImage ? 'collage_image_path' : null,
            $hasManualImage ? 'image_path' : null,
        ]));

        if ($dropColumns !== []) {
            Schema::table('categories', function (Blueprint $table) use ($dropColumns): void {
                $table->dropColumn($dropColumns);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('categories')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table): void {
            if (! Schema::hasColumn('categories', 'image_path')) {
                $table->string('image_path')->nullable()->after('description');
            }

            if (! Schema::hasColumn('categories', 'collage_image_path')) {
                $table->string('collage_image_path')->nullable()->after('image_path');
            }

            if (! Schema::hasColumn('categories', 'collage_updated_at')) {
                $table->timestamp('collage_updated_at')->nullable()->after('collage_image_path');
            }
        });
    }
};
