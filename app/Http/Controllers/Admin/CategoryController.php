<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $cats = Category::orderBy('name')->get(['id', 'name', 'category_code']);
        return response()->json(['data' => $cats]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255','unique:categories,name','regex:/^[A-Za-z\s\-\_]+$/'],
            'category_code' => ['required','digits:4','unique:categories,category_code'],
        ], [
            'name.regex' => 'Category name may contain only letters, spaces, hyphens or underscores (no digits).',
            'category_code.digits' => 'Category code must be exactly 4 digits.',
        ]);

        $cat = Category::create([
            'name' => $data['name'],
            'category_code' => $data['category_code'],
        ]);

        return response()->json(['data' => $cat], 201);
    }

    public function destroy($name)
    {
        // allow deleting by name (encoded in URL). Prevent deletion if used by items.
        $decoded = urldecode($name);
        $cat = is_numeric($decoded) ? Category::find((int) $decoded) : Category::where('name', $decoded)->first();
        if (! $cat) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        // ensure no items reference this category (simple safeguard)
        if (\App\Models\Item::where('category', $cat->name)->exists()) {
            return response()->json(['message' => 'Category in use by items'], 409);
        }

        $cat->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
