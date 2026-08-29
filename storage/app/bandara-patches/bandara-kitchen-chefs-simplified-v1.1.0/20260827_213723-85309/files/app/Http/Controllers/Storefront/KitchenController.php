<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Chef;
use Illuminate\Contracts\View\View;

class KitchenController extends Controller
{
    public function index(): View
    {
        $featuredChef = Chef::query()
            ->homepageFeatured()
            ->with([
                'featuredRecipe' => fn ($query) => $query->where('is_active', true),
                'recipes' => fn ($query) => $query
                    ->where('recipes.is_active', true)
                    ->limit(3),
            ])
            ->orderByDesc('updated_at')
            ->first();

        $chefs = Chef::query()
            ->published()
            ->when($featuredChef, fn ($query) => $query->where('id', '<>', $featuredChef->getKey()))
            ->orderBy('sort_order')
            ->orderBy('display_name')
            ->limit(6)
            ->get();

        return view('storefront.kitchen.index', compact('featuredChef', 'chefs'));
    }
}
