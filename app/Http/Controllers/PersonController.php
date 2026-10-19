<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\PersonCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PersonController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $category = $request->string('category')->toString();

        return Inertia::render('people/index', [
            'filters' => ['search' => $search, 'category' => $category],
            'categories' => PersonCategory::orderBy('name')->get(['id', 'name', 'slug']),
            'people' => Person::query()
                ->with('categories:id,name')
                ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('trade_name', 'like', "%{$search}%")
                        ->orWhere('document', 'like', "%{$search}%");
                }))
                ->when($category, fn ($query) => $query->whereHas(
                    'categories',
                    fn ($query) => $query->where('slug', $category),
                ))
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }
}
