<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class BandaraWeeklyRecipe
{
    private bool $resolved = false;

    private ?Model $recipe = null;

    public function current(): ?Model
    {
        if ($this->resolved) {
            return $this->recipe;
        }

        $this->resolved = true;
        $this->recipe = $this->resolveRecipe();

        return $this->recipe;
    }

    private function resolveRecipe(): ?Model
    {
        $modelClass = 'App\\Models\\Recipe';

        if (! class_exists($modelClass)) {
            return null;
        }

        try {
            /** @var Model $model */
            $model = new $modelClass();
            $table = $model->getTable();

            if (! Schema::hasTable($table)) {
                return null;
            }

            $query = $modelClass::query();

            if (Schema::hasColumn($table, 'is_active')) {
                $query->where('is_active', true);
            }

            if (Schema::hasColumn($table, 'is_published')) {
                $query->where('is_published', true);
            }

            if (Schema::hasColumn($table, 'published_at')) {
                $query->where(function ($published): void {
                    $published->whereNull('published_at')
                        ->orWhere('published_at', '<=', now());
                });
            }

            $count = (clone $query)->count();

            if ($count < 1) {
                return null;
            }

            $week = CarbonImmutable::now('Asia/Kolkata')
                ->startOfWeek(CarbonInterface::MONDAY)
                ->format('o-\\WW');

            $unsignedSeed = (int) sprintf('%u', crc32((string) config('app.key').'|'.$week));
            $offset = $unsignedSeed % $count;

            return $query
                ->orderBy($model->getQualifiedKeyName())
                ->offset($offset)
                ->first();
        } catch (Throwable) {
            // The homepage must continue rendering even if the optional recipe feature
            // is unavailable or the project schema differs from the expected shape.
            return null;
        }
    }
}
