<?php

namespace App\Providers;

use App\Console\Commands\BandaraLaunchContentSyncCommand;
use App\Support\BandaraWeeklyRecipe;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

final class BandaraLaunchUiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BandaraWeeklyRecipe::class);
    }

    public function boot(BandaraWeeklyRecipe $weeklyRecipe): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([BandaraLaunchContentSyncCommand::class]);
        }

        View::composer('*', static function ($view) use ($weeklyRecipe): void {
            if (app()->runningInConsole() || ! request()->is('/') || request()->is('admin*')) {
                return;
            }

            $viewName = strtolower((string) $view->name());

            if (
                str_contains($viewName, 'partial')
                || str_contains($viewName, 'component')
                || str_contains($viewName, 'layout')
                || str_contains($viewName, 'email')
            ) {
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
            ];
            $allRecipeKeys = array_merge($singleRecipeKeys, $recipeCollectionKeys);

            $hasRecipeData = collect($allRecipeKeys)
                ->contains(static fn (string $key): bool => array_key_exists($key, $data));

            $looksLikeHomeView = in_array($viewName, ['home', 'home.index', 'welcome'], true)
                || str_ends_with($viewName, '.home')
                || str_ends_with($viewName, '.home.index')
                || str_ends_with($viewName, '.welcome')
                || $hasRecipeData;

            if (! $looksLikeHomeView) {
                return;
            }

            $recipe = $weeklyRecipe->current();

            if ($recipe === null) {
                return;
            }

            foreach ($singleRecipeKeys as $key) {
                if (array_key_exists($key, $data)) {
                    $view->with($key, $recipe);
                }
            }

            foreach ($recipeCollectionKeys as $key) {
                if (! array_key_exists($key, $data)) {
                    continue;
                }

                $existing = $data[$key];

                if ($existing instanceof Collection) {
                    $view->with($key, collect([$recipe]));
                } elseif (is_array($existing)) {
                    $view->with($key, [$recipe]);
                }
            }

            // This additional key is non-breaking and is available to any future
            // homepage partial that explicitly adopts the weekly recipe name.
            $view->with('recipeOfTheWeek', $recipe);
        });
    }
}
