<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Resolves a deterministic daily set of recipes for the storefront homepage.
 *
 * The legacy class name is retained so the v1.2 service-provider registration
 * and any existing type hints remain compatible. The selected set is stable for
 * the entire Asia/Kolkata calendar day and changes on the following day.
 */
final class BandaraWeeklyRecipe
{
    private bool $resolved = false;

    /** @var EloquentCollection<int, Model>|null */
    private ?EloquentCollection $recipes = null;

    private bool $currentRecipeResolved = false;

    private ?Model $currentRecipe = null;

    /**
     * @return EloquentCollection<int, Model>
     */
    public function currentSet(): EloquentCollection
    {
        if ($this->resolved) {
            return $this->recipes ?? new EloquentCollection();
        }

        $this->resolved = true;
        $this->recipes = $this->resolveRecipes();

        return $this->recipes;
    }

    public function current(): ?Model
    {
        if ($this->currentRecipeResolved) {
            return $this->currentRecipe;
        }

        $this->currentRecipeResolved = true;
        $recipes = $this->currentSet()->values();
        $count = $recipes->count();

        if ($count < 1) {
            return null;
        }

        try {
            $index = random_int(0, $count - 1);
        } catch (Throwable) {
            $index = 0;
        }

        $recipe = $recipes->get($index);
        $this->currentRecipe = $recipe instanceof Model ? $recipe : null;

        return $this->currentRecipe;
    }

    /**
     * @return EloquentCollection<int, Model>
     */
    private function resolveRecipes(): EloquentCollection
    {
        $empty = new EloquentCollection();
        $modelClass = 'App\\Models\\Recipe';

        if (! class_exists($modelClass)) {
            return $empty;
        }

        try {
            /** @var Model $model */
            $model = new $modelClass();
            $table = $model->getTable();

            if (! Schema::hasTable($table)) {
                return $empty;
            }

            $query = $modelClass::query();

            if (Schema::hasColumn($table, 'is_active')) {
                $query->where('is_active', true);
            }

            if (Schema::hasColumn($table, 'is_published')) {
                $query->where('is_published', true);
            }

            if (Schema::hasColumn($table, 'published_at')) {
                $query->where(static function ($published): void {
                    $published->whereNull('published_at')
                        ->orWhere('published_at', '<=', now());
                });
            }

            $keyName = $model->getKeyName();
            $ids = (clone $query)
                ->orderBy($model->qualifyColumn($keyName))
                ->pluck($keyName)
                ->all();

            $available = count($ids);

            if ($available < 1) {
                return $empty;
            }

            $date = CarbonImmutable::now('Asia/Kolkata')->format('Y-m-d');
            $seed = (string) config('app.key').'|bandara-daily-recipes|'.$date;
            $minimum = min(2, $available);
            $maximum = min(4, $available);
            $range = max(1, $maximum - $minimum + 1);
            $countSeed = hexdec(substr(hash('sha256', $seed.'|count'), 0, 8));
            $desiredCount = $minimum + ($countSeed % $range);

            usort($ids, static function ($left, $right) use ($seed): int {
                $leftHash = hash('sha256', $seed.'|'.(string) $left);
                $rightHash = hash('sha256', $seed.'|'.(string) $right);
                $comparison = strcmp($leftHash, $rightHash);

                if ($comparison !== 0) {
                    return $comparison;
                }

                return strcmp((string) $left, (string) $right);
            });

            $selectedIds = array_slice($ids, 0, $desiredCount);
            $models = (clone $query)->whereKey($selectedIds)->get()->keyBy($keyName);
            $ordered = new EloquentCollection();

            foreach ($selectedIds as $id) {
                $recipe = $models->get($id);

                if ($recipe instanceof Model) {
                    $ordered->push($recipe);
                }
            }

            return $ordered;
        } catch (Throwable) {
            // This enhancement is optional. A schema difference or temporary database
            // issue must never prevent the homepage from rendering normally.
            return $empty;
        }
    }
}
