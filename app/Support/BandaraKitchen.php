<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class BandaraKitchen
{
    public static function localizedText(mixed $value, ?string $locale = null): string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed === '') {
                return '';
            }

            if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
                $decoded = json_decode($trimmed, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    return self::localizedText($decoded, $locale);
                }
            }

            return $trimmed;
        }

        if (is_scalar($value)) {
            return trim((string) $value);
        }

        if (! is_array($value)) {
            return '';
        }

        $locale ??= app()->getLocale();

        foreach (array_unique([$locale, 'en']) as $candidate) {
            if (array_key_exists($candidate, $value)) {
                $resolved = self::localizedText($value[$candidate], $locale);

                if ($resolved !== '') {
                    return $resolved;
                }
            }
        }

        foreach ($value as $candidate) {
            $resolved = self::localizedText($candidate, $locale);

            if ($resolved !== '') {
                return $resolved;
            }
        }

        return '';
    }

    public static function recipeTitle(Model $recipe): string
    {
        $title = self::localizedText($recipe->getAttribute('title'));

        return $title !== '' ? $title : 'Recipe';
    }

    public static function recipeSlug(Model $recipe): string
    {
        $slug = self::localizedText($recipe->getAttribute('slug'));

        return $slug !== '' ? $slug : (string) $recipe->getKey();
    }

    public static function recipeUrl(Model $recipe): string
    {
        $slug = self::recipeSlug($recipe);
        $routeNames = [
            'recipes.show',
            'recipe.show',
            'storefront.recipes.show',
            'storefront.recipe.show',
        ];

        foreach ($routeNames as $routeName) {
            if (! Route::has($routeName)) {
                continue;
            }

            foreach ([
                ['recipe' => $recipe],
                ['recipe' => $slug],
                ['slug' => $slug],
                [$slug],
                ['recipe' => $recipe->getKey()],
            ] as $parameters) {
                try {
                    return route($routeName, $parameters);
                } catch (Throwable) {
                    // Try the next compatible route signature.
                }
            }
        }

        return url('/recipes/'.rawurlencode($slug));
    }

    public static function recipeImageUrl(Model $recipe): ?string
    {
        $path = $recipe->getAttribute('image_path');

        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    public static function initials(string $name): string
    {
        return collect(preg_split('/\s+/u', trim($name)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }
}
