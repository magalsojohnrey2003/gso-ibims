<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        // Get only PPE categories (parent_id is null)
        $cats = Category::whereNull('parent_id')->orderBy('name')->get(['id', 'name', 'category_code', 'parent_id']);
        return response()->json(['data' => $cats]);
    }

    /**
     * Get all GLA sub-categories for a specific PPE category.
     */
    public function getGLAs($categoryId)
    {
        $category = Category::find($categoryId);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $glas = Category::where('parent_id', $categoryId)
            ->orderBy('name')
            ->get(['id', 'name', 'category_code', 'parent_id']);
        
        return response()->json(['data' => $glas]);
    }

    /**
     * Store a new GLA under a specific PPE category.
     */
    public function storeGLA(Request $request, $categoryId)
    {
        $category = Category::find($categoryId);
        if (!$category) {
            return response()->json(['message' => 'Parent category not found'], 404);
        }

        $data = $request->validate([
            'name' => ['required','string','max:255','unique:categories,name','regex:/^[A-Za-z\s\-\_]+$/'],
            'category_code' => ['required','regex:/^\d{1,4}$/','unique:categories,category_code'],
        ], [
            'name.regex' => 'GLA name may contain only letters, spaces, hyphens or underscores (no digits).',
            'category_code.regex' => 'GLA code must be 1-4 digits.',
        ]);

        $gla = Category::create([
            'name' => $data['name'],
            'category_code' => $data['category_code'],
            'parent_id' => $categoryId,
        ]);

        return response()->json(['data' => $gla], 201);
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

        // If deleting a PPE, check if it has GLAs
        if ($cat->isPPE() && $cat->children()->count() > 0) {
            return response()->json(['message' => 'Cannot delete category with GLA sub-categories'], 409);
        }

        $cat->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * Delete a specific GLA by ID.
     */
    public function destroyGLA($categoryId, $glaId)
    {
        $gla = Category::where('id', $glaId)
            ->where('parent_id', $categoryId)
            ->first();
        
        if (!$gla) {
            return response()->json(['message' => 'GLA not found'], 404);
        }

        // Check if any items use this GLA
        if (\App\Models\Item::where('category', $gla->name)->exists()) {
            return response()->json(['message' => 'GLA in use by items'], 409);
        }

        $gla->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
