<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Chef;
use Illuminate\Contracts\View\View;

class ChefController extends Controller
{
    public function index(): View
    {
        $chefs = Chef::query()
            ->published()
            ->orderBy('sort_order')
            ->orderBy('display_name')
            ->paginate(12)
            ->withQueryString();

        return view('storefront.kitchen.chefs.index', compact('chefs'));
    }

    public function show(Chef $chef): View
    {
        abort_unless($chef->isPublished(), 404);

        $chef->load([
            'featuredRecipe' => fn ($query) => $query->where('is_active', true),
            'recipes' => fn ($query) => $query
                ->where('recipes.is_active', true),
        ]);

        $relatedChefs = Chef::query()
            ->published()
            ->where('id', '<>', $chef->getKey())
            ->orderBy('sort_order')
            ->orderBy('display_name')
            ->limit(3)
            ->get();

        return view('storefront.kitchen.chefs.show', compact('chef', 'relatedChefs'));
    }
}
