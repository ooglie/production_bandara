<?php

namespace App\Providers;

use App\Support\BandaraWeeklyRecipe;
use Illuminate\Contracts\Pagination\Paginator as PaginatorContract;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

final class BandaraLaunchUiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(BandaraWeeklyRecipe::class);
    }

    public function boot(): void
    {
        View::composer('*', static function ($view): void {
            if (app()->runningInConsole() || request()->is('admin*')) {
                return;
            }

            $isHomepage = request()->is('/') || request()->path() === '/';

            if (! $isHomepage) {
                return;
            }

            $viewName = strtolower((string) $view->name());

            if (str_contains($viewName, 'email') || str_contains($viewName, 'mail.')) {
                return;
            }

            $data = $view->getData();
            $singleRecipeKeys = [
                'recipe',
                'featuredRecipe',
                'featured_recipe',
                'randomRecipe',
                'random_recipe',
                'recipeOfTheWeek',
                'recipe_of_the_week',
                'recipeOfTheDay',
                'recipe_of_the_day',
                'homeRecipe',
                'home_recipe',
            ];
            $recipeCollectionKeys = [
                'recipes',
                'featuredRecipes',
                'featured_recipes',
                'homeRecipes',
                'home_recipes',
                'chefRecipes',
                'chef_recipes',
                'recipePicks',
                'recipe_picks',
                'dailyRecipes',
                'daily_recipes',
                'recipesForToday',
                'recipes_for_today',
                'recipeCards',
                'recipe_cards',
                'selectedRecipes',
                'selected_recipes',
                'kitchenRecipes',
                'kitchen_recipes',
            ];
            $allRecipeKeys = array_merge($singleRecipeKeys, $recipeCollectionKeys);
            $hasRecipeData = collect($allRecipeKeys)
                ->contains(static fn (string $key): bool => array_key_exists($key, $data));
            $looksLikeHomeView = in_array($viewName, ['home', 'home.index', 'welcome'], true)
                || str_ends_with($viewName, '.home')
                || str_ends_with($viewName, '.home.index')
                || str_ends_with($viewName, '.welcome');
            $looksLikeRecipeView = str_contains($viewName, 'recipe')
                || str_contains($viewName, 'chef-picks')
                || str_contains($viewName, 'chef_picks')
                || str_contains($viewName, 'bandara-kitchen')
                || str_contains($viewName, 'bandara_kitchen');

            if (! $hasRecipeData && ! $looksLikeHomeView && ! $looksLikeRecipeView) {
                return;
            }

            /** @var BandaraWeeklyRecipe $dailyRecipes */
            $dailyRecipes = app(BandaraWeeklyRecipe::class);
            $recipes = $dailyRecipes->currentSet();
            $displayRecipe = $dailyRecipes->current();

            if (! $displayRecipe instanceof Model) {
                return;
            }

            $dailyCount = $recipes->count();

            // v1.2 changed the database-backed homepage recipe limit to one. Do not
            // write to the database again: adjust only the section object supplied to
            // the current view so this request may render the complete daily set.
            foreach (['section', 'homeSection', 'home_section'] as $sectionKey) {
                if (! array_key_exists($sectionKey, $data)) {
                    continue;
                }

                $section = $data[$sectionKey];

                if (is_array($section)) {
                    $settings = is_array($section['settings'] ?? null) ? $section['settings'] : [];
                    $settings['recipe_limit'] = $dailyCount;
                    $section['settings'] = $settings;
                    $view->with($sectionKey, $section);
                    continue;
                }

                if (! is_object($section)) {
                    continue;
                }

                try {
                    $settings = $section->settings ?? null;

                    if (is_string($settings)) {
                        $decoded = json_decode($settings, true);
                        $settings = is_array($decoded) ? $decoded : [];
                    }

                    $settings = is_array($settings) ? $settings : [];
                    $settings['recipe_limit'] = $dailyCount;

                    if (method_exists($section, 'setAttribute')) {
                        $section->setAttribute('settings', $settings);
                    } else {
                        $section->settings = $settings;
                    }

                    $view->with($sectionKey, $section);
                } catch (Throwable) {
                    // A custom immutable section object is allowed; the recipe aliases
                    // and collection replacements below still apply.
                }
            }

            foreach (['recipeLimit', 'recipe_limit', 'recipesLimit', 'recipes_limit'] as $limitKey) {
                if (array_key_exists($limitKey, $data) || $looksLikeRecipeView) {
                    $view->with($limitKey, $dailyCount);
                }
            }

            foreach ($singleRecipeKeys as $key) {
                if (array_key_exists($key, $data)) {
                    $view->with($key, $displayRecipe);
                }
            }

            foreach ($recipeCollectionKeys as $key) {
                if (! array_key_exists($key, $data)) {
                    continue;
                }

                self::replaceRecipeCollection($view, $key, $data[$key], $recipes);
            }

            // Catch project-specific variable names by inspecting values, but only on
            // the homepage/recipe partials already identified above.
            foreach ($data as $key => $existing) {
                if (in_array((string) $key, $allRecipeKeys, true)) {
                    continue;
                }

                if ($existing instanceof Model && is_a($existing, 'App\\Models\\Recipe')) {
                    $view->with((string) $key, $displayRecipe);
                    continue;
                }

                if ($existing instanceof EloquentCollection || $existing instanceof Collection) {
                    $firstExisting = $existing->first();

                    if ($firstExisting instanceof Model && is_a($firstExisting, 'App\\Models\\Recipe')) {
                        self::replaceRecipeCollection($view, (string) $key, $existing, $recipes);
                    }
                }
            }

            // Stable aliases for the homepage and all nested recipe partials.
            $view->with('dailyRecipes', $recipes);
            $view->with('recipesForToday', $recipes);
            $view->with('recipeOfTheDay', $displayRecipe);
            $view->with('recipeOfTheWeek', $displayRecipe);
            $view->with('dailyRecipeCount', $dailyCount);
        });
    }

    /**
     * @param mixed $existing
     * @param EloquentCollection<int, Model> $recipes
     */
    private static function replaceRecipeCollection($view, string $key, mixed $existing, EloquentCollection $recipes): void
    {
        if ($existing instanceof PaginatorContract && method_exists($existing, 'setCollection')) {
            $existing->setCollection(collect($recipes->all()));
            $view->with($key, $existing);
        } elseif ($existing instanceof EloquentCollection) {
            $view->with($key, $recipes);
        } elseif ($existing instanceof Collection) {
            $view->with($key, collect($recipes->all()));
        } elseif (is_array($existing)) {
            $view->with($key, $recipes->all());
        }
    }
}
