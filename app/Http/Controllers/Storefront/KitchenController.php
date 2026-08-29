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
