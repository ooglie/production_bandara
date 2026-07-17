<?php

namespace App\Http\Controllers;

use App\Models\ProductCollection;
use App\Services\PricingService;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function show(Request $request, ProductCollection $collection)
    {
        abort_unless($collection->is_active, 404);

        $productsQuery = $collection->products()
            ->with('images');

        app(PricingService::class)->applyProductAvailabilityFilter(
            $productsQuery->getQuery(),
            $request->user()
        );

        $products = $productsQuery
            ->paginate(24)
            ->withQueryString();

        return view('collections.show', compact('collection', 'products'));
    }
}