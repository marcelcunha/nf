<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('products/index', [
            'filters' => ['search' => $search],
            'products' => Product::query()
                ->with('supplier:id,name')
                ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('ean', 'like', "%{$search}%");
                }))
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }
}
