<?php

namespace App\Http\Controllers;
use App\Models\Announcement;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

use App\Models\ProductCollection;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $occasionCollections = ProductCollection::homeSection('occasions')
            ->take(3)
            ->get();

        $chefCollection = ProductCollection::homeSection('chef_picks')
            ->first();

        $recipeFeatureProduct = null;

        if ($chefCollection) {
            $recipeFeatureProduct = $chefCollection->products()
                ->with([
                    'images',
                    'activeRecipes' => function ($q) {
                        $q->inRandomOrder();
                    },
                ])
                ->whereHas('activeRecipes')
                ->inRandomOrder()
                ->first();
        }

        $featuredProducts = Product::with('images')
            ->featured()
            ->limit(8)
            ->get();

        $newProducts = Product::with('images')
            ->new()
            ->limit(8)
            ->get();

        $specialProducts = Product::with('images')
            ->special()
            ->limit(8)
            ->get();

        $topCategories = Category::query()
            ->where('is_active', true)
            ->withCount('products')
            ->orderByDesc('products_count')
            ->orderBy('name')
            ->limit(8)
            ->get();

        $homeAnnouncement = Announcement::activeForHome()->first();

        $recipeProducts = Product::with(['images', 'activeRecipes'])
            ->whereHas('activeRecipes')
            ->orderByDesc('is_featured')
            ->latest()
            ->take(3)
            ->get();
        
        $recipeFeatureProduct = Product::with([
            'images',
            'activeRecipes' => function ($q) {
                $q->inRandomOrder();
            },
        ])
            ->whereHas('activeRecipes')
            ->inRandomOrder()
            ->first();

        return view('home', compact(
            'featuredProducts',
            'newProducts',
            'specialProducts',
            'topCategories',
            'homeAnnouncement',
            'occasionCollections',
            'chefCollection',
            'recipeProducts',
            'recipeFeatureProduct'
        ));

        
    }
}
