<?php

namespace App\Http\Controllers;

use App\Models\ProductCollection;

class CollectionController extends Controller
{
    public function show(ProductCollection $collection)
    {
        abort_unless($collection->is_active, 404);

        $products = $collection->products()
            ->with('images')
            ->paginate(24)
            ->withQueryString();

        return view('collections.show', compact('collection', 'products'));
    }
}